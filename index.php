<?php
namespace Kiwi;

require_once('./config.php');

require_once('./includes/database.php');
require_once('./includes/account.php');
require_once('./includes/template.php');
require_once('./includes/common.php');
require_once('./includes/cipher.php');
require_once('./includes/session.php');


Database::connect(Config::SQL_HOST, Config::SQL_USER, Config::SQL_PASSWORD, Config::SQL_DATABASE);

/*var_dump(Database::delete('sale', ['sala_id' => ['>', 0]]));
var_dump(Database::insert('sale', ['numer_sali' => '1D', 'ilosc_miejsc' => 10]));
var_dump($a = Database::insert('sale', ['numer_sali' => '1A', 'ilosc_miejsc' => 11]));
var_dump(Database::insert('sale', ['numer_sali' => '1B', 'ilosc_miejsc' => 40]));
var_dump($b = Database::insert('sale', ['numer_sali' => '99C', 'ilosc_miejsc' => 10]));

var_dump(Database::update('sale', ['ilosc_miejsc' => 51], ['numer_sali' => '1D']));
var_dump(Database::delete('sale', [['ilosc_miejsc' => ['=', 11]], ['sala_id' => $a], ['sala_id' => $b]]));

Database::select('sale', ['numer_sali', 'ilosc_miejsc'], ['sala_id' => ['>', 0]]);*/

/*list ($a, $salt) = Cipher::encrypt('test');

$b = Cipher::encrypt('test', $salt);

$c = Cipher::verify($a, $b);

var_dump($a);
var_dump($b);
var_dump($c);

var_dump(hash_equals($a, 'test'));*/

/*var_dump(Cipher::encrypt('test'));
var_dump(Cipher::encrypt('trolololo', Cipher::generate_salt(10)));
var_dump(Cipher::encrypt('trolololo', $salt = Cipher::generate_salt(10)));
var_dump(Cipher::encrypt('test', $salt));*/

//var_dump(Session::authorize());
//var_dump(Session::load(1));
//var_dump(Session::open(1));
//var_dump($a = Session::find(1));
//var_dump(Session::close($a->get_id()));
//var_dump(Session::close(17));
//
var_dump(Account::create('dupaaasaaaaa', 'dupasaaaaa@dupa.com', 'dupa123'));
//var_dump($a = Account::authorize('dupaaa', 'test1234'));
//var_dump(Account::delete($a->get_id()));
//var_dump($s = Session::open($a->get_id()));
//var_dump($s->kill());