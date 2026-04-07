<?php
$tablepre = $db->tablepre;
function top_get_ip()
{
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    }
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    }
    if (!empty($_SERVER['REMOTE_ADDR'])) {
        return $_SERVER['REMOTE_ADDR'];
    }
    return '';
}
function vod_top_view($vid, $cid)
{
    global $time, $tablepre;
    if (empty($vid)) return false;
    $ipstr = top_get_ip();
    $ips = ip2long($ipstr);
    if ($ips === false) $ips = 0;
    if ($ips < 0) $ips = sprintf("%u", $ips);
    $sql = "SELECT * FROM {$tablepre}vod_top_ip 
        WHERE vid = $vid 
          AND ip = $ips 
        ORDER BY create_date DESC 
        LIMIT 1";
    $exist = db_sql_find_one($sql);
    if ($exist && $time - intval($exist['create_date']) < 60) {
        return false;
    }
    db_insert('vod_top', [
        'vid' => $vid,
        'cid' => $cid,
        'create_date' => $time,
    ]);
    if ($exist) {
        db_update('vod_top_ip', ['id' => $exist['id']], [
            'create_date' => $time,
        ]);
    } else {
        db_insert('vod_top_ip', [
            'vid' => $vid,
            'ip'  => $ips,
            'create_date' => $time,
        ]);
    }
    return true;
}
function art_top_view($aid, $cid)
{
    global $time, $tablepre;
    if (empty($aid)) return false;
    $ipstr = top_get_ip();
    $ips = ip2long($ipstr);
    if ($ips === false) $ips = 0;
    if ($ips < 0) $ips = sprintf("%u", $ips);
    $sql = "SELECT * FROM {$tablepre}art_top_ip 
        WHERE aid = $aid 
          AND ip = $ips 
        ORDER BY create_date DESC 
        LIMIT 1";
    $exist = db_sql_find_one($sql);
    if ($exist && $time - intval($exist['create_date']) < 60) {
        return false;
    }
    db_insert('art_top', [
        'aid' => $aid,
        'cid' => $cid,
        'create_date' => $time,
    ]);
    if ($exist) {
        db_update('art_top_ip', ['id' => $exist['id']], [
            'create_date' => $time,
        ]);
    } else {
        db_insert('art_top_ip', [
            'aid' => $aid,
            'ip'  => $ips,
            'create_date' => $time,
        ]);
    }
    return true;
}
function vod_get($type = 'd', $limit = 20)
{
    global $time, $tablepre;
    if ($type == 'd') {
        $start = strtotime('today');
    } elseif ($type == 'w') {
        $start = strtotime('monday this week');
    } elseif ($type == 'm') {
        $start = strtotime(date('Y-m-01'));
    } else {
        $start = 0;
    }
    $sql = "SELECT vid, COUNT(*) AS cnt 
            FROM {$tablepre}vod_top 
            WHERE create_date >= $start 
            GROUP BY vid 
            ORDER BY cnt DESC 
            LIMIT $limit";

    return db_sql_find($sql);
}
function art_get($type = 'd', $limit = 20)
{
    global $time, $tablepre;
    if ($type == 'd') {
        $start = strtotime('today');
    } elseif ($type == 'w') {
        $start = strtotime('monday this week');
    } elseif ($type == 'm') {
        $start = strtotime(date('Y-m-01'));
    } else {
        $start = 0;
    }
    $sql = "SELECT aid, COUNT(*) AS cnt     
            FROM {$tablepre}art_top 
            WHERE create_date >= $start
            GROUP BY aid 
            ORDER BY cnt DESC 
            LIMIT $limit";
    return db_sql_find($sql);
}
function art_get_cid($type = 'd', $cid = 0, $limit = 20)
{
    global $time, $tablepre;
    if ($type == 'd') {
        $start = strtotime('today');
    } elseif ($type == 'w') {
        $start = strtotime('monday this week');
    } elseif ($type == 'm') {
        $start = strtotime(date('Y-m-01'));
    } else {
        $start = 0;
    }
    $sql = "SELECT aid, COUNT(*) AS cnt 
            FROM {$tablepre}art_top 
            WHERE create_date >= $start    
            AND cid = $cid
            GROUP BY  aid 
            ORDER BY cnt DESC 
            LIMIT $limit";
    return db_sql_find($sql);
}

function vod_get_cid($type = 'd', $cid = 0, $limit = 20)
{
    global $time, $tablepre;
    if ($type == 'd') {
        $start = strtotime('today');
    } elseif ($type == 'w') {
        $start = strtotime('monday this week');
    } elseif ($type == 'm') {
        $start = strtotime(date('Y-m-01'));
    } else {
        $start = 0;
    }
    $sql = "SELECT vid, COUNT(*) AS cnt 
            FROM {$tablepre}vod_top 
            WHERE create_date >= $start    
            AND cid = $cid
            GROUP BY vid 
            ORDER BY cnt DESC 
            LIMIT $limit";
    return db_sql_find($sql);
}

/*function vod_hit_cid($type = 'hits_day', $cid = 0, $limit = 20) {
    global $time, $tablepre;
    
    if ($type == 'hits_day') {
        $start = strtotime('today');
    } elseif ($type == 'hits_week') {
        // 改成最近7天，而不是“本周一”
        $start = $time - 7 * 86400;
    } elseif ($type == 'hits_month') {
        $start = $time - 30 * 86400;
    } else {
        $start = 0;
    }

    $sql = "SELECT vid, COUNT(*) AS cnt 
            FROM {$tablepre}vod_top 
            WHERE create_date >= $start    
            AND cid = $cid
            GROUP BY vid 
            ORDER BY cnt DESC 
            LIMIT $limit";

    return db_sql_find($sql);
    
}*/
//兼容cate分类的
/*function vod_hit_cid($type = 'hits_day', $cid = 0, $limit = 20) {
    global $time, $tablepre;

    // 时间段
    if ($type == 'hits_day') {
        $start = strtotime('today');
    } elseif ($type == 'hits_week') {
        $start = $time - 7 * 86400;
    } elseif ($type == 'hits_month') {
        $start = $time - 30 * 86400;
    } else {
        $start = 0;
    }

    // 分类过滤
    if (is_array($cid)) {
        $cid = array_filter(array_map('intval', $cid));
        if (empty($cid)) return array(); // 防止空数组 SQL 报错
        $cid_in = implode(',', $cid);
        $where_cid = "AND cid IN ($cid_in)";
    } else {
        $cid = intval($cid);
        $where_cid = "AND cid = $cid";
    }

    // 查询
    $sql = "SELECT vid, COUNT(*) AS cnt 
            FROM {$tablepre}vod_top 
            WHERE create_date >= $start
            $where_cid
            GROUP BY vid 
            ORDER BY cnt DESC 
            LIMIT $limit";

    return db_sql_find($sql);
}
*/

//可翻页的分类热门视频 
function vod_hit_cid($type = 'hits_day', $cid = 0, $page = 1, $pagesize = 20)
{
    global $time, $tablepre;

    // 计算时间起点
    if ($type == 'hits_day') {
        $start = strtotime('today');
    } elseif ($type == 'hits_week') {
        $start = $time - 7 * 86400;
    } elseif ($type == 'hits_month') {
        $start = $time - 30 * 86400;
    } else {
        $start = 0;
    }

    // 处理 CID
    if (is_array($cid)) {
        $cid = array_filter(array_map('intval', $cid));
        if (empty($cid)) return array('list' => array(), 'total' => 0);
        $cid_in = implode(',', $cid);
        $where_cid = "AND cid IN ($cid_in)";
    } else {
        $cid = intval($cid);
        $where_cid = "AND cid = $cid";
    }

    // 分页计算
    $page = max(1, intval($page));
    $pagesize = max(1, intval($pagesize));
    $start_row = ($page - 1) * $pagesize;

    // 统计总数
    $count_sql = "SELECT COUNT(DISTINCT vid) AS total 
                  FROM {$tablepre}vod_top 
                  WHERE create_date >= $start
                  $where_cid";
    $count_row = db_sql_find_one($count_sql);
    $total = isset($count_row['total']) ? intval($count_row['total']) : 0;

    // 查询当前页数据
    $sql = "SELECT vid, COUNT(*) AS cnt 
            FROM {$tablepre}vod_top 
            WHERE create_date >= $start
            $where_cid
            GROUP BY vid 
            ORDER BY cnt DESC 
            LIMIT $start_row, $pagesize";

    $list = db_sql_find($sql);

    // 返回分页结果
    return array(
        'list'  => $list,
        'total' => $total,
        'page'  => $page,
        'pagesize' => $pagesize
    );
}

function art_hit_cid($type = 'hits_day', $cid = 0, $page = 1, $pagesize = 20)
{
    global $time, $tablepre;

    // 计算时间起点
    if ($type == 'hits_day') {
        $start = strtotime('today');
    } elseif ($type == 'hits_week') {
        $start = $time - 7 * 86400;
    } elseif ($type == 'hits_month') {
        $start = $time - 30 * 86400;
    } else {
        $start = 0;
    }

    // 处理 CID
    if (is_array($cid)) {
        $cid = array_filter(array_map('intval', $cid));
        if (empty($cid)) return array('list' => array(), 'total' => 0);
        $cid_in = implode(',', $cid);
        $where_cid = "AND cid IN ($cid_in)";
    } else {
        $cid = intval($cid);
        $where_cid = "AND cid = $cid";
    }

    // 分页计算
    $page = max(1, intval($page));
    $pagesize = max(1, intval($pagesize));
    $start_row = ($page - 1) * $pagesize;

    // 统计总数
    $count_sql = "SELECT COUNT(DISTINCT aid) AS total 
                  FROM {$tablepre}art_top 
                  WHERE create_date >= $start
                  $where_cid";
    $count_row = db_sql_find_one($count_sql);
    $total = isset($count_row['total']) ? intval($count_row['total']) : 0;

    // 查询当前页数据
    $sql = "SELECT aid, COUNT(*) AS cnt 
            FROM {$tablepre}art_top 
            WHERE create_date >= $start
            $where_cid
            GROUP BY aid    
            ORDER BY cnt DESC 
            LIMIT $start_row, $pagesize";

    $list = db_sql_find($sql);

    // 返回分页结果
    return array(
        'list'  => $list,
        'total' => $total,
        'page'  => $page,
        'pagesize' => $pagesize
    );
}

/*function art_hit_cid($type = 'hits_day', $cid = 0, $limit = 20) {
    global $time, $tablepre;
    
    if ($type == 'hits_day') {
        $start = strtotime('today');
    } elseif ($type == 'hits_week') {
        // 改成最近7天，而不是“本周一”
        $start = $time - 7 * 86400;
    } elseif ($type == 'hits_month') {
        $start = $time - 30 * 86400;
    } else {
        $start = 0;
    }

    $sql = "SELECT aid, COUNT(*) AS cnt 
            FROM {$tablepre}art_top 
            WHERE create_date >= $start    
            AND cid = $cid
            GROUP BY aid 
            ORDER BY cnt DESC 
            LIMIT $limit"; 
    return db_sql_find($sql);
    
}*/
// 清理超过 N 天的 IP 记录
function art_del($days = 30)
{
    global $time;
    $expire = $time - $days * 86400;
    db_delete('art_top_ip', ['create_date' => ['<', $expire]]);
}
function vod_del($days = 30)
{
    global $time;
    $expire = $time - $days * 86400;
    db_delete('vod_top_ip', ['create_date' => ['<', $expire]]);
}
