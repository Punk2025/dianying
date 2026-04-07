<?php
!defined('DEBUG') and exit('Access Denied.');
$action = param(1, 'list');
$header['title'] = lang('api_page');
switch ($action) {
    case 'rsync':
        if ('GET' == $method) {
            $from_code = param('code');
            $type = param('type');
            switch ($type) {
                //启动
                case 'start':
                    $res = rysnc_start($from_code)['msg'];
                    message(0, jump($res, url('api-list', array(), TRUE), 3));
                    break;
                //停止
                case 'stop':
                    $res = rysnc_stop($from_code)['msg'];
                    message(0, jump($res, url('api-list', array(), TRUE), 3));
                    break;
                //重启
                case 'restart':
                    $res = rysnc_restart($from_code)['msg'];
                    message(0, jump($res, url('api-list', array(), TRUE), 3));
                    break;
                case 'see':
                    $res = rysnc_info_code($from_code);

                    include _include(APP_PATH . 'admin/html/api_rsync.html');
                    break;
                //全部异步任务
                case 'seeall':
                    $res = rysnc_info($from_code);

                    include _include(APP_PATH . 'admin/html/api_rsync.html');
                    break;
                default:
                    break;
            }
        }
        break;
    case 'api':
        if ('GET' == $method) {
            $type = param('type', 'day');
            $code = param('code');
            $api = param('api');
            $p = param('p', 0);
            $t = '';
            switch ($type) {
                case 'day':
                    $h = '24';
                    $res = api_api($api, $code, $t, $p, $h);
                    $resstr = implode('<br>', $res);
                    if ($resstr == lang('api_ok')) {
                        message(0, jump(lang('api_ok'), url('api-list', array(), TRUE), 3));
                    }
                    $netxp = $p + 1;
                    $jump = url('api-api', array('api' => $api, 'p' => $netxp, 'h' => $h, 't' => $t, 'code' => $code, 'type' => $type), TRUE);
                    include _include(APP_PATH . 'admin/html/api_res.html');
                    break;
                case 'week':
                    $h = '168';
                    $res = api_api($api, $code, $t, $p, $h);
                    $resstr = implode('<br>', $res);
                    if ($resstr == lang('api_ok')) {
                        message(0, jump(lang('api_ok'), url('api-list', array(), TRUE), 3));
                    }
                    $netxp = $p + 1;
                    $jump = url('api-api', array('api' => $api, 'p' => $netxp, 'h' => $h, 't' => $t, 'code' => $code, 'type' => $type), TRUE);
                    include _include(APP_PATH . 'admin/html/api_res.html');
                    break;
                case 'all':
                    $h = '';
                    $res = api_api($api, $code, $t, $p, $h);
                    $resstr = implode('<br>', $res);
                    if ($resstr == lang('api_ok')) {
                        message(0, jump(lang('api_ok'), url('api-list', array(), TRUE), 3));
                    }
                    if ($resstr == lang('api_err')) {
                        cache_set($code . 'pg', $p);
                        $jump = url('api-api', array('api' => $api, 'p' => $p, 'h' => $h, 't' => $t, 'code' => $code, 'type' => $type), TRUE);
                        include _include(APP_PATH . 'admin/html/api_res.html');
                    } else {
                        $netxp = $p + 1;
                        cache_set($code . 'pg', $p);
                        $jump = url('api-api', array('api' => $api, 'p' => $netxp, 'h' => $h, 't' => $t, 'code' => $code, 'type' => $type), TRUE);
                        include _include(APP_PATH . 'admin/html/api_res.html');
                    }
                    break;
                default:
                    message(-1, lang('data_malformation'));
            }
        }
        break;
    case 'cate':
        if ('GET' == $method) {
            $code = $_GET['code']; 
            if (!isset($conf['api'][$code]['api'])) {
                echo json_encode(['status' => 'error', 'msg' => '采集源未配置']);
                exit;
            } 
            if (cache_get($code)) {
                $api_type = cache_get($code);
            } else {
                $api = $conf['api'][$code]['api'];
                $res = get_url($api);
                $api_type = xn_json_decode($res)['class'] ?? [];
                cache_set($code, $api_type, 3600);
            }
             
            if (!is_array($api_type)) {
                $api_type = [];
            } 
            $typelist = cate_list_cache();
            $cate = arrlist_key_values($typelist, 'name', 'cid');

            $res = [
                'status' => 'success',
                'api_type' => $api_type,       
                'cate' => $cate,                 
                'bind' => $conf['bind'][$code] ?? []  
            ];
            echo json_encode($res);
            exit;
        }  
        if ($method === 'POST') { 
            $code = param('code');
            $binstatus = param('binstatus'); 
            $name = param('type_name') ?: param('name');
            $local_id = param('local_id');

            if (empty($code) || empty($binstatus) || empty($name)) {
                echo json_encode(['success' => false, 'msg' => '参数不完整']);
                exit;
            }

            if ($binstatus === 'bind') {
                if (empty($local_id)) {
                    echo json_encode(['success' => false, 'msg' => '绑定ID不能为空']);
                    exit;
                }
                $conf['bind'][$code][$name] = $local_id; 
                $replace = array();
                $replace['bind'] = $conf['bind'];
                file_replace_var(APP_PATH . 'config/config.php', $replace);
            } elseif ($binstatus === 'unbind') {
                if (isset($conf['bind'][$code][$name])) {
                    unset($conf['bind'][$code][$name]); 
                    $replace = array();
                    $replace['bind'] = $conf['bind'];
                    file_replace_var(APP_PATH . 'config/config.php', $replace);
                }
            } 
            echo json_encode(['success' => true]);
            exit;
        }
        break;


    case 'fastapi':
         if ('GET' == $method) {
            $apistr = cache_get('code');
            if (empty($apistr)) {
                $apistr = get_code();
            }
            $apistr = core_decrypt($apistr);
            $arrlist = xn_json_decode($apistr)['list']; 
            include _include(APP_PATH . 'admin/html/api_fastapi.html'); 
        }
        break;
    case 'fastdb':
         
            $code = param('code'); 
            $limit = param('limit'); 
             $p = param('p', 0); 
            if ('POST' == $method) {
               fast_cat($code);
               $resstr='分类已导入';
               $jump = url('api-fastdb', array('code' => $code, 'p' => 1,'limit' => $limit), TRUE);
               include _include(APP_PATH . 'admin/html/api_fast_res.html'); 
            }elseif ('GET' == $method) {
                if($p>=1){
                   $nextp=$p+1;
                }
                fast_db($code,$nextp,$limit);
                $resstr='数据已导入-每页导入数据'.$limit.',即将开始第'.$nextp.'页';
                $jump = url('api-fastdb', array('code' => $code, 'p' => $nextp,'limit' => $limit), TRUE);
                include _include(APP_PATH . 'admin/html/api_fast_res.html');  
            }
 
        break;

    case 'list':
        if ('GET' == $method) {
            $apistr = cache_get('code');
            if (empty($apistr)) {
                $apistr = get_code();
            }
            $apistr = core_decrypt($apistr);
            $arrlist = xn_json_decode($apistr)['list']; 
            include _include(APP_PATH . 'admin/html/api_list.html');
        }
        break;
    case 'art_list':
        if ('GET' == $method) {
            $apistr = cache_get('code');
            if (empty($apistr)) {
                $apistr = get_code();
            }
            $apistr = core_decrypt($apistr);
            $arrlist = xn_json_decode($apistr)['artapi'];
            include _include(APP_PATH . 'admin/html/api_art_list.html');
        }
        break;
    case 'auto': 
            $api = param('api');
            $code = param('code');
            if (!isset($conf['api'][$code]['api'])) {
                message(0, jump('请先设置采集源', url('api-list', array(), TRUE), 3));
            }
            if (cache_get($code)) {
                $apiarr = cache_get($code);
            } else {
                $res = get_url($api);
                $apiarr = xn_json_decode($res)['class'];
                cache_set($code, $apiarr, 7200);
            }
            $catelist = cate_list_cache(); //本地分类
            $arrlista = arrlist_key_values($catelist, 'name', 'cid');
            $catelistarr = array();
            foreach ($apiarr as $key => $value) {
                $sitetype = $value['type_name'];
                if (!empty($arrlista[$sitetype])) {
                    $catelistarr[] = array(
                        'cid' => $arrlista[$sitetype],
                        'name' => $sitetype,
                    );
                }
            }
            $catelistarr = arrlist_key_values($catelistarr, 'name', 'cid');
            $apiconf = $conf['bind'];
            unset($apiconf[$code]);
            $apiconf[$code] = $catelistarr;
            $replace = array();
            $replace['bind'] = $apiconf;
            file_replace_var(APP_PATH . 'config/config.php', $replace);
            message(0, jump('绑定已有分类成功', url('api-list', array(), TRUE), 3));
        break;
    case 'newcreate':
         if ('POST' == $method) {
            $api = param('api', '');
            $code = param('code', '');
            $name = param('type_name');
            $name = filter_all_html($name);
            empty($name) and message(1, lang('data_empty_to_last_step'));
            $seo_des = param('seo_des', '', FALSE);
            $seo_des = xn_html_safe($seo_des);
            $nav_display = param('nav_display', 0);
            $model = param('model', 0);
            $seo_title = param('seo_title', '', FALSE);
            $seo_key = param('seo_key', '', FALSE);
            $category = param('category', 0);
            $alias = uniq_id();
            if (0 == $category) {
                $cup = _POST('fup', 0);
                $cup = intval($cup);
                $display = 0;
                $category = 1;
            } else {
                $cup = intval($category);
                $display = param('display', 0);
                $category = 0;
            }
            !preg_match('#^[\w]*$#i', $alias) and message(1, lang('alias_tips'));
            in_array($alias, $alias_reservation) and message('alias', lang('alias_system_reservation'));
            $aliaslist = cate_alias_cache();
            $alias && !empty($aliaslist) && isset($aliaslist[$alias]) and message('alias', lang('alias_duplicate'));

            $cate_arr = arrlist_key_values($catelist, 'name', 'cid');
            if (!isset($cate_arr[$name])) {
               $arr = array(
                'cup' => $cup,
                'type' => 1,
                'model' => $model,
                'category' => 0,
                'name' => $name,
                'create_date' => $time,
                'display' => $display,
                'nav_display' => $nav_display,
                'seo_title' => $seo_title,
                'seo_key' => $seo_key,
                'seo_des' => $seo_des,
                'create_date' => time(),
                'alias' => $alias,
            );
            $_fid = db_create("cate", $arr);
            $cup and db_update("cate", array('cid' => $cup), array('son+' => 1));
            cate_list_cache_delete(); 
            echo json_encode(['success' => true, 'msg' => '创建成功']);
            }else{
                echo json_encode(['success' => false, 'msg' => lang('cate_not_exist')]);
            }
            
            
        }

        break;
    case 'create':
        if ('POST' == $method) {
            $api = param('api');
            $code = param('code');
            $name = param('name');
            $name = filter_all_html($name);
            empty($name) and message(1, lang('data_empty_to_last_step'));
            $seo_des = param('seo_des', '', FALSE);
            $seo_des = xn_html_safe($seo_des);
            $nav_display = param('nav_display', 0);
            $model = param('model', 0);
            $seo_title = param('seo_title', '', FALSE);
            $seo_key = param('seo_key', '', FALSE);
            $category = param('category', 0);
            $alias = uniq_id();
            if (0 == $category) {
                $cup = _POST('fup', 0);
                $cup = intval($cup);
                $display = 0;
                $category = 1;
            } else {
                $cup = intval($category);
                $display = param('display', 0);
                $category = 0;
            }
            !preg_match('#^[\w]*$#i', $alias) and message(1, lang('alias_tips'));
            in_array($alias, $alias_reservation) and message('alias', lang('alias_system_reservation'));
            $aliaslist = cate_alias_cache();
            $alias && !empty($aliaslist) && isset($aliaslist[$alias]) and message('alias', lang('alias_duplicate'));
            $arr = array(
                'cup' => $cup,
                'type' => 1,
                'model' => $model,
                'category' => 0,
                'name' => $name,
                'create_date' => $time,
                'display' => $display,
                'nav_display' => $nav_display,
                'seo_title' => $seo_title,
                'seo_key' => $seo_key,
                'seo_des' => $seo_des,
                'create_date' => time(),
                'alias' => $alias,
            );
            $_fid = db_create("cate", $arr);
            $cup and db_update("cate", array('cid' => $cup), array('son+' => 1));
            cate_list_cache_delete();
            if (cache_get($code)) {
                $apiarr = cache_get($code);
            } else {
                $res = get_url($api);
                $apiarr = xn_json_decode($res)['class'];
                cache_set($code, $apiarr, 3600);
            }
            $catelist = cate_list_cache(); //本地分类
            $arrlista = arrlist_key_values($catelist, 'name', 'cid');
            $catelistarr = array();
            foreach ($apiarr as $key => $value) {
                $sitetype = $value['type_name'];
                if (!empty($arrlista[$sitetype])) {
                    $catelistarr[] = array(
                        'cid' => $arrlista[$sitetype],
                        'name' => $sitetype,
                    );
                }
            }
            $catelistarr = arrlist_key_values($catelistarr, 'name', 'cid');
            $apiconf = $conf['bind'];
            unset($apiconf[$code]);
            $apiconf[$code] = $catelistarr;
            $replace = array();
            $replace['bind'] = $apiconf;
            file_replace_var(APP_PATH . 'config/config.php', $replace);
            message(0, jump('分类创建成功', url('api-list', array(), TRUE), 3));
        }
        break;
    case 'set':
         if ('POST' == $method) {
            $api = param('api');
            $code = param('code');
            $name = param('name');
            $play = param('play');
            $arr = $conf['api'];
            $arr[$code] = array(
                'api' => $api,
                'code' => $code,
            );
            $replace['api'] = $arr;
            $vodplayer = $conf['vodplayer'];
            unset($vodplayer[$code]);
            $vodplayer[$code] = array(
                'status' => '1',
                'from' => $code,
                'show' => $name,
                'des' => $name,
                'target' => '_self',
                'ps' => '1',
                'parse' => $play,
                'sort' => '1000',
                'tip' => '无需安装任何插件',
            );
            $replace['vodplayer'] = $vodplayer;
            file_replace_var(APP_PATH . 'config/config.php', $replace);
            message(0, jump('设置采集源、播放器成功', url('api-auto', array('api'=>$api,'code'=>$code), TRUE), 1));
        }
        break;
       /* if ('POST' == $method) {
            $api = param('api');
            $code = param('code');
            $name = param('name');
            $play = param('play');
            $arr = $conf['api'];
            $arr[$code] = array(
                'api' => $api,
                'code' => $code,
            );
            $replace['api'] = $arr;
            $vodplayer = $conf['vodplayer'];
            unset($vodplayer[$code]);
            $vodplayer[$code] = array(
                'status' => '1',
                'from' => $code,
                'show' => $name,
                'des' => $name,
                'target' => '_self',
                'ps' => '1',
                'parse' => $play,
                'sort' => '1000',
                'tip' => '无需安装任何插件',
            );
            $replace['vodplayer'] = $vodplayer;
            file_replace_var(APP_PATH . 'config/config.php', $replace);
            if (cache_get($code)) {
                $apiarr = cache_get($code);
            } else {
                $res = get_url($api);
                $apiarr = xn_json_decode($res)['class'];
                cache_set($code, $apiarr, 7200);
            }
            $catelist = cate_list_cache(); //本地分类
            $arrlista = arrlist_key_values($catelist, 'name', 'cid');
            $catelistarr = array();
            foreach ($apiarr as $key => $value) {
                $sitetype = $value['type_name'];
                if (!empty($arrlista[$sitetype])) {
                    $catelistarr[] = array(
                        'cid' => $arrlista[$sitetype],
                        'name' => $sitetype,
                    );
                }
            }
            $catelistarr = arrlist_key_values($catelistarr, 'name', 'cid');
            $apiconf = $conf['bind'];
            unset($apiconf[$code]);
            $apiconf[$code] = $catelistarr;
            $replace = array();
            $replace['bind'] = $apiconf;
            file_replace_var(APP_PATH . 'config/config.php', $replace);
            message(0, jump('设置采集源、播放器、自动绑定分类成功', url('api-list', array(), TRUE), 3));
        }
        break;*/
    case 'set_art':
        if ('POST' == $method) {
            $api = param('api');
            $code = param('code');
            $name = param('name');
            $arr = $conf['api'];
            $arr[$code] = array(
                'api' => $api,
                'code' => $code,
            );
            $replace['api'] = $arr;
            file_replace_var(APP_PATH . 'config/config.php', $replace);
            message(0, jump('设置采集源成功', url('api-art_list', array(), TRUE), 3));
        }
        break;

    case 'setdel':
        if ('POST' == $method) {
            $code = param('code');
            $arr = $conf['api'];
            unset($arr[$code]);
            $bind = $conf['bind'];
            unset($bind[$code]);
            $replace['api'] = $arr;
            $replace['bind'] = $bind;
            file_replace_var(APP_PATH . 'config/config.php', $replace);
            message(0, jump('移除采集源成功', url('api-list', array(), TRUE), 3));
        }
        break;
    case 'del':
        db_truncate('cate');
        cate_list_cache_delete();
        message(0, jump('清空分类成功', url('api-list', array(), TRUE), 3));
        break;
    case 'intov':
        auto_into('v');
        message(0, jump('导入影视分类成功', url('api-list', array(), TRUE), 3));
        break;
    case 'intox':
        auto_into('x');
        message(0, jump('导入X影视分类成功', url('api-list', array(), TRUE), 3));
        break;
    case 'into_av':
        auto_into('av');
        message(0, jump('导入X资讯分类成功', url('api-art_list', array(), TRUE), 3));
        break;
    case 'into_art':
        auto_into('art');
        message(0, jump('导入影视资讯分类成功', url('api-art_list', array(), TRUE), 3));
        break;
    case 'art_auto':
        if ('POST' == $method) {
            $api = param('api');
            $code = param('code');
            if (!isset($conf['api'][$code]['api'])) {
                message(0, jump('请先设置采集源', url('api-art_list', array(), TRUE), 3));
            }
            if (cache_get($code)) {
                $apiarr = cache_get($code);
            } else {
                $res = get_url($api);
                $apiarr = xn_json_decode($res)['class'];
                cache_set($code, $apiarr, 3600);
            }
            $catelist = cate_list_cache(); //本地分类
            $arrlista = arrlist_key_values($catelist, 'name', 'cid');
            $catelistarr = array();
            foreach ($apiarr as $key => $value) {
                $sitetype = $value['type_name'];
                if (!empty($arrlista[$sitetype])) {
                    $catelistarr[] = array(
                        'cid' => $arrlista[$sitetype],
                        'name' => $sitetype,
                    );
                }
            }
            $catelistarr = arrlist_key_values($catelistarr, 'name', 'cid');
            $apiconf = $conf['bind'];
            unset($apiconf[$code]);
            $apiconf[$code] = $catelistarr;
            $replace = array();
            $replace['bind'] = $apiconf;
            file_replace_var(APP_PATH . 'config/config.php', $replace);
            message(0, jump('绑定已有分类成功', url('api-art_list', array(), TRUE), 3));
        }
        break;
    case 'art_create':
        if ('POST' == $method) {
            $api = param('api');
            $code = param('code');
            $name = param('name');
            $name = filter_all_html($name);
            empty($name) and message(1, lang('data_empty_to_last_step'));
            $seo_des = param('seo_des', '', FALSE);
            $seo_des = xn_html_safe($seo_des);
            $nav_display = param('nav_display', 0);
            $model = param('model', 0);
            $seo_title = param('seo_title', '', FALSE);
            $seo_key = param('seo_key', '', FALSE);
            $category = param('category', 0);
            $alias = uniq_id();
            if (0 == $category) {
                $cup = _POST('fup', 0);
                $cup = intval($cup);
                $display = 0;
                $category = 1;
            } else {
                $cup = intval($category);
                $display = param('display', 0);
                $category = 0;
            }
            !preg_match('#^[\w]*$#i', $alias) and message(1, lang('alias_tips'));
            in_array($alias, $alias_reservation) and message('alias', lang('alias_system_reservation'));
            $aliaslist = cate_alias_cache();
            $alias && !empty($aliaslist) && isset($aliaslist[$alias]) and message('alias', lang('alias_duplicate'));
            $arr = array(
                'cup' => $cup,
                'type' => 1,
                'model' => $model,
                'category' => 0,
                'name' => $name,
                'create_date' => $time,
                'display' => $display,
                'nav_display' => $nav_display,
                'seo_title' => $seo_title,
                'seo_key' => $seo_key,
                'seo_des' => $seo_des,
                'create_date' => time(),
                'alias' => $alias,
            );
            $_fid = db_create("cate", $arr);
            $cup and db_update("cate", array('cid' => $cup), array('son+' => 1));
            cate_list_cache_delete();
            if (cache_get($code)) {
                $apiarr = cache_get($code);
            } else {
                $res = get_url($api);
                $apiarr = xn_json_decode($res)['class'];
                cache_set($code, $apiarr, 3600);
            }
            $catelist = cate_list_cache(); //本地分类
            $arrlista = arrlist_key_values($catelist, 'name', 'cid');
            $catelistarr = array();
            foreach ($apiarr as $key => $value) {
                $sitetype = $value['type_name'];
                if (!empty($arrlista[$sitetype])) {
                    $catelistarr[] = array(
                        'cid' => $arrlista[$sitetype],
                        'name' => $sitetype,
                    );
                }
            }
            $catelistarr = arrlist_key_values($catelistarr, 'name', 'cid');
            $apiconf = $conf['bind'];
            unset($apiconf[$code]);
            $apiconf[$code] = $catelistarr;
            $replace = array();
            $replace['bind'] = $apiconf;

            file_replace_var(APP_PATH . 'config/config.php', $replace);
            message(0, jump('分类创建成功', url('api-art_list', array(), TRUE), 3));
        }
        break;
    case 'art_del':
        if ('POST' == $method) {
            $code = param('code');
            $arr = $conf['api'];
            unset($arr[$code]);
            $bind = $conf['bind'];
            unset($bind[$code]);
            $replace['api'] = $arr;
            $replace['bind'] = $bind;
            file_replace_var(APP_PATH . 'config/config.php', $replace);
            message(0, jump('移除采集源成功', url('api-art_list', array(), TRUE), 3));
        }
        break;
    case 'art_api':
        if ('GET' == $method) {
            $type = param('type', 'day');
            $code = param('code');
            $api = param('api');
            $p = param('p', 0);
            $t = '';
            switch ($type) {
                case 'day':
                    $h = '24';
                    $res = art_api($api, $code, $t, $p, $h);
                    $resstr = implode('<br>', $res);
                    if ($resstr == lang('api_ok')) {
                        message(0, jump(lang('api_ok'), url('api-art_list', array(), TRUE), 3));
                    }
                    $netxp = $p + 1;
                    $jump = url('api-art_api', array('api' => $api, 'p' => $netxp, 'h' => $h, 't' => $t, 'code' => $code, 'type' => $type), TRUE);
                    include _include(APP_PATH . 'admin/html/api_res.html');
                    break;
                case 'week':
                    $h = '168';
                    $res = art_api($api, $code, $t, $p, $h);
                    $resstr = implode('<br>', $res);
                    if ($resstr == lang('api_ok')) {
                        message(0, jump(lang('api_ok'), url('api-art_list', array(), TRUE), 3));
                    }
                    $netxp = $p + 1;
                    $jump = url('api-art_api', array('api' => $api, 'p' => $netxp, 'h' => $h, 't' => $t, 'code' => $code, 'type' => $type), TRUE);
                    include _include(APP_PATH . 'admin/html/api_res.html');
                    break;
                case 'all':
                    $h = '';
                    $res = art_api($api, $code, $t, $p, $h);
                    $resstr = implode('<br>', $res);
                    if ($resstr == lang('api_ok')) {
                        message(0, jump(lang('api_ok'), url('api-art_list', array(), TRUE), 3));
                    }
                    if ($resstr == lang('api_err')) {
                        cache_set($code . 'artpg', $p);
                        $jump = url('api-art_api', array('api' => $api, 'p' => $p, 'h' => $h, 't' => $t, 'code' => $code, 'type' => $type), TRUE);
                        include _include(APP_PATH . 'admin/html/api_res.html');
                    } else {
                        $netxp = $p + 1;
                        cache_set($code . 'artpg', $p);
                        $jump = url('api-art_api', array('api' => $api, 'p' => $netxp, 'h' => $h, 't' => $t, 'code' => $code, 'type' => $type), TRUE);
                        include _include(APP_PATH . 'admin/html/api_res.html');
                    }
                    break;
                default:
                    message(-1, lang('data_malformation'));
            }
        }
        break;
    default:
        message(-1, lang('data_malformation'));
        break;
}
?>