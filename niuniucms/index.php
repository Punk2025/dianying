<?php 
!defined('DEBUG') AND define('DEBUG', 0);
!defined('APP_NAME') AND define('APP_NAME', 'NiuNiuCms');
define('APP_PATH', dirname(__FILE__) . '/'); // __DIR__
!defined('ADMIN_PATH') AND define('ADMIN_PATH', APP_PATH . 'admin/');
!defined('CORE_PATH') AND define('CORE_PATH', APP_PATH . 'core/');
register_shutdown_function(function () {
    $e = error_get_last();
    if (empty($e) || empty($e['type'])) return;
    $t = (int)$e['type'];
    if (!in_array($t, array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_RECOVERABLE_ERROR), true)) return;
    $now = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    $msg = isset($e['message']) ? (string)$e['message'] : '';
    $file = isset($e['file']) ? (string)$e['file'] : '';
    $line = isset($e['line']) ? (int)$e['line'] : 0;
    $msg = str_replace(array("\r", "\n", "\t"), ' ', $msg);
    $uri = str_replace(array("\r", "\n", "\t"), ' ', $uri);
    $logDir = APP_PATH . 'runtime/log/' . date('Ym') . '/';
    if (!is_dir($logDir)) @mkdir($logDir, 0777, true);
    $logFile = $logDir . 'php_fatal.php';
    $s = "<?php exit;?>\t$now\t$ip\t$uri\t0\t$t\t$msg\t$file\t$line\n";
    @file_put_contents($logFile, $s, FILE_APPEND | LOCK_EX);
});

$reqPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$reqPath = $reqPath ? rawurldecode($reqPath) : '';
$reqExt = $reqPath ? strtolower(pathinfo($reqPath, PATHINFO_EXTENSION)) : '';
$staticMime = [
    'css' => 'text/css; charset=utf-8',
    'js' => 'application/javascript; charset=utf-8',
    'map' => 'application/json; charset=utf-8',
    'json' => 'application/json; charset=utf-8',
    'png' => 'image/png',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'gif' => 'image/gif',
    'webp' => 'image/webp',
    'svg' => 'image/svg+xml',
    'ico' => 'image/x-icon',
    'woff' => 'font/woff',
    'woff2' => 'font/woff2',
    'ttf' => 'font/ttf',
    'eot' => 'application/vnd.ms-fontobject',
    'mp4' => 'video/mp4',
    'm3u8' => 'application/vnd.apple.mpegurl',
    'ts' => 'video/mp2t',
];
if ($reqExt && isset($staticMime[$reqExt])) {
    if (strpos($reqPath, "\0") !== false || strpos($reqPath, '..') !== false) {
        header('HTTP/1.1 400 Bad Request');
        exit;
    }
    $staticFile = APP_PATH . ltrim($reqPath, '/');
    if (is_file($staticFile)) {
        header('Content-Type: ' . $staticMime[$reqExt]);
        header('Cache-Control: public, max-age=604800');
        readfile($staticFile);
        exit;
    }
    header('HTTP/1.1 404 Not Found');
    exit;
}
$conf = (@include APP_PATH . 'config/config.php') OR exit('<script>window.location="install/"</script>');
$domain = (@include APP_PATH . 'config/domain.php');
substr($conf['log_path'], 0, 2) == './' AND $conf['log_path'] = APP_PATH . $conf['log_path'];
substr($conf['cache_path'], 0, 2) == './' AND $conf['cache_path'] = APP_PATH . $conf['cache_path'];
substr($conf['upload_path'], 0, 2) == './' AND $conf['upload_path'] = APP_PATH . $conf['upload_path'];
function get_domain($host = null, $level = 2)
{
    if ($host === null) {
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';
    }
    $host = trim((string)$host);
    if ($host === '') return '';
    $host = preg_replace('#^https?://#i', '', $host);
    $host = preg_replace('#[/].*$#', '', $host);
    if (preg_match('/^\[(.+)\](?::\d+)?$/', $host, $m)) {
        $hostNoPort = $m[1];
    } else {
        $hostNoPort = preg_replace('/:\d+$/', '', $host);
    }
    $hostNoPort = strtolower($hostNoPort);
    if (filter_var($hostNoPort, FILTER_VALIDATE_IP)) {
        return $hostNoPort;
    }
    $parts = explode('.', $hostNoPort);
    $count = count($parts);
    if ($count <= $level) {
        return $hostNoPort;
    }
    return implode('.', array_slice($parts, -$level));
}
$hosts = get_domain();
if (isset($domain['site'][$hosts]) && is_array($domain['site'][$hosts])) {
    $siteconf = $domain['site'][$hosts];
    if (isset($siteconf['site_rewrite'])) {
        $conf['url_rewrite_on'] = intval($siteconf['site_rewrite']);
        if ($conf['url_rewrite_on'] == 0 || $conf['url_rewrite_on'] == 1) {
            $conf['path'] = './';
            $conf['cookie_path'] = '';
        }
        if ($conf['url_rewrite_on'] == 2 || $conf['url_rewrite_on'] == 3) {
            $conf['path'] = '/';
            $conf['cookie_path'] = '/';
        }
    }
    if (isset($siteconf['site_name'])) {
        $conf['site_name'] = $siteconf['site_name'];
        
        $conf['sitename'] = $siteconf['site_name'];
    }
}
// 站群模式结束
$_SERVER['conf'] = $conf;
include CORE_PATH . 'core.php';
include APP_PATH . 'model/addons.php';
include _include(APP_PATH . 'model.inc.php');
include _include(APP_PATH . 'index.inc.php');
?>
