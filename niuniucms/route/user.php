<?php
!defined('DEBUG') and exit('Access Denied.');
$passwd_url = $user_passwd = url('user-passwd');
$action = param(1);
switch ($action) {
    case 'login':
        $uid and http_location($conf['path']);
        if ('GET' == $method) {
            $header['title'] = lang('user_login');
            empty($safe_token) and $safe_token = bull_token_set(0);
            $referer = user_http_referer();
            $extra = array('safe_token' => $safe_token);
            $form_action = url('user-login');
            include _file(view_load('user_login'));
        } elseif ('POST' == $method) {
            $email = param('email'); // 邮箱或者手机号 
            $email = filter_all_html($email);
            $password = param('password');
            empty($email) and message(-1, lang('email_is_empty'));
            if (is_email($email, $err)) {
                $_user = user_read_by_email($email);
                empty($_user) and message(-1, lang('email_not_exists'));
            } else {
                $_user = user_read_by_username($email);
                empty($_user) and message(-1, lang('username_not_exists'));
            }
            is_password($password, $err) || message(-1, $err);
            $check = (md5($password . $_user['salt']) == $_user['password']);
            
            empty($check) and message(-1, lang('password_incorrect'));
            $update = array('login_ip' => $longip, 'login_date' => time(), 'logins+' => 1); 
            user_update($_user['uid'], $update); 
            $_SESSION['uid'] = $_user['uid'];
            $token = user_token_set($_user['uid']); 
            unset($_user['password'], $_user['salt'], $_user['password_sms'], $_user['create_ip'], $_user['create_ip_fmt'], $_user['create_date'], $_user['login_ip'], $_user['login_date'], $_user['login_ip_fmt']);
            $extra = array('user' => $_user, 'token_key' => $conf['cookie_pre'] . 'token', 'token' => $token);
            message(0, lang('login_successfully'), $extra);
        }
        break;
    case 'create':
        $uid and http_location($conf['path']); 
        empty($conf['user_create_on']) and message(-1, lang('user_create_not_on'));
        if ('GET' == $method) {
            $header['title'] = lang('create_user');
            $referer = user_http_referer();
            empty($safe_token) and $safe_token = bull_token_set(0);
            $extra = array('safe_token' => $safe_token);
            $form_action = url('user-create', $extra);
            if ('1' == _GET('ajax')) {
                $apilist['header'] = $header;
                $apilist['safe_token'] = $safe_token;
                $apilist['referer'] = $referer;
                $apilist['action'] = $form_action;
                $conf['api_on'] ? message(0, $apilist) : message(0, lang('closed'));
            } else {
                include _include(APP_PATH . 'template/user/html/user_create.html');
            }
        } else if ('POST' == $method) { 
            $email = param('email');
            $username = param('username');
            $password = param('password');
            $code = param('code');
            $email = filter_all_html($email);
            empty($email) and message('email', lang('please_input_email'));
            $username = filter_all_html($username);
            empty($username) and message('username', lang('please_input_username'));
            empty($password) and message('password', lang('please_input_password'));
            if ($conf['user_create_email_on']) {
                $sess_email = _SESSION('user_create_email');
                $sess_code = _SESSION('user_create_code');
                empty($sess_code) and message('code', lang('click_to_get_verify_code'));
                empty($sess_email) and message('code', lang('click_to_get_verify_code'));
                $email != $sess_email and message('code', lang('verify_code_incorrect'));
                $code != $sess_code and message('code', lang('verify_code_incorrect'));
            }
            is_email($email, $err) || message('email', $err);
            $_user = user_read_by_email($email);
            $_user and message('email', lang('email_is_in_use'));
            is_username($username, $err) || message('username', $err);
            $_user = user_read_by_username($username);
            $_user and message('username', lang('username_is_in_use'));
            is_password($password, $err) || message('password', $err);
            
            $salt = xn_rand(16);
            $_user = array(
                'username' => $username,
                'email' => $email,
                'password' => md5($password . $salt),
                'salt' => $salt,
                'gid' => 2,
                'create_ip' => $longip,
                'create_date' => $time,
                'logins' => 1,
                'login_date' => $time,
                'login_ip' => $longip,
            );
            
            $uid = user_create($_user);
            FALSE === $uid and message(-1, lang('user_create_failed'));
            $user = user_read($uid); 
            unset($_SESSION['user_create_email'], $_SESSION['user_create_code']);
            $_SESSION['uid'] = $uid;
            $token = user_token_set($uid);
            unset($user['password'], $user['salt'], $user['password_sms'], $user['create_ip'], $user['create_ip_fmt'], $user['create_date'], $user['login_ip'], $user['login_date'], $user['login_ip_fmt'], $user['avatar_path']);
            $extra = array('user' => $user, 'token_key' => $conf['cookie_pre'] . 'token', 'token' => $token);
            message(0, lang('user_create_successfully'), $extra);
        }
        break;
    case 'logout':
        $uid = 0;
        $_SESSION['uid'] = $uid;
        user_token_clear();
        message(0, jump(lang('logout_successfully'), url('/'), 1));
        break;
    case 'passwd':
        if ('GET' == $method) {
            include _file(view_load('user_passwd'));
        } elseif ('POST' == $method) {
            $password_old = param('password_old');
            $password_new = param('password_new');
            $password_new_repeat = param('password_new_repeat');
            $password_new_repeat != $password_new and message(-1, lang('repeat_password_incorrect'));
            md5($password_old . $user['salt']) != $user['password'] and message(-1, lang('old_password_incorrect'));
            $password_new = md5($password_new . $user['salt']);
            FALSE === user_update($uid, array('password' => $password_new)) and message(-1, lang('password_modify_failed'));
            message(0, lang('password_modify_successfully'));
        }
        break;
    
    default:
        include _file(view_load('index'));
        break;
}
