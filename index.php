<?php
namespace Kiwi;

require_once('./config.php');

require_once('./includes/database.php');
//require_once('./includes/account.php');
require_once('./includes/template.php');
require_once('./includes/common.php');


Database::connect();
//echo Database::insert('sale', ['numer_sali' => '1A', 'ilosc_miejsc' => 40], 'si') ? 'dodano' : 'duplikat';


var_dump(Database::_parseConditions(
		[
				[
						'a' => ['<=', 10],
						'b' => ['!%', 'aaaa']
				],
				[
						['a' => ['!=', 10]],
						[['b' => ['%', 'bbbb']],
						['t' => null]]
				],
				['a' =>
						 ['=' , 1]
				]
		]));