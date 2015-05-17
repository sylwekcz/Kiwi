<?php
namespace Kiwi;

use InvalidArgumentException;
use RuntimeException;


class Card
{
	private $_id = 0;

	private $_fields = [[]];


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
				['first_name', 'middle_name', 'surname', 'birth_date', 'phone_number', 'address', 'city', 'postal_code', 'street'],
				['card_id' => $card_id]);

		// Table corrupted
		if (count($data) > 1)
			throw new CardDamagedException('Query returned ' . count($data) . ' rows');

		// Card not found
		if (empty($data))
			return false;


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

	final public static function create($data)
	{
		/*$id = Database::insert(
				Config::SQL_TABLE_CARDS,
				['first_name' => '']);


		return self::load($id);*/
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
		foreach ($this->_fields as $name => $data)
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
	 * @return bool Whenever variable exists and has been changed
	 */
	final public function set_field($name, $value)
	{
		if (empty($value))
			throw new InvalidArgumentException;

		if (!isset($this->_fields[$name]))
			return false;

		// Must be of same type
		if (gettype($this->_fields['value']) != gettype($value))
			return false;


		$this->_fields[$name]['value']    = $value;
		$this->_fields[$name]['modified'] = true;

		return true;
	}
}


class CardDamagedException extends RuntimeException
{
}