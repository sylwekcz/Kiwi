<?php
namespace Kiwi;

use InvalidArgumentException;
use RuntimeException;


/**
 * Encryption class that uses blowfish for hashing
 * @package Kiwi
 */
abstract class Cipher
{
	/**
	 * Encrypt data using blowfish hashing algorithm
	 *
	 * @param string $data Data to encrypt, surely you can encrypt nothing if you want some random data...
	 * @param string $salt Optional. If empty, new salt of cost 10 will be generated
	 *
	 * @return array|string Generated hash, additionally salt will be returned if no custom salt was provided
	 *
	 * @throws InvalidArgumentException On invalid input
	 * @throws CipherSaltCostInvalidException On invalid salt
	 * @throws CipherEncryptionFailedException On hashing fail (wrongly built salt?)
	 */
	final public static function encrypt($data = '', $salt = '')
	{
		if (!is_string($data) || !is_string($salt))
			throw new InvalidArgumentException;


		$custom_salt = false;

		// No custom salt provided, generate random
		if (empty($salt))
			$salt = self::generate_salt(10);
		else
		{
			// You are doing it wrong...
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

		// No need to return salt
		if ($custom_salt)
			return $hash;

		return [$hash, $salt];
	}

	/**
	 * Compare hashes
	 *
	 * @param string $hash_one First hash
	 * @param string $hash_two Second hash
	 *
	 * @return bool Whenever hashes are equal
	 *
	 * @throws InvalidArgumentException On invalid input
	 */
	final public static function verify($hash_one, $hash_two)
	{
		if (!self::is_valid_hash($hash_one) || !self::is_valid_hash($hash_two))
			throw new InvalidArgumentException;


		return hash_equals($hash_one, $hash_two);
	}

	/**
	 * Generate random salt
	 *
	 * @param int $cost Blowfish hash cost in range of 5(fast) to 20(slow), 10 is mostly recommended
	 *
	 * @return string Generated salt
	 *
	 * @throws InvalidArgumentException On invalid input
	 * @throws CipherSaltCostInvalidException On invalid cost
	 */
	final public static function generate_salt($cost)
	{
		if (!is_int($cost))
			throw new InvalidArgumentException;

		// Too weak/strong salt
		if (($cost < 5) || ($cost > 20))
			throw new CipherSaltCostInvalidException;


		// Build salt characters
		$salt_pieces = array_merge(['.', '/'], range('A', 'Z'), range('a', 'z'), range(0, 9));
		$salt = '';

		// Build 22 characters long salt
		for ($i = 0; $i < 22; $i++)
			$salt .= $salt_pieces[array_rand($salt_pieces)]; // Get "random >.>" character

		// TODO: ssl pseudo maybe ?

		// Build header
		$salt_header = sprintf('$2y$%02d$', $cost);

		return $salt_header . $salt;
	}

	/**
	 * Validate salt structure
	 *
	 * @param string $salt Salt to test
	 *
	 * @return bool Whenever salt is valid
	 *
	 * @throws InvalidArgumentException On invalid input
	 */
	final public static function is_valid_salt($salt)
	{
		if (!is_string($salt))
			throw new InvalidArgumentException;

		// Header - 7, salt - 22 bytes
		if (strlen($salt) != 29)
			return false;


		// Split into header and data
		$salt_header = substr($salt, 0, 7);
		$salt        = substr($salt, 7);

		// Validate header pattern and check salt characters
		if (!preg_match('/(\$2y\$\d\d\$)/', $salt_header) || preg_match('/[^\.\/0-9A-Za-z]/', $salt))
			return false;

		return true;
	}

	/**
	 * Validate hash structure
	 *
	 * @param string $hash Hash to test
	 *
	 * @return bool Whenever hash is valid
	 *
	 * @throws InvalidArgumentException On invalid input
	 */
	final public static function is_valid_hash($hash)
	{
		if (!is_string($hash))
			throw new InvalidArgumentException;


		return (strlen($hash) == 31) && !preg_match('/[^\.\/0-9A-Za-z]/', $hash);
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