<?php
// 本地插件
$addons_paths = array();
$addonss = array(); // 合并官方插件
$themes = array(); // 初始化主题
$themelist = array();
// 我的仓库列表
$official_addonss = array();
define('addons_OFFICIAL_URL', 3 == DEBUG ? 'http://www.x.com/' : 'http://www.2131123123.cn/');
$g_include_slot_kv = array();
function _include($srcfile)
{
    global $conf; 
    $tmpfile = $conf['cache_path'] . substr(str_replace('/', '_', $srcfile), strlen(APP_PATH));
    $srcMtime = 0;
    if (is_file($srcfile)) $srcMtime = filemtime($srcfile);
    $overwriteFile = addons_find_overwrite($srcfile);
    if ($overwriteFile !== $srcfile && is_file($overwriteFile)) $srcMtime = max($srcMtime, filemtime($overwriteFile));
    $tmpMtime = is_file($tmpfile) ? filemtime($tmpfile) : 0;
    // tmp不存在文件则进行编译
    if (!is_file($tmpfile) || DEBUG > 1 || ($srcMtime && $tmpMtime && $srcMtime > $tmpMtime)) {
        // 开始编译
        $s = addons_compile_srcfile($srcfile);
        // 支持 <template> <slot>$g_include_slot_kv = array();
        for ($i = 0; $i < 10; ++$i) {
            $s = preg_replace_callback('#<template\sinclude="(.*?)">(.*?)</template>#is', '_include_callback_1', $s);
            if (FALSE === strpos($s, '<template')) break;
        }
        file_put_contents_try($tmpfile, $s);
        if ('php' == file_ext($tmpfile) && 0 == DEBUG && $conf['compress'] > 0) {
            $s = trim(php_strip_whitespace($tmpfile));
        } elseif (in_array(file_ext($tmpfile), array('htm', 'html')) && 0 == DEBUG && $conf['compress'] > 0) {
            $s = addons_compile_srcfile($tmpfile);
            if (1 == $conf['compress']) {
                // 不压缩换行
                $s = str_replace(array("\t"), '', $s);
                $s = preg_replace(array("#> *([^ ]*) *<#", "#<!--[\\w\\W\r\\n]*?-->#", "# \"#", '#>\s+<#', "#/\*[^*]*\*/#", "//", '#\/\*(\s|.)*?\*\/#', "#>\s+\r\n#"), array(">\\1<", '', "\"", '><', '', '', '', '>'), $s);
            } elseif (2 == $conf['compress']) {
                // 全压缩
                $s = preg_replace(array("#> *([^ ]*) *<#", "#[\s]+#", "#<!--[\\w\\W\r\\n]*?-->#", "# \"#", "#/\*[^*]*\*/#", "//", '#>\s+<#', '#\/\*(\s|.)*?\*\/#'), array(">\\1<", ' ', '', "\"", '', '', '><', ''), $s);
            }
        } else {
            $s = addons_compile_srcfile($tmpfile);
        }
        file_put_contents_try($tmpfile, $s);
    }
    return $tmpfile;
}
function _include_callback_1($m)
{
    global $g_include_slot_kv;
    $r = file_get_contents($m[1]);
    preg_match_all('#<slot\sname="(.*?)">(.*?)</slot>#is', $m[2], $m2);
    if (!empty($m2[1])) {
        $kv = array_combine($m2[1], $m2[2]);
        $g_include_slot_kv += $kv;
        foreach ($g_include_slot_kv as $slot => $content) {
            $r = preg_replace('#<slot\sname="' . $slot . '"\s*/>#is', $content, $r);
        }
    }
    return $r;
}
function addons_init()
{
    global $addons_paths, $themes, $themelist, $addonss, $official_addonss, $conf;
    $official_addonss = kv_cache_get('addons_official_list');
    empty($official_addonss) and $official_addonss = array();
    $addons_paths = glob(APP_PATH . 'addons/*', GLOB_ONLYDIR);
    if (is_array($addons_paths)) {
        foreach ($addons_paths as $path) {
            $dir = file_name($path);
            $conffile = $path . '/conf.json';
            if (!is_file($conffile)) continue;
            $arr = xn_json_decode(file_get_contents($conffile));
            if (empty($arr)) continue;
            $addonss[$dir] = $arr;
            // 额外的信息
            $addonss[$dir]['hooks'] = array();
            $hookpaths = glob(APP_PATH . "addons/$dir/hook/*.*"); // path
            if (is_array($hookpaths)) {
                foreach ($hookpaths as $hookpath) {
                    $hookname = file_name($hookpath);
                    $addonss[$dir]['hooks'][$hookname] = $hookpath;
                }
            }
            // 本地 + 线上数据
            $addonss[$dir] = addons_read_by_dir($dir);
        }
    }
    $theme_paths = glob(APP_PATH . 'view/template/*', GLOB_ONLYDIR);
    if (is_array($theme_paths)) {
        foreach ($theme_paths as $path) {
            $dir = file_name($path);
            $conffile = $path . '/conf.json';
            if (!is_file($conffile)) continue;
            $arr = xn_json_decode(file_get_contents($conffile));
            if (empty($arr)) continue;
            $themes[$dir] = $arr;
            $themelist[$dir] = $arr;
            $icon = 'view/template/' . $dir . '/icon.png';
            $themes[$dir]['icon'] = is_file(APP_PATH . $icon) ? '../' . $icon : '';
            $themelist[$dir]['icon'] = is_file(APP_PATH . $icon) ? '../' . $icon : '';
        }
        // 风格二叉树
        foreach ($themelist as $dir => $theme) {
            $dependencies_theme = array_value($theme, 'dependencies_theme');
            if ($dependencies_theme) {
                $dependencies_dir = key($dependencies_theme);
                $themelist[$dependencies_dir]['child'][$dir] = $themelist[$dir];
                unset($themelist[$dir]);
            }
        }
    }
}
function addons_dependencies($dir, $type = 'addons')
{
    global $addons_paths, $addonss, $themes;
    if ('addons' == $type) {
        $action = 'addons';
        $addons = $addonss[$dir];
    } else {
        $action = 'theme';
        $addons = $themes[$dir];
        $dependencies_theme = array_value($addons, 'dependencies_theme');
    }
    $arr = array();
    if ('theme' == $action && $dependencies_theme) {
        foreach ($dependencies_theme as $_dir => $version) {
            if (!isset($themes[$_dir]) || 1 != $themes[$_dir]['installed'] || -1 == version_compare($themes[$_dir]['version'], $version)) {
                $arr[$_dir] = $version;
            }
        }
    }
    $dependencies = $addons['dependencies'];
    foreach ($dependencies as $_dir => $version) {
        if (!isset($addonss[$_dir]) || !$addonss[$_dir]['enable'] || -1 == version_compare($addonss[$_dir]['version'], $version)) {
            $arr[$_dir] = $version;
        }
    }
    return $arr;
}
function addons_by_dependencies($dir, $type = 'addons')
{
    global $addonss, $themes;
    $arr = array();
    if ('addons' == $type) {
        foreach ($addonss as $_dir => $addons) {
            if (isset($addons['dependencies'][$dir]) && $addons['enable']) {
                $arr[$_dir] = $addons['version'];
            }
        }
    } else {
        foreach ($themes as $_dir => $theme) {
            if (isset($theme['dependencies'][$dir]) && $theme['enable']) {
                $arr[$_dir] = $theme['version'];
            }
            if (isset($theme['dependencies_theme'][$dir]) && $theme['enable']) {
                $arr[$_dir] = $theme['version'];
            }
        }
    }
    return $arr;
}
function addons_enable($dir)
{
    global $addonss;
    if (!isset($addonss[$dir])) return FALSE;
    $addonss[$dir]['enable'] = 1;
    file_replace_var(APP_PATH . "addons/$dir/conf.json", array('enable' => 1), TRUE);
    addons_clear_tmp_dir();
    return TRUE;
}
// 清空插件的临时目录
function addons_clear_tmp_dir()
{
    global $conf;
    rmdir_recusive($conf['cache_path'], TRUE);
    xn_unlink($conf['cache_path'] . 'model.min.php');
}
function addons_disable($dir)
{
    global $addonss;
    if (!isset($addonss[$dir])) return FALSE;
    $addonss[$dir]['enable'] = 0;
    file_replace_var(APP_PATH . "addons/$dir/conf.json", array('enable' => 0), TRUE);
    addons_clear_tmp_dir();
    return TRUE;
}
function addons_install($dir)
{
    global $addonss;
    if (!isset($addonss[$dir])) return FALSE;
    $addonss[$dir]['installed'] = 1;
    $addonss[$dir]['enable'] = 1;
    // 写入配置文件
    file_replace_var(APP_PATH . "addons/$dir/conf.json", array('installed' => 1, 'enable' => 1), TRUE);
    addons_clear_tmp_dir();
    return TRUE;
}
// copy from addons_install 修改
function addons_uninstall($dir)
{
    global $addonss;
    if (!isset($addonss[$dir])) return TRUE;
    $addonss[$dir]['installed'] = 0;
    $addonss[$dir]['enable'] = 0;
    // 写入配置文件
    file_replace_var(APP_PATH . "addons/$dir/conf.json", array('installed' => 0, 'enable' => 0), TRUE);
    addons_clear_tmp_dir();
    return TRUE;
}
// 返回所有开启的插件
function addons_paths_enabled()
{
    static $return_paths;
    if (isset($return_paths)) return $return_paths;
    $return_paths = array();
    $addons_paths = glob(APP_PATH . 'addons/*', GLOB_ONLYDIR);
    foreach ($addons_paths as $path) {
        $conffile = $path . '/conf.json';
        if (!is_file($conffile)) continue;
        $pconf = xn_json_decode(file_get_contents($conffile));
        if (empty($pconf)) continue;
        if (empty($pconf['enable']) || empty($pconf['installed'])) continue;
        $return_paths[$path] = $pconf;
    }
    return $return_paths;
}
// 编译源文件，把插件合并到该文件，不需要递归，执行的过程中 include _include() 自动递归
function addons_compile_srcfile($srcfile)
{
    global $conf;
    // 判断是否开启插件
    if (!empty($conf['disabled_addons'])) {
        $s = file_get_contents($srcfile);
        return $s;
    }
    // 如果有 overwrite，则用 overwrite 替换掉
    $srcfile = addons_find_overwrite($srcfile);
    $s = file_get_contents($srcfile);
    if ($s !== '' && substr($s, 0, 3) === "\xEF\xBB\xBF") $s = substr($s, 3);
    $addons_paths = addons_paths_enabled();
    // 最多支持 10 层 合并html模板hook和php文件hook
    for ($i = 0; $i <= 10; ++$i) {
        if (FALSE !== strpos($s, '<!--{hook') || FALSE !== strpos($s, '// hook')) {
            if (empty($addons_paths)) {
                $s = preg_replace('#<!--{hook\s+(.*?)}-->#', '', $s);
            } else {
                $s = preg_replace('#<!--{hook\s+(.*?)}-->#', '// hook \\1', $s);
                $s = preg_replace_callback('#//\s*hook\s+(\S+)#is', 'addons_compile_srcfile_callback', $s);
            }
        } else {
            break;
        }
    }
    return $s;
}
/* 只返回一个权重最高的文件名，最大值overwrite，read.php 文件:值
 * "overwrites_rank":{"read.php": 100}
 * */
function addons_find_overwrite($srcfile)
{
    // 遍历所有开启的插件
    $addons_paths = addons_paths_enabled();
    if (empty($addons_paths)) return $srcfile;
    $len = strlen(APP_PATH);
    $returnfile = $srcfile;
    $maxrank = 0;
    foreach ($addons_paths as $path => $pconf) {
        // 获取插件目录名
        $dir = file_name($path);
        $filepath_half = substr($srcfile, $len);
        $overwrite_name = file_name($srcfile); // 获取覆盖的文件
        $overwrite_file = APP_PATH . "addons/$dir/overwrite/$filepath_half";
        if (is_file($overwrite_file)) {
            $rank = isset($pconf['overwrites_rank'][$overwrite_name]) ? $pconf['overwrites_rank'][$overwrite_name] : 0;
            if ($rank >= $maxrank) {
                $returnfile = $overwrite_file;
                $maxrank = $rank;
            }
        }
    }
    return $returnfile;
}
function addons_compile_srcfile_callback($m)
{
    static $hooks;
    if (empty($hooks)) {
        $hooks = array();
        $addons_paths = addons_paths_enabled();
        if (empty($addons_paths)) return '';
        foreach ($addons_paths as $path => $pconf) {
            $dir = file_name($path);
            $hookpaths = glob(APP_PATH . "addons/$dir/hook/*.*"); // path
            if (is_array($hookpaths)) {
                foreach ($hookpaths as $hookpath) {
                    $hookname = file_name($hookpath);
                    $rank = isset($pconf['hooks_rank']["$hookname"]) ? $pconf['hooks_rank']["$hookname"] : 0;
                    $hooks[$hookname][] = array('hookpath' => $hookpath, 'rank' => $rank);
                }
            }
        }
        foreach ($hooks as $hookname => $arrlist) {
            $arrlist = arrlist_multisort($arrlist, 'rank', FALSE);
            $hooks[$hookname] = arrlist_values($arrlist, 'hookpath');
        }
    }
    $s = '';
    $hookname = $m[1];
    if (!empty($hooks[$hookname])) {
        $fileext = file_ext($hookname);
        foreach ($hooks[$hookname] as $path) {
            $t = file_get_contents($path);
            if ('php' == $fileext && preg_match('#^\s*<\?php\s+exit;#is', $t)) {
                // 正则表达式去除兼容性比较好。
                $t = preg_replace('#^\s*<\?php\s*exit;(.*?)(?:\?>)?\s*$#is', '\\1', $t);
            }
            $s .= $t;
        }
    }
    return $s;
}
// -------------------> 官方插件列表缓存到本地。
// 条件满足的总数
function addons_official_total($cond = array())
{
    global $official_addonss;
    $offlist = $official_addonss;
    $offlist = arrlist_cond_orderby($offlist, $cond, array(), 1, 1000);
    return count($offlist);
}
function addons_official_list($cond = array(), $orderby = array('storeid' => -1), $page = 1, $pagesize = 20)
{
    global $official_addonss;
    // 服务端信息，缓存起来
    $offlist = $official_addonss;
    $offlist = arrlist_cond_orderby($offlist, $cond, $orderby, $page, $pagesize);
    foreach ($offlist as &$addons) $addons = addons_read_by_dir($addons['dir'], FALSE);
    return $offlist;
}
/* 从官方服务器获取数据
 * @param int $type 1 Synchronous Data
 * @return bool|mixed|null|string
 */
function addons_official_store($type = 0)
{
    global $conf, $ip;
    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) return NULL;
    $s = '';
    if ($type) {
        $cookie = _COOKIE($conf['cookie_pre'] . 'addons_official_list');
        if ($cookie) $s = kv_cache_get('addons_official_list');
    }
    if (empty($s)) {
        $arr = addons_data_verify();
        if (FALSE === $arr) {
            setting_delete('addons_data');
            return NULL;
        }
        $post = array('siteid' => addons_siteid(), 'domain' => xn_urlencode(_SERVER('HTTP_HOST')), 'token' => $arr[4], 'uid' => $arr[0]);
        $url = addons_OFFICIAL_URL . 'addons-store.html';
        $s = https_request($url, $post);
        // 检查返回值是否正确
        if (empty($s)) return xn_error(-1, lang('addons_get_data_failed'));
        $s = xn_json_decode($s);
        if (empty($s)) return xn_error(-1, lang('addons_get_data_fmt_failed'));
        kv_cache_set('addons_official_list', $s);
        cookie_set('addons_official_list', 1, 120);
    }
    return $s;
}
function addons_official_read($dir)
{
    global $official_addonss;
    $offlist = $official_addonss;
    $addons = isset($offlist[$dir]) ? $offlist[$dir] : array();
    return $addons;
}
// -------------------> 本地插件列表缓存到本地
// TRUE:插件 FALSE:主题风格
function addons_list($cond = array(), $orderby = array(), $page = 1, $pagesize = 20, $type = 1)
{
    global $addonss, $themes, $themelist;
    if (1 == $type) {
        $offlist = arrlist_cond_orderby($addonss, $cond, $orderby, $page, $pagesize);
    } elseif (2 == $type) {
        $offlist = arrlist_cond_orderby($themelist, $cond, $orderby, $page, $pagesize);
    } elseif (3 == $type) {
        $offlist = arrlist_cond_orderby($themes, $cond, $orderby, $page, $pagesize);
    }
    return $offlist;
}
// 安装，卸载，禁用，更新
function addons_read_by_dir($dir, $local_first = TRUE)
{
    global $addonss, $themes;
    $type = 0;
    $icon = is_file(APP_PATH . 'addons/' . $dir . '/icon.png') ? '../addons/' . $dir . '/icon.png' : '';
    $local = array_value($addonss, $dir, array());
    if (empty($local)) {
        if (isset($themes[$dir]) && $local = $themes[$dir]) {
            $type = 1;
            $icon = is_file(APP_PATH . 'view/template/' . $dir . '/icon.png') ? '../view/template/' . $dir . '/icon.png' : '';
        }
    }
    $official = addons_official_read($dir);
    if (empty($local) && empty($official)) return array();
    if (empty($local)) $local_first = FALSE;
    // 本地插件信息
    !isset($local['name']) && $local['name'] = '';
    !isset($local['price']) && $local['price'] = 0;
    !isset($local['brief']) && $local['brief'] = '';
    !isset($local['version']) && $local['version'] = '1.0.0';
    !isset($local['software_version']) && $local['software_version'] = '2.0';
    !isset($local['installed']) && $local['installed'] = 0;
    !isset($local['enable']) && $local['enable'] = 0;
    !isset($local['hooks']) && $local['hooks'] = array();
    !isset($local['hooks_rank']) && $local['hooks_rank'] = array();
    !isset($local['dependencies']) && $local['dependencies'] = array();
    !isset($local['icon_url']) && $local['icon_url'] = '';
    !isset($local['have_setting']) && $local['have_setting'] = 0;
    !isset($local['setting_url']) && $local['setting_url'] = 0;
    !isset($local['author']) && $local['author'] = 0;
    !isset($local['domain']) && $local['domain'] = 0;
    !isset($local['type']) && $local['type'] = $type; // 0插件 1主题
    // 加上官方插件的信息
    !isset($official['storeid']) && $official['storeid'] = 0;
    !isset($official['name']) && $official['name'] = '';
    !isset($official['price']) && $official['price'] = 0;
    !isset($official['original_price']) && $official['original_price'] = 0;
    !isset($official['brief']) && $official['brief'] = '';
    !isset($official['software_version']) && $official['software_version'] = '2.0';
    !isset($official['version']) && $official['version'] = '1.0.0';
    // 0 所有插件 1主题风格 2功能增强 3大型插件 4接口整合 99未分类
    !isset($official['type']) && $official['type'] = 0;
    !isset($official['last_update']) && $official['last_update'] = 0;
    !isset($official['create_date']) && $official['create_date'] = 0;
    !isset($official['last_update_fmt']) && $official['last_update_fmt'] = lang('none');
    !isset($official['stars']) && $official['stars'] = 0;
    !isset($official['user_stars']) && $official['user_stars'] = 0;
    !isset($official['downloads']) && $official['downloads'] = 0;
    !isset($official['sells']) && $official['sells'] = 0;
    !isset($official['file_md5']) && $official['file_md5'] = '';
    !isset($official['filename']) && $official['filename'] = '';
    !isset($official['is_cert']) && $official['is_cert'] = 0;
    !isset($official['is_show']) && $official['is_show'] = 0;
    !isset($official['brief_url']) && $official['brief_url'] = '';
    !isset($official['qq']) && $official['qq'] = '';
    !isset($official['author']) && $official['author'] = '';
    !isset($official['uid']) && $official['uid'] = '';
    !isset($official['domain']) && $official['domain'] = '';
    !isset($official['upgrade']) && $official['upgrade'] = 0;
    $local['official'] = $official;
    if ($local_first) {
        $addons = $local + $official;
    } else {
        $addons = $official + $local;
    }
    // 额外的判断
    $addons['icon_url'] = $icon ? $icon : ($official['storeid'] ? addons_OFFICIAL_URL . 'upload/addons/' . date('Ym', $addons['create_date']) . '/' . $addons['storeid'] . '/icon.png' : '');
    $addons['setting_url'] = $addons['installed'] && is_file(APP_PATH . "addons/$dir/setting.php") ? url('addons-setting', array('dir' => $dir), TRUE) : '';
    $addons['downloaded'] = isset($addonss[$dir]);
    // 10赞一星 100赞二星 1k赞三星 10k赞四星 100k+五星
    $addons['stars_fmt'] = $official['storeid'] ? str_repeat('<span class="icon-star"></span>', $official['stars']) : '';
    $addons['user_stars_fmt'] = $official['storeid'] ? str_repeat('<span class="icon-star"></span>', $official['user_stars']) : '';
    $addons['have_upgrade'] = $addons['installed'] && version_compare($official['version'], $local['version']) > 0 ? TRUE : FALSE;
    $addons['official_version'] = $official['version'];
    $addons['upgrade'] = $official['upgrade'];
    $addons['downloads'] = $official['downloads'];
    return $addons;
}
function addons_siteid()
{
    global $conf;
    $auth_key = $conf['auth_key'];
    $siteip = _SERVER('SERVER_ADDR');
    return md5($auth_key . $siteip);
}
?>
