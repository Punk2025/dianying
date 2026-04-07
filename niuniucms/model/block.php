<?php
function block_cate_cids($arr)
{
    global $catelist,$conf;
    $type = empty($arr['type']) ? '' : $arr['type'];
    $cid = empty($arr['cid']) ? '' : $arr['cid'];
    $cids = explode(",", $cid);
    $cids = array_filter_empty($cids);
    $keys = "vod_cate_cid_".$conf['url_rewrite_on']."_".$type  ;
    static $cache = array();
    if (isset($cache[$keys])) return $cache[$keys];
    $r = cache_get($keys);
    if (NULL === $r) {
        $typelist = array();
        foreach ($cids as $_cid) {
            if (isset($catelist[$_cid]) && is_array($catelist[$_cid])) {
                $typelist[] = $catelist[$_cid];
            }
        }
        cache_set($keys, $typelist, 3600);
        return $typelist;
    }
    $cache[$keys] = $r ? $r : NULL;
    return $cache[$keys];
}
function block_vod_index_new($arr)
{
    global $videolist,$conf;
    $pagesize = empty($arr['n']) ? '' : $arr['n'];
    $keys = "vod_vod_new_".$conf['url_rewrite_on']."_".$pagesize;
    static $cache = array();
    if (isset($cache[$keys])) return $cache[$keys];
    $r = cache_get($keys);
    if (NULL === $r) {
        $videolists = array_slice($videolist, 0, $pagesize);
        cache_set($keys, $videolists, 3600);
        return $videolists;
    }
    $cache[$keys] = $r ? $r : NULL;
    return $cache[$keys];
}
function block_vod_list_new($arr)
{
    global $videolist,$conf;
    $cid = $_SESSION['cid'];
    $pagesize = empty($arr['n']) ? '' : $arr['n'];
    $keys = "vod_vod_list_".$conf['url_rewrite_on']."_".$cid."_".$pagesize;   
    static $cache = array();
    if (isset($cache[$keys])) return $cache[$keys];
    $r = cache_get($keys);
    if (NULL === $r) {
        $videolists = array_slice($videolist, 0, $pagesize);
        cache_set($keys, $videolists, 3600);
        return $videolists;
    }
    $cache[$keys] = $r ? $r : NULL;
    return $cache[$keys];
}

function block_vod_top($arr)
{
    global $conf;
    if (empty($arr['cid'])) {
        $cid = array();
        $cids = '';
    } else {
        $cid = array('cid' => $arr['cid']);
        $cids = '_' . $arr['cid'] . '_';
    }
    $n = empty($arr['n']) ? '' : $arr['n'];
    $key = 'vod_top_'.$conf['url_rewrite_on'] .'_'. $n;
    static $cache = array();
    if (isset($cache[$key])) return $cache[$key];
    $r = cache_get($key);
    if (NULL === $r) {
        $r = db_find('vod', $cid, array('views' => -1), 1, $n);
        if ($r) {
            $r = arrlist_multisort($r, 'vid', FALSE);
            $i = 0;
            foreach ($r as &$video) {
                $i++;
                video_format($video);
                $video['n'] = $i;
            }
        }
        $r and cache_set($key, $r, 3600);
    }
    $cache[$key] = $r ? $r : NULL;
    return $cache[$key];
}
function block_vod_rand($arr)
{
    global $conf;
    if (empty($arr['cid'])) {
        $cid = array();
        $cids = '';
    } else {
        $cid = array('cid' => $arr['cid']);
        $cids = '_' . $arr['cid'] . '_';
    }
    $n = empty($arr['n']) ? '' : $arr['n'];
    $key = 'vod_rand_'.$conf['url_rewrite_on']."_".$cids."_".$n;
    static $cache = array();
    if (isset($cache[$key])) return $cache[$key];
    $r = cache_get($key);
    if (NULL === $r) {
        $arrlist = video_vid_find(1, 5000);
        if ($arrlist) {
            $videos = count($arrlist);
            $total = $videos > 20 ? 20 : $videos;
            $vidarr = $videos > 20 ? array_rand($arrlist, $total) : array_keys($arrlist);
            $r = video_find_asc($vidarr, $total);
            $r and cache_set($key, $r, 3600);
        }
    }
    $cache[$key] = $r ? $r : NULL;
    return $cache[$key];
}
function block_vod_list($arr)
{   
    global $conf;
    $cid = $_SESSION['cid'];
    $keys = "vod_vod_list_".$conf['url_rewrite_on']."_".$cid;
    static $cache = array();
    if (isset($cache[$keys])) return $cache[$keys];
    $r = cache_get($keys);
    if (NULL === $r) {
        $pagesize = empty($arr['n']) ? '' : $arr['n'];
        $page = 1;
        $cate = cate_read($cid);
        $vidlist = video_find_vid($cid, $page, $pagesize);
        $arr = array('vidlist' => $vidlist);
        $arrlist = video_unified_pull($arr);
        $videolist['videolist'] = array_value($arrlist, 'videolist');
        $videolist['cate_name'] = $cate['name'];
        $videolist['cate_url'] = $cate['url'];
        $videolist['cid'] = $cate['cid'];
        cache_set($keys, $videolist, 3600);
        return $videolist;
    }
    $cache[$keys] = $r ? $r : NULL;
    return $cache[$keys];
}
function block_vod_list_top($arr)
{
        global $conf;
    $cid = $_SESSION['cid'];
    $keys = "vod_vod_list_top_".$conf['url_rewrite_on']."_".$cid;
    static $cache = array();
    if (isset($cache[$keys])) return $cache[$keys];
    $r = cache_get($keys);
    if (NULL === $r) {
        $pagesize = empty($arr['n']) ? '' : $arr['n'];
        $page = 1;
        $cate = cate_read($cid);

        $vidlist = video_vid_find_by_cid_top($cid, $page, $pagesize);
        $arr = array('vidlist' => $vidlist);
        $arrlist = video_unified_pull($arr);
        $videolist['videolist'] = array_value($arrlist, 'videolist');
        $videolist['cate_name'] = $cate['name'];
        $videolist['cate_url'] = $cate['url'];
        $videolist['cid'] = $cate['cid'];
        cache_set($keys, $videolist, 3600);
        return $videolist;
    }
    $cache[$keys] = $r ? $r : NULL;
    return $cache[$keys];
}
function block_vod_list_rand($arr)
{
        global $conf;
    $cid = $_SESSION['cid'];
    $pagesize = empty($arr['n']) ? '' : $arr['n'];
    $key = "vod_vod_list_rand_".$conf['url_rewrite_on']."_".$cid;
    static $cache = array();
    if (isset($cache[$key])) return $cache[$key];
    $r = cache_get($key);
    if (NULL === $r) {
        $arrlist = video_vid_find(1, 5000);
        if ($arrlist) {
            $videos = count($arrlist);
            $total = $videos > $pagesize ? $pagesize : $videos;
            $vidarr = $videos > 20 ? array_rand($arrlist, $total) : array_keys($arrlist);
            $r = video_find_asc($vidarr, $total);
            $r and cache_set($key, $r, 3600);
        }
    }
    $cache[$key] = $r ? $r : NULL;
    return $cache[$key];
}
function block_vod_cid($arr)
{
    global $catelist, $cate_nav, $conf;
    $cid = empty($arr['cid']) ? '' : $arr['cid'];
    $keys = "vod_vod_cid_".$conf['url_rewrite_on']."_".$cid;
    static $cache = array();
    if (isset($cache[$keys])) return $cache[$keys];
    $r = cache_get($keys);
    if (NULL === $r) {
        $pagesize = empty($arr['n']) ? '' : $arr['n'];
        $page = 1;
        $cate = cate_read($cid);
        $vidlist = video_find_vid($cid, $page, $pagesize);
        $arr = array('vidlist' => $vidlist);
        $arrlist = video_unified_pull($arr);
        $videolist['videolist'] = array_value($arrlist, 'videolist');
        $videolist['cate_name'] = $cate['name'];
        $videolist['cate_url'] = $cate['url'];
        $videolist['cid'] = $cate['cid'];
        cache_set($keys, $videolist, 3600);
        return $videolist;
    }
    $cache[$keys] = $r ? $r : NULL;
    return $cache[$keys];
}
function block_vod_cates($arr)
{
    global $catelist, $cate_nav, $conf;
    $type = empty($arr['type']) ? '' : $arr['type'];
    $keys = "vod_cate_top_".$conf['url_rewrite_on']."_".$type;
    static $cache = array();
    if (isset($cache[$keys])) return $cache[$keys];
    $r = cache_get($keys);
    if (NULL === $r) {
        $res = array();
        $page = 1;
        $pagesize = empty($arr['n']) ? '' : $arr['n'];
        $topsize = empty($arr['top']) ? '' : $arr['top'];
        $res = array();
        foreach ($cate_nav as $key => $val) {
            if ($type == "vod") {

                if ($val['model'] == 0 && $val['category'] == 1) {
                    $cid = $val['cid'];
                    $arrlistall['cate'] = $cate_nav[$cid];
                }
                if (!empty($cid)) {
                    $videolist = NULL;
                    $vidlist = NULL;
                    $videos = 0;
                    $cids = array();
                    if ($catelist) {
                        foreach ($catelist as $key => $val) {
                            if ($val['cup'] == $cid && 1 == $val['type'] && 0 == $val['category'] && $val['model'] == 0) {
                                $cids[] = $val['cid'];
                                $videos += $val['videos'];
                            }
                        }
                        $cate['videos'] = $videos;
                        $vidlist = video_vid_find_by_cid($cids, $page, $pagesize, TRUE);
                        $arr = array('vidlist' => $vidlist);
                        $arrlist = video_unified_pull($arr);
                        $videolist = array_value($arrlist, 'videolist');
                        $topvidlist = video_vid_find_by_cid_top($cids, $page, $topsize, TRUE);
                        $toparr = array('vidlist' => $topvidlist);
                        $toparrlist = video_unified_pull($toparr);
                        $topvideolist = array_value($toparrlist, 'videolist');
                        $i = 0;
                        $topvideolists = array();
                        foreach ($topvideolist as &$ttvideo) {
                            ++$i;
                            $ttvideo['i'] = $i;
                            $topvideolists[] = $ttvideo;
                        }

                        $arrlistall['top'] = $topvideolists;
                        $arrlistall['list'] = $videolist;
                    }
                    $res[$cid] = $arrlistall;
                }
            }
            if ($type == "art") {

                if ($val['model'] == 1 && $val['category'] == 1) {
                    $cid = $val['cid'];
                    $arrlistall['cate'] = $cate_nav[$cid];
                }
                if (!empty($cid)) {
                    $artlist = NULL;
                    $aidlist = NULL;
                    $arts = 0;
                    $cids = array();
                    if ($catelist) {
                        foreach ($catelist as $key => $val) {
                            if ($val['cup'] == $cid && 1 == $val['type'] && 0 == $val['category'] && $val['model'] == 1) {
                                $cids[] = $val['cid'];
                                $arts += $val['arts'];
                            }
                        }
                        $cate['arts'] = $arts;
                        $aidlist = art_aid_find_by_cid($cids, $page, $pagesize, TRUE);
                        $arr = array('aidlist' => $aidlist);
                        $arrlist = art_unified_pull($arr);
                        $artlist = array_value($arrlist, 'artlist');
                        $topaidlist = art_aid_find_by_cid_top($cids, $page, $topsize, TRUE);
                        $toparr = array('aidlist' => $topaidlist);
                        $toparrlist = art_unified_pull($toparr);
                        $topartlist = array_value($toparrlist, 'artlist');
                        $arrlistall['top'] = $topartlist;
                        $arrlistall['list'] = $artlist;
                    }
                    $res[$cid] = $arrlistall;
                }
            }
        }
        cache_set($keys, $res, 3600);
        return $res;
    }
    $cache[$keys] = $r ? $r : NULL;
    return $cache[$keys];
}

function block_vod_cate($arr)
{
    global $catelist, $cate_nav, $conf;
    $cid = empty($arr['cid']) ? '' : $arr['cid'];
    $pagesize = empty($arr['n']) ? '' : $arr['n'];
    $keys = "vod_vod_cate_".$conf['url_rewrite_on']."_".$cid . '_' . $pagesize;
    static $cache = array();
    if (isset($cache[$keys])) return $cache[$keys];
    $r = cache_get($keys);
    if (NULL === $r) {
        $page = 1;
        $videolist = NULL;
        $vidlist = NULL;
        $videos = 0;
        $cids = array();
        if ($catelist) {
            foreach ($catelist as $key => $val) {
                if ($val['cup'] == $cid && 1 == $val['type'] && 0 == $val['category']) {
                    $cids[] = $val['cid'];
                    $videos += $val['videos'];
                }
            }
        }
        $cate['videos'] = $videos;
        $vidlist = video_vid_find_by_cid($cids, $page, $pagesize, TRUE);
        $arr = array('vidlist' => $vidlist);
        $arrlist = video_unified_pull($arr);
        $videolist['videolist'] = array_value($arrlist, 'videolist');
        $topvidlist = video_vid_find_by_cid_top($cids, $page, $pagesize, TRUE);
        $toparr = array('vidlist' => $topvidlist);
        $toparrlist = video_unified_pull($toparr);
        $topvideolist = array_value($toparrlist, 'videolist');
        $i = 0;
        $topvideolists = array();
        foreach ($topvideolist as &$ttvideo) {
            ++$i;
            $ttvideo['i'] = $i;
            $topvideolists[] = $ttvideo;
        }
        $videolist['top'] = $topvideolists;
        $videolist['cate'] = $cate_nav[$cid];
        cache_set($keys, $videolist, 3600);
        return $videolist;
    }
    $cache[$keys] = $r ? $r : NULL;
    return $cache[$keys];
}
function block_poster_rand($arr)
{
    global $conf;
    $keys = "vod_poster_rand_".$conf['url_rewrite_on'];
    static $cache = array();
    if (isset($cache[$keys])) return $cache[$keys];
    $r = cache_get($keys);
    if (NULL === $r) {
        $cid = empty($arr['cid']) ? '' : $arr['cid'];
        $page = empty($arr['p']) ? '' : $arr['p'];
        $pagesize = empty($arr['n']) ? '' : $arr['n'];
        $arrlist = sticky_video_find_cache_rand($cid, $page, $pagesize);
        if (empty($arrlist)) return NULL;
        $arr = array();
        foreach ($arrlist as $val) {
            if (1 == $val['sticky']) {
                $arr[$val['vid']] = $val;
            }
        }
        $arr = array_multisort_key($arr, 'create_date', FALSE, 'vid');
        $sidarr = arrlist_values($arr, 'vid');
        $stickylist = video_find(array_unique($sidarr), count($sidarr));
        cache_set($keys, $stickylist, 3600);
        return $stickylist;
    }
    $cache[$keys] = $r ? $r : NULL;
    return $cache[$keys];
}

function block_cate_type($arr)
{
    global $catelist, $conf;
    $type = empty($arr['type']) ? '' : $arr['type'];
    $keys = "vod_cate_type_".$conf['url_rewrite_on']."_".$type;
    static $cache = array();
    if (isset($cache[$keys])) return $cache[$keys];
    $r = cache_get($keys);
    if (NULL === $r) {
        if ($type == "vod") {
            $arrlist = arrlist_cond_orderby($catelist, array('model' => 0), array(), 1, 1000);
        }
        if ($type == "art") {
            $arrlist = arrlist_cond_orderby($catelist, array('model' => 1), array(), 1, 1000);
        }
        $arrlist = category_tree_format($arrlist);
        $typelist = array_multisort_key($arrlist, 'rank', FALSE, 'cid');
        cache_set($keys, $typelist, 3600);
        return $typelist;
    }
    $cache[$keys] = $r ? $r : NULL;
    return $cache[$keys];
}
//art
function block_art_list($arr)
{
        global $conf;
    $cid = $_SESSION['cid'];
    $keys = "art_art_list_".$conf['url_rewrite_on']."_".$cid;
    static $cache = array();
    if (isset($cache[$keys])) return $cache[$keys];
    $r = cache_get($keys);
    if (NULL === $r) {
        $pagesize = empty($arr['n']) ? '' : $arr['n'];
        $page = 1;
        $cate = cate_read($cid);
        $aidlist = art_find_aid($cid, $page, $pagesize);
        $arr = array('aidlist' => $aidlist);
        $arrlist = art_unified_pull($arr);
        $artlist['artlist'] = array_value($arrlist, 'artlist');
        $artlist['cate_name'] = $cate['name'];
        $artlist['cate_url'] = $cate['url'];
        $artlist['cid'] = $cate['cid'];
        cache_set($keys, $artlist, 3600);
        return $artlist;
    }
    $cache[$keys] = $r ? $r : NULL;
    return $cache[$keys];
}

function block_art_list_top($arr)
{
        global $conf;
    $cid = $_SESSION['cid'];
    $keys = "art_art_list_top_".$conf['url_rewrite_on']."_".$cid;
    static $cache = array();
    if (isset($cache[$keys])) return $cache[$keys];
    $r = cache_get($keys);
    if (NULL === $r) {
        $pagesize = empty($arr['n']) ? '' : $arr['n'];
        $page = 1;
        $cate = cate_read($cid);

        $aidlist = art_aid_find_by_cid_top($cid, $page, $pagesize);
        $arr = array('aidlist' => $aidlist);
        $arrlist = art_unified_pull($arr);
        $artlist['artlist'] = array_value($arrlist, 'artlist');
        $artlist['cate_name'] = $cate['name'];
        $artlist['cate_url'] = $cate['url'];
        $artlist['cid'] = $cate['cid'];
        cache_set($keys, $artlist, 3600);
        return $artlist;
    }
    $cache[$keys] = $r ? $r : NULL;
    return $cache[$keys];
}
function block_art_list_rand($arr)
{
    global $conf;
    $cid = $_SESSION['cid'];
    $pagesize = empty($arr['n']) ? '' : $arr['n'];
    $key = "art_art_list_rand_".$conf['url_rewrite_on']."_".$cid;
    static $cache = array();
    if (isset($cache[$key])) return $cache[$key];
    $r = cache_get($key);
    if (NULL === $r) {
        $arrlist = art_aid_find(1, 5000);
        if ($arrlist) {
            $arts = count($arrlist);
            $total = $arts > $pagesize ? $pagesize : $arts;
            $aidarr = $arts > 20 ? array_rand($arrlist, $total) : array_keys($arrlist);
            $r = art_find_asc($aidarr, $total);
            $r and cache_set($key, $r, 3600);
        }
    }
    $cache[$key] = $r ? $r : NULL;
    return $cache[$key];
}
function block_index_cids($arr)
{
    global $catelist, $conf;
    $type = empty($arr['type']) ? '' : $arr['type'];
    $cid = empty($arr['cid']) ? '' : $arr['cid'];
    $n = empty($arr['n']) ? '' : $arr['n'];
    $cids = explode(",", $cid);
    $cids = array_filter_empty($cids);
    $keys = "vod_index_cids_".$conf['url_rewrite_on']."_".$n."_".$type;
    static $cache = array();
    if (isset($cache[$keys])) return $cache[$keys];
    $r = cache_get($keys);
    if (NULL === $r) {
        $typelist = array();
        foreach ($cids as $_cid) {
            $cid_arr = array(
                'cid' => $_cid,
                'n' => $n,
            );

            $res = block_index_cid($cid_arr);
            $typelist[$_cid] = $res;
        }
        cache_set($keys, $typelist, 3600);
        return $typelist;
    }
    $cache[$keys] = $r ? $r : NULL;
    return $cache[$keys];
}
function block_index_cid($arr)
{
    global $catelist, $cate_nav , $conf;
    $cid = empty($arr['cid']) ? '' : $arr['cid'];
    $pagesize = empty($arr['n']) ? '' : $arr['n'];
    $keys = "vod_index_cid_".$conf['url_rewrite_on']."_".$pagesize."_".$cid;
    static $cache = array();
    if (isset($cache[$keys])) return $cache[$keys];
    $r = cache_get($keys);
    if (NULL === $r) {
        $page = 1;
        $cate = cate_read($cid);
        $vidlist = video_find_vid($cid, $page, $pagesize);
        $arr = array('vidlist' => $vidlist);
        $arrlist = video_unified_pull($arr);
        $videolist['videolist'] = array_value($arrlist, 'videolist');
        $videolist['cate_name'] = $cate['name'];
        $videolist['cate_url'] = $cate['url'];
        $videolist['cid'] = $cate['cid'];
        cache_set($keys, $videolist, 3600);
        return $videolist;
    }
    $cache[$keys] = $r ? $r : NULL;
    return $cache[$keys];
}

function block_vod_dwm($arr)
{
    global $conf;
    $n = empty($arr['n']) ? '' : $arr['n'];
    $type = empty($arr['type']) ? '' : $arr['type'];
    $keys = "vod_" . $type . "_dwm_".$conf['url_rewrite_on']."_".$n;
    static $cache = array();
    if (isset($cache[$keys])) return $cache[$keys];
    $r = cache_get($keys);
    if (NULL === $r) {
        $vidarr = vod_get($type, $n);
        $vids = array_column($vidarr, 'vid');
        $r = video_find_asc($vids, $n);
        cache_set($keys, $r, 3600);
    }
    $cache[$keys] = $r ? $r : NULL;
    return $cache[$keys];
}

function block_art_dwm($arr)
{
    global $conf;
    $n = empty($arr['n']) ? '' : $arr['n'];
    $type = empty($arr['type']) ? '' : $arr['type'];
    $keys = "art_" . $type . "_dwm_".$conf['url_rewrite_on']."_".$n;
    static $cache = array();
    if (isset($cache[$keys])) return $cache[$keys];
    $r = cache_get($keys);
    if (NULL === $r) {
        $artarr = art_get($type, $n);
        $vids = array_column($artarr, 'aid');
        $r = art_find_asc($vids, $n);
        cache_set($keys, $r, 3600);
    }
    $cache[$keys] = $r ? $r : NULL;
    return $cache[$keys];
}

function block_vod_dwm_cid($arr)
{
    global $conf;
    $n = empty($arr['n']) ? '' : $arr['n'];
    $type = empty($arr['type']) ? '' : $arr['type'];
    $cid = empty($arr['cid']) ? '' : $arr['cid'];
    if ($cid == '') $cid = $_SESSION['cid'];
    $keys = "vod_" . $type . "_cid_".$conf['url_rewrite_on']."_".$n . $cid;
    static $cache = array();
    if (isset($cache[$keys])) return $cache[$keys];
    $r = cache_get($keys);
    if (NULL === $r) {
        $vidarr = vod_get_cid($type, $cid, $n);
        $vids = array_column($vidarr, 'vid');
        $r = video_find_asc($vids, $n);
        cache_set($keys, $r, 3600);
    }
    $cache[$keys] = $r ? $r : NULL;
    return $cache[$keys];
}
function block_art_dwm_cid($arr)
{
    global $conf;
    $n = empty($arr['n']) ? '' : $arr['n'];
    $type = empty($arr['type']) ? '' : $arr['type'];
    $cid = empty($arr['cid']) ? '' : $arr['cid'];
    if ($cid == '') $cid = $_SESSION['cid'];
    $keys = "art_" . $type . "_cid_".$conf['url_rewrite_on']."_".$n . $cid;
    static $cache = array();
    if (isset($cache[$keys])) return $cache[$keys];
    $r = cache_get($keys);
    if (NULL === $r) {
        $artarr = art_get_cid($type, $cid, $n);
        $vids = array_column($artarr, 'aid');
        $r = art_find_asc($vids, $n);
        cache_set($keys, $r, 3600);
    }
    $cache[$keys] = $r ? $r : NULL;
    return $cache[$keys];
}
//2025-12-19
function block_type_cid($arr)
//传递 7,8,9,10,11,12
{
    global  $catelist,$conf; 

    $cid = empty($arr['cid']) ? '' : $arr['cid'];
    $cids = explode(",", $cid);
    $cids = array_filter_empty($cids);
    $keys = "type_cid_".$conf['url_rewrite_on']."_".$cid;
    static $cache = array();
    if (isset($cache[$keys])) return $cache[$keys];
    $r = cache_get($keys);
    if (NULL === $r) {
        $typelist = array();
        foreach ($cids as $_cid) {
            //echo $_cid;
            if (isset($catelist[$_cid]) && is_array($catelist[$_cid])) {
                $typelist[] = $catelist[$_cid];
            }
        }
        cache_set($keys, $typelist, 3600);
        return $typelist;
        
    }
    $cache[$keys] = $r ? $r : NULL;
    return $cache[$keys];
}
function block_index_type($arr)
{
    global $conf;
    $cid = empty($arr['cid']) ? '' : $arr['cid'];
    $pagesize = empty($arr['n']) ? '' : $arr['n'];
    $keys = "vod_index_type_".$conf['url_rewrite_on']."_".$pagesize."_".$cid;
    static $cache = array();
    if (isset($cache[$keys])) return $cache[$keys];
    $r = cache_get($keys);
    if (NULL === $r) {
        $page = 1;
        $cate = cate_read($cid);
        $vidlist = video_find_vid($cid, $page, $pagesize);
        $arr = array('vidlist' => $vidlist);
        $arrlist = video_unified_pull($arr);
        $videolist['videolist'] = array_value($arrlist, 'videolist');
        $videolist['cate_name'] = $cate['name'];
        $videolist['cate_url'] = $cate['url'];
        $videolist['cid'] = $cate['cid'];
        cache_set($keys, $videolist, 3600);
        return $videolist;
    }
    $cache[$keys] = $r ? $r : NULL;
    return $cache[$keys];
}

function block_list_type($arr)
{
    global $conf;
    //$cid = empty($arr['cid']) ? '' : $arr['cid'];
    $cid = $_SESSION['cid'];
    $pagesize = empty($arr['n']) ? '' : $arr['n'];
    $keys = "vod_list_type_".$conf['url_rewrite_on']."_".$pagesize."_".$cid;
    static $cache = array();
    if (isset($cache[$keys])) return $cache[$keys];
    $r = cache_get($keys);
    if (NULL === $r) {
        $page = 1;
        $cate = cate_read($cid);
        $vidlist = video_find_vid($cid, $page, $pagesize);
        $arr = array('vidlist' => $vidlist);
        $arrlist = video_unified_pull($arr);
        $videolist['videolist'] = array_value($arrlist, 'videolist');
        $videolist['cate_name'] = $cate['name'];
        $videolist['cate_url'] = $cate['url'];
        $videolist['cid'] = $cate['cid'];
        cache_set($keys, $videolist, 3600);
        return $videolist;
    }
    $cache[$keys] = $r ? $r : NULL;
    return $cache[$keys];
}

?>