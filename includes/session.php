<?php
namespace Kiwi;

use RuntimeException;
use InvalidArgumentException;


class Session
{
	/** @var int Session ID */
	private $_id = 0;

	/** @var string Session key */
	private $_key = '';

	/** @var int Account ID */
	private $_account_id = 0;


	/** @var int Last refreshed */
	private $_last_activity = 0;


	/** @var string Client IP address */
	private $_browser_ip = '';

	/** @var string Client browser ID */
	private $_browser_id = '';


	/**
	 * Disable constructor
	 */
	final private function __construct()
	{

	}


	/**
	 * Kill session
	 * @return bool Whenever the session was closed
	 */
	final public function kill()
	{
		// Cookie present
		if (isset($_COOKIE['session']))
		{
			$key = $_COOKIE['session'];

			// Cookie damaged or keys march (local session)
			if (!Cipher::is_valid_hash($key) || Cipher::verify($this->_key, $key))
			{
				// Delete cookie
				unset($_COOKIE['session']);
				setcookie('session', null, -1, '/');
			}
		}

		return self::close($this->_id);
	}


	/**
	 * Load session details
	 *
	 * @param int $session_id Target session ID
	 *
	 * @return Session|bool Session object on successfully loaded session, false on invalid session ID
	 */
	final public static function load($session_id)
	{
		if (!is_valid_id($session_id))
			throw new InvalidArgumentException;


		$data = Database::select(
				Config::SQL_TABLE_SESSIONS,
				['session_key', 'account_id', 'last_activity', 'browser_ip', 'browser_id'],
				['session_id' => $session_id]);

		// Not what we have expected...
		if (count($data) > 1)
			throw new SessionCorruptedException;

		// Session not found
		if (empty($data))
			return false;


		// Session loaded, now store it
		$session      = new Session();
		$session->_id = $session_id;

		$session->_key        = $data[0]['session_key'];
		$session->_account_id    = $data[0]['account_id'];
		$session->_last_activity = $data[0]['last_activity'];
		$session->_browser_ip    = $data[0]['browser_ip'];
		$session->_browser_id = $data[0]['browser_id'];

		return $session;
	}

	/**
	 * Find and resume session for this client
	 *
	 * @return Session|bool Session object successful authorization (key found, is valid, session loaded), false on missing/invalid key
	 */
	final public static function authorize()
	{
		if (!isset($_COOKIE['session']))
			return false;


		// Cookie contains session key
		$key = $_COOKIE['session'];

		// Something in here but not valid (hacking?)
		if (!Cipher::is_valid_hash($key))
		{
			// Delete cookie
			unset($_COOKIE['session']);
			setcookie('session', null, -1, '/');

			return false;
		}

		// Is there session like this?
		$data = Database::select(
				Config::SQL_TABLE_SESSIONS,
				['session_id'/*, 'account_id'*/],
				['session_key' => $key, 'browser_ip' => $_SERVER['REMOTE_ADDR'], 'browser_id' => get_browser_id()]);

		// Table corrupted ?
		if (count($data) > 1)
			throw new SessionCorruptedException;

		// Session not found
		if (empty($data))
		{
			// Delete cookie
			unset($_COOKIE['session']);
			setcookie('session', null, -1, '/');

			return false;
		}


		$id = $data[0]['session_id'];

		// Refresh session
		$result = Database::update(
				Config::SQL_TABLE_SESSIONS,
				['last_activity' => ['CURRENT_TIMESTAMP']],
				['session_id' => $id]);

		// Whoops ?
		if ($result === 0)
			return false;


		// $session                 = new Session();
		// $session->_id            = $data[0]['session_id'];
		// $session->_account_id    = $data[0]['account_id'];
		// $session->_browser_ip    = $browser_ip;
		// $session->_browser_id = $browser_id;

		return self::load($id);
	}

	/**
	 * Close session, force account logout
	 *
	 * @param int $session_id Session ID to kill
	 *
	 * @return bool True on successfully killed session, false on invalid session ID
	 */
	final public static function close($session_id)
	{
		if (!is_valid_id($session_id))
			throw new InvalidArgumentException;


		$result = Database::delete(
				Config::SQL_TABLE_SESSIONS,
				['session_id' => $session_id]);

		return ($result !== 0);
	}

	/**
	 * Start new session of given account for this client
	 *
	 * @param int $account_id Target account ID
	 *
	 * @return Session|bool Session object when session has been opened and client has been authorized, false on duplicate session
	 */
	final public static function open($account_id)
	{
		if (!is_valid_id($account_id))
			throw new InvalidArgumentException;


		// Generate key, simple :)
		$key = Cipher::encrypt(strval($account_id), Cipher::generate_salt(7));

		// Open session
		$id = Database::insert(
				Config::SQL_TABLE_SESSIONS,
				['account_id' => $account_id, 'session_key' => $key,
				 'browser_ip' => $_SERVER['REMOTE_ADDR'], 'browser_id' => get_browser_id()]);

		// Duplicate data, session already exists for this account
		if ($id === false)
			return false;


		// Session opened, throw a cookie :>
		// setcookie('session', $session_key, time() + Config::SESSION_TIMEOUT, '/', null, false, true);
		setcookie('session', $key, time() + Config::SESSION_TIMEOUT, '/');


		// $session                 = new Session();
		// $session->_id            = $result;
		// $session->_account_id    = $account_id;
		// $session->_browser_ip    = $browser_ip;
		// $session->_browser_id = $browser_id;

		// Load session data
		return self::load($id);
	}

	/**
	 * Find and load session of given account
	 *
	 * @param int $account_id Target account ID
	 *
	 * @return Session|bool Session object on successfully loaded account session, false on offline account
	 */
	final public static function find($account_id)
	{
		if (!is_valid_id($account_id))
			throw new InvalidArgumentException;


		$data = Database::select(
				Config::SQL_TABLE_SESSIONS,
				['session_id'],
				['account_id' => $account_id]);

		// Table corrupted
		if (count($data) > 1)
			throw new SessionCorruptedException;

		// Account is offline
		if (empty($data))
			return false;


		//		$session                 = new Session();
		//		$session->_id            = $data[0]['session_id'];
		//		$session->_account_id    = $account_id;
		//		$session->_browser_ip    = $data[0]['browser_ip'];
		//		$session->_browser_id = $data[0]['browser_id'];

		// Load session data
		return self::load($data[0]['session_id']);
	}


	/**
	 * Get session ID
	 * @return int Session ID
	 */
	final public function get_id()
	{
		return $this->_id;
	}

	/**
	 * Get session account ID
	 * @return int Session account ID
	 */
	final public function get_account_id()
	{
		return $this->_account_id;
	}

	/**
	 * Get session last activity timestamp
	 * @return int Last client activity timestamp
	 */
	final public function get_last_activity()
	{
		return $this->_last_activity;
	}

	/**
	 * Get session clients browser IP address
	 * @return string Client IP address
	 */
	final public function get_browser_ip()
	{
		return $this->_browser_ip;
	}

	/**
	 * Get session clients browser ID
	 * @return string Client browser ID
	 */
	final public function get_browser_id()
	{
		return $this->_browser_id;
	}
}


class SessionCorruptedException extends RuntimeException
{
}