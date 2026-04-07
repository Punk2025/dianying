<?php
!defined('DEBUG') and exit('Access Denied.');
$action = param(1); 
$http = http_url_path();
$pagesize = $conf['sitemap_num'];
1 < $conf['url_rewrite_on'] and $http = rtrim($http, '/');
switch ($action) {
    case 'list':
        header("Content-type: text/plain");
        $str = '';
        $count = video_vid_count();
        $n=ceil($count / $pagesize); 
        for ($i=0; $i < $n; $i++) { 
            $url = $http . url_sitemap("sitemap-$i");
            $str .= "\r\n" . $url;
        } 
         echo trim($str, "\r\n");
        break; 
    default:
         header("Content-type: text/plain");
         ini_set('memory_limit', '256M');
        $page = param(1, 0); 
        $count = video_vid_count();
        $n = ceil($count / $pagesize);
        $arrlist = video_vid_find($page, $pagesize, FALSE);
        $vidarr = arrlist_values($arrlist, 'vid');
        $videolist = video_find($vidarr, $pagesize, FALSE);
        $str = '';
        foreach ($videolist as $_video) {
            $url = $http . $_video['url'];
            $str .= "\r\n" . $url;
            //根据需求自己选择是否开启
            if ($conf['sitemap_tag']==1) {
                if (isset($_video['tag_url'])) {
                 foreach ($_video['tag_url'] as $tag => $_tag) {
                $turl=$http . $_tag['url'];
                $str .= "\r\n" . $turl;
            }
            }
            
            }
            //根据需求自己选择是否开启
            if ($conf['sitemap_play']==1) {
            if (isset($_video['play_data'])) {
                 foreach ($_video['play_data'] as $play => $_play) {
                    foreach ($_play['info'] as $p => $_p) {
                        $purl=$http . $_p['url'];
                        $str .= "\r\n" . $purl; 
                    } 
                 }
            }
            }
        } 
        echo trim($str, "\r\n");
        break;
}

function url_sitemap($url, $extra = array(), $url_access = NULL)
{
    $conf = _SERVER('conf');
    NULL === $url_access and $url_access = GLOBALS('url_access');
    !isset($conf['url_rewrite_on']) and $conf['url_rewrite_on'] = 0;
    $r = $path = $query = '';
    if ($url && FALSE !== strpos($url, '/')) {
        $path = substr($url, 0, strrpos($url, '/') + 1);
        $query = substr($url, strrpos($url, '/') + 1);
    } else {
        $path = '';
        $query = $url;
    }
    if (0 == $conf['url_rewrite_on']) {
        $r = $path . '?' . $query . '.txt';
    } elseif (1 == $conf['url_rewrite_on']) {
        $r = $path . $query . '.txt';
    } elseif (2 == $conf['url_rewrite_on'] || 3 == $conf['url_rewrite_on']) {
        if (FALSE === strpos($query, '-')) {
            $r = $conf['path'] . $query;
        } else {
            $r = $conf['path'] . str_replace('-', '/', $query) . (2 == $conf['url_rewrite_on'] ? '.txt' : '');
        }
    }
    $arr = explode('-', $query);
    $filter = array('operate');
    // 后台链接
    if ((TRUE === $url_access && !in_array($arr[0], $filter, TRUE)) || 3 === $url_access) {
        $r = 'index.php?' . http_build_query($arr);
    } 
    if ($extra) {
        $args = http_build_query($extra);
        $sep = FALSE === strpos($r, '?') ? '?' : '&';
        $r .= $sep . $args;
    }
    return $r;
}
