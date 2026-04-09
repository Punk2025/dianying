<?php
!defined('DEBUG') and exit('Access Denied.');

$cid = 0;
$action = param(1, 'list');

if (!function_exists('telegram_install_tables')) {
    include _include(APP_PATH . 'model/telegram.php');
}

function admin_push_menu_normalize_tg($names, $urls, $max = 8)
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
        $ret[] = array('text' => strip_tags($text), 'url' => strip_tags($url));
        if (count($ret) >= $max) {
            break;
        }
    }
    return $ret;
}

function admin_push_menu_decode_tg($raw)
{
    $raw = trim((string) $raw);
    if ($raw === '') return array();
    $arr = xn_json_decode($raw);
    if (!is_array($arr)) $arr = json_decode($raw, true);
    if (!is_array($arr)) return array();
    $ret = array();
    foreach ($arr as $it) {
        if (!is_array($it)) continue;
        $text = trim((string) array_value($it, 'text', ''));
        $url = trim((string) array_value($it, 'url', ''));
        if ($text === '' || $url === '') continue;
        $ret[] = array('text' => $text, 'url' => $url);
    }
    return $ret;
}

function admin_bot_id_from_token_tg($token)
{
    $token = trim((string) $token);
    if ($token === '') return '';
    return substr(md5($token), 0, 16);
}

function admin_bot_pool_normalize_tg($names, $tokens, $enableds, $max = 20)
{
    $ret = array();
    $names = is_array($names) ? $names : array();
    $tokens = is_array($tokens) ? $tokens : array();
    $enableds = is_array($enableds) ? $enableds : array();
    $n = max(count($names), count($tokens));
    for ($i = 0; $i < $n; $i++) {
        $name = trim((string) array_value($names, $i, ''));
        $token = trim((string) array_value($tokens, $i, ''));
        if ($token === '') continue;
        if ($name === '') $name = 'Bot ' . (count($ret) + 1);
        $ret[] = array(
            'id' => admin_bot_id_from_token_tg($token),
            'name' => strip_tags($name),
            'token' => $token,
            'enabled' => intval(array_value($enableds, $i, 0)) ? 1 : 0,
        );
        if (count($ret) >= $max) break;
    }
    return $ret;
}

function admin_bot_pool_decode_tg($raw, $legacy_token = '')
{
    $arr = array();
    $raw = trim((string) $raw);
    if ($raw !== '') {
        $arr = xn_json_decode($raw);
        if (!is_array($arr)) $arr = json_decode($raw, true);
    }
    $ret = array();
    if (is_array($arr)) {
        foreach ($arr as $it) {
            if (!is_array($it)) continue;
            $token = trim((string) array_value($it, 'token', ''));
            if ($token === '') continue;
            $ret[] = array(
                'id' => trim((string) array_value($it, 'id', admin_bot_id_from_token_tg($token))),
                'name' => trim((string) array_value($it, 'name', 'Bot')),
                'token' => $token,
                'enabled' => intval(array_value($it, 'enabled', 1)) ? 1 : 0,
            );
        }
    }
    if (empty($ret) && trim((string) $legacy_token) !== '') {
        $legacy_token = trim((string) $legacy_token);
        $ret[] = array('id' => admin_bot_id_from_token_tg($legacy_token), 'name' => '默认机器人', 'token' => $legacy_token, 'enabled' => 1);
    }
    return $ret;
}

function admin_bot_pool_enabled_tg($pool)
{
    $ret = array();
    foreach ((array) $pool as $bot) {
        if (intval(array_value($bot, 'enabled', 0)) !== 1) continue;
        $token = trim((string) array_value($bot, 'token', ''));
        if ($token === '') continue;
        $ret[] = $bot;
    }
    return $ret;
}

function admin_tg_dispatch_cli_job($script_name, $limit = 10, $force = true)
{
    $disabled = ',' . str_replace(' ', '', strtolower((string) ini_get('disable_functions'))) . ',';
    if (!function_exists('shell_exec') || strpos($disabled, ',shell_exec,') !== false) {
        return array('ok' => false, 'message' => 'shell_exec disabled');
    }
    $script_name = trim((string) $script_name);
    if ($script_name === '') {
        return array('ok' => false, 'message' => '脚本名为空');
    }
    $cli_file = APP_PATH . 'cli/' . $script_name;
    if (!is_file($cli_file)) {
        return array('ok' => false, 'message' => 'CLI脚本不存在：' . $script_name);
    }
    $php_bin = '/usr/bin/php';
    if (!is_file($php_bin)) {
        $php_bin = PHP_BINARY;
    }
    $limit = max(1, intval($limit));
    $log_file = '/www/wwwlogs/tg_jobs.log';
    if (strpos($script_name, 'push') !== false) {
        $log_file = '/www/wwwlogs/tg_push.log';
    } elseif (strpos($script_name, 'sync') !== false) {
        $log_file = '/www/wwwlogs/tg_sync.log';
    }
    $cmd = 'nohup ' . escapeshellarg($php_bin) . ' ' . escapeshellarg($cli_file) . ' ' . $limit;
    if ($force) {
        $cmd .= ' --force';
    }
    $cmd .= ' >> ' . escapeshellarg($log_file) . ' 2>&1 & echo $!';
    $pid = trim((string) @shell_exec($cmd));
    if ($pid === '' || !preg_match('/^\d+$/', $pid)) {
        return array('ok' => false, 'message' => '启动后台任务失败');
    }
    return array('ok' => true, 'pid' => $pid, 'log' => $log_file);
}

switch ($action) {
    case 'save':
        if ('POST' == $method) {
            $base_url = trim((string) param('tg_base_url', '', false));
            $push_agent_code = strtoupper(trim((string) param('tg_push_agent_code', '', false)));
            $tag_pool = trim((string) param('tg_tag_pool', '', false));
            $tag_spoiler_on = intval(param('tg_tag_spoiler_on', 0)) ? 1 : 0;
            $bot_names = array_value($_POST, 'tg_bot_name', array_value($_POST, 'tg_bot_name[]', array()));
            $bot_tokens = array_value($_POST, 'tg_bot_token', array_value($_POST, 'tg_bot_token[]', array()));
            $bot_enabled = array_value($_POST, 'tg_bot_enabled', array_value($_POST, 'tg_bot_enabled[]', array()));
            $bot_pool = admin_bot_pool_normalize_tg($bot_names, $bot_tokens, $bot_enabled, 20);
            $menu_names = isset($_POST['tg_menu_name']) && is_array($_POST['tg_menu_name']) ? $_POST['tg_menu_name'] : array();
            $menu_urls = isset($_POST['tg_menu_url']) && is_array($_POST['tg_menu_url']) ? $_POST['tg_menu_url'] : array();
            $menu_buttons = admin_push_menu_normalize_tg($menu_names, $menu_urls, 8);
            $push_limit = max(1, min(50, intval(param('tg_push_limit', 10))));
            $sync_limit = max(1, min(100, intval(param('tg_sync_limit', 100))));
            $sync_interval_min = max(1, min(1440, intval(param('tg_sync_interval_min', 1))));
            $push_interval_min = max(1, min(1440, intval(param('tg_push_interval_min', 5))));
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
                'tg_tag_spoiler_on' => $tag_spoiler_on,
                'tg_menu_buttons' => json_encode($menu_buttons, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'tg_push_limit' => $push_limit,
                'tg_sync_limit' => $sync_limit,
                'tg_sync_interval_min' => $sync_interval_min,
                'tg_push_interval_min' => $push_interval_min,
            );
            file_replace_var(APP_PATH . 'config/config.php', $replace);
            message(0, lang('modify_successfully'));
        }
        break;
    case 'sync':
        if ('POST' == $method) {
            $bot_pool = admin_bot_pool_decode_tg((string) array_value($conf, 'tg_bot_pool', ''), (string) array_value($conf, 'tg_bot_token', ''));
            $enabled_bots = admin_bot_pool_enabled_tg($bot_pool);
            $limit = max(1, min(100, intval(array_value($conf, 'tg_sync_limit', 100))));
            if (empty($enabled_bots)) message(1, '请先新增并启用 Telegram 机器人');
            $job = admin_tg_dispatch_cli_job('tg_sync_all.php', $limit, true);
            if (array_value($job, 'ok', false)) {
                $msg = '已启动后台同步任务（PID:' . (string) array_value($job, 'pid', '-') . '），请稍后刷新频道列表查看结果';
                message(0, $msg);
            }
            // 兼容禁用 shell_exec 的环境：入队后由每分钟 cron 拉起 tg_sync_all.php 执行
            kv_set('job_manual_tg_sync_force', '1');
            kv_set('job_manual_tg_sync_limit', strval($limit));
            kv_set('job_manual_tg_sync_enqueue_ts', strval(time()));
            message(0, '已加入同步队列，约1分钟内由定时任务执行（服务器禁用后台拉起）');
        }
        break;
    case 'push':
        if ('POST' == $method) {
            $bot_pool = admin_bot_pool_decode_tg((string) array_value($conf, 'tg_bot_pool', ''), (string) array_value($conf, 'tg_bot_token', ''));
            $enabled_bots = admin_bot_pool_enabled_tg($bot_pool);
            $base_url = trim((string) array_value($conf, 'tg_base_url', ''));
            $limit = max(1, min(50, intval(array_value($conf, 'tg_push_limit', 10))));
            if (empty($enabled_bots) || $base_url === '') {
                $missing = array();
                if (empty($enabled_bots)) $missing[] = '至少启用1个机器人';
                if ($base_url === '') $missing[] = '站点域名';
                message(1, '请先配置：' . implode('、', $missing) . '（保存配置后再试）');
            }
            $job = admin_tg_dispatch_cli_job('tg_push_all.php', $limit, true);
            if (array_value($job, 'ok', false)) {
                $msg = '已启动后台推送任务（PID:' . (string) array_value($job, 'pid', '-') . '），请稍后查看频道消息';
                message(0, $msg);
            }
            // 兼容禁用 shell_exec 的环境：入队后由每分钟 cron 拉起 tg_push_all.php 执行
            kv_set('job_manual_tg_push_force', '1');
            kv_set('job_manual_tg_push_limit', strval($limit));
            kv_set('job_manual_tg_push_enqueue_ts', strval(time()));
            message(0, '已加入推送队列，约1分钟内由定时任务执行（服务器禁用后台拉起）');
        }
        break;
    case 'toggle':
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
            telegram_install_tables();
            global $db;
            $pre = $db->tablepre;
            $tg_arrlist = db_sql_find("SELECT * FROM `{$pre}tg_chat` ORDER BY update_date DESC,id DESC");
            $tg_arrlist = is_array($tg_arrlist) ? $tg_arrlist : array();
            $tg_bot_pool = admin_bot_pool_decode_tg((string) array_value($conf, 'tg_bot_pool', ''), (string) array_value($conf, 'tg_bot_token', ''));
            $tg_sync_limit = max(1, min(100, intval(array_value($conf, 'tg_sync_limit', 100))));
            $tg_push_limit = max(1, min(50, intval(array_value($conf, 'tg_push_limit', 10))));
            $tg_sync_interval_min = max(1, min(1440, intval(array_value($conf, 'tg_sync_interval_min', 1))));
            $tg_push_interval_min = max(1, min(1440, intval(array_value($conf, 'tg_push_interval_min', 5))));
            $tg_menu_buttons = admin_push_menu_decode_tg((string) array_value($conf, 'tg_menu_buttons', ''));
            $header['title'] = 'Telegram机器人';
            include _include(APP_PATH . 'admin/html/telegram_list.html');
        }
        break;
}

