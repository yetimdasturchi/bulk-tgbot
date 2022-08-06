<?php
	date_default_timezone_set('Asia/Tashkent');
	$config['token'] = ''; //Telegram bot tokeni majburiy
	$config['bot_id'] = ''; //Telegram bot idenfikatori majburiy
	$config['owners'] = ['441307831']; //Bot administratorlari idenfikatori massiv shaklda
	include 'Telegram.php';
	include 'functions.php';
	
	$tg_settings = [
		'token' => $config['token']
	];

	$tg = new Telegram( $tg_settings );
?>
