<?php
namespace Kiwi;

use \mysqli;
use \RuntimeException;
use \InvalidArgumentException;
use \UnexpectedValueException;


register_shutdown_function(__NAMESPACE__ . '\Database::disconnect');


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
	 * @param string $tableName  Target table
	 * @param array  $data       Data array consisting of field and value
	 *
	 * @return bool True on successfully inserted record, false otherwise
	 */
	final public static function insert($tableName, $data)
	{
		if (!is_string($tableName) || !is_array($data))
			throw new InvalidArgumentException;

		if (!self::isValidName($tableName))
			throw new UnexpectedValueException;

		if (!self::isConnected())
			throw new DatabaseNotConnected;


		// Prepare data...
		list ($fields, $placeholders, $values, $format) = self::_prepareData($data);
		array_unshift($values, $format); // Prepare for bind_param where format is the first param

		$statement = self::$_handle->prepare("INSERT INTO {$tableName} ({$fields}) VALUES ({$placeholders})");

		// Formatting failed somehow
		if (!$statement)
			throw new RuntimeException;


		$handler = [$statement, 'bind_param'];

		// Pass parameters
		if (!is_callable($handler) || !call_user_func_array($handler, array_references($values)))
			throw new RuntimeException;


		return $statement->execute() && $statement->affected_rows;
	}

	/**
	 * Execute update query
	 *
	 * @param string $tableName Target table
	 * @param array $data
	 * @param array $where
	 */
	final public static function update($tableName, $data, $where)
	{
		if (!is_string($tableName) || !is_array($data) || !is_string($where))
			throw new InvalidArgumentException;
	}

	final public static function delete($table, $where, $whereFormat)
	{

	}

	final public static function select()
	{

	}

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
				if (is_int($value))         $format .= 'i';
				else if (is_string($value)) $format .= 's';
				else if (is_double($value)) $format .= 'd';
				else
					throw new UnexpectedValueException;


				$fields .= $field . ', '; // Build field list
				array_push($values, $value); // Build value list

				// Column name is required when updating
				if ($update) $placeholders .= $field . '=?, ';
				else         $placeholders .= '?, ';
			}

			// Remove last comma space sequence
			$fields       = substr($fields, 0, -2);
			$placeholders = substr($placeholders, 0, -2);
		}

		return [$fields, $placeholders, $values, $format];
	}

	final public static function _prepareConditions($conditionData)
	{
		if (!is_array($conditionData))
			throw new InvalidArgumentException;


		$conditions = ''; // Output SQL query
		$values     = [];
		$format     = ''; // Values format

		$inside     = false; // Condition nesting


		if (!empty($conditionData))
		{
			foreach ($conditionData as $field => $data)
			{
				// Nested condition, ie: ['a' => ['b' => 'c', 'd' => 1]]
				if (is_int($field) && is_array($data))
				{
					$inside = true;

					// Parse nested condition, this means we are building an alternative
					list ($nestedFields, $nestedValues, $nestedFormat) = self::_prepareConditions($data);
					$conditions .= '(' . $nestedFields . ') OR ';
					$values     = array_merge($values, $nestedValues); // Copy values
					$format     .= $nestedFormat; // Copy format

					continue;
				}


				// Extract value
				$value = is_array($data) ? $data[1] : $data;

				// Convert extra operators
				if (is_null($value))
				{
					$conditions .= $field . ' IS NULL AND ';
					continue;
				}


				// Validate type and build format
				if (is_int($value))         $format .= 'i';
				else if (is_string($value)) $format .= 's';
				else if (is_double($value)) $format .= 'd';
				else
					throw new UnexpectedValueException;


				// Determinate used operator
				$operator = is_array($data) ? $data[0] : '=';

				// Operators allowed: null = != > < !< !> >= <= <> % !%
				if (!preg_match('/^( |=|!=|>|<|!<|!>|>=|<=|<>|%|!%)$/', $operator))
					throw new InvalidArgumentException;


				if ($operator === '%')       $operator = 'LIKE';
				else if ($operator === '!%') $operator = 'NOT LIKE';

				$conditions .= $field . ' ' . $operator . ' ? AND ';
				array_push($values, $value);
			}

			// Cut the last AND statement
			if (!$inside)
				$conditions = substr($conditions, 0, -5);
		}

		// Cut the last OR statement
		if ($inside)
			$conditions = substr($conditions, 0, -4);


		return [$conditions, $values, $format];
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


	final private static function isValidName($tableName)
	{
		return !preg_match('/(\W)/', $tableName);
	}
}

class DatabaseFailedException          extends RuntimeException { }
class DatabaseNotConnected             extends RuntimeException { }

class DatabaseConditionSyntaxException extends RuntimeException { }