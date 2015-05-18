<?php
namespace Kiwi;

use InvalidArgumentException;
use RuntimeException;


class Card
{
	const FIELDS = ['first_name' => 'string', 'middle_name' => 'string', 'surname' => 'string',
					'birth_date' => 'integer', 'phone_number' => 'string', 'address' => 'string',
					'city'       => 'string', 'postal_code' => 'string', 'street' => 'string'];

	/** @var int Card ID */
	private $_id = 0;

	/** @var array Card fields together with modified status */
	private $_fields = [];


	/**
	 * Disable constructor
	 */
	final private function __construct()
	{

	}


	/**
	 * Load card data
	 *
	 * @param int $card_id Target card ID
	 *
	 * @return Card|bool Card object when all data was loaded, false for invalid card
	 *
	 * @throws InvalidArgumentException On invalid input
	 * @throws CardDamagedException When card is damaged on server-side
	 */
	final public static function load($card_id)
	{
		if (!is_valid_id($card_id))
			throw new InvalidArgumentException;


		$data = Database::select(
				Config::SQL_TABLE_CARDS,
				array_keys(self::FIELDS),
				['card_id' => $card_id]);

		// Table corrupted
		if (count($data) > 1)
			throw new CardDamagedException('Query returned ' . count($data) . ' rows');

		// Card not found
		if (empty($data))
			return false;


		$data = $data[0];

		// Store what was loaded
		$card      = new Card();
		$card->_id = $card_id;

		foreach ($data as $name => $value)
		{
			$card->_fields[$name]['value']    = $value;
			$card->_fields[$name]['modified'] = false;
		}

		return $card;
	}

	/**
	 * Build new card and fill it with given data
	 *
	 * @param array $fields Array of fields consisting of name and value, ie: ['first_name' => 'Bob', 'surname' => 'Marley']
	 *
	 * @return Card|bool Card object when operation finished without any conflicts, false for duplicates
	 *
	 * @throws InvalidArgumentException On invalid input
	 */
	final public static function create($fields)
	{
		// No duplicate keys
		if (count(self::FIELDS) !== count(array_unique(array_keys($fields))))
			throw new InvalidArgumentException;


		// Make sure fields are correct, no trash
		foreach ($fields as $name => $value)
			if (!self::_is_valid_field([$name => $value]))
				throw new InvalidArgumentException;


		$id = Database::insert(
				Config::SQL_TABLE_CARDS,
				$fields);

		// Already exists!
		if (!$id)
			return false;


		return self::load($id);
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
	 * @return int|bool Number on fields updated on success, false if no changes were performed
	 *
	 * @throws CardDamagedException Wne card is damaged on server-side
	 */
	final public function update()
	{
		$fields = [];

		// Build list of modified fields, don't waste time on unchanged ones
		foreach ($this->_fields as $name => $data)
		{
			if ($data['modified'] == true)
				$fields[$name] = $data['value'];
		}

		// Nothing to update...
		if (!count($fields))
			return false;


		$result = Database::update(
				Config::SQL_TABLE_CARDS,
				$fields,
				['card_id' => $this->_id]);

		// Table corrupted
		if ($result > 1)
			throw new CardDamagedException('Query updated ' . $result . ' rows');

		// Nothing changed...
		if (!$result)
			return false;


		// Everything is up-to-date
		foreach ($this->_fields as $name => &$data)
			$data['modified'] = false;


		return count($fields);
	}

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

	/**
	 * Change field value
	 *
	 * @param string $name  Target field name
	 * @param mixed  $value New value, empty data not allowed
	 *
	 * @return bool Whenever variable has been modified
	 *
	 * @throws InvalidArgumentException On invalid input
	 */
	final public function set_field($name, $value)
	{
		if (!self::_is_valid_field([$name => $value]))
			throw new InvalidArgumentException;


		$this->_fields[$name]['value']    = $value;
		$this->_fields[$name]['modified'] = true;

		return true;
	}
}


class CardDamagedException extends RuntimeException
{
}