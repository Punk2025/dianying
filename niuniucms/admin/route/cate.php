<?php
!defined('DEBUG') and exit('Access Denied.');
$action = param(1, 'list');
$header['title'] = lang('cate_page');
switch ($action) {
    case 'list':
        if ('GET' == $method) {
            $arrlist = category_tree($catelist);
            include _include(APP_PATH . 'admin/html/cate_list.html');
        }
        break;
    case 'rank':
        if ('POST' != $method) message(-1, lang('method_error'));
        $cidarr = param('cid', array(0));
        $rankarr = param('rank', array(0));
        $arrlist = array();
        foreach ($cidarr as $k => $v) {
            cate_update($k, array('cid' => $k, 'rank' => array_value($rankarr, $k)));
        }
        message(0, lang('save_successfully'));
        break;
    case 'create':
        if ('POST' == $method) {
            $name = param('name');
            $name = filter_all_html($name);
            empty($name) and message(1, lang('data_empty_to_last_step'));
            $seo_des = param('seo_des', '', FALSE);
            $seo_des = xn_html_safe($seo_des);
            $nav_display = param('nav_display', 0);
            $model = param('model', 0);
            $seo_title = param('seo_title');
            $seo_key = param('seo_key');
            $category = param('category', 0);
            $alias = param('alias');
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
                'category' => $category,
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
           message(0, jump('分类创建成功', url('cate-list', array(), TRUE), 3));
        }
        break;
    case 'update':
        $_cid = param('cid', 0);
        $_cate = cate_read($_cid);
        empty($_cate) and message(-1, lang('cate_not_exists'));
        if ('GET' == $method) {
            $extra = array('cid' => $_cid);
            array_htmlspecialchars($_cate);
            $arrlist = category_tree($catelist);
            $channelarr = all_channel($catelist);
            $cid = $_cate['cid'];
            $name = $_cate['name'];
            $seo_title = $_cate['seo_title'];
            $seo_key = $_cate['seo_key'];
            $seo_des = $_cate['seo_des'];
            $alias = $_cate['alias'];
            $category = $_cate['category']; // 0列表 1频道
            $catearr = array(lang('first_level_cate'), lang('channel'), lang('single_page'), lang('outer_chain'));
            $cup = $_cate['cup'];
            $model = $_cate['model'];
            $nav_display = $_cate['nav_display'];
            $display = $_cate['display'];
            include _include(APP_PATH . 'admin/html/cate_post.html');
        } elseif ('POST' == $method) {
            $cid = param('cid', 0);
            $name = param('name');
            $name = filter_all_html($name);
            empty($name) and message(1, lang('data_empty_to_last_step'));
            $seo_des = param('seo_des', '', FALSE);
            $seo_des = xn_html_safe($seo_des);
            $nav_display = param('nav_display', 0);
            $model = param('model', 0);
            $seo_title = param('seo_title');
            $seo_key = param('seo_key');
            $category = param('category', 0);
            $alias = param('alias');
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
                'category' => $category,
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
            db_update("cate", array('cid' => $cid), $arr);
            cate_list_cache_delete();
            message(0, lang('edit_successfully'));
        }
        break;
    case 'del':
        if ('POST' != $method) message(-1, lang('method_error'));
        $_cid = param('cid', 0);
        db_delete("vod", array('cid' => $_cid)); 
        db_delete("vod_vid", array('cid' => $_cid));
        $_cate = cate_read($_cid); 
        $cate_name=$_cate['name'];
        empty($_cate) and message(-1, lang('cate_not_exists'));
        $videolist = video_vid_find_by_cid($_cid, 1, 20); 
        empty($videolist) || message(-1, lang('cate_delete_video_before_delete_cate'));
        $_cate['son'] and message(-1, lang('cate_please_delete_sub_cate'));
        cate_delete($_cid);

        $replaces=array();
        $bind=$conf['bind']; 
        foreach($bind as $k=>$v){ 
            if (isset($v[$cate_name])) {
                unset($v[$cate_name]); 
            }
            $replaces[$k]=$v; 
        }
        $replace['bind'] = $replaces;
        file_replace_var(APP_PATH . 'config/config.php', $replace); 

        message(0, lang('cate_delete_successfully'));
        break;
    default:
        message(-1, lang('data_malformation'));
        break;
}
function user_names_to_ids($names, $sep = ',')
{
    if (empty($names)) return '';
    $namearr = explode($sep, $names);
    $r = array();
    foreach ($namearr as $name) {
        $user = user_read_by_username($name);
        if (empty($user)) continue;
        $r[] = $user ? $user['uid'] : 0;
    }
    return implode($sep, $r);
}
function user_ids_to_names($ids, $sep = ',')
{
    if (empty($ids)) return '';
    $idarr = explode($sep, $ids);
    $r = array();
    foreach ($idarr as $id) {
        $user = user_read($id);
        if (empty($user)) continue;
        $r[] = $user ? $user['username'] : '';
    }
    return implode($sep, $r);
}
?>