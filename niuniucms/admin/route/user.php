<?php
!defined('DEBUG') and exit('Access Denied.');
3 != DEBUG && FALSE === group_access($gid, 'manageuser') and message(1, lang('user_group_insufficient_privilege'));
$action = param(1, 'list');
$system_group = array(0, 1, 2); 
switch ($action) {
    case 'list':
        $header['title'] = lang('user_admin');
        $header['mobile_title'] = lang('user_admin'); 
        $page = param('page', 1);
        $extra = array('page' => '{page}'); // 插件预留
        $pagesize = 20; 
        $srchtype = param('srchtype');
        $keyword = trim(xn_urldecode(param('keyword')));
        $srchtype and $keyword and $extra += array('srchtype' => $srchtype, 'keyword' => urlencode($keyword)); 
        $cond = array();
        $allowtype = array('uid', 'username', 'email', 'gid', 'create_ip');  
        if ($keyword) {
            !in_array($srchtype, $allowtype, TRUE) and $srchtype = 'uid';
            $cond[$srchtype] = 'create_ip' == $srchtype ? sprintf('%u', ip2long($keyword)) : $keyword;
        } 
        $n = user_count($cond);
        $userlist = user_find($cond, array('uid' => -1), $page, $pagesize);
        $pagination = pagination(url('user-list', $extra, TRUE), $n, $page, $pagesize); 
        foreach ($userlist as &$_user) {
            $_user['group'] = array_value($grouplist, $_user['gid'], '');
        }
        $safe_token = bull_token_set($uid); 
        include _include(ADMIN_PATH . 'html/user_list.html');
        break;
    case 'create': 
        if ('GET' == $method) { 
            $header['title'] = lang('admin_user_create');
            $header['mobile_title'] = lang('admin_user_create');
            $input['email'] = form_text('email', '');
            $input['username'] = form_text('username', '');
            $input['password'] = form_password('password', '');
            $grouparr = arrlist_key_values($grouplist, 'gid', 'name');
            $input['_gid'] = form_select('_gid', $grouparr, 0);
            $safe_token = bull_token_set($uid, 'admin_create_user');
            $input['safe_token'] = form_hidden('safe_token', $safe_token); 
            include _include(ADMIN_PATH . 'html/user_create.html');
        } elseif ('POST' == $method) {
            FALSE === group_access($gid, 'managecreateuser') and message(1, lang('user_group_insufficient_privilege'));
            $email = param('email');
            $username = param('username');
            $password = param('password');
            $_gid = param('_gid', 0); 
            empty($email) and message('email', lang('please_input_email'));
            $email && !is_email($email, $err) and message('email', $err);
            $username && !is_username($username, $err) and message('username', $err);
            $_user = user_read_by_email($email);
            $_user and message('email', lang('email_is_in_use'));
            $_user = user_read_by_username($username);
            $_user and message('username', lang('user_already_exists')); 
            $salt = xn_rand(16);
            $arr = array(
                'username' => $username,
                'password' => md5(md5($password) . $salt),
                'salt' => $salt,
                'gid' => $_gid,
                'email' => $email,
                'create_ip' => $longip,
                'create_date' => $time
            );  
            $r = user_create($arr);
            FALSE === $r and message(-1, lang('create_failed')); 
            message(0, lang('create_successfully'));
        }
        break;
    case 'update':
        $_uid = param('uid', 0);
        if ('GET' == $method) {
            $extra = array('uid' => $_uid); 
            $header['title'] = lang('user_edit');
            $header['mobile_title'] = lang('user_edit');
            $_user = user_read($_uid);
            $input['email'] = form_text('email', $_user['email']);
            $input['username'] = form_text('username', $_user['username']);
            $input['password'] = form_password('password', '');
            $grouparr = arrlist_key_values($grouplist, 'gid', 'name');
            $input['_gid'] = form_select('_gid', $grouparr, $_user['gid']);
            $safe_token = bull_token_set($uid, 'admin_update_user');
            $input['safe_token'] = form_hidden('safe_token', $safe_token); 
            include _include(ADMIN_PATH . 'html/user_update.html');
        } elseif ('POST' == $method) {
            3 != DEBUG && FALSE === group_access($gid, 'manageupdateuser') and message(1, lang('user_group_insufficient_privilege')); 
            $email = param('email');
            $username = param('username');
            $password = param('password');
            $_gid = param('_gid'); 
            $old = user_read($_uid);
            empty($old) and message('username', lang('uid_not_exists'));
            $email && !is_email($email, $err) and message(2, $err);
            if ($email and $old['email'] != $email) {
                $_user = user_read_by_email($email);
                $_user && $_user['uid'] != $_uid and message('email', lang('email_already_exists'));
            }
            if ($username and $old['username'] != $username) {
                $_user = user_read_by_username($username);
                $_user && $_user['uid'] != $_uid and message('username', lang('user_already_exists'));
            }
            $arr = array();
            $arr['email'] = $email;
            $arr['username'] = $username;
            $arr['gid'] = $_gid;
            if ($password) {
                $salt = xn_rand(16);
                $arr['password'] = md5(md5($password) . $salt);
                $arr['salt'] = $salt;
            }
            // 仅仅更新发生变化的部分 / only update changed field
            $update = array_diff_value($arr, $old);
            !empty($update) && FALSE === user_update($_uid, $update) and message(-1, lang('update_failed'));
            message(0, lang('update_successfully'));
        }
        break;
    case 'delete':
        if ('POST' != $method) message(-1, lang('method_error'));
        FALSE === group_access($gid, 'managedeleteuser') and message(1, lang('user_group_insufficient_privilege'));
        $safe_token = param('safe_token');
        FALSE === bull_token_verify($uid, $safe_token) and message(1, lang('illegal_operation'));
        $_uid = param('uid', 0); 
        $_user = user_read($_uid);
        empty($_user) and message(-1, lang('user_not_exists'));
        (1 == $_user['gid']) and message(-1, lang('admin_cant_be_deleted'));
        FALSE === user_delete($_uid) and message(-1, lang('delete_failed')); 
        message(0, lang('delete_successfully'));
        break;
     case 'group_list': 
        if ('GET' == $method) { 
            $header['title'] = lang('group_admin');
            $header['mobile_title'] = lang('group_admin'); 
            $maxgid = group_maxid();
            $safe_token = bull_token_set($uid); 
            include _include(ADMIN_PATH . 'html/user_group_list.html');
        } elseif ('POST' == $method) {
            $safe_token = param('safe_token');
            FALSE === bull_token_verify($uid, $safe_token) AND message(1, lang('illegal_operation'));
            FALSE === group_access($gid, 'managegroup') AND message(1, lang('user_group_insufficient_privilege'));
            $gidarr = param('_gid', array(0));
            $namearr = param('name', array(''));
            $arrlist = array();
            foreach ($gidarr as $k => $v) {
                $arr = array(
                    'gid' => $k,
                    'name' => $namearr[$k],
                );
                isset($grouplist[$k]) ? group_update($k, $arr) : group_create($arr);
            }
            // 删除 / delete
            $deletearr = array_diff_key($grouplist, $gidarr);
            foreach ($deletearr as $k => $v) {
                if (in_array($k, $system_group)) continue;
                group_delete($k);
            }
            group_list_cache_delete();
            message(0, lang('save_successfully'));
        }
        break;
    case 'group_update':
        $_gid = param('gid', 0);
        $_group = group_read($_gid);
        empty($_group) AND message(-1, lang('group_not_exists')); 
        if ('GET' == $method) {
            $extra = array('gid' => $_gid);
            $header['title'] = lang('group_admin');
            $header['mobile_title'] = lang('group_admin');
            FALSE === group_access($gid, 'manageupdategroup') AND message(1, lang('user_group_insufficient_privilege'));
            $input = array();
            $input['name'] = form_text('name', $_group['name']);
            $input['allowread'] = form_checkbox('allowread', $_group['allowread'], lang('allow_view')); 
            $input['intoadmin'] = form_checkbox('intoadmin', $_group['intoadmin'], lang('in_to_admin'));
            $safe_token = bull_token_set($uid, 'admin_update_group');
            $input['safe_token'] = form_hidden('safe_token', $safe_token);
            include _include(ADMIN_PATH . 'html/user_group_update.html');
        } elseif ('POST' == $method) {
            $safe_token = param('safe_token');
            $name = param('name');
            !$name and message(1, lang('group_name_empty'));
            $allowread = param('allowread', 0); 
            $intoadmin = param('intoadmin', 0);
            $arr = array(
                'name' => $name, 
                'allowread' => $allowread, 
                'intoadmin' => $intoadmin, 
            ); 
            group_update($_gid, $arr);
            message(0, lang('edit_successfully'));
        }
        break; 
    default:
        message(-1, lang('data_malformation'));
        break;
}
// hook admin_user_end.php
?>