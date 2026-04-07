<?php
!defined('DEBUG') and exit('Access Denied.');
$aid = param(1, 0);
$art = 1 == array_value($conf, 'cache_art') ? art_read_cache($aid) : art_read($aid);
$conf['url_rewrite_on'] > 1 && !empty($alias_cid) && $alias_cid != $art['cid'] and http_location($conf['path']);
$cid = $art['cid'];
$cate = isset($catelist[$cid]) ? $catelist[$cid] : NULL;
$cup = $cate['cup'];
if ($cup != 0) {
    $sonlist = $cate_nav[$cup]['sonlist'];
}

$like_state = art_like_check($aid,$uid) ? 'un' : ''; 
$favorite_state = art_favorites_check($aid,$uid) ? 'un' : ''; 

art_inc_views($aid);
art_top_view($aid, $cid);
if ($art['tag']) {
    $tagids = array_keys($art['tag_fmt']);
    $arrlist = tag_video_find($tagids, 1, 100);
    !$arrlist and $arrlist = tag_video__find(array(), array('id' => 1), 1, 100);
    if ($arrlist) {
        $count = count($arrlist);
        $pagesize = 12;
        $total = $count > $pagesize ? $pagesize : $count;
        $tidarr = $count > 20 ? array_rand($arrlist, $total) : array_keys($arrlist);
        $likelist =  art_find_asc($tidarr, $total);
    }
}
seo_apply($conf['seo']['art'], [
    'name' => $art['name'],
    'des' => xn_html_safe(filter_all_html(xn_substr($art['content'], 0, 120))),
    'cate_name' => $cate['name'],
    'sitename' => $conf['sitename'],
]);
$_SESSION['cid'] = $cid;
if ($json) {
    $api_json['cid'] = $cid;
    $api_json['cate_name'] = $cate['name'];
    $api_json['cate_url'] = $cate['url'];
    $api_json['seo'] = $seo;
    $api_json['art'] = $art;
    $api_json['likelist'] = $likelist;
    $conf['api_on'] ? message(0, $api_json) : message(0, lang('closed'));
} else {
    include _file(view_load('art', $cid));
}
?>