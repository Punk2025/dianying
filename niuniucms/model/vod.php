<?php
function sticky_video_create($arr, $d = NULL)
{
    $r = db_replace('vod_sticky', $arr, $d);
    cache_delete('sticky_video_list');
    return $r;
}
function sticky_video__update($vid, $arr, $d = NULL)
{
    $r = db_update('vod_sticky', array('vid' => $vid), $arr, $d);
    return $r;
}
function sticky_video__find($cond = array(), $orderby = array(), $page = 1, $pagesize = 20, $key = 'vid', $col = array(), $d = NULL)
{
    $videolist = db_find('vod_sticky', $cond, $orderby, $page, $pagesize, $key, $col, $d);
    return $videolist;
}
function sticky_video__delete($vid, $d = NULL)
{
    $r = db_delete('vod_sticky', array('vid' => $vid), $d);
    return $r;
}
function sticky_video__count($cond = array(), $d = NULL)
{
    $n = db_count('vod_sticky', $cond, $d);
    return $n;
}
function video__create($arr, $d = NULL)
{
    $r = db_insert('vod', $arr, $d);
    return $r;
}
function video__update($vid, $update, $d = NULL)
{
    $r = db_update('vod', array('vid' => $vid), $update, $d);
    return $r;
}
function video__read($cond = array(), $orderby = array(), $col = array(), $d = NULL)
{
    $video = db_find_one('vod', $cond, $orderby, $col, $d);
    return $video;
}
function video_max_vid($col = 'vid', $cond = array(), $d = NULL)
{
    $vid = db_maxid('vod', $col, $cond, $d);
    return $vid;
}
function video_last($cond = array(), $col = array(), $d = NULL)
{
    $video = db_find_one('vod', $cond, array('vid' => -1), $col, $d);
    return $video;
}
function video__delete($vid, $d = NULL)
{
    $r = db_delete('vod', array('vid' => $vid), $d);
    return $r;
}
function video__find($cond = array(), $orderby = array(), $page = 1, $pagesize = 20, $key = 'vid', $col = array(), $d = NULL)
{
    $videolist = db_find('vod', $cond, $orderby, $page, $pagesize, $key, $col, $d);
    return $videolist;
}
function video_count_day()
{
    $now = time();
    $start = strtotime(date('Y-m-d 00:00:00', $now));
    $end   = strtotime('+1 day', $start);
    $n= db_count('vod', ['create_date' => ['>=' => $start, '<' => $end] ]);
    return $n;
}
function video_count($cond = array(), $d = NULL)
{
    $n = db_count('vod', $cond, $d);
    return $n;
}
function video_big_insert($arr = array(), $d = NULL)
{
    $r = db_big_insert('vod', $arr, $d);
    return $r;
}
function video_big_update($cond = array(), $update = array(), $d = NULL)
{
    $r = db_big_update('vod', $cond, $update, $d);
    return $r;
}
function video_vid__create($arr = array(), $d = NULL)
{
    $r = db_replace('vod_vid', $arr, $d);
    return $r;
}
function video_vid__update($cond = array(), $update = array(), $d = NULL)
{
    $r = db_update('vod_vid', $cond, $update, $d);
    return $r;
}
function video_vid__read($cond = array(), $orderby = array(), $col = array(), $d = NULL)
{
    $r = db_find_one('vod_vid', $cond, $orderby, $col, $d);
    return $r;
}
function video_vid__find($cond = array(), $orderby = array(), $page = 1, $pagesize = 20, $key = 'vid', $col = array(), $d = NULL)
{
    $arr = db_find('vod_vid', $cond, $orderby, $page, $pagesize, $key, $col, $d);
    return $arr;
}
function video_vid__delete($cond = array(), $d = NULL)
{
    $r = db_delete('vod_vid', $cond, $d);
    return $r;
}
function video_vid__count($cond = array(), $d = NULL)
{
    $n = db_count('vod_vid', $cond, $d);
    return $n;
}
function video_vid_big_insert($arr = array(), $d = NULL)
{
    $r = db_big_insert('vod_vid', $arr, $d);
    return $r;
}
function video_vid_big_update($cond = array(), $update = array(), $d = NULL)
{
    $r = db_big_update('vod_vid', $cond, $update, $d);
    return $r;
}
function video_vid_create($arr)
{
    if (empty($arr)) return FALSE;
    $r = video_vid__create($arr);
    if (FALSE === $r) return FALSE;
    return $r;
}
function video_vid_read($vid)
{
    $r = video_vid__read(array('vid' => $vid));
    return $r;
}
function video_vid_update($vid, $cid)
{
    if (empty($vid) || empty($cid)) return FALSE;
    $r = video_vid__update(array('vid' => $vid), array('cid' => $cid));
    return $r;
}
function video_vid_update_lastpid($vid, $lastpid)
{
    if (empty($vid) || empty($lastpid)) return FALSE;
    $r = video_vid__update(array('vid' => $vid), array('lastpid' => $lastpid));
    return $r;
}
function video_vid_update_rank($vid, $rank)
{
    if (empty($vid) || empty($rank)) return FALSE;
    $r = video_vid__update(array('vid' => $vid), array('rank' => $rank));
    return $r;
}
function video_vid_find($page = 1, $pagesize = 20, $desc = TRUE)
{
    $orderby = TRUE == $desc ? -1 : 1;
    $arr = video_vid__find($cond = array(), array('vid' => $orderby), $page, $pagesize, 'vid', array('vid'));
    return $arr;
}
function video_vid_find_by_uid($uid, $page = 1, $pagesize = 1000, $desc = TRUE, $key = 'vid', $col = array())
{
    if (empty($uid)) return array();
    $orderby = TRUE == $desc ? -1 : 1;
    $arr = video_vid__find($cond = array(), array('vid' => $orderby), $page, $pagesize, $key, $col);
    return $arr;
}
function video_vid_find_by_cid($cid, $page = 1, $pagesize = 1000, $desc = TRUE)
{
    if (empty($cid)) return array();
    $orderby = TRUE == $desc ? -1 : 1;
    $arr = video_vid__find($cond = array('cid' => $cid), array('vid' => $orderby), $page, $pagesize, 'vid', array('vid'));
    return $arr;
}
function video_vid_find_by_cid_top($cid, $page = 1, $pagesize = 1000, $desc = TRUE)
{
    if (empty($cid)) return array();
     
    $arr = db_find('vod',$cond = array('cid' => $cid), array('views'=>-1), $page, $pagesize, 'vid', array('vid'));
    return $arr;
}
function video_vid_delete($vid)
{
    if (empty($vid)) return FALSE;
    $r = video_vid__delete(array('vid' => $vid));
    return $r;
}
function video_vid_count()
{
    $n = video_vid__count();
    return $n;
}
function video_cid_count($cid)
{
    $n = video_vid__count(array('cid' => $cid));
    return $n;
}
function video_update($vid, $update)
{
    global $conf;
    if (empty($vid) || empty($update)) return FALSE;
    $r = video__update($vid, $update);
    if (FALSE === $r) return FALSE;
    if ('mysql' != $conf['cache']['type']) {
        if (TRUE === is_array($vid)) {
            foreach ($vid as $_vid) cache_delete('vod_' . $_vid);
        } else {
            cache_delete('vod_' . $vid);
        }
    }
    return $r;
}
// 更新全部数据
function video_update_all($vid, $update)
{
    if (empty($vid) || empty($update)) return FALSE;
    $r = video_update($vid, $update);
    if (FALSE === $r) return FALSE;
    return $r;
}
// 遍历栏目vid 按照: 发布时间 倒序，不包含置顶 
function video_find_vid($cid, $page = 1, $pagesize = 20)
{
    global $conf, $catelist;
    $key = 'video_find_vid_' . $cid . '_' . $page . '_' . $pagesize;
    static $cache = array();
    if (isset($cache[$key])) return $cache[$key];
    $cate = array_value($catelist, $cid);
    $videos = video_cid_count($cid);
    $desc = TRUE;
    $limitpage = 5000; // 如果需要防止 CC 攻击，可以调整为 5000
    if ($page > 100) {
        $totalpage = ceil($videos / $pagesize);
        $halfpage = ceil($totalpage / 2);
        if ($halfpage > $limitpage && $page < ($totalpage - $limitpage)) {
            $page = $limitpage;
        }
        if ($page > $halfpage) {
            $page = max(1, $totalpage - $page + 1);
            $arr = video_vid_find_by_cid($cid, $page, $pagesize, FALSE);
            $arr = array_reverse($arr, TRUE);
            $desc = FALSE;
        }
    }
    $desc and $arr = video_vid_find_by_cid($cid, $page, $pagesize, TRUE);
    if (empty($arr)) return NULL;
    return $arr;
}
// 按照: rank 倒序，含置顶帖 查询栏目cid下vid 主题数据详情
function video_find_desc($cid, $page = 1, $pagesize = 20)
{
    global $conf, $catelist;
    $key = 'video_find_desc_' . $cid . '_' . $page . '_' . $pagesize;
    static $cache = array();
    if (isset($cache[$key])) return $cache[$key];
    $cate = array_value($catelist, $cid);
    $videos = $cate['videos'];
    $desc = TRUE;
    $limitpage = 5000; // 如果需要防止 CC 攻击，可以调整为 5000
    if ($page > 100) {
        $totalpage = ceil($videos / $pagesize);
        $halfpage = ceil($totalpage / 2);
        if ($halfpage > $limitpage && $page < ($totalpage - $limitpage)) {
            $page = $limitpage;
        }
        if ($page > $halfpage) {
            $page = max(1, $totalpage - $page + 1);
            $arr = video_vid__find(array('cid' => $cid), array('rank' => 1), $page, $pagesize);
            $arr = array_reverse($arr, TRUE);
            $desc = FALSE;
        }
    }
    $desc and $arr = video_vid__find(array('cid' => $cid), array('rank' => -1), $page, $pagesize);
    if (empty($arr)) return NULL;
    return $arr;
}
// vidarr 查询主题数据
function video_find($vidarr, $pagesize = 20, $desc = TRUE)
{
    $orderby = TRUE == $desc ? -1 : 1;
    $videolist = video__find(array('vid' => $vidarr), array('vid' => $orderby), 1, $pagesize);
    if (!$videolist) return NULL;
    $i = 0;
    foreach ($videolist as &$video) {
        ++$i;
        $video['i'] = $i;
        video_format($video);
    }
    return $videolist;
}
// vidarr 查询主题数据 不给mysql增加压力使用正序 倒叙可以使用array_reverse($videolist, TRUE);
function video_find_asc($vidarr, $pagesize = 20)
{
    $videolist = video__find(array('vid' => $vidarr), array('vid' => 1), 1, $pagesize);
    if (!$videolist) return NULL;
    $i = 0;
    foreach ($videolist as $_vid => &$video) {
        ++$i;
        $video['i'] = $i;
        video_format($video);
    }
    return $videolist;
}
function video_find_by_vids($vidarr)
{
    $videolist = video_find($vidarr, 1000);
    return $videolist;
}
function video_inc_views($vid, $n = 1)
{
    global $conf, $db;
    $tablepre = $db->tablepre;
    $sqladd = in_array($conf['cache']['type'], array('mysql', 'pdo_mysql')) ? ' LOW_PRIORITY' : '';
    $r = db_exec("UPDATE$sqladd `{$tablepre}vod` SET views=views+$n WHERE vid='$vid'");
    'mysql' != $conf['cache']['type'] and cache_update('vod_' . $vid, array('views+' => $n), 1800);
    return $r;
}
function video_read($vid)
{
    if (empty($vid)) return NULL;
    static $cache = array();
    if (isset($cache[$vid])) return $cache[$vid];
    $cache[$vid] = video__read(array('vid' => $vid));
    $cache[$vid] and video_format($cache[$vid]);
    return $cache[$vid];
}
// 只删除vid和缓存
function video_delete($vid)
{
    global $conf;
    if (empty($vid)) return FALSE;
    $r = video__delete($vid);
    if (FALSE === $r) return FALSE;
    if ('mysql' != $conf['cache']['type']) {
        if (is_array($vid)) {
            foreach ($vid as $_vid) cache_delete('vod_' . $_vid);
        } else {
            cache_delete('vod_' . $vid);
        }
    }
    return $r;
}
// 单独删除传vid 批量删除传vids array(1,2,3)
function video_delete_all($vid)
{
    global $gid, $uid, $time, $conf, $config, $catelist;
    if (empty($vid)) return FALSE;
    set_time_limit(0);
    $n = is_array($vid) ? count($vid) : 1;
    $videolist = video__find(array('vid' => $vid), array('vid' => 1), 1, $n, 'vid');
    if (empty($videolist)) return FALSE;
    $vids = array();
    $tagids = array();
    $tagarr = array();
    $tagvids = array();
    foreach ($videolist as $video) {
        $vids[] = $video['vid'];
        if ($video['tag']) {
            $tagvids[] = $video['vid'];
            $_tagarr = xn_json_decode($video['tag']);
            foreach ($_tagarr as $_tagid => $tagname) {
                isset($tagarr[$_tagid]) ? $tagarr[$_tagid] += 1 : $tagarr[$_tagid] = 1;
            }
        }
        if ($video['sticky']) {
            $sticky_vids[] = $video['vid'];
        }
    }
    // 删除标签主题表
    if (!empty($tagvids)) {
        $arlist = tag_video_find_by_vid($tagvids, 1, count($tagvids) * 10);
        if ($arlist) {
            $ids = array();
            foreach ($arlist as $val) $ids[] = $val['id'];
            tag_video_delete($ids);
        }
    }
    // 更新tag统计
    if (!empty($tagids)) {
        $tagids = array_unique($tagids);
        $update = array();
        foreach ($tagarr as $tagid => $n) {
            $update[$tagid] = array('count-' => $n);
        }
        tag_big_update(array('tagid' => $tagids), $update);
    }
    //删除推荐
    if (!empty($sticky_vids)) {
        cache_delete('sticky_video_list');
        sticky_video__delete($sticky_vids);
    }
    if ($vids) {
        video__delete($vids);
        video_vid_delete($vids);
    }
    return TRUE;
}
// 搜索标题
function video_find_by_keyword($keyword, $d = NULL)
{
    if (empty($keyword)) return NULL;
    $db = $_SERVER['db'];
    $d = $d ? $d : $db;
    if (!$d) return FALSE;
    $keyword = trim($keyword);
    $keyword = str_replace(array("\0", "\r", "\n", "\t"), '', $keyword);
    $keyword = addslashes($keyword);
    $videolist = db_sql_find("SELECT * FROM `{$d->tablepre}vod` WHERE `name` LIKE '%$keyword%' LIMIT 60;", 'vid', $d);
    if ($videolist) {
        $videolist = arrlist_multisort($videolist, 'vid', FALSE);
        foreach ($videolist as &$video) {
            video_format($video);
        }
    }
    return $videolist;
}
//筛选 
function video_find_by_type($cond = array(), $page = 1)
{
    global $conf;
    $videolist = video__find($cond, $orderby = array(), $page = $page, $pagesize = $conf['pagesize'], $key = 'vid', $col = array(), $d = NULL);
    if ($videolist) {
        $videolist = arrlist_multisort($videolist, 'vid', FALSE);
        foreach ($videolist as &$video) {
            video_format($video);
        }
    }
    return $videolist;
}
function video_maxid()
{
    $n = db_maxid('vod', 'vid');
    return $n;
}
// 主题状态 0:通过 1~9 审核:1待审核 10~19:10退稿 11逻辑删除
function video_format(&$video)
{
    global $conf, $uid, $catelist;
    $conf = _SERVER('conf');
    if (empty($video)) return; 
    $video['create_date_fmt'] = humandate($video['create_date']); 
    $video['create_date_fmt_ymd'] = date('Y-m-d', $video['create_date']); 
    $cate = array_value($catelist, $video['cid']); 
    $video['cate_name'] = array_value($cate, 'name');
    $video['cate_url'] = array_value($cate, 'url');
    $route_page_vod = $conf['route']['vod'];
    $video['url'] = url($route_page_vod . '-' . $video['vid'], '', FALSE);
    if ($conf['url_rewrite_on'] > 1) {
        !empty($cate['alias']) and $video['url'] = url(urlencode($cate['alias']) . '-' . $video['vid'], '', FALSE);
    } 
    $video['tag_fmt'] = $video['tag'] ? xn_json_decode($video['tag']) : '';
    if (!empty($video['tag_fmt'])) {
        foreach ($video['tag_fmt'] as $_tag => $_tagname) {
            $tagurl = url($conf['route']['tag'].'-' . $_tag);
            /*功能暂时不启用 tag/pinyin
        if ($conf['url_rewrite_on'] > 1) {
        !empty($cate['alias']) and $video['url'] = url(urlencode($cate['alias']) . '-' . $video['vid'], '', FALSE); 
        }*/
            $tagarr[] = array(
                'name' => $_tagname,
                'url' => $tagurl
            );
        }
        $video['tag_url'] = $tagarr;
    }
    $video['play_data'] = video_play($video['play_from'], $video['play_url'], $cate['alias'], $video['vid']);
    $video['play_data_json'] = xn_json_encode($video['play_data']);
    return $video;
}
function video_tag($tag_fmt)
{
    global $conf;
    $pagesize = $conf['pagesize'];
    $tagids = array_keys($tag_fmt);
    $arrlist = tag_video_find($tagids, 1, 100);
    !$arrlist and $arrlist = tag_video__find(array(), array('id' => 1), 1, 100);
    if ($arrlist) {
        $count = count($arrlist);
        $total = $count > $pagesize ? $pagesize : $count;
        $tidarr = $count > 20 ? array_rand($arrlist, $total) : array_keys($arrlist);
        $arrlist = video_find_asc($tidarr, $total);
        return $arrlist;
    }
}
function video_format_last_date(&$video)
{
    if ($video['last_date'] != $video['create_date']) {
        $video['last_date_fmt'] = humandate($video['last_date']);
    } else {
        $video['create_date_fmt'] = humandate($video['create_date']);
    }
}
function video_list_access_filter(&$videolist, $gid)
{
    global $catelist;
    if (empty($videolist)) return NULL;
    foreach ($videolist as $vid => $video) {
        if (empty($catelist[$video['cid']]['accesson'])) continue;
        if ($video['sticky'] > 0) continue;
        if (!cate_access_user($video['cid'], $gid, 'allowread')) {
            unset($videolist[$vid]);
        }
    }
}
// 过滤安全数据
function video_filter(&$val)
{
    // hook video_filter_start.php
    unset($val['userip'], $val['cid'], $val['flagid'], $val['type'], $val['user'], $val['create_date']);
    // hook video_filter_end.php
}
//------------------------ 其他方法 ------------------------
// 集合主题vid，统一拉取，避免多次查询video表
function video_unified_pull($arr)
{
    global $gid, $cid; 
    $stickylist = array_value($arr, 'stickylist', array());
    $vidlist = array_value($arr, 'vidlist', array());
    $vidarrlist = $vidlist = $stickylist + $vidlist;
    $vidarr = empty($vidarrlist) ? array() : arrlist_values($vidarrlist, 'vid');
    if (empty($vidarr)) return NULL;
    $vidarr = array_unique($vidarr);
    $arrlist = video_find($vidarr, count($vidarr));
    video_list_access_filter($arrlist, $gid);
    $videolist = array();
    foreach ($arrlist as $_vid => &$_video) {
        isset($vidlist[$_video['vid']]) and $videolist[$_vid] = $_video;
    }
    $videolist = array2_sort_key($videolist, $vidlist, 'vid');
    unset($arrlist, $vidlist);
    $arr = array('videolist' => $videolist);
    return $arr;
}
function video_read_cache($vid)
{
    global $conf;
    $key = 'vod_' . $vid;
    static $cache = array();  
    if (isset($cache[$key])) return $cache[$key];
    if ('mysql' == $conf['cache']['type']) {
        $r = video_read($vid);
    } else {
        $r = cache_get($key);
        if (NULL === $r) {
            $r = video_read($vid);
            $r and cache_set($key, $r, 1800);
        }
    }
    $cache[$key] = $r ? $r : NULL;
    return $cache[$key];
}

function sticky_video_find_cache_rand($cid='',$page=1,$pagesize=20){
    global $conf;
    $key = 'sticky_video_list-'.$pagesize.$cid.$page;
    static $cache = array();  
    if (isset($cache[$key])) return $cache[$key];
    $where = array_filter_empty([
    'cid' => $cid
    ]);
    if ('mysql' == $conf['cache']['type']) {
        $arr = sticky_video__find($where, array('vid' => -1), $page,$pagesize);
    } else {
        $arr = cache_get($key);
        if (NULL === $arr) {
            $arr = sticky_video__find($where, array('vid' => -1), $page,$pagesize);
            $arr AND cache_set($key, $arr, 3600);
        }
    } 
    $cache[$key] = $arr ? $arr : NULL; 
    return $cache[$key];
}
function sticky_video_change($vid, $sticky, $video)
{
    global $time;
    if (empty($video)) return FALSE;
    video_update($vid, array('sticky' => $sticky));
    $r = sticky_video_create(array('cid' => $video['cid'], 'vid' => $video['vid'], 'sticky' => $sticky, 'create_date' => $time));
    return $r;
}
function sticky_video_update_by_vid($vid, $newcid)
{
    $r = sticky_video__update($vid, array('cid' => $newcid));
    cache_delete('sticky_video_list');
    return $r;
}
function sticky_video_delete($vid)
{
    global $config;
    $video = video__read(array('vid' => $vid));
    if (empty($video)) return FALSE;
    if (3 == $video['sticky']) {
        $config['index_stickys'] -= 1;
        setting_set('conf', $config);
    }
    if ($video['sticky']) {
        video_update($vid, array('sticky' => 0));
        cache_delete('sticky_video_list');
    }
    $r = sticky_video__delete($vid);
    return $r;
}
function sticky_video_count()
{
    $n = sticky_video__count();
    return $n;
}
function sticky_video_count_by_sticky($sticky)
{
    $n = sticky_video__count(array('sticky' => $sticky));
    return $n;
}
function sticky_video_count_by_cid($cid)
{
    $n = sticky_video__count(array('cid' => $cid));
    return $n;
}
function sticky_index_video()
{
    $arrlist = sticky_video_find_cache();
    if (empty($arrlist)) return NULL; 
    $arr = array();
    foreach ($arrlist as $val) {
        if (1 == $val['sticky']) {
            $arr[$val['vid']] = $val;
        }
    } 
    $arr = array_multisort_key($arr, 'create_date', FALSE, 'vid'); 
    return $arr;
}
function sticky_list_video($cid)
{
    global $catelist_show;
    $cate = isset($catelist_show[$cid]) ? $catelist_show[$cid] : NULL;
    if (empty($cate)) return NULL; 
    $arrlist = sticky_video_find_cache();
    if (empty($arrlist)) return NULL; 
    $cids = array();
    foreach ($catelist_show as $val) {
        if ($val['type'] && $val['display']) {
            if (1 == $cate['category']) {
                if ($val['cup'] == $cid) {
                    $cids[] = $val['cid'];
                }
            } else { 
                if ($val['cup'] == $cate['cup']) {
                    $cids[] = $val['cid'];
                }
            }
        }
    }
    $sticky1 = array();
    $sticky2 = array();
    $sticky3 = array();
    if (1 == $cate['category']) {
        foreach ($arrlist as $val) {
            if (in_array($val['cid'], $cids) && 2 == $val['sticky']) {
                $sticky2[$val['vid']] = $val;
            } elseif (3 == $val['sticky']) {
                $sticky3[$val['vid']] = $val;
            }
        }
    } else {
        foreach ($arrlist as $val) {
            if ($cate['cid'] == $val['cid'] && 1 == $val['sticky']) {
                $sticky1[$val['vid']] = $val;
            } elseif (in_array($val['cid'], $cids) && 2 == $val['sticky']) {
                $sticky2[$val['vid']] = $val;
            } elseif (3 == $val['sticky']) {
                $sticky3[$val['vid']] = $val;
            }
        }
    }
    $sticky3 = empty($sticky3) ? array() : array_multisort_key($sticky3, 'create_date', FALSE, 'vid');
    $sticky2 = empty($sticky2) ? array() : array_multisort_key($sticky2, 'create_date', FALSE, 'vid');
    $sticky1 = empty($sticky1) ? array() : array_multisort_key($sticky1, 'create_date', FALSE, 'vid');
    $arr = $sticky3 + $sticky2 + $sticky1;
    return $arr;
}
function sticky_video_find_cache()
{
    global $conf;
    $key = 'sticky_video_list';
    static $cache = array(); 
    if (isset($cache[$key])) return $cache[$key];
    if ('mysql' == $conf['cache']['type']) {
        $arr = sticky_video__find(array(), array('vid' => -1), 1, 2000);
    } else {
        $arr = cache_get($key);
        if (NULL === $arr) {
            $arr = sticky_video__find(array(), array('vid' => -1), 1, 2000);
            $arr AND cache_set($key, $arr, 1800);
        }
    } 
    $cache[$key] = $arr ? $arr : NULL; 
    return $cache[$key];
}
 
