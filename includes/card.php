<?php
namespace Kiwi;

use InvalidArgumentException;


/**
 * Information card for Accounts
 * Holds all personal data
 *
 * @package Kiwi
 */
class Card
{
	/** Card fields and their types */
	const FIELDS = ['first_name'   => 'string',
	                'middle_name'  => 'string',
	                'surname'      => 'string',
	                'birth_date'   => 'integer',
	                'phone_number' => 'string',
	                'address'      => 'string',
	                'city'         => 'string',
	                'postal_code'  => 'string',
	                'street'       => 'string'];


	/** @var int Card ID */
	private $_id = 0;
	/** @var array Card fields together with modified status */
	private $_fields = [];


	/**
	 * Initialize variables
	 *
	 * @param int   $id     Card ID
	 * @param array $fields Card fields
	 */
	final private function __construct($id, $fields)
	{
		$this->_id     = $id;
		$this->_fields = $fields;
	}


	/**
	 * Load card data
	 *
	 * @param int $id Target card ID
	 *
	 * @return bool|Card Card object when all data was loaded, false for invalid card
	 *
	 * @throws InvalidArgumentException On invalid input
	 */
	final public static function load($id)
	{
		if (!is_valid_id($id))
			throw new InvalidArgumentException;


		$data = Database::select
		(
			Config::SQL_TABLE_CARDS,
			array_keys(self::FIELDS),
			['card_id' => $id],
			true
		);

		// Card not found
		if (!$data)
			return false;


		$fields = [];

		// Prepare data
		foreach ($data as $name => $value)
			$fields[$name]['value'] = $value;

		// Store it
		$card = new Card
		(
			$id,
			$fields
		);

		return $card;
	}

	/**
	 * Build new card and fill it with given data
	 *
	 * @param array $fields Array of fields consisting of name and value, ie: ['first_name' => 'Bob', 'surname' => 'Marley']
	 *
	 * @return bool|Card Card object when operation finished without any conflicts, false for duplicates
	 *
	 * @throws InvalidArgumentException On invalid input
	 */
	final public static function create($fields)
	{
		// No duplicate keys
		if (count(self::FIELDS) != count(array_unique(array_keys($fields))))
			throw new InvalidArgumentException;


		// TODO: check first

		// Make sure fields are correct, no trash
		foreach ($fields as $name => $value)
			if (!self::_is_valid_field([$name => $value]))
				throw new InvalidArgumentException;


		$id = Database::insert
		(
			Config::SQL_TABLE_CARDS,
			$fields
		);

		// Card created, load it
		return $id ? self::load($id) : false;
	}

	/**
	 * Make sure field exists and is of valid type
	 *
	 * @param array $field Array consisting of field name and value, ie: ['name' => 'bob']
	 *
	 * @return bool Whenever field is valid
	 */
	final private static function _is_valid_field($field)
	{
		if (!is_array($field) || (count($field) != 1))
			return false;


		// Compare with field list, also check type
		foreach (self::FIELDS as $name => $value)
			if (isset($field[$name]) && (gettype($field[$name]) === $value))
				return true;

		return false;
	}


	/**
	 * Synchronize card data
	 *
	 * @param array $fields New fields values
	 *
	 * @return bool|int True on successfully updated card, false if no changes were performed
	 *
	 * @throws InvalidArgumentException On invalid input
	 */
	final public function update($fields)
	{
		// No duplicate keys
		if (count($fields) != count(array_unique(array_keys($fields))))
			throw new InvalidArgumentException;

		// Make sure fields are correct
		foreach ($fields as $name => $value)
			if (!self::_is_valid_field([$name => $value]))
				throw new InvalidArgumentException;


		$result = Database::update
		(
			Config::SQL_TABLE_CARDS,
			$fields,
			['card_id' => $this->_id],
			true
		);

		// Was something changed?
		return ($result != false);
	}


	/**
	 * Get card ID
	 *
	 * @return int Card ID
	 */
	final public function get_id() { return $this->_id; }

	/**
	 * Get field value by name
	 *
	 * @param string $name Target field name
	 *
	 * @return mixed Field value
	 */
	final public function get_field($name)
	{
		if (!isset($this->_fields[$name]))
			return false;

		return $this->_fields[$name]['value'];
	}

	/**
	 * Get all fields
	 *
	 * @return array Fields array
	 */
	final public function get_fields()
	{
		$fields = [];

		// Copy values only
		foreach ($this->_fields as $field => $data)
			$fields[$field] = $data['value'];

		return $fields;
	}
}