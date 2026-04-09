<?php
!defined('DEBUG') and exit('Access Denied.');

$cid = 0;
$action = param(1, 'list');

if (!function_exists('safew_install_tables')) {
    include _include(APP_PATH . 'model/safew.php');
}
if (!function_exists('telegram_install_tables')) {
    include _include(APP_PATH . 'model/telegram.php');
}

function admin_push_menu_normalize($names, $urls, $max = 8)
{
    $ret = array();
    $names = is_array($names) ? $names : array();
    $urls = is_array($urls) ? $urls : array();
    $n = max(count($names), count($urls));
    for ($i = 0; $i < $n; $i++) {
        $text = trim((string) array_value($names, $i, ''));
        $url = trim((string) array_value($urls, $i, ''));
        if ($text === '' || $url === '') {
            continue;
        }
        $text = strip_tags($text);
        $url = strip_tags($url);
        $ret[] = array('text' => $text, 'url' => $url);
        if (count($ret) >= $max) {
            break;
        }
    }
    return $ret;
}

function admin_push_menu_decode($raw)
{
    $raw = trim((string) $raw);
    if ($raw === '') {
        return array();
    }
    $arr = xn_json_decode($raw);
    if (!is_array($arr)) {
        $arr = json_decode($raw, true);
    }
    if (!is_array($arr)) {
        return array();
    }
    $ret = array();
    foreach ($arr as $it) {
        if (!is_array($it)) {
            continue;
        }
        $text = trim((string) array_value($it, 'text', ''));
        $url = trim((string) array_value($it, 'url', ''));
        if ($text === '' || $url === '') {
            continue;
        }
        $ret[] = array('text' => $text, 'url' => $url);
    }
    return $ret;
}

function admin_bot_id_from_token($token)
{
    $token = trim((string) $token);
    if ($token === '') {
        return '';
    }
    return substr(md5($token), 0, 16);
}

function admin_bot_pool_normalize($names, $tokens, $enableds, $max = 20)
{
    $ret = array();
    $names = is_array($names) ? $names : array();
    $tokens = is_array($tokens) ? $tokens : array();
    $enableds = is_array($enableds) ? $enableds : array();
    $n = max(count($names), count($tokens));
    for ($i = 0; $i < $n; $i++) {
        $name = trim((string) array_value($names, $i, ''));
        $token = trim((string) array_value($tokens, $i, ''));
        if ($token === '') {
            continue;
        }
        if ($name === '') {
            $name = 'Bot ' . (count($ret) + 1);
        }
        $enabled = intval(array_value($enableds, $i, 0)) ? 1 : 0;
        $ret[] = array(
            'id' => admin_bot_id_from_token($token),
            'name' => strip_tags($name),
            'token' => $token,
            'enabled' => $enabled,
        );
        if (count($ret) >= $max) {
            break;
        }
    }
    return $ret;
}

function admin_bot_pool_decode($raw, $legacy_token = '')
{
    $arr = array();
    $raw = trim((string) $raw);
    if ($raw !== '') {
        $arr = xn_json_decode($raw);
        if (!is_array($arr)) {
            $arr = json_decode($raw, true);
        }
    }
    $ret = array();
    if (is_array($arr)) {
        foreach ($arr as $it) {
            if (!is_array($it)) {
                continue;
            }
            $token = trim((string) array_value($it, 'token', ''));
            if ($token === '') {
                continue;
            }
            $ret[] = array(
                'id' => trim((string) array_value($it, 'id', admin_bot_id_from_token($token))),
                'name' => trim((string) array_value($it, 'name', 'Bot')),
                'token' => $token,
                'enabled' => intval(array_value($it, 'enabled', 1)) ? 1 : 0,
            );
        }
    }
    if (empty($ret) && trim((string) $legacy_token) !== '') {
        $legacy_token = trim((string) $legacy_token);
        $ret[] = array(
            'id' => admin_bot_id_from_token($legacy_token),
            'name' => '默认机器人',
            'token' => $legacy_token,
            'enabled' => 1,
        );
    }
    return $ret;
}

function admin_bot_pool_enabled($pool)
{
    $ret = array();
    foreach ((array) $pool as $bot) {
        if (intval(array_value($bot, 'enabled', 0)) !== 1) {
            continue;
        }
        $token = trim((string) array_value($bot, 'token', ''));
        if ($token === '') {
            continue;
        }
        $ret[] = $bot;
    }
    return $ret;
}

function admin_mask_token($token)
{
    $token = trim((string) $token);
    if ($token === '') {
        return '';
    }
    return substr($token, 0, 8) . '********';
}

switch ($action) {
    case 'save':
        if ('POST' == $method) {
            $base_url = trim((string) param('safew_base_url', '', false));
            $push_agent_code = strtoupper(trim((string) param('safew_push_agent_code', '', false)));
            $tag_pool = trim((string) param('safew_tag_pool', '', false));
            $tag_spoiler_on = intval(param('safew_tag_spoiler_on', 0)) ? 1 : 0;
            $bot_names = array_value($_POST, 'safew_bot_name', array_value($_POST, 'safew_bot_name[]', array()));
            $bot_tokens = array_value($_POST, 'safew_bot_token', array_value($_POST, 'safew_bot_token[]', array()));
            $bot_enabled = array_value($_POST, 'safew_bot_enabled', array_value($_POST, 'safew_bot_enabled[]', array()));
            $bot_names = is_array($bot_names) ? $bot_names : array();
            $bot_tokens = is_array($bot_tokens) ? $bot_tokens : array();
            $bot_enabled = is_array($bot_enabled) ? $bot_enabled : array();
            $bot_pool = admin_bot_pool_normalize($bot_names, $bot_tokens, $bot_enabled, 20);
            $menu_names = isset($_POST['safew_menu_name']) && is_array($_POST['safew_menu_name']) ? $_POST['safew_menu_name'] : array();
            $menu_urls = isset($_POST['safew_menu_url']) && is_array($_POST['safew_menu_url']) ? $_POST['safew_menu_url'] : array();
            $menu_buttons = admin_push_menu_normalize($menu_names, $menu_urls, 8);
            $push_limit = max(1, min(50, intval(param('safew_push_limit', 10))));
            $sync_limit = max(1, min(100, intval(param('safew_sync_limit', 100))));
            if ($base_url !== '' && !preg_match('#^https?://#i', $base_url)) {
                $base_url = 'https://' . ltrim($base_url, '/');
            }
            if ($push_agent_code !== '' && !preg_match('/^[A-Z0-9_\\-]{4,32}$/', $push_agent_code)) {
                message(1, '推送代理码格式错误，仅支持 A-Z 0-9 _ -，长度 4~32');
            }
            $replace = array(
                'safew_bot_token' => !empty($bot_pool) ? (string) array_value($bot_pool[0], 'token', '') : '',
                'safew_bot_pool' => json_encode($bot_pool, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'safew_base_url' => rtrim($base_url, '/'),
                'safew_push_agent_code' => $push_agent_code,
                'safew_tag_pool' => $tag_pool,
                'safew_tag_spoiler_on' => $tag_spoiler_on,
                'safew_menu_buttons' => json_encode($menu_buttons, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'safew_push_limit' => $push_limit,
                'safew_sync_limit' => $sync_limit,
            );
            file_replace_var(APP_PATH . 'config/config.php', $replace);
            message(0, lang('modify_successfully'));
        }
        break;
    case 'tg_save':
        if ('POST' == $method) {
            $base_url = trim((string) param('tg_base_url', '', false));
            $push_agent_code = strtoupper(trim((string) param('tg_push_agent_code', '', false)));
            $tag_pool = trim((string) param('tg_tag_pool', '', false));
            $bot_names = array_value($_POST, 'tg_bot_name', array_value($_POST, 'tg_bot_name[]', array()));
            $bot_tokens = array_value($_POST, 'tg_bot_token', array_value($_POST, 'tg_bot_token[]', array()));
            $bot_enabled = array_value($_POST, 'tg_bot_enabled', array_value($_POST, 'tg_bot_enabled[]', array()));
            $bot_names = is_array($bot_names) ? $bot_names : array();
            $bot_tokens = is_array($bot_tokens) ? $bot_tokens : array();
            $bot_enabled = is_array($bot_enabled) ? $bot_enabled : array();
            $bot_pool = admin_bot_pool_normalize($bot_names, $bot_tokens, $bot_enabled, 20);
            $menu_names = isset($_POST['tg_menu_name']) && is_array($_POST['tg_menu_name']) ? $_POST['tg_menu_name'] : array();
            $menu_urls = isset($_POST['tg_menu_url']) && is_array($_POST['tg_menu_url']) ? $_POST['tg_menu_url'] : array();
            $menu_buttons = admin_push_menu_normalize($menu_names, $menu_urls, 8);
            $push_limit = max(1, min(50, intval(param('tg_push_limit', 10))));
            $sync_limit = max(1, min(100, intval(param('tg_sync_limit', 100))));
            if ($base_url !== '' && !preg_match('#^https?://#i', $base_url)) {
                $base_url = 'https://' . ltrim($base_url, '/');
            }
            if ($push_agent_code !== '' && !preg_match('/^[A-Z0-9_\\-]{4,32}$/', $push_agent_code)) {
                message(1, '推送代理码格式错误，仅支持 A-Z 0-9 _ -，长度 4~32');
            }
            $replace = array(
                'tg_bot_token' => !empty($bot_pool) ? (string) array_value($bot_pool[0], 'token', '') : '',
                'tg_bot_pool' => json_encode($bot_pool, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'tg_base_url' => rtrim($base_url, '/'),
                'tg_push_agent_code' => $push_agent_code,
                'tg_tag_pool' => $tag_pool,
                'tg_menu_buttons' => json_encode($menu_buttons, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'tg_push_limit' => $push_limit,
                'tg_sync_limit' => $sync_limit,
            );
            file_replace_var(APP_PATH . 'config/config.php', $replace);
            message(0, lang('modify_successfully'));
        }
        break;
    case 'sync':
        if ('POST' == $method) {
            $bot_pool = admin_bot_pool_decode((string) array_value($conf, 'safew_bot_pool', ''), (string) array_value($conf, 'safew_bot_token', ''));
            $enabled_bots = admin_bot_pool_enabled($bot_pool);
            $limit = max(1, min(100, intval(array_value($conf, 'safew_sync_limit', 100))));
            if (empty($enabled_bots)) {
                message(1, '请先新增并启用 SafeW 机器人');
            }
            $total_updates = 0;
            $total_saved = 0;
            $failed_msgs = array();
            foreach ($enabled_bots as $bot) {
                $token = (string) array_value($bot, 'token', '');
                $bot_id = (string) array_value($bot, 'id', admin_bot_id_from_token($token));
                $ret = safew_sync_updates($token, $limit, $bot_id);
                if (!array_value($ret, 'ok', false)) {
                    $failed_msgs[] = (string) array_value($bot, 'name', $bot_id) . ':' . (string) array_value($ret, 'message', 'sync fail');
                    continue;
                }
                $total_updates += intval(array_value($ret, 'updates', 0));
                $total_saved += intval(array_value($ret, 'saved_chats', 0));
            }
            if (!empty($failed_msgs) && $total_saved === 0) {
                message(1, '同步失败：' . implode('；', $failed_msgs));
            }
            $msg = '同步完成：拉取 ' . $total_updates . ' 条，保存频道/群 ' . $total_saved . ' 条';
            if (!empty($failed_msgs)) {
                $msg .= '；部分失败：' . implode('；', $failed_msgs);
            }
            message(0, $msg);
        }
        break;
    case 'push':
        if ('POST' == $method) {
            $bot_pool = admin_bot_pool_decode((string) array_value($conf, 'safew_bot_pool', ''), (string) array_value($conf, 'safew_bot_token', ''));
            $enabled_bots = admin_bot_pool_enabled($bot_pool);
            $base_url = trim((string) array_value($conf, 'safew_base_url', ''));
            $limit = max(1, min(50, intval(array_value($conf, 'safew_push_limit', 10))));
            if (empty($enabled_bots) || $base_url === '') {
                $missing = array();
                if (empty($enabled_bots)) {
                    $missing[] = '至少启用1个机器人';
                }
                if ($base_url === '') {
                    $missing[] = '站点域名';
                }
                message(1, '请先配置：' . implode('、', $missing) . '（保存配置后再试）');
            }
            $total_sent = 0;
            $total_failed = 0;
            $failed_msgs = array();
            $info_msgs = array();
            foreach ($enabled_bots as $bot) {
                $token = (string) array_value($bot, 'token', '');
                $bot_id = (string) array_value($bot, 'id', admin_bot_id_from_token($token));
                $bot_name = (string) array_value($bot, 'name', $bot_id);
                $ret = safew_push_new_vod($token, $base_url, $limit, $bot_id);
                if (!array_value($ret, 'ok', false)) {
                    $failed_msgs[] = $bot_name . ':' . (string) array_value($ret, 'message', 'push fail');
                    continue;
                }
                $bot_sent = intval(array_value($ret, 'sent', 0));
                $bot_failed = intval(array_value($ret, 'failed', 0));
                $total_sent += $bot_sent;
                $total_failed += $bot_failed;
                if ($bot_sent === 0 && $bot_failed === 0) {
                    $reason = trim((string) array_value($ret, 'message', ''));
                    if ($reason === '') {
                        $reason = '无可推送数据';
                    }
                    $info_msgs[] = $bot_name . ':' . $reason;
                }
            }
            if (!empty($failed_msgs) && $total_sent === 0) {
                message(1, '推送失败：' . implode('；', $failed_msgs));
            }
            $msg = '推送完成：成功 ' . $total_sent . '，失败 ' . $total_failed;
            if (!empty($failed_msgs)) {
                $msg .= '；部分失败：' . implode('；', $failed_msgs);
            }
            if (!empty($info_msgs)) {
                $msg .= '；说明：' . implode('；', $info_msgs);
            }
            message(0, $msg);
        }
        break;
    case 'tg_sync':
        if ('POST' == $method) {
            $bot_pool = admin_bot_pool_decode((string) array_value($conf, 'tg_bot_pool', ''), (string) array_value($conf, 'tg_bot_token', ''));
            $enabled_bots = admin_bot_pool_enabled($bot_pool);
            $limit = max(1, min(100, intval(array_value($conf, 'tg_sync_limit', 100))));
            if (empty($enabled_bots)) {
                message(1, '请先新增并启用 Telegram 机器人');
            }
            $total_updates = 0;
            $total_saved = 0;
            $failed_msgs = array();
            foreach ($enabled_bots as $bot) {
                $token = (string) array_value($bot, 'token', '');
                $bot_id = (string) array_value($bot, 'id', admin_bot_id_from_token($token));
                $ret = telegram_sync_updates($token, $limit, $bot_id);
                if (!array_value($ret, 'ok', false)) {
                    $failed_msgs[] = (string) array_value($bot, 'name', $bot_id) . ':' . (string) array_value($ret, 'message', 'sync fail');
                    continue;
                }
                $total_updates += intval(array_value($ret, 'updates', 0));
                $total_saved += intval(array_value($ret, 'saved_chats', 0));
            }
            if (!empty($failed_msgs) && $total_saved === 0) {
                message(1, '同步失败：' . implode('；', $failed_msgs));
            }
            $msg = '同步完成：拉取 ' . $total_updates . ' 条，保存频道/群 ' . $total_saved . ' 条';
            if (!empty($failed_msgs)) {
                $msg .= '；部分失败：' . implode('；', $failed_msgs);
            }
            message(0, $msg);
        }
        break;
    case 'tg_push':
        if ('POST' == $method) {
            $bot_pool = admin_bot_pool_decode((string) array_value($conf, 'tg_bot_pool', ''), (string) array_value($conf, 'tg_bot_token', ''));
            $enabled_bots = admin_bot_pool_enabled($bot_pool);
            $base_url = trim((string) array_value($conf, 'tg_base_url', ''));
            $limit = max(1, min(50, intval(array_value($conf, 'tg_push_limit', 10))));
            if (empty($enabled_bots) || $base_url === '') {
                $missing = array();
                if (empty($enabled_bots)) {
                    $missing[] = '至少启用1个机器人';
                }
                if ($base_url === '') {
                    $missing[] = '站点域名';
                }
                message(1, '请先配置：' . implode('、', $missing) . '（保存配置后再试）');
            }
            $total_sent = 0;
            $total_failed = 0;
            $failed_msgs = array();
            $info_msgs = array();
            foreach ($enabled_bots as $bot) {
                $token = (string) array_value($bot, 'token', '');
                $bot_id = (string) array_value($bot, 'id', admin_bot_id_from_token($token));
                $bot_name = (string) array_value($bot, 'name', $bot_id);
                $ret = telegram_push_new_vod($token, $base_url, $limit, $bot_id);
                if (!array_value($ret, 'ok', false)) {
                    $failed_msgs[] = $bot_name . ':' . (string) array_value($ret, 'message', 'push fail');
                    continue;
                }
                $bot_sent = intval(array_value($ret, 'sent', 0));
                $bot_failed = intval(array_value($ret, 'failed', 0));
                $total_sent += $bot_sent;
                $total_failed += $bot_failed;
                if ($bot_sent === 0 && $bot_failed === 0) {
                    $reason = trim((string) array_value($ret, 'message', ''));
                    if ($reason === '') {
                        $reason = '无可推送数据';
                    }
                    $info_msgs[] = $bot_name . ':' . $reason;
                }
            }
            if (!empty($failed_msgs) && $total_sent === 0) {
                message(1, '推送失败：' . implode('；', $failed_msgs));
            }
            $msg = '推送完成：成功 ' . $total_sent . '，失败 ' . $total_failed;
            if (!empty($failed_msgs)) {
                $msg .= '；部分失败：' . implode('；', $failed_msgs);
            }
            if (!empty($info_msgs)) {
                $msg .= '；说明：' . implode('；', $info_msgs);
            }
            message(0, $msg);
        }
        break;
    case 'toggle':
        if ('POST' == $method) {
            $id = intval(param('id', 0));
            $enabled = intval(param('enabled', 0)) ? 1 : 0;
            $id < 1 && message(1, '参数错误');
            db_update('safew_chat', array('id' => $id), array('enabled' => $enabled, 'update_date' => (int) $time));
            message(0, '状态已更新');
        }
        break;
    case 'tg_toggle':
        if ('POST' == $method) {
            $id = intval(param('id', 0));
            $enabled = intval(param('enabled', 0)) ? 1 : 0;
            $id < 1 && message(1, '参数错误');
            db_update('tg_chat', array('id' => $id), array('enabled' => $enabled, 'update_date' => (int) $time));
            message(0, '状态已更新');
        }
        break;
    case 'list':
    default:
        if ('GET' == $method) {
            safew_install_tables();
            telegram_install_tables();
            global $db;
            $pre = $db->tablepre;
            $arrlist = db_sql_find("SELECT * FROM `{$pre}safew_chat` ORDER BY update_date DESC,id DESC");
            $arrlist = is_array($arrlist) ? $arrlist : array();
            $tg_arrlist = db_sql_find("SELECT * FROM `{$pre}tg_chat` ORDER BY update_date DESC,id DESC");
            $tg_arrlist = is_array($tg_arrlist) ? $tg_arrlist : array();
            $safew_bot_pool = admin_bot_pool_decode((string) array_value($conf, 'safew_bot_pool', ''), (string) array_value($conf, 'safew_bot_token', ''));
            $tg_bot_pool = admin_bot_pool_decode((string) array_value($conf, 'tg_bot_pool', ''), (string) array_value($conf, 'tg_bot_token', ''));
            $sync_limit = max(1, min(100, intval(array_value($conf, 'safew_sync_limit', 100))));
            $push_limit = max(1, min(50, intval(array_value($conf, 'safew_push_limit', 10))));
            $safew_menu_buttons = admin_push_menu_decode((string) array_value($conf, 'safew_menu_buttons', ''));
            $tg_sync_limit = max(1, min(100, intval(array_value($conf, 'tg_sync_limit', 100))));
            $tg_push_limit = max(1, min(50, intval(array_value($conf, 'tg_push_limit', 10))));
            $tg_menu_buttons = admin_push_menu_decode((string) array_value($conf, 'tg_menu_buttons', ''));
            $header['title'] = 'SafeW机器人';
            include _include(APP_PATH . 'admin/html/safew_only_list.html');
        }
        break;
}
