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
	const SQL_DATABASE = 'kiwi2';

	/*
	 * sMYSQL tables
	 */
	const SQL_TABLE_CARDS = 'cards';
	const SQL_TABLE_ACCOUNTS = 'accounts';
	const SQL_TABLE_SESSIONS = 'sessions';

	const SQL_TABLE_TEACHERS = 'teachers';
	const SQL_TABLE_STUDENTS = 'students';

	const SQL_TABLE_ROOMS   = 'rooms';
	const SQL_TABLE_COURSES = 'courses';

	const SQL_TABLE_GROUPS            = 'groups';
	const SQL_TABLE_GROUP_ASSIGNMENTS = 'group_assignments';
	const SQL_TABLE_GROUP_MEETINGS    = 'group_meetings';

	const SQL_TABLE_LANGUAGES = 'languages';


	/*
	 * Account config
	 */
	const ACCOUNT_PASSWORD_MIN_LENGTH = 5;
	const ACCOUNT_PASSWORD_MAX_LENGTH = 32;

	const ACCOUNT_NAME_LENGTH    = 25;
	const ACCOUNT_SURNAME_LENGTH = 30;

	/*
	 * Session config
	 */
	const SESSION_TIMEOUT = 1800;
	const SESSION_COOKIE  = 'kiwi_session';

	/*
	 * Room config
	 */
	const ROOM_NUMBER_LENGTH    = 5;
	const ROOM_CAPACITY_LIMIT   = 300;
	const ROOM_CAPACITY_MINIMUM = 10;

	/*
	 * Language config
	 */
	const LANGUAGE_NAME_LENGTH  = 20;
	const LANGUAGE_LEVEL_LENGTH = 2;
}