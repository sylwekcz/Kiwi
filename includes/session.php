<?php
namespace Kiwi;

use InvalidArgumentException;
use RuntimeException;


/**
 * Accounts session
 * Handles local sessions but also provides remote access
 *
 * @package Kiwi
 */
class Session
{
	/** @var int Session ID */
	private $_id = 0;
	/** @var int Session Account ID */
	private $_account_id = 0;

	/** @var string Session key */
	private $_key = '';

	/** @var int Last refresh timestamp */
	private $_last_activity = 0;

	/** @var string Client IP address, used along with $_key and $_browser_id for verification */
	private $_browser_ip = '';
	/** @var string Client browser ID, used along with $_key and $_browser_ip for verification */
	private $_browser_id = '';


	/**
	 * Initialize variables
	 *
	 * @param int    $id            Session ID
	 * @param string $key           Session Key
	 * @param int    $account_id    Session account ID
	 * @param int    $last_activity Last activity
	 * @param string $browser_ip    Browser IP address
	 * @param string $browser_id    Browser unique ID
	 */
	final private function __construct($id, $key, $account_id, $last_activity, $browser_ip, $browser_id)
	{

		$this->_id         = $id;
		$this->_account_id = $account_id;

		$this->_key = $key;

		$this->_last_activity = $last_activity;

		$this->_browser_ip = $browser_ip;
		$this->_browser_id = $browser_id;
	}

	/**
	 * Load session details
	 *
	 * @param int $id Target session ID
	 *
	 * @return bool|Session Session object when everything has been loaded, false for invalid session
	 *
	 * @throws InvalidArgumentException On invalid input
	 */
	final public static function load($id)
	{
		if (!is_valid_id($id))
			throw new InvalidArgumentException;


		$data = Database::select
		(
			Config::SQL_TABLE_SESSIONS,
			[
				'session_key',
				'account_id',
				'last_activity',
				'browser_ip',
				'browser_id',
			],
			['session_id' => $id],
			true
		);

		// Session not found
		if (!$data)
			return false;


		// Store data
		$session = new Session
		(
			$id,
			$data['session_key'],
			$data['account_id'],
			$data['last_activity'],
			$data['browser_ip'],
			$data['browser_id']
		);

		return $session;
	}

	/**
	 * Find and resume local session
	 *
	 * @return bool|Session Session object when cookie has been found and session was loaded, false for missing/invalid key
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


		$data = Database::select
		(
			Config::SQL_TABLE_SESSIONS,
			['session_id'],
			[
				'session_key' => $key,
				'browser_ip'  => $_SERVER['REMOTE_ADDR'],
				'browser_id'  => get_browser_id(),
			],
			true
		);


		// Session not found
		if (!$data)
		{
			// Delete cookie
			unset($_COOKIE[Config::SESSION_COOKIE]);
			setcookie(Config::SESSION_COOKIE, null, -1, '/');

			return false;
		}


		// Refresh session
		$result = Database::update
		(
			Config::SQL_TABLE_SESSIONS,
			['last_activity' => ['CURRENT_TIMESTAMP']],
			['session_id' => $data['session_id']],
			true
		);

		// Unable to update session?
		return $result ? self::load($data['session_id']) : false;
	}

	/**
	 * Close session, force account logout.
	 * This is a brute method of doing this
	 *
	 * @param int $session Session to kill
	 *
	 * @return bool Whenever session exists and was successfully closed
	 *
	 * @throws InvalidArgumentException On invalid input
	 */
	final public static function close($session)
	{
		if (!$session instanceof Session)
			throw new InvalidArgumentException;


		// Cookie present - local session
		if (isset($_COOKIE[Config::SESSION_COOKIE]))
		{
			$key = $_COOKIE[Config::SESSION_COOKIE];

			// Cookie damaged or keys match (local session)
			if (!Cipher::is_valid_hash($key) || Cipher::verify($session->_key, $key))
			{
				// Delete cookie as it is not needed anymore
				unset($_COOKIE[Config::SESSION_COOKIE]);
				setcookie(Config::SESSION_COOKIE, null, -1, '/');
			}
		}

		$result = Database::delete
		(
			Config::SQL_TABLE_SESSIONS,
			['session_id' => $session->_id]
		);

		// Found and removed
		return ($result != false);
	}

	/**
	 * Start new session for local
	 *
	 * @param Account $account Account object
	 *
	 * @return bool|Session Session object when session has been opened and client has been authorized, false when session is already opened for this account
	 *
	 * @throws InvalidArgumentException On invalid input
	 */
	final public static function open($account)
	{
		if (!$account instanceof Account)
			throw new InvalidArgumentException;


		// TODO: check first

		// Generate key, simple :)
		$key = Cipher::encrypt(strval($account->get_login()), Cipher::generate_salt(7));

		$id = Database::insert
		(
			Config::SQL_TABLE_SESSIONS,
			[
				'account_id'  => $account->get_id(),
				'session_key' => $key,
				'browser_ip'  => $_SERVER['REMOTE_ADDR'],
				'browser_id'  => get_browser_id(),
			]
		);

		// No duplicates? Throw a cookie and load session
		return ($id && setcookie(Config::SESSION_COOKIE, $key, time() + Config::SESSION_TIMEOUT, '/')) ? self::load($id) : false;
	}

	/**
	 * Find and load session of given account.
	 * This is used to track running sessions
	 *
	 * @param Account $account Account object
	 *
	 * @return bool|Session Session object when session exists and has been loaded, false when account is offline
	 *
	 * @throws InvalidArgumentException On invalid input
	 */
	final public static function find($account)
	{
		if (!$account instanceof Account)
			throw new InvalidArgumentException;


		$data = Database::select
		(
			Config::SQL_TABLE_SESSIONS,
			['session_id'],
			['account_id' => $account->get_id()],
			true
		);

		// Load session data, if found
		return $data ? self::load($data['session_id']) : false;
	}


	/**
	 * Get session ID
	 *
	 * @return int Session ID
	 */
	final public function get_id() { return $this->_id; }

	/**
	 * Get session Account ID
	 *
	 * @return int Account ID
	 */
	final public function get_account_id() { return $this->_account_id; }

	/**
	 * Get session last activity timestamp
	 *
	 * @return int Last client refresh timestamp
	 */
	final public function get_last_activity() { return $this->_last_activity; }

	/**
	 * Get session clients browser IP address
	 *
	 * @return string Client browser IP address
	 */
	final public function get_browser_ip() { return $this->_browser_ip; }

	/**
	 * Get session clients browser ID
	 *
	 * @return string Client browser ID
	 */
	final public function get_browser_id() { return $this->_browser_id; }
}


class SessionCorruptedException extends RuntimeException
{
}