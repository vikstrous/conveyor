<?php

require('conveyor.php');

$data = array(
   array(
      "name" => "foo",
      "age" => 24
   ),
   array(
      "name" => "bar",
      "age" => 23
   ),
   array(
      "name" => "baz",
      "age" => 30
   ),
   array(
      "name" => "Anonymous",
      "age" => 14
   )
);

$template = <<<EOF
{{#users}}
	<p>Name: {{name}}</p>
	<p>Age: {{age}}</p>
{{/users}}
EOF;

$logic = array(
	// Name the list of users.
	"/" => Conveyor::make_namer("users"),
	// Sort users by age.
	"/users" => function(&$value, $path){
		$indices = array();
		foreach($value as $obj) {
		    $indeces[] = $obj['age'];
		}
		array_multisort($indeces,$value);
	},
	// Don't display users under 18.
	"/users/*" => function(&$value, $path){
		if($value['age'] < 18){
			return false;
		}
	},
	// Capitalize the first letter of usersname.
	"name" => function(&$value, $path){
		$value = strtoupper($value[0]) . substr($value, 1);
	}
);
echo "Data out:\n";
var_dump(Conveyor::apply($data, $logic));
echo "Output:\n";
echo Conveyor::render($template, $data, $logic);