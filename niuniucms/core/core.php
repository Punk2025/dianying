<?php
!defined('DEBUG') and define('DEBUG', 1); // 1: 开发模式， 2: 线上调试：日志记录，0: 关闭
!defined('APP_PATH') and define('APP_PATH', './');
!defined('CORE_PATH') and define('CORE_PATH', dirname(__FILE__) . '/');
function_exists('ini_set') and ini_set('display_errors', DEBUG ? '1' : '0');
error_reporting(DEBUG ? E_ALL : 0);
version_compare(PHP_VERSION, '5.3.0', '<') and set_magic_quotes_runtime(FALSE);
$get_magic_quotes_gpc = version_compare(PHP_VERSION, '5.4.0', '<') ? get_magic_quotes_gpc() : FALSE;
$starttime = microtime(1);
$time = time();
define('IN_CMD', !empty($_SERVER['SHELL']) || empty($_SERVER['REMOTE_ADDR']));
if (IN_CMD) {
    !isset($_SERVER['REMOTE_ADDR']) and $_SERVER['REMOTE_ADDR'] = '';
    !isset($_SERVER['REQUEST_URI']) and $_SERVER['REQUEST_URI'] = '';
    !isset($_SERVER['REQUEST_METHOD']) and $_SERVER['REQUEST_METHOD'] = 'GET';
} else {
    header("Content-type: text/html; charset=utf-8"); 
}
include CORE_PATH . 'db/db_mysql.php';
include CORE_PATH . 'db/db_pdo_mysql.php';
include CORE_PATH . 'cache/cache_memcached.php';
include CORE_PATH . 'cache/cache_mysql.php'; 
include CORE_PATH . 'cache/cache_yac.php';
include CORE_PATH . 'cache/cache_apc.class.php';
include CORE_PATH . 'cache/cache_redis.php';
include CORE_PATH . 'cache/cache_xcache.php';
include CORE_PATH . 'db/db.php';
include CORE_PATH . 'cache/cache.php';
include CORE_PATH . 'func/array.php';
include CORE_PATH . 'lib/core.php';
include CORE_PATH . 'lib/encrypt.php';
include CORE_PATH . 'func/misc.php';
empty($conf) and $conf = array('db' => array(), 'cache' => array(), 'cache_path' => './', 'log_path' => './', 'timezone' => 'Asia/Shanghai');
empty($conf['cache_path']) and $conf['cache_path'] = ini_get('upload_tmp_dir');
empty($conf['log_path']) and $conf['log_path'] = './';
$ip = ip();
$longip = bull_longip();
$useragent = _SERVER('HTTP_USER_AGENT');
!isset($lang) and $lang = array();
$errno = 0;
$errstr = '';
DEBUG and set_exception_handler('exception_handler');
DEBUG and set_error_handler('error_handle', -1);
empty($conf['timezone']) and $conf['timezone'] = 'Asia/Shanghai';
date_default_timezone_set($conf['timezone']);
// 超级全局变量
!empty($_SERVER['HTTP_X_REWRITE_URL']) and $_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_REWRITE_URL'];
!isset($_SERVER['REQUEST_URI']) and $_SERVER['REQUEST_URI'] = ''; 
$_REQUEST = array_merge($_COOKIE, $_POST, $_GET, xn_url_parse($_SERVER['REQUEST_URI'], $conf));
// IP 地址
!isset($_SERVER['REMOTE_ADDR']) and $_SERVER['REMOTE_ADDR'] = '';
!isset($_SERVER['SERVER_ADDR']) and $_SERVER['SERVER_ADDR'] = ''; 
$json = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower(trim($_SERVER['HTTP_X_REQUESTED_WITH'])) == 'xmlhttprequest') || param('json');
$method = $_SERVER['REQUEST_METHOD']; 
$_SERVER['starttime'] = $starttime;
$_SERVER['time'] = $time;
$_SERVER['ip'] = $ip;
$_SERVER['longip'] = $longip;
$_SERVER['useragent'] = $useragent;
$_SERVER['conf'] = $conf;
$_SERVER['lang'] = $lang;
$_SERVER['errno'] = $errno;
$_SERVER['errstr'] = $errstr;
$_SERVER['method'] = $method;
$_SERVER['json'] = $json;
$_SERVER['get_magic_quotes_gpc'] = $get_magic_quotes_gpc; 
$db = !empty($conf['db']) ? db_new($conf['db']) : NULL;
$conf['cache']['mysql']['db'] = $db;  
$cache = !empty($conf['cache']) ? cache_new($conf['cache']) : NULL;
unset($conf['cache']['mysql']['db']);
!empty($conf) and (function_exists('xiuno_key') ? ($conf['auth_key'] = xiuno_key()) : NULL);
$_SERVER['db'] = $db;
$_SERVER['cache'] = $cache;
?>