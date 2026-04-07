<?php
!defined('DEBUG') and exit('Access Denied.');
$action = param(1);
switch ($action) {
    case 'login':
        if ('GET' == $method) {
            $header['title'] = lang('admin_login');
            include _include(ADMIN_PATH . "html/index_login.html");
        } elseif ('POST' == $method) {
            $password = param('password');
            if (md5($password . $user['salt']) != $user['password']) {
                xn_log('password error. uid:' . $user['uid'] . ' - ******' . substr($password, -6), 'admin_login_error');
                message('password', lang('password_incorrect'));
            }
            admin_token_set();
            xn_log('login successed. uid:' . $user['uid'], 'admin_login');
            message(0, jump(lang('login_successfully'), '.'));
        }
        break;
    case 'logout':
        admin_token_clean();
        message(0, jump(lang('logout_successfully'), $conf['path']));
        break;
    default:
        $info = array();
        $info['disable_functions'] = ini_get('disable_functions');
        $info['allow_url_fopen'] = ini_get('allow_url_fopen') ? lang('yes') : lang('no');
        $info['safe_mode'] = ini_get('safe_mode') ? lang('yes') : lang('no');
        empty($info['disable_functions']) && $info['disable_functions'] = lang('none');
        $info['upload_max_filesize'] = ini_get('upload_max_filesize');
        $info['post_max_size'] = ini_get('post_max_size');
        $info['memory_limit'] = ini_get('memory_limit');
        $info['max_execution_time'] = ini_get('max_execution_time');
        $info['dbversion'] = $db->version();
        $info['SERVER_SOFTWARE'] = _SERVER('SERVER_SOFTWARE');
        $info['HTTP_X_FORWARDED_FOR'] = _SERVER('HTTP_X_FORWARDED_FOR');
        $info['REMOTE_ADDR'] = _SERVER('REMOTE_ADDR');
        $stat = array();
        $stat['art'] = art_count();
        $stat['vod'] = video_count();
        $stat['tag'] = tag_count();
        $stat['users'] = isset($runtime['users']) ? $runtime['users'] : 0;
        $stat['disk_free_space'] = function_exists('disk_free_space') ? humansize(disk_free_space(APP_PATH)) : lang('unknown');
        //登陆后台清理
        art_del();
        vod_del();
        $header['title'] = lang('admin_page');
        include _include(APP_PATH . 'admin/html/index.html');
        break;
}
