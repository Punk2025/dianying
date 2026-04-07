<?php
$g_static_users = array();
function user__create($arr, $d = NULL)
{
    $r = db_insert('user', $arr, $d);
    return $r;
}
function user__update($uid, $update, $d = NULL)
{
    $r = db_update('user', array('uid' => $uid), $update, $d);
    return $r;
}
function user__read($cond = array(), $orderby = array(), $col = array(), $d = NULL)
{
    $user = db_find_one('user', $cond, $orderby, $col, $d);
    return $user;
}
function user__find($cond = array(), $orderby = array(), $page = 1, $pagesize = 20, $key = 'uid', $col = array(), $d = NULL)
{
    $arr = db_find('user', $cond, $orderby, $page, $pagesize, $key, $col, $d);
    return $arr;
}
function user__delete($uid, $d = NULL)
{
    $r = db_delete('user', array('uid' => $uid), $d);
    return $r;
}
function user_count($cond = array(), $d = NULL)
{
    $n = db_count('user', $cond, $d);
    return $n;
}
function user_big_insert($arr = array(), $d = NULL)
{
    $r = db_big_insert('user', $arr, $d);
    return $r;
}
function user_big_update($cond = array(), $update = array(), $d = NULL)
{
    $r = db_big_update('user', $cond, $update, $d);
    return $r;
}
function group__create($arr)
{
    $r = db_create('group', $arr);
    return $r;
}
function group__update($gid, $arr)
{
    $r = db_update('group', array('gid' => $gid), $arr);
    return $r;
}
function group__read($gid)
{
    $group = db_find_one('group', array('gid' => $gid));
    return $group;
}
function group__delete($gid)
{
    $r = db_delete('group', array('gid' => $gid));
    return $r;
}
function group__find($cond = array(), $orderby = array(), $page = 1, $pagesize = 1000)
{
    $grouplist = db_find('group', $cond, $orderby, $page, $pagesize, 'gid');
    return $grouplist;
}
function user_create($arr)
{
    global $conf;
    $r = user__create($arr);
    runtime_set('users+', 1);
    runtime_set('todayusers+', 1);
    return $r;
}
function user_update($uid, $arr)
{
    global $conf, $g_static_users;
    if (empty($uid)) return FALSE;
    $r = user__update($uid, $arr);
    'mysql' != $conf['cache']['type'] and cache_delete('user-' . $uid);
    isset($g_static_users[$uid]) and $g_static_users[$uid] = array_merge($g_static_users[$uid], $arr);
    return $r;
}
function user_read($uid)
{
    global $g_static_users;
    $uid = intval($uid);
    if (empty($uid)) return array();
    if (isset($g_static_users[$uid])) return $g_static_users[$uid];
    $user = user__read(array('uid' => $uid));
    if ($user) {
        user_format($user);
        $g_static_users[$user['uid']] = $user;
    }
    return $user;
}
function user_read_cache($uid)
{
    global $conf, $g_static_users;
    if (isset($g_static_users[$uid])) return $g_static_users[$uid];
    if (0 == $uid) return user_guest();
    if ('mysql' == $conf['cache']['type']) {
        $r = user_read($uid);
    } else {
        $r = cache_get('user-' . $uid);
        if (NULL === $r) {
            $r = user_read($uid);
            $r and cache_set('user-' . $uid, $r, 7200);
        }
    }
    $g_static_users[$uid] = $r ? $r : user_guest();
    return $g_static_users[$uid];
}
function user_delete($uid)
{
    global $conf, $g_static_users;
    $user = user_read($uid);
    if (empty($user)) return FALSE;
    $r = user__delete($uid);
    'mysql' == $conf['cache']['type'] || cache_delete('user-' . $uid);
    if (isset($g_static_users[$uid])) unset($g_static_users[$uid]);
    runtime_set('users-', 1);
    return $r;
}
function user_find($cond = array(), $orderby = array(), $page = 1, $pagesize = 20)
{
    global $g_static_users;
    $userlist = user__find($cond, $orderby, $page, $pagesize);
    if (!$userlist) return NULL;
    foreach ($userlist as &$user) {
        user_format($user);
        $g_static_users[$user['uid']] = $user;
    }
    return $userlist;
}
function user_read_by_email($email)
{
    global $g_static_users;
    $user = user__read(array('email' => $email));
    if ($user) {
        user_format($user);
        $g_static_users[$user['uid']] = $user;
    }
    return $user;
}
function user_read_by_username($username)
{
    global $g_static_users;
    $user = user__read(array('username' => $username));
    if ($user) {
        user_format($user);
        $g_static_users[$user['uid']] = $user;
    }
    return $user;
}
function user_maxid()
{
    $n = db_maxid('user', 'uid');
    return $n;
}
function user_format(&$user)
{
    $conf = _SERVER('conf');
    if (empty($user)) return;
    $user['create_ip_fmt'] = safe_long2ip($user['create_ip']);
    $user['create_date_fmt'] = empty($user['create_date']) ? '0000-00-00' : date('Y-m-d', $user['create_date']);
    $user['login_ip_fmt'] = safe_long2ip($user['login_ip']);
    $user['login_date_fmt'] = empty($user['login_date']) ? '0000-00-00' : date('Y-m-d', $user['login_date']);
    $user['groupname'] = group_name($user['gid']);
    $onlinelist = online_user_list_cache();
    $user['online_status'] = isset($onlinelist[$user['uid']]) ? 1 : 0;
    $user['url'] = url('user-' . $user['uid']);
}
function user_guest()
{
    $conf = _SERVER('conf');
    static $guest = NULL;
    if ($guest) return $guest; // 返回引用，节省内存。
    $guest = array(
        'uid' => 0,
        'gid' => 0,
        'groupname' => lang('guest_group'),
        'username' => lang('guest'),
        'create_ip_fmt' => '',
        'create_date_fmt' => '',
        'login_date_fmt' => '',
        'email' => '',
    );
    return $guest;
}
function user_update_group($uid)
{
    global $conf, $grouplist;
    if (empty($uid)) return FALSE;
    $user = user_read_cache($uid);
    if ($user['gid'] < 100) return FALSE;
    foreach ($grouplist as $group) {
        if ($group['gid'] < 100) continue;
    }
    return FALSE;
}
function user_find_by_uids($uids)
{
    $uids = trim($uids);
    if (empty($uids)) return array();
    $arr = explode(',', $uids);
    $r = array();
    foreach ($arr as $_uid) {
        $user = user_read_cache($_uid);
        if (empty($user)) continue;
        $r[$user['uid']] = $user;
    }
    return $r;
}
function user_safe_info($user)
{
    unset($user['password'], $user['credits'], $user['golds'], $user['money'], $user['email'], $user['salt'], $user['password_sms'], $user['idnumber'], $user['realname'], $user['qq'], $user['mobile'], $user['create_ip'], $user['create_ip_fmt'], $user['create_date'], $user['create_date_fmt'], $user['login_ip'], $user['login_date'], $user['login_ip_fmt'], $user['login_date_fmt'], $user['logins'], $user['avatar_path']);
    return $user;
}
function user_rest()
{
    $uid = intval(_SESSION('uid'));
    empty($uid) and $uid = user_token_get() and $_SESSION['uid'] = $uid;
    $user = user_read($uid);
    return $user;
}
function user_token_get()
{
    global $conf, $time;
    $_uid = user_token_get_do();
    empty($_uid) and user_token_clear(); // 退出登录
    return $_uid;
}
function user_token_get_do()
{
    global $conf, $time, $ip, $useragent;
    $token = _COOKIE($conf['cookie_pre'] . 'token');
    if (empty($token)) return FALSE;
    $tokenkey = md5(xn_key());
    $s = xn_decrypt($token, $tokenkey);
    if (empty($s)) return FALSE;
    $arr = explode("\t", $s);
    if (count($arr) != 5) return FALSE;
    list($_ip, $_time, $_uid, $_pwd, $ua_md5) = $arr;
    if (array_value($conf, 'login_ip') && $ip != $_ip) return FALSE;
    if (array_value($conf, 'login_ua') && md5($useragent) != $ua_md5) return FALSE;
    $_user = user_read($_uid);
    if (empty($_user)) return FALSE;
    if (array_value($conf, 'login_only') && $_user['login_date'] != $_time) return FALSE;
    if (md5($_user['password']) != $_pwd) return FALSE;
    return $_uid;
}
function user_token_set($uid)
{
    global $conf, $time;
    if (empty($uid)) return '';
    $token = user_token_gen($uid);
    $https = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) != 'off') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    $opt = array(
        'expires' => $time + 86400000,
        'path' => $conf['cookie_path'],
        'secure' => $https,
        'httponly' => TRUE,
        'samesite' => 'Lax',
    );
    if (!empty($conf['cookie_domain'])) $opt['domain'] = $conf['cookie_domain'];
    setcookie($conf['cookie_pre'] . 'token', $token, $opt);
    return $token;
}
function user_token_clear()
{
    global $conf, $time;
    $https = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) != 'off') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    $opt = array(
        'expires' => $time - 8640000,
        'path' => $conf['cookie_path'],
        'secure' => $https,
        'httponly' => TRUE,
        'samesite' => 'Lax',
    );
    if (!empty($conf['cookie_domain'])) $opt['domain'] = $conf['cookie_domain'];
    setcookie($conf['cookie_pre'] . 'token', '', $opt);
}
function user_token_gen($uid)
{
    global $conf, $time, $ip, $useragent;
    $key = 'user_token' . $uid;
    static $cache = array();
    if (isset($cache[$key])) return $cache[$key];
    $user = user_read($uid);
    $pwd = md5($user['password']);
    $ua_md5 = md5($useragent);
    $tokenkey = md5(xn_key());
    $cache[$key] = xn_encrypt("$ip	$time	$uid	$pwd	$ua_md5", $tokenkey);
    return $cache[$key];
}
// 前台登录验证
function user_login_check()
{
    global $user;
    empty($user) and http_location(url('user-login'));
}
function user_http_referer()
{
    global $conf;
    $referer = param('referer');
    empty($referer) and $referer = array_value($_SERVER, 'HTTP_REFERER', '');
    $referer = str_replace(array('\"', '"', '<', '>', ' ', '*', "\t", "\r", "\n"), '', $referer);
    if (
        !preg_match('#^(http|https)://[\w\-=/\.]+/[\w\-=.%\#?]*$#is', $referer)
        || FALSE !== strpos($referer, url('user-login'))
        || FALSE !== strpos($referer, url('user-logout'))
        || FALSE !== strpos($referer, url('user-create'))
        || FALSE !== strpos($referer, url('user-setpw'))
        || FALSE !== strpos($referer, url('user-resetpw_complete'))
    ) {
        $referer = $conf['path'];
    }
    return $referer;
}
function user_auth_check($token)
{
    global $time, $ip;
    $auth = param(2);
    $s = xn_decrypt($auth);
    empty($s) and message(-1, lang('decrypt_failed'));
    $arr = explode('-', $s);
    count($arr) != 4 and message(-1, lang('encrypt_failed'));
    list($_ip, $_time, $_uid, $_pwd) = $arr;
    $_user = user_read($_uid);
    empty($_user) and message(-1, lang('user_not_exists'));
    $time - $_time > 3600 and message(-1, lang('link_has_expired'));
    return $_user;
}
function group_create($arr)
{
    if (empty($arr)) return FALSE;
    $r = group__create($arr);
    group_list_cache_delete();
    return $r;
}
function group_update($gid, $arr)
{
    $r = group__update($gid, $arr);
    group_list_cache_delete();
    return $r;
}
function group_read($gid)
{
    $group = group__read($gid);
    group_format($group);
    return $group;
}
function group_delete($gid)
{
    if (empty($gid)) return FALSE;
    $r = group__delete($gid);
    group_list_cache_delete();
    return $r;
}
function group_find($cond = array(), $orderby = array('gid' => 1), $page = 1, $pagesize = 1000)
{
    $grouplist = group__find($cond, $orderby, $page, $pagesize);
    if ($grouplist) foreach ($grouplist as &$group) group_format($group);
    return $grouplist;
}
function group_format(&$group) {}
function group_name($gid)
{
    global $grouplist;
    return isset($grouplist[$gid]['name']) ? $grouplist[$gid]['name'] : '';
}
function group_count($cond = array())
{
    $n = db_count('group', $cond);
    return $n;
}
function group_maxid()
{
    $n = db_maxid('group', 'gid');
    return $n;
}
function group_access($gid, $access)
{
    global $grouplist, $uid;
    if (empty($gid)) return FALSE;
    static $result = array();
    $k = 'group_' . $gid . '-' . $access;
    if (isset($result[$k])) return $result[$k];
    if (3 == DEBUG) return TRUE;
    if (1 == $gid) return TRUE;
    if (!isset($grouplist[$gid])) return TRUE;
    $group = $grouplist[$gid];
    $result[$k] = empty($group[$access]) ? FALSE : TRUE;
    return $result[$k];
}
function group_list_cache()
{
    global $conf;
    if ('mysql' == $conf['cache']['type']) {
        $grouplist = group_get();
    } else {
        $grouplist = cache_get('grouplist');
        if (NULL === $grouplist || FALSE === $grouplist) {
            $grouplist = group_find();
            cache_set('grouplist', $grouplist);
        }
    }
    return $grouplist;
}
function group_list_cache_delete()
{
    global $conf;
    $r = 'mysql' == $conf['cache']['type'] ? group_delete_cache() : cache_delete('grouplist');
    return $r;
}
$g_grouplist = FALSE;
function group_get()
{
    global $g_grouplist;
    FALSE === $g_grouplist and $g_grouplist = website_get('grouplist');
    if (empty($g_grouplist)) {
        $g_grouplist = group_find();
        $g_grouplist and group_set($g_grouplist);
    }
    return $g_grouplist;
}
function group_set($val)
{
    global $g_grouplist;
    $g_grouplist = $val;
    return website_set('grouplist', $g_grouplist);
}
function group_delete_cache()
{
    website_set('grouplist', '');
    return TRUE;
}
