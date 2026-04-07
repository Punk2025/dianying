<?php 
define('XN_ADMIN_BIND_IP', array_value($conf, 'admin_bind_ip'));

/** 当前请求是否 HTTPS（含常见反代头），用于下发 Secure Cookie */
function admin_request_is_https()
{
    if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off' && (string) $_SERVER['HTTPS'] !== '0') {
        return true;
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower(trim((string) $_SERVER['HTTP_X_FORWARDED_PROTO'])) === 'https') {
        return true;
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_SSL']) === 'on') {
        return true;
    }
    return false;
}

/**
 * 后台 admin_token Cookie：HTTPS 必须带 Secure，否则部分浏览器不携带，点「编辑」等子页会反复掉登录或表现异常。
 */
function admin_admin_token_setcookie($value, $expires_ts)
{
    global $conf;
    $name = $conf['cookie_pre'] . 'admin_token';
    $path = admin_token_cookie_path();
    $domain = array_value($conf, 'cookie_domain', '');
    $secure = admin_request_is_https();
    if (PHP_VERSION_ID >= 70300) {
        $opts = array(
            'expires' => (int) $expires_ts,
            'path' => $path,
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        );
        if ($domain !== '' && $domain !== null) {
            $opts['domain'] = $domain;
        }
        return setcookie($name, $value, $opts);
    }
    return setcookie($name, $value, (int) $expires_ts, $path, $domain, $secure, true);
}

function admin_token_check()
{
    global $longip, $time, $useragent, $conf;
    $useragent_md5 = md5($useragent);
    $key = md5((XN_ADMIN_BIND_IP ? $longip : '') . $useragent_md5 . xn_key());
    // 必须原样读取 Cookie，禁止 htmlspecialchars，否则令牌可能被改写导致永远判空或解密失败
    $admin_token = param($conf['cookie_pre'] . 'admin_token', '', FALSE);
    if (empty($admin_token)) {
        $_REQUEST[0] = 'index';
        $_REQUEST[1] = 'login';
    } else {
        $s = xn_decrypt($admin_token, $key);
        if (empty($s)) {
            admin_admin_token_setcookie('', $time - 86400);
            message(-1, lang('admin_token_expiry'));
        }
        list($_ip, $_time) = explode("\t", $s);
        // 后台超过 3600 自动退出。
        if ((XN_ADMIN_BIND_IP && $_ip != $longip || !XN_ADMIN_BIND_IP) && $time - $_time > 3600) {
            admin_admin_token_setcookie('', $time - 86400);
            message(-1, lang('admin_token_expiry'));
        }
        $time - $_time > 1800 AND admin_token_set();
    }
}
function admin_token_cookie_path()
{
    global $conf;
    $p = array_value($conf, 'cookie_path', '');
    return ($p !== '' && $p !== null) ? $p : '/';
}
function admin_token_set()
{
    global $longip, $time, $useragent, $conf;
    $useragent_md5 = md5($useragent);
    $key = md5((XN_ADMIN_BIND_IP ? $longip : '') . $useragent_md5 . xn_key());
    $s = "$longip	$time";
    $admin_token = xn_encrypt($s, $key);
    admin_admin_token_setcookie($admin_token, $time + 3600);
}
function admin_token_clean()
{
    global $time, $conf;
    admin_admin_token_setcookie('', $time - 86400);
}
?>