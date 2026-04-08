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
function early_acl_split_list($s)
{
    $s = str_replace(array('，', "\r", "\n", "\t"), array(',', ',', ',', ''), (string) $s);
    return array_values(array_filter(array_map('trim', explode(',', $s))));
}
function early_acl_client_ip($conf)
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if (empty($conf['cdn_on'])) {
        return trim((string) $ip);
    }
    $candidates = array(
        $_SERVER['HTTP_CDN_SRC_IP'] ?? '',
        $_SERVER['HTTP_CLIENTIP'] ?? '',
        $_SERVER['HTTP_CLIENT_IP'] ?? '',
        $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '',
    );
    foreach ($candidates as $cand) {
        $cand = trim((string) $cand);
        if ($cand === '') continue;
        if (strpos($cand, ',') !== false) {
            $arr = array_filter(array_map('trim', explode(',', $cand)));
            $cand = !empty($arr) ? end($arr) : '';
        }
        if ($cand !== '') return $cand;
    }
    return trim((string) $ip);
}
function early_acl_ipv4_u32($ip)
{
    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) return false;
    $n = ip2long($ip);
    if ($n === false) return false;
    if ($n < 0) $n = sprintf('%u', $n);
    return (float) $n;
}
function early_acl_ip_in_cidr($ip, $cidr)
{
    if (strpos($cidr, '/') === false) return false;
    list($base, $mask) = array_pad(explode('/', $cidr, 2), 2, '');
    $mask = intval($mask);
    if ($mask < 0 || $mask > 32) return false;
    $ipN = early_acl_ipv4_u32($ip);
    $baseN = early_acl_ipv4_u32(trim($base));
    if ($ipN === false || $baseN === false) return false;
    $size = pow(2, 32 - $mask);
    if ($size <= 0) return false;
    return floor($ipN / $size) === floor($baseN / $size);
}
function early_acl_ip_in_list($ip, $listText)
{
    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) return false;
    $items = early_acl_split_list($listText);
    foreach ($items as $rule) {
        if ($rule === '') continue;
        if (strpos($rule, '/') !== false) {
            if (early_acl_ip_in_cidr($ip, $rule)) return true;
        } elseif ($ip === $rule) {
            return true;
        }
    }
    return false;
}
if (!empty($conf['agent_ip_acl_on'])) {
    $clientIp = early_acl_client_ip($conf);
    $allowList = (string) ($conf['agent_ip_allowlist'] ?? '');
    $blockList = (string) ($conf['agent_ip_blocklist'] ?? '');
    $allow = $allowList !== '' && early_acl_ip_in_list($clientIp, $allowList);
    $block = !$allow && $blockList !== '' && early_acl_ip_in_list($clientIp, $blockList);
    if ($block) {
        $msg = trim((string) ($conf['agent_ip_deny_message'] ?? '访问受限'));
        if ($msg === '') $msg = '访问受限';
        header('HTTP/1.1 403 Forbidden');
        header('Content-Type: text/html; charset=utf-8');
        echo '<!doctype html><html><head><meta charset="utf-8"><title>403 Forbidden</title></head><body><h3>403 Forbidden</h3><p>' . htmlspecialchars($msg, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p></body></html>';
        exit;
    }
}
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
