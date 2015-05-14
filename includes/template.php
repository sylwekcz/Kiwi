<?php
namespace Kiwi;

use \RuntimeException;
use \InvalidArgumentException;


class Template
{
	/** @var string File contents */
	private $_data = '';

	/** @var array Template variables */
	private $_variables = [];


	/**
	 * Load template
	 *
	 * @param string $name Template name
	 */
	final public function __construct($name)
	{
		if (!is_string($name)) // Type checks
			throw new InvalidArgumentException;


		$file_path = $_SERVER['DOCUMENT_ROOT'] . '/' . Config::TEMPLATE_DIRECTORY . '/' . $name . '.' . Config::TEMPLATE_EXTENSION;

		if (!file_exists($file_path)) // File not found maybe
			throw new TemplateNotReachableException;

		// Read the whole file so it can be parsed
		if (($this->_data = file_get_contents($file_path)) === false)
			throw new TemplateNotFoundException;


		preg_match_all('/{{(.*?)}}/e', $this->_data, $this->_variables); // Get list of variables (variable is the content between {{ and }})
		$this->_variables = array_fill_keys(array_values($this->_variables[1]), null); // We need to switch columns since preg_match returns data as values with numeric indexes, we need it as keys
	}


	/**
	 * Set variable value
	 *
	 * @param string $target Variable name
	 * @param string $data   New value, empty string allowed
	 *
	 * @return bool True if variable exists and has been set, false otherwise
	 */
	final public function parse_variable($target, $data)
	{
		if (!is_string($target) || !is_string($data)) // Type checks
			throw new InvalidArgumentException;


		foreach ($this->_variables as $variable => &$value) // Find and override variable data
		{
			if ($variable === $target) // For sure
			{
				$value = $data;

				return true;
			}
		}

		return false; // Variable not found, nothing loaded...
	}

	/**
	 * Compile and print template
	 */
	final public function execute()
	{
		$prepared_data = [];

		// Replace only loaded variables
		foreach ($this->_variables as $variable => $value)
			$prepared_data['/{{' . $variable . '}}/'] = $value;

		echo preg_replace(array_keys($prepared_data), array_values($prepared_data), $this->_data);

		unset($prepared_data);
	}
}


class TemplateNotFoundException extends RuntimeException
{
}

class TemplateNotReachableException extends RuntimeException
{
}