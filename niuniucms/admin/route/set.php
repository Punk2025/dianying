<?php
!defined('DEBUG') and exit('Access Denied.');
$action = param(1);
switch ($action) {
    case 'logo_upload':
        if ('POST' !== $method) {
            message(4, lang('method_error'));
        }
        empty($_FILES['logo']) && message(1, '请选择图片');
        $f = $_FILES['logo'];
        if (empty($f['tmp_name']) || !is_uploaded_file($f['tmp_name'])) {
            message(1, '上传无效');
        }
        if (isset($f['error']) && (int) $f['error'] !== UPLOAD_ERR_OK) {
            message(1, '上传失败');
        }
        $max_bytes = 2097152;
        if (isset($f['size']) && (int) $f['size'] > $max_bytes) {
            message(1, '图片不能超过 2MB');
        }
        $imginfo = @getimagesize($f['tmp_name']);
        if ($imginfo === false) {
            message(1, '不是有效的图片文件');
        }
        $mime_map = array(
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG => 'png',
            IMAGETYPE_GIF => 'gif',
            IMAGETYPE_WEBP => 'webp',
        );
        if (!isset($mime_map[$imginfo[2]])) {
            message(1, '仅支持 jpg、png、gif、webp');
        }
        $ext = $mime_map[$imginfo[2]];
        if (function_exists('finfo_open')) {
            $fi = finfo_open(FILEINFO_MIME_TYPE);
            if ($fi) {
                $mime = finfo_file($fi, $f['tmp_name']);
                finfo_close($fi);
                $mime_ok = array('image/jpeg', 'image/png', 'image/gif', 'image/webp');
                if ($mime && !in_array($mime, $mime_ok, true)) {
                    message(1, '文件类型校验未通过');
                }
            }
        }
        $subdir = 'site_logo/' . date('Ym') . '/';
        $base = rtrim(str_replace('\\', '/', $conf['upload_path']), '/');
        $dest_dir = $base . '/' . $subdir;
        if (!is_dir($dest_dir) && !@mkdir($dest_dir, 0755, true)) {
            message(-1, '无法创建上传目录');
        }
        $basename = 'logo_' . date('YmdHis') . '_' . substr(md5(uniqid((string) mt_rand(), true)), 0, 8) . '.' . $ext;
        $dest = $dest_dir . $basename;
        if (!move_upload_file($f['tmp_name'], $dest)) {
            message(-1, '保存文件失败');
        }
        $pub = rtrim(str_replace('\\', '/', $conf['upload_url']), '/') . '/' . $subdir . $basename;
        message(0, '上传成功', array('url' => $pub));
        break;
    case 'mobile_qr_upload':
        if ('POST' !== $method) {
            message(4, lang('method_error'));
        }
        empty($_FILES['mobile_qr']) && message(1, '请选择图片');
        $f = $_FILES['mobile_qr'];
        if (empty($f['tmp_name']) || !is_uploaded_file($f['tmp_name'])) {
            message(1, '上传无效');
        }
        if (isset($f['error']) && (int) $f['error'] !== UPLOAD_ERR_OK) {
            message(1, '上传失败');
        }
        $max_bytes = 2097152;
        if (isset($f['size']) && (int) $f['size'] > $max_bytes) {
            message(1, '图片不能超过 2MB');
        }
        $imginfo = @getimagesize($f['tmp_name']);
        if ($imginfo === false) {
            message(1, '不是有效的图片文件');
        }
        $mime_map = array(
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG => 'png',
            IMAGETYPE_GIF => 'gif',
            IMAGETYPE_WEBP => 'webp',
        );
        if (!isset($mime_map[$imginfo[2]])) {
            message(1, '仅支持 jpg、png、gif、webp');
        }
        $ext = $mime_map[$imginfo[2]];
        if (function_exists('finfo_open')) {
            $fi = finfo_open(FILEINFO_MIME_TYPE);
            if ($fi) {
                $mime = finfo_file($fi, $f['tmp_name']);
                finfo_close($fi);
                $mime_ok = array('image/jpeg', 'image/png', 'image/gif', 'image/webp');
                if ($mime && !in_array($mime, $mime_ok, true)) {
                    message(1, '文件类型校验未通过');
                }
            }
        }
        $subdir = 'mobile_qr/' . date('Ym') . '/';
        $base = rtrim(str_replace('\\', '/', $conf['upload_path']), '/');
        $dest_dir = $base . '/' . $subdir;
        if (!is_dir($dest_dir) && !@mkdir($dest_dir, 0755, true)) {
            message(-1, '无法创建上传目录');
        }
        $basename = 'mqr_' . date('YmdHis') . '_' . substr(md5(uniqid((string) mt_rand(), true)), 0, 8) . '.' . $ext;
        $dest = $dest_dir . $basename;
        if (!move_upload_file($f['tmp_name'], $dest)) {
            message(-1, '保存文件失败');
        }
        $pub = rtrim(str_replace('\\', '/', $conf['upload_url']), '/') . '/' . $subdir . $basename;
        message(0, '上传成功', array('url' => $pub));
        break;
    case 'set':
        $setting = array_value($config, 'setting');
        if ('GET' == $method) {
            $input = array();
            $input['sitename'] = form_text('sitename', $conf['sitename']);
            $input['logo_pc'] = form_text('logo_pc', array_value($conf, 'logo_pc', ''), '100%', '/upload/logo.png 或完整图片 URL');
            $input['logo_m'] = form_text('logo_m', array_value($conf, 'logo_m', ''), '100%', '可选，留空同电脑端');
            $input['mobile_watch_qr'] = form_text('mobile_watch_qr', array_value($conf, 'mobile_watch_qr', ''), '100%', '/upload/... 或完整 URL，留空用模板默认图');
            $input['pagesize'] = form_text('pagesize', $conf['pagesize']);
            $input['postlist_pagesize'] = form_text('postlist_pagesize', $conf['postlist_pagesize']);
            $input['listsize'] = form_text('listsize', $conf['listsize']);
            $input['linksize'] = form_text('linksize', $conf['linksize']);
            $input['tagsize'] = form_text('tagsize', $conf['tagsize']);
            $input['tag_num'] = form_text('tag_num', $conf['tag_num'] ?? 5);
            $input['cdn_on'] = form_radio('cdn_on', array('1'=>"开",'0'=>"关"), array_value($conf, 'cdn_on', 0));
            $input['api_on'] = form_radio('api_on', array('1'=>"开",'0'=>"关"), array_value($conf, 'api_on', 0));
            $input['search_on'] = form_radio('search_on', array('0'=>"开",'1'=>"关"), array_value($conf, 'search_on', 0));  
            $input['sitemap_tag'] = form_radio('sitemap_tag', array('1'=>"开",'0'=>"关"), array_value($conf, 'sitemap_tag', 0)); 
            $input['sitemap_play'] = form_radio('sitemap_play', array('1'=>"开",'0'=>"关"), array_value($conf, 'sitemap_play', 0)); 
            $input['sitemap_num'] = form_text('sitemap_num', $conf['sitemap_num']);
            $input['down_pic'] = form_radio_yes_no('down_pic',  $conf['down_pic']);
            $input['area'] = form_text('area', $conf['area']);
            $input['lang'] = form_text('lang', $conf['lang']);
            $input['year'] = form_text('year', $conf['year']);
            $input['agg'] = form_textarea('agg', $conf['agg'], '', '100%', 100);
            $input['tg'] = form_text('tg', $conf['tg']);
            $input['email'] = form_text('email', $conf['email']);
            $tpl_modearr = array('0' => lang('mode_0'), '1' => lang('mode_1'), '2' => lang('mode_2'));
            $input['tpl_mode'] = form_radio('tpl_mode', $tpl_modearr, array_value($setting, 'tpl_mode', 0));
            $header['title'] = lang('set_web');
            include _include(APP_PATH . 'admin/html/set.html');
        } elseif ('POST' == $method) {
            $sitename = param('sitename');
            $logo_pc = trim((string) param('logo_pc', ''));
            $logo_m = trim((string) param('logo_m', ''));
            $mobile_watch_qr = trim((string) param('mobile_watch_qr', ''));
            $pagesize = param('pagesize');
            $postlist_pagesize = param('postlist_pagesize');
            $listsize = param('listsize');
            $linksize = param('linksize');
            $tagsize = param('tagsize');
            $tag_num = param('tag_num');
            $cdn_on = param('cdn_on');
            $api_on = param('api_on');
            $search_on = param('search_on');
            $sitemap_tag = param('sitemap_tag');
            $sitemap_play = param('sitemap_play');
            $sitemap_num = param('sitemap_num');
            $down_pic = param('down_pic');
            $area = param('area');
            $lang = param('lang');
            $year = param('year');
            $agg = param('agg');
            $tg = param('tg');
            $email = param('email');
            $tpl_mode = param('tpl_mode', 0);
            $website_mode = param('website_mode', 0);
            $replace = array();
            $replace['sitename'] = xn_html_safe(filter_all_html($sitename));
            $replace['logo_pc'] = strip_tags(trim(stripslashes($logo_pc)));
            $replace['logo_m'] = strip_tags(trim(stripslashes($logo_m)));
            $replace['mobile_watch_qr'] = strip_tags(trim(stripslashes($mobile_watch_qr)));
            $replace['pagesize'] = intval($pagesize);
            $replace['postlist_pagesize'] = intval($postlist_pagesize);
            $replace['listsize'] = intval($listsize);
            $replace['linksize'] = intval($linksize);
            $replace['tagsize'] = intval($tagsize);
            $replace['tag_num'] = intval($tag_num);
            $replace['cdn_on'] = intval($cdn_on);
            $replace['api_on'] = intval($api_on);
            $replace['search_on'] = intval($search_on);
            $replace['sitemap_tag'] = intval($sitemap_tag);
            $replace['sitemap_play'] = intval($sitemap_play);
            $replace['sitemap_num'] = intval($sitemap_num);
            $replace['down_pic'] = intval($down_pic);
            $replace['area'] = xn_html_safe(filter_all_html($area));
            $replace['lang'] = xn_html_safe(filter_all_html($lang));
            $replace['year'] = xn_html_safe(filter_all_html($year));
            $replace['agg'] = html_entity_decode($agg, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $replace['tg'] = $tg;
            $replace['email'] = $email;
            file_replace_var(APP_PATH . 'config/config.php', $replace);
            $setting['website_mode'] = $website_mode;
            $setting['tpl_mode'] = $tpl_mode;
            $config['setting'] = $setting;
            setting_set('conf', $config);
            if (function_exists('view_clear_compiled_theme_html')) {
                view_clear_compiled_theme_html(isset($config['theme']) ? $config['theme'] : 'default');
            }
            message(0, lang('modify_successfully'));
        }
        break;
    case 'route':
        if ('GET' == $method) {
            $arrlist = $conf['route'];
            $header['title'] = lang('route_set');
            include _include(APP_PATH . 'admin/html/set_route.html');
        } elseif ('POST' == $method) {
            $arr = _POST('data');
            empty($arr) && message(1, lang('data_is_empty'));
            $arrs = array_filter($arr);
            $reparr['route'] = arrlist_key_values($arrs, 'id', 'rank');
            file_replace_var(APP_PATH . 'config/config.php', $reparr);
            message(0, lang('route_res'));
        }
        break;
    case 'player_add':
        if ('POST' == $method) {
            $player_name = param('player_name');
            $player_url = param('player_url');
            $player_rank = param('player_rank', 0);
            $player_code = param('player_code');
            empty($player_code) && message(1, lang('player_code_is_empty'));
            empty($player_url) && message(1, lang('player_url_is_empty'));
            empty($player_name) && message(1, lang('player_name_is_empty'));
            $arr = $conf['vodplayer'];
            $arr[$player_code] = array(
                'status' => '1',
                'from' => $player_code,
                'show' => $player_name,
                'des' => $player_name,
                'target' => '_self',
                'ps' => '1',
                'parse' => $player_url,
                'sort' => $player_rank,
                'tip' => '无需安装任何插件',
            );
            $replace['vodplayer'] = $arr;
            file_replace_var(APP_PATH . 'config/config.php', $replace);
            message(0, jump(lang('player_seting'), url('set-player', array(), TRUE), 3));
        }
        break;
    case 'player':
        if ('GET' == $method) {
            $arrlist = $conf['vodplayer'];
            $header['title'] = lang('player_set');
            include _include(APP_PATH . 'admin/html/set_player.html');
        } elseif ('POST' == $method) {
            $type = param('type', 0);
            if (1 == $type) {
                $name = param('name');
                $name = filter_all_html($name);
                $url = param('url');
                message(0, lang('create_successfully'));
            } elseif (2 == $type) {
                //排序
                $arr = _POST('data');
                empty($arr) && message(1, lang('data_is_empty'));
                $arrs = array_filter($arr);
                foreach ($arrs as &$val) {
                    $rank = intval($val['rank']);
                    $id = $val['id'];
                    $arr = $conf['vodplayer'];
                    $arr[$id]['sort'] = $rank;
                    $replace['vodplayer'] = $arr;
                    file_replace_var(APP_PATH . 'config/config.php', $replace);
                }
                message(0, lang('update_successfully'));
            } else {
                $arr = $conf['vodplayer'];
                $from = param('from');
                unset($arr[$from]);
                $replace['vodplayer'] = $arr;
                file_replace_var(APP_PATH . 'config/config.php', $replace);
                message(0, lang('delete_successfully'));
            }
        }
        break;
        case 'danmu':
            if ('GET' == $method) {
                $header['title'] = lang('danmu_set');
                $input['on'] = form_radio('on', array('0' => '开启', '1' => '关闭'), array_value($conf['danmu'], 'on', 0));
                $input['cdn'] = form_radio('cdn', array('0' => '开启', '1' => '关闭'), array_value($conf['danmu'], 'cdn', 0));
                $input['start_ad'] = form_radio('start_ad', array('0' => '图片', '1' => '视频'), array_value($conf['danmu'], 'start_ad', 0));
                $input['danmu_toggle'] = form_radio('danmu_toggle', array('0' => '开启', '1' => '关闭'), array_value($conf['danmu'], 'danmu_toggle', 0));
                $input['pause_ad'] = form_radio('pause_ad', array('0' => '开启', '1' => '关闭'), array_value($conf['danmu'], 'pause_ad', 0));
                $input['selec_col'] = form_radio('selec_col', array('0' => '开启', '1' => '关闭'), array_value($conf['danmu'], 'selec_col', 0));
                $input['start_ad_on'] = form_radio('start_ad_on', array('0' => '开启', '1' => '关闭'), array_value($conf['danmu'], 'start_ad_on', 0));

                //$input['listsize'] = form_text('listsize', $conf['listsize']);
                $input['start_img_ad'] = form_text('start_img_ad',$conf['danmu']['start_img_ad']); 
                $input['start_mp4_ad'] = form_text('start_mp4_ad',$conf['danmu']['start_mp4_ad']);
                $input['start_ad_url'] = form_text('start_ad_url',$conf['danmu']['start_ad_url']);
                $input['start_all_time'] = form_text('start_all_time',$conf['danmu']['start_all_time']);
                $input['start_close_time'] = form_text('start_close_time',$conf['danmu']['start_close_time']);
                $input['stop_ad'] = form_text('stop_ad',$conf['danmu']['stop_ad']);
                $input['stop_ad_url'] = form_text('stop_ad_url',$conf['danmu']['stop_ad_url']); 

                include _include(APP_PATH . 'admin/html/set_danmu.html');
            } elseif ('POST' == $method) {
                $on = param('on', 0);
                $cdn = param('cdn', 0);
                $start_ad = param('start_ad', 0);
                $danmu_toggle = param('danmu_toggle', 0);
                $pause_ad = param('pause_ad', 0);
                $selec_col = param('selec_col', 0);
                $start_ad_on = param('start_ad_on', 0);
                $start_img_ad = param('start_img_ad');
                $start_mp4_ad = param('start_mp4_ad');
                $start_ad_url = param('start_ad_url');
                $start_all_time = param('start_all_time', 10);
                $start_close_time = param('start_close_time', 5);
                $stop_ad = param('stop_ad');
                $stop_ad_url = param('stop_ad_url');
                $replace = array();
                $replace['danmu'] = array(
                    'on' => $on,
                    'cdn' => $cdn,
                    'start_ad' => $start_ad,
                    'danmu_toggle' => $danmu_toggle,
                    'pause_ad' => $pause_ad,
                    'selec_col' => $selec_col,
                    'start_ad_on' => $start_ad_on,
                    'start_img_ad' => $start_img_ad,
                    'start_mp4_ad' => $start_mp4_ad,
                    'start_ad_url' => $start_ad_url,
                    'start_all_time' => $start_all_time,
                    'start_close_time' => $start_close_time,
                    'stop_ad' => $stop_ad,
                    'stop_ad_url' => $stop_ad_url,
                ); 
                file_replace_var(APP_PATH . 'config/config.php', $replace);
                message(0, jump(lang('modify_successfully'), url('set-danmu', array(), TRUE), 3));
            }
            break;
    case 'op':
        if ('GET' == $method) {
            $cache = array('mysql' => 'mysql', 'yac' => 'yac', 'apc' => 'apc', 'xcache' => 'xcache', 'redis' => 'redis', 'memcached' => 'memcached');
            $input['cache'] = form_radio('cache', $cache, array_value($conf['cache'], 'type', 0));
            $header['title'] = lang('set_op');
            include _include(APP_PATH . 'admin/html/set_op.html');
        } elseif ('POST' == $method) {
            $cache = param('cache', 'mysql');
            $replace = array();
            $old = $conf['cache'];
            $old['type'] = $cache;
            $replace['cache'] = $old;
            file_replace_var(APP_PATH . 'config/config.php', $replace);
            message(0, lang('modify_successfully'));
        }
        break;
    case 'rw':
        if ('GET' == $method) {
            $rewrite = array('0' => '默认', '1' => '伪静态1', '2' => '伪静态2', '3' => '伪静态3');
            $input['rewrite'] = form_radio('rewrite', $rewrite, array_value($conf, 'url_rewrite_on', 0));
            $header['title'] = lang('set_rw');
            include _include(APP_PATH . 'admin/html/set_rw.html');
        } elseif ('POST' == $method) {
            $rewrite = param('rewrite', 0);
            $replace = array();
            $replace['url_rewrite_on'] = $rewrite;
            if ($rewrite == 0 || $rewrite == 1) {
                $replace['path'] = './';
                $replace['cookie_path'] = '';
            }
            if ($rewrite == 2 || $rewrite == 3) {
                $replace['path'] = '/';
                $replace['cookie_path'] = '/';
            }
            file_replace_var(APP_PATH . 'config/config.php', $replace);
            message(0, lang('modify_successfully'));
        }
        break;
    case 'cache':
        if ('GET' == $method) {
            $input = array();
            $input['clear_tmp'] = form_checkbox('clear_tmp', 1);
            $input['clear_cache'] = form_checkbox('clear_cache');
            $header['title'] = lang('set_cache');
            include _include(APP_PATH . 'admin/html/set_cache.html');
        } elseif ('POST' == $method) {
            $clear_tmp = param('clear_tmp');
            $clear_cache = param('clear_cache');
            art_del();
            vod_del();
            $clear_cache and cache_truncate();
            $clear_cache and $runtime = NULL; // 清空
            $g_website = kv_cache_get('website');
            $g_website['grouplist'] = '';
            kv_cache_set('website', $g_website);
            $clear_tmp and rmdir_recusive($conf['cache_path'], 1);
            message(0, lang('admin_clear_successfully'));
        }
        break;
    case 'upgrade':
        if ('GET' == $method) {
            $header['title'] = lang('online_upgrade');
            $type = param('type', 0);
            if (1 == $type) {
                $down_path = $rescode['down_path'] . $cloud_version . '.zip';
                update_down($down_path, $cloud_version);
                $clear_tmp = $clear_cache = 1;
                $clear_cache and cache_truncate();
                $clear_cache and $runtime = NULL; // 清空
                $g_website = kv_cache_get('website');
                $g_website['grouplist'] = '';
                kv_cache_set('website', $g_website);
                $clear_tmp and rmdir_recusive($conf['cache_path'], 1);
                message(0, jump('更新版本成功', url('set-upgrade', array(), TRUE), 3));
            } else {
                include _include(APP_PATH . 'admin/html/set_upgrade.html');
            }
        }
        break;
    case 'seo':
        if ('GET' == $method) {
            $header['title'] = lang('seo');
            include _include(APP_PATH . 'admin/html/set_seo.html');
        } elseif ('POST' == $method) {
            $page = param('page', '');
            $seo_title = param('seo_title', '');
            $seo_key = param('seo_key', '');
            $seo_des = param('seo_des', '');
            $replace = array();
            $old = $conf['seo'];
            $old[$page] =  array(
                'seo_title' => $seo_title,
                'seo_des' => $seo_des,
                'seo_key' => $seo_key,
            );;
            $replace['seo'] = $old;
            file_replace_var(APP_PATH . 'config/config.php', $replace);
            message(0, lang('modify_successfully'));
        }
        break;
    case 'rep':
        if ('GET' == $method) {
            $header['title'] = lang('rep');
            include _include(APP_PATH . 'admin/html/set_rep.html');
        }
        break;
    case 'rm':
        if ('GET' == $method) {
            $header['title'] = '清空所有数据';
            $input = array();
            $input['clear_data'] = form_checkbox('clear_data', 1);
            include _include(APP_PATH . 'admin/html/set_rm.html');
        } elseif ('POST' == $method) {
            $clear_tmp = $clear_data = $clear_cache = param('clear_data');
            db_truncate('art');
            db_truncate('art_aid');
            db_truncate('cache');
            db_truncate('cate');
            db_truncate('link');
            db_truncate('session');
            db_truncate('session_data');
            db_truncate('tag');
            db_truncate('tag_id');
            db_truncate('vod');
            db_truncate('vod_sticky');
            db_truncate('vod_vid');
            db_truncate('vod_top');
            db_truncate('vod_top_ip');
            db_truncate('art_top');
            db_truncate('art_top_ip');
            $replace = array();
            $old =  array();
            $replace['vodplayer'] = $old;
            $replace['api'] = $old;
            $replace['bind'] = $old;
            $replace['tg'] = '';
            $replace['email'] = '';
            $replace['agg'] = '';
            file_replace_var(APP_PATH . 'config/config.php', $replace);
            $clear_cache and cache_truncate();
            $clear_cache and $runtime = NULL; // 清空
            $g_website = kv_cache_get('website');
            $g_website['grouplist'] = '';
            kv_cache_set('website', $g_website);
            $clear_tmp and rmdir_recusive($conf['cache_path'], 1);
            message(0, '数据清理成功');
        }
        break;
      //站群模式 
    case 'domain':
        if ('GET' == $method) {
            $header['title'] = '站群设置';
            tmpl_init();
            $cond = array();
            $tmpllist = tmpl_list($cond, $orderby = array(), 1, 500, 2);
            $rewrite = array('0' => '默认', '1' => '伪静态1', '2' => '伪静态2', '3' => '伪静态3');
            $site = $domain['site'];
            include _include(APP_PATH . 'admin/html/set_domain.html');
        } elseif ('POST' == $method) {
            $site_name    = param('site_name', ''); 
            $site_domain  = get_domain(trim(param('site_domain', '')));
            $path         = param('path', '');
            $site_rewrite = intval(param('site_rewrite', 0));
            $old_domain   = trim(param('old_site_domain', ''));

            if ($site_domain == '') {
                message(1, '域名不能为空');
            }

            $site = isset($domain['site']) && is_array($domain['site']) ? $domain['site'] : [];
            if ($old_domain && $old_domain !== $site_domain && isset($site[$old_domain])) {
                unset($site[$old_domain]);
            }
            $data = [
                'site_name'    => $site_name, 
                'site_domain'  => $site_domain,
                'path'         => $path,
                'site_rewrite' => $site_rewrite,
            ];
            $site[$site_domain] = $data;
            $domain['site'] = $site;
            file_replace_var(APP_PATH . 'config/domain.php', $domain);
            message(0, jump('更新/添加成功', url('set-domain', array(), TRUE), 3));
        }
        break;
    case 'domain_add':
        if ('POST' == $method) {
            $input = param('batch_sites');   
            tmpl_init();
            $cond = array();
            $tmpllist = tmpl_list($cond, $orderby = array(), 1, 500, 2);
            $res = site_batch_add($input);
            message(0, jump($res, url('set-domain', array(), TRUE), 3));
        }
        break;
    case 'domain_del':
        if ('GET' == $method) {
            $site_domain = param('site_domain', '');
            $site_domain = get_domain($site_domain);
            $replace = array();
            $old = $domain['site'][$site_domain];
            unset($domain['site'][$site_domain]);
            $replace['site'] = $domain['site'];
            file_replace_var(APP_PATH . 'config/domain.php', $replace);
            message(0, jump('删除成功', url('set-domain', array(), TRUE), 3));
        }
        break;
    default:
        message(-1, lang('data_malformation'));
        break;
}
