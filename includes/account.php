<?php
namespace Kiwi;

use InvalidArgumentException;


/**
 * Accounts
 *
 * @package Kiwi
 */
class Account
{
	/** @var int Account ID */
	private $_id = 0;

	/** @var string Account login, alternately with $_email for authorization */
	private $_login = '';
	/** @var string Account email address, alternately with $_login for authorization */
	private $_email = '';

	/** @var string User first name */
	private $name = '';
	/** @var string User surname */
	private $_surname = '';

	/** @var int User birth date */
	private $_birth_date = '';


	/**
	 * Initialize variables
	 *
	 * @param int    $id         Account ID
	 * @param string $login      Account login
	 * @param string $email      Account email
	 *
	 * @param string $name       User name
	 * @param string $surname    User surname
	 *
	 * @param int    $birth_date User birth date
	 *
	 */
	final private function __construct($id, $login, $email, $name, $surname, $birth_date)
	{
		$this->_id = $id;

		$this->_login = $login;
		$this->_email = $email;

		$this->name     = $name;
		$this->_surname = $surname;

		$this->_birth_date = $birth_date;
	}


	/**
	 * Load account data
	 *
	 * @param int $id Target account ID
	 *
	 * @return bool|Account Account object when everything was loaded, false for invalid account
	 *
	 * @throws InvalidArgumentException On invalid input
	 */
	final public static function load($id)
	{
		if (!is_valid_id($id))
			throw new InvalidArgumentException;


		$data = Database::select
		(
			Config::SQL_TABLE_ACCOUNTS,
			[
				'login',
				'email',

				'name',
				'surname',

				'birth_date',
			],
			['account_id' => $id],
			true
		);

		// Account not found
		if (!$data)
			return false;


		// Store data
		$account = new Account
		(
			$id,

			$data['login'],
			$data['email'],

			$data['name'],
			$data['surname'],

			$data['birth_date']
		);

		return $account;
	}

	/**
	 * Load account by verifying login and password
	 *
	 * @param string $login    Account login/email
	 * @param string $password Account password
	 *
	 * @return bool|Account Account object when login and password matches, false for invalid account
	 *
	 * @throws InvalidArgumentException On invalid input
	 */
	final public static function authorize($login, $password)
	{
		if (self::_is_valid_login($login)) $conditions['login'] = $login;
		else if (self::_is_valid_email($login)) $conditions['email'] = $login; // Users can also login with email
		else
			throw new InvalidArgumentException;

		if (!self::_is_valid_password($password))
			throw new InvalidArgumentException;


		$data = Database::select
		(
			Config::SQL_TABLE_ACCOUNTS,
			[
				'account_id',

				'password_hash',
				'password_salt',
			],
			$conditions,
			true
		);

		// Account not found
		if (!$data)
			return false;


		// Prepare salt
		$password_salt = '$2y$10$' . $data['password_salt'];

		// Password invalid
		if (!Cipher::verify($data['password_hash'], Cipher::encrypt($password, $password_salt)))
			return false;

		return self::load($data['account_id']);
	}

	/**
	 * Create new account
	 *
	 * @param string $login      Account login consisting of small/big letters,
	 *                           digits and underlines with at least 5 and at most 16 characters
	 * @param string $email      Account email address, at most 30 characters
	 *
	 * @param string $password   Account password everything allowed but at least 5 characters
	 *
	 * @param string $name       User name
	 * @param string $surname    User surname
	 *
	 * @param int    $birth_date User birth date
	 *
	 * @return bool|Account Account object when account was created, false for duplicate
	 *
	 * @throws InvalidArgumentException On invalid input
	 */
	public static function create($login, $email, $password, $name, $surname, $birth_date)
	{
		if (!self::_is_valid_login($login) || !self::_is_valid_email($email) || !self::_is_valid_password($password))
			throw new InvalidArgumentException;

		if (!self::_is_valid_name($name) || !self::_is_valid_surname($surname))
			throw new InvalidArgumentException;

		if (!self::_is_valid_birth_date($birth_date))
			throw new InvalidArgumentException;


		// TODO: check first

		// Encrypt password
		$password_salt = Cipher::generate_salt(10);
		$password_hash = Cipher::encrypt($password, $password_salt);
		$password_salt = substr($password_salt, 7); // Skip header

		$id = Database::insert
		(
			Config::SQL_TABLE_ACCOUNTS,
			[
				'login'         => $login,
				'email'         => $email,

				'password_hash' => $password_hash,
				'password_salt' => $password_salt,

				'name'       => $name,
				'surname'    => $surname,

				'birth_date' => $birth_date,
			]
		);

		// Account created, now load it
		return $id ? self::load($id) : false;
	}

	/**
	 * Delete account
	 *
	 * @param Account $account Target account
	 *
	 * @return bool Whenever account exists and has been removed
	 *
	 * @throws InvalidArgumentException On invalid input
	 */
	final public static function delete(&$account)
	{
		if (!$account instanceof Account)
			throw new InvalidArgumentException;


		$result = Database::delete
		(
			Config::SQL_TABLE_ACCOUNTS,
			['account_id' => $account->_id]
		);

		// Unable to remove
		return ($result != false);
	}


	/**
	 * Make sure login is in correct format
	 *
	 * @param string $login String to verify
	 *
	 * @return bool Whenever login is in valid format
	 */
	final private static function _is_valid_login($login)
	{
		return
			is_string($login) &&
			preg_match('/^[a-zA-Z0-9_]{5,16}$/', $login);
	}

	/**
	 * Make sure email is in correct format
	 *
	 * @param string $email String to verify
	 *
	 * @return bool Whenever emails is in valid format
	 */
	final private static function _is_valid_email($email)
	{
		return
			is_string($email) &&
			preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$/', $email);
	}

	/**
	 * Make sure password is in correct format
	 *
	 * @param string $password String to verify
	 *
	 * @return bool Whenever password is in valid format
	 */
	final private static function _is_valid_password($password)
	{
		return
			is_string($password) &&
			(strlen($password) > Config::ACCOUNT_PASSWORD_MIN_LENGTH) &&
			(strlen($password) < Config::ACCOUNT_PASSWORD_MAX_LENGTH);
	}

	/**
	 * Make sure name is valid
	 *
	 * @param string $name Name to test
	 *
	 * @return bool Whenever name is valid
	 */
	final private static function _is_valid_name($name)
	{
		return
			is_string($name) &&
			preg_match('/^[a-zA-Z]{3,' . Config::ACCOUNT_NAME_LENGTH . '}/', $name);
	}

	/**
	 * Make sure surname is valid
	 *
	 * @param string $surname Surname to test
	 *
	 * @return bool Whenever surname is valid
	 */
	final private static function _is_valid_surname($surname)
	{
		return
			is_string($surname) &&
			preg_match('/^[a-zA-Z]{3,' . Config::ACCOUNT_SURNAME_LENGTH . '}/', $surname);
	}

	/**
	 * Make sure birth day is correct
	 *
	 * @param string $birth_date Birth day timestamp to test
	 *
	 * @return bool Whenever birth day is valid
	 */
	final private static function _is_valid_birth_date($birth_date)
	{
		return
			is_string($birth_date) &&
			preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $birth_date, $matches) &&
			checkdate($matches[2], $matches[3], $matches[1]);
	}

	/**
	 * Update account data
	 *
	 * @param string $login      Optional. New login
	 * @param string $email      Optional. New email
	 *
	 * @param string $password   Optional. New password
	 *
	 * @param string $name       Optional. New user name
	 * @param string $surname    Optional. New user surname
	 *
	 * @param string $birth_date Optional. New user birth date
	 *
	 * @return bool True on successfully updated account, false if no changes were performed
	 *
	 * @throws InvalidArgumentException On invalid input
	 */
	final public function update($login = '', $email = '', $password = '', $name = '', $surname = '', $birth_date = '')
	{
		if ((!empty($login) && !self::_is_valid_login($login)) || (!empty($email) && !self::_is_valid_email($email)))
			throw new InvalidArgumentException;

		if (!empty($password) && !self::_is_valid_password($password))
			throw new InvalidArgumentException;

		if ((!empty($name) && !self::_is_valid_name($name)) || (!empty($surname) && !self::_is_valid_surname($surname)))
			throw new InvalidArgumentException;

		if (!empty($birth_date) && !self::_is_valid_birth_date($birth_date))
			throw new InvalidArgumentException;


		// Login or email change
		if (!empty($login)) $fields['login'] = $login;
		if (!empty($email)) $fields['email'] = $email;

		// Password update
		if (!empty($password))
		{
			// Encrypt it
			$password_salt = Cipher::generate_salt(10);

			$fields['password_hash'] = Cipher::encrypt($password, $password_salt);
			$fields['password_salt'] = substr($password_salt, 7); // Skip header
		}

		if (!empty($name)) $fields['name'] = $name;
		if (!empty($surname)) $fields['surname'] = $surname;

		if (!empty($birth_date)) $fields['birth_date'] = $birth_date;


		// Nothing to update
		if (!isset($fields))
			return false;


		$result = Database::update
		(
			Config::SQL_TABLE_ACCOUNTS,
			$fields,
			['account_id' => $this->_id]
		);

		return ($result != false);
	}

	/**
	 * Get account ID
	 *
	 * @return int Account ID
	 */
	final public function get_id() { return $this->_id; }

	/**
	 * Get account login
	 *
	 * @return string Account login
	 */
	final public function get_login() { return $this->_login; }

	/**
	 * Get account email address
	 *
	 * @return string Account email address
	 */
	final public function get_email() { return $this->_email; }

	/**
	 * Get user name
	 *
	 * @return string User name
	 */
	final public function get_name() { return $this->name; }

	/**
	 * Get user surname
	 *
	 * @return string User surname
	 */
	final public function get_surname() { return $this->_surname; }

	/**
	 * Get user birth date
	 *
	 * @return string User birth date
	 */
	final public function get_birth_date() { return $this->_birth_date; }

}