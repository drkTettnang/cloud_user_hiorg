<?php
echo 'OK: '.base64_encode(serialize(array(
		'ov' => 'ttt',
		'name' => 'Bar',
		'vorname' => 'Foo',
		'kuerzel' => 'FooBa',
		'gruppe' => '41',
		'perms' => 'helfer',
		'username' => 'foo.bar',
		'email' => 'foo@bar',
		'quali' => '3',
		'user_id' => '832ajksbe383jkasb3kjb3k3',
		'login_expires' => time() + 60*30
)));
?>
