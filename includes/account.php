<?php
namespace Kiwi;

use RuntimeException;
use InvalidArgumentException;
use UnexpectedValueException;


class Account
{
	private $_id    = 0;
	private $_login = '';
	private $_email = '';

	private $_session = null;
	private $_card    = null;


	final public function __construct()
	{

	}


	final public static function authorize($login, $password)
	{
		if (is_valid_login($login)) $conditions['login'] = $login;
		else if (is_valid_email($login)) $conditions['email'] = $login; // Might be also an attempt to login by email...
		else
			throw new InvalidArgumentException;

		if (!is_valid_password($password))
			throw new InvalidArgumentException;


		// Account not found
		if (!$data = Database::select('accounts', ['account_id', 'login', 'email', 'password_hash', 'password_salt'], $conditions))
			return false;

		// Some kind of mess in database there is
		if (count($data) != 1)
			throw new AccountDamagedException;


		// Validate password
		Cipher::verify($data[0]['password_hash'], Cipher::encrypt($password, $data[0]['password_salt']));

		return new Account;
	}

	final public function load($account_id)
	{

	}


	/*final public function logout()
	{
		// Is session opened ?
		if (!$this->_session instanceof Session)
			return false;

		if (!$this->_session->close())
			throw new AccountSessionFailedException;


		unset($this->_session); // Destroy session object
		return true;
	}*/


	final public static function create($login, $email, $password)
	{
		if (!is_string($login) || !is_string($email) || !is_string($password))
			throw new InvalidArgumentException;

		if (!is_valid_login($login) || !is_valid_email($email) || !is_valid_password($password))
			throw new UnexpectedValueException;


		list ($password_hash, $password_salt) = Cipher::encrypt($password);

		return Database::insert('accounts', ['login' => $login, 'email' => $email, 'password_hash' => $password_hash, 'password_salt' => $password_salt]);
	}

	final public static function delete($account_id)
	{

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