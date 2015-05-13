<?php
namespace Kiwi;

use \RuntimeException;
use \InvalidArgumentException;


class Template
{
	private $fileData     = ''; // File contents
	private $variableData = []; // Variables with values


	/**
	 * Load template
	 *
*@param string $templateName Template name
	 */
	final public function __construct($templateName)
	{
		if (!is_string($templateName)) // Type checks
			throw new InvalidArgumentException;


		$filePath = $_SERVER['DOCUMENT_ROOT'] . '/' . Config::TEMPLATE_DIRECTORY . '/' . $templateName . '.' . Config::TEMPLATE_EXTENSION;

		if (!file_exists($filePath)) // File not found maybe
			throw new TemplateNotReachableException;

		// Read the whole file so it can be parsed
		if (($this->fileData = file_get_contents($filePath)) === false)
			throw new TemplateNotFoundException;


		preg_match_all('/{{(.*?)}}/e', $this->fileData, $this->variableData); // Get list of variables (variable is the content between {{ and }})
		$this->variableData = array_fill_keys(array_values($this->variableData[1]), null); // We need to switch columns since preg_match returns data as values with numeric indexes, we need it as keys
	}


	/**
	 * Set variable value
	 *
	 * @param string $variableName Variable name
	 * @param string $newData New value
	 *
	 * @return bool True if variable exists and has been set, false otherwise
	 */
	final public function parseVariable($variableName, $newData)
	{
		if (!is_string($variableName) || !is_string($newData)) // Type checks
			throw new InvalidArgumentException;


		foreach ($this->variableData as $variable => &$value) // Find and override variable data
		{
			if ($variable === $variableName) // For sure
			{
				$value = $newData;
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
		$variableData = [];

		// Replace only loaded variables
		foreach ($this->variableData as $variable => $value)
			$variableData['/{{' . $variable . '}}/'] = $value;

		echo preg_replace(array_keys($variableData), array_values($variableData), $this->fileData);

		unset($variableData);
	}
}


class TemplateNotFoundException     extends RuntimeException { }
class TemplateNotReachableException extends RuntimeException { }