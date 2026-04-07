<?php
$extra = array();
$cid = 0;
$num = $page = $pagesize = $page_url = '';
$page = param(1, 1);
$pagesize = $conf['pagesize'];
$videolist = $vidlist = NULL;
$videos = 0;
$cids = array();
$videos = 0;
if ($cid_count != 0) {
    foreach ($catelist as $key => $val) {
        if (1 == $val['type'] && 1 == $val['nav_display'] && 0 == $val['category']) {
            $cids[] = $val['cid'];
            $videos += $val['videos'];
        }
    }
}
$vidlist = empty($cids) ? array() : video_vid_find_by_cid($cids, $page, $conf['pagesize'], TRUE);
$arr = array('vidlist' => $vidlist);
$arrlist = video_unified_pull($arr);
$videolist = array_value($arrlist, 'videolist');
$aidlist = empty($cids) ? array() : art_aid_find_by_cid($cids, $page, $conf['pagesize'], TRUE);
$art_arr = array('aidlist' => $aidlist);
$art_arrlist = art_unified_pull($art_arr);
$artlist = array_value($art_arrlist, 'artlist');
$linklist = link_get(1, $conf['linksize']);
$page_url = url($route . '-{page}', $extra);
$num = $videos > $pagesize * $conf['listsize'] ? $pagesize * $conf['listsize'] : $videos;
$index_page = index_page($page_url, $num, $page, $pagesize);
$_SESSION['cid'] = $cid;
seo_apply($conf['seo']['index'], [
    'sitename' => $conf['sitename'],
]);
if ($json) {
    $api_json['seo'] = $seo;
    $api_json['num'] = $num;
    $api_json['page'] = $page;
    $api_json['pagesize'] = $pagesize;
    $api_json['page_url'] = $page_url;
    $api_json['linklist'] = $linklist;
    $api_json['videolist'] = $videolist;
    $conf['api_on'] ? message(0, $api_json) : message(0, lang('closed'));
} else {
    include _file(view_load('index'));
}
?>
