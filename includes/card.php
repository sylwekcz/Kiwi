<?php
namespace Kiwi;

use InvalidArgumentException;
use RuntimeException;


class Card
{
	private $_id   = 0;
	private $_data = [];

	private $_first_name  = '';
	private $_middle_name = '';
	private $_surname     = '';
	private $_birth_date  = 0;

	private $_phone_number = '';
	private $_address      = '';
	private $_city         = '';
	private $_postal_code  = '';
	private $_street       = '';


	// Load
	final public function __construct($card_id)
	{
		if (!is_int($card_id))
			throw new InvalidArgumentException;


		$fields = ['first_name', 'middle_name', 'surname', 'birth_date', 'phone_number', 'address', 'city', 'postal_code', 'street'];

		if (!($data = Database::select('cards', $fields, ['card_id' => $card_id])))
			throw new \RuntimeException;

		if (count($data) !== 1)
			throw new RuntimeException;


		$this->_data = $data;

		$this->_first_name   = $data['first_name'];
		$this->_middle_name  = $data['middle_name'];
		$this->_surname      = $data['surname'];
		$this->_birth_date   = $data['birth_date'];
		$this->_phone_number = $data['phone_number'];
		$this->_address      = $data['address'];
		$this->_city         = $data['city'];
		$this->_postal_code  = $data['postal_code'];
		$this->_street       = $data['street'];

	}


	final public static function update($card_id, $data)
	{

	}

	final public static function create($data)
	{

	}

	final public function get_data()
	{
		return $this->_data;
	}


}