<?php
namespace Kiwi;

use mysqli;
use RuntimeException;
use InvalidArgumentException;
use UnexpectedValueException;


register_shutdown_function(__NAMESPACE__ . '\Database::disconnect');


/**
 * Database access and management class
 *
 * @package Kiwi
 */
abstract class Database
{
	/** @var mysqli Mysqli connection handle */
	private static $_handle = null;


	/**
	 * Connect to database using details from config.php
	 *
	 * @param string $host     SQL hostname
	 * @param string $user     SQL username
	 * @param string $password SQL user password
	 * @param string $database SQL database to use
	 */
	final public static function connect($host, $user, $password, $database)
	{
		if (!is_string($host) || !is_string($user) || !is_string($password) || !is_string($database))
			throw new InvalidArgumentException;

		if (empty($host) || empty($user) || empty($database))
			throw new UnexpectedValueException;

		// Already connected!
		if (self::is_connected())
			return;


		self::$_handle = new mysqli($host, $user, $password, $database);

		// Connection failed, bad config might it be...
		if (!self::$_handle)
			throw new DatabaseFailedException;
	}

	/**
	 * Close database connection
	 *
	 * @return bool True on success, false otherwise
	 */
	final public static function disconnect()
	{
		// No existing connections
		if (!self::is_connected())
			return;

		// Unable to close
		if (!self::$_handle->close())
			throw new DatabaseFailedException;
	}


	/**
	 * Execute insert query
	 *
	 * @param string $table Target table
	 * @param array  $data  Data array consisting of field and value
	 *
	 * @return int ID of inserted record on success (positive values only), false on duplicates
	 */
	final public static function insert($table, $data)
	{
		if (!is_string($table) || !is_array($data))
			throw new InvalidArgumentException;

		if (empty($data))
			throw new UnexpectedValueException;

		if (!is_safe_string($table))
			throw new UnexpectedValueException;

		if (!self::is_connected())
			throw new DatabaseNotConnected;


		// Prepare data...
		list ($fields, $placeholders, $values, $format) = self::_prepare_data($data);
		array_unshift($values, $format); // Prepare for bind_param where format is the first param

		// Bad query
		if (!($statement = self::$_handle->prepare("INSERT INTO {$table} ({$fields}) VALUES ({$placeholders})")))
			throw new RuntimeException;


		$handler = [$statement, 'bind_param'];

		// Pass parameters
		if (!is_callable($handler) || !call_user_func_array($handler, array_references($values)))
			throw new RuntimeException;


		// Query failed
		if (!$statement->execute())
		{
			// Duplicate record?
			if ($statement->errno == 1062)
				return false;

			throw new RuntimeException;
		}

		// Something went wrong
		if (!$statement->affected_rows)
			throw new RuntimeException;


		return $statement->insert_id;
	}

	/**
	 * Execute update query
	 *
	 * @param string $table    Target table
	 * @param array  $data     New data
	 * @param array  $criteria Update conditions
	 *
	 * @return int Number of records updated (keep in mind that it might be zero)
	 */
	final public static function update($table, $data, $criteria)
	{
		if (!is_string($table) || !is_array($data) || !is_array($criteria))
			throw new InvalidArgumentException;

		if (empty($data) || empty($criteria))
			throw new UnexpectedValueException;

		if (!is_safe_string($table))
			throw new UnexpectedValueException;

		if (!self::is_connected())
			throw new DatabaseNotConnected;


		// Prepare data and criteria
		list ($data_placeholders, $data_values, $data_format) = self::_prepare_data($data, true); // Data format for update query required
		list ($criteria, $criteria_values, $criteria_format) = self::_prepare_conditions($criteria);

		// Merge values and prepare for bind_param
		$values = array_merge($data_values, $criteria_values);
		$format = $data_format . $criteria_format;
		array_unshift($values, $format); // Concentrate values with their format

		// Bad query
		if (!($statement = self::$_handle->prepare("UPDATE {$table} SET {$data_placeholders} WHERE {$criteria}")))
			throw new RuntimeException;


		$handler = [$statement, 'bind_param'];

		// Pass parameters
		if (!is_callable($handler) || !call_user_func_array($handler, array_references($values)) || !$statement->execute())
			throw new RuntimeException;


		return $statement->affected_rows;
	}

	/**
	 * Execute delete query
	 *
	 * @param string $table    Target table
	 * @param array  $criteria Delete conditions
	 *
	 * @return int Number of deleted records on success (keep in mind that it might be zero)
	 */
	final public static function delete($table, $criteria)
	{
		if (!is_string($table) || !is_array($criteria))
			throw new InvalidArgumentException;

		if (empty($criteria))
			throw new UnexpectedValueException;

		if (!is_safe_string($table))
			throw new UnexpectedValueException;


		// Prepare for bind_param
		list ($criteria, $values, $format) = self::_prepare_conditions($criteria);
		array_unshift($values, $format); // Format comes first

		// Bad query
		if (!($statement = self::$_handle->prepare("DELETE FROM {$table} WHERE {$criteria}")))
			throw new RuntimeException;


		$handler = [$statement, 'bind_param'];

		// Pass parameters
		if (!is_callable($handler) || !call_user_func_array($handler, array_references($values)) || !$statement->execute())
			throw new RuntimeException;


		return $statement->affected_rows;
	}

	/**
	 * Execute select query
	 *
	 * @param string $table      Target table
	 * @param array  $columns    List of columns to retrieve
	 * @param array  $conditions Select conditions
	 *
	 * @return array|bool Array of results on success, false otherwise
	 */
	final public static function select($table, $columns, $conditions)
	{
		if (!is_string($table) || !is_array($columns) || !is_array($conditions))
			throw new InvalidArgumentException;

		if (empty($columns) || empty($conditions))
			throw new UnexpectedValueException;

		if (!is_safe_string($table))
			throw new UnexpectedValueException;

		if (!is_safe_string($columns))
			throw new UnexpectedValueException;


		// Build fields list
		$fields = implode(', ', $columns);

		// Prepare params
		list ($criteria, $values, $format) = self::_prepare_conditions($conditions);
		array_unshift($values, $format);

		// Bad query
		if (!($statement = self::$_handle->prepare("SELECT {$fields} FROM {$table} WHERE {$criteria}")))
			throw new RuntimeException;


		$param_handler = [$statement, 'bind_param'];

		// Pass parameters
		if (!is_callable($param_handler) || !call_user_func_array($param_handler, array_references($values)) || !$statement->execute())
			throw new RuntimeException;


		// Lets see what was returned
		$meta = $statement->result_metadata();

		// No data returned
		// TODO: test this
		if (!$meta)
		{
			$statement->close(); // Clean up the mess
			throw new RuntimeException;
		}

		// Something went wrong...
		if (!$meta->field_count != count($columns))
			throw new RuntimeException;


		// Buffer returned data
		$statement->store_result();
		$result = [];

		// Build field list
		while ($field = $meta->fetch_field())
			$result[] = &$row[$field->name];

		$result_handler = [$statement, 'bind_result'];

		// Store fields in array
		if (!is_callable($result_handler) || !call_user_func_array($result_handler, array_references($result, true)))
			throw new RuntimeException;


		$results = []; // Contains all returned data

		// Loop through every returned record
		while ($statement->fetch())
		{
			$row = [];

			// Build a temporary record and copy data since the result consist of references
			foreach ($result as $field => $value)
				$row[$field] = $value;

			array_push($results, $row);
		}

		// Clean up
		$statement->free_result();
		$statement->close();

		return $results;
	}

	/**
	 * Parse data for later (easier) use with bind_param
	 * Supports Update queries
	 *
	 * @param array $data   Input data array
	 * @param bool  $update Switch to Update queries, fields column will be omitted
	 *
	 * @return array Parsed data as fields, placeholders, values and values format
	 */
	final private static function _prepare_data($data, $update = false)
	{
		if (!is_array($data))
			throw new InvalidArgumentException;


		$fields       = '';
		$placeholders = ''; // Aside with values
		$values       = [];
		$format       = ''; // Values format

		// Something in here
		if (!empty($data))
		{
			foreach ($data as $field => $value)
			{
				// Validate type and build format
				if (is_int($value)) $format .= 'i';
				else if (is_string($value)) $format .= 's';
				else if (is_double($value)) $format .= 'd';
				else
					throw new UnexpectedValueException;


				if ($update)
					$placeholders .= $field . '=?, '; // Column name is required when updating
				else
				{
					$fields .= $field . ', '; // Build field list
					$placeholders .= '?, ';
				}

				array_push($values, $value); // Build value list
			}

			// Remove last comma space sequence
			if (!$update)
				$fields = substr($fields, 0, -2);

			$placeholders = substr($placeholders, 0, -2);
		}

		if ($update)
			return [$placeholders, $values, $format];

		return [$fields, $placeholders, $values, $format];
	}

	/**
	 * Parse conditions for later (easier) use with bind_param
	 * Supported operators: null(IS NULL) = < > >= <= <> %(LIKE) !%(NOT LIKE)
	 *
	 * @param array $conditions Input data
	 *
	 * @return array Parsed data as criteria, values, and values format
	 */
	final private static function _prepare_conditions($conditions)
	{
		if (!is_array($conditions))
			throw new InvalidArgumentException;


		$criteria = ''; // Output SQL query
		$values   = [];
		$format   = ''; // Values format

		$nested = false; // Condition nesting


		if (!empty($conditions))
		{
			foreach ($conditions as $field => $data)
			{
				// Nested condition, ie: ['a' => ['b' => 'c', 'd' => 1]]
				if (is_int($field) && is_array($data))
				{
					$nested = true;

					// Parse nested condition, this means we are building an alternative
					list ($nested_fields, $nested_values, $nested_format) = self::_prepare_conditions($data);
					$criteria .= '(' . $nested_fields . ') OR ';
					$values = array_merge($values, $nested_values); // Copy values
					$format .= $nested_format; // Copy format

					continue;
				}


				if (!is_safe_string($field))
					throw new UnexpectedValueException;


				// Extract value
				$value = is_array($data) ? $data[1] : $data;

				// Convert extra operators
				if (is_null($value))
				{
					$criteria .= $field . ' IS NULL AND ';
					continue;
				}


				// Validate type and build format
				if (is_int($value)) $format .= 'i';
				else if (is_string($value)) $format .= 's';
				else if (is_double($value)) $format .= 'd';
				else
					throw new UnexpectedValueException;


				// Determinate used operator
				$operator = is_array($data) ? $data[0] : '=';

				// Operators allowed: null = != > < !< !> >= <= <> % !%
				if (!preg_match('/^( |=|!=|>|<|>=|<=|<>|%|!%)$/', $operator))
					throw new InvalidArgumentException;


				if ($operator === '%') $operator = 'LIKE';
				else if ($operator === '!%') $operator = 'NOT LIKE';

				$criteria .= $field . ' ' . $operator . ' ? AND ';
				array_push($values, $value);
			}

			// Cut the last AND statement
			if (!$nested)
				$criteria = substr($criteria, 0, -5);
		}

		// Cut the last OR statement
		if ($nested)
			$criteria = substr($criteria, 0, -4);


		return [$criteria, $values, $format];
	}


	/**
	 * Test database connection
	 *
	 * @return mysqli True when connected, false otherwise
	 */
	final public static function is_connected()
	{
		if (!self::$_handle) // No opened connection
			return false;

		if (!self::$_handle->ping()) // Server timed out
		{
			// Close and clean-up
			self::$_handle->close();
			self::$_handle = null;

			return false;
		}

		return true;
	}
}


class DatabaseFailedException extends RuntimeException
{
}

class DatabaseNotConnected extends RuntimeException
{
}

class DatabaseConditionSyntaxException extends RuntimeException
{
}