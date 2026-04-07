<?php 
define('XN_ADMIN_BIND_IP', array_value($conf, 'admin_bind_ip'));
function admin_token_check()
{
    global $longip, $time, $useragent, $conf;
    $useragent_md5 = md5($useragent);
    $key = md5((XN_ADMIN_BIND_IP ? $longip : '') . $useragent_md5 . xn_key());
    $admin_token = param($conf['cookie_pre'] . 'admin_token');
    if (empty($admin_token)) {
        $_REQUEST[0] = 'index';
        $_REQUEST[1] = 'login';
    } else {
        $s = xn_decrypt($admin_token, $key);
        if (empty($s)) {
            setcookie($conf['cookie_pre'] . 'admin_token', '', $time - 86400, $conf['cookie_path'], $conf['cookie_domain'], 0, TRUE);
            message(-1, lang('admin_token_expiry'));
        }
        list($_ip, $_time) = explode("\t", $s);
        // 后台超过 3600 自动退出。
        if ((XN_ADMIN_BIND_IP && $_ip != $longip || !XN_ADMIN_BIND_IP) && $time - $_time > 3600) {
            setcookie($conf['cookie_pre'] . 'admin_token', '', $time - 86400, $conf['cookie_path'], $conf['cookie_domain'], 0, TRUE);
            message(-1, lang('admin_token_expiry'));
        }
        $time - $_time > 1800 AND admin_token_set();
    }
}
function admin_token_set()
{
    global $longip, $time, $useragent, $conf;
    $useragent_md5 = md5($useragent);
    $key = md5((XN_ADMIN_BIND_IP ? $longip : '') . $useragent_md5 . xn_key());
    $s = "$longip	$time";
    $admin_token = xn_encrypt($s, $key);
    setcookie($conf['cookie_pre'] . 'admin_token', $admin_token, $time + 3600, $conf['cookie_path'], $conf['cookie_domain'], 0, TRUE);
}
function admin_token_clean()
{
    global $time, $conf;
    setcookie($conf['cookie_pre'] . 'admin_token', '', $time - 86400, $conf['cookie_path'], $conf['cookie_domain'], 0, TRUE);
}
?>