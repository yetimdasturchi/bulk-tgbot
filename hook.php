<?php
uopz_allow_exit(true);
/*ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set("error_log", "php-error.log");
error_reporting(E_ALL);
*/
include 'config.php';

$updates = $tg->get_webhookUpdates();

$startMessage = function(){
    global $tg, $config;
    setUserConfig($tg->get_chatId(), 'lastmessage', '/start');
    if ( in_array( $tg->get_chatId(), $config['owners'] ) )  {
        $tg->send_chatAction('typing')->set_replyKeyboard([
            ['👉 Forward', '✍️ Bildirishnoma', '🟢 Holat'],
            ['✍️ Yozma murojaat', '📸 Foto murojaat', '📽 Video murojaat'],
            ['👨‍👩‍👧 Foydalanuvchilar']
        ])->send_message("Harakatni davom ettirish uchun quyidagi bo'limlardan birini tanlang 👇");
    }else{
        $buttons = [
            ['☎️ Aloqa', '🤖 Bot haqida']
        ];
        if (getUserConfig($tg->get_chatId(), 'welcome') == 'yes' ) {
            $tg->send_chatAction('typing')->set_replyKeyboard($buttons)->send_message("Harakatni davom ettirish uchun quyidagilardan birini tanlang 👇");
        }else{
            setUserConfig($tg->get_chatId(), 'welcome', 'yes');
            $tg->send_chatAction('typing')->set_replyKeyboard($buttons)->send_message("👋 Assalomu alaykum\n\nMen sizni turli ma'lumotlar haqida tezkorlik bilan xabardor qilib turaman.");
        }
    }
    
};

$startusMessages = function(){
    global $tg, $config;
    
    $count_notifications = message_status('count');
    $status = (message_status() == 'on') ? '🟢' : '🔴';
    $tg->send_chatAction('typing')->set_inlineKeyboard([
        [
            [
                "text" => "🟢",
                "callback_data" => "status=on"
            ],
            [
                "text" => "🔄",
                "callback_data" => "status=check"
            ],
            [
                "text" => "🔴",
                "callback_data" => "status=off"
            ]
        ],
        [
            [
                "text" => "🗑 Tozalash",
                "callback_data" => "clear=true"
            ]
        ],
    ])->send_message( "Bildirishnoma yuborish holati: {$status}\n\n⏳ Jarayondagi xabarlar: {$count_notifications}");
    
};

$applicationMessage = function($type){
    global $tg, $config;
    $applications = get_applications($type);
    $applications_count = count($applications);
    if ( $applications_count == 0 ) {
        $tg->send_chatAction('typing')->send_message( '❌ Murojaatlar mavjud emas' );
        exit(1);
    }

    $application = application( $applications[0], $applications_count);
    $pagination = getPagination($applications[0]['time'], 0, $applications_count, 'app'.$type);
    array_unshift($pagination , [
        [
            'text' => '💬',
            'callback_data' => 'app_ans='.$applications[0]['chat_id']
        ],
        [
            'text' => '🗑',
            'callback_data' => 'app'.$type.'_del='.$applications[0]['time']
        ]
    ]);
    if ($type == 'text') {
        $tg->send_chatAction('typing')->set_inlineKeyboard($pagination)->send_message( $application );
    }
    if ($type == 'photo') {
        $tg->send_chatAction('upload_photo')->set_inlineKeyboard($pagination)->send_photo($applications[0]['photo']['file_id'], $application );
    }
    if ($type == 'video') {
        $tg->send_chatAction('upload_video')->set_inlineKeyboard($pagination)->send_video($applications[0]['video'], $application );
    }
};

$checkChatmember = function( $chat_id, $user_id ){
    global $tg, $config;
    $chatmem = $tg->get_chatMember($chat_id, $user_id);
    if(!in_array($chatmem['result']['status'], ['creator', 'administrator', 'member'])){
        $tg->send_chatAction('typing')->send_message( "🚫 Kechirasiz xabarni forward qilish uchun bot ushbu chatga a'zo emas!");
        exit(1);
    }
};

$setStats = function( $updates ){
    global $tg, $config;
    if (!empty( $updates['message']['chat']['first_name'] )){
        setUserConfig( $tg->get_chatId(), 'first_name', $updates['message']['chat']['first_name'] );
    }else{
        setUserConfig( $tg->get_chatId(), 'first_name', '');
    }
    if (!empty( $updates['message']['chat']['last_name'] )){
        setUserConfig( $tg->get_chatId(), 'last_name', $updates['message']['chat']['last_name'] );
    }else{
        setUserConfig( $tg->get_chatId(), 'last_name', '');
    }
    if (!empty( $updates['message']['chat']['username'] )){
        setUserConfig( $tg->get_chatId(), 'username', $updates['message']['chat']['username'] );
    }else{
        setUserConfig( $tg->get_chatId(), 'username', '');
    }
    setUserConfig( $tg->get_chatId(), 'lastaction', time() );
};

if (! empty( $updates ) ) {
    if (!empty($updates['message']['chat']['id'])) {
        $tg->set_chatId( $updates['message']['chat']['id'] );
        $setStats( $updates );
    }
    if( ! empty( $updates['message']['text'] ) ){
        $text = $updates['message']['text'];

        if ( $text == '/start' ) {
            $startMessage();
        }else if ($text == '🔙 Orqaga' ) {
            $startMessage(true);
        }else if ( $text == '/haqida' || $text == '🤖 Bot haqida') {
            $tg->send_chatAction('typing')->send_message( "Ushbu bot foydalanuvchilar uchun turli ma'lumotlarni yetkazish va ular bilan bog'lanishlar uchun ishlab chiqilgan.\n\n👨‍💻 Dasturchi: <a href=\"https://t.me/yetimdasturchi\">Manuchehr Usmonov</a>\n🌐 Veb-sayt: https://manu.uno/");
        }else if ($text == '/aloqa' || $text == '☎️ Aloqa') {
            setUserConfig($tg->get_chatId(), 'lastmessage', '/contact');
            $tg->send_chatAction('typing')->set_replyKeyboard([
                ['🔙 Orqaga']
            ])->send_message( "Takliflar, shikoyarlar va boshqa turdagi murojaatlaringizni bot ma’muriyatiga yozib qoldirishinggiz mumkin 😊" );
        }else if (getUserConfig($tg->get_chatId(), 'lastmessage') == '/contact' ) {
            if(in_array($tg->get_chatId(), $config['owners'])){
                $tg->send_chatAction('typing')->send_message( 'Kechirasiz, faqatgina foydalanuvchilar murojaat yo‘llashlari mumkin 🤨' );
                $startMessage(true);
                exit(1);
            }
            addRequest('text', [
                'chat_id' => $tg->get_chatId(),
                'time' => time(),
                'text' => $text
            ]);
            $tg->send_chatAction('typing')->send_message( '✅ Murojaat muvaffaqiyatli yuborildi' );
            $startMessage(true);
        }else if (in_array( $tg->get_chatId(), $config['owners'] ) && $text == '⬅️ Orqaga' ) {
            $startMessage();
        }else if (in_array( $tg->get_chatId(), $config['owners'] ) && $text == '🟢 Holat' ) {
            $startusMessages();
        }else if (in_array( $tg->get_chatId(), $config['owners'] ) && $text == '✍️ Yozma murojaat') {
            $applicationMessage('text');
        }else if (in_array( $tg->get_chatId(), $config['owners'] ) && $text == '📸 Foto murojaat') {
            $applicationMessage('photo');
        }else if (in_array( $tg->get_chatId(), $config['owners'] ) && $text == '📽 Video murojaat') {
            $applicationMessage('video');
        }else if (in_array( $tg->get_chatId(), $config['owners'] ) && $text == '👨‍👩‍👧 Foydalanuvchilar' ) {
            $users = get_users(FALSE);
            $users_count = count($users);
            if ( $users_count == 0 ) {
                $tg->send_chatAction('typing')->send_message( '⚠️ Foydalanuvchilar mavjud emas' );
                exit(1);
            }
            $user = user( $users[0], $users_count);
            $pagination = getPagination($users[0]['id'], 0, $users_count, 'users');
            $tg->send_chatAction('typing')->set_inlineKeyboard( $pagination )->send_message( $user );
        }else if (in_array( $tg->get_chatId(), $config['owners'] ) && $text == '✍️ Bildirishnoma' ) {
            setUserConfig($tg->get_chatId(), 'lastmessage', 'send_notification');
            $tg->send_chatAction('typing')->set_replyKeyboard([
                ['⬅️ Orqaga'],
            ])->send_message("📢 Foydalanuvchilarga bildirishnoma yuborish uchun quyida xabarni kiriting...");
        }else if (in_array( $tg->get_chatId(), $config['owners'] ) && getUserConfig($tg->get_chatId(), 'lastmessage') == 'send_notification' ) {
            if ( strlen( $text ) > 10) {
                add_notifications([
                    'text' => $text
                ]);
                $tg->send_chatAction('typing')->send_message( "✅ Foydalanuvchilarga bildirishnoma yuborish jarayoni boshlandi" );
                $startMessage();
            }else{
                $tg->send_chatAction('typing')->send_message( "<em>🛑 Kechirasiz, bildirishnoma matni 10 dona belgidan kam bo'lmasligi lozim.</em>" );   
            }
        }else if (in_array( $tg->get_chatId(), $config['owners'] ) && $text == '👉 Forward' ) {
            setUserConfig($tg->get_chatId(), 'lastmessage', 'forward_notification');
            $tg->send_chatAction('typing')->set_replyKeyboard([
                ['⬅️ Orqaga'],
            ])->send_message("👉 Biror bir xabarni forward qilish uchun xabarni shuyerga uzating...");
        }else if (in_array( $tg->get_chatId(), $config['owners'] ) && getUserConfig($tg->get_chatId(), 'lastmessage') == 'forward_notification' ) {
            if (!empty($updates['message']['forward_from_chat']) && !empty($updates['message']['forward_from_message_id'])) {
                $checkChatmember($updates['message']['forward_from_chat']['id'], $config['bot_id']);
                add_notifications([
                    'from_chat_id' => $updates['message']['forward_from_chat']['id'],
                    'message_id' => $updates['message']['forward_from_message_id'],
                ]);
                $tg->send_chatAction('typing')->send_message( "✅ Foydalanuvchilarga bildirishnoma yuborish jarayoni boshlandi" );
                $startMessage();
            }else{
                $tg->send_chatAction('typing')->send_message("Xabar forward uchun ishlamaydi 🤷‍♂️");
            }
        }else if (in_array( $tg->get_chatId(), $config['owners'] ) && getUserConfig($tg->get_chatId(), 'lastmessage') == 'clear_notification' ) {
            if ($text == '👍 Ha') {
                clear_notification();
                $tg->send_chatAction('typing')->send_message( "✅ Jarayondagi bildirishnomalar muvaffaqiyatli tozalandi." );
                $startMessage();
            }else{
                $startMessage();
            }
        }else if(in_array( $tg->get_chatId(), $config['owners'] ) && getUserConfig( $tg->get_chatId(), 'lastmessage') == 'app_ans'){
            $chat_id = getUserConfig( $tg->get_chatId(), 'app_ans');
            $tg->send_chatAction('typing', $chat_id)->send_message( "💬 Murojaatnomaga javob xati: \n\n" . $text, $chat_id )->send_message( '✅ Javob yo‘llandi' );
            $startMessage();
        }else{
            $tg->send_chatAction('typing')->send_message("⚠️ Kechirasiz, menga faqat bildirishnomalarni yetkazish vazifasi yuklatilgan!");
        }
    }

    if( ! empty( $updates['message']['photo'] ) ){
        if (in_array( $tg->get_chatId(), $config['owners'] ) && getUserConfig($tg->get_chatId(), 'lastmessage') == 'send_notification' ) {
            $photo = end($updates['message']['photo']);
            $caption = (!empty($updates['message']['caption'])) ? $updates['message']['caption'] : '';
            add_notifications([
                'photo' => $photo['file_id'],
                'caption' => $caption
            ]);
            $tg->send_chatAction('typing')->send_message( "✅ Foydalanuvchilarga bildirishnoma yuborish jarayoni boshlandi" );
            $startMessage();
        }

        if (in_array( $tg->get_chatId(), $config['owners'] ) && getUserConfig($tg->get_chatId(), 'lastmessage') == 'forward_notification' ) {
            if (!empty($updates['message']['forward_from_chat']) && !empty($updates['message']['forward_from_message_id'])) {
                $checkChatmember($updates['message']['forward_from_chat']['id'], $config['bot_id']);
                add_notifications([
                    'from_chat_id' => $updates['message']['forward_from_chat']['id'],
                    'message_id' => $updates['message']['forward_from_message_id'],
                ]);
                $tg->send_chatAction('typing')->send_message( "✅ Foydalanuvchilarga bildirishnoma yuborish jarayoni boshlandi" );
                $startMessage();
            }else{
                $tg->send_chatAction('typing')->send_message("Xabar forward uchun ishlamaydi 🤷‍♂️");
            }
        }

        if (in_array( $tg->get_chatId(), $config['owners'] ) && getUserConfig($tg->get_chatId(), 'lastmessage') == 'app_ans' ) {
            $chat_id = getUserConfig( $tg->get_chatId(), 'app_ans');
            $photo = end($updates['message']['photo']);
            $caption = (!empty($updates['message']['caption'])) ? "💬 Murojaatnomaga javob xati: \n\n".$updates['message']['caption'] : '💬 Murojaatnomaga javob xati';
            $tg->send_chatAction('typing', $chat_id)->send_photo($photo['file_id'], $caption, $chat_id )->send_message( '✅ Javob yo‘llandi' );
            $startMessage();
        }

        if (getUserConfig($tg->get_chatId(), 'lastmessage') == '/contact' ) {
            if(in_array($tg->get_chatId(), $config['owners'])){
                $tg->send_chatAction('typing')->send_message( 'Kechirasiz, faqatgina foydalanuvchilar murojaat yo‘llashlari mumkin 🤨' );
                $startMessage(true);
                exit(1);
            }
            $photo = end($updates['message']['photo']);
            $caption = (!empty($updates['message']['caption'])) ? $updates['message']['caption'] : '';
            addRequest('photo', [
                'chat_id' => $tg->get_chatId(),
                'time' => time(),
                'photo' => $photo,
                'caption' => $caption
            ]);
            $tg->send_chatAction('typing')->send_message( '✅ Murojaat muvaffaqiyatli yuborildi' );
            $startMessage(true);
        }
    }

    if( ! empty( $updates['message']['video'] ) ){
        if (in_array( $tg->get_chatId(), $config['owners'] ) && getUserConfig($tg->get_chatId(), 'lastmessage') == 'send_notification' ) {
            $video = $updates['message']['video']['file_id'];
            $caption = (!empty($updates['message']['caption'])) ? $updates['message']['caption'] : '';
            add_notifications([
                'video' => $video,
                'caption' => $caption
            ]);
            $tg->send_chatAction('typing')->send_message( "✅ Foydalanuvchilarga bildirishnoma yuborish jarayoni boshlandi" );
            $startMessage();
        }

        if (in_array( $tg->get_chatId(), $config['owners'] ) && getUserConfig($tg->get_chatId(), 'lastmessage') == 'forward_notification' ) {
            if (!empty($updates['message']['forward_from_chat']) && !empty($updates['message']['forward_from_message_id'])) {
                $checkChatmember($updates['message']['forward_from_chat']['id'], $config['bot_id']);
                add_notifications([
                    'from_chat_id' => $updates['message']['forward_from_chat']['id'],
                    'message_id' => $updates['message']['forward_from_message_id'],
                ]);
                $tg->send_chatAction('typing')->send_message( "✅ Foydalanuvchilarga bildirishnoma yuborish jarayoni boshlandi" );
                $startMessage();
            }else{
                $tg->send_chatAction('typing')->send_message("Xabar forward uchun ishlamaydi 🤷‍♂️");
            }
        }

        if (in_array( $tg->get_chatId(), $config['owners'] ) && getUserConfig($tg->get_chatId(), 'lastmessage') == 'app_ans' ) {
            $chat_id = getUserConfig( $tg->get_chatId(), 'app_ans');
            $video = $updates['message']['video']['file_id'];
            $caption = (!empty($updates['message']['caption'])) ? "💬 Murojaatnomaga javob xati: \n\n".$updates['message']['caption'] : '💬 Murojaatnomaga javob xati';
            $tg->send_chatAction('typing', $chat_id)->send_video($video, $caption, $chat_id )->send_message( '✅ Javob yo‘llandi' );
            $startMessage();
        }

        if (getUserConfig($tg->get_chatId(), 'lastmessage') == '/contact' ) {
            if(in_array($tg->get_chatId(), $config['owners'])){
                $tg->send_chatAction('typing')->send_message( 'Kechirasiz, faqatgina foydalanuvchilar murojaat yo‘llashlari mumkin 🤨' );
                $startMessage(true);
                exit(1);
            }
            $video = $updates['message']['video']['file_id'];
            $caption = (!empty($updates['message']['caption'])) ? $updates['message']['caption'] : '';
            addRequest('video', [
                'chat_id' => $tg->get_chatId(),
                'time' => time(),
                'video' => $video,
                'caption' => $caption
            ]);
            $tg->send_chatAction('typing')->send_message( '✅ Murojaat muvaffaqiyatli yuborildi' );
            $startMessage(true);
        }
    }
    if( ! empty( $updates['callback_query']['data'] ) ){
        $tg->set_chatId($updates['callback_query']['message']['chat']['id']);
        parse_str($updates['callback_query']['data'], $query);
        if (count($query) > 0) {
            if ( ! empty( $query['status'] ) ) {
                if (in_array($query['status'], ['on', 'off'])) {
                    message_status($query['status']);
                    $count_notifications = message_status('count');
                    $status = (message_status() == 'on') ? '🟢' : '🔴';
                    $req = $tg->request('editMessageText', [
                        'chat_id' => $updates['callback_query']['message']['chat']['id'],
                        'message_id' => $updates['callback_query']['message']['message_id'],
                        'reply_markup' => $updates['callback_query']['message']['reply_markup'],
                        'text' => "Bildirishnoma yuborish holati: {$status}\n\n⏳ Jarayondagi xabarlar: {$count_notifications}",
                        'parse_mode' => 'html',
                        'disable_web_page_preview' => true
                    ]);
                    $tg->request('answerCallbackQuery', ['callback_query_id' => $updates['callback_query']['id'], 'text' => "Holat o'zgartirildi"]);
                }elseif ($query['status'] == 'check') {
                    $count_notifications = message_status('count');
                    $status = (message_status() == 'on') ? '🟢' : '🔴';
                    $req = $tg->request('editMessageText', [
                        'chat_id' => $updates['callback_query']['message']['chat']['id'],
                        'message_id' => $updates['callback_query']['message']['message_id'],
                        'reply_markup' => $updates['callback_query']['message']['reply_markup'],
                        'text' => "Bildirishnoma yuborish holati: {$status}\n\n⏳ Jarayondagi xabarlar: {$count_notifications}",
                        'parse_mode' => 'html',
                        'disable_web_page_preview' => true
                    ]);
                    $tg->request('answerCallbackQuery', ['callback_query_id' => $updates['callback_query']['id'], 'text' => "Natija yangilandi"]);
                }else{
                    $tg->request('answerCallbackQuery', ['callback_query_id' => $updates['callback_query']['id'], 'text' => "⚠️ Tizimda xatolik yuzberdi", 'show_alert' => true]);
                }
            }
            if ( ! empty( $query['clear'] ) ) {
                if ($query['clear'] == 'true') {
                    setUserConfig($tg->get_chatId(), 'lastmessage', 'clear_notification');
                    $tg->send_chatAction('typing')->set_replyKeyboard([
                        ['👍 Ha', '🙅‍♂️ Yo‘q'],
                        ['⬅️ Orqaga'],
                    ])->send_message("⚠️ Siz chindan ham jarayondagi bildirishnomalarni o'chirmoqchimisiz?");
                    $tg->request('answerCallbackQuery', ['callback_query_id' => $updates['callback_query']['id'], 'text' => "Variantlardan birini tanlang"]);
                }else{
                    $tg->request('answerCallbackQuery', ['callback_query_id' => $updates['callback_query']['id'], 'text' => "⚠️ Tizimda xatolik yuzberdi", 'show_alert' => true]);
                }
            }
            if ( ! empty( $query['users'] ) ) {
                $users = get_users(FALSE);
                $users_count = count($users);
                if ( $users_count == 0 ) {
                    $tg->request('answerCallbackQuery', ['callback_query_id' => $updates['callback_query']['id'], 'text' => "❌ Foydalanuvchilar mavjud emas"]);
                    exit(1);
                }
                $page = ( array_key_exists('prev', $query) ) ? intval($query['prev']) : intval($query['next']);
                $user = array_slice($users, $page, 1, true);
                if (count($user) > 0) {
                    $user = reset($user);
                    
                    $message = user( $user, $users_count);
                    $pagination = getPagination($user['id'], $page, $users_count, 'users');
                    $req = $tg->request('editMessageText', [
                        'chat_id' => $updates['callback_query']['message']['chat']['id'],
                        'message_id' => $updates['callback_query']['message']['message_id'],
                        'reply_markup' => [
                            'inline_keyboard' => $pagination
                        ],
                        'text' => $message,
                        'parse_mode' => 'html',
                        'disable_web_page_preview' => true
                    ]);
                    $tg->request('answerCallbackQuery', ['callback_query_id' => $updates['callback_query']['id'], 'text' => "Natija yangilandi"]);
                }else{
                    $tg->request('answerCallbackQuery', ['callback_query_id' => $updates['callback_query']['id'], 'text' => "Natijalar topilmadi"]);
                }
            }
            if ( ! empty($query['apptext_del'])) {
                get_applications('text', $query['apptext_del']);
                $tg->request('answerCallbackQuery', ['callback_query_id' => $updates['callback_query']['id'], 'text' => '✅ Murojaat muvaffaqiyatli o‘chirildi', 'show_alert' => true]);
                $tg->request('deleteMessage', ['chat_id' => $updates['callback_query']['message']['chat']['id'], 'message_id' => $updates['callback_query']['message']['message_id']]);
            }
            if ( ! empty($query['appvideo_del'])) {
                get_applications('text', $query['appvideo_del']);
                $tg->request('answerCallbackQuery', ['callback_query_id' => $updates['callback_query']['id'], 'text' => '✅ Murojaat muvaffaqiyatli o‘chirildi', 'show_alert' => true]);
                $tg->request('deleteMessage', ['chat_id' => $updates['callback_query']['message']['chat']['id'], 'message_id' => $updates['callback_query']['message']['message_id']]);
            }
            if ( ! empty($query['appphoto_del'])) {
                get_applications('text', $query['appphoto_del']);
                $tg->request('answerCallbackQuery', ['callback_query_id' => $updates['callback_query']['id'], 'text' => '✅ Murojaat muvaffaqiyatli o‘chirildi', 'show_alert' => true]);
                $tg->request('deleteMessage', ['chat_id' => $updates['callback_query']['message']['chat']['id'], 'message_id' => $updates['callback_query']['message']['message_id']]);
            }
            if ( ! empty( $query['apptext'] ) ) {
                $applications = get_applications('text');
                $applications_count = count($applications);
                if ( $applications_count == 0 ) {
                    $tg->request('answerCallbackQuery', ['callback_query_id' => $updates['callback_query']['id'], 'text' => '❌ Murojaatlar mavjud emas']);
                    exit(1);
                }
                $page = ( array_key_exists('prev', $query) ) ? intval($query['prev']) : intval($query['next']);
                $application = array_slice($applications, $page, 1, true);
                if (count($application) > 0) {
                    $application = reset($application);
                    
                    $message = application( $application, $applications_count);
                    $pagination = getPagination($application['chat_id'], $page, $applications_count, 'apptext');
                    array_unshift($pagination , [
                        [
                            'text' => '💬',
                            'callback_data' => 'app_ans='.$application['chat_id']
                        ],
                        [
                            'text' => '🗑',
                            'callback_data' => 'apptext_del='.$application['time']
                        ]
                    ]);
                    $req = $tg->request('editMessageText', [
                        'chat_id' => $updates['callback_query']['message']['chat']['id'],
                        'message_id' => $updates['callback_query']['message']['message_id'],
                        'reply_markup' => [
                            'inline_keyboard' => $pagination
                        ],
                        'text' => $message,
                        'parse_mode' => 'html',
                        'disable_web_page_preview' => true
                    ]);
                    $tg->request('answerCallbackQuery', ['callback_query_id' => $updates['callback_query']['id'], 'text' => 'Natija yangilandi']);
                }else{
                    $tg->request('answerCallbackQuery', ['callback_query_id' => $updates['callback_query']['id'], 'text' => 'Natijalar topilmadi']);
                }
            }
            if ( ! empty( $query['appphoto'] ) ) {
                $applications = get_applications('photo');
                $applications_count = count($applications);
                if ( $applications_count == 0 ) {
                    $tg->request('answerCallbackQuery', ['callback_query_id' => $updates['callback_query']['id'], 'text' => '❌ Murojaatlar mavjud emas']);
                    exit(1);
                }
                $page = ( array_key_exists('prev', $query) ) ? intval($query['prev']) : intval($query['next']);
                $application = array_slice($applications, $page, 1, true);
                if (count($application) > 0) {
                    $application = reset($application);
                    
                    $message = application( $application, $applications_count);
                    $pagination = getPagination($application['chat_id'], $page, $applications_count, 'appphoto');
                    array_unshift($pagination , [
                        [
                            'text' => '💬',
                            'callback_data' => 'app_ans='.$application['chat_id']
                        ],
                        [
                            'text' => '🗑',
                            'callback_data' => 'appphoto_del='.$application['time']
                        ]
                    ]);
                    $req = $tg->request('editMessageMedia', [
                        'chat_id' => $updates['callback_query']['message']['chat']['id'],
                        'message_id' => $updates['callback_query']['message']['message_id'],
                        'reply_markup' => [
                            'inline_keyboard' => $pagination
                        ],
                        'media' => [
                            'type' => 'photo',
                            'parse_mode' => 'html',
                            'caption' => $message,
                            'media' => $application['photo']['file_id']
                        ]
                    ]);
                    $tg->request('answerCallbackQuery', ['callback_query_id' => $updates['callback_query']['id'], 'text' => 'Natija yangilandi']);
                }else{
                    $tg->request('answerCallbackQuery', ['callback_query_id' => $updates['callback_query']['id'], 'text' => 'Natijalar topilmadi']);
                }
            }
            if ( ! empty( $query['appvideo'] ) ) {
                $applications = get_applications('video');
                $applications_count = count($applications);
                if ( $applications_count == 0 ) {
                    $tg->request('answerCallbackQuery', ['callback_query_id' => $updates['callback_query']['id'], 'text' => '❌ Murojaatlar mavjud emas']);
                    exit(1);
                }
                $page = ( array_key_exists('prev', $query) ) ? intval($query['prev']) : intval($query['next']);
                $application = array_slice($applications, $page, 1, true);
                if (count($application) > 0) {
                    $application = reset($application);
                    
                    $message = application( $application, $applications_count);
                    $pagination = getPagination($application['chat_id'], $page, $applications_count, 'appvideo');
                    array_unshift($pagination , [
                        [
                            'text' => '💬',
                            'callback_data' => 'app_ans='.$application['chat_id']
                        ],
                        [
                            'text' => '🗑',
                            'callback_data' => 'appvideo_del='.$application['time']
                        ]
                    ]);
                    $req = $tg->request('editMessageMedia', [
                        'chat_id' => $updates['callback_query']['message']['chat']['id'],
                        'message_id' => $updates['callback_query']['message']['message_id'],
                        'reply_markup' => [
                            'inline_keyboard' => $pagination
                        ],
                        'media' => [
                            'type' => 'video',
                            'parse_mode' => 'html',
                            'caption' => $message,
                            'media' => $application['video']
                        ]
                    ]);
                    $tg->request('answerCallbackQuery', ['callback_query_id' => $updates['callback_query']['id'], 'text' => 'Natija yangilandi']);
                }else{
                    $tg->request('answerCallbackQuery', ['callback_query_id' => $updates['callback_query']['id'], 'text' => 'Natijalar topilmadi']);
                }
            }
            if ( ! empty( $query['app_ans'] ) ) {
                setUserConfig($updates['callback_query']['message']['chat']['id'], 'lastmessage', 'app_ans');
                setUserConfig($updates['callback_query']['message']['chat']['id'], 'app_ans', $query['app_ans']);
                $tg->set_chatId($updates['callback_query']['message']['chat']['id'])->send_chatAction('typing')->set_replyKeyboard([
                    ['⬅️ Orqaga']
                ])->send_message( '💬 Javob matnini kiriting...' );
                $tg->request('answerCallbackQuery', ['callback_query_id' => $updates['callback_query']['id'], 'text' => 'Murojaatga javob yo‘llash 👇']);
            }
        }
    }
}
