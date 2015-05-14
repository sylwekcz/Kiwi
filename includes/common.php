<?php


/**
 * Get array values references
 *
 * @param array $array Input array
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

	var_dump($references);
	return $references;
}