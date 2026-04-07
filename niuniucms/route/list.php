<?php
!defined('DEBUG') and exit('Access Denied.');
$cid = param(1, 0);
empty($cid) and message(1, lang('data_malformation'));
$page = param(2, 1);
$extra = array();
$cate = cate_read($cid);
$cup = $cate['cup'];
if ($cup != 0) {
    $sonlist = $cate_nav[$cup]['sonlist'];
}
$pagesize = empty($cate['pagesize']) ? $conf['pagesize'] : $cate['pagesize'];
$hit = param(3);
$hit_types = array(
    'new'        => '最新',
    'hits'       => '总浏览',
    'hits_day'   => '今日浏览',
    'hits_week'  => '本周浏览',
    'hits_month' => '本月浏览'
);
$base = ($conf['url_rewrite_on'] > 1 && !empty($cate['alias']))
    ? xn_urlencode($cate['alias'])
    : $route_list . '-' . $cid . '-' . $page;
$hits_url = array();
foreach ($hit_types as $key => $name) {
    $hits_url[$key] = array(
        'name' => $name,
        'url'  => $key == 'new'
            ? url($base)
            : url($base . '-' . $key)
    );
}
$allow_hits = array('hits', 'hits_day', 'hits_week', 'hits_month');
$hit_name = isset($hit_types[$hit]) ? $hit_types[$hit] : '最新';
switch ($cate['model']) {
    case '1':
        $art_list_from_default = 1;
        if (1 == $art_list_from_default) {
            $orderby = FALSE;
            FALSE === $orderby and $aidlist = art_find_aid($cid, $page, $pagesize);
        }
        $arr = array('aidlist' => $aidlist);
        $arrlist = art_unified_pull($arr);
        $artlist = array_value($arrlist, 'artlist');
        $page_url = url($route_list . '-' . $cid . '-{page}', $extra);
        $page_baseurl=url($route_list . '-' . $cid . '-currentPage', $extra);
        $acc = art_cid_count($cid);
        $num = $acc > $pagesize * $conf['listsize'] ? $pagesize * $conf['listsize'] : $acc;
        if ($conf['url_rewrite_on'] > 1 && $cate['alias']) {
            $page_url = url(xn_urlencode($cate['alias']) . '-list_{page}', $extra);
            $page_baseurl=url(xn_urlencode($cate['alias']) . '-list_currentPage', $extra);
        }
        $index_page = index_page($page_url, $num, $page, $pagesize);
        $page_list = page_array($page_url, $num, $page, $pagesize);
        if ($hit && in_array($hit, $allow_hits, true)) {

            $page_url = url($route_list . '-' . $cid . '-{page}-' . $hit, $extra);
            $page_baseurl=url($route_list . '-' . $cid . '-currentPage-' . $hit, $extra);
            if ($conf['url_rewrite_on'] > 1 && $cate['alias']) {
                $page_url = url(xn_urlencode($cate['alias']) . '-list_{page}-' . $hit, $extra);
                $page_baseurl=url(xn_urlencode($cate['alias']) . '-list_currentPage-' . $hit, $extra);
            }
            if ($hit == 'hits_day' || $hit == 'hits_week' || $hit == 'hits_month') {
                $aidarr = art_hit_cid($hit, $cid, $page, $pagesize);
                $num = $aidarr['total'];
                if (!empty($aidarr['list'])) {
                    $aids = array_column($aidarr['list'], 'aid');
                    $artlist = art_find_asc($aids, $pagesize);
                    $artlist = arrlist_multisort($artlist, 'views', FALSE);
                }
            }
            $index_page = index_page($page_url, $num, $page, $pagesize);
            $page_list = page_array($page_url, $num, $page, $pagesize);
            if ($hit == 'hits') {
                $r = db_find('art', array('cid' => $cid), array('views' => -1), $page, $pagesize);
                if ($r) {
                    $i = 0;
                    foreach ($r as &$art) {
                        $i++;
                        art_format($art);
                        $art['n'] = $i;
                    }
                    $artlist = $r;
                }
            }
        }
        $url_page = url_page();
        $_SESSION['cid'] = $cid;
        $_SESSION['num'] = $num;
        $_SESSION['page'] = $page;
        $_SESSION['pagesize'] = $pagesize;
        $_SESSION['page_url'] = $page_url;
        seo_apply($conf['seo']['art_list'], [
            'name' => $cate['name'],
            'page' => $page,
            'sitename' => $conf['sitename'],
        ]);
        if ($json) {
            $api_json['cid'] = $cid;
            $api_json['cate_name'] = $cate['name'];
            $api_json['cate_url'] = $cate['url'];
            $api_json['seo'] = $seo;
            $api_json['num'] = $num;
            $api_json['page'] = $page;
            $api_json['pagesize'] = $pagesize;
            $api_json['page_url'] = $page_url;
            $api_json['artlist'] = $artlist;
            $conf['api_on'] ? message(0, $api_json) : message(0, lang('closed'));
        } else {
            include _file(view_load('art_list', $cid));
        }
        break;
    default:
        //视频
        $cup = $cate['cup'];
        if ($cup != 0) {
            $sonlist = $cate_nav[$cup]['sonlist'];
        }
        $req_area = $req_year = $req_lang = '';
        $type_urls = type_urls($cid, $conf, $areaarray, $yeararray, $langarray, $req_area, $req_lang, $req_year);
        $video_list_from_default = 1;
        if (1 == $video_list_from_default) {
            $orderby = FALSE;
            FALSE === $orderby and $vidlist = video_find_vid($cid, $page, $pagesize);
        }
        $arr = array('vidlist' => $vidlist);
        $arrlist = video_unified_pull($arr);
        $videolist = array_value($arrlist, 'videolist');
        $page_url = url($route_list . '-' . $cid . '-{page}', $extra);
        $page_baseurl = url($route_list . '-' . $cid . '-currentPage', $extra);
        $vcc = video_cid_count($cid);
        $num = $vcc > $pagesize * $conf['listsize'] ? $pagesize * $conf['listsize'] : $vcc;
        if ($conf['url_rewrite_on'] > 1 && $cate['alias']) {
            $page_url = url(xn_urlencode($cate['alias']) . '-list_{page}', $extra);
            $page_baseurl= url(xn_urlencode($cate['alias']) . '-list_currentPage', $extra);
        }
        $index_page = index_page($page_url, $num, $page, $pagesize);
        $page_list = page_array($page_url, $num, $page, $pagesize);
        if ($hit && in_array($hit, $allow_hits, true)) {

            $page_url = url($route_list . '-' . $cid . '-{page}-' . $hit, $extra);
            $page_baseurl=url($route_list . '-' . $cid . '-currentPage-' . $hit, $extra);
            if ($conf['url_rewrite_on'] > 1 && $cate['alias']) {
                $page_url = url(xn_urlencode($cate['alias']) . '-list_{page}-' . $hit, $extra);
                $page_baseurl=url(xn_urlencode($cate['alias']) . '-list_currentPage-' . $hit, $extra);
            }
            if ($hit == 'hits_day' || $hit == 'hits_week' || $hit == 'hits_month') {
                $vidarr = vod_hit_cid($hit, $cid, $page, $pagesize);
                $num = $vidarr['total'];
                if (!empty($vidarr['list'])) {
                    $vids = array_column($vidarr['list'], 'vid');
                    $videolist = video_find_asc($vids, $pagesize);
                    $videolist = arrlist_multisort($videolist, 'views', FALSE);
                }
            }
            $index_page = index_page($page_url, $num, $page, $pagesize);
            $page_list = page_array($page_url, $num, $page, $pagesize);
            if ($hit == 'hits') {
                $r = db_find('vod', array('cid' => $cid), array('views' => -1), $page, $pagesize);
                if ($r) {
                    $i = 0;
                    foreach ($r as &$video) {
                        $i++;
                        video_format($video);
                        $video['n'] = $i;
                    }
                    $videolist = $r;
                }
            }
        }
        $url_page = url_page();
        $_SESSION['cid'] = $cid;
        $_SESSION['num'] = $num;
        $_SESSION['page'] = $page;
        $_SESSION['pagesize'] = $pagesize;
        $_SESSION['page_url'] = $page_url;
        seo_apply($conf['seo']['list'], [
            'name' => $cate['name'],
            'page' => $page,
            'sitename' => $conf['sitename'],
        ]);
        if ($json) {
            $api_json['cid'] = $cid;
            $api_json['cate_name'] = $cate['name'];
            $api_json['cate_url'] = $cate['url'];
            $api_json['seo'] = $seo;
            $api_json['num'] = $num;
            $api_json['page'] = $page;
            $api_json['pagesize'] = $pagesize;
            $api_json['page_url'] = $page_url;
            $api_json['videolist'] = $videolist;
            $conf['api_on'] ? message(0, $api_json) : message(0, lang('closed'));
        } else {
            include _file(view_load('list', $cid));
        }
        break;
}
?>