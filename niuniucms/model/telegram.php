<?php
!defined('DEBUG') and exit('Access Denied.');

function telegram_table_ready()
{
    static $ok = null;
    if ($ok !== null) {
        return $ok;
    }
    global $db;
    $pre = $db->tablepre;
    $like = addslashes($pre . 'tg_chat');
    $row = db_sql_find_one("SHOW TABLES LIKE '{$like}'", $db);
    if (empty($row)) {
        $ok = false;
        return false;
    }
    $like2 = addslashes($pre . 'tg_push_log');
    $row2 = db_sql_find_one("SHOW TABLES LIKE '{$like2}'", $db);
    $ok = !empty($row2);
    if ($ok) {
        telegram_ensure_bot_columns();
    }
    return $ok;
}

function telegram_norm_bot_id($bot_id = '')
{
    if (function_exists('safew_norm_bot_id')) {
        return safew_norm_bot_id($bot_id);
    }
    $bot_id = trim((string) $bot_id);
    if ($bot_id === '') {
        $bot_id = 'default';
    }
    if (!preg_match('/^[a-zA-Z0-9_\\-]{1,40}$/', $bot_id)) {
        $bot_id = substr(md5($bot_id), 0, 16);
    }
    return strtolower($bot_id);
}

function telegram_ensure_bot_columns()
{
    global $db;
    $pre = $db->tablepre;
    $chat = "`{$pre}tg_chat`";
    $log = "`{$pre}tg_push_log`";

    $c1 = db_sql_find_one("SHOW COLUMNS FROM {$chat} LIKE 'bot_id'");
    if (empty($c1)) {
        db_exec("ALTER TABLE {$chat} ADD COLUMN `bot_id` varchar(40) NOT NULL DEFAULT 'default' AFTER `id`");
        db_exec("UPDATE {$chat} SET bot_id='default' WHERE bot_id=''");
    }
    $c2 = db_sql_find_one("SHOW COLUMNS FROM {$log} LIKE 'bot_id'");
    if (empty($c2)) {
        db_exec("ALTER TABLE {$log} ADD COLUMN `bot_id` varchar(40) NOT NULL DEFAULT 'default' AFTER `id`");
        db_exec("UPDATE {$log} SET bot_id='default' WHERE bot_id=''");
    }
    $idx_chat_old = db_sql_find_one("SHOW INDEX FROM {$chat} WHERE Key_name='chat_id'");
    if (!empty($idx_chat_old)) {
        db_exec("ALTER TABLE {$chat} DROP INDEX `chat_id`");
    }
    $idx_chat_new = db_sql_find_one("SHOW INDEX FROM {$chat} WHERE Key_name='bot_chat'");
    if (empty($idx_chat_new)) {
        db_exec("ALTER TABLE {$chat} ADD UNIQUE KEY `bot_chat` (`bot_id`,`chat_id`)");
    }
    $idx_log_old = db_sql_find_one("SHOW INDEX FROM {$log} WHERE Key_name='chat_msg'");
    if (!empty($idx_log_old)) {
        db_exec("ALTER TABLE {$log} DROP INDEX `chat_msg`");
    }
    $idx_log_new = db_sql_find_one("SHOW INDEX FROM {$log} WHERE Key_name='bot_chat_msg'");
    if (empty($idx_log_new)) {
        db_exec("ALTER TABLE {$log} ADD UNIQUE KEY `bot_chat_msg` (`bot_id`,`chat_id`,`msg_key`)");
    }
}

function telegram_install_tables()
{
    global $db;
    $pre = $db->tablepre;
    $sql1 = "CREATE TABLE IF NOT EXISTS `{$pre}tg_chat` ("
        . "`id` int(11) unsigned NOT NULL AUTO_INCREMENT,"
        . "`bot_id` varchar(40) NOT NULL DEFAULT 'default',"
        . "`chat_id` varchar(32) NOT NULL DEFAULT '',"
        . "`chat_type` varchar(20) NOT NULL DEFAULT '',"
        . "`title` varchar(255) NOT NULL DEFAULT '',"
        . "`username` varchar(120) NOT NULL DEFAULT '',"
        . "`enabled` tinyint(1) unsigned NOT NULL DEFAULT '1',"
        . "`last_seen_date` int(11) unsigned NOT NULL DEFAULT '0',"
        . "`create_date` int(11) unsigned NOT NULL DEFAULT '0',"
        . "`update_date` int(11) unsigned NOT NULL DEFAULT '0',"
        . "PRIMARY KEY (`id`),"
        . "UNIQUE KEY `bot_chat` (`bot_id`,`chat_id`),"
        . "KEY `enabled_update` (`enabled`,`update_date`)"
        . ") ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COMMENT='Telegram频道/群绑定'";
    $sql2 = "CREATE TABLE IF NOT EXISTS `{$pre}tg_push_log` ("
        . "`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,"
        . "`bot_id` varchar(40) NOT NULL DEFAULT 'default',"
        . "`chat_id` varchar(32) NOT NULL DEFAULT '',"
        . "`vid` int(11) unsigned NOT NULL DEFAULT '0',"
        . "`msg_key` varchar(80) NOT NULL DEFAULT '',"
        . "`status` tinyint(1) unsigned NOT NULL DEFAULT '0',"
        . "`error_msg` varchar(500) NOT NULL DEFAULT '',"
        . "`create_date` int(11) unsigned NOT NULL DEFAULT '0',"
        . "PRIMARY KEY (`id`),"
        . "UNIQUE KEY `bot_chat_msg` (`bot_id`,`chat_id`,`msg_key`),"
        . "KEY `vid_date` (`vid`,`create_date`)"
        . ") ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COMMENT='Telegram发送日志'";
    db_exec($sql1);
    db_exec($sql2);
    telegram_ensure_bot_columns();
    return telegram_table_ready();
}

function telegram_chat_upsert($chat, $now = 0, $bot_id = 'default')
{
    global $time;
    if (!is_array($chat)) {
        return false;
    }
    if (empty($now)) {
        $now = (int) $time;
    }
    $chat_id = trim((string) array_value($chat, 'id', ''));
    if ($chat_id === '') {
        return false;
    }
    $bot_id = telegram_norm_bot_id($bot_id);
    $chat_type = trim((string) array_value($chat, 'type', ''));
    $title = trim((string) array_value($chat, 'title', ''));
    if ($title === '') {
        $first = trim((string) array_value($chat, 'first_name', ''));
        $last = trim((string) array_value($chat, 'last_name', ''));
        $title = trim($first . ' ' . $last);
    }
    $username = trim((string) array_value($chat, 'username', ''));
    $safe_bot_id = addslashes($bot_id);
    $safe_chat_id = addslashes($chat_id);
    $old = db_sql_find_one("SELECT id FROM `{$GLOBALS['db']->tablepre}tg_chat` WHERE bot_id='{$safe_bot_id}' AND chat_id='{$safe_chat_id}' LIMIT 1");
    $data = array(
        'chat_type' => $chat_type,
        'title' => $title,
        'username' => $username,
        'enabled' => 1,
        'last_seen_date' => $now,
        'update_date' => $now,
    );
    if ($old && !empty($old['id'])) {
        return db_update('tg_chat', array('id' => intval($old['id'])), $data);
    }
    $data['bot_id'] = $bot_id;
    $data['chat_id'] = $chat_id;
    $data['create_date'] = $now;
    return db_insert('tg_chat', $data);
}

function telegram_chat_list_enabled($bot_id = 'default')
{
    global $db;
    $pre = $db->tablepre;
    $bot_id = addslashes(telegram_norm_bot_id($bot_id));
    $sql = "SELECT * FROM `{$pre}tg_chat` WHERE bot_id='{$bot_id}' AND enabled=1 ORDER BY id ASC";
    $rows = db_sql_find($sql);
    return is_array($rows) ? $rows : array();
}

function telegram_api_call($token, $method, $payload = array(), &$raw = '')
{
    $token = trim((string) $token);
    $method = trim((string) $method);
    if ($token === '' || $method === '') {
        return array('ok' => false, 'description' => 'token or method empty');
    }
    $url = "https://api.telegram.org/bot{$token}/{$method}";
    $raw = https_request($url, $payload, '', 25);
    if ($raw === false || $raw === '') {
        return array('ok' => false, 'description' => 'empty response');
    }
    $arr = xn_json_decode($raw);
    if (!is_array($arr)) {
        $arr = json_decode($raw, true);
    }
    if (!is_array($arr)) {
        return array('ok' => false, 'description' => 'invalid json', 'raw' => $raw);
    }
    return $arr;
}

function telegram_upload_photo_file($token, $chat_id, $caption, $photo_url, &$resp = array(), $reply_markup = '', $parse_mode = '')
{
    $token = trim((string) $token);
    $chat_id = trim((string) $chat_id);
    $photo_url = trim((string) $photo_url);
    if ($token === '' || $chat_id === '' || $photo_url === '') {
        $resp = array('ok' => false, 'description' => 'invalid args');
        return false;
    }
    $bin = save_image($photo_url);
    if ($bin === false || $bin === '') {
        $resp = array('ok' => false, 'description' => 'download image failed');
        return false;
    }
    $tmp = tempnam(sys_get_temp_dir(), 'tgp_');
    if ($tmp === false) {
        $resp = array('ok' => false, 'description' => 'temp file failed');
        return false;
    }
    file_put_contents($tmp, $bin);
    $mime = 'application/octet-stream';
    if (function_exists('finfo_open')) {
        $fi = @finfo_open(FILEINFO_MIME_TYPE);
        if ($fi) {
            $m = @finfo_file($fi, $tmp);
            @finfo_close($fi);
            if (!empty($m)) {
                $mime = $m;
            }
        }
    }
    $ch = curl_init();
    $url = "https://api.telegram.org/bot{$token}/sendPhoto";
    $post = array(
        'chat_id' => $chat_id,
        'caption' => (string) $caption,
        'photo' => curl_file_create($tmp, $mime, basename($photo_url)),
    );
    $reply_markup = trim((string) $reply_markup);
    if ($reply_markup !== '') {
        $post['reply_markup'] = $reply_markup;
    }
    $parse_mode = trim((string) $parse_mode);
    if ($parse_mode !== '') {
        $post['parse_mode'] = $parse_mode;
    }
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $out = curl_exec($ch);
    curl_close($ch);
    @unlink($tmp);
    $arr = xn_json_decode((string) $out);
    if (!is_array($arr)) {
        $arr = json_decode((string) $out, true);
    }
    if (!is_array($arr)) {
        $resp = array('ok' => false, 'description' => 'invalid json', 'raw' => $out);
        return false;
    }
    $resp = $arr;
    return array_value($arr, 'ok', false) ? true : false;
}

function telegram_send_photo_best_effort($token, $chat_id, $photo, $caption, &$resp = array(), $reply_markup = '', $parse_mode = '')
{
    $payload = array(
        'chat_id' => (string) $chat_id,
        'photo' => (string) $photo,
        'caption' => (string) $caption,
    );
    $reply_markup = trim((string) $reply_markup);
    if ($reply_markup !== '') {
        $payload['reply_markup'] = $reply_markup;
    }
    $parse_mode = trim((string) $parse_mode);
    if ($parse_mode !== '') {
        $payload['parse_mode'] = $parse_mode;
    }
    $resp = telegram_api_call($token, 'sendPhoto', $payload, $raw);
    if (array_value($resp, 'ok', false)) {
        return true;
    }
    if (!function_exists('safew_is_absolute_url') || !safew_is_absolute_url($photo)) {
        return false;
    }
    $desc = strtolower((string) array_value($resp, 'description', ''));
    $need_upload_retry = (strpos($desc, 'invalid file_id') !== false)
        || (strpos($desc, 'wrong file identifier') !== false)
        || (strpos($desc, 'bad request') !== false);
    if (!$need_upload_retry) {
        return false;
    }
    $upload_resp = array();
    $ok = telegram_upload_photo_file($token, $chat_id, $caption, $photo, $upload_resp, $reply_markup, $parse_mode);
    if ($ok) {
        $resp = $upload_resp;
        return true;
    }
    if (!empty($upload_resp)) {
        $resp = $upload_resp;
    }
    return false;
}

function telegram_sync_updates($token, $limit = 100, $bot_id = 'default')
{
    global $time;
    if (!telegram_table_ready() && !telegram_install_tables()) {
        return array('ok' => false, 'message' => 'table not ready');
    }
    $bot_id = telegram_norm_bot_id($bot_id);
    $limit = max(1, min(100, intval($limit)));
    $offset = intval(kv_get('tg_last_update_id_' . $bot_id));
    $payload = array(
        'offset' => $offset > 0 ? $offset + 1 : 0,
        'limit' => $limit,
        'timeout' => 0,
    );
    $resp = telegram_api_call($token, 'getUpdates', $payload, $raw);
    if (!array_value($resp, 'ok', false)) {
        return array('ok' => false, 'message' => array_value($resp, 'description', 'getUpdates failed'), 'raw' => $raw);
    }
    $rows = array_value($resp, 'result', array());
    $saved = 0;
    $max_update_id = $offset;
    foreach ((array) $rows as $u) {
        $uid = intval(array_value($u, 'update_id', 0));
        if ($uid > $max_update_id) {
            $max_update_id = $uid;
        }
        $msg = array();
        if (!empty($u['channel_post'])) {
            $msg = $u['channel_post'];
        } elseif (!empty($u['message'])) {
            $msg = $u['message'];
        } elseif (!empty($u['edited_channel_post'])) {
            $msg = $u['edited_channel_post'];
        } elseif (!empty($u['edited_message'])) {
            $msg = $u['edited_message'];
        }
        $chat = array_value($msg, 'chat', array());
        if (!empty($chat) && telegram_chat_upsert($chat, (int) $time, $bot_id) !== false) {
            $saved++;
        }
    }
    if ($max_update_id > $offset) {
        kv_set('tg_last_update_id_' . $bot_id, strval($max_update_id));
    }
    return array(
        'ok' => true,
        'updates' => count((array) $rows),
        'saved_chats' => $saved,
        'last_update_id' => $max_update_id,
    );
}

function telegram_push_log_exists($chat_id, $msg_key, $bot_id = 'default')
{
    global $db;
    $pre = $db->tablepre;
    $bot_id = addslashes(telegram_norm_bot_id($bot_id));
    $cid = addslashes((string) $chat_id);
    $mk = addslashes((string) $msg_key);
    $row = db_sql_find_one("SELECT id FROM `{$pre}tg_push_log` WHERE bot_id='{$bot_id}' AND chat_id='{$cid}' AND msg_key='{$mk}' AND status=1 LIMIT 1");
    return !empty($row);
}

function telegram_push_log_add($chat_id, $vid, $msg_key, $status, $error = '', $bot_id = 'default')
{
    global $time;
    $bot_id = telegram_norm_bot_id($bot_id);
    $row = array(
        'bot_id' => $bot_id,
        'chat_id' => (string) $chat_id,
        'vid' => intval($vid),
        'msg_key' => (string) $msg_key,
        'status' => intval($status),
        'error_msg' => substr((string) $error, 0, 500),
        'create_date' => (int) $time,
    );
    $pre = $GLOBALS['db']->tablepre;
    $safe_bot_id = addslashes($bot_id);
    $cid = addslashes((string) $chat_id);
    $mk = addslashes((string) $msg_key);
    $old = db_sql_find_one("SELECT id FROM `{$pre}tg_push_log` WHERE bot_id='{$safe_bot_id}' AND chat_id='{$cid}' AND msg_key='{$mk}' LIMIT 1");
    if ($old && !empty($old['id'])) {
        return db_update('tg_push_log', array('id' => intval($old['id'])), array(
            'vid' => intval($vid),
            'status' => intval($status),
            'error_msg' => substr((string) $error, 0, 500),
            'create_date' => (int) $time,
        ));
    }
    return db_insert('tg_push_log', $row);
}

function telegram_push_new_vod($token, $base_url, $limit = 10, $bot_id = 'default')
{
    global $db, $time, $conf;
    if (!telegram_table_ready() && !telegram_install_tables()) {
        return array('ok' => false, 'message' => 'table not ready');
    }
    $bot_id = telegram_norm_bot_id($bot_id);
    $token = trim((string) $token);
    if ($token === '') {
        return array('ok' => false, 'message' => 'token empty');
    }
    $limit = max(1, min(50, intval($limit)));
    $chat_list = telegram_chat_list_enabled($bot_id);
    if (empty($chat_list)) {
        return array('ok' => true, 'message' => 'no enabled chat', 'sent' => 0);
    }
    $last_ts = intval(kv_get('tg_last_push_ts_' . $bot_id));
    if ($last_ts <= 0) {
        $last_ts = (int) $time - 3600;
    }
    $pre = $db->tablepre;
    $sql = "SELECT vid,cid,name,pic,remarks,views,actor,area,lang,year,blurb,content,create_date FROM `{$pre}vod` "
        . "WHERE create_date>{$last_ts} ORDER BY create_date ASC LIMIT {$limit}";
    $rows = db_sql_find($sql);
    if (!is_array($rows) || empty($rows)) {
        return array('ok' => true, 'message' => 'no new vod', 'sent' => 0);
    }
    $max_ts = $last_ts;
    $sent = 0;
    $failed = 0;
    $push_agent_code = strtoupper(trim((string) array_value($conf, 'tg_push_agent_code', '')));
    $tag_spoiler_on = intval(array_value($conf, 'tg_tag_spoiler_on', 0)) ? 1 : 0;
    $tag_pool_raw = (string) array_value($conf, 'tg_tag_pool', '');
    $reply_markup = function_exists('safew_build_reply_markup') ? safew_build_reply_markup((string) array_value($conf, 'tg_menu_buttons', ''), 8) : '';
    foreach ($rows as $v) {
        $vid = intval(array_value($v, 'vid', 0));
        $create_date = intval(array_value($v, 'create_date', 0));
        if ($create_date > $max_ts) {
            $max_ts = $create_date;
        }
        if ($vid <= 0) {
            continue;
        }
        $title = trim((string) array_value($v, 'name', ''));
        if ($title === '') {
            continue;
        }
        $link = function_exists('safew_build_vod_link') ? safew_build_vod_link($base_url, $vid, $conf, $push_agent_code) : '';
        $poster = function_exists('safew_build_abs_url') ? safew_build_abs_url($base_url, array_value($v, 'pic', '')) : '';
        $remarks = trim((string) array_value($v, 'remarks', ''));
        $views = intval(array_value($v, 'views', 0));
        $intro_raw = trim((string) array_value($v, 'blurb', ''));
        if ($intro_raw === '') {
            $intro_raw = trim((string) array_value($v, 'content', ''));
        }
        $intro = function_exists('safew_brief_text') ? safew_brief_text($intro_raw, 90) : strip_tags($intro_raw);
        if ($intro === '') {
            $actor = trim((string) array_value($v, 'actor', ''));
            $area = trim((string) array_value($v, 'area', ''));
            $year = trim((string) array_value($v, 'year', ''));
            $parts = array();
            if ($actor !== '') $parts[] = '主演：' . $actor;
            if ($area !== '') $parts[] = '地区：' . $area;
            if ($year !== '') $parts[] = '年份：' . $year;
            if ($remarks !== '') $parts[] = '状态：' . $remarks;
            $intro = !empty($parts)
                ? '《' . $title . '》' . implode('，', $parts) . '。点击观看页查看完整剧情介绍。'
                : '《' . $title . '》暂无详细简介，请点击观看页查看完整信息。';
            if (function_exists('safew_brief_text')) {
                $intro = safew_brief_text($intro, 90);
            }
        }
        $msg_key = 'vod:' . $vid . ':' . $create_date;
        $caption = "【视频更新】{$title}\n";
        if ($remarks !== '') {
            $caption .= "状态：{$remarks}\n";
        }
        $caption .= "热度：{$views}\n";
        if ($intro !== '') {
            $caption .= "简介：{$intro}\n";
        }
        if ($link !== '') {
            $caption .= "\n观看：{$link}";
        }
        // Rebuild tag line per message to keep random order.
        $tag_line = function_exists('safew_build_tag_line') ? safew_build_tag_line($tag_pool_raw, 12) : '';
        $tag_line_spoiler = ($tag_spoiler_on && function_exists('safew_build_tag_line_spoiler_html')) ? safew_build_tag_line_spoiler_html($tag_pool_raw, 12) : '';
        $parse_mode = '';
        if ($tag_spoiler_on && $tag_line_spoiler !== '') {
            $caption_html = '【视频更新】' . htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "\n";
            if ($remarks !== '') {
                $caption_html .= '状态：' . htmlspecialchars($remarks, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "\n";
            }
            $caption_html .= '热度：' . intval($views) . "\n";
            if ($intro !== '') {
                $caption_html .= '简介：' . htmlspecialchars($intro, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "\n";
            }
            if ($link !== '') {
                $href = htmlspecialchars($link, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $caption_html .= "\n观看：<a href=\"{$href}\">{$href}</a>";
            }
            $caption = $caption_html . "\n\n" . $tag_line_spoiler;
            $parse_mode = 'HTML';
        } elseif ($tag_line !== '') {
            $caption .= "\n\n{$tag_line}";
        }
        foreach ($chat_list as $chat) {
            $chat_id = (string) array_value($chat, 'chat_id', '');
            if ($chat_id === '') {
                continue;
            }
            if (telegram_push_log_exists($chat_id, $msg_key, $bot_id)) {
                continue;
            }
            if ($poster !== '') {
                $resp = array();
                $ok_photo = telegram_send_photo_best_effort($token, $chat_id, $poster, $caption, $resp, $reply_markup, $parse_mode);
                if (!$ok_photo) {
                    $payload2 = array(
                        'chat_id' => $chat_id,
                        'text' => $caption,
                    );
                    if ($reply_markup !== '') {
                        $payload2['reply_markup'] = $reply_markup;
                    }
                    if ($parse_mode !== '') {
                        $payload2['parse_mode'] = $parse_mode;
                    }
                    $resp2 = telegram_api_call($token, 'sendMessage', $payload2, $raw2);
                    if (array_value($resp2, 'ok', false)) {
                        $resp = $resp2;
                    }
                }
            } else {
                $payload = array(
                    'chat_id' => $chat_id,
                    'text' => $caption,
                );
                if ($reply_markup !== '') {
                    $payload['reply_markup'] = $reply_markup;
                }
                if ($parse_mode !== '') {
                    $payload['parse_mode'] = $parse_mode;
                }
                $resp = telegram_api_call($token, 'sendMessage', $payload, $raw);
            }
            if (array_value($resp, 'ok', false)) {
                telegram_push_log_add($chat_id, $vid, $msg_key, 1, '', $bot_id);
                $sent++;
            } else {
                telegram_push_log_add($chat_id, $vid, $msg_key, 0, array_value($resp, 'description', 'send failed'), $bot_id);
                $failed++;
            }
        }
    }
    if ($max_ts > $last_ts) {
        kv_set('tg_last_push_ts_' . $bot_id, strval($max_ts));
    }
    return array(
        'ok' => true,
        'sent' => $sent,
        'failed' => $failed,
        'last_push_ts' => $max_ts,
        'rows' => count($rows),
    );
}

