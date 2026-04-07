<?php
!defined('DEBUG') and exit('Access Denied.');
user_login_check();
$action = param(1, 'index');
$header['title'] = '会员中心';
switch ($action) {
    case 'like':
        if ('GET' == $method) {
            $header['title'] = '我的点赞';
            include _include(APP_PATH . 'template/user/html/like.html');
        } elseif ('POST' == $method) {
            $aid = param('aid');
            $type = param('type');
            $model = param('model');
            if ($type == 'like') {
                if ($model == '1') {
                    art_like($aid, $uid);
                    art_like_view($aid);
                } else {
                    vod_like($aid, $uid);
                    vod_like_view($aid);
                }
                nn_message(0, '点赞成功');
            } elseif ($type == 'unlike') {
                if ($model == '1') {
                    art_unlike($aid, $uid);
                    art_unlike_view($aid);
                    art_like_del($aid, $uid);
                } else {
                    vod_unlike($aid, $uid);
                    vod_unlike_view($aid);
                    vod_like_del($aid, $uid);
                }
                nn_message(3, '取消点赞成功');
            } else {
                nn_message(-1, lang('parameter_error'));
            }
        }
        break;
    case 'art_like':
        if ('GET' == $method) {
            $header['title'] = '文章点赞';
            $page = param('page', 1);
            $limit = 20;
            $aidlist = art_like_list($uid, $page, $limit);
            $aids = array_column($aidlist, 'aid');
            $lfarrlis = art_find_asc($aids);
            include _include(APP_PATH . 'template/user/html/like.html');
        }
        break;
    case 'vod_like':
        if ('GET' == $method) {
            $header['title'] = '视频点赞';
            $page = param('page', 1);
            $limit = 20;
            $vidlist = vod_like_list($uid, $page, $limit);
            $vids = array_column($vidlist, 'aid');
            $lfarrlis = video_find_asc($vids);
            include _include(APP_PATH . 'template/user/html/like.html');
        }
        break;
    case 'favorite':
        if ('GET' == $method) {
            $header['title'] = '我的收藏';
            include _include(APP_PATH . 'template/user/html/favorite.html');
        } elseif ('POST' == $method) {
            $aid = param('aid');
            $type = param('type');
            $model = param('model');
            if ($type == 'favorite') {
                if ($model == '1') {
                    art_favorites($aid, $uid);
                    art_favorites_view($aid);
                } else {
                    vod_favorites($aid, $uid);
                    vod_favorites_view($aid);
                }
                nn_message(0, '收藏成功');
            } elseif ($type == 'unfavorite') {
                if ($model == '1') {
                    art_unfavorites($aid, $uid);
                    art_unfavorites_view($aid);
                    art_favorites_del($aid, $uid);
                } else {
                    vod_unfavorites($aid, $uid);
                    vod_unfavorites_view($aid);
                    vod_favorites_del($aid, $uid);
                }
                nn_message(3, '取消收藏成功');
            } else {
                nn_message(-1, lang('parameter_error'));
            }
        }
        break;
    case 'art_favorites':
        if ('GET' == $method) {
            $header['title'] = '文章收藏';
            $page = param('page', 1);
            $limit = 20;
            $aidlist = art_favorites_list($uid, $page, $limit);
            $aids = array_column($aidlist, 'aid');
            $lfarrlis = art_find_asc($aids)  ;
            include _include(APP_PATH . 'template/user/html/favorite.html');
        }
        break;
    case 'vod_favorites':
        if ('GET' == $method) {
            $header['title'] = '视频收藏';
            $page = param('page', 1);
            $limit = 20;
            $vidlist = vod_favorites_list($uid, $page, $limit);
            $vids = array_column($vidlist, 'aid');
            $lfarrlis = video_find_asc($vids);
            include _include(APP_PATH . 'template/user/html/favorite.html');
        }
        break;
    case 'password':
        if ('GET' == $method) {
            $header['title'] = '修改密码';
            include _include(APP_PATH . 'template/user/html/password.html');
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
        $header['title'] = '会员中心';
        include _include(APP_PATH . 'template/user/html/index.html');
        break;
}
