<?php
function art__create($arr, $d = NULL)
{
    $r = db_insert('art', $arr, $d);
    return $r;
}
function art__update($aid, $update, $d = NULL)
{
    $r = db_update('art', array('aid' => $aid), $update, $d);
    return $r;
}
function art__read($cond = array(), $orderby = array(), $col = array(), $d = NULL)
{
    $art = db_find_one('art', $cond, $orderby, $col, $d);
    return $art;
}
function art_max_aid($col = 'aid', $cond = array(), $d = NULL)
{
    $aid = db_maxid('art', $col, $cond, $d);
    return $aid;
}
function art_last($cond = array(), $col = array(), $d = NULL)
{
    $art = db_find_one('art', $cond, array('aid' => -1), $col, $d);
    return $art;
}
// 彻底删除
function art__delete($aid, $d = NULL)
{
    $r = db_delete('art', array('aid' => $aid), $d);
    return $r;
}
function art__find($cond = array(), $orderby = array(), $page = 1, $pagesize = 20, $key = 'aid', $col = array(), $d = NULL)
{
    $artlist = db_find('art', $cond, $orderby, $page, $pagesize, $key, $col, $d);
    return $artlist;
}
function art_count_day($cond = array(), $d = NULL)
{
    $now = time();
    $start = strtotime(date('Y-m-d 00:00:00', $now));
    $end   = strtotime('+1 day', $start);
    $n= db_count('art', ['create_date' => ['>=' => $start, '<' => $end] ]);
    return $n;
}
function art_count($cond = array(), $d = NULL)
{
    $n = db_count('art', $cond, $d);
    return $n;
}
function art_big_insert($arr = array(), $d = NULL)
{
    $r = db_big_insert('art', $arr, $d);
    return $r;
}
function art_big_update($cond = array(), $update = array(), $d = NULL)
{
    $r = db_big_update('art', $cond, $update, $d);
    return $r;
}
//--------------------------强相关--------------------------
// 仅更新文章表数据和缓存 如更新 tag 等
function art_update($aid, $update)
{
    global $conf;
    if (empty($aid) || empty($update)) return FALSE;
    $r = art__update($aid, $update);
    if (FALSE === $r) return FALSE;
    if ('mysql' != $conf['cache']['type']) {
        if (TRUE === is_array($aid)) {
            foreach ($aid as $_aid) cache_delete('art_' . $_aid);
        } else {
            cache_delete('art_' . $aid);
        }
    }
    return $r;
}
// 更新全部数据
function art_update_all($aid, $update)
{ 
    if (empty($aid) || empty($update)) return FALSE; 
    $r = art_update($aid, $update);
    if (FALSE === $r) return FALSE;  
    return $r;
}
// 遍历栏目aid 按照: 发布时间 倒序，不包含置顶
function art_find_aid($cid, $page = 1, $pagesize = 20)
{
    global $conf, $catelist;
    $key = 'art_find_aid_' . $cid . '_' . $page . '_' . $pagesize;
    static $cache = array();
    if (isset($cache[$key])) return $cache[$key];
    $cate = array_value($catelist, $cid);
    $arts = art_cid_count($cid);
    $desc = TRUE;
    $limitpage = 5000; // 如果需要防止 CC 攻击，可以调整为 5000
    if ($page > 100) {
        $totalpage = ceil($arts / $pagesize);
        $halfpage = ceil($totalpage / 2);
        if ($halfpage > $limitpage && $page < ($totalpage - $limitpage)) {
            $page = $limitpage;
        }
        if ($page > $halfpage) {
            $page = max(1, $totalpage - $page + 1);
            $arr = art_aid_find_by_cid($cid, $page, $pagesize, FALSE);
            $arr = array_reverse($arr, TRUE);
            $desc = FALSE;
        }
    }
    $desc and $arr = art_aid_find_by_cid($cid, $page, $pagesize, TRUE);
    if (empty($arr)) return NULL;
    return $arr;
}
// 按照: rank 倒序，含置顶帖 查询栏目cid下aid 文章数据详情
function art_find_desc($cid, $page = 1, $pagesize = 20)
{
    global $conf, $catelist;
    $key = 'art_find_desc_' . $cid . '_' . $page . '_' . $pagesize;
    static $cache = array();
    if (isset($cache[$key])) return $cache[$key];
    $cate = array_value($catelist, $cid);
    $arts = $cate['arts'];
    $desc = TRUE;
    $limitpage = 5000; // 如果需要防止 CC 攻击，可以调整为 5000
    if ($page > 100) {
        $totalpage = ceil($arts / $pagesize);
        $halfpage = ceil($totalpage / 2);
        if ($halfpage > $limitpage && $page < ($totalpage - $limitpage)) {
            $page = $limitpage;
        }
        if ($page > $halfpage) {
            $page = max(1, $totalpage - $page + 1);
            $arr = art_aid__find(array('cid' => $cid), array('rank' => 1), $page, $pagesize);
            $arr = array_reverse($arr, TRUE);
            $desc = FALSE;
        }
    }
    $desc and $arr = art_aid__find(array('cid' => $cid), array('rank' => -1), $page, $pagesize);
    if (empty($arr)) return NULL;
    return $arr;
}
// 查询用户uid下aid 文章数据详情
function art_find_by_uid($uid, $page = 1, $pagesize = 20)
{
    $key = 'art_find_by_uid_' . $uid . '_' . $page . '_' . $pagesize;
    static $cache = array();
    if (isset($cache[$key])) return $cache[$key];
    $arr = art_aid_find_by_uid($uid, $page, $pagesize);
    if (!$arr) return NULL;
    $aidarr = arrlist_values($arr, 'aid');
    $artlist = art_find($aidarr, $pagesize);
    return $artlist;
}
// aidarr 查询文章数据
// 文章状态0:通过 1~9审核:1待审核 10~19:10退稿 11逻辑删除
function art_find($aidarr, $pagesize = 20, $desc = TRUE)
{ 
    $orderby = TRUE == $desc ? -1 : 1;
    $artlist = art__find(array('aid' => $aidarr), array('aid' => $orderby), 1, $pagesize);
    if (!$artlist) return NULL; 
    $i = 0;
    foreach ($artlist as &$art) {
        ++$i;
        $art['i'] = $i;
        art_format($art);
    }
    return $artlist;
}
// aidarr 查询文章数据 不给mysql增加压力使用正序 倒叙可以使用array_reverse($artlist, TRUE);
// 文章状态0:通过 1~9审核:1待审核 10~19:10退稿 11逻辑删除
function art_find_asc($aidarr, $pagesize = 20)
{ 
    $artlist = art__find(array('aid' => $aidarr), array('aid' => 1), 1, $pagesize);
    if (!$artlist) return NULL; 
    $i = 0;
    foreach ($artlist as $_aid => &$art) {
        ++$i;
        $art['i'] = $i;
        art_format($art);
    }
    return $artlist;
}
function art_find_by_aids($aidarr)
{
    $artlist = art_find($aidarr, 1000);
    return $artlist;
}
// views + 1 大站可以单独剥离出来
function art_inc_views($aid, $n = 1)
{
    global $conf, $db;
    $tablepre = $db->tablepre;
    //if (!$conf['update_views_on']) return TRUE;
    $sqladd = in_array($conf['cache']['type'], array('mysql', 'pdo_mysql')) ? ' LOW_PRIORITY' : '';
    $r = db_exec("UPDATE$sqladd `{$tablepre}art` SET views=views+$n WHERE aid='$aid'");
    'mysql' != $conf['cache']['type'] and cache_update('art_' . $aid, array('views+' => $n), 1800);
    return $r;
}
function art_read($aid)
{
    if (empty($aid)) return NULL;
    static $cache = array();
    if (isset($cache[$aid])) return $cache[$aid];
    $cache[$aid] = art__read(array('aid' => $aid));
    $cache[$aid] and art_format($cache[$aid]);
    return $cache[$aid];
}
// 只删除文章和缓存
function art_delete($aid)
{
    global $conf;
    if (empty($aid)) return FALSE; 
    $r = art__delete($aid);
    if (FALSE === $r) return FALSE;
    if ('mysql' != $conf['cache']['type']) {
        if (is_array($aid)) {
            foreach ($aid as $_aid) cache_delete('art_' . $_aid);
        } else {
            cache_delete('art_' . $aid);
            runtime_set('articles-', 1);
        }
    } 
    return $r;
}


// 删除已经发布成功的主题 单独删除传aid 批量删除传aids array(1,2,3) 
function art_delete_all($aid)
{
    global $gid, $uid, $time, $conf, $config, $catelist;

    if (empty($aid)) return FALSE;
    set_time_limit(0);
    $n = is_array($aid) ? count($aid) : 1;
    $artlist = art__find(array('aid' => $aid), array('aid' => 1), 1, $n, 'aid'); 
    if (empty($artlist)) return FALSE; 
    // 需要删除的aid
    $aids = array(); 
    foreach ($artlist as $art) {
        $aids[] = $art['aid']; 
    }
    if ($aids) { 
        art__delete($aids); 
        art_aid_delete($aids);
    } 
    return TRUE;
} 
// 搜索标题
function art_find_by_keyword($keyword, $d = NULL)
{
    if (empty($keyword)) return NULL; 
    $db = $_SERVER['db'];
    $d = $d ? $d : $db;
    if (!$d) return FALSE; 
    $artlist = db_sql_find("SELECT * FROM `{$d->tablepre}art` WHERE name LIKE '%$keyword%' LIMIT 60;", 'aid', $d); 
    if ($artlist) {
        $artlist = arrlist_multisort($artlist, 'aid', FALSE); 
        foreach ($artlist as &$art) {
            art_format($art); 
        }
    }
    return $artlist;
}
function art_maxid()
{
    $n = db_maxid('art', 'aid'); 
    return $n;
}
// 文章状态 0:通过 1~9 审核:1待审核 10~19:10退稿 11逻辑删除
function art_format(&$art)
{
    global $gid, $uid, $catelist;
    $conf = _SERVER('conf');
    if (empty($art)) return;
    $art['create_date_fmt'] = humandate($art['create_date']); 
    $art['create_date_fmt_ymd'] = date('Y-m-d', $art['create_date']); 
     
    $cate = array_value($catelist, $art['cid']);
    $art['cate_name'] = array_value($cate, 'name');
    $art['cate_url'] = array_value($cate, 'url');
    $route_page_art = $conf['route']['art'];
    $art['url'] = url($route_page_art . '-' . $art['aid'], '', FALSE);
    if ($conf['url_rewrite_on'] > 1) {
        !empty($cate['alias']) and $art['url'] = url(urlencode($cate['alias']) . '-' . $art['aid'], '', FALSE);
    } 
    $art['tag_fmt'] = $art['tag'] ? xn_json_decode($art['tag']) : '';
    if (!empty($art['tag_fmt'])) {
        foreach ($art['tag_fmt'] as $_tag => $_tagname) {
            $tagurl = url('tag-' . $_tag);
            /*功能暂时不启用 tag/pinyin
        if ($conf['url_rewrite_on'] > 1) {
        !empty($cate['alias']) and $art['url'] = url(urlencode($cate['alias']) . '-' . $art['vid'], '', FALSE); 
        }*/
            $tagarr[] = array(
                'name' => $_tagname,
                'url' => $tagurl
            );
        }
        $art['tag_url'] = $tagarr;
    }
    $art = art_safe_info($art);
}
// 对 $artlist 权限过滤
function art_list_access_filter(&$artlist, $gid)
{
    global $catelist;
    if (empty($artlist)) return NULL;
    foreach ($artlist as $aid => $art) {
        if (empty($catelist[$art['cid']]['accesson'])) continue;
        if ($art['sticky'] > 0) continue; 
    }
}
function art_safe_info($art)
{
    unset($art['userip'], $art['user']['arts'], $art['user']['posts'], $art['user']['credits'], $art['user']['golds'], $art['user']['money']);
    empty($art['user']) || $art['user'] = user_safe_info($art['user']);
    return $art;
}
// 过滤安全数据
function art_filter(&$val)
{
    // hook art_filter_start.php
    unset($val['userip'], $val['cid'], $val['type'], $val['user'], $val['create_date']);
    // hook art_filter_end.php
}
//------------------------ 其他方法 ------------------------
// 集合文章aid，统一拉取，避免多次查询art表
function art_unified_pull($arr)
{
    global $gid, $cid; 
    $stickylist = array_value($arr, 'stickylist', array());
    $aidlist = array_value($arr, 'aidlist', array()); 
    $aidarrlist = $aidlist = $stickylist + $aidlist;  
    $aidarr = empty($aidarrlist) ? array() : arrlist_values($aidarrlist, 'aid'); 
    if (empty($aidarr)) return NULL;
    $aidarr = array_unique($aidarr);  
    $arrlist = art_find($aidarr, count($aidarr)); 
    art_list_access_filter($arrlist, $gid);
    $artlist = array();
    foreach ($arrlist as $_aid => &$_art) {
        $_art = art_safe_info($_art); 
        isset($aidlist[$_art['aid']]) and $artlist[$_aid] = $_art; 
    } 
    $artlist = array2_sort_key($artlist, $aidlist, 'aid');
    unset($arrlist, $aidlist);
    $arr = array('artlist' => $artlist); 
    return $arr;
} 
function art_other_pull($art)
{
    global $catelist, $gid;
    $cid = array_value($art, 'cid');
    $cate = array_value($catelist, $cid);
    if (empty($cate)) return NULL; 
    $arrlist = array();
    $aidlist = array(); 
    $aidarr = empty($aidlist) ? array() : arrlist_values($aidlist, 'aid'); 
    if (empty($aidarr)) return NULL;
    $aidarr = array_unique($aidarr); 
    $artlist = art_find($aidarr, count($aidarr)); 
    art_list_access_filter($artlist, $gid);
    foreach ($artlist as &$_art) {
        $_art = art_safe_info($_art); 
    }
    return $arrlist;
}
//--------------------------cache--------------------------
// 已格式化 从缓存中读取，避免重复从数据库取数据
function art_read_cache($aid)
{
    global $conf;
    $key = 'art_' . $aid;
    static $cache = array();  
    if (isset($cache[$key])) return $cache[$key];
    if ('mysql' == $conf['cache']['type']) {
        $r = art_read($aid);
    } else {
        $r = cache_get($key);
        if (NULL === $r) {
            $r = art_read($aid);
            $r and cache_set($key, $r, 1800);
        }
    }
    $cache[$key] = $r ? $r : NULL;
    return $cache[$key];
}
function art_aid__create($arr = array(), $d = NULL)
{
    $r = db_replace('art_aid', $arr, $d);
    return $r;
}
function art_aid__update($cond = array(), $update = array(), $d = NULL)
{
    $r = db_update('art_aid', $cond, $update, $d);
    return $r;
}
function art_aid__read($cond = array(), $orderby = array(), $col = array(), $d = NULL)
{
    $r = db_find_one('art_aid', $cond, $orderby, $col, $d);
    return $r;
}
function art_aid__find($cond = array(), $orderby = array(), $page = 1, $pagesize = 20, $key = 'aid', $col = array(), $d = NULL)
{
    $arr = db_find('art_aid', $cond, $orderby, $page, $pagesize, $key, $col, $d);
    return $arr;
}
function art_aid__delete($cond = array(), $d = NULL)
{
    $r = db_delete('art_aid', $cond, $d);
    return $r;
}
function art_aid__count($cond = array(), $d = NULL)
{
    $n = db_count('art_aid', $cond, $d);
    return $n;
}
function art_aid_big_insert($arr = array(), $d = NULL)
{
    $r = db_big_insert('art_aid', $arr, $d);
    return $r;
}
function art_aid_big_update($cond = array(), $update = array(), $d = NULL)
{
    $r = db_big_update('art_aid', $cond, $update, $d);
    return $r;
}
//--------------------------强相关--------------------------
function art_aid_create($arr)
{
    if (empty($arr)) return FALSE;
    $r = art_aid__create($arr);
    if (FALSE === $r) return FALSE;
    return $r;
}
// 单次查询 aid 正常直接单次查询主表
function art_aid_read($aid)
{
    $r = art_aid__read(array('aid' => $aid));
    return $r;
}
// 主键更新 若移动栏目 则需要更新此表cid
function art_aid_update($aid, $cid)
{
    if (empty($aid) || empty($cid)) return FALSE;
    $r = art_aid__update(array('aid' => $aid), array('cid' => $cid));
    return $r;
}
// 主键更新lastpid
function art_aid_update_lastpid($aid, $lastpid)
{
    if (empty($aid) || empty($lastpid)) return FALSE;
    $r = art_aid__update(array('aid' => $aid), array('lastpid' => $lastpid));
    return $r;
}
// 更新自定义文章排序
function art_aid_update_rank($aid, $rank)
{
    if (empty($aid) || empty($rank)) return FALSE;
    $r = art_aid__update(array('aid' => $aid), array('rank' => $rank));
    return $r;
}
// 遍历所有文章aid
function art_aid_find($page = 1, $pagesize = 20, $desc = TRUE)
{
    $orderby = TRUE == $desc ? -1 : 1;
    $arr = art_aid__find($cond = array(), array('aid' => $orderby), $page, $pagesize, 'aid', array('aid'));
    return $arr;
}
/* 遍历用户所有文章
 * @param $uid 用户ID
 * @param int $page 页数
 * @param int $pagesize 每页记录条数
 * @param bool $desc 排序方式 TRUE降序 FALSE升序
 * @param string $key 返回的数组用那一列的值作为 key
 * @param array $col 查询哪些列
 */
function art_aid_find_by_uid($uid, $page = 1, $pagesize = 1000, $desc = TRUE, $key = 'aid', $col = array())
{
    if (empty($uid)) return array();
    $orderby = TRUE == $desc ? -1 : 1;
    $arr = art_aid__find($cond = array('uid' => $uid), array('aid' => $orderby), $page, $pagesize, $key, $col);
    return $arr;
}
// 遍历栏目下aid 支持数组 $cid = array(1,2,3)
function art_aid_find_by_cid($cid, $page = 1, $pagesize = 1000, $desc = TRUE)
{
    if (empty($cid)) return array();
    $orderby = TRUE == $desc ? -1 : 1;
    $arr = art_aid__find($cond = array('cid' => $cid), array('aid' => $orderby), $page, $pagesize, 'aid', array('aid'));
    return $arr;
}
function art_aid_find_by_cid_top($cid, $page = 1, $pagesize = 1000, $desc = TRUE)
{
    if (empty($cid)) return array();
     
    $arr = db_find('art',$cond = array('cid' => $cid), array('views'=>-1), $page, $pagesize, 'aid', array('aid'));
    return $arr;
}
function art_aid_delete($aid)
{
    if (empty($aid)) return FALSE;
    $r = art_aid__delete(array('aid' => $aid));
    return $r;
}
function art_aid_count()
{
    $n = art_aid__count();
    return $n;
}
// 统计用户文章数 大数量下严谨使用非主键统计
function art_uid_count($uid)
{
    $n = art_aid__count(array('uid' => $uid));
    return $n;
}
// 统计栏目文章数 大数量下严谨使用非主键统计
function art_cid_count($cid)
{
    $n = art_aid__count(array('cid' => $cid));
    return $n;
}
?>