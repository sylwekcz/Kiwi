<?php
namespace Kiwi;

use \InvalidArgumentException;
use \UnexpectedValueException;
use \RuntimeException;


// TODO: documentation
abstract class Cipher
{
	final public static function encrypt($data, $salt = '')
	{
		if (!is_string($data) || !is_string($salt))
			throw new InvalidArgumentException;

		if (empty($data))
			throw new UnexpectedValueException;


		$custom_salt = false;

		// No custom salt provided, generate random
		if (empty($salt))
			$salt = self::generate_salt(10);
		else
		{
			if (!self::is_valid_salt($salt))
				throw new CipherInvalidSaltException;

			$custom_salt = true;
		}


		// Encryption failed... O.o
		if (!($blowfish = crypt($data, $salt)))
			throw new CipherEncryptionFailedException;


		// Cut the part we need
		$hash = substr($blowfish, 29);

		// For sure
		unset($data);
		unset($blowfish);

		if ($custom_salt)
			return $hash;

		return [$hash, $salt];
	}

	final public static function verify($hash_one, $hash_two)
	{
		if (!is_string($hash_one) || !is_string($hash_two))
			throw new InvalidArgumentException;

		if (!self::is_valid_hash($hash_one) || !self::is_valid_hash($hash_two))
			throw new UnexpectedValueException;


		return hash_equals($hash_one, $hash_two);
	}

	final public static function generate_salt($cost)
	{
		if (!is_int($cost))
			throw new InvalidArgumentException;

		// Too weak/strong salt
		if (($cost < 5) || ($cost > 20))
			throw new CipherSaltCostInvalidException;


		// Build salt characters
		$salt_pieces = array_merge(['.', '/'], range('A', 'Z'), range('a', 'z'), range(0, 9));

		// Build 22 characters long salt
		$salt = '';

		for ($i = 0; $i < 22; $i++)
			$salt .= $salt_pieces[array_rand($salt_pieces)];

		// Build header
		$salt_header = sprintf('$2y$%02d$', $cost);

		return $salt_header . $salt;
	}

	final public static function is_valid_salt($salt)
	{
		if (!is_string($salt))
			throw new InvalidArgumentException;

		if (strlen($salt) !== 29)
			return false;


		// Split into header and data
		$salt_header = substr($salt, 0, 7);
		$salt        = substr($salt, 7);

		// Validate header pattern and check salt characters
		if (!preg_match('/(\$2y\$\d\d\$)/', $salt_header) || !self::is_valid_hash($salt))
			return false;

		return true;
	}

	final public static function is_valid_hash($hash)
	{
		if (!is_string($hash))
			throw new InvalidArgumentException;


		return (strlen($hash) === 31) && !preg_match('/[^\.\/0-9A-Za-z]/', $hash);
	}
}


class CipherEncryptionFailedException extends RuntimeException
{
}

class CipherInvalidSaltException extends RuntimeException
{
}

class CipherSaltCostInvalidException extends RuntimeException
{
}