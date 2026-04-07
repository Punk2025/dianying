<?php
function _file($srcfile)
{
    global $conf; 
    $tmpfile = $conf['cache_path'] . substr(str_replace('/', '_', $srcfile), strlen(APP_PATH));
    $srcMtime = is_file($srcfile) ? (int) filemtime($srcfile) : 0;
    $tmpMtime = is_file($tmpfile) ? (int) filemtime($tmpfile) : 0;
    $need_rebuild = !is_file($tmpfile) || DEBUG > 1 || $srcMtime > $tmpMtime;
    if (!$need_rebuild && $srcMtime > 0 && is_file($srcfile)) {
        $srct = @file_get_contents($srcfile, false, null, 0, 262144);
        if ($srct !== false && preg_match_all('#\{inc\:([\w|\/\.]+)\}#', $srct, $incm)) {
            $bd = dirname($srcfile);
            foreach ($incm[1] as $rel) {
                if ($rel === '' || strpos($rel, '..') !== false || strpos($rel, "\0") !== false) {
                    continue;
                }
                $p = $bd . '/' . ltrim($rel, '/');
                if (is_file($p) && (int) filemtime($p) > $tmpMtime) {
                    $need_rebuild = true;
                    break;
                }
            }
        }
    }
    if ($need_rebuild) {
        $s = addons_compile_srcfile($srcfile);
        $s = ltrim($s);
        $reg_arr = '[a-zA-Z_]\w*(?:\[\w+\]|\[\'\w+\'\]|\[\"\w+\"\]|\[\$[a-zA-Z_]\w*\])*';
        //2025.09.07 修复inc
        $s = preg_replace_callback(
            '#\{inc\:([\w|\/\.]+)\}#',
            function ($matches) use ($srcfile) {
                return process_inc($matches, dirname($srcfile));
            },
            $s
        );
        /*0ld----$s = preg_replace_callback('#\{inc\:([\w|\/\.]+)\}#', 'process_inc', $s);*/
        $s = preg_replace('#(?:\<\?.*?\?\>|\<\?.*)#s', '', $s); 
        $allow_tpl_php = !empty($conf['tpl_allow_php']);
        if ($allow_tpl_php) {
            $s = preg_replace('#\{php\}(.*?)\{\/php\}#s', '<?php \\1 ?>', $s);
        } else {
            $s = preg_replace('#\{php\}.*?\{\/php\}#s', '', $s);
        }
        $s = preg_replace_callback('#\{block\:([a-zA-Z_]\w*)\040?([^\n\}]*?)\}(.*?){\/block}#s', 'process_block', $s);
        while (preg_match('#\{loop\:\$' . $reg_arr . '(?:\040\$[a-zA-Z_]\w*){1,2}\}.*?\{\/loop\}#s', $s))
            $s = preg_replace_callback('#\{loop\:(\$' . $reg_arr . '(?:\040\$[a-zA-Z_]\w*){1,2})\}(.*?)\{\/loop\}#s', 'process_loop', $s);
        while (preg_match('#\{if\:[^\n\}]+\}.*?\{\/if\}#s', $s))
            $s = preg_replace_callback('#\{if\:([^\n\}]+)\}(.*?)\{\/if\}#s', 'process_if', $s); 
        $s = preg_replace_callback('#\{\$([a-zA-Z_]\w*(?:\.[a-zA-Z0-9_]+)+)\}#', 'process_vars_dot', $s);
        if (!empty($conf['tpl_allow_expr'])) {
            $s = preg_replace('#\{\@([^\}]+)\}#', '<?php echo(\\1); ?>', $s);
        } else {
            $s = preg_replace('#\{\@[^\}]+\}#', '', $s);
        }
        $s = preg_replace_callback('#\{(\$' . $reg_arr . ')\}#', 'process_vars', $s); 
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
                $s = str_replace(array("\t"), '', $s);
                $s = preg_replace(array("#> *([^ ]*) *<#", "#<!--[\\w\\W\r\\n]*?-->#", "# \"#", '#>\s+<#', "#/\*[^*]*\*/#", "//", '#\/\*(\s|.)*?\*\/#', "#>\s+\r\n#"), array(">\\1<", '', "\"", '><', '', '', '', '>'), $s);
            } elseif (2 == $conf['compress']) { 
                $s = preg_replace(array("#> *([^ ]*) *<#", "#[\s]+#", "#<!--[\\w\\W\r\\n]*?-->#", "# \"#", "#/\*[^*]*\*/#", "//", '#>\s+<#', '#\/\*(\s|.)*?\*\/#'), array(">\\1<", ' ', '', "\"", '', '', '><', ''), $s);
            }
        } else {
            $s = addons_compile_srcfile($tmpfile);
        }
        file_put_contents_try($tmpfile, $s);
    }
    return $tmpfile;
}
function dot_to_array_syntax($str)
{
    $parts = explode('.', $str);
    $base = array_shift($parts);
    return '$' . $base . implode('', array_map(function ($v) {
        return "['" . $v . "']";
    }, $parts));
}
function process_vars_dot($matches)
{
    $var = dot_to_array_syntax($matches[1]);
    $default = isset($matches[2]) ? $matches[2] : "''";
    return "<?php echo (isset($var) ? $var : $default); ?>";
}
function process_vars($matches)
{
    $vars = rep_double($matches[1]);
    $vars = rep_vars($vars);
    return "<?php echo (isset($vars) ? $vars : ''); ?>";
}
function rep_double($s)
{
    return str_replace('\"', '"', $s);
}
function rep_vars($s)
{
    $s = preg_replace('#\[(\w+)\]#', "['\\1']", $s);
    $s = preg_replace('#\[\"(\w+)\"\]#', "['\\1']", $s);
    $s = preg_replace('#\[\'(\d+)\'\]#', '[\\1]', $s);
    return $s;
}
function process_if($matches)
{
    $expr = rep_double($matches[1]);
    $expr = rep_vars($expr);
    $s = preg_replace_callback('#\{elseif\:([^\n\}]+)\}#', 'rep_elseif', rep_double($matches[2]));
    $s = str_replace('{else}', '<?php }else{ ?>', $s);
    return "<?php if ($expr) { ?>$s<?php } ?>";
}
function rep_elseif($matches)
{
    $expr = rep_double($matches[1]);
    $expr = rep_vars($expr);
    return "<?php }elseif($expr) { ?>";
}
function process_loop($matches)
{
    $args = explode(' ', rep_double($matches[1]));
    $s = rep_double($matches[2]);
    $arr = rep_vars($args[0]);
    $v = empty($args[1]) ? '$v' : $args[1];
    $k = empty($args[2]) ? '' : $args[2] . '=>';
    return "<?php if(isset($arr) && is_array($arr)) { foreach($arr as $k&$v) { ?>$s<?php }} ?>";
}
function process_inc($matches, $basedir = '')
{
    global $config;
    $filename = $matches[1];
    if (strpos($filename, '/') !== false) {
        $arr = explode('/', $filename, 2);
        $filename = $arr[0] . '/' . $arr[1];
    }
    $filename = ltrim($filename, '/');
    if ($filename === '' || strpos($filename, "\0") !== false || strpos($filename, '..') !== false) return '';
    // 如果有上下文，就优先从当前模板所在目录找
    if ($basedir) {
        $tpl_file = $basedir . '/' . $filename;
        if (file_exists($tpl_file)) {
            return file_get_contents($tpl_file);
        }
    }
    // 否则，走默认
    $tpl_file = APP_PATH . 'template/' . $config['theme'] . '/html/' . $filename;
    if (!file_exists($tpl_file)) {
        $tpl_file = APP_PATH . 'template/default/html/' . $filename;
    }

    return file_exists($tpl_file) ? file_get_contents($tpl_file) : '';
}
/*function process_inc($matches)
{ 
    global $config;
    if (strpos($matches[1], '/') != false) { 
        $arr = explode('/', $matches[1]);
        $filename = $arr[0] . '/' . $arr[1];
    } else {
        $filename = $matches[1];
    }
    $tpl_file = APP_PATH . 'template/' . $config['theme'] . '/html/' . $filename;
    if (!file_exists($tpl_file)) $tpl_file = APP_PATH . 'template/default/html/' . $filename; 
    return file_get_contents($tpl_file);
}*/
function process_block($matches)
{
    $func = $matches[1];
    $config = $matches[2];
    $s = $matches[3];
    $s = rep_double($s);
    $config = rep_double($config);
    $config_arr = array();
    preg_match_all('#([a-zA-Z_]\w*)="(.*?)" #', $config . ' ', $m);
    foreach ($m[2] as $k => $v) {
        if (isset($v)) $config_arr[strtolower($m[1][$k])] = addslashes($v);
    }
    unset($m);
    $func_str = 'block_' . $func . '(' . var_export($config_arr, 1) . ');';
    $before = $after = ''; 
    $before .= '<?php $data = ' . $func_str . ' ?>';
    $after .= '<?php unset($data); ?>'; 
    return $before . $s . $after;
}
// 模板路径解析
function view_load($type = '', $id = 0, $dir = '')
{
    global $config;
    $tpl_mode = $config['setting']['tpl_mode'] ?? 0;
     isset($tpl_mode) || $tpl_mode = 0;
    $detect = get_device();
    $pre = $default_pre = ''; 
    if ($tpl_mode && $detect) {
        if (2 == $tpl_mode && 2 == $detect) {
            $pre = 'pad.';  
        } else {
            $pre = 'm.'; 
        }
    }
    switch ($type) {
        case 'index':
            $pre .= $default_pre .= 'index.html';
            break;
        case 'list':
            $pre .= $default_pre .= 'list.html';
            break;
        case 'art_list':
            $pre .= $default_pre .= 'art_list.html';
            break;
        case 'category':
            $pre .= $default_pre .= 'category.html';
            break;
        case 'art_category':
            $pre .= $default_pre .= 'art_category.html';
            break;
        case 'video':
            $pre .= $default_pre .= 'video.html';
            break;
         case 'art':
            $pre .= $default_pre .= 'art.html';
            break;
        case 'player':
            $pre .= $default_pre .= 'player.html';
            break; 
        case 'tag':
            $pre .= $default_pre .= 'tag.html';
            break;
        case 'tag_list':
            $pre .= $default_pre .= 'tag_list.html';
            break;
        case 'type':
            $pre .= $default_pre .= 'type.html';
            break;
        case 'search':
            $pre .= $default_pre .= 'search.html';
            break;
        case 'message':
            $pre .= $default_pre .= 'message.html';
            break;
         case '404':
            $pre .= $default_pre .= '404.html';
            break;
        case 'user_login':
            $pre .= $default_pre .= 'user/login.html';
            break;
        case 'user_index':
            $pre .= $default_pre .= 'user/index.html';
            break;
        case 'user_passwd':
            $pre .= $default_pre .= 'user/passwd.html';
            break;
        case 'user-like':
            $pre .= $default_pre .= 'user/like.html';
            break;
        case 'user-favorites':
            $pre .= $default_pre .= 'user/favorites.html';
            break;
        default:
            $pre .= $default_pre .= $default_pre;
            break;
    }
    if ($config['theme']) {
        $conffile = APP_PATH . 'template/' . $config['theme'] . '/conf.json';
        $json = is_file($conffile) ? xn_json_decode(file_get_contents($conffile)) : array();
    }

    //!empty($json['installed']) and $path_file = APP_PATH . 'template/' . $config['theme'] . '/html/' . ($id ? $id . '_' : '') . $pre;
    // 从数据库或缓存中获取配置
    $configs = setting_get('conf');
if ( isset($configs['theme']) && $config['theme'] == $configs['theme']) {
    !empty($json['installed']) and $path_file = APP_PATH . 'template/' . $configs['theme'] . '/html/' . ($id ? $id . '_' : '') . $pre;
} else {
    
    $path_file = APP_PATH . 'template/' . $config['theme'] . '/html/' . ($id ? $id . '_' : '') . $pre;
}
    (empty($path_file) || !is_file($path_file)) and $path_file = APP_PATH . 'template/' . $config['theme'] . '/html/' . $pre;

  
    !is_file($path_file) and $path_file = APP_PATH . ($dir ? 'addons/' . $dir . '/view/html/' : 'template/default/html/') . $default_pre;

    return $path_file;
    /*$json = [];
    if ($config['theme']) {
        $conffile = APP_PATH . 'template/' . $config['theme'] . '/conf.json';
        $json = is_file($conffile) ? xn_json_decode(file_get_contents($conffile)) : [];
    }
    $path_file = '';
    if (!empty($json['installed']))
        $path_file = APP_PATH . 'template/' . $config['theme'] . '/html/' . ($id ? $id . '_' : '') . $pre;
    if (!is_file($path_file))
        $path_file = APP_PATH . 'template/' . $config['theme'] . '/html/' . $pre;
    if (!is_file($path_file))
        $path_file = APP_PATH . ($dir ? 'addons/' . $dir . '/view/html/' : 'template/default/html/') . $default_pre;
    return $path_file;*/
}

/** 删除某主题已编译的前台 HTML 模板缓存（runtime/cache/template_{theme}_html_*.html），改 inc 子模板或站点变量后需刷新时用 */
function view_clear_compiled_theme_html($theme)
{
    global $conf;
    $theme = is_string($theme) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $theme) : '';
    if ($theme === '') {
        return;
    }
    $dir = rtrim(str_replace('\\', '/', $conf['cache_path']), '/');
    if ($dir === '' || !is_dir($dir)) {
        return;
    }
    foreach (glob($dir . '/template_' . $theme . '_html_*.html') ?: array() as $f) {
        if (is_file($f)) {
            @unlink($f);
        }
    }
}
?>
