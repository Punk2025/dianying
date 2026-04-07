<?php
!defined('DEBUG') and exit('Access Denied.');
$action = param(1);
switch ($action) {
    case 'list':
        $page = param(2, 1);
        $pagesize = $conf['tagsize'];
        $extra = array();
        $count = tag_count();
        $taglist = $count ? tag_find($page, $pagesize) : NULL;
        $page_url = url($conf['route']['tag'] . '-list-{page}', $extra);
        $num = $count > $pagesize * $conf['listsize'] ? $pagesize * $conf['listsize'] : $count;
        $index_page = index_page($page_url, $num, $page, $pagesize);
        $_SESSION['cid'] = 0;
        $_SESSION['num'] = $num;
        $_SESSION['page'] = $page;
        $_SESSION['pagesize'] = $pagesize;
        $_SESSION['page_url'] = $page_url;
        seo_apply($conf['seo']['tag_list'], [
            'page' => $page,
            'sitename' => $conf['sitename'],
        ]);
        if ($json) {
            $api_json['seo'] = $seo;
            $api_json['num'] = $num;
            $api_json['page'] = $page;
            $api_json['pagesize'] = $pagesize;
            $api_json['page_url'] = $page_url;
            $api_json['taglist'] = $taglist;
            $conf['api_on'] ? message(0, $api_json) : message(0, lang('closed'));
        } else {
            include _file(view_load('tag_list'));
        }
        break;
    default:
        $tagid = param(1, 0);
        $page = param(2, 1);
        $pagesize = $conf['pagesize'];
        $extra = array();
        $tags = tag_read_by_tagid_cache($tagid);
        empty($tags) and message(-1, lang('data_malformation'));
        $arr =  tag_video_find($tagid, $page, $pagesize);
        if (empty($arr)) {
            $videolist = NULL;
        } else {
            $vidarr = arrlist_values($arr, 'vid');
            $videolist = video_find($vidarr, $pagesize);
            //文章+视频tag
            $artlist   = art_find($vidarr, $pagesize);
            $videolist = is_array($videolist) ? $videolist : [];
            $artlist   = is_array($artlist)   ? $artlist   : [];
            $videolist = array_merge($videolist, $artlist); 
        }
        $page_url = url($conf['route']['tag'] . '-' . $tagid . '-{page}', $extra);
        $num = $tags['count'] > $pagesize * $conf['listsize'] ? $pagesize * $conf['listsize'] : $tags['count'];
        $index_page = index_page($page_url, $num, $page, $pagesize);
        $_SESSION['cid'] = 0;
        seo_apply($conf['seo']['tag'], [
            'name' => $tags['name'],
            'page' => $page,
            'sitename' => $conf['sitename'],
        ]);
        if ($json) {
            $api_json['tagid'] = $tagid;
            $api_json['tag_name'] = $tags['name'];
            $api_json['seo'] = $seo;
            $api_json['num'] = $num;
            $api_json['page'] = $page;
            $api_json['pagesize'] = $pagesize;
            $api_json['page_url'] = $page_url;
            $api_json['videolist'] = $videolist;
            $conf['api_on'] ? message(0, $api_json) : message(0, lang('closed'));
        } else {
            include _file(view_load('tag', $cid));
        }
        break;
}
?>
