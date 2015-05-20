<?php
namespace Kiwi;

use InvalidArgumentException;
use RuntimeException;


class Room
{
	/** @var int Room ID */
	private $_id;

	/** @var string Room number */
	private $_number;
	/** @var int Room student capacity */
	private $_capacity;


	/**
	 * Initialize fields
	 *
	 * @param int    $id       Room ID
	 * @param string $number   Room number string
	 * @param int    $capacity Room capacity
	 */
	final private function __construct($id, $number, $capacity)
	{
		$this->_id       = $id;
		$this->_number   = $number;
		$this->_capacity = $capacity;
	}

	/**
	 * Load room details
	 *
	 * @param int $id Target room ID
	 *
	 * @return bool|Room Room object when everything was loaded, false for invalid room
	 *
	 * @throws InvalidArgumentException On invalid input
	 */
	final public static function load($id)
	{
		if (!is_valid_id(($id)))
			throw new InvalidArgumentException;


		$data = Database::select
		(
			Config::SQL_TABLE_ROOMS,
			[
				'number',
				'capacity',
			],
			['room_id' => $id],
			true
		);

		// Room not found
		if (!$data)
			return false;


		// Store loaded data
		$room = new Room
		(
			$id,
			$data['number'],
			$data['capacity']
		);

		return $room;
	}

	/**
	 * Create new room
	 *
	 * @param string $number   Room number
	 * @param int    $capacity Room student capacity
	 *
	 * @return bool|Room Room object when room was created, false for duplicate
	 *
	 * @throws InvalidArgumentException On invalid input
	 */
	final public static function create($number, $capacity)
	{
		if (!self::_is_valid_number($number))
			throw new InvalidArgumentException;

		if (!is_int($capacity) || ($capacity > Config::ROOM_CAPACITY_LIMIT))
			throw new InvalidArgumentException;


		// TODO: check first

		$id = Database::insert
		(
			Config::SQL_TABLE_ROOMS,
			[
				'number'   => $number,
				'capacity' => $capacity,
			]
		);

		// Create and load
		return $id ? self::load($id) : false;
	}

	/**
	 * Make sure room number is valid
	 *
	 * @param string $number Room number to test
	 *
	 * @return int Whenever number is valid
	 */
	final private static function _is_valid_number($number)
	{
		return is_string($number) && (preg_match('/^([A-Za-z0-9]){1,' . Config::ROOM_NUMBER_LENGTH . '}$/', $number) === 1);
	}

	/**
	 * Make sure room capacity is valid
	 *
	 * @param int $capacity Room capacity to test
	 *
	 * @return bool Whenever capacity is valid
	 */
	final private static function _is_valid_capacity($capacity)
	{
		return (!is_int($capacity) || ($capacity > Config::ROOM_CAPACITY_LIMIT) || $capacity < Config::ROOM_CAPACITY_MINIMUM);
	}


	/**
	 * Update room details
	 *
	 * @param string $number   Optional. New room number
	 * @param int    $capacity Optional. New room student capacity
	 *
	 * @return bool True on successfully updated room, false if no changes were performed
	 */
	final public function update($number = '', $capacity = 0)
	{
		if ((!empty($number) && !self::_is_valid_number($number)) || (($capacity != 0) && !self::_is_valid_capacity($capacity)))
			throw new InvalidArgumentException;


		// Was anything passed?
		if (!empty($number))
			$fields['number'] = $number;

		if ($capacity > 0)
			$fields['capacity'] = $capacity;


		// Nothing to update...
		if (!isset($fields))
			return false;


		$result = Database::update
		(
			Config::SQL_TABLE_ROOMS,
			$fields,
			['room_id' => $this->_id],
			true
		);

		// Room updated
		return ($result != false);
	}

	/**
	 * Get room ID
	 *
	 * @return int Room ID
	 */
	final public function get_id() { return $this->_id; }

	/**
	 * Get room number
	 *
	 * @return string Room number
	 */
	final public function get_number() { return $this->_number; }

	/**
	 * Get room student capacity
	 *
	 * @return int Room capacity
	 */
	final public function get_capacity() { return $this->_capacity; }
}


class RoomDamagedException extends RuntimeException
{
}