<?php
namespace Kiwi;

use RuntimeException;
use InvalidArgumentException;


class Session
{
	private $_id         = 0;
	private $_account_id = 0;

	private $_browser_ip    = '';
	private $_browser_agent = '';


	final private function __construct() { }


	final public static function load($session_id)
	{
		if (!is_valid_id($session_id))
			throw new InvalidArgumentException;


		$data = Database::select(
				Config::SQL_TABLE_SESSIONS,
				['account_id', 'browser_ip', 'browser_agent'],
				['session_id' => $session_id]);

		// Not what we have expected...
		if (count($data) > 1)
			throw new RuntimeException;

		// Session not found
		if (empty($data))
			return false;


		// Session loaded, now store it
		$session                 = new Session();
		$session->_id            = $session_id;
		$session->_account_id    = $data[0]['account_id'];
		$session->_browser_ip    = $data[0]['browser_ip'];
		$session->_browser_agent = $data[0]['browser_agent'];

		return $session;
	}

	final public static function authorize()
	{
		if (!isset($_COOKIE))
			return false;


		// Cookie contains session key
		$session_key = $_COOKIE['session'];

		// Something in here but not valid (hacking?)
		if (!self::is_valid_key($session_key))
		{
			// Delete cookie
			unset($_COOKIE['session']);
			setcookie('session', null, -1, '/');

			return false;
		}


		// Collect some browser data
		list ($browser_ip, $browser_agent) = get_browser_info();

		// Is there session like this?
		$data = Database::select(
				Config::SQL_TABLE_SESSIONS,
				['session_id', 'account_id'],
				['session_key' => $session_key, 'browser_ip' => $browser_ip, 'browser_agent' => $browser_agent]);

		// Table corrupted ?
		if (count($data) > 1)
			throw new RuntimeException;

		// Session not found
		if (empty($data))
			return false;


		// Refresh session
		$result = Database::update(
				Config::SQL_TABLE_SESSIONS,
				['last_activity' => 'CURRENT_TIMESTAMP'],
				['session_id' => $data[0]['session_id']]);

		// Whoops ?
		if ($result === 0)
			return false;


		// Store session
		$session                 = new Session();
		$session->_id            = $data[0]['session_id'];
		$session->_account_id    = $data[0]['account_id'];
		$session->_browser_ip    = $browser_ip;
		$session->_browser_agent = $browser_agent;

		return $session;
	}

	final public function close()
	{
		$result = Database::delete(
				Config::SQL_TABLE_SESSIONS,
				['session_id' => $this->_id]);

		return ($result !== 0);
	}


	final public static function open($account_id)
	{
		if (!is_valid_id($account_id))
			throw new InvalidArgumentException;


		// Generate key, simple :)
		$session_key = Cipher::encrypt(strval($account_id), Cipher::generate_salt(7));

		// Collect some browser data
		list ($browser_ip, $browser_agent) = get_browser_info();

		// Open session
		$result = Database::insert(
				Config::SQL_TABLE_SESSIONS,
				['account_id' => $account_id, 'session_key' => $session_key, 'browser_ip' => $browser_ip, 'browser_agent' => $browser_agent]);

		// Duplicate data, session already exists for this account
		if ($result === false)
			return false;


		// Session opened, throw a cookie :>
		// setcookie('session', $session_key, time() + Config::SESSION_TIMEOUT, '/', null, false, true);
		setcookie('session', $session_key, time() + Config::SESSION_TIMEOUT, '/');


		// Store it
		$session                 = new Session();
		$session->_id            = $result;
		$session->_account_id    = $account_id;
		$session->_browser_ip    = $browser_ip;
		$session->_browser_agent = $browser_agent;

		return $session;
	}

	final public static function find($account_id)
	{
		// TODO
	}


	final public function is_opened()
	{
		return ($this->_id !== 0);
	}

	final public function get_id()
	{
		// TODO
	}

	final public function get_account_id()
	{
		// TODO
	}

	final public function get_browser_ip()
	{
		// TODO
	}

	final public function get_browser_agent()
	{
		// TODO
	}

	final public static function is_valid_key($session_key)
	{
		// TODO: regex
		return true;
	}
}