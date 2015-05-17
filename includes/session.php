<?php
namespace Kiwi;

use RuntimeException;
use InvalidArgumentException;


/**
 * Accounts session, handles local sessions but also provides remote access
 * @package Kiwi
 */
class Session
{
	/** @var int Session ID */
	private $_id = 0;

	/** @var string Session key */
	private $_key = '';

	/** @var int Account ID */
	private $_account_id = 0;


	/** @var int Last refresh timestamp */
	private $_last_activity = 0;


	/** @var string Client IP address, used along with $_key and $_browser_id for verification */
	private $_browser_ip = '';

	/** @var string Client browser ID, used along with $_key and $_browser_ip for verification */
	private $_browser_id = '';


	/**
	 * Disable constructor
	 */
	final private function __construct()
	{

	}

	/**
	 * Load session details
	 *
	 * @param int $session_id Target session ID
	 *
	 * @return Session|bool Session object when everything has been loaded, false for invalid session
	 *
	 * @throws InvalidArgumentException On invalid input
	 * @throws SessionCorruptedException When session is damaged on server-side
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


		$data = $data[0]; // Simplify a bit

		// Session loaded, now store it
		$session      = new Session();
		$session->_id = $session_id;

		$session->_key           = $data['session_key'];
		$session->_account_id    = $data['account_id'];
		$session->_last_activity = $data['last_activity'];
		$session->_browser_ip    = $data['browser_ip'];
		$session->_browser_id    = $data['browser_id'];

		return $session;
	}

	/**
	 * Find and resume local session
	 *
	 * @return Session|bool Session object when cookie has been found and session was loaded, false for missing/invalid key
	 *
	 * @throws SessionCorruptedException When session is damaged on server-side
	 */
	final public static function authorize()
	{
		if (!isset($_COOKIE[Config::SESSION_COOKIE]))
			return false;


		// Cookie contains session key
		$key = $_COOKIE[Config::SESSION_COOKIE];

		// Something in here but not valid (hacking?)
		if (!Cipher::is_valid_hash($key))
		{
			// Delete cookie
			unset($_COOKIE[Config::SESSION_COOKIE]);
			setcookie(Config::SESSION_COOKIE, null, -1, '/');

			return false;
		}


		$data = Database::select(
				Config::SQL_TABLE_SESSIONS,
				['session_id'],
				['session_key' => $key, 'browser_ip' => $_SERVER['REMOTE_ADDR'], 'browser_id' => get_browser_id()]);

		// Table corrupted
		if (count($data) > 1)
			throw new SessionCorruptedException;

		// Session not found
		if (empty($data))
		{
			// Delete cookie
			unset($_COOKIE[Config::SESSION_COOKIE]);
			setcookie(Config::SESSION_COOKIE, null, -1, '/');

			return false;
		}


		$data = $data[0]; // Simplify a bit
		$id   = $data['session_id'];

		// Refresh session
		$result = Database::update(
				Config::SQL_TABLE_SESSIONS,
				['last_activity' => ['CURRENT_TIMESTAMP']],
				['session_id' => $id]);

		// Cannot update session, whoops ?
		if ($result === 0)
			return false;


		return self::load($id);
	}

	/**
	 * Close session, force account logout.
	 * This is a brute method of doing this
	 *
	 * @param int $session_id Session ID to kill
	 *
	 * @return bool Whenever session exists and was successfully closed
	 *
	 * @throws InvalidArgumentException On invalid input
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
	 * Start new session for local
	 *
	 * @param int $account_id Target account ID
	 *
	 * @return Session|bool Session object when session has been opened and client has been authorized, false when session is already opened for this account
	 *
	 * @throws InvalidArgumentException On invalid input
	 */
	final public static function open($account_id)
	{
		if (!is_valid_id($account_id))
			throw new InvalidArgumentException;


		// Generate key, simple :)
		$key = Cipher::encrypt(strval($account_id), Cipher::generate_salt(7));

		$id = Database::insert(
				Config::SQL_TABLE_SESSIONS,
				['account_id' => $account_id, 'session_key' => $key,
				 'browser_ip' => $_SERVER['REMOTE_ADDR'], 'browser_id' => get_browser_id()]);

		// Duplicate data, session already exists for this account
		if ($id === false)
			return false;


		// Session opened, throw a cookie :>
		// setcookie(Config::SESSION_COOKIE, $session_key, time() + Config::SESSION_TIMEOUT, '/', null, false, true);
		setcookie(Config::SESSION_COOKIE, $key, time() + Config::SESSION_TIMEOUT, '/');

		return self::load($id);
	}

	/**
	 * Find and load session of given account.
	 * This is used to track running sessions
	 *
	 * @param int $account_id Target account ID
	 *
	 * @return Session|bool Session object when session exists and has been loaded, false when account is offline
	 *
	 * @throws InvalidArgumentException On invalid input
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


		$data = $data[0]; // Simplify

		// Load session data
		return self::load($data['session_id']);
	}


	/**
	 * Kill local session
	 * @return bool Whenever the session was closed
	 */
	final public function kill()
	{
		// Cookie present
		if (isset($_COOKIE[Config::SESSION_COOKIE]))
		{
			$key = $_COOKIE[Config::SESSION_COOKIE];

			// Cookie damaged or keys match (local session)
			if (!Cipher::is_valid_hash($key) || Cipher::verify($this->_key, $key))
			{
				// Delete cookie as it is not needed anymore
				unset($_COOKIE[Config::SESSION_COOKIE]);
				setcookie(Config::SESSION_COOKIE, null, -1, '/');
			}

			unset($key);
		}

		return self::close($this->_id);
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
	 * @return int Last client refresh timestamp
	 */
	final public function get_last_activity()
	{
		return $this->_last_activity;
	}

	/**
	 * Get session clients browser IP address
	 * @return string Client browser IP address
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