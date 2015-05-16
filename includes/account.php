<?php
namespace Kiwi;

use RuntimeException;
use InvalidArgumentException;


class Account
{
	private $_id    = 0;
	private $_login = '';
	private $_email = '';

	private $_card_id = 0;


	final private function __construct()
	{

	}


	final public static function load($account_id)
	{
		if (!is_valid_id($account_id))
			throw new InvalidArgumentException;


		$data = Database::select(
				Config::SQL_TABLE_ACCOUNTS,
				['login', 'email', 'card_id'],
				['account_id' => $account_id]);

		if (!count($data) > 1)
			throw new AccountDamagedException;

		if (empty($data))
			return false;


		$account      = new Account();
		$account->_id = $account_id;

		$account->_login   = $data[0]['login'];
		$account->_email   = $data[0]['email'];
		$account->_card_id = $data[0]['card_id'];

		return $account;
	}

	final public static function authorize($login, $password)
	{
		if (is_valid_login($login)) $conditions['login'] = $login;
		else if (is_valid_email($login)) $conditions['email'] = $login; // Might be also an attempt to login by email...
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

		if (empty($data))
			return false;


		// Prepare salt
		$password_salt = '$2y$10$' . $data[0]['password_salt'];

		// Validate password
		if (!Cipher::verify($data[0]['password_hash'], Cipher::encrypt($password, $password_salt)))
			return false;


		$id = $data[0]['account_id'];
		unset($password_salt);
		unset($data);


		return self::load($id);
	}

	final public static function create($login, $email, $password)
	{
		if (!is_valid_login($login) || !is_valid_email($email) || !is_valid_password($password))
			throw new InvalidArgumentException;


		$password_salt = Cipher::generate_salt(10);
		$password_hash = Cipher::encrypt($password, $password_salt);
		$password_salt = substr($password_salt, 7);

		unset($password);


		$id = Database::insert(
				Config::SQL_TABLE_ACCOUNTS,
				['login' => $login, 'email' => $email, 'password_hash' => $password_hash, 'password_salt' => $password_salt]);

		unset($password_hash);
		unset($password_salt);

		// Duplicate account
		if (!$id)
			return false;


		return self::load($id);
	}

	final public static function delete($account_id)
	{
		if (!is_valid_id($account_id))
			throw new InvalidArgumentException;


		$result = Database::delete(
				Config::SQL_TABLE_ACCOUNTS,
				['account_id' => $account_id]);

		return ($result !== 0);
	}


	final public function get_id()
	{
		return $this->_id ? $this->_id : false;
	}

	final public function get_login()
	{
		return $this->_id ? $this->_login : false;
	}

	final public function get_email()
	{
		return $this->_id ? $this->_email : false;
	}
}


class AccountDamagedException extends RuntimeException
{
}

class AccountSessionFailedException extends RuntimeException
{
}