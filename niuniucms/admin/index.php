<?php
define('SKIP_ROUTE', TRUE);
define('ADMIN_PATH', dirname(__FILE__) . '/'); // __DIR__ 
$url_access = TRUE;
include '../index.php';
include _include(ADMIN_PATH . 'admin.func.php');
$_REQUEST = array_merge($_COOKIE, $_POST, $_GET);
if (DEBUG < 3) { 
    if (FALSE === group_access($gid, 'intoadmin')) {
        setcookie($conf['cookie_pre'] . 'sid', '', $time - 86400);
        http_location(url('../user-login', '', 2));
    }
    admin_token_check();
}
$nnzs=niuniuzs_codes(); 
$version=$conf['version'];
$rescode=codes();
$cloud_version=$rescode['cloud_version'];
$update_log=$rescode['update_log']; 
$route = param(0, 'index');
//cron_run();
switch ($route) { 
    case 'index':
        include _include(ADMIN_PATH . 'route/index.php');
        break;
    case 'cate':
        include _include(ADMIN_PATH . 'route/cate.php');
        break;
    case 'art':
        include _include(ADMIN_PATH . 'route/art.php');
        break;
    case 'vod':
        include _include(ADMIN_PATH . 'route/vod.php');
        break;
    case 'link':
        include _include(ADMIN_PATH . 'route/link.php');
        break;
     case 'api':
        include _include(ADMIN_PATH . 'route/api.php');
        break; 
    case 'set':
        include _include(ADMIN_PATH . 'route/set.php');
        break;
    case 'tmpl':
        include _include(ADMIN_PATH . 'route/tmpl.php');
        break;
     case 'user':
        include _include(ADMIN_PATH . 'route/user.php');
        break; 
    default:
        include _include(ADMIN_PATH . 'route/index.php');
        break;
}
