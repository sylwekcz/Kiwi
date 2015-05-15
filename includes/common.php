<?php


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
 * Make sure given data contains only safe (word) characters
 *
 * @param string|array $data Input containing name/names
 *
 * @return bool True if name/names are safe, false otherwise
 */
function is_safe_string($data)
{
	if ((is_string($data) && preg_match('/(\W)/', $data)) || (is_array($data) && preg_grep('/(\W)/', $data)))
		return false;

	return true;
}

function is_valid_email($email)
{
	// TODO
	return true;
}

function is_valid_login($login)
{
	// TODO
	return true;
}

function is_valid_password($password)
{
	// TODO
	return true;
}

function is_valid_id($id)
{
	return is_int($id) && ($id > 0);
}