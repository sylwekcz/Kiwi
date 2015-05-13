<?php


/**
 * Get array values references
 *
 * @param array $array Input array
 *
 * @return array Array of value references
 */
function array_references($array)
{
	$references = [];

	foreach ($array as $key => $value)
		$references[$key] = &$array[$key];

	return $references;
}