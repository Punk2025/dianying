<?php
!defined('DEBUG') and exit('Access Denied.');
$action = param(1, 'list');
        if ('GET' == $method) { 
            $page = param('page', 1);
            $pagesize = 20;
            $extra = array('page' => '{page}'); 
            $input = array();
            $input['name'] = form_text('name', '', $width = FALSE, lang('site_name'));
            $input['url'] = form_text('url', '', $width = FALSE, lang('site_url')); 
            $n = link_count();
            $arrlist = link_get($page, $n); 
            $pagination = pagination(url('link', $extra, TRUE), $n, $page, $pagesize); 
            $header['title'] = lang('friends_link');
            $header['mobile_title'] = lang('friends_link');
            $header['mobile_link'] = url('link', '', TRUE); 
            include _include(APP_PATH . 'admin/html/link.html'); 
        } elseif ('POST' == $method) { 
            $type = param('type', 0); 
            if (1 == $type) {
                $name = param('name');
                $name = filter_all_html($name);
                $url = param('url');
                FALSE === link_create(array('name' => $name, 'url' => $url, 'create_date' => $time)) and message(-1, lang('create_failed'));
                message(0, lang('create_successfully'));
            } elseif (2 == $type) {
                // 排序
                $arr = _POST('data');
                empty($arr) && message(1, lang('data_is_empty'));
                foreach ($arr as &$val) {
                    $rank = intval($val['rank']);
                    $id = intval($val['id']);
                    intval($val['oldrank']) != $rank && $id && link_update($id, array('rank' => $rank));
                }
                message(0, lang('update_successfully'));
            } else {
                $id = param('id', 0);
                FALSE === link_delete($id) and message(-1, lang('delete_failed'));
                message(0, lang('delete_successfully'));
            }
        }
?>