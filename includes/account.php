<?php
namespace Kiwi;

use RuntimeException;
use InvalidArgumentException;


/**
 * Accounts
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


	/** @var int Account card ID */
	private $_card_id = 0;


	/**
	 * Disable constructor
	 */
	final private function __construct()
	{

	}


	/**
	 * Load account data
	 *
	 * @param int $account_id Target account ID
	 *
	 * @return Account|bool Account object when everything was loaded, false for invalid account
	 *
	 * @throws InvalidArgumentException On invalid input
	 * @throws AccountDamagedException When account is damaged on server-side
	 */
	final public static function load($account_id)
	{
		if (!is_valid_id($account_id))
			throw new InvalidArgumentException;


		$data = Database::select(
				Config::SQL_TABLE_ACCOUNTS,
				['login', 'email', 'card_id'],
				['account_id' => $account_id]);

		// Table corrupter
		if (!count($data) > 1)
			throw new AccountDamagedException;

		// Account not found
		if (empty($data))
			return false;


		$data = $data[0]; // Simplify

		// Store data
		$account      = new Account();
		$account->_id = $account_id;

		$account->_login   = $data['login'];
		$account->_email   = $data['email'];
		$account->_card_id = $data['card_id'];

		return $account;
	}

	/**
	 * Load account by verifying login and password
	 *
	 * @param string $login    Account login/email
	 * @param string $password Account password
	 *
	 * @return Account|bool Account object when login and password matches, false for invalid account
	 *
	 * @throws InvalidArgumentException On invalid input
	 * @throws AccountDamagedException When account is damaged on server-side
	 */
	final public static function authorize($login, $password)
	{
		if (is_valid_login($login)) $conditions['login'] = $login;
		else if (is_valid_email($login)) $conditions['email'] = $login; // Users can also login with email
		else
			throw new InvalidArgumentException;

		if (!is_valid_password($password))
			throw new InvalidArgumentException;


		$data = Database::select(
				Config::SQL_TABLE_ACCOUNTS,
				['account_id', 'password_hash', 'password_salt'],
				$conditions);


		// Some kind of mess in database there is
		if (count($data) > 1)
			throw new AccountDamagedException;

		// Account not found
		if (empty($data))
			return false;


		$data = $data[0]; // Simplify

		// Prepare salt
		$password_salt = '$2y$10$' . $data['password_salt'];

		// Password invalid
		if (!Cipher::verify($data['password_hash'], Cipher::encrypt($password, $password_salt)))
			return false;


		// Paranoid
		$id = $data['account_id'];
		unset($password_salt);
		unset($data);

		return self::load($id);
	}

	/**
	 * Create new account
	 *
	 * @param string $login    Account login consisting of small/big letters, digits and underlines with at least 5 and at most 16 characters
	 * @param string $email    Account email address, at most 30 characters
	 * @param string $password Account password everything allowed but at least 5 characters
	 *
	 * @return Account|bool Account object when account was created, false for duplicate
	 *
	 * @throws InvalidArgumentException On invalid input
	 */
	final public static function create($login, $email, $password)
	{
		if (!is_valid_login($login) || !is_valid_email($email) || !is_valid_password($password))
			throw new InvalidArgumentException;


		// Encrypt password
		$password_salt = Cipher::generate_salt(10);
		$password_hash = Cipher::encrypt($password, $password_salt);
		$password_salt = substr($password_salt, 7); // Skip header

		$id = Database::insert(
				Config::SQL_TABLE_ACCOUNTS,
				['login' => $login, 'email' => $email, 'password_hash' => $password_hash, 'password_salt' => $password_salt]);

		// Fo sure...
		unset($password_hash);
		unset($password_salt);

		// Duplicate account
		if (!$id)
			return false;


		return self::load($id);
	}

	/**
	 * Delete account
	 *
	 * @param int $account_id Target account ID
	 *
	 * @return bool Whenever account exists and has been removed
	 *
	 * @throws InvalidArgumentException On invalid input
	 */
	final public static function delete($account_id)
	{
		if (!is_valid_id($account_id))
			throw new InvalidArgumentException;


		$result = Database::delete(
				Config::SQL_TABLE_ACCOUNTS,
				['account_id' => $account_id]);

		return ($result !== 0);
	}


	/**
	 * Get account ID
	 * @return int Account ID
	 */
	final public function get_id()
	{
		return $this->_id;
	}

	/**
	 * Get account login
	 * @return string Account login
	 */
	final public function get_login()
	{
		return $this->_id;
	}

	/**
	 * Get account email address
	 * @return string Account email address
	 */
	final public function get_email()
	{
		return $this->_id;
	}
}


class AccountDamagedException extends RuntimeException
{
}

class AccountSessionFailedException extends RuntimeException
{
}