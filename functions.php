<?php
function setUserConfig($chat_id='', $key='', $value='') {
	$file = 'data/users/'.$chat_id.'.json';
    if (file_exists( $file )) {
		$user_data = file_get_contents( $file );
		$user_data = json_decode( $user_data, TRUE );
	}else{
		$user_data = [];
	}
	$user_data[$key] = $value; 
	write_file( $file, json_encode( $user_data ) );

	return TRUE;
}

function getUserConfig($chat_id='', $key='') {
	$file = 'data/users/'.$chat_id.'.json';
	if (file_exists( $file )) {
		$user_data = file_get_contents( $file );
		$user_data = json_decode( $user_data, TRUE );
	}else{
        $user_data = [];
    }

	if (array_key_exists($key, $user_data)) {
        return $user_data[$key];
    }

	return FALSE;
}

function write_file( $path, $data, $mode = 'wb') {
	if ( ! $fp = @fopen( $path, $mode ) ) return FALSE;

	flock( $fp, LOCK_EX );

	for ( $result = $written = 0, $length = strlen( $data ); $written < $length; $written += $result ) {
		if ( ( $result = fwrite( $fp, substr( $data, $written ) ) ) === FALSE ) break;
	}

	flock( $fp, LOCK_UN );
	fclose( $fp );

	return is_int( $result );
}

function get_users($all=FALSE) {
    global $config;
    $users = glob('data/users/*.json');
    $temp_users = [];
    foreach ($users as $user) {
        $fileName = basename($user);
        $chat_id = str_replace('.json', '', $fileName);
        if (!$all) {
        	if ( in_array( $chat_id, $config['owners'] ) ) continue;
        }
        if(file_exists($user)){
            $user = file_get_contents($user);
            $user = json_decode($user, TRUE);
            $user['id'] = $chat_id;
            $temp_users[] = $user;
            
        }
    }

    usort($temp_users, function( $a, $b ) {
    	if(empty($a['lastaction'])) $a['lastaction'] = 0;
    	if(empty($b['lastaction'])) $b['lastaction'] = 0;
    	return $b['lastaction'] <=> $a['lastaction'];
	});
    
    return $temp_users;
}

function get_applications($type, $del=FALSE) {
	global $config;
    $apps = glob('data/requests/'.$type.'/*.json');
    $temp_applications = [];
    foreach ($apps as $app) {
        if(file_exists($app)){
            $filename = $app;
            $app = file_get_contents($app);
            $app = json_decode($app, TRUE);
            $app['filename'] = $filename;
            if($del){
        		if (intval($app['time']) == intval($del)) {
        			@unlink($filename);
        		}
        	}else{
        		$temp_applications[] = $app;
        	}    
        }
    }

    usort($temp_applications, function( $a, $b ) {
    	if(empty($a['time'])) $a['time'] = 0;
    	if(empty($b['time'])) $b['time'] = 0;
    	return $b['time'] <=> $a['time'];
	});
    
    return $temp_applications;
}
function application($application, $applications_count) {
	$users = get_users();
	$user = [];
	foreach ($users as $u) {
		if ($u['id'] == $application['chat_id']) {
			$user = $u;
			break;	
		}
	}

	$message = "";
	if ( !empty( $user['id'] ) ) {
    	$message .=  "🆔 <a href=\"tg://user?id={$user['id']}\">{$user['id']}</a>".PHP_EOL;
    	$message .= str_repeat("-", 40).PHP_EOL;
    }
	if ( !empty( $user['first_name'] ) ) {
    	$message .= "▫️ {$user['first_name']}".PHP_EOL;   
	}
    if ( !empty( $user['last_name'] ) ) {
    	$message .= "▫️ {$user['last_name']}".PHP_EOL;
    }
    if ( !empty( $user['username'] ) ) {
    	$message .= "▫️ @{$user['username']}".PHP_EOL;
    }

    $message .= str_repeat("-", 40).PHP_EOL;
    $message .= "🕐 ".date("Y-m-d | H:i:s", $application['time']).PHP_EOL;
    $message .= str_repeat("-", 40).PHP_EOL;
    if (array_key_exists('text', $application)) {
        $message .= $application['text'].PHP_EOL;
    }else if (array_key_exists('caption', $application)) {
        $message .= $application['caption'].PHP_EOL;
    }

	$message .= str_repeat("-", 40);
	$message .= PHP_EOL. "📝 Jami: {$applications_count}";
	return $message;
}

function addRequest($type='', $data=[]) {
	global $config, $tg;
	$fileName = 'data/requests/'.$type.'/' . md5( generate_uuid() . time() ).'.json';
	file_put_contents($fileName, json_encode($data));
	return TRUE;
}

function getPagination( $query, $current, $maxpage, $type ) {
    $q = $query;
    $keys = [];
    if ($current>0) $keys[] = ['text' => "◀️ Avvalgi", 'callback_data' => http_build_query([$type => $q, 'prev' => strval(($current-1))])];
    if ($current != $maxpage-1)  $keys[] = ['text' => "Keyingi ▶️", 'callback_data' => http_build_query([$type => $q, 'next' => strval(($current+1))])];
    //if ($current<$maxpage) $keys[] = ['text' => strval($maxpage).'»', 'callback_data' => strval($maxpage)];
	return [$keys];
}

function user($user, $users_count) {
	global $config, $tg;
	$message = "";
	foreach($user as $k => $v){ $user[$k] = htmlentities($v);};
	if ( !empty( $user['id'] ) ) {
    	$message .= "🆔 <a href=\"tg://user?id={$user['id']}\">{$user['id']}</a>".PHP_EOL;
    	$message .= str_repeat("-", 40).PHP_EOL;
    }
	if ( !empty( $user['first_name'] ) ) {
    	$message .= "▫️ {$user['first_name']}".PHP_EOL;   
	}
    if ( !empty( $user['last_name'] ) ) {
    	$message .= "▫️ {$user['last_name']}".PHP_EOL;
    }
    if ( !empty( $user['username'] ) ) {
    	$message .= "👤 <span class=\"tg-spoiler\">@{$user['username']}</span>".PHP_EOL;
    }
    if ( !empty( $user['lastmessage'] ) || !empty( $user['lastaction'] ) ) {
    	$message .= PHP_EOL.str_repeat("-", 40).PHP_EOL;
    }
    if ( !empty( $user['lastmessage'] ) ) {
    	$message .= "💬 {$user['lastmessage']}".PHP_EOL;
	}
	if ( !empty( $user['lastaction'] ) ) {
    	$lastaction = date("Y-m-d | H:i:s", $user['lastaction']);
        $message .= "🕐 {$lastaction}".PHP_EOL;
	}

	$message .= str_repeat("-", 40);
	$message .= PHP_EOL."👥 Jami:" . " {$users_count}";
	return $message;
}

function generate_uuid() {
    return sprintf( '%04x%04x%04x',
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
    );
}

function add_notifications($message) {
	$users = get_users(TRUE);
	$users_count = count( $users );
	if ($users_count == 0) return FALSE;

	foreach ($users as $user) {
		$message['chat_id'] = $user['id'];
    	$id = 'data/notifications/' . md5( generate_uuid() . time() ).'.json';
		file_put_contents($id, json_encode($message));
		usleep(2);
	}

	return TRUE;
}

function clear_notification(){
	array_map( 'unlink', array_filter((array) glob("data/notifications/*") ) );
}

function message_status($set=FALSE){
	if ($set == 'count') {
		return count(
			glob("data/notifications/*.{json}",GLOB_BRACE)
		);
	}

	$status_file = dirname(__FILE__).'/data/status.dat';
	
	if (in_array($set, ['on', 'off'])) {
		file_put_contents($status_file, $set);
		return $set;
	}

	if ( file_exists($status_file) ) {
		return file_get_contents($status_file);
	}

	return 'on';
}
?>
