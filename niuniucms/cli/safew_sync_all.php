<?php
/**
 * SafeW 多机器人同步 worker（读取后台启用机器人池）
 *
 * 用法：
 * php /www/wwwroot/sesewu3.eu.cc/niuniucms/cli/safew_sync_all.php [limit] [--force]
 */
if (PHP_SAPI !== 'cli') {
    exit("CLI only\n");
}
define('SKIP_ROUTE', true);
$_SERVER['REQUEST_URI'] = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
$_SERVER['REMOTE_ADDR'] = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
include dirname(__DIR__) . '/index.php';
if (!function_exists('safew_sync_updates')) {
    include dirname(__DIR__) . '/model/safew.php';
}

function safew_decode_bot_pool($raw, $legacy_token = '')
{
    $ret = array();
    $raw = trim((string) $raw);
    if ($raw !== '') {
        $arr = xn_json_decode($raw);
        if (!is_array($arr)) {
            $arr = json_decode($raw, true);
        }
        if (is_array($arr)) {
            foreach ($arr as $it) {
                if (!is_array($it)) continue;
                $token = trim((string) array_value($it, 'token', ''));
                if ($token === '') continue;
                $ret[] = array(
                    'id' => trim((string) array_value($it, 'id', substr(md5($token), 0, 16))),
                    'name' => trim((string) array_value($it, 'name', 'Bot')),
                    'token' => $token,
                    'enabled' => intval(array_value($it, 'enabled', 1)) ? 1 : 0,
                );
            }
        }
    }
    if (empty($ret) && trim((string) $legacy_token) !== '') {
        $legacy_token = trim((string) $legacy_token);
        $ret[] = array('id' => substr(md5($legacy_token), 0, 16), 'name' => '默认机器人', 'token' => $legacy_token, 'enabled' => 1);
    }
    return $ret;
}

$force = false;
$limit = intval(array_value($conf, 'safew_sync_limit', 100));
for ($i = 1; $i < count($argv); $i++) {
    $arg = trim((string) $argv[$i]);
    if ($arg === '--force') {
        $force = true;
        continue;
    }
    if (preg_match('/^\d+$/', $arg)) {
        $limit = intval($arg);
    }
}
$limit = max(1, min(100, $limit));
$interval_min = max(1, min(1440, intval(array_value($conf, 'safew_sync_interval_min', 1))));
if (!$force) {
    $now = time();
    $last = intval(kv_get('job_last_safew_sync_all_ts'));
    if ($last > 0 && ($now - $last) < ($interval_min * 60)) {
        echo json_encode(array('ok' => true, 'skipped' => true, 'message' => 'interval not reached', 'interval_min' => $interval_min), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
        exit(0);
    }
}
$pool = safew_decode_bot_pool((string) array_value($conf, 'safew_bot_pool', ''), (string) array_value($conf, 'safew_bot_token', ''));
$enabled = array();
foreach ($pool as $bot) {
    if (intval(array_value($bot, 'enabled', 0)) === 1) $enabled[] = $bot;
}
if (empty($enabled)) {
    echo json_encode(array('ok' => false, 'message' => 'no enabled bot'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit(1);
}

$summary = array('ok' => true, 'workers' => 0, 'updates' => 0, 'saved_chats' => 0, 'failed' => array());
foreach ($enabled as $bot) {
    $token = (string) array_value($bot, 'token', '');
    $bot_id = (string) array_value($bot, 'id', substr(md5($token), 0, 16));
    $name = (string) array_value($bot, 'name', $bot_id);
    $ret = safew_sync_updates($token, $limit, $bot_id);
    $summary['workers']++;
    if (!array_value($ret, 'ok', false)) {
        $summary['failed'][] = $name . ':' . (string) array_value($ret, 'message', 'sync fail');
        continue;
    }
    $summary['updates'] += intval(array_value($ret, 'updates', 0));
    $summary['saved_chats'] += intval(array_value($ret, 'saved_chats', 0));
}
if (!empty($summary['failed']) && $summary['saved_chats'] === 0) {
    $summary['ok'] = false;
}
echo json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
if ($summary['ok']) {
    kv_set('job_last_safew_sync_all_ts', strval(time()));
}
exit($summary['ok'] ? 0 : 1);

