<?php
/**
 * Telegram 频道ID同步（自动发现 chat_id）
 *
 * 用法：
 * php /www/wwwroot/sesewu3.eu.cc/niuniucms/cli/tg_sync_updates.php <BOT_TOKEN> [limit] [bot_id]
 */
if (PHP_SAPI !== 'cli') {
    exit("CLI only\n");
}
define('SKIP_ROUTE', true);
$_SERVER['REQUEST_URI'] = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
$_SERVER['REMOTE_ADDR'] = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
include dirname(__DIR__) . '/index.php';
if (!function_exists('telegram_sync_updates')) {
    include dirname(__DIR__) . '/model/telegram.php';
}

$token = isset($argv[1]) ? trim((string) $argv[1]) : '';
$limit = isset($argv[2]) ? intval($argv[2]) : 100;
$limit = max(1, min(100, $limit));
$bot_id = isset($argv[3]) ? trim((string) $argv[3]) : '';
if ($bot_id === '') {
    $bot_id = substr(md5($token), 0, 16);
}

if ($token === '') {
    echo "usage: php cli/tg_sync_updates.php <BOT_TOKEN> [limit]\n";
    exit(1);
}
if (!function_exists('telegram_sync_updates')) {
    echo "telegram model not loaded\n";
    exit(1);
}
$ret = telegram_sync_updates($token, $limit, $bot_id);
echo json_encode($ret, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
exit(array_value($ret, 'ok', false) ? 0 : 1);

