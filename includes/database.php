<?php
namespace Kiwi;

use \mysqli;
use \RuntimeException;
use \InvalidArgumentException;
use \UnexpectedValueException;


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
	 */
	final public static function connect()
	{
		// Already connected!
		if (self::isConnected())
			return;


		self::$_handle = new mysqli(Config::SQL_HOST, Config::SQL_USER, Config::SQL_PASSWORD, Config::SQL_DATABASE);

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
		if (!self::isConnected())
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
	 * @return bool ID of inserted record on success (positive values only), false otherwise
	 */
	final public static function insert($table, $data)
	{
		if (!is_string($table) || !is_array($data) && !empty($data))
			throw new InvalidArgumentException;

		if (!self::isValidName($table))
			throw new UnexpectedValueException;

		if (!self::isConnected())
			throw new DatabaseNotConnected;


		// Prepare data...
		list ($fields, $placeholders, $values, $format) = self::_prepareData($data);
		array_unshift($values, $format); // Prepare for bind_param where format is the first param

		$statement = self::$_handle->prepare("INSERT INTO {$table} ({$fields}) VALUES ({$placeholders})");

		// Bad query
		if (!$statement)
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
			return false;


		return $statement->insert_id;
	}

	/**
	 * Execute update query
	 *
	 * @param string $table      Target table
	 * @param array  $data       New data
	 * @param array  $conditions Update conditions
	 *
	 * @return bool True on successfully updated record (keep in mind that it might be zero), false otherwise
	 */
	final public static function update($table, $data, $conditions)
	{
		if (!is_string($table) || !is_array($data) || !is_array($conditions))
			throw new InvalidArgumentException;

		if (empty($data) || empty($conditions))
			throw new UnexpectedValueException;

		if (!self::isValidName($table))
			throw new UnexpectedValueException;

		if (!self::isConnected())
			throw new DatabaseNotConnected;


		// Prepare data and conditions
		list ($dataPlaceholders, $dataValues, $dataFormat) = self::_prepareData($data, true);
		list ($conditions, $conditionValues, $conditionFormat) = self::_prepareConditions($conditions);

		// Merge values and prepare for bind_param
		$values = array_merge($dataValues, $conditionValues);
		$format = $dataFormat . $conditionFormat;
		array_unshift($values, $format);

		$statement = self::$_handle->prepare("UPDATE {$table} SET {$dataPlaceholders} WHERE {$conditions}");

		// Bad query
		if (!$statement)
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
	 * @param string $table      Target table
	 * @param array  $conditions Delete conditions
	 *
	 * @return bool|int Number of deleted records on success (keep in mind that it might be zero), false otherwise
	 */
	final public static function delete($table, $conditions)
	{
		if (!is_string($table) || !is_array($conditions))
			throw new InvalidArgumentException;

		if (empty($conditions))
			throw new UnexpectedValueException;

		if (!self::isValidName($table))
			throw new UnexpectedValueException;


		list ($conditions, $values, $format) = self::_prepareConditions($conditions);
		array_unshift($values, $format);

		$statement = self::$_handle->prepare("DELETE FROM {$table} WHERE {$conditions}");

		// Bad query
		if (!$statement)
			throw new RuntimeException;


		$handler = [$statement, 'bind_param'];

		// Pass parameters
		if (!is_callable($handler) || !call_user_func_array($handler, array_references($values)) || !$statement->execute())
			throw new RuntimeException;


		return $statement->affected_rows;
	}

	final public static function select()
	{

	}

	/**
	 * Parse data for later (easier) use with bind_param
	 * Supports Update queries
	 *
	 * @param array $data Input data array
	 * @param bool $update Switch to Update queries, fields column will be omitted
	 *
	 * @return array Parsed data as fields, placeholders, values and values format
	 */
	final private static function _prepareData($data, $update = false)
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
	final public static function _prepareConditions($conditions)
	{
		if (!is_array($conditions))
			throw new InvalidArgumentException;


		$criteria = ''; // Output SQL query
		$values     = [];
		$format     = ''; // Values format

		$inside = false; // Condition nesting


		if (!empty($conditions))
		{
			foreach ($conditions as $field => $data)
			{
				// Nested condition, ie: ['a' => ['b' => 'c', 'd' => 1]]
				if (is_int($field) && is_array($data))
				{
					$inside = true;

					// Parse nested condition, this means we are building an alternative
					list ($nestedFields, $nestedValues, $nestedFormat) = self::_prepareConditions($data);
					$criteria .= '(' . $nestedFields . ') OR ';
					$values = array_merge($values, $nestedValues); // Copy values
					$format .= $nestedFormat; // Copy format

					continue;
				}


				if (!self::isValidName($field))
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
			if (!$inside)
				$criteria = substr($criteria, 0, -5);
		}

		// Cut the last OR statement
		if ($inside)
			$criteria = substr($criteria, 0, -4);


		return [$criteria, $values, $format];
	}


	/**
	 * Test database connection
	 *
	 * @return mysqli True when connected, false otherwise
	 */
	final public static function isConnected()
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


	/**
	 * Make sure given string contains only safe (word) characters
	 *
	 * @param string $name Input string
	 *
	 * @return bool True if string is safe, false otherwise
	 */
	final private static function isValidName($name)
	{
		return is_string($name) && !preg_match('/(\W)/', $name);
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