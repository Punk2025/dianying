<?php
!defined('DEBUG') and exit('Access Denied.');
$action = param(1, 'list');
tmpl_init();
switch ($action) {
    case 'list':
        if ('GET' == $method) {
            $cond = array();
            $tmpllist = tmpl_list($cond, $orderby = array(), 1, 500, 2); 
            $header['title'] = lang('tmpl_page');
            include _include(APP_PATH . 'admin/html/tmpl_list.html');
        }
        break;
    case 'cloud':
        if ('GET' == $method) {
            $apistr = cache_get('code');
            if (empty($apistr)) {
                $apistr = get_code();
            } 
            $apistr = core_decrypt($apistr);
            $arrlist = xn_json_decode($apistr)['tmpl']; //云端模板
            //本地模板
            $header['title'] = lang('tmpl_page_cloud');
            include _include(APP_PATH . 'admin/html/tmpl_cloud.html');
        }
        break;
    case 'post':
        if ('POST' == $method) {
            $dir = param_word('dir');
            $type = param('type', 0);
            empty($dir) and message(1, lang('data_malformation'));
            if (1 == $type) {
                tmpl_install($dir);
                message(0, lang('install_successfully'));
            } else {
                tmpl_uninstall($dir);
                message(0, lang('uninstall_successfully'));
            }
        }
        break;
    case 'down':
        if ('POST' == $method) {
            $dir = param_word('dir');
            $url = param('downurl');
            empty($url) and message(-1, lang('data_is_empty'));
            tmpl_down($url, $dir);
            message(0, lang('down_successfully'));
        }
        break;
        case 'file': 
            $theme=setting_get('conf')['theme'];
            empty($theme) and $theme='default';
            $base = realpath(APP_PATH . "template/$theme");
            if (empty($base)) message(-1, lang('data_malformation'));
            $base = rtrim($base, '/') . '/';
            $allow_ext = array('html', 'htm', 'css', 'js', 'json', 'txt', 'xml', 'map', 'svg');
            if ('GET' == $method) { 
                $editor = param(2);   
                $path = param('path', ''); 
                $filename = param('filename');
                $path = trim($path, '/');
                if ($path !== '' && (!preg_match('#^[a-zA-Z0-9_\\-/]+$#', $path) || strpos($path, '..') !== false || strpos($path, "\0") !== false)) {
                    message(-1, lang('data_malformation'));
                }
                if ($filename !== '' && (!preg_match('#^[a-zA-Z0-9_\\-.]+$#', $filename) || strpos($filename, '/') !== false || strpos($filename, '..') !== false || strpos($filename, "\0") !== false || $filename[0] === '.')) {
                    message(-1, lang('data_malformation'));
                }
                $file = $base . ($path ? $path : '');
                $file_real = realpath($file);
                if (empty($file_real) || strpos(rtrim($file_real, '/') . '/', $base) !== 0) message(-1, lang('data_malformation'));
                $arrs = dir_file($file_real);
                if ($path == '') {
                    $arrlist = tow_array($arrs);
                } else {
                    $arrlist = tow_array($arrs);
                    $arrlist = $arrlist["$path"];
                } 
                if ($editor == "editor") {
                    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                    if (empty($ext) || !in_array($ext, $allow_ext)) message(-1, lang('data_malformation'));
                    $datafile = $base . ($path ? $path . '/' : '') . $filename;
                    $datafile_real = realpath($datafile);
                    if (empty($datafile_real) || strpos($datafile_real, $base) !== 0 || !is_file($datafile_real)) message(-1, lang('data_malformation'));
                    $s = file_get_contents($datafile_real);
                    $header['title'] = lang('tmpl_editor');
                    include _include(APP_PATH . 'admin/html/editor.html');
                } else {
                    $header['title'] = lang('tmpl_file');
                    include _include(APP_PATH . 'admin/html/file.html');
                } 
            } elseif ('POST' == $method) { 
                $path = param('path');
                $filename = param('filename');
                $code = param('code');
                $path = trim($path, '/');
                if ($path !== '' && (!preg_match('#^[a-zA-Z0-9_\\-/]+$#', $path) || strpos($path, '..') !== false || strpos($path, "\0") !== false)) {
                    message(-1, lang('data_malformation'));
                }
                if ($filename === '' || !preg_match('#^[a-zA-Z0-9_\\-.]+$#', $filename) || strpos($filename, '/') !== false || strpos($filename, '..') !== false || strpos($filename, "\0") !== false || $filename[0] === '.') {
                    message(-1, lang('data_malformation'));
                }
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                if (empty($ext) || !in_array($ext, $allow_ext)) message(-1, lang('data_malformation'));
                $codes = html_entity_decode($code);
                $r = savefile($codes);
                if ($r === false) {
                    exit(message(0, "模板中包含php代码禁止在后台编辑"));
                }
                $target = $base . ($path ? $path . '/' : '') . $filename;
                $target_dir_real = realpath(dirname($target));
                if (empty($target_dir_real) || strpos(rtrim($target_dir_real, '/') . '/', $base) !== 0) message(-1, lang('data_malformation'));
                file_put_contents_try($target, $codes); 
                message(0, "模板修改成功");
            } 
            break;
    default:
        message(-1, lang('data_malformation'));
        break;
}
