<?php
!defined('DEBUG') and exit('Access Denied.');
$page = param(2, 1);
$type = param(1);
$type = urldecode($type);
$parts = explode('_', $type);
$parts = array_pad($parts, 4, '');
$cid = ($cid == '') ? param(1, 0) : $parts[0];
$cate = cate_read($cid);
$req_area = $parts[1];
$req_year = $parts[2];
$req_lang = $parts[3];
$pagesize = 24;
$sonlist = arrlist_cond_orderby($catelist, array('category' => 0), array(), 1, 2000);
$type_urls = type_urls($cid, $conf, $areaarray, $yeararray, $langarray, $req_area, $req_lang, $req_year);
$vodlist =  array();
$where = array_filter_empty([
    'area' => $req_area,
    'lang' => $req_lang,
    'year' => $req_year,
    'cid' => $cid
]);
$videolist =  video_find_by_type($where, $page);
$_SESSION['cid'] = $cid;
seo_apply($conf['seo']['type'], [
    'name' => $cate['name'] ?? '片库',
    'page' => $page,
    'area' => $req_area,
    'lang' => $req_lang,
    'year' => $req_year,
    'sitename' => $conf['sitename'],
]);
if ($json) {
    $api_json['cid'] = $cid;
    $api_json['cate_name'] = $cate['name'] ?? '片库';
    $api_json['cate_url'] = $cate['url'];
    $api_json['area'] = $req_area;
    $api_json['lang'] = $req_lang;
    $api_json['year'] = $req_year;
    $api_json['seo'] = $seo; 
    $api_json['page'] = $page;
    $api_json['pagesize'] = $pagesize; 
    $api_json['videolist'] = $videolist;
    $conf['api_on'] ? message(0, $api_json) : message(0, lang('closed'));
} else {
    include _file(view_load('type', $cid));
}
?>