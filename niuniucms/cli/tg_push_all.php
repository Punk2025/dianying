<?php
/**
 * Telegram 多机器人推送 worker（读取后台启用机器人池）
 *
 * 用法：
 * php /www/wwwroot/sesewu3.eu.cc/niuniucms/cli/tg_push_all.php [limit]
 */
if (PHP_SAPI !== 'cli') {
    exit("CLI only\n");
}
define('SKIP_ROUTE', true);
$_SERVER['REQUEST_URI'] = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
$_SERVER['REMOTE_ADDR'] = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
include dirname(__DIR__) . '/index.php';
if (!function_exists('telegram_push_new_vod')) {
    include dirname(__DIR__) . '/model/telegram.php';
}

function tg_decode_bot_pool($raw, $legacy_token = '')
{
    $ret = array();
    $raw = trim((string) $raw);
    if ($raw !== '') {
        $arr = xn_json_decode($raw);
        if (!is_array($arr)) $arr = json_decode($raw, true);
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

$limit = isset($argv[1]) ? intval($argv[1]) : intval(array_value($conf, 'tg_push_limit', 10));
$limit = max(1, min(50, $limit));
$base_url = trim((string) array_value($conf, 'tg_base_url', ''));
if ($base_url === '') {
    echo json_encode(array('ok' => false, 'message' => 'tg_base_url empty'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit(1);
}

$pool = tg_decode_bot_pool((string) array_value($conf, 'tg_bot_pool', ''), (string) array_value($conf, 'tg_bot_token', ''));
$enabled = array();
foreach ($pool as $bot) {
    if (intval(array_value($bot, 'enabled', 0)) === 1) $enabled[] = $bot;
}
if (empty($enabled)) {
    echo json_encode(array('ok' => false, 'message' => 'no enabled bot'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit(1);
}

$summary = array('ok' => true, 'workers' => 0, 'sent' => 0, 'failed' => 0, 'details' => array(), 'errors' => array());
foreach ($enabled as $bot) {
    $token = (string) array_value($bot, 'token', '');
    $bot_id = (string) array_value($bot, 'id', substr(md5($token), 0, 16));
    $name = (string) array_value($bot, 'name', $bot_id);
    $ret = telegram_push_new_vod($token, $base_url, $limit, $bot_id);
    $summary['workers']++;
    if (!array_value($ret, 'ok', false)) {
        $summary['errors'][] = $name . ':' . (string) array_value($ret, 'message', 'push fail');
        continue;
    }
    $s = intval(array_value($ret, 'sent', 0));
    $f = intval(array_value($ret, 'failed', 0));
    $summary['sent'] += $s;
    $summary['failed'] += $f;
    $summary['details'][] = $name . ':sent=' . $s . ',failed=' . $f . ',msg=' . (string) array_value($ret, 'message', '');
}
if (!empty($summary['errors']) && $summary['sent'] === 0) {
    $summary['ok'] = false;
}
echo json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
exit($summary['ok'] ? 0 : 1);

