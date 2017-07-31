<?php
$this->create('user_hiorg_index', '/')
	->actionInclude('user_hiorg/index.php');

$this->create('user_hiorg_ajax_setsettings', 'ajax/setSettings.php')
	->actionInclude('user_hiorg/ajax/setSettings.php');
