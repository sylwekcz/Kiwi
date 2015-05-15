<?php
namespace Kiwi;

use RuntimeException;
use InvalidArgumentException;
use UnexpectedValueException;

class Session
{
	private $_id         = 0;
	private $_account_id = 0;

	private $_browser_ip    = '';
	private $_browser_agent = '';


	final public function __construct($session_id = 0)
	{
		if ($session_id === 0) $this->_authorize();
		else $this->_load($session_id);
	}

	final private function _load($session_id)
	{
		if (!is_valid_id($session_id))
			throw new InvalidArgumentException;


		// Database failed ?
		if (!($data = Database::select('sessions', ['account_id', 'browser_ip', 'browser_agent'], ['session_id' => $session_id])))
			return false;

		// We expect exactly one record
		if (count($data) === 0)
			return false;

		if (count($data) > 1)
			throw new RuntimeException;


		$this->_id            = $session_id;
		$this->_account_id    = $data[0]['account_id'];
		$this->_browser_ip    = $data[0]['browser_ip'];
		$this->_browser_agent = $data[0]['browser_agent'];

		return true;
	}

	final private function _authorize()
	{
		if (!isset($_COOKIE))
			return false;

		$session_cookie = $_COOKIE['session'];

		// Load cookie

		return true;
	}

	final public function close()
	{
		//		if (!$this->is_opened())
		//			return false;
		//
		//
		//		return (Database::delete('sessions', ['session_id' => $this->_id]) !== 0);
	}


	final public static function open($account_id)
	{
		// Open new session and call load

		$session_id = 0;

		return new Session($session_id);

		//		if ($this->is_opened())
		//			return false;
		//
		//
		//		// Generate key
		//		$session_key = '';
		//
		//
		//		// Session already opened for this account
		//		if (!($result = Database::insert('sessions', ['account_id' => $this->_account->get_id()])))
		//			return false;
		//
		//		$this->_account->get_id();

		//		return true;
	}


	final public function is_opened()
	{
		return ($this->_id !== 0);
	}

	final public function get_id()
	{

	}

	final public function get_account_id()
	{

	}
}


$session = Session::open(10);