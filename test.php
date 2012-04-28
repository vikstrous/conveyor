<?php
require('conveyor.php');

class ConveyorTest {

	private static $c = array(
		'normal' => "\033[0m",
		'gray' => "\033[90m",
		'red' => "\033[91m",
		'green' => "\033[92m",
		'yellow' => "\033[93m",
		'blue' => "\033[94m",
		'magenta' => "\033[95m",
		'cyan' => "\033[96m",
		'white' => "\033[97m"
		);

	public static function test_all(){
		self::test_pattern_to_regex();
		self::test_apply();
	}

	public static function test_pattern_to_regex(){
		$c = self::$c;
		echo "$c[magenta]test_pattern_to_regex()$c[normal]\n";
		$pass = array(
			'/' => '/',
			'/child1' => '/child1',
			'/child?' => '/child?',
			'/*' => '/anychild',
			'/*/one' => '/anything/one',
			'one' => '/anything/one',
			'anything/one' => '/anything/one',
			'*/one' => '/anything/one',
			'*/*' => '/anything/one',
			'**' => '/anything/one',
			'*' => '/anything/one',
			'th\ing\/' => '/anything/th\ing\/',
			'thing\/' => '/anything/thing\/',
			'anything/**' => '/anything/one',
			'anything2/**' => '/anything2/one/two'
			);
		$fail = array(
			'' => '/',
			'/' => '/notroot',
			'test' => '/test/not',
			'anything/**' => '/not/one'
			);


		foreach ($pass as $pattern => $match){
			$regex = Conveyor::_pattern_to_regex($pattern);
			if (!preg_match($regex, $match))
				echo "$c[blue]$pattern$c[normal] ($c[cyan]$regex$c[normal]) should match $c[white]$match$c[normal] \n";
			else
				echo "$c[green]Pass!$c[normal]\n";
		}
		foreach ($fail as $pattern => $match){
			$regex = Conveyor::_pattern_to_regex($pattern);
			if (preg_match($regex, $match))
				echo "$c[blue]$pattern$c[normal] ($c[cyan]$regex$c[normal]) should $c[red]not$c[normal] match $c[white]$match$c[normal] \n";
			else
				echo "$c[green]Pass!$c[normal]\n";
		}
	}

	public static function test_apply(){
		$c = self::$c;
		echo "$c[magenta]test_apply()$c[normal]\n";
		$tests = array(
			array(
				'data' => array('hello' => 'world'),
				'logic' => array('/hello' => function(&$value, $path) {
					$value = 'Conveyor';
				}),
				'result' => array('hello' => 'Conveyor')
			),
			array(
				'data' => array('world1', 'world2', 'world3'),
				'logic' => array('/*' => function(&$value, $path) {
					$value = $value . ' visited';
				}),
				'result' => array('world1 visited', 'world2 visited', 'world3 visited')
			),
			array(
				'data' => array('hello' => array('world1', 'world2', 'world3')),
				'logic' => array('/hello/*' => function(&$value, $path) {
					$value = $value . ' visited';
				}),
				'result' => array('hello' => array('world1 visited', 'world2 visited', 'world3 visited'))
			),
			array(
				'data' => array('hello' => array('world0', 'world1', 'world2')),
				'logic' => array(
					'/hello/*' => function(&$value, $path) {
						if($path == '/hello/1'){
							return false;
						} else {
							$value = $value . ' visited';
							return true;
						}
					}
				),
				'result' => array('hello' => array('world0 visited', 'world2 visited'))
			),
			array(
				'data' => array('hello' => array('world0', 'world1', 'world2')),
				'logic' => array(
					'/hello/1' => function(&$value, $path) {
						return false;
					},
					'/hello/*' => function(&$value, $path) {
						$value = $value . ' visited';
					}
				),
				'result' => array('hello' => array('world0 visited', 'world2 visited'))
			),
			array(
				'data' => array('hello' => array('a'=>'world0', 'b'=>'world1', 'c'=>'world2')),
				'logic' => array(
					'/hello/b' => function(&$value, $path) {
						return false;
					},
					'/hello/*' => function(&$value, $path) {
						$value = $value . ' visited';
					}
				),
				'result' => array('hello' => array('a'=>'world0 visited', 'c'=>'world2 visited'))
			),
			array(
				'data' => array('hello' => 'world'),
				'logic' => array('/' => function(&$value, $path) {
					return false;
				}),
				'result' => null
			),
			array(
				'data' => 'data',
				'logic' => array('/' => Conveyor::make_namer('weee')),
				'result' => array('weee' => 'data')
			),
			array(
				'data' => array(0,1,2,3),
				'logic' => array(
					'/' => Conveyor::make_rowifier(2, 'row')
				),
				'result' => array(
					array('row' => 
						array(0,1)
					),
					array('row' => 
						array(2,3)
					)
				)
			),
			array(
				'data' => array(0,1,2,3),
				'logic' => array(
					'/' => Conveyor::make_rowifier(2)
				),
				'result' => array(
					array(0,1), 
					array(2,3)
				)
			)
		);
		foreach ($tests as $key => $test){
			$res = Conveyor::apply($test['data'], $test['logic']);
			if($res == $test['result']){
				echo "$c[green]Pass!$c[normal]\n";
			} else {
				echo $c['red'];
				echo "Test $key failed.\n";
				echo $c['normal'];
				var_dump($res);
				echo $c['yellow'];
				echo "should be\n";
				echo $c['normal'];
				var_dump($test['result']);
			}
		}
	}
}

ConveyorTest::test_all();