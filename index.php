<?php
namespace Kiwi;

require_once('./config.php');

require_once('./includes/database.php');
require_once('./includes/account.php');
require_once('./includes/template.php');
require_once('./includes/common.php');


Database::connect();
var_dump(Database::delete('sale', ['sala_id' => ['>', 0]]));
var_dump(Database::insert('sale', ['numer_sali' => '1D', 'ilosc_miejsc' => 10]));
var_dump($a = Database::insert('sale', ['numer_sali' => '1A', 'ilosc_miejsc' => 11]));
var_dump(Database::insert('sale', ['numer_sali' => '1B', 'ilosc_miejsc' => 40]));
var_dump($b = Database::insert('sale', ['numer_sali' => '99C', 'ilosc_miejsc' => 10]));

var_dump(Database::update('sale', ['ilosc_miejsc' => 51], ['numer_sali' => '1D']));
var_dump(Database::delete('sale', [['ilosc_miejsc' => ['>', 11]], ['sala_id' => $a], ['sala_id' => $b]]));