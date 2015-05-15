<?php
namespace Kiwi;


abstract class Config
{
	/*
	 * Directories
	 */
	const INCLUDE_DIRECTORY  = 'includes';
	const TEMPLATE_DIRECTORY = 'templates';

	const TEMPLATE_EXTENSION = 'tpl';

	/*
	 * MYSQL database config
	 */
	const SQL_HOST     = 'localhost';
	const SQL_USER     = 'root';
	const SQL_PASSWORD = 'dupadupa7';
	const SQL_DATABASE = 'kiwi';


	/*
	 * Session config
	 */
	const SESSION_TIMEOUT = 30;
}