<?php
function cate__create($arr)
{
    $r = db_create('cate', $arr);
    return $r;
}
function cate__update($cid, $arr)
{
    $r = db_update('cate', array('cid' => $cid), $arr);
    return $r;
}
function cate__read($cid)
{
    $cate = db_find_one('cate', array('cid' => $cid));
    return $cate;
}
function cate__delete($cid)
{
    $r = db_delete('cate', array('cid' => $cid));
    return $r;
}
function cate__find($cond = array(), $orderby = array(), $page = 1, $pagesize = 1000)
{
    $catelist = db_find('cate', $cond, $orderby, $page, $pagesize, 'cid');
    return $catelist;
}
function cate_big_insert($arr = array(), $d = NULL)
{
    $r = db_big_insert('cate', $arr, $d);
    return $r;
}
function cate_big_update($cond = array(), $update = array(), $d = NULL)
{
    $r = db_big_update('cate', $cond, $update, $d);
    return $r;
}
function cate_create($arr)
{
    $r = cate__create($arr);
    cate_list_cache_delete();
    return $r;
}
function cate_update($cid, $arr)
{
    $r = cate__update($cid, $arr);
    cate_list_cache_delete();
    return $r;
}
function cate_read($cid)
{
    global $conf, $catelist;
    if ($conf['cache']['enable']) {
        return empty($catelist[$cid]) ? array() : $catelist[$cid];
    } else {
        $cate = cate__read($cid);
        $cate and cate_format($cate);
        return $cate;
    }
}
function cate_delete($cid)
{
    global $catelist;
    if (empty($cid)) return FALSE;
    $cate = $catelist[$cid];
    if (empty($cate)) return FALSE;
    $cate['videos'] = video_cid_count($cid);
    $cond = array('cid' => $cid);
    // 分类 0论坛 1cms
    if (1 == $cate['type']) {
        $pagesize = 5000;
        if ($cate['videos'] > 5000) {
            $totalpage = ceil($cate['videos'] / $pagesize);
        } else {
            $totalpage = 1;
        }
        for ($i = 1; $i <= $totalpage; ++$i) {
            $videolist = video_vid__find($cond, array(), $i, $pagesize, 'vid', array('vid'));
            if ($videolist) {
                $vids = array();
                foreach ($videolist as $video) $vids[] = $video['vid'];
                !empty($vids) and video_delete_all($vids);
            }
        }
    } 
    $cate['cup'] and cate_update($cate['cup'], array('son-' => 1));
    $r = cate__delete($cid); 
    cate_list_cache_delete();
    return $r;
}
function cate_find($cond = array(), $orderby = array('rank' => -1), $page = 1, $pagesize = 1000)
{
    static $cache = array();
    $key = md5(xn_json_encode($cond));
    if (isset($cache[$key])) return $cache[$key];
    $cache[$key] = cate__find($cond, $orderby, $page, $pagesize);
    return $cache[$key];
}
function cate_find_fmt($cond = array(), $orderby = array('rank' => -1), $page = 1, $pagesize = 1000)
{
    $catelist = cate_find($cond, $orderby, $page, $pagesize);
    if ($catelist) {
        foreach ($catelist as $key => &$cate) {
            cate_format($cate);
        }
    }
    return $catelist;
}
function cate_format(&$cate)
{
    global $conf ;
    $route_list = $conf['route']['list'];
    $route_cate = $conf['route']['cate'];
    if (empty($cate)) return;
    $cate['create_date_fmt'] = date('Y-n-j', $cate['create_date']);
    if ($cate['type']) {
        switch ($cate['model']) {
            default:
                switch ($cate['category']) {
                    case 1:
                        $cate['url'] = url($route_cate.'-' . $cate['cid'], '', FALSE);
                        break;
                    case 2:
                        $cate['url'] = $cate['videos'] ? url('read-' . trim($cate['seo_des']), '', FALSE) : url($route_list.'-' . $cate['cid'], '', FALSE);
                        break;
                    case 3:
                        $cate['url'] = url($route_list.'-' . $cate['cid'], '', FALSE);
                        break;
                    default:
                        $cate['url'] = url($route_list.'-' . $cate['cid'], '', FALSE);
                        break;
                }
                break;
        }
        if ($conf['url_rewrite_on'] > 1 && $cate['alias']) {
    if (0 == $cate['category'] || 1 == $cate['category']) {
        $cate['url'] = url($cate['alias'], '', FALSE);
    } 
}
    }
    if ($conf['url_rewrite_on'] > 1 && $cate['alias']) {
    if (0 == $cate['category'] || 1 == $cate['category']) {
        $url = url($cate['alias'], '', FALSE);
    } 
    }
    $cate['type_url']=url($conf['route']['type'].'-'.$cate['cid'],'',false);
}
function cate_count($cond = array())
{
    $n = db_count('cate', $cond);
    return $n;
}
function cate_maxid()
{
    $n = db_maxid('cate', 'cid');
    return $n;
}
function cate_list_cache()
{
    global $conf, $catelist, $domain;
    $key = 'cate-list';
    static $cache = array();
    if (isset($cache[$key])) return $cache[$key];
    if (empty($domain['site']) || !is_array($domain['site'])) {
    $catelist = cache_get('catelist');
    }
    if (NULL === $catelist) {
        $catelist = cate_find_fmt();
        if (empty($domain['site']) || !is_array($domain['site'])) {
            cache_set('catelist', $catelist, 7200);
        }
    }
    $cache[$key] = $catelist ? $catelist : NULL;
    return $cache[$key];
}
function cate_list_cache_delete()
{
    global $conf;
    static $deleted = FALSE;
    if ($deleted) return;
    cache_delete('cate-alias-cache');
    cache_delete('catelist'); 
    $deleted = TRUE;
}
function cate_safe_info($cate)
{
    return $cate;
}
function cate_filter($catelist)
{
    foreach ($catelist as &$val) {
        unset($val['seo_des'], $val['seo_title'], $val['seo_key'], $val['create_date_fmt']);
    }
    return $catelist;
}
function cate_format_url($cate)
{
    global $conf ;
    $route_list = $conf['route']['list'];
    $route_cate = $conf['route']['cate'];
    if (0 == $cate['category']) {
        // 列表URL
        $url = url($route_list.'-' . $cate['cid'], '', FALSE);
    } elseif (1 == $cate['category']) { 
        $url = url($route_cate.'-' . $cate['cid'], '', FALSE);
    } elseif (2 == $cate['category']) { 
        $url = url('page-' . trim($cate['seo_des']), '', FALSE);
    }
    return $url;
}
function cate_alias()
{
    global $catelist;
    $key = 'cate-alias';
    static $cache = array();
    if (isset($cache[$key])) return $cache[$key];
    if (empty($catelist)) return '';
    $cache[$key] = array();
    foreach ($catelist as $val) {
        if ($val['alias']) $cache[$key][$val['cid']] = $val['alias'];
    }
    return array_flip($cache[$key]);
}
function cate_alias_cache()
{
    global $conf;
    $key = 'cate-alias-cache';
    static $cache = array(); // 用静态变量只能在当前 request 生命周期缓存，跨进程需要再加一层缓存：redis/memcached/xcache/apc
    if (isset($cache[$key])) return $cache[$key];
    if ('mysql' == $conf['cache']['type']) {
        $arr = cate_alias();
    } else {
        $arr = cache_get($key);
        if (NULL === $arr) {
            $arr = cate_alias();
            !empty($arr) AND cache_set($key, $arr);
        }
    }
    $cache[$key] = empty($arr) ? '' : $arr;
    return $cache[$key];
}
// 获取CMS全部栏目，包括频道的二叉树结构
function category_tree($catelist)
{
    if (empty($catelist)) return NULL;
    static $cache = array();
    if (isset($cache['catelist'])) return $cache['catelist'];
    $arrlist = arrlist_cond_orderby($catelist, array('type' => 1), array(), 1, 1000);
    $arrlist = category_tree_format($arrlist);
    $cache['catelist'] = array_multisort_key($arrlist, 'rank', FALSE, 'cid');
    return $cache['catelist'];
}
// 门户 获取需要在频道显示的栏目 cid name index_new最新显示数量
function channel_category($cid)
{
    global $catelist_show;
    static $cache = array();
    if (isset($cache[$cid])) return $cache[$cid]; 
    if (empty($catelist_show[$cid])) return NULL;
    $cate = $catelist_show[$cid];
    $cache[$cid] = $cate['son'] ? arrlist_cond_orderby($catelist_show, array('cup' => $cid, 'type' => 1, 'category' => 0), array('cid' => -1), 1, 1000) : NULL;
    return $cache[$cid];
}
// 返回网站所有频道
function all_channel($catelist)
{
    if (empty($catelist)) return NULL;
    static $cache = array();
    if (isset($cache['all_channel'])) return $cache['all_channel'];
    $channellist = arrlist_cond_orderby($catelist, array('type' => 1, 'category' => 1), array(), 1, 100);
    $cidarr = arrlist_key_values($channellist, 'cid', 'name');
    $cache['all_channel'] = array('0' => lang('first_level_cate'));
    foreach ($cidarr as $key => $v) {
        $cache['all_channel'][$key] = $v;
    }
    return $cache['all_channel'];
}
/**
 * @param $catelist    所有版块列表
 * @return mixed    返回二叉树结构的版块列表
 */
function category_tree_format($catelist)
{
    // 格式化为树状结构 (会舍弃不合格的结构)
    foreach ($catelist as &$v) {
        if ($v['cup']) {
            $catelist[$v['cup']]['sonlist'][$v['cid']] = $v;
            unset($catelist[$v['cid']]);
        }
    }
    return $catelist;
}
function all_category($catelist)
{
    if (empty($catelist)) return NULL;
    static $cache = array();
    if (isset($cache['all_category'])) return $cache['all_category'];
    $cache['all_category'] = arrlist_cond_orderby($catelist, array('type' => 1, 'category' => array('<' => 2)), array(), 1, 1000);
    return $cache['all_category'];
}
function category_list($catelist, $model = 0, $display = 0, $category = 0)
{
    if (empty($catelist)) return NULL;
    static $cache = array();
    $key = $model . '-' . $display . '-' . $category;
    if (isset($cache[$key])) return $cache[$key];
    if ($display) {
        foreach ($catelist as $k => $val) {
            if (1 == $val['display'] && 1 == $val['type'] && $val['category'] == $category) {
                $cache[$key][$k] = $val;
            }
        }
    } else {
        foreach ($catelist as $k => $val) {
            if (1 == $val['type'] && $val['category'] == $category) {
                $cache[$key][$k] = $val;
            }
        }
    }
    return empty($cache[$key]) ? NULL : $cache[$key];
}
function category_list_show($catelist, $display = 0, $category = 0)
{
    if (empty($catelist)) return NULL;
    static $cache = array();
    $key = $display . '-' . $category;
    if (isset($cache[$key])) return $cache[$key];
    if ($display) {
        foreach ($catelist as $k => $val) {
            if (1 == $val['nav_display'] && 1 == $val['type'] && $val['category'] == $category) {
                $cache[$key][$k] = $val;
            }
        }
    } else {
        foreach ($catelist as $k => $val) {
            if (1 == $val['type'] && $val['category'] == $category) {
                $cache[$key][$k] = $val;
            }
        }
    }
    return empty($cache[$key]) ? NULL : $cache[$key];
}
function cate_list($catelist)
{
    if (empty($catelist)) return array();
    static $cache = array();
    if (isset($cache['bbs_cate_list'])) return $cache['bbs_cate_list'];
    $cache['bbs_cate_list'] = array();
    foreach ($catelist as $_cid => $_cate) {
        if ($_cate['type']) continue;
        $cache['bbs_cate_list'][$_cid] = $_cate;
    }
    return $cache['bbs_cate_list'];
}
function nav_list($catelist)
{
    if (empty($catelist)) return NULL;
    static $cache = array();
    if (isset($cache['nav_list'])) return $cache['nav_list'];
    foreach ($catelist as $cid => $cate) {
        if (0 == $cate['nav_display']) {
            unset($catelist[$cid]);
        }
    }
    return $cache['nav_list'] = $catelist;
}
function index_video_cache($catelist,$num)
{
    $key = 'index_video';
    static $cache = array();  
    if (isset($cache[$key])) return $cache[$key];
    $arr = cache_get($key);
    if (NULL === $arr) {
        $arr = index_video($catelist,$num);
        empty($arr) || cache_set($key, $arr);
    }
    $cache[$key] = empty($arr) ? NULL : $arr; 
    return $cache[$key];
}
function index_video($catelist,$num)
{
    if (empty($catelist)) return NULL;
    $orderby = array('vid' => -1);
    $page = 1;
    $index_catelist = category_list_show($catelist, 1);
    $arrlist = array();
    $cate_vids = array();
    $vidlist = array();
    if ($index_catelist) {
        foreach ($index_catelist as &$_cate) {
            $arrlist['list'][$_cate['cid']] = array(
                'cid' => $_cate['cid'],
                'name' => $_cate['name'],
                'rank' => $_cate['rank'],
                'type' => $_cate['type'],
                'url' => $_cate['url'],
                'index_new' => $num,
            );
            $cate_video = video_vid__find(array('cid' => $_cate['cid']), $orderby, $page, $num, 'vid', array('vid'));
            foreach ($cate_video as $key => $_video) {
                $cate_vids[$key] = $_video;
            }
            unset($cate_video);
        }
        $vidlist += $cate_vids;
    }
    unset($index_catelist);
    unset($catelist);
    $stickylist = sticky_index_video();
    empty($stickylist) || $vidlist += $stickylist;
    $vidarr = arrlist_values($vidlist, 'vid');
    if (empty($vidarr)) {
        $arrlist['list'] = isset($arrlist['list']) ? array_multisort_key($arrlist['list'], 'rank', FALSE, 'cid') : array();
        return $arrlist;
    }
    $vidarr = array_unique($vidarr);
    $pagesize = count($vidarr);
    $videolist = video_find_asc($vidarr, $pagesize);
    $videolist = array_reverse($videolist);
    foreach ($videolist as &$_video) {
        isset($cate_vids[$_video['vid']]) AND $arrlist['list'][$_video['cid']]['news'][$_video['vid']] = $_video;
        isset($stickylist[$_video['vid']]) AND $arrlist['sticky'][$_video['vid']] = $_video;
    }
    unset($videolist);
    if (isset($arrlist['sticky'])) {
        $i = 0;
        foreach ($arrlist['sticky'] as &$val) {
            ++$i;
            $val['i'] = $i;
        }
    }
    isset($arrlist['list']) AND $arrlist['list'] = array_multisort_key($arrlist['list'], 'rank', FALSE, 'cid');
    return $arrlist;
}
function channel_video_cache($cid,$pagesize)
{
    $key = 'channel_video_' . $cid;
    static $cache = array(); 
    if (isset($cache[$key])) return $cache[$key];
    $arr = cache_get($key);
    if (NULL === $arr) {
        $arr = channel_video($cid,$pagesize);
        empty($arr) || cache_set($key, $arr, 1200);
    }
    $cache[$key] = empty($arr) ? NULL : $arr;
    return $cache[$key];
}
function channel_art_cache($cid,$pagesize)
{
    $key = 'channel_art_' . $cid;
    static $cache = array(); 
    if (isset($cache[$key])) return $cache[$key];
    $arr = cache_get($key);
    if (NULL === $arr) {
        $arr = channel_art($cid,$pagesize);
        empty($arr) || cache_set($key, $arr, 1200);
    }
    $cache[$key] = empty($arr) ? NULL : $arr;
    return $cache[$key];
}
function channel_art($cid,$pagesize)
{
    global $catelist;
    if (empty($cid)) return NULL; 
    $orderby = array('aid' => 1);
    $page = 1; 
    $category_catelist = channel_category($cid);
    $arrlist = array();
    $cate_aids = array();
    $aidlist = array();
    if ($category_catelist) {
        foreach ($category_catelist as &$_cate) {
            $arrlist['list'][$_cate['cid']] = array(
                'cid' => $_cate['cid'],
                'name' => $_cate['name'],
                'rank' => $_cate['rank'],
                'type' => $_cate['type'],
                'url' => $_cate['url'],
                'channel_new' => $pagesize,
            );
            $cate_art = art_aid__find(array('cid' => $_cate['cid']), $orderby, $page, $pagesize, 'aid', array('aid'));
            foreach ($cate_art as $key => $_art) {
                $cate_aids[$key] = $_art;
            } 
            unset($cate_art);
        }
        $aidlist += $cate_aids;
    } 
    unset($category_catelist);  
    $aidarr = arrlist_values($aidlist, 'aid'); 
    if (empty($aidarr)) {
        $arrlist['list'] = isset($arrlist['list']) ? array_multisort_key($arrlist['list'], 'rank', FALSE, 'cid') : array();
        return $arrlist;
    }
    $aidarr = array_unique($aidarr); 
    $pagesize = count($aidarr);
    $artlist = art_find_asc($aidarr, $pagesize); 
    $artlist = array_reverse($artlist); 
    foreach ($artlist as &$_art) { 
        isset($cate_aids[$_art['aid']]) AND $arrlist['list'][$_art['cid']]['news'][$_art['aid']] = $_art;
        isset($stickylist[$_art['aid']]) AND $arrlist['sticky'][$_art['aid']] = $_art;
    }
    unset($artlist);
    if (isset($arrlist['sticky'])) {
        $i = 0;
        foreach ($arrlist['sticky'] as &$val) {
            ++$i;
            $val['i'] = $i;
        }
    }  
    isset($arrlist['list']) AND $arrlist['list'] = array_multisort_key($arrlist['list'], 'rank', FALSE, 'cid');
    return $arrlist;
} 
function channel_video($cid,$pagesize)
{
    global $catelist;
    if (empty($cid)) return NULL; 
    $orderby = array('vid' => 1);
    $page = 1; 
    $category_catelist = channel_category($cid);
    $arrlist = array();
    $cate_vids = array();
    $vidlist = array();
    if ($category_catelist) {
        foreach ($category_catelist as &$_cate) {
            $arrlist['list'][$_cate['cid']] = array(
                'cid' => $_cate['cid'],
                'name' => $_cate['name'],
                'rank' => $_cate['rank'],
                'type' => $_cate['type'],
                'url' => $_cate['url'],
                'channel_new' => $pagesize,
            );
            $cate_video = video_vid__find(array('cid' => $_cate['cid']), $orderby, $page, $pagesize, 'vid', array('vid'));
            foreach ($cate_video as $key => $_video) {
                $cate_vids[$key] = $_video;
            } 
            unset($cate_video);
        }
        $vidlist += $cate_vids;
    } 
    unset($category_catelist); 
    $stickylist = sticky_list_video($cid);
    empty($stickylist) || $vidlist += $stickylist; 
    $vidarr = arrlist_values($vidlist, 'vid'); 
    if (empty($vidarr)) {
        $arrlist['list'] = isset($arrlist['list']) ? array_multisort_key($arrlist['list'], 'rank', FALSE, 'cid') : array();
        return $arrlist;
    }
    $vidarr = array_unique($vidarr); 
    $pagesize = count($vidarr);
    $videolist = video_find_asc($vidarr, $pagesize); 
    $videolist = array_reverse($videolist); 
    foreach ($videolist as &$_video) { 
        isset($cate_vids[$_video['vid']]) AND $arrlist['list'][$_video['cid']]['news'][$_video['vid']] = $_video;
        isset($stickylist[$_video['vid']]) AND $arrlist['sticky'][$_video['vid']] = $_video;
    }
    unset($videolist);
    if (isset($arrlist['sticky'])) {
        $i = 0;
        foreach ($arrlist['sticky'] as &$val) {
            ++$i;
            $val['i'] = $i;
        }
    }  
    isset($arrlist['list']) AND $arrlist['list'] = array_multisort_key($arrlist['list'], 'rank', FALSE, 'cid');
    return $arrlist;
} 
function cate_access_user($cid, $gid, $access)
{
    global $grouplist, $catelist; 
    if (empty($catelist[$cid])) return FALSE;
    $group = $grouplist[$gid];
    $cate = $catelist[$cid];
    if (!empty($cate['accesson'])) {
        $r = (!isset($group[$access]) || $group[$access]) && !empty($cate['accesslist'][$gid][$access]);
    } else {
        $r = !empty($group[$access]);
    } 
    return $r;
}
function findcate($data, $model, $defaultUrl = '/', $defaultName = '默认') { 
     if (!is_array($data) || empty($data)) {
        return ['name' => $defaultName, 'url' => $defaultUrl];
    }

    foreach ($data as $v) {
        if (!is_array($v)) continue;
        if (!isset($v['model'], $v['url'], $v['name'])) continue; 
        if ($v['model'] == $model && !empty($v['url'])) {
            return ['name' => $v['name'], 'url' => $v['url']];
        }
    } 
    return ['name' => $defaultName, 'url' => $defaultUrl];
}
?>