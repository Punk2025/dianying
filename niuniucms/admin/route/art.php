<?php
!defined('DEBUG') and exit('Access Denied.');
$action = param(1, 'list');
$header['title'] = lang('art_page');
$catelists = arrlist_cond_orderby($catelist, array('model' => 1), array(), 1, 200);
$columnlist = category_list($catelists);
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
                $n = art_cid_count($cid);
                $aidlist = $n ? art_find_aid($cid, $page, $pagesize) : NULL;
            } else {
                $n = art_aid_count();
                $aidlist = $n ? art_aid_find($page, $pagesize) : NULL;
            }
            if (empty($aidlist)) {
                $artlist = NULL;
            } else {
                $aidarr = arrlist_values($aidlist, 'aid');
                $artlist = art_find($aidarr, count($aidlist));
                $artlist = array2_sort_key($artlist, $aidlist, 'aid');
            }
            $pagination = pagination(url('art-list', $extra, TRUE), $n, $page, $pagesize);
            include _include(APP_PATH . 'admin/html/art_list.html');
        }
        break;
    case 'search':
        if ('GET' == $method) {
            $keyword = trim(xn_urldecode(param('keyword')));
            if ($keyword) {
                $artlist = art_find_by_keyword($keyword);
            }
            $pagination = '';
            include _include(APP_PATH . 'admin/html/art_list.html');
        }
        break;
    case 'del':
        $aids = param('aid');
        FALSE ===  art_delete_all($aids) and message(-1, lang('delete_failed'));
        message(0, lang('delete_successfully'));
        break;
    default:
        message(-1, lang('data_malformation'));
        break;
}
?>
