<?php
namespace Kiwi;


/**
 * Get array values references
 *
 * @param array $array Input array
 * @param bool $plain Determinate whenever to copy the array keys or not
 *
 * @return array Array of value references
 */
function array_references($array, $plain = false)
{
	$references = [];

	foreach ($array as $key => $value)
	{
		if (!$plain) $references[$key] = &$array[$key];
		else $references[] = &$array[$key];
	}

	return $references;
}

/**
 * Get browser IP and User Agent
 * @return array
 */
function get_browser_id()
{
	return md5($_SERVER['HTTP_USER_AGENT']);
	//return Cipher::encrypt($_SERVER['HTTP_USER_AGENT'], '$2y$05$gfQ2/G0UNDFlkuTashxc.l');
}


/**
 * Make sure given data contains only safe (word) characters
 *
 * @param string|array $data Input containing name/names
 *
 * @return bool True if name/names are safe, false otherwise
 */
function is_safe_string($data)
{
	return ((is_string($data) && preg_match('/(\W)/', $data)) || (is_array($data) && preg_grep('/(\W)/', $data)));
}

/**
 * Make sure email is in correct format
 *
 * @param string $email String to verify
 *
 * @return bool Whenever emails is in valid format
 */
function is_valid_email($email)
{
	return is_string($email) && (preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$/', $email) === 1);
}

/**
 * Make sure login is in correct format
 *
 * @param string $login String to verify
 *
 * @return bool Whenever login is in valid format
 */
function is_valid_login($login)
{
	return is_string($login) && (preg_match('/^[a-zA-Z0-9_]{5,16}$/', $login) === 1);
}

/**
 * Make sure password is in correct format
 * @param string $password String to verify
 *
 * @return bool Whenever password is in valid format
 */
function is_valid_password($password)
{
	return is_string($password) && (strlen($password) > 5);
}

/**
 * Make sure ID is correct
 * @param int $id Number to verify
 *
 * @return bool Whenever ID is valid
 */
function is_valid_id($id)
{
	return is_int($id) && ($id > 0);
}