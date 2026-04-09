<?php
/**
 * Telegram 自动推送 worker（推送新入库视频）
 *
 * 用法：
 * php /www/wwwroot/sesewu3.eu.cc/niuniucms/cli/tg_push_worker.php <BOT_TOKEN> <BASE_URL> [limit] [bot_id]
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

$token = isset($argv[1]) ? trim((string) $argv[1]) : '';
$base_url = isset($argv[2]) ? trim((string) $argv[2]) : '';
$limit = isset($argv[3]) ? intval($argv[3]) : 10;
$limit = max(1, min(50, $limit));
$bot_id = isset($argv[4]) ? trim((string) $argv[4]) : '';
if ($bot_id === '') {
    $bot_id = substr(md5($token), 0, 16);
}

if ($token === '' || $base_url === '') {
    echo "usage: php cli/tg_push_worker.php <BOT_TOKEN> <BASE_URL> [limit]\n";
    exit(1);
}
if (!preg_match('#^https?://#i', $base_url)) {
    echo "BASE_URL must start with http:// or https://\n";
    exit(1);
}
if (!function_exists('telegram_push_new_vod')) {
    echo "telegram model not loaded\n";
    exit(1);
}
$ret = telegram_push_new_vod($token, $base_url, $limit, $bot_id);
echo json_encode($ret, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
exit(array_value($ret, 'ok', false) ? 0 : 1);

