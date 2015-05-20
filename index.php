<?php
namespace Kiwi;
/*
require_once('./config.php');

require_once('./includes/database.php');
require_once('./includes/card.php');
require_once('./includes/account.php');
require_once('./includes/template.php');
require_once('./includes/common.php');
require_once('./includes/cipher.php');
require_once('./includes/session.php');*/

ini_set('xdebug.var_display_max_depth', -1);
ini_set('xdebug.var_display_max_children', -1);
ini_set('xdebug.var_display_max_data', -1);


class Test
{
	private static $cache = [];

	public $a;

	private function __construct()
	{
		$this->a = 10;
		echo 'dupa';
	}

	public static function &b()
	{
		foreach (self::$cache as &$test)
			if ($test->a == 10) // It might have been loaded already...
				return $test; // If so, return reference


		$u = new Test();
		$p = $u;
		$y = &array_insert(self::$cache, $p);

		return $y;
	}

	public function d()
	{
		self::$cache[0] = null;
		unset(self::$cache[0]);
	}
}

function &array_insert(&$array, $value)
{
	array_push($array, $value);
	end($array);
	$a = &$array[key($array)];

	return $a;
}


$reference1 = &Test::b();
$reference2 = &Test::b();

$reference2->d();

var_dump($reference1);
var_dump($reference2);


/*
$array = [];

$tes = new Test();
$test = &$tes;
/*
array_push($array, new Test());
var_dump($array);

end($array);*/
/*
$reference = &array_insert($array, new Test());
//$reference1 = &$array[key($array)];
//$reference2 = &$array[key($array)];
//$reference3 = &$array[key($array)];
//$reference4 = &$array[key($array)];

echo 'poof';

//$reference2->a = 10;
$array[0]->a = 11;

$array[0] = null;
unset($array[0]);

var_dump($array);

var_dump($reference);
//var_dump($reference2);
//var_dump($reference3);
//var_dump($reference4);

*/


//Database::connect(Config::SQL_HOST, Config::SQL_USER, Config::SQL_PASSWORD, Config::SQL_DATABASE);

/*
$card = Card::create(
	['first_name'   => 'name',
	 'middle_name'  => 'middle',
	 'surname'      => 'surname',
	 'birth_date'   => 10,
	 'phone_number' => '12345',
	 'address'      => 'address',
	 'city'         => 'city',
	 'postal_code'  => 'code',
	 'street'       => 'street']);*/
/*
$card1 = Card::load(11);
//$card2 = Card::load(11);


//var_dump($card1->get_field('surname'));
//var_dump($card2->get_field('surname'));
//$card1->set_field('surname', 'test');

//$card1->update();
$a = 'b';
$$a = 'a';

$account = Account::create('account', 'email@random.com', 'password', $card1);
$account = Account::authorize('account', 'password');
$account1 = Account::authorize('account', 'password');

var_dump(Account::delete($account));

var_dump($account);
var_dump($account1);
*/


//var_dump($session = Session::open($account2));
//var_dump($session = Session::find($account2));
//var_dump($session = Session::authorize());
//var_dump($session->kill());














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


//var_dump(Account::delete($a->get_id()));
//var_dump($s = Session::open($a->get_id()));
//var_dump($s->kill());
//
//var_dump(Card::create(['first_name' => 'a', 'middle_name' => 'b', 'surname' => 'c', 'birth_date' => 0, 'phone_number' => 'd', 'address' => 'e', 'city' => 'f', 'postal_code' => 'g', 'street' => 'j']));
//$c = Card::load(1);
//$c->set_field('phone_number', 'b');
//var_dump($c);
//$c->update();
//var_dump($c);

