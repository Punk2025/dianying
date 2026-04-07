<?php
!defined('DEBUG') and exit('Access Denied.');
$vid = param(1, 0);
$video = 1 == array_value($conf, 'cache_video') ?  video_read_cache($vid) :  video_read($vid);
$conf['url_rewrite_on'] > 1 && !empty($alias_cid) && $alias_cid != $video['cid'] and http_location($conf['path']);
$cid = $video['cid'];
$cate = isset($catelist[$cid]) ? $catelist[$cid] : NULL;
$cup = $cate['cup'];
if ($cup != 0) {
    $sonlist = $cate_nav[$cup]['sonlist'];
}
$like_state = vod_like_check($vid, $uid) ? 'un' : '';
$favorite_state = vod_favorites_check($vid, $uid) ? 'un' : '';
video_inc_views($vid);
vod_top_view($vid, $cid);
//处理快速采集的标签 
if (!$video['tag']) {
    $get_tag = get_tag($video['name']);
    $tags = $video['area'] . "," . $video['lang'] . "," . $video['year'] . ',' . $video['cate_name'] . ',' . $get_tag;
    $tag_json = tag_post($vid, $cid, $tags);
    if (xn_strlen($tag_json) >= 120) {
        $s = xn_substr($tag_json, -1, NULL);
        if ('}' != $s) {
            $len = mb_strripos($tag_json, ',', 0, 'UTF-8');
            $tag_json = $len ? xn_substr($tag_json, 0, $len) . '}' : '';
        }
    }
    !empty($tag_json) and $vod_update['tag'] = $tag_json;
    !empty($vod_update) && FALSE === video_update($vid, $vod_update) and message(-1, lang('update_thread_failed'));
}
if ($video['tag']) {
    $tagids = array_keys($video['tag_fmt']);
    $arrlist = tag_video_find($tagids, 1, 100);
    !$arrlist and $arrlist = tag_video__find(array(), array('id' => 1), 1, 100);
    if ($arrlist) {
        $count = count($arrlist);
        $pagesize = 12;
        $total = $count > $pagesize ? $pagesize : $count;
        $tidarr = $count > 20 ? array_rand($arrlist, $total) : array_keys($arrlist);
        $likelist = video_find_asc($tidarr, $total);
    }
}



$play_urls = $video['play_data'][0]['url'];
seo_apply($conf['seo']['vod'], [
    'name' => $video['name'],
    'lang' => $video['lang'],
    'area' => $video['area'],
    'year' => $video['year'],
    'remarks' => $video['remarks'],
    'des' => xn_html_safe(filter_all_html(xn_substr($video['content'], 0, 120))),
    'cate_name' => $cate['name'],
    'sitename' => $conf['sitename'],
]);
$_SESSION['cid'] = $cid;
if ($json) {
    $api_json['cid'] = $cid;
    $api_json['cate_name'] = $cate['name'];
    $api_json['cate_url'] = $cate['url'];
    $api_json['seo'] = $seo;
    $api_json['video'] = $video;
    $api_json['likelist'] = $likelist;
    $conf['api_on'] ? message(0, $api_json) : message(0, lang('closed'));
} else {
    include _file(view_load('video', $cid));
}
