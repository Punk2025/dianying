<?php
function tag__create($arr = array(), $d = NULL)
{
    $r = db_insert('tag', $arr, $d);
    return $r;
}
function tag__update($cond = array(), $update = array(), $d = NULL)
{
    $r = db_update('tag', $cond, $update, $d);
    return $r;
}
function tag__read($cond = array(), $orderby = array(), $col = array(), $d = NULL)
{
    $r = db_find_one('tag', $cond, $orderby, $col, $d);
    return $r;
}
function tag__find($cond = array(), $orderby = array(), $page = 1, $pagesize = 20, $key = 'tagid', $col = array(), $d = NULL)
{
    $arr = db_find('tag', $cond, $orderby, $page, $pagesize, $key, $col, $d);
    return $arr;
}
function tag__delete($tagid, $d = NULL)
{
    $r = db_delete('tag', array('tagid' => $tagid), $d);
    return $r;
}
function tag__count($cond = array(), $d = NULL)
{
    $n = db_count('tag', $cond, $d);
    return $n;
}
function tag_max_tagid($col = 'tagid', $cond = array(), $d = NULL)
{
    $tagid = db_maxid('tag', $col, $cond, $d);
    return $tagid;
}
function tag_big_insert($arr = array(), $d = NULL)
{
    $r = db_big_insert('tag', $arr, $d);
    return $r;
}
function tag_big_update($cond = array(), $update = array(), $d = NULL)
{
    $r = db_big_update('tag', $cond, $update, $d);
    return $r;
}
function tag_video_create($arr, $d = NULL)
{
    if (empty($arr)) return FALSE;
    $r = db_replace('tag_id', $arr, $d);
    return $r;
}
function tag_video__update($cond = array(), $update = array(), $d = NULL)
{
    $r = db_update('tag_id', $cond, $update, $d);
    return $r;
}
function tag_video_delete($id, $d = NULL)
{
    if (empty($id)) return FALSE;
    $r = db_delete('tag_id', array('id' => $id), $d);
    if (FALSE === $r) return FALSE;
    return $r;
}
function tag_video__find($cond = array(), $orderby = array(), $page = 1, $pagesize = 20, $key = 'vid', $col = array(), $d = NULL)
{
    $arr = db_find('tag_id', $cond, $orderby, $page, $pagesize, $key, $col, $d);
    return $arr;
}
function tag_video__count($cond = array(), $d = NULL)
{
    $n = db_count('tag_id', $cond, $d);
    return $n;
}
function tag_video_max_id($col = 'id', $cond = array(), $d = NULL)
{
    $id = db_maxid('tag_id', $col, $cond, $d);
    return $id;
}
function tag_video_big_insert($arr = array(), $d = NULL)
{
    $r = db_big_insert('tag_id', $arr, $d);
    return $r;
}
function tag_video_big_update($cond = array(), $update = array(), $d = NULL)
{
    $r = db_big_update('tag_id', $cond, $update, $d);
    return $r;
}
//--------------------------强相关--------------------------
function tag_create($arr)
{
    if (empty($arr)) return FALSE;
    $r = tag__create($arr);
    return $r;
}
// 标签名查询
function tag_read_name($name)
{
    $r = tag__read(array('name' => $name));
    $r and tag_format($r);
    return $r;
}
// 标签tagid查询
function tag_read_tagid($tagid)
{
    $r = tag__read(array('tagid' => $tagid));
    $r and tag_format($r);
    return $r;
}
function tag_update($tagid, $update)
{
    global $conf;
    if (empty($tagid) || empty($update)) return FALSE;
    $r = tag__update(array('tagid' => $tagid), $update);
    if (FALSE === $r) return FALSE;
    if ('mysql' != $conf['cache']['type']) {
        if (is_array($tagid)) {
            foreach ($tagid as $_tagid) {
                cache_delete('web_tag_' . $_tagid);
            }
        } else {
            cache_delete('web_tag_' . $tagid);
        }
    }
    return $r;
}
function tag_find($page, $pagesize, $desc = TRUE)
{
    $orderby = TRUE == $desc ? -1 : 1;
    $arrlist = tag__find(array(), array('tagid' => $orderby), $page, $pagesize);
    if (empty($arrlist)) return NULL;
    $i = 0;
    foreach ($arrlist as &$val) {
        ++$i;
        $val['i'] = $i;
        tag_format($val);
    }
    return $arrlist;
}
function tag_find_by_tagids($tagids, $page, $pagesize)
{
    $arrlist = tag__find(array('tagid' => $tagids), array('tagid' => 1), $page, $pagesize);
    if (empty($arrlist)) return NULL;
    $i = 0;
    foreach ($arrlist as &$val) {
        ++$i;
        $val['i'] = $i;
        tag_format($val);
    }
    return $arrlist;
}
function tag_count()
{
    $n = tag__count();
    return $n;
}
function tag_delete($tagid)
{
    global $conf;
    if (empty($tagid)) return FALSE;
    $read = tag_read_tagid($tagid);
    if (empty($read)) return FALSE;
    $r = tag__delete($tagid);
    if (FALSE === $r) return FALSE;
    if ('mysql' != $conf['cache']['type']) {
        $key = 'web_tag_' . $tagid;
        cache_delete($key);
        $key = 'web_tag_' . md5($read['name']);
        cache_delete($key);
    }
    return $r;
}
function tag_format(&$val)
{
    global $conf;
    if (empty($val)) return; 
    $val['url'] = url($conf['route']['tag'].'-'. $val['tagid'], '', FALSE); 
}
//--------------------------cache--------------------------
function tag_read_by_tagid_cache($tagid)
{
    global $conf;
    $key = 'web_tag_' . $tagid;
    static $cache = array();
    if (isset($cache[$key])) return $cache[$key];
    if ('mysql' == $conf['cache']['type']) {
        $r = tag_read_tagid($tagid);
    } else {
        $r = cache_get($key);
        if (NULL === $r) {
            $r = tag_read_tagid($tagid);
            $r and cache_set($key, $r, 1800);
        }
    }
    $cache[$key] = $r ? $r : NULL;
    return $cache[$key];
}
function tag_read_by_name_cache($name)
{
    global $conf;
    if (empty($name)) return NULL;
    $key = 'web_tag_' . md5($name);
    static $cache = array();
    if (isset($cache[$key])) return $cache[$key];
    if ('mysql' == $conf['cache']['type']) {
        $r = tag_read_name($name);
    } else {
        $r = cache_get($key);
        if (NULL === $r) {
            $r = tag_read_name($name);
            $r and cache_set($key, $r, 1800);
        }
    }
    $cache[$key] = $r ? $r : NULL;
    return $cache[$key];
}
//--------------其他方法-------------
// 标签预处理 一般出入的是数组
function tag_post($vid, $cid, $str)
{
    if (empty($str)) return '';
    $arr = explode(',', $str);
    $arr = array_filter($arr);
    if (empty($arr)) return '';
    $arr = array_unique($arr);
    // $tags中的tagid和帖子vid入库 创建帖子时json入库主题附表
    return tag_process($vid, $cid, $arr);
}
// 修改内容标签预处理 $newtag数组, $oldtag旧的json数据
function tag_post_update($vid, $cid, $newtag, $oldtag)
{
    // 如果旧的tag为空 直接创建新标签
    if (empty($oldtag)) return tag_post($vid, $cid, $newtag);
    // json旧标签
    if (!is_array($oldtag)) {
        $oldtag = xn_json_decode($oldtag);
        is_array($oldtag) || $oldtag = array();
    }
    $newtag = explode(',', $newtag);
    $newtag = array_filter($newtag);
    $newtag = array_unique($newtag);
    //if (empty($newtag)) return '';
    $create_tag = array();
    $tagarr = array();
    if (!empty($newtag)) {
        foreach ($newtag as $tagname) {
            // 搜索数组键值，并返回对应的键名
            $tagname = filter_all_html($tagname);
            $key = array_search($tagname, $oldtag);
            if (FALSE === $key) {
                // 创建新数组$new_tags
                $create_tag[] = $tagname;
            } else {
                // 保留的旧标签
                $tagarr[$key] = $tagname;
                // 销毁旧数组保留的标签 余下为需要删除的标签
                unset($oldtag[$key]);
            }
        }
    }
    if (!empty($oldtag)) {
        $tagids = array();
        foreach ($oldtag as $tagid => $tagname) {
            $tagids[] = $tagid;
        }
        oldtag_delete($tagids, $vid);
    }
    $r = tag_process($vid, $cid, $create_tag, $tagarr);
    return $r;
}
// 删除标签和绑定的主题
function oldtag_delete($tagids, $vid)
{
    $pagesize = count($tagids);
    $arrlist = tag_find_by_tagids($tagids, 1, $pagesize);
    $delete_tagids = array(); // 删除
    $tagids = array();
    $n = 0;
    foreach ($arrlist as $val) {
        ++$n;
        if (1 == $val['count']) {
            // 只有一个主题
            $delete_tagids[] = $val['tagid'];
        } else {
            $tagids[] = $val['tagid'];
        }
    }
    !empty($delete_tagids) and tag_delete($delete_tagids);
    $arlist = tag_video_find_by_vid($vid, 1, $n);
    if ($arlist) {
        $ids = array();
        foreach ($arlist as $val) $ids[] = $val['id'];
        tag_video_delete($ids);
    }
    !empty($tagids) and tag_update($tagids, array('count-' => 1));
}
// 标签数据处理 $arr=新提交的数组 $tagarr=保留的旧标签
function tag_process($vid, $cid, $new_tags = array(), $tagarr = array())
{
     $conf = _SERVER('conf');
    if (empty($vid)) return '';
    // 新标签处理入库
    if ($new_tags) {
        $videoarr = array();
        $tagids = array();
        $i = 0;
        $size = $conf['tag_num'];
        $n = count($tagarr);
        $n = $n > $size ? $size : $size - $n;
        foreach ($new_tags as $name) {
            ++$i;
            $name = trim($name);
            $name = stripslashes($name);
            $name = strip_tags($name);
            $name = str_replace(array('&nbsp;', '#', "@", "$", "%", "^", '&', '·', '<', '>', '；', '`', '~', '!', '￥', '……', ';', '?', '？', '-', '—', '_', '=', '+', '.', '{', '}', '|', ':', '：', '、', '/', '。', '[', ']', '【', '】', '‘', '	', '    ', '  ', '   ', '    '), '', $name);
            $name = htmlspecialchars($name, ENT_QUOTES);
            if ($name && $i <= $n) {
                // 查询标签
                $read = tag_read_name($name);
                if ($read) {
                    // 存在 count+1
                    $tagids[] = $read['tagid'];
                } else {
                    // 入库
                    $arr = array('name' => $name, 'count' => 1);
                    $tagid = tag_create($arr);
                    FALSE === $tagid and message(-1, lang('create_failed'));
                    $read = array('tagid' => $tagid, 'name' => $name);
                }
                $tag_id = array('tagid' => $read['tagid'], 'vid' => $vid);
                $videoarr[] = $tag_id;
                $tagarr[$read['tagid']] = $read['name'];
            }
        }
        !empty($videoarr) and tag_video_big_insert($videoarr);
        !empty($tagids) and tag_update($tagids, array('count+' => 1));
    }
    $json = empty($tagarr) ? '' : xn_json_encode($tagarr);
    return $json;
}
function tag_video_update($id, $update)
{
    if (empty($id)) return FALSE;
    $r = tag_video__update(array('id' => $id), $update);
    return $r;
}
function tag_video_find($tagid, $page, $pagesize)
{
    $arr = tag_video__find(array('tagid' => $tagid), array('id' => -1), $page, $pagesize);
    return $arr;
}
function tag_video_find_by_vid($vid, $page, $pagesize)
{
    $arr = tag_video__find(array('vid' => $vid), array(), $page, $pagesize);
    return $arr;
}
?>