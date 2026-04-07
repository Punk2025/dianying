<?php
!defined('DEBUG') and exit('Access Denied.');
if ($conf['search_on']==1) {
    $keyword = param('keyword');
    seo_apply($conf['seo']['searchs'], [
    'name' => $keyword.'搜索关闭',
    'num' => 0,
    'sitename' => $conf['sitename'],
]);
$msg='暂不支持搜索';
include _file(view_load('message'));
exit;
}

$keyword = param('keyword');
empty($keyword) and $keyword = param(2);
$keyword = trim($keyword);
$keyword_decode = xn_urldecode($keyword);
$keyword_arr = explode(' ', $keyword_decode);
$videolist = array();
$search_type = 'like';
if ($keyword) {
    if ('like' == $search_type) {
        $videolist = video_find_by_keyword($keyword);
    }
}
$num=count($videolist);
$_SESSION['cid'] = $cid;
seo_apply($conf['seo']['searchs'], [
    'name' => $keyword,
    'num' => $num,
    'sitename' => $conf['sitename'],
]);
if ($json) {
    $api_json['cid'] = $cid;
    $api_json['seo'] = $seo;
    $api_json['count'] = $num;
    $api_json['videolist'] = $videolist;
    $conf['api_on'] ? message(0, $api_json) : message(0, lang('closed'));
} else {
    include _file(view_load('search', $cid));
}
?>