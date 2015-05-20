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

	/** @var int Account Card ID */
	private $_card_id = 0;


	/**
	 * Initialize variables
	 *
	 * @param int    $id      Account ID
	 * @param string $login   Account login
	 * @param string $email   Account email
	 * @param int    $card_id Account card ID
	 */
	final private function __construct($id, $login, $email, $card_id)
	{
		$this->_id = $id;

		$this->_login = $login;
		$this->_email = $email;

		$this->_card_id = $card_id;
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
				'card_id',
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
			$data['card_id']
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
		if (is_valid_login($login)) $conditions['login'] = $login;
		else if (is_valid_email($login)) $conditions['email'] = $login; // Users can also login with email
		else
			throw new InvalidArgumentException;

		if (!is_valid_password($password))
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
	 * @param string $login    Account login consisting of small/big letters,
	 *                         digits and underlines with at least 5 and at most 16 characters
	 * @param string $email    Account email address, at most 30 characters
	 * @param string $password Account password everything allowed but at least 5 characters
	 * @param Card   $card     Card object that will be used for this account
	 *
	 * @return bool|Account Account object when account was created, false for duplicate
	 *
	 * @throws InvalidArgumentException On invalid input
	 */
	public static function create($login, $email, $password, $card)
	{
		if (!is_valid_login($login) || !is_valid_email($email) || !is_valid_password($password))
			throw new InvalidArgumentException;

		if (!$card instanceof Card)
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
				'card_id'       => $card->get_id(),
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
	 * Update account data
	 *
	 * @param string $login    Optional. New login
	 * @param string $email    Optional. New email
	 * @param string $password Optional. New password
	 * @param Card   $card     Optional. New Card object
	 *
	 * @return bool True on successfully updated account, false if no changes were performed
	 *
	 * @throws InvalidArgumentException On invalid input
	 */
	final public function update($login = '', $email = '', $password = '', $card = null)
	{
		if ((!empty($login) && !is_valid_login($login)) || (!empty($email) && !is_valid_email($email)))
			throw new InvalidArgumentException;

		if (!empty($password) && !is_valid_password($password))
			throw new InvalidArgumentException;

		if (($card !== null) && (!$card instanceof Card))
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

		// Card specified
		if ($card !== null)
			$fields['card_id'] = $card->get_id();


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
	 * Get account card ID
	 *
	 * @return int Account card ID
	 */
	final public function get_card_id() { return $this->_card_id; }
}