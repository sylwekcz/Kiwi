<?php
namespace Kiwi;

use InvalidArgumentException;
use RuntimeException;


class Language
{
	/** @var int Language ID */
	private $_id = 0;

	/** @var string Language name */
	private $_name = '';
	/** @var string Language advance level */
	private $_level = '';


	/**
	 * Initialize variables
	 *
	 * @param int    $id    Language ID
	 * @param string $name  Language name
	 * @param string $level Language advance level
	 */
	final private function __construct($id, $name, $level)
	{
		$this->_id = $id;

		$this->_name  = $name;
		$this->_level = $level;
	}

	/**
	 * Load language details
	 *
	 * @param int $id Language ID
	 *
	 * @return bool|Language Language object when everything was loaded, false for invalid language
	 */
	final public static function load($id)
	{
		if (!is_valid_id($id))
			throw new InvalidArgumentException;


		$data = Database::select
		(
			Config::SQL_TABLE_LANGUAGES,
			[
				'name',
				'level',
			],
			['language_id' => $id],
			true
		);

		// Not found
		if (!$data)
			return false;


		// Store data
		$language = new Language
		(
			$id,
			$data['name'],
			$data['level']
		);

		return $language;
	}

	/**
	 * Create new language
	 *
	 * @param string $name  Language name
	 * @param string $level Language advance level
	 *
	 * @return bool|Language Language object when language was created, false for duplicate
	 *
	 * @throws InvalidArgumentException On invalid input
	 */
	final public static function create($name, $level)
	{
		if (!self::_is_valid_name($name) || !self::_is_valid_level($level))
			throw new InvalidArgumentException;


		// TODO: check first

		$id = Database::insert
		(
			Config::SQL_TABLE_LANGUAGES,
			[
				'name'  => $name,
				'level' => $level,
			]
		);

		// Language created, load it
		return $id ? self::load($id) : false;
	}

	/**
	 * Search database for matching languages
	 *
	 * @param string $name  Language name
	 * @param string $level Optional. Language level
	 *
	 * @return array|bool|Language Language object for complete criteria, Array of matching languages, false when nothing found
	 *
	 * @throws InvalidArgumentException On invalid input
	 */
	final public static function find($name, $level = '')
	{
		if (!self::_is_valid_name($name))
			throw new InvalidArgumentException;

		// Level is optional
		if (!empty($level) && !self::_is_valid_level($level))
			throw new InvalidArgumentException;


		$conditions['name'] = $name;

		// We know exactly what we want
		if (!empty($level))
			$conditions['level'] = $level;

		$data = Database::select
		(
			Config::SQL_TABLE_LANGUAGES,
			['language_id'],
			$conditions,
			!empty($level) // If we specify level, there can be only one record
		);

		// Not found
		if (!$data)
			return false;

		// Single language loaded
		if (!isset($data[0]))
			return self::load($data['language_id']);


		$languages = [];

		// Build array of matching languages
		foreach ($data as $language)
			array_push($languages, self::load($language['language_id']));

		return $languages;
	}


	/**
	 * Make sure language name is valid
	 *
	 * @param string $name Language name to test
	 *
	 * @return bool Whenever name is valid
	 */
	final private static function _is_valid_name($name)
	{
		return is_string($name) && (preg_match('/^([a-zA-Z]){1,' . Config::LANGUAGE_NAME_LENGTH . '}$/', $name) === 1);
	}

	/**
	 * Make sure language advance level is valid
	 *
	 * @param string $level Language level to test
	 *
	 * @return bool Whenever level is valid
	 */
	final private static function _is_valid_level($level)
	{
		return is_string($level) && (preg_match('/^[a-zA-Z0-9]{1,2}$/', $level) === 1);
	}


	/**
	 * Update language details
	 *
	 * @param string $name  Optional. New language name
	 * @param string $level Optional. New language advance level
	 *
	 * @return bool True on successfully updated language, false if no changes were performed
	 *
	 * @throws InvalidArgumentException On invalid input
	 */
	final public function update($name = '', $level = '')
	{
		if ((!empty($name) && !self::_is_valid_name($name)) || (!empty($level) && !self::_is_valid_level($level)))
			throw new InvalidArgumentException;


		// Was anything passed?
		if (!empty($name))
			$fields['name'] = $name;

		if (!empty($level))
			$fields['level'] = $level;


		// Nothing to update...
		if (!isset($fields))
			return false;


		$result = Database::update
		(
			Config::SQL_TABLE_LANGUAGES,
			$fields,
			['language_id' => $this->_id],
			true
		);

		// Language updated
		return ($result != false);
	}

	/**
	 * Get language ID
	 *
	 * @return int Language ID
	 */
	final public function get_id() { return $this->_id; }

	/**
	 * Get language name
	 *
	 * @return string Language name
	 */
	final public function get_name() { return $this->_name; }

	/**
	 * Get language advance level
	 *
	 * @return string Language level
	 */
	final public function get_level() { return $this->_level; }
}


class LanguageCorruptedException extends RuntimeException
{
}