<?php
!defined('DEBUG') and exit('Access Denied.');
$action = param(1, 'list');
$header['title'] = lang('vod_page');
$columnlist = category_list($catelist);
switch ($action) {
    case 'list':
        if ('GET' == $method) {
            $cid = param('cid', 0);
            $page = param('page', 1);
            $pagesize = 20;
            $extra = array('page' => '{page}', 'cid' => $cid,);
            if ($cid) {
                $cate = array_value($catelist, $cid);
                empty($cate) and message(1, lang('cate_not_exists'));
                $n = video_cid_count($cid);
                $vidlist = $n ? video_find_vid($cid, $page, $pagesize) : NULL;
            } else {
                $n = video_vid_count();
                $vidlist = $n ? video_vid_find($page, $pagesize) : NULL;
            }
            if (1 == $page) {
                $stickylist = $cid ? sticky_list_video($cid) : sticky_index_video();
                $vidlist = (array)$stickylist + (array)$vidlist;
            }
            if (empty($vidlist)) {
                $videolist = NULL;
            } else {
                $vidarr = arrlist_values($vidlist, 'vid');
                $videolist = video_find($vidarr, count($vidlist));
                $videolist = array2_sort_key($videolist, $vidlist, 'vid');
            }
            $pagination = pagination(url('vod-list', $extra, TRUE), $n, $page, $pagesize);
            include _include(APP_PATH . 'admin/html/vod_list.html');
        }
        break;
    case 'search': 
        if ('GET' == $method) {
            $keyword = trim(xn_urldecode(param('keyword')));
            if ($keyword) {
                $videolist = video_find_by_keyword($keyword);
            }
            $pagination = '';
            include _include(APP_PATH . 'admin/html/vod_list.html');
        }
        break;
    case 'del':
        $vids = param('vid');
        FALSE ===  video_delete_all($vids) and message(-1, lang('delete_failed'));
        message(0, lang('delete_successfully'));
        break;
    case 'sticky':
        if ('POST' != $method) message(-1, lang('method_error'));
        $vid = param('vid');
        $cid = param('cid');
        $sticky = param('sticky', 0);
        switch ($sticky) {
            //取消推荐
            case '0':
              sticky_video_delete($vid);
               message(0, jump('取消推荐成功', url('vod-list', array(), TRUE), 3));
                break;
             case '1':
                $arr = array(
            'vid' => $vid,
            'cid' => $cid,
            'sticky' => $sticky,
            'create_date'=>time()
        );
        db_insert('vod_sticky', $arr);
        db_update('vod', array('vid' => $vid), array('sticky' => $sticky));
        message(0, jump('设置推荐成功', url('vod-list', array(), TRUE), 3));
                break;
            default:
                break;
        } 
        break; 
    case 'poster':
        $n=up_poster();
        message(0, jump("更新海报 $n 条", url('vod-posterlist', array(), TRUE), 3));
        break;
     case 'posterlist':
         $page = param('page', 1);
         $pagesize = 20;
         $n=video_find_poster_count();
         $extra = array('page' => '{page}');
         $videolist=video_find_poster($page,$pagesize, $d = NULL);
         $pagination = pagination(url('vod-posterlist', $extra, TRUE), $n, $page, $pagesize);
         include _include(APP_PATH . 'admin/html/vod_poster.html');
        break;
    case 'update':
         $vid = param('vid', 0);
        if ('GET' == $method) {
           
        $page = param('page', 0);
        empty($vid) and message(1, lang('data_malformation')); 
        $video=video_read($vid); 
        empty($video) and message(1, lang('video_not_exists'));
        $cid=$video['cid'];
            $extra = array('vid' => $vid, 'page' => $page);
            $header['title'] = lang('edit');
            $header['mobile_title'] = lang('edit');
            $referer = http_referer();
            include _include(ADMIN_PATH . 'html/vod_update.html'); 
         }elseif('POST' == $method){
            // 获取表单数据
            $name = param('name');
            $sub = param('sub');
            $alias = param('alias');
            $cid = param('cid');
            $sticky = param('sticky', 0);
            $status = param('status', 0);
            $area = param('area');
            $lang = param('lang');
            $year = param('year');
            $version = param('version');
            $en = param('en');
            $letter = param('letter');
            $views = param('views', 0);
            $favorites = param('favorites', 0);
            $likes = param('likes', 0); 
            $douban_id = param('douban_id', 0);
            $douban_score = param('douban_score', 0.0);
            $actor = param('actor');
            $director = param('director');
            $writer = param('writer');
            $behind = param('behind');
            $pic = param('pic');
            $pic_slide = param('pic_slide');
            $play_from = param('play_from');
            $blurb = param('blurb');
            $remarks = param('remarks');
            $content = param('content');
            $play_url = param('play_url');
            
            // 构建更新数组
            $update = array(
                'name' => $name,
                'sub' => $sub,
                'alias' => $alias,
                'cid' => $cid,
                'sticky' => $sticky,
                'status' => $status,
                'area' => $area,
                'lang' => $lang,
                'year' => $year,
                'version' => $version,
                'en' => $en,
                'letter' => $letter,
                'views' => $views,
                'favorites' => $favorites,
                'likes' => $likes, 
                'douban_id' => $douban_id,
                'douban_score' => $douban_score,
                'actor' => $actor,
                'director' => $director,
                'writer' => $writer,
                'behind' => $behind,
                'pic' => $pic,
                'pic_slide' => $pic_slide,
                'play_from' => $play_from,
                'blurb' => $blurb,
                'remarks' => $remarks,
                'content' => $content,
                'play_url' => $play_url,
                'uptime' => date('Y-m-d H:i:s')
            );
            
            // 执行更新
            $result = db_update('vod', array('vid' => $vid), $update);
            //print_r($update);
            if ($result === FALSE) {
                message(-1, lang('update_failed'));
            }
            
            // 如果设置了置顶，处理置顶表
            if ($sticky == 1) {
                // 检查是否已经在置顶表中
                $sticky_info = db_find_one('vod_sticky', array('vid' => $vid));
                if (empty($sticky_info)) {
                    // 不在置顶表中，添加
                    $sticky_data = array(
                        'vid' => $vid,
                        'cid' => $cid,
                        'sticky' => $sticky,
                        'create_date' => time()
                    );
                    db_insert('vod_sticky', $sticky_data);
                }
            } else {
                // 取消置顶，从置顶表中删除
                db_delete('vod_sticky', array('vid' => $vid));
            }
            
            message(0, jump('更新成功', url('vod-list', array(), TRUE), 3));
         }
         break;
    default:
        message(-1, lang('data_malformation'));
        break;
}
