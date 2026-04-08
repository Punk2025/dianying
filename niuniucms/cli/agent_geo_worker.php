<?php
/**
 * IP 地区异步补全 worker
 *
 * 用法：
 * php /www/wwwroot/sesewu3.eu.cc/niuniucms/cli/agent_geo_worker.php 30 30 5
 * 参数1：每次处理数量（建议 20~50，默认 30）
 * 参数2：回溯天数（默认 30）
 * 参数3：最大失败次数（默认 5，达到后 status=2）
 */
if (PHP_SAPI !== 'cli') {
    exit("CLI only\n");
}
define('SKIP_ROUTE', true);
$_SERVER['REQUEST_URI'] = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
$_SERVER['REMOTE_ADDR'] = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
include dirname(__DIR__) . '/index.php';

$limit = isset($argv[1]) ? intval($argv[1]) : 30;
$days = isset($argv[2]) ? intval($argv[2]) : 30;
$maxFail = isset($argv[3]) ? intval($argv[3]) : 5;
$limit = max(1, min(200, $limit));
$days = max(1, min(180, $days));
$maxFail = max(1, min(20, $maxFail));

if (!function_exists('agent_ip_geo_resolve_batch')) {
    echo "agent_ip_geo_resolve_batch not found\n";
    exit(1);
}
$stat = agent_ip_geo_resolve_batch($limit, $days, $maxFail);
echo json_encode($stat, JSON_UNESCAPED_UNICODE) . PHP_EOL;
exit(0);
