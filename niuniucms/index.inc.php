<?php
!defined('DEBUG') and exit('Access Denied.');
$sid = sess_start();
$_SERVER['lang'] = $lang = include _include(APP_PATH . 'lang/zh_cn.php');
$grouplist = group_list_cache();
$user = user_rest(); 
$uid = array_value($user, 'uid', 0);
$gid = array_value($user, 'gid', 0);
$group = isset($grouplist[$gid]) ? $grouplist[$gid] : $grouplist[0];
$config = setting_get('conf');
$cid = 0;
$catelist = cate_list_cache();
$cid_count = is_array($catelist) ? count($catelist) : 0;
$catearr = arrlist_key_values($catelist, 'cid', 'name');
$catelist_show = $catelist;
$cate_nav = category_tree($catelist);
$api_json['catelist'] = $cate_nav;
$runtime = runtime_init();
check_runlevel();
$txt_len = strpos($_SERVER['REQUEST_URI'], '.txt');
if (FALSE !== strpos($_SERVER['REQUEST_URI'], 'sitemap') && $txt_len) {
    $request_url = mb_substr($_SERVER['REQUEST_URI'], 0, $txt_len, 'UTF-8');
    $request_url = trim($request_url, '/');
    if ($request_url) {
        $request_url = str_replace('/', '-', $request_url);
        $requestlist = explode('-', $request_url);
        if (empty($requestlist) || !is_array($requestlist)) exit('Access Error.');
        $_REQUEST = $requestlist;
        $_REQUEST[0] = 'sitemap';
    }
}
$areaarray = explode(',', $conf['area']);
$yeararray = explode(',', $conf['year']);
$langarray = explode(',', $conf['lang']);
$route = param(0, 'index');
$route_type = $conf['route']['type'];
$route_list = $conf['route']['list'];
$route_cate = $conf['route']['cate'];
$route_vod = $conf['route']['vod'];
$route_play = $conf['route']['play'];
$route_tag = $conf['route']['tag'];
$route_art = $conf['route']['art'];
$route = param(0, 'index');
$pku_url = url($route_type, '', false);
$alias_reservation = array('index', 'admin','user', 'search','sitemap','adclick','adimg','adenc');
if ($conf['url_rewrite_on'] > 1 && !in_array($route, $alias_reservation)) {
    $aliaslist = cate_alias_cache();
    $alias_cid = array_value($aliaslist, $route, 0);
    if ($alias_cid) {
        $alias_cate = cate_read($alias_cid);
        $alias_param1 = param(1);
        $alias_param = explode('_', $alias_param1);
        if (!$alias_param1 || ('list'  == $alias_param[0] && is_numeric($alias_param[1]))) {
            $route = $_REQUEST[0] = 1 == $alias_cate['category'] ? $route_cate : $route_list;
            $_REQUEST[1] = $alias_cid;
            isset($alias_param[1]) and $_REQUEST[2] = $alias_param[1];
        } else {
            if ($alias_cate['model'] == 1) {
                $route = $_REQUEST[0] = $route_art;
            } else {
                $param2 = param(2, '');
                $param3 = param(3, '');
                if (strpos($param2, '_') !== false) {
                    $route = $_REQUEST[0] = $route_type;
                } elseif ($param3 == '') {
                    $route = $_REQUEST[0] = $route_vod;
                } else {
                    $route = $_REQUEST[0] = $route_play;
                }
            }
        }
    }
}
$path_tpl = '/template/' . $config['theme'] . '/';
$tpl_default_logo = $path_tpl . 'Images/logo.png';
$site_logo_pc = !empty($conf['logo_pc']) ? $conf['logo_pc'] : $tpl_default_logo;
$site_logo_m = !empty($conf['logo_m']) ? $conf['logo_m'] : $site_logo_pc;
$tpl_default_mobile_qr = $path_tpl . 'Images/erweima.png';
$site_mobile_watch_qr = !empty($conf['mobile_watch_qr']) ? $conf['mobile_watch_qr'] : $tpl_default_mobile_qr;
$maccms =$nncms = array(
    'site_name' => $conf['sitename'],
    'path_tpl' => $path_tpl,
    'v'=>findcate($catelist, 0),
    'a'=>findcate($catelist, 1),
    'site_url' => http_url_path(),
    'site_email' => $conf['email'],
    'logo' => $site_logo_pc,
    'logo_m' => $site_logo_m,
    'mobile_watch_qr' => $site_mobile_watch_qr,
    'path' => http_url_path(),
    'tj' => $conf['agg'],
    'type'=>$catelist,
);
$nncms +=$runtime;
if (0 == $conf['url_rewrite_on']) {
    $search_url = '/' . url('search');
} elseif (1 == $conf['url_rewrite_on']) {
    $search_url = '/' . url('search');
} elseif (2 == $conf['url_rewrite_on'] || 3 == $conf['url_rewrite_on']) {
    $search_url = url('search');
}
$user_like = url('member-like');
$user_favorite = url('member-favorite');
$user_login = url('user-login');
$user_create = url('user-create');
$user_index = url('member-index');
$user_logout = url('user-logout');
$sitemap_url = url('sitemap-list');
$danmu_list = url('danmu-list');
$danmu_send = url('danmu-send');
$stickytid = sticky_index_video();
$sidarr = arrlist_values($stickytid, 'vid');
$stickylist = video_find(array_unique($sidarr), count($sidarr));
if (isset($domain['site'][$hosts]) && is_array($domain['site'][$hosts])) {
    if (!empty($siteconf['path'])) {
        $config['theme'] = $siteconf['path'];
        $nncms['path_tpl'] = $maccms['path_tpl'] = $path_tpl = '/template/' . $siteconf['path'] . '/';
        if (empty($conf['logo_pc'])) {
            $nncms['logo'] = $maccms['logo'] = $path_tpl . 'Images/logo.png';
        }
        if (empty($conf['logo_m'])) {
            $nncms['logo_m'] = $maccms['logo_m'] = $nncms['logo'];
        }
        if (empty($conf['mobile_watch_qr'])) {
            $nncms['mobile_watch_qr'] = $maccms['mobile_watch_qr'] = $path_tpl . 'Images/erweima.png';
        }
    }
}
if (!defined('SKIP_ROUTE')) {
    if (function_exists('agent_access_ip_block_if_needed')) {
        agent_access_ip_block_if_needed($route);
    }
    ad_inject_nncms($nncms, $maccms);
    if (function_exists('agent_entry_jump_if_needed')) {
        agent_entry_jump_if_needed($route);
    }
    if (function_exists('agent_track_visit')) {
        agent_track_visit($route);
    }
    switch ($route) {
        case 'index':
            include _include(APP_PATH . 'route/index.php');
            break;
        case "$route_type":
            include _include(APP_PATH . 'route/type.php');
            break;
        case "$route_cate":
            include _include(APP_PATH . 'route/category.php');
            break;
        case "$route_list":
            include _include(APP_PATH . 'route/list.php');
            break;
        case "$route_vod":
            include _include(APP_PATH . 'route/vod.php');
            break;
        case "$route_play":
            include _include(APP_PATH . 'route/play.php');
            break;
        case "$route_tag":
            include _include(APP_PATH . 'route/tag.php');
            break;
        case "$route_art":
            include _include(APP_PATH . 'route/art.php');
            break;
        case 'search':
            include _include(APP_PATH . 'route/search.php');
            break;
        case 'sitemap':
            include _include(APP_PATH . 'route/sitemap.php');
            break;
        case 'user':
            include _include(APP_PATH . 'route/user.php');
            break;
        case 'member':
            include _include(APP_PATH . 'route/member.php');
            break;
        case 'danmu':
            include _include(APP_PATH . 'route/danmu.php');
            break;
        case 'adclick':
            include _include(APP_PATH . 'route/adclick.php');
            break;
        case 'adimg':
            include _include(APP_PATH . 'route/adimg.php');
            break;
        case 'adenc':
            include _include(APP_PATH . 'route/adenc.php');
            break;
        default:
             include _include(APP_PATH . 'route/index.php');
            break;
    }
}
