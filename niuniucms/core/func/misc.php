<?php
//2026-02-17
function page_array($url_tpl, $totalnum, $page, $pagesize = 20)
{
    $totalpage = max(1, ceil($totalnum / $pagesize));
    $page = max(1, min((int)$page, $totalpage));
    $shownum = 3;
    $start = max(1, $page - $shownum);
    $end   = min($totalpage, $page + $shownum);
    $right = $page + $shownum - $totalpage;
    if ($right > 0) {
        $start = max(1, $start - $right);
    }

    $left = $page - $shownum;
    if ($left < 0) {
        $end = min($totalpage, $end - $left);
    }
    // 构建中部页码数组
    $pages = [];
    for ($i = $start; $i <= $end; $i++) {
        $pages[] = [
            'page'    => $i,
            'url'     => str_replace('{page}', $i, $url_tpl),
            'current' => $i == $page,
        ];
    }
    return [
        'current_page' => $page,
        'total_page'   => $totalpage,
        'current_url'  => str_replace('{page}', $page, $url_tpl),
        'first'        => $page > 1 ? str_replace('{page}', 1, $url_tpl) : null,
        'last'         => $page < $totalpage ? str_replace('{page}', $totalpage, $url_tpl) : null,
        'prev'         => $page > 1 ? str_replace('{page}', $page - 1, $url_tpl) : null,
        'next'         => $page < $totalpage ? str_replace('{page}', $page + 1, $url_tpl) : null,
        'pages'        => $pages,
    ];
}
//2026-02-17

function sname($str,$n){
    echo mb_substr($str, 0, $n, 'utf-8');
}
function bull_longip()
{
    $ip = ip();
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $longip = ip2long($ip);
        // fix 32 位 OS 下溢出的问题
        $longip < 0 and $longip = sprintf("%u", $longip);
    } else {
        $longip = ip2long_v6($ip);
    }
    return $longip;
}
function xn_message($code, $message)
{
    $json = $_SERVER['json'];
    echo $json ? xn_json_encode(array('code' => $code, 'message' => $message)) : $message;
    exit;
}
function xn_log_post_data()
{
    $method = $_SERVER['method'];
    if ('POST' != $method) return;
    $post = $_POST;
    isset($post['password']) and $post['password'] = '******';        // 干掉密码信息
    isset($post['password_new']) and $post['password_new'] = '******';    // 干掉密码信息
    isset($post['password_old']) and $post['password_old'] = '******';    // 干掉密码信息
    xn_log(xn_json_encode($post), 'post_data');
}
// 捕获全局异常 throw new Exception('exception')
function exception_handler($exception)
{
    $json = $_SERVER['json'];
    $message = 0 == DEBUG ? $exception->getMessage() : $exception;
    $html = $s = "<fieldset class=\"fieldset small notice\"><div>" . $message . "</div></fieldset>";
    echo ($json || IN_CMD) ? $message : $html;
    2 == DEBUG and xn_log($exception, 'debug_error');
}
// 中断流程很危险！可能会导致数据问题，线上模式不允许中断流程！
function error_handle($errno, $errstr, $errfile, $errline)
{
    // PHP 内部默认处理
    if (0 == DEBUG) return FALSE;
    // 如果放在 register_shutdown_function 里面，文件句柄会被关闭，然后这里就写入不了文件了！
    // if(FALSE !== strpos($s, 'error_log(')) return TRUE;
    $time = $_SERVER['time'];
    $json = $_SERVER['json'];
    IN_CMD and $errstr = str_replace('<br>', "\n", $errstr);
    $subject = "Error[$errno]: $errstr, File: $errfile, Line: $errline";
    $message = array();
    xn_log($subject, 'php_error'); // 所有PHP错误报告都记录日志
    $arr = debug_backtrace();
    array_shift($arr);
    foreach ($arr as $v) {
        $args = '';
        if (!empty($v['args']) && is_array($v['args'])) foreach ($v['args'] as $v2) $args .= ($args ? ' , ' : '') . (is_array($v2) ? 'array(' . count($v2) . ')' : (is_object($v2) ? 'object' : $v2));
        !isset($v['file']) and $v['file'] = '';
        !isset($v['line']) and $v['line'] = '';
        $message[] = "File: $v[file], Line: $v[line], $v[function]($args) ";
    }
    $txt = $subject . "\r\n" . implode("\r\n", $message);
    $html = $s = "<fieldset class=\"fieldset small notice\">
			<b>$subject</b>
			<div>" . implode("<br>\r\n", $message) . "</div>
		</fieldset>";
    echo ($json || IN_CMD) ? $txt : $html;
    2 == DEBUG and xn_log($txt, 'debug_error');
    return TRUE;
}
// 使用全局变量记录错误信息
function xn_error($no, $str, $return = FALSE)
{
    global $errno, $errstr;
    $errno = $no;
    $errstr = $str;
    return $return;
}
/*
	param(1);
	param(1, '');
	param(1, 0);
	param(1, array());
	param(1, array(''));
	param(1, array(0));
*/
function param($key, $defval = '', $htmlspecialchars = TRUE, $addslashes = FALSE)
{
    if (!isset($_REQUEST[$key]) || (0 == $key && empty($_REQUEST[$key]))) {
        if (is_array($defval)) {
            return array();
        } else {
            return $defval;
        }
    }
    $val = $_REQUEST[$key];
    $val = param_force($val, $defval, $htmlspecialchars, $addslashes);
    return $val;
}
// 安全获取单词类参数
function param_word($key, $len = 32)
{
    $s = param($key);
    $s = xn_safe_word($s, $len);
    return $s;
}
function param_base64($key, $len = 0)
{
    $s = param($key, '', FALSE);
    if (empty($s)) return '';
    $s = substr($s, strpos($s, ',') + 1);
    $s = base64_decode($s);
    $len and $s = substr($s, 0, $len);
    return $s;
}
function param_json($key)
{
    $s = param($key, '', FALSE);
    if (empty($s)) return '';
    $arr = xn_json_decode($s);
    return $arr;
}
function param_url($key)
{
    $s = param($key, '', FALSE);
    $arr = xn_urldecode($s);
    return $arr;
}
// 安全过滤字符串，仅仅保留 [a-zA-Z0-9_]
function xn_safe_word($s, $len)
{
    $s = preg_replace('#\W+#', '', $s);
    $s = substr($s, 0, $len);
    return $s;
}
/*
	仅支持一维数组的类型强制转换。
	param_force($val);
	param_force($val, '');
	param_force($val, 0);
	param_force($arr, array());
	param_force($arr, array(''));
	param_force($arr, array(0));
*/
function param_force($val, $defval, $htmlspecialchars = TRUE, $addslashes = FALSE)
{
    $get_magic_quotes_gpc = _SERVER('get_magic_quotes_gpc');
    if (is_array($defval)) {
        $defval = empty($defval) ? '' : $defval[0]; // 数组的第一个元素，如果没有则为空字符串
        if (is_array($val)) {
            foreach ($val as &$v) {
                if (is_array($v)) {
                    $v = $defval;
                } else {
                    if (is_string($defval)) {
                        //$v = trim($v);
                        $addslashes and !$get_magic_quotes_gpc && $v = addslashes(isset($v) ? $v : '');
                        !$addslashes and $get_magic_quotes_gpc && $v = stripslashes($v);
                        $htmlspecialchars and $v = htmlspecialchars($v, ENT_QUOTES);
                    } else {
                        $v = intval($v);
                    }
                }
            }
        } else {
            return array();
        }
    } else {
        if (is_array($val)) {
            $val = $defval;
        } else {
            if (is_string($defval)) {
                //$val = trim($val);
                $addslashes and !$get_magic_quotes_gpc && $val = addslashes(isset($val) ? $val : '');
                !$addslashes and $get_magic_quotes_gpc && $val = stripslashes($val);
                $htmlspecialchars and $val = htmlspecialchars($val, ENT_QUOTES);
            } else {
                $val = intval($val);
            }
        }
    }
    return $val;
}
/*
	lang('mobile_length_error');
	lang('mobile_length_error', array('mobile'=>$mobile));
*/
function lang($key, $arr = array())
{
    $lang = $_SERVER['lang'];
    if (!isset($lang[$key])) return 'lang[' . $key . ']';
    $s = $lang[$key];
    if (!empty($arr)) {
        foreach ($arr as $k => $v) {
            $s = str_replace('{' . $k . '}', $v, $s);
        }
    }
    return $s;
}
function jump($message, $url = '', $delay = 3)
{
    $json = $_SERVER['json'];
    if ($json) return $message;
    if (!$url) return $message;
    'back' == $url and $url = 'javascript:history.back()';
    $htmladd = '<script>setTimeout(function() {window.location=\'' . $url . '\'}, ' . ($delay * 1000) . ');</script>';
    return '<a href="' . $url . '">' . $message . '</a>' . $htmladd;
}
function xn_strlen($s)
{
    return mb_strlen($s, 'UTF-8');
}
function xn_substr($s, $start, $len)
{
    return mb_substr($s, $start, $len, 'UTF-8');
}
// txt 转换到 html
function xn_txt_to_html($s)
{
    $s = htmlspecialchars($s, ENT_QUOTES);
    $s = str_replace(" ", '&nbsp;', $s);
    $s = str_replace("\t", ' &nbsp; &nbsp; &nbsp; &nbsp;', $s);
    $s = str_replace("\r\n", "\n", $s);
    $s = str_replace("\n", '<br>', $s);
    return $s;
}
function xn_urlencode($s)
{
    $s = urlencode($s);
    $s = str_replace('_', '_5f', $s);
    $s = str_replace('-', '_2d', $s);
    $s = str_replace('.', '_2e', $s);
    $s = str_replace('+', '_2b', $s);
    $s = str_replace('=', '_3d', $s);
    $s = str_replace('%', '_', $s);
    return $s;
}
function xn_urldecode($s)
{
    $s = str_replace('_', '%', $s);
    $s = urldecode($s);
    return $s;
}

function xn_json_encode($data, $pretty = FALSE, $level = 0)
{
    if (version_compare(PHP_VERSION, '5.4.0') >= 0) {
        return $pretty ? json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
    $tab = $pretty ? str_repeat("\t", $level) : '';
    $tab2 = $pretty ? str_repeat("\t", $level + 1) : '';
    $br = $pretty ? "\r\n" : '';
    switch ($type = gettype($data)) {
        case 'NULL':
            return 'null';
        case 'boolean':
            return ($data ? 'true' : 'false');
        case 'integer':
        case 'double':
        case 'float':
            return $data;
        case 'string':
            $data = '"' . str_replace(array('\\', '"'), array('\\\\', '\\"'), $data) . '"';
            $data = str_replace("\r", '\\r', $data);
            $data = str_replace("\n", '\\n', $data);
            $data = str_replace("\t", '\\t', $data);
            return $data;
        case 'object':
            return get_object_vars($data);
        case 'array':
            $output_index_count = 0;
            $output_indexed = array();
            $output_associative = array();
            foreach ($data as $key => $value) {
                $output_indexed[] = xn_json_encode($value, $pretty, $level + 1);
                $output_associative[] = $tab2 . '"' . $key . '":' . xn_json_encode($value, $pretty, $level + 1);
                if (NULL !== $output_index_count && $output_index_count++ !== $key) {
                    $output_index_count = NULL;
                }
            }
            if (NULL !== $output_index_count) {
                return '[' . implode(",$br", $output_indexed) . ']';
            } else {
                return "{{$br}" . implode(",$br", $output_associative) . "{$br}{$tab}}";
            }
        default:
            return ''; // Not supported
    }
}
function xn_json_decode($json)
{
    $json = trim($json, "\xEF\xBB\xBF");
    $json = trim($json, "\xFE\xFF");
    return json_decode($json, TRUE);
}
// ---------------------> encrypt function end
function pagination_tpl($url, $text, $active = '')
{
    global $g_pagination_tpl;
    empty($g_pagination_tpl) and $g_pagination_tpl = '<li class="page-item{active}"><a href="{url}" class="page-link">{text}</a></li>';
    return str_replace(array('{url}', '{text}', '{active}'), array($url, $text, $active), $g_pagination_tpl);
}
//自定义翻页
function index_page($url_tpl, $totalnum, $page, $pagesize = 20)
{
    $totalpage = max(1, ceil($totalnum / $pagesize));
    $page = max(1, min((int)$page, $totalpage));
    $shownum = 3;
    if ($totalpage < 2) return '';
    $start = max(1, $page - $shownum);
    $end = min($totalpage, $page + $shownum);
    // 补足页码
    $right = $page + $shownum - $totalpage;
    $right > 0 && $start = max(1, $start - $right);
    $left = $page - $shownum;
    $left < 0 && $end = min($totalpage, $end - $left);
    $html = '';
    // 首页 / 上一页
    if ($page > 1) {
        $html .= '<a href="' . str_replace('{page}', 1, $url_tpl) . '" class="page-number page-previous" title="首页">首页</a>';
        $html .= '<a href="' . str_replace('{page}', $page - 1, $url_tpl) . '" class="page-number page-previous" title="上一页">上一页</a>';
    }
    // 中部页码
    for ($i = $start; $i <= $end; $i++) {
        if ($i == $page) {
            $html .= '<span class="page-number page-current desktop">' . $i . '</span>';
        } else {
            $html .= '<a href="' . str_replace('{page}', $i, $url_tpl) . '" class="page-number desktop" title="第' . $i . '页">' . $i . '</a>';
        }
    }
    // 下一页 / 尾页
    if ($page < $totalpage) {
        $html .= '<a href="' . str_replace('{page}', $page + 1, $url_tpl) . '" class="page-number page-next" title="下一页">下一页</a>';
        $html .= '<a href="' . str_replace('{page}', $totalpage, $url_tpl) . '" class="page-number page-next" title="尾页">尾页</a>';
    }
    return $html;
}
// bootstrap 翻页，命名与 bootstrap 保持一致
function pagination($url, $totalnum, $page, $pagesize = 20)
{
    $url = trim(xn_urldecode($url));
    $totalpage = ceil($totalnum / $pagesize);
    if ($totalpage < 2) return '';
    $page = min($totalpage, $page);
    $shownum = 3; // 显示多少个页 * 2
    $start = max(1, $page - $shownum);
    $end = min($totalpage, $page + $shownum);
    // 不足 $shownum，补全左右两侧
    $right = $page + $shownum - $totalpage;
    $right > 0 && $start = max(1, $start -= $right);
    $left = $page - $shownum;
    $left < 0 && $end = min($totalpage, $end -= $left);
    $s = '';
    $page != 1 && $s .= pagination_tpl(str_replace('{page}', $page - 1, $url), '&laquo;', '');
    if ($start > 1) $s .= pagination_tpl(str_replace('{page}', 1, $url), '1 ' . ($start > 2 ? '...' : ''));
    for ($i = $start; $i <= $end; $i++) {
        $s .= pagination_tpl(str_replace('{page}', $i, $url), $i, $i == $page ? ' active' : '');
    }
    if ($end != $totalpage) $s .= pagination_tpl(str_replace('{page}', $totalpage, $url), ($totalpage - $end > 1 ? '...' : '') . $totalpage);
    $page != $totalpage && $s .= pagination_tpl(str_replace('{page}', $page + 1, $url), '&raquo;');
    return $s;
}
// 简单的上一页，下一页，比较省资源，不用count(), 推荐使用，命名与 bootstrap 保持一致
function pager($url, $totalnum, $page, $pagesize = 20)
{
    $url = trim(xn_urldecode($url));
    $totalpage = ceil($totalnum / $pagesize);
    if ($totalpage < 2) return '';
    $page = min($totalpage, $page);
    $s = '';
    $page > 1 and $s .= '<li class="page-item"><a class="page-link" href="' . str_replace('{page}', $page - 1, $url) . '">Prev</a></li>';
    $s .= "<li class=\"page-item page-link\">$page / $totalpage</li>";
    $totalnum >= $pagesize and $page != $totalpage and $s .= '<li class="page-item"><a class="page-link" href="' . str_replace('{page}', $page + 1, $url) . '">Next</a></li>';
    return $s;
}
function mid($n, $min, $max)
{
    if ($n < $min) return $min;
    if ($n > $max) return $max;
    return $n;
}
function humandate($timestamp, $lan = array())
{
    $time = $_SERVER['time'];
    $lang = $_SERVER['lang'];
    is_array($lan) || $lan = array();
    is_array($lang) || $lang = array();
    static $custom_humandate = NULL;
    if (NULL === $custom_humandate) $custom_humandate = function_exists('custom_humandate');
    if ($custom_humandate) return custom_humandate($timestamp, $lan);
    $seconds = $time - $timestamp;
    $lan = empty($lang) ? $lan : $lang;
    empty($lan) and $lan = array(
        'month_ago' => '月前',
        'day_ago' => '天前',
        'hour_ago' => '小时前',
        'minute_ago' => '分钟前',
        'second_ago' => '秒前',
    );
    if ($seconds > 31536000) {
        return date('Y-n-j', $timestamp);
    } elseif ($seconds > 2592000) {
        return floor($seconds / 2592000) . $lan['month_ago'];
    } elseif ($seconds > 86400) {
        return floor($seconds / 86400) . $lan['day_ago'];
    } elseif ($seconds > 3600) {
        return floor($seconds / 3600) . $lan['hour_ago'];
    } elseif ($seconds > 60) {
        return floor($seconds / 60) . $lan['minute_ago'];
    } else {
        return $seconds . $lan['second_ago'];
    }
}
function humannumber($num)
{
    $num > 100000 && $num = ceil($num / 10000) . '万';
    return $num;
}
function humansize($num)
{
    if ($num > 1073741824) {
        return number_format($num / 1073741824, 2, '.', '') . 'G';
    } elseif ($num > 1048576) {
        return number_format($num / 1048576, 2, '.', '') . 'M';
    } elseif ($num > 1024) {
        return number_format($num / 1024, 2, '.', '') . 'K';
    } else {
        return $num . 'B';
    }
}
// 不安全的获取 IP 方式，在开启 CDN 的时候，如果被人猜到真实 IP，则可以伪造。
function ip()
{
    $conf = _SERVER('conf');
    if (empty($conf['cdn_on'])) {
        $ip = _SERVER('REMOTE_ADDR');
    } else {
        if (isset($_SERVER['HTTP_CDN_SRC_IP'])) {
            $ip = $_SERVER['HTTP_CDN_SRC_IP'];
        } elseif (isset($_SERVER['HTTP_CLIENTIP'])) {
            $ip = $_SERVER['HTTP_CLIENTIP'];
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            $arr = array_filter(explode(',', $ip));
            $ip = trim(end($arr));
        } else {
            $ip = _SERVER('REMOTE_ADDR');
        }
    }
    return $ip;
}
// 转IP格式，支持IPV4 IPV6
function safe_long2ip($longip)
{
    if (!is_numeric($longip)) return htmlspecialchars($longip, ENT_QUOTES);
    // IPV6
    if (strlen($longip) > 10) return long2ip_v6($longip);
    $longip = intval(4294967295 - ($longip - 1));
    return long2ip(-$longip);
    // IPV4转换32位
    /*$str = sprintf("%032s", decbin((float)$longip));
    $arr = array();
    for ($i = 0; $i < 4; ++$i) {
        $arr[] = bindec(substr($str, $i * 8, 8));
    }
    return implode('.', $arr);*/
}
// IPV6转数字
function ip2long_v6($ip)
{
    $ip_n = inet_pton($ip);
    $bin = '';
    for ($bit = strlen($ip_n) - 1; $bit >= 0; $bit--) {
        $bin = sprintf('%08b', ord($ip_n[$bit])) . $bin;
    }
    if (function_exists('gmp_init')) {
        return gmp_strval(gmp_init($bin, 2), 10);
    } elseif (function_exists('bcadd')) {
        $dec = '0';
        for ($i = 0; $i < strlen($bin); $i++) {
            $dec = bcmul($dec, '2', 0);
            $dec = bcadd($dec, $bin[$i], 0);
        }
        return $dec;
    } else {
        trigger_error('GMP or BCMATH extension not installed!', E_USER_ERROR);
    }
}
// 转IPV6
function long2ip_v6($dec)
{
    if (function_exists('gmp_init')) {
        $bin = gmp_strval(gmp_init($dec, 10), 2);
    } elseif (function_exists('bcadd')) {
        $bin = '';
        do {
            $bin = bcmod($dec, '2') . $bin;
            $dec = bcdiv($dec, '2', 0);
        } while (bccomp($dec, '0'));
    } else {
        trigger_error('GMP or BCMATH extension not installed!', E_USER_ERROR);
    }
    $bin = str_pad($bin, 128, '0', STR_PAD_LEFT);
    $ip = array();
    for ($bit = 0; $bit <= 7; $bit++) {
        $bin_part = substr($bin, $bit * 16, 16);
        $ip[] = dechex(bindec($bin_part));
    }
    $ip = implode(':', $ip);
    return inet_ntop(inet_pton($ip));
}
// 安全获取用户IP，信任 CDN 发过来的 X-FORWARDED-FOR
/*
function ip() {
	global $conf;
	$ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1'; // 如果有 CDN 的时候，为离服务器最近的 IP
	if(empty($conf['cdn_ip']) || empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		return $ip;
	} else {
		// 判断 cdnip 合法性，严格过滤 HTTP_X_FORWARDED_FOR
		// X-Forwarded-For: client1, proxy1, proxy2, ...
		// 离服务器最最近的为最后一个 proxy2，应该在 $conf['cdn_ip'] 当中才安全可信
		foreach($conf['cdn_ip'] as $cdnip) {
			$pos1 = strrpos($cdnip, '.');
			$pos2 = strrpos($ip, '.');
			// 合法 CDN IP 段
			if($ip == $cdnip || ($pos1 == $pos2 && substr($cdnip, $pos1) == '.*' && substr($cdnip, 0, $pos1) == substr($ip, 0, $pos2))) {
				$userips = !empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['HTTP_X_REAL_IP'];
				if(empty($userips)) return $ip; // 此处 CDN 未转发 userip，有错误，可能需要记录日志
				$arr = array_values(array_filter(explode(',', $userips)));
				return long2ip(ip2long(end($arr)));
			}
		}
		return $ip;
	}
}
*/
// 日志记录
function xn_log($s, $file = 'error')
{
    if (0 == DEBUG && FALSE === strpos($file, 'error')) return;
    $time = $_SERVER['time'];
    $ip = $_SERVER['ip'];
    $conf = _SERVER('conf');
    $uid = intval(G('uid')); // xiunophp 未定义 $uid
    $day = date('Ym', $time); // 按照月存放，否则 Ymd 目录太多。
    $mtime = date('Y-m-d H:i:s'); // 默认值为 time()
    $url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
    $logpath = $conf['log_path'] . $day;
    !is_dir($logpath) and mkdir($logpath, 0777, true);
    $s = str_replace(array("\r\n", "\n", "\t"), ' ', $s);
    $s = "<?php exit;?>\t$mtime\t$ip\t$url\t$uid\t$s\r\n";
    @error_log($s, 3, $logpath . "/$file.php");
}
function get__browser()
{
    // 默认为 chrome 标准浏览器
    $browser = array(
        'device' => 'pc', // pc|mobile|pad
        'name' => 'chrome', // chrome|firefox|ie|opera
        'version' => 30,
    );
    $agent = _SERVER('HTTP_USER_AGENT');
    // 主要判断是否为垃圾 IE6789
    if (FALSE !== strpos($agent, 'msie') || FALSE !== stripos($agent, 'trident')) {
        $browser['name'] = 'ie';
        $browser['version'] = 8;
        preg_match('#msie\s*([\d\.]+)#is', $agent, $m);
        if (!empty($m[1])) {
            if (FALSE !== strpos($agent, 'compatible; msie 7.0;')) {
                $browser['version'] = 8;
            } else {
                $browser['version'] = intval($m[1]);
            }
        } else {
            // 匹配兼容模式 Trident/7.0，兼容模式下会有此标志 $trident = 7;
            preg_match('#Trident/([\d\.]+)#is', $agent, $m);
            if (!empty($m[1])) {
                $trident = intval($m[1]);
                4 == $trident and $browser['version'] = 8;
                5 == $trident and $browser['version'] = 9;
                $trident > 5 and $browser['version'] = 10;
            }
        }
    }
    if (isset($_SERVER['HTTP_X_WAP_PROFILE']) || (isset($_SERVER['HTTP_VIA']) && stristr($_SERVER['HTTP_VIA'], "wap") || stripos($agent, 'phone') || stripos($agent, 'mobile') || strpos($agent, 'ipod'))) {
        $browser['device'] = 'mobile';
    } elseif (FALSE !== strpos($agent, 'pad')) {
        $browser['device'] = 'pad';
        $browser['name'] = '';
        $browser['version'] = '';
    } else {
        $robots = array('bot', 'spider', 'slurp');
        foreach ($robots as $robot) {
            if (FALSE !== strpos($agent, $robot)) {
                $browser['name'] = 'robot';
                return $browser;
            }
        }
    }
    return $browser;
}
function check_browser($browser)
{
    if ('ie' == $browser['name'] && $browser['version'] < 8) {
        include _include(APP_PATH . 'view/htm/browser.htm');
        exit;
    }
}
function is_robot()
{
    $agent = _SERVER('HTTP_USER_AGENT');
    $agent = strtolower($agent);
    $robots = array('bot', 'spider', 'slurp');
    foreach ($robots as $robot) {
        if (FALSE !== strpos($agent, $robot)) {
            return TRUE;
        }
    }
    return FALSE;
}
function browser_lang()
{
    // return 'zh-cn';
    $accept = _SERVER('HTTP_ACCEPT_LANGUAGE');
    $accept = substr($accept, 0, strpos($accept, ';'));
    if (FALSE !== strpos($accept, 'ko-kr')) {
        return 'ko-kr';
        // } elseif(FALSE !== strpos($accept, 'en')) {
        // 	return 'en';
    } else {
        return 'zh-cn';
    }
}
// 安全请求一个 URL
// ini_set('default_socket_timeout', 60);
function http_get($url, $cookie = '', $timeout = 30, $times = 3)
{
    if (extension_loaded('curl')) return https_post($url, '', $cookie, $timeout, 'GET');
    $arr = array(
        'http' => array(
            'method' => 'GET',
            'timeout' => $timeout
        )
    );
    $stream = stream_context_create($arr);
    while ($times-- > 0) {
        $s = file_get_contents($url, NULL, $stream, 0, 4096000);
        if (FALSE !== $s) return $s;
    }
    return FALSE;
}
function http_post($url, $post = '', $cookie = '', $timeout = 30, $times = 3)
{
    if (extension_loaded('curl')) return https_post($url, $post, $cookie, $timeout);
    is_array($post) and $post = http_build_query($post);
    is_array($cookie) and $cookie = http_build_query($cookie);
    $stream = stream_context_create(array('http' => array('header' => "Content-type: application/x-www-form-urlencoded\r\nx-requested-with: XMLHttpRequest\r\nCookie: $cookie\r\n", 'method' => 'POST', 'content' => $post, 'timeout' => $timeout)));
    while ($times-- > 0) {
        $s = file_get_contents($url, NULL, $stream, 0, 4096000);
        if (FALSE !== $s) return $s;
    }
    return FALSE;
}
function https_get($url, $cookie = '', $timeout = 30, $times = 1)
{
    return https_post($url, '', $cookie, $timeout, 'GET');
}
function https_post($url, $post = '', $cookie = '', $timeout = 30, $method = 'POST')
{
    $allow_url_fopen = strtolower(ini_get('allow_url_fopen'));
    $allow_url_fopen = (empty($allow_url_fopen) || 'off' == $allow_url_fopen) ? 0 : 1;
    $allow_get_contents = $allow_url_fopen && strtolower(ini_get('user_agent'));
    $allow_curl = extension_loaded('curl');
    if (!$allow_curl && !$allow_get_contents) return xn_error(-1, 'CURL and OpenSSL are not installed on the server.');
    is_array($post) and $post = http_build_query($post);
    is_array($cookie) and $cookie = http_build_query($cookie);
    //$w = stream_get_wrappers(); //  && in_array('https', $w)
    if (!$allow_curl) {
        if ('https://' == substr($url, 0, 8) && !extension_loaded('openssl')) return xn_error(-1, 'CURL and OpenSSL are not installed on the server.');
        $stream = stream_context_create(array('http' => array('header' => "Content-type: application/x-www-form-urlencoded\r\nx-requested-with: XMLHttpRequest\r\nCookie: $cookie\r\n", 'method' => $method, 'content' => $post, 'timeout' => $timeout)));
        $s = file_get_contents($url, NULL, $stream, 0, 4096000);
        return $s;
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    //php5.5跟php5.6中的CURLOPT_SAFE_UPLOAD的默认值不同
    if (class_exists('\CURLFile')) {
        curl_setopt($ch, CURLOPT_SAFE_UPLOAD, true);
    } else {
        defined('CURLOPT_SAFE_UPLOAD') and curl_setopt($ch, CURLOPT_SAFE_UPLOAD, false);
    }
    curl_setopt($ch, CURLOPT_HEADER, 2); // 1/2
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERAGENT, _SERVER('HTTP_USER_AGENT'));
    // 兼容HTTPS
    if (false !== stripos($url, 'https://')) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        //ssl版本控制
        //curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
        curl_setopt($ch, CURLOPT_SSLVERSION, true);
    }
    if ('POST' == $method) {
        curl_setopt($ch, CURLOPT_POST, true);
        // 自动设置Referer
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    }
    $header = array('Content-type: application/x-www-form-urlencoded', 'X-Requested-With: XMLHttpRequest');
    $cookie and $header[] = "Cookie: $cookie";
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    // 使用自动跳转, 安全模式不允许
    (!ini_get('safe_mode') && !ini_get('open_basedir')) && curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    //优先解析 IPv6 超时后IPv4
    //curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
    $data = curl_exec($ch);
    if (curl_errno($ch)) {
        return xn_error(-1, 'Errno' . curl_error($ch));
    }
    if (!$data) {
        curl_close($ch);
        return '';
    }
    list($header, $data) = explode("\r\n\r\n", $data, 2);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if (301 == $http_code || 302 == $http_code) {
        $matches = array();
        preg_match('/Location:(.*?)\n/', $header, $matches);
        $url = trim(array_pop($matches));
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $data = curl_exec($ch);
    }
    curl_close($ch);
    return $data;
}
// 多线程抓取数据，需要CURL支持，一般在命令行下执行，此函数收集于互联网，由 xiuno 整理，经过测试，会导致 CPU 100%。
function http_multi_get($urls)
{
    // 如果不支持，则转为单线程顺序抓取
    $data = array();
    if (!function_exists('curl_multi_init')) {
        foreach ($urls as $k => $url) {
            $data[$k] = https_get($url);
        }
        return $data;
    }
    $multi_handle = curl_multi_init();
    foreach ($urls as $i => $url) {
        $conn[$i] = curl_init($url);
        curl_setopt($conn[$i], CURLOPT_RETURNTRANSFER, 1);
        $timeout = 3;
        curl_setopt($conn[$i], CURLOPT_CONNECTTIMEOUT, $timeout); // 超时 seconds
        curl_setopt($conn[$i], CURLOPT_FOLLOWLOCATION, 1);
        //curl_easy_setopt(curl, CURLOPT_NOSIGNAL, 1);
        curl_multi_add_handle($multi_handle, $conn[$i]);
    }
    do {
        $mrc = curl_multi_exec($multi_handle, $active);
    } while (CURLM_CALL_MULTI_PERFORM == $mrc);
    while ($active and CURLM_OK == $mrc) {
        if (curl_multi_select($multi_handle) != -1) {
            do {
                $mrc = curl_multi_exec($multi_handle, $active);
            } while (CURLM_CALL_MULTI_PERFORM == $mrc);
        }
    }
    foreach ($urls as $i => $url) {
        $data[$i] = curl_multi_getcontent($conn[$i]);
        curl_multi_remove_handle($multi_handle, $conn[$i]);
        curl_close($conn[$i]);
    }
    return $data;
}
// 将变量写入到文件，根据后缀判断文件格式，先备份，再写入，写入失败，还原备份
function file_replace_var($filepath, $replace = array(), $pretty = FALSE)
{
    $ext = file_ext($filepath);
    if ('php' == $ext) {
        $arr = include $filepath;
        $arr = array_merge($arr, $replace);
        $s = "<?php\r\nreturn " . var_export($arr, true) . ";\r\n?>";
        // 备份文件
        file_backup($filepath);
        $r = file_put_contents_try($filepath, $s);
        $r != strlen($s) ? file_backup_restore($filepath) : file_backup_unlink($filepath);
        return $r;
    } elseif ('js' == $ext || 'json' == $ext) {
        $s = file_get_contents_try($filepath);
        $arr = xn_json_decode($s);
        if (empty($arr)) return FALSE;
        $arr = array_merge($arr, $replace);
        $s = xn_json_encode($arr, $pretty);
        file_backup($filepath);
        $r = file_put_contents_try($filepath, $s);
        $r != strlen($s) ? file_backup_restore($filepath) : file_backup_unlink($filepath);
        return $r;
    }
}
function file_backname($filepath)
{
    $filepre = file_pre($filepath);
    $fileext = file_ext($filepath);
    $s = "$filepre.backup.$fileext";
    return $s;
}
function is_backfile($filepath)
{
    return FALSE !== strpos($filepath, '.backup.');
}
// 备份文件
function file_backup($filepath)
{
    $backfile = file_backname($filepath);
    if (is_file($backfile)) return TRUE; // 备份已经存在
    $r = xn_copy($filepath, $backfile);
    clearstatcache();
    return $r && filesize($backfile) == filesize($filepath);
}
// 还原备份
function file_backup_restore($filepath)
{
    $backfile = file_backname($filepath);
    $r = xn_copy($backfile, $filepath);
    clearstatcache();
    $r && filesize($backfile) == filesize($filepath) && xn_unlink($backfile);
    return $r;
}
// 删除备份
function file_backup_unlink($filepath)
{
    $backfile = file_backname($filepath);
    $r = xn_unlink($backfile);
    return $r;
}
function file_get_contents_try($file, $times = 3)
{
    while ($times-- > 0) {
        $fp = fopen($file, 'rb');
        if ($fp) {
            $size = filesize($file);
            if (0 == $size) return '';
            $s = fread($fp, $size);
            fclose($fp);
            return $s;
        } else {
            sleep(1);
        }
    }
    return FALSE;
}
function file_put_contents_try($file, $s, $times = 3)
{
    while ($times-- > 0) {
        $dir = dirname($file);
        if (!is_dir($dir)) @mkdir($dir, 0777, true);
        $tmp = $file . '.tmp.' . getmypid() . '.' . mt_rand(100000, 999999);
        $fp = fopen($tmp, 'wb');
        if ($fp && flock($fp, LOCK_EX)) {
            $n = fwrite($fp, $s);
            fflush($fp);
            version_compare(PHP_VERSION, '5.3.2', '>=') and flock($fp, LOCK_UN);
            fclose($fp);
            $ok = @rename($tmp, $file);
            if (!$ok) {
                $ok = xn_copy($tmp, $file);
                @unlink($tmp);
            }
            clearstatcache();
            return $ok ? $n : FALSE;
        } else {
            $fp && fclose($fp);
            @unlink($tmp);
            sleep(1);
        }
    }
    return FALSE;
}
// 判断一个字符串是否在另外一个字符串里面，分隔符 ,
function in_string($s, $str)
{
    if (!$s || !$str) return FALSE;
    $s = ",$s,";
    $str = ",$str,";
    return FALSE !== strpos($str, $s);
}
function move_upload_file($srcfile, $destfile)
{
    $r = FALSE;
    if (function_exists('is_uploaded_file') && function_exists('move_uploaded_file') && is_uploaded_file($srcfile)) {
        $r = move_uploaded_file($srcfile, $destfile);
        if ($r) return $r;
    }
    $r = xn_copy($srcfile, $destfile);
    return $r;
}
// 文件后缀名，不包含 .
function file_ext($filename, $max = 16)
{
    $ext = strtolower(substr(strrchr($filename, '.'), 1));
    $ext = xn_urlencode($ext);
    strlen($ext) > $max and $ext = substr($ext, 0, $max);
    if (!preg_match('#^\w+$#', $ext)) $ext = 'attach';
    return $ext;
}
// 文件的前缀，不包含最后一个 .
function file_pre($filename, $max = 32)
{
    return substr($filename, 0, strrpos($filename, '.'));
}
// 获取路径中的文件名
function file_name($path)
{
    return substr($path, strrpos($path, '/') + 1);
}
// 在 header 头中发送DEBUG信息
/*function t($name = '') {
	global $starttime;
	header("Time $name:".substr(microtime(1) - $starttime, 0, 7));
}*/
// 获取 http://xxx.com/path/
function http_url_path()
{
    $conf = _SERVER('conf');
    $port = _SERVER('SERVER_PORT');
    $host = _SERVER('HTTP_HOST');
    $https_off = _SERVER('HTTPS', 'off');
    $https = $https_off ? strtolower($https_off) : '';
    $http_proto = _SERVER('HTTP_X_FORWARDED_PROTO');
    $proto = $http_proto ? strtolower(_SERVER('HTTP_X_FORWARDED_PROTO')) : '';
    $len = strrpos($_SERVER['PHP_SELF'], '//');
    FALSE === $len and $len = strrpos($_SERVER['PHP_SELF'], '/');
    $path = substr($_SERVER['PHP_SELF'], 0, $len);
    !isset($conf['url_rewrite_on']) and $conf['url_rewrite_on'] = 0;
    $conf['url_rewrite_on'] < 2 and $path = $path . '/';
    $http = ((443 == $port) || 'https' == $proto || ($https && 'off' != $https)) ? 'https' : 'http';
    return "$http://$host$path";
}
// 将参数添加到 URL
function xn_url_add_arg($url, $k, $v)
{
    $pos = strpos($url, '.html');
    if (FALSE === $pos) {
        return FALSE === strpos($url, '?') ? $url . '&' . $k . '=' . $v : $url . '?' . $k . '=' . $v;
    } else {
        return substr($url, 0, $pos) . '-' . $v . substr($url, $pos);
    }
}
/**
 * URL format: http://www.domain.com/demo/?user-login.htm?a=b&c=d
 * URL format: http://www.domain.com/demo/?user-login.htm&a=b&c=d
 * URL format: http://www.domain.com/demo/user-login.htm?a=b&c=d
 * URL format: http://www.domain.com/demo/user-login.htm&a=b&c=d
 * array(
 *     0 => user,
 *     1 => login
 *     a => b
 *     c => d
 * )
 */
function xn_url_parse($request_url, $conf = array(), $access = '')
{
    $url_access = GLOBALS('url_access');
    if ($url_access || 'manage' == $access) return $_GET;
    if ('access' == $access || !$access) {
        !isset($conf['url_rewrite_on']) and $conf['url_rewrite_on'] = 0;
        if ($conf['url_rewrite_on'] < 2) {
            0 == $conf['url_rewrite_on'] and $request_url = str_replace('/?', '/', $request_url);
            $arr = parse_url($request_url);
            $q = array_value($arr, 'path');
            substr_count($q, '/') > 2 && FALSE === stripos($q, '/install') and http_location('/');
            $pos = strrpos($q, '/');
            FALSE === $pos && $pos = -1;
            $q = substr($q, $pos + 1); // 截取最后一个 / 后面的内容
            // 查找第一个 ? & 进行分割
            $sep = FALSE === strpos($q, '?') ? strpos($q, '&') : FALSE;
            if (FALSE !== $sep) {
                // 对后半部分截取，并且分析
                $front = substr($q, 0, $sep);
                $behind = substr($q, $sep + 1);
            } else {
                $front = $q;
                $behind = '';
            }
            if ('.html' == substr($front, -5)) $front = substr($front, 0, -5);
            $r = $front ? explode('-', $front) : array();
            // 将后半部分合并
            $arr1 = $arr2 = $arr3 = array();
            $behind and parse_str($behind, $arr1);
            // 将 xxx.htm?a=b&c=d 放到后面，并且修正 $_GET
            if (!empty($arr['query'])) {
                parse_str($arr['query'], $arr2);
            } else {
                !empty($_GET) and $_GET = array();
            }
            $arr3 = $arr1 + $arr2;
            if ($arr3) {
                //array_diff_key($arr3, $_GET) || array_diff_key($_GET, $arr3);
                count($arr3) != count($_GET) and $_GET = $arr3;
            } else {
                !empty($_GET) and $_GET = array();
            }
            $r += $arr3;
        } else {
            $r = xn_url_parse_path_format($_SERVER['REQUEST_URI']);
        }
        isset($r[0]) && ('admin' == $r[0] || 'index.php' == $r[0]) and $r[0] = 'index';
    }
    return $r;
}
/**
 * 支持 URL format: http://www.domain.com/user/login?a=1&b=2
 * array(
 *     0 => user,
 *     1 => login,
 *     a => 1,
 *     b => 2
 * )
 */
function xn_url_parse_path_format($s)
{
    $request_url = explode('?', $s);
    $url = str_replace('.html', '', $request_url[0]);
    $url = trim($url, '/');
    $get = explode('/', $url);
    if (!empty($request_url[1])) {
        parse_str($request_url[1], $arr2);
        $get = array_merge($get, $arr2);
    }
    return $get;
}
// 递归遍历目录
function glob_recursive($pattern, $flags = 0)
{
    $files = glob($pattern, $flags);
    foreach (glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
        $files = array_merge($files, glob_recursive($dir . '/' . basename($pattern), $flags));
    }
    return $files;
}
// 递归删除目录，这个函数比较危险，传参一定要小心
function rmdir_recusive($dir, $keepdir = 0)
{
    if ('/' == $dir || './' == $dir || '../' == $dir) return FALSE; // 不允许删除根目录，避免程序意外删除数据。
    if (!is_dir($dir)) return FALSE;
    '/' != substr($dir, -1) and $dir .= '/';
    $files = glob($dir . '*'); // +glob($dir.'.*')
    foreach (glob($dir . '.*') as $v) {
        if (substr($v, -1) != '.' && substr($v, -2) != '..') $files[] = $v;
    }
    $filearr = $dirarr = array();
    if ($files) {
        foreach ($files as $file) {
            if (is_dir($file)) {
                $dirarr[] = $file;
            } else {
                $filearr[] = $file;
            }
        }
    }
    if ($filearr) {
        foreach ($filearr as $file) {
            xn_unlink($file);
        }
    }
    if ($dirarr) {
        foreach ($dirarr as $file) {
            rmdir_recusive($file);
        }
    }
    if (!$keepdir) xn_rmdir($dir);
    return TRUE;
}
function xn_copy($src, $dest)
{
    $r = is_file($src) ? copy($src, $dest) : FALSE;
    return $r;
}
function xn_mkdir($dir, $mod = '', $recusive = '')
{
    $r = !is_dir($dir) ? mkdir($dir, $mod, $recusive) : FALSE;
    return $r;
}
function xn_rmdir($dir)
{
    $r = is_dir($dir) ? rmdir($dir) : FALSE;
    return $r;
}
function xn_unlink($file)
{
    $r = is_file($file) ? unlink($file) : FALSE;
    return $r;
}
function xn_filemtime($file)
{
    return is_file($file) ? filemtime($file) : 0;
}
/*
	实例：
	xn_set_dir(123, APP_PATH.'upload');
	000/000/1.jpg
	000/000/100.jpg
	000/000/100.jpg
	000/000/999.jpg
	000/001/1000.jpg
	000/001/001.jpg
	000/002/001.jpg
*/
function xn_set_dir($id, $dir = './')
{
    $id = sprintf("%09d", $id);
    $s1 = substr($id, 0, 3);
    $s2 = substr($id, 3, 3);
    $dir1 = $dir . $s1;
    $dir2 = $dir . "$s1/$s2";
    !is_dir($dir1) && mkdir($dir1, 0777);
    !is_dir($dir2) && mkdir($dir2, 0777);
    return "$s1/$s2";
}
// 取得路径：001/123
function xn_get_dir($id)
{
    $id = sprintf("%09d", $id);
    $s1 = substr($id, 0, 3);
    $s2 = substr($id, 3, 3);
    return "$s1/$s2";
}
// 递归拷贝目录
function copy_recusive($src, $dst)
{
    '/' == substr($src, -1) and $src = substr($src, 0, -1);
    '/' == substr($dst, -1) and $dst = substr($dst, 0, -1);
    $dir = opendir($src);
    !is_dir($dst) and mkdir($dst);
    while (FALSE !== ($file = readdir($dir))) {
        if (($file != '.') && ($file != '..')) {
            if (is_dir($src . '/' . $file)) {
                copy_recusive($src . '/' . $file, $dst . '/' . $file);
            } else {
                xn_copy($src . '/' . $file, $dst . '/' . $file);
            }
        }
    }
    closedir($dir);
}
// 随机字符
function xn_rand($n = 16)
{
    $str = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';
    $len = strlen($str);
    $return = '';
    for ($i = 0; $i < $n; $i++) {
        $r = mt_rand(1, $len);
        $return .= $str[$r - 1];
    }
    return $return;
}
// 检测文件是否可写，兼容 windows
function xn_is_writable($file)
{
    if (PHP_OS != 'WINNT') {
        return is_writable($file);
    } else {
        // 如果是 windows，比较麻烦，这也只是大致检测，不够精准。
        if (is_file($file)) {
            $fp = fopen($file, 'a+');
            if (!$fp) return FALSE;
            fclose($fp);
            return TRUE;
        } elseif (is_dir($file)) {
            $tmpfile = $file . uniqid() . '.tmp';
            $r = touch($tmpfile);
            if (!$r) return FALSE;
            if (!is_file($tmpfile)) return FALSE;
            xn_unlink($tmpfile);
            return TRUE;
        } else {
            return FALSE;
        }
    }
}
function xn_debug_info()
{
    $db = $_SERVER['db'];
    $starttime = $_SERVER['starttime'];
    $s = '';
    if (DEBUG > 1) {
        $s .= '<fieldset class="fieldset small debug break-all">';
        $s .= '<p>Processed Time:' . (microtime(1) - $starttime) . '</p>';
        if (IN_CMD) {
            foreach ($db->sqls as $sql) {
                $s .= "$sql\r\n";
            }
        } else {
            $s .= "\r\n<ul>\r\n";
            foreach ($db->sqls as $sql) {
                $s .= "<li>$sql</li>\r\n";
            }
            $s .= "</ul>\r\n";
            $s .= '_REQUEST:<br>';
            $s .= xn_txt_to_html(print_r($_REQUEST, 1));
            if (!empty($_SESSION)) {
                $s .= '_SESSION:<br>';
                $s .= xn_txt_to_html(print_r($_SESSION, 1));
            }
            $s .= '';
        }
        $s .= '</fieldset>';
    }
    return $s;
}
// 解码客户端提交的 base64 数据
function base64_decode_file_data($data)
{
    if ('data:' == substr($data, 0, 5)) {
        $data = substr($data, strpos($data, ',') + 1);    // 去掉 data:image/png;base64,
    }
    $data = base64_decode($data);
    return $data;
}
// 输出
function http_404()
{
    header('HTTP/1.1 404 Not Found');
    header('Status: 404 Not Found');
    echo '<h1>404 Not Found</h1>';
    exit;
}
// 无权限访问
function http_403()
{
    header('HTTP/1.1 403 Forbidden');
    header('Status: 403 Forbidden');
    echo '<h1>403 Forbidden</h1>';
    exit;
}
function http_location($url)
{
    header('Location:' . $url);
    exit;
}
// 获取 referer
function http_referer()
{
    $referer = param('referer');
    empty($referer) and $referer = _SERVER('HTTP_REFERER');
    if (FALSE !== strpos($referer, url('user-login')) || FALSE !== strpos($referer, url('user-logout')) || FALSE !== strpos($referer, url('user-create'))) {
        $referer = http_url_path();
    }
    // 安全过滤，只支持站内跳转，不允许跳到外部，否则可能会被 XSS
    $parse_url = parse_url($referer);
    if (isset($parse_url['host']) && $parse_url['host'] != $_SERVER['HTTP_HOST']) $referer = './';
    return $referer;
}
function str_push($str, $v, $sep = '_')
{
    if (empty($str)) return $v;
    if (FALSE === strpos($str, $v . $sep)) {
        return $str . $sep . $v;
    }
    return $str;
}
function y2f($rmb)
{
    $rmb = floor($rmb * 10 * 10);
    return $rmb;
}
// $round: float round ceil floor
function f2y($rmb, $round = 'float')
{
    $rmb = floor($rmb * 100) / 10000;
    if ('float' == $round) {
        $rmb = number_format($rmb, 2, '.', '');
    } elseif ('round' == $round) {
        $rmb = round($rmb);
    } elseif ('ceil' == $round) {
        $rmb = ceil($rmb);
    } elseif ('floor' == $round) {
        $rmb = floor($rmb);
    }
    return $rmb;
}
function url($url, $extra = array(), $url_access = NULL)
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
        $r = $path . '?' . $query . '.html';
    } elseif (1 == $conf['url_rewrite_on']) {
        $r = $path . $query . '.html';
    } elseif (2 == $conf['url_rewrite_on'] || 3 == $conf['url_rewrite_on']) {
        if (FALSE === strpos($query, '-')) {
            $r = $conf['path'] . $query;
        } else {
            $r = $conf['path'] . str_replace('-', '/', $query) . (2 == $conf['url_rewrite_on'] ? '.html' : '');
        }
    }
    $arr = explode('-', $query);
    $filter = array('operate', 'attach', 'read', 'category', 'list', 'my', 'forum', 'thread');
    // 后台链接
    if ((TRUE === $url_access && !in_array($arr[0], $filter, TRUE)) || 3 === $url_access) {
        $r = 'index.php?' . http_build_query($arr);
    }
    /*$json = param('json', 0);
    if ($json) {
        empty($extra) and $extra = array();
        $extra['json'] = $json;
    }*/
    // 附加参数
    if ($extra) {
        $args = http_build_query($extra);
        $sep = FALSE === strpos($r, '?') ? '?' : '&';
        $r .= $sep . $args;
    }
    return $r;
}
// 检测站点的运行级别
function check_runlevel()
{
    global $conf, $method, $gid;
    $rules = array(
        'user' => array('login', 'create', 'logout', 'sendinitpw', 'resetpw', 'resetpw_sendcode', 'resetpw_complete', 'synlogin')
    );
    if (1 == $gid) return;
    $param0 = param(0);
    $param1 = param(1);
    foreach ($rules as $route => $actions) {
        if ($param0 == $route && (empty($actions) || in_array($param1, $actions))) {
            return;
        }
    }
    switch ($conf['runlevel']) {
        case 0:
            message(-1, $conf['runlevel_reason']);
            break;
        case 1:
            message(-1, lang('runlevel_reson_1'));
            break;
        case 2:
            (0 == $gid || 'GET' != $method) and message(-1, lang('runlevel_reson_2'));
            break;
        case 3:
            0 == $gid and message(-1, lang('runlevel_reson_3'));
            break;
        case 4:
            'GET' != $method and message(-1, lang('runlevel_reson_4'));
            break;
            //case 5: break;
    }
}
/*
	message(0, '登录成功');
	message(1, '参数错误');
	message(-1, '数据创建失败');
0成功
1参数错误 2权限错误 3密码错误 4方法错误
-1数据创建或更新失败 -2查询数据不存在 -3数据比对为空 -4参数为空 -5获取数据为空
	code:
		< 0 全局错误，比如：系统错误：数据库丢失连接/文件不可读写
		= 0 正确
		> 0 一般业务逻辑错误，可以定位到具体控件，比如：用户名为空/密码为空
*/
function message($code, $message, $extra = array())
{
    global $json, $header, $conf;
    $arr = $extra;
    $arr['code'] = $code;
    $arr['message'] = $message;
    $header['title'] = $conf['sitename'];
    // 防止 message 本身出现错误死循环
    static $called = FALSE;
    $called ? exit(xn_json_encode($arr)) : $called = TRUE;
    if ($json) {
        echo xn_json_encode($arr);
    } else {
        if (IN_CMD) {
            if (is_array($message) || is_object($message)) {
                print_r($message);
            } else {
                echo $message;
            }
            exit;
        } else {
            if (defined('MESSAGE_HTM_PATH')) {
                include _include(MESSAGE_HTM_PATH);
            } else {
                include _include(APP_PATH . "public/html/message.htm");
            }
        }
    }
    exit;
}
// 上锁
function xn_lock_start($lockname = '', $life = 10)
{
    global $conf, $time;
    $lockfile = $conf['cache_path'] . 'lock_' . $lockname . '.lock';
    if (is_file($lockfile)) {
        // 大于 $life 秒，删除锁
        if ($time - filemtime($lockfile) > $life) {
            xn_unlink($lockfile);
        } else {
            // 锁存在，上锁失败。
            return FALSE;
        }
    }
    $r = file_put_contents($lockfile, $time, LOCK_EX);
    return $r;
}
// 删除锁
function xn_lock_end($lockname = '')
{
    global $conf;
    $lockfile = $conf['cache_path'] . 'lock_' . $lockname . '.lock';
    xn_unlink($lockfile);
}
include CORE_PATH . 'lib/html_safe.php';
function xn_html_safe($doc, $arg = array())
{
    $conf = include APP_PATH . 'config/config.php';
    empty($arg['table_max_width']) and $arg['table_max_width'] = 746; // 这个宽度为 回帖宽度
    $pattern = array(
        //'img_url'=>'#^(https?://[^\'"\\\\<>:\s]+(:\d+)?)?([^\'"\\\\<>:\s]+?)*$#is',
        'img_url' => '#^(((https?://[^\'"\\\\<>:\s]+(:\d+)?)?([^\'"\\\\<>:\s]+?)*)|(data:image/jpg;base64,[\w/+=\/+]+)|(data:image/gif;base64,[\w/+=\/+]+)|(data:image/jpeg;base64,[\w/+=\/+]+)|(data:image/png;base64,[\w/+=\/+]+))$#is',
        'url' => '#^(https?://[^\'"\\\\<>:\s]+(:\d+)?)?([^\'"\\\\<>:\s]+?)*$#is', // '#https?://[\w\-/%?.=]+#is'
        'mailto' => '#^mailto:([\w%\-\.]+)@([\w%\-\.]+)(\.[\w%\-\.]+?)+$#is',
        'ftp_url' => '#^ftp:([\w%\-\.]+)@([\w%\-\.]+)(\.[\w%\-\.]+?)+$#is',
        'ed2k_url' => '#^(?:ed2k|thunder|qvod|magnet)://[^\s\'\"\\\\<>]+$#is',
        'color' => '#^(\#\w{3,6})|(rgb\(\d+,\s*\d+,\s*\d+\)|(\w{3,10}))$#is',
        'safe' => '#^[\w\-:;\.\s\x7f-\xff]+$#is',
        'css' => '#^[\(,\)\#;\w\-\.\s\x7f-\xff]+$#is',
        'word' => '#^[\w\-\x7f-\xff]+$#is',
    );
    if (1 == array_value($conf, 'img_base64')) $pattern['img_url'] = '#^(((https?://[^\'"\\\\<>:\s]+(:\d+)?)?([^\'"\\\\<>:\s]+?)*)|(data:image/jpg;base64,[\w/+=\/+]+)|(data:image/gif;base64,[\w/+=\/+]+)|(data:image/jpeg;base64,[\w/+=\/+]+)|(data:image/png;base64,[\w/+=\/+]+))$#is';
    $white_tag = array(
        'a',
        'b',
        'i',
        'u',
        'font',
        'strong',
        'em',
        'span',
        'table',
        'tr',
        'td',
        'th',
        'tbody',
        'thead',
        'tfoot',
        'caption',
        'ol',
        'ul',
        'li',
        'dl',
        'dt',
        'dd',
        'menu',
        'multicol',
        'h1',
        'h2',
        'h3',
        'h4',
        'h5',
        'h6',
        'hr',
        'p',
        'div',
        'pre',
        'br',
        'img',
        'area',
        'embed',
        'code',
        'blockquote',
        'iframe',
        'section',
        'fieldset',
        'legend'
    );
    $white_value = array(
        'href' => array('pcre', '', array($pattern['url'], $pattern['ed2k_url'])),
        'src' => array('pcre', '', array($pattern['img_url'])),
        'width' => array('range', '', array(0, 4096)),
        'height' => array('range', 'auto', array(0, 80000)),
        'size' => array('range', 4, array(-10, 10)),
        'border' => array('range', 0, array(0, 10)),
        'family' => array('pcre', '', array($pattern['word'])),
        'class' => array('pcre', '', array($pattern['safe'])),
        'face' => array('pcre', '', array($pattern['word'])),
        'color' => array('pcre', '', array($pattern['color'])),
        'alt' => array('pcre', '', array($pattern['safe'])),
        'label' => array('pcre', '', array($pattern['safe'])),
        'title' => array('pcre', '', array($pattern['safe'])),
        'target' => array('list', '_self', array('_blank', '_self')),
        'type' => array('pcre', '', array('#^[\w/\-]+$#')),
        'wmode' => array('list', 'transparent', array('transparent', '')),
        'allowscriptaccess' => array('list', 'never', array('never')),
        'value' => array('list', '', array('#^[\w+/\-]$#')),
        'cellspacing' => array('range', 0, array(0, 10)),
        'cellpadding' => array('range', 0, array(0, 10)),
        'frameborder' => array('range', 0, array(0, 10)),
        'allowfullscreen' => array('list', 'true', array('true', '1', 'on'), 'range', 0, array(0, 10)),
        'align' => array('list', 'left', array('left', 'center', 'right')),
        'valign' => array('list', 'middle', array('middle', 'top', 'bottom')),
        'name' => array('pcre', '', array($pattern['word'])),
    );
    $white_css = array(
        'font' => array('pcre', 'none', array($pattern['safe'])),
        'font-style' => array('pcre', 'none', array($pattern['safe'])),
        'font-weight' => array('pcre', 'none', array($pattern['safe'])),
        'font-family' => array('pcre', 'none', array($pattern['word'])),
        'font-size' => array('range', 12, array(6, 48)),
        'width' => array('range', '100%', array(1, 1800)),
        'height' => array('range', '', array(1, 80000)),
        'min-width' => array('range', 1, array(1, 80000)),
        'min-height' => array('range', 400, array(1, 80000)),
        'max-width' => array('range', 1800, array(1, 80000)),
        'max-height' => array('range', 80000, array(1, 80000)),
        'line-height' => array('range', '14px', array(1, 50)),
        'color' => array('pcre', '#000000', array($pattern['color'])),
        'background' => array('pcre', 'none', array($pattern['color'], '#url\((https?://[^\'"\\\\<>]+?:?\d?)?([^\'"\\\\<>:]+?)*\)[\w\s\-]*$#')),
        'background-color' => array('pcre', 'none', array($pattern['color'])),
        'background-image' => array('pcre', 'none', array($pattern['img_url'])),
        'background-position' => array('pcre', 'none', array($pattern['safe'])),
        'border' => array('pcre', 'none', array($pattern['css'])),
        'border-left' => array('pcre', 'none', array($pattern['css'])),
        'border-right' => array('pcre', 'none', array($pattern['css'])),
        'border-top' => array('pcre', 'none', array($pattern['css'])),
        'border-left-color' => array('pcre', 'none', array($pattern['css'])),
        'border-right-color' => array('pcre', 'none', array($pattern['css'])),
        'border-top-color' => array('pcre', 'none', array($pattern['css'])),
        'border-bottom-color' => array('pcre', 'none', array($pattern['css'])),
        'border-left-width' => array('pcre', 'none', array($pattern['css'])),
        'border-right-width' => array('pcre', 'none', array($pattern['css'])),
        'border-top-width' => array('pcre', 'none', array($pattern['css'])),
        'border-bottom-width' => array('pcre', 'none', array($pattern['css'])),
        'border-bottom-style' => array('pcre', 'none', array($pattern['css'])),
        'margin-left' => array('range', 0, array(0, 100)),
        'margin-right' => array('range', 0, array(0, 100)),
        'margin-top' => array('range', 0, array(0, 100)),
        'margin-bottom' => array('range', 0, array(0, 100)),
        'margin' => array('pcre', '', array($pattern['safe'])),
        'padding' => array('pcre', '', array($pattern['safe'])),
        'padding-left' => array('range', 0, array(0, 100)),
        'padding-right' => array('range', 0, array(0, 100)),
        'padding-top' => array('range', 0, array(0, 100)),
        'padding-bottom' => array('range', 0, array(0, 100)),
        'zoom' => array('range', 1, array(1, 10)),
        'list-style' => array('list', 'none', array('disc', 'circle', 'square', 'decimal', 'lower-roman', 'upper-roman', 'none')),
        'text-align' => array('list', 'left', array('left', 'right', 'center', 'justify')),
        'text-indent' => array('range', 0, array(0, 100)),
        'display' => array('range', 0, array(0, 100)),
    );
    $safehtml = new HTML_White($white_tag, $white_value, $white_css, $arg);
    $result = $safehtml->parse($doc);
    return $result;
}
// 前台访问view目录下文件路径/支持分离
function view_path()
{
    static $path = array();
    if (isset($path['view_path'])) return $path['view_path'];
    $conf = _SERVER('conf');
    $conf_path = $conf['url_rewrite_on'] > 1 ? $conf['path'] : '';
    $path['view_path'] = $conf_path . $conf['view_url'];
    return $path['view_path'];
}
// 附件路径/支持分离 $attach_on 为传入标识，默认不传入读取$conf['attach_on']
function file_path($attach_on = NULL)
{
    $conf = include APP_PATH . 'config/config.php';
    if (NULL !== $attach_on && $conf['attach_on']) {
        if (0 == $attach_on && 1 == $conf['attach_on']) {
            // 云储存
            $path = $conf['cloud_url'] . $conf['upload_url'];
        } elseif ($attach_on && $attach_on == $conf['attach_on']) {
            // 云储存接口
            $path = $conf['cloud_url'] . $conf['upload_url'];
        } else {
            // 本地
            $path = $conf['url_rewrite_on'] > 1 ? $conf['path'] . $conf['upload_url'] : $conf['upload_url'];
        }
    } else {
        // 本地
        $path = $conf['url_rewrite_on'] > 1 ? $conf['path'] . $conf['upload_url'] : $conf['upload_url'];
    }
    return $path;
}
// 后台访问view目录下文件路径/支持分离
function admin_view_path()
{
    static $path = array();
    if (isset($path['admin_view_path'])) return $path['admin_view_path'];
    $conf = _SERVER('conf');
    $path['admin_view_path'] = 'view/' == $conf['view_url'] ? '../' . $conf['view_url'] : $conf['view_url'];
    return $path['admin_view_path'];
}
// 后台处理头像或主题缩略图、自定义图标
function admin_access_file($icon = 0, $icon_fmt = '')
{
    global $conf;
    if (empty($icon_fmt)) return $icon_fmt;
    $local = FALSE;
    // 本地未分离
    if ($icon) {
        // 上传文件
        if (0 == $conf['attach_on']) $local = TRUE;
    } else {
        // icon 默认图片，view 目录
        if ('view/' == $conf['view_url']) $local = TRUE;
    }
    if ($local) {
        // 伪静态 1 追加 ../ 伪静态 2 追加 ..
        $icon_fmt = $conf['url_rewrite_on'] < 2 ? '../' . $icon_fmt : '..' . $icon_fmt;
    }
    return $icon_fmt;
}
// 后台处理内容图、附件路径
function admin_attach_path()
{
    global $conf;
    static $cache = array();
    $key = 'admin_attach_path';
    if (isset($cache[$key])) return $cache[$key];
    $cache[$key] = '';
    // 未分离图片
    if (0 == $conf['attach_on']) {
        // 伪静态 1 追加 ../
        if ($conf['url_rewrite_on'] < 2) {
            $cache[$key] = '../';
        } else {
            $cache[$key] = '..';
        }
    }
    return $cache[$key];
}
// 设置token
function bull_token_set($uid = 0, $safe_key = '')
{
    $key = 'safe_token_' . $uid;
    static $cache = array();
    if (isset($cache[$key])) return $cache[$key];
    if ($uid) {
        $user = user_read_cache($uid);
        if (empty($user)) return FALSE;
        $pwd = md5($user['password']);
    } else {
        $useragent = _SERVER('HTTP_USER_AGENT');
        $pwd = md5($useragent);
    }
    $cache[$key] = bull_token_gen($uid, $pwd);
    $safe_key and $_SESSION[$safe_key] = md5($cache[$key]);
    return $cache[$key];
}
/*
 * @param $uid 当前用户UID
 * @param $token 获取的token
 * @param int $safe_key 验证当前页token 为空不验证
 * @param int $life token 生命期
 * @return bool|mixed|string 返回 token 验证成功 / FALSE 验证失败
 */
function bull_token_verify($uid, $token, $safe_key = '', $life = 3600)
{
    if (empty($token)) return FALSE;
    if ($safe_key && (empty($_SESSION[$safe_key]) || $_SESSION[$safe_key] != md5($token))) return FALSE;
    $useragent = _SERVER('HTTP_USER_AGENT');
    if ($uid) {
        $user = user_read_cache($uid);
        if (empty($user)) return FALSE;
        $pwd = md5($user['password']);
    } else {
        if (empty($useragent)) return FALSE;
        $pwd = md5($useragent);
    }
    return bull_token_decrypt($token, $uid, $pwd, $safe_key, $life);
}
// 生成token / salt 混淆码用于加解密
function bull_token_gen($uid, $salt = '')
{
    $token_key = md5(xn_key() . $salt);
    $useragent = _SERVER('HTTP_USER_AGENT');
    $ua_md5 = md5($useragent);
    $ip = ip();
    $time = time();
    $token = xn_encrypt("$ip	$uid	$time	$ua_md5", $token_key);
    return $token;
}
// 解密token 正确则返回新token 错误返回FALSE
function bull_token_decrypt($token, $uid, $salt = '', $safe_key = '', $life = 3600)
{
    $ip = ip();
    $time = time();
    $useragent = _SERVER('HTTP_USER_AGENT');
    $token_key = md5(xn_key() . $salt);
    $s = xn_decrypt($token, $token_key);
    if (empty($s)) return FALSE;
    $arr = explode("\t", $s);
    if (count($arr) != 4) return FALSE;
    list($_ip, $_uid, $_time, $ua_md5) = $arr;
    $life < 10 and $life = 1800;
    if ($ua_md5 != md5($useragent) || $time - $_time > $life || $uid != $_uid || $ip != $_ip) return FALSE;
    $new_token = bull_token_gen($uid, $salt);
    if ($safe_key) $_SESSION[$safe_key] = $new_token;
    return $new_token;
}
// 清理token
function bull_token_clear($token = 0)
{
    global $uid, $conf, $time;
    $key = md5($conf['auth_key'] . '_safe_token_' . $uid);
    setcookie($key, '', $time - 1, '/', $conf['cookie_domain'], '', TRUE);
    $token and setcookie(md5($token), 0, $time - 1, '/', $conf['cookie_domain'], '', TRUE);
}
// 格式化数字 1k
function format_number($number)
{
    $number = intval($number);
    if ($number < 1000) return $number;
    if ($number > 1000 && $number < 1000000) {
        // 千
        $return = number_format($number / 1000, 1) . 'K+';
    } elseif ($number > 1000000 && $number < 1000000000) {
        // 百万
        $return = number_format($number / 1000000, 1) . 'M+';
    } elseif ($number > 1000000000) {
        // 10亿
        $return = number_format($number / 1000000000, 1) . 'B+';
    }
    return $return;
}
//---------------表单安全过滤---------------
/*
 * 专门处理表单多维数组安全过滤 指定最终级一维数组key为字符串安全处理
    $filter 为需要按照字符串处理的key数组 array('key1','key2')
    如需按照int型处理时 $filter 数组为空或省略
    $filter = array('name','message','brief');
	bull_param(1, array(), $filter);
    bull_param('warm_up', array(), array('name','message','brief'));
*/
function bull_param($key, $defval = '', $filter = array(), $htmlspecialchars = TRUE, $addslashes = FALSE)
{
    if (!isset($_REQUEST[$key]) || (0 == $key && empty($_REQUEST[$key]))) {
        if (is_array($defval)) {
            return array();
        } else {
            return $defval;
        }
    }
    $val = $_REQUEST[$key];
    $val = bull_param_force($val, $filter, $htmlspecialchars, $addslashes);
    return $val;
}
function bull_param_force($val, $filter, $htmlspecialchars, $addslashes)
{
    if (empty($val)) return array();
    foreach ($val as $k => &$v) {
        if (is_array($v)) {
            $v = bull_mulit_array_safe($v, array(), $filter, $htmlspecialchars, $addslashes);
        } else {
            $defval = bull_safe_defval($k, $filter);
            $v = bull_safe($v, $defval, $htmlspecialchars, $addslashes);
        }
    }
    return $val;
}
// 遍历多维数组安全过滤 $filter一维数组中能找到的一律按照字符处理
function bull_mulit_array_safe($array, $arr, $filter, $htmlspecialchars, $addslashes)
{
    if (is_array($array)) {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                bull_mulit_array_safe($value, $arr[$key], $filter, $htmlspecialchars, $addslashes);
            } else {
                $defval = bull_safe_defval($key, $filter);
                $arr[$key] = bull_safe($value, $defval, $htmlspecialchars, $addslashes);
            }
        }
    }
    return $arr;
}
// 返回1则按照字符串处理
function bull_safe_defval($key, $filter)
{
    $defval = 0;
    if (is_array($filter)) {
        // 限定的 key值 按照字符串处理
        $defval = in_array($key, $filter) ? 1 : 0;
    }
    return $defval;
}
// 参数安全处理
function bull_safe($val, $defval, $htmlspecialchars, $addslashes)
{
    $get_magic_quotes_gpc = _SERVER('get_magic_quotes_gpc');
    // 处理字符串
    if (1 == $defval) {
        //$val = trim($val);
        $val = isset($val) ? $val : '';
        $addslashes and empty($get_magic_quotes_gpc) && $val = addslashes($val);
        empty($addslashes) and $get_magic_quotes_gpc && $val = stripslashes($val);
        $htmlspecialchars and $val = htmlspecialchars($val, ENT_QUOTES);
    } else {
        $val = intval($val);
    }
    return $val;
}
// 专门处理表单多维数组安全过滤 哪些表单限定数字
// bull_mulit_array_int(array(), array('id','fid'));
function bull_mulit_array_int($array = array(), $filter = array())
{
    if (empty($array)) return;
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            bull_mulit_array_int($value, $filter);
        } else {
            if (in_array($key, $filter) && !is_numeric($value)) message(1, lang('type_error'));
        }
    }
}
//---------------表单安全过滤结束---------------
/*
 * @param $str 转换字符串
 * @param string $charset 转换编码
 * @param string $original 字符串原始编码
 * @return string
 */
function code_conversion($str, $charset = 'utf-8', $original = '')
{
    if ($original) return iconv($original, $charset . '//IGNORE', $str);
    $list = array('gb2312', 'big5', 'ascii', 'gbk', 'utf-16', 'ucs-2', 'utf-8');
    $encoding_list = $charset == 'utf-8' ? $list : array('utf-8', 'utf-16', 'ascii', 'gb2312', 'gbk');
    $encoding = mb_detect_encoding($str, $encoding_list);
    // 强制转换
    $encoding = in_array($encoding, $list) ? $encoding : $charset;
    return mb_convert_encoding($str, $charset, $encoding);
}
// 过滤用户昵称里面的特殊字符
function filter_username($username)
{
    $username = preg_replace_callback('/./u', "filter_emoji", $username);
    return $username;
}
// emoji过滤
function filter_emoji($match)
{
    return strlen($match[0]) >= 4 ? '' : $match[0];
}
// check plugin installation / $dir插件目录名
function check_plugin($dir, $file = NULL, $return = FALSE)
{
    $r = pull_plugin_info($dir);
    if (empty($r)) return FALSE;
    $destpath = APP_PATH . 'plugin/' . $dir . '/';
    if ($file) {
        $getfile = $destpath . $file;
        $str = file_get_contents($getfile);
        return $return ? htmlspecialchars($str) : $str;
    } else {
        if ($r['installed'] && $r['enable']) {
            return TRUE;
        } else {
            return FALSE;
        }
    }
}
// pull plugin info
function pull_plugin_info($dir)
{
    $destpath = APP_PATH . 'plugin/' . $dir . '/';
    if (!file_exists($destpath)) return FALSE;
    $conffile = $destpath . 'conf.json';
    $r = xn_json_decode(file_get_contents($conffile));
    return $r;
}
// 0:pc 1:wechat 2:pad 3:mobile
function get_device()
{
    $agent = _SERVER('HTTP_USER_AGENT');
    static $cache = array();
    $md5 = md5($agent);
    if (isset($cache[$md5])) return $cache[$md5];
    if (FALSE !== strpos($agent, 'MicroMessenger')) {
        $cache[$md5] = 1; // 微信
    } elseif (strpos($agent, 'pad') || strpos($agent, 'Pad')) {
        $cache[$md5] = 2; // pad
    } elseif (isset($_SERVER['HTTP_X_WAP_PROFILE']) || (isset($_SERVER['HTTP_VIA']) && stristr($_SERVER['HTTP_VIA'], "wap") || stripos($agent, 'phone') || stripos($agent, 'mobile') || strpos($agent, 'ipod'))) {
        $cache[$md5] = 3; // 手机
    } else {
        $cache[$md5] = 0;
    }
    return $cache[$md5];
}
// random string, no number
function rand_str($length)
{
    $str = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    return substr(str_shuffle($str), 26, $length);
}
// html换行转换为\r\n
function br_to_chars($data)
{
    //$data = htmlspecialchars_decode($data);
    return str_replace("<br>", "\r\n", $data);
}
// 直接传message 也可以传数组$arr = array('message' => message, 'doctype' => 1, 'gid' => $gid)
// 格式转换: 类型，0: html, 1: txt; 2: markdown; 3: ubb
// 入库时进行转换，编辑时再转码
function code_safe($arr)
{
    if (empty($arr)) return array();
    // 如果没有传doctype变量 默认为 0 安全格式
    $doctype = isset($arr['doctype']) ? intval($arr['doctype']) : 0;
    $gid = empty($arr['gid']) ? 0 : intval($arr['gid']);
    $message = isset($arr['message']) ? $arr['message'] : $arr;
    if ($message) {
        // 格式转换: 类型，0: html, 1: txt; 2: markdown; 3: ubb
        $message = htmlspecialchars($message, ENT_QUOTES);
        // html格式过滤不安全代码 管理员html格式时不转换
        0 == $doctype && $message = group_access($gid, 'managecontent') ? $message : xn_html_safe($message);
        // text转html格式\r\n会被转换html代码
        1 == $doctype && $message = xn_txt_to_html($message);
    }
    return $message;
}
// 过滤所有html标签
function filter_all_html($text)
{
    $text = trim($text);
    $text = stripslashes($text);
    $text = strip_tags($text);
    $text = str_replace(array('&nbsp;', '/', "\t", "\r\n", "\r", "\n", '  ', '   ', '    ', '	'), '', $text);
    //$text = htmlspecialchars($text, ENT_QUOTES); // 入库前保留干净，入库时转码 输出时无需htmlspecialchars_decode()
    return $text;
}
function filter_html($text)
{
    global $config;
    $filter = array_value($config, 'filter');
    $arr = array_value($filter, 'content');
    $html_enable = array_value($arr, 'html_enable');
    $html_tag = array_value($arr, 'html_tag');
    if (0 == $html_enable || empty($html_tag)) return TRUE;
    $html_tag = htmlspecialchars_decode($html_tag);
    $text = trim($text);
    $text = stripslashes($text);
    $text = strip_tags($text, "$html_tag"); // 需要保留的字符在后台设置
    $text = str_replace(array("\r\n", "\r", "\n", '  ', '   ', '    ', '	'), '', $text);
    //$text = preg_replace('#\s+#', '', $text);//空白区域 会过滤图片等
    //$text = preg_replace("#<(.*?)>#is", "", $text);
    // 过滤所有的style
    $text = preg_replace("#style=.+?['|\"]#i", '', $text);
    // 过滤所有的class
    $text = preg_replace("#class=.+?['|\"]#i", '', $text);
    // 获取img= 过滤标签中其他属性
    $text = preg_replace('#(<img.*?)(class=.+?[\'|\"])|(data-src=.+?[\'|"])|(data-type=.+?[\'|"])|(data-ratio=.+?[\'|"])|(data-s=.+?[\'|"])|(data-fail=.+?[\'|"])|(crossorigin=.+?[\'|"])|((data-w)=[\'"]+[0-9]+[\'"]+)|(_width=.+?[\'|"]+)|(_height=.+?[\'|"]+)|(style=.+?[\'|"])|((width)=[\'"]+[0-9]+[\'"]+)|((height)=[\'"]+[0-9]+[\'"]+)#i', '$1', $text);
    return $text;
}
// filter keyword
function filter_keyword($keyword, $type, &$error)
{
    global $config;
    $filter = array_value($config, 'filter');
    $arr = array_value($filter, $type);
    $enable = array_value($arr, 'enable');
    $wordarr = array_value($arr, 'keyword');
    if (0 == $enable || empty($wordarr)) return FALSE;
    foreach ($wordarr as $_keyword) {
        if (!$_keyword) continue;
        $r = strpos(strtolower($keyword), strtolower($_keyword));
        if (FALSE !== $r) {
            $error = $_keyword;
            return TRUE;
        }
    }
    return FALSE;
}
// return http://domain.com OR https://domain.com
function url_prefix()
{
    $http = ((isset($_SERVER['HTTPS']) && 'on' == $_SERVER['HTTPS']) || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
    return $http . $_SERVER['HTTP_HOST'];
}
// 唯一身份ID
function uniq_id()
{
    return uniqid(substr(md5(microtime(true) . mt_rand(1000, 9999)), 8, 8));
}
// 生成订单号 14位
function trade_no()
{
    $trade_no = str_replace('.', '', microtime(1));
    $strlen = mb_strlen($trade_no, 'UTF-8');
    $strlen = 14 - $strlen;
    $str = '';
    if ($strlen) {
        for ($i = 0; $i <= $strlen; $i++) {
            if ($i < $strlen) $str .= '0';
        }
    }
    return $trade_no . $str;
}
// 生成订单号 16位
function trade_no_16()
{
    $explode = explode(' ', microtime());
    $trade_no = $explode[1] . mb_substr($explode[0], 2, 6, 'UTF-8');
    return $trade_no;
}
// 当前年的天数
function date_year($time = NULL)
{
    $time = intval($time) ? $time : time();
    return date('L', $time) + 365;
}
// 当前年份中的第几天
function date_z($time = NULL)
{
    $time = intval($time) ? $time : time();
    return date('z', $time);
}
// 当前月份中的第几天，没有前导零 1 到 31
function date_j($time = NULL)
{
    $time = intval($time) ? $time : time();
    return date('j', $time);
}
// 当前月份中的第几天，有前导零的2位数字 01 到 31
function date_d($time = NULL)
{
    $time = intval($time) ? $time : time();
    return date('d', $time);
}
// 当前时间为星期中的第几天 数字表示 1表示星期一 到 7表示星期天
function date_w_n($time = NULL)
{
    $time = intval($time) ? $time : time();
    return date('N', $time);
}
// 当前日第几周
function date_d_w($time = NULL)
{
    $time = intval($time) ? $time : time();
    return date('W', $time);
}
// 当前几月 没有前导零1-12
function date_n($time = NULL)
{
    $time = intval($time) ? $time : time();
    return date('n', $time);
}
// 当前月的天数
function date_t($time = NULL)
{
    $time = intval($time) ? $time : time();
    return date('t', $time);
}
// 0 o'clock on the day
function clock_zero()
{
    return strtotime(date('Ymd'));
}
// 24 o'clock on the day
function clock_twenty_four()
{
    return strtotime(date('Ymd')) + 86400;
}
// 8点过期 / expired at 8 a.m.
function eight_expired($time = NULL)
{
    $time = intval($time) ? $time : time();
    // 当前时间大于8点则改为第二天8点过期
    $life = date('G') <= 8 ? (strtotime(date('Ymd')) + 28800 - $time) : clock_twenty_four() - $time + 28800;
    return $life;
}
// 24点过期 / expired at 24 a.m.
function twenty_four_expired($time = NULL)
{
    $time = intval($time) ? $time : time();
    $twenty_four = clock_twenty_four();
    $life = $twenty_four - $time;
    return $life;
}
/**
 * @param $url 提交地址
 * @param string $post POST数组 / 空为GET获取数据 / $post='GET'获取连续跳转最终URL
 * @param string $cookie cookie
 * @param int $timeout 超时
 * @param int $ms 设为1是毫秒
 * @return mixed    返回数据
 */
function https_request($url, $post = '', $cookie = '', $timeout = 30, $ms = 0)
{
    if (empty($url)) return FALSE;
    if (version_compare(PHP_VERSION, '5.2.3', '<')) {
        $ms = 0;
        $timeout = 30;
    }
    is_array($post) and $post = http_build_query($post);
    // 没有安装curl 使用http的形式，支持post
    if (!extension_loaded('curl')) {
        //throw new Exception('server not install CURL');
        if ($post) {
            return https_post($url, $post, $cookie, $timeout);
        } else {
            return http_get($url, $cookie, $timeout);
        }
    }
    is_array($cookie) and $cookie = http_build_query($cookie);
    $curl = curl_init();
    // 返回执行结果，不输出
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    //php5.5跟php5.6中的CURLOPT_SAFE_UPLOAD的默认值不同
    if (class_exists('\CURLFile')) {
        curl_setopt($curl, CURLOPT_SAFE_UPLOAD, true);
    } else {
        defined('CURLOPT_SAFE_UPLOAD') and curl_setopt($curl, CURLOPT_SAFE_UPLOAD, false);
    }
    // 设定请求的RUL
    curl_setopt($curl, CURLOPT_URL, $url);
    // 设定返回信息中包含响应信息头
    if (ini_get('safe_mode') && ini_get('open_basedir')) {
        // $post参数必须为GET
        if ('GET' == $post) {
            // 安全模式时将头文件的信息作为数据流输出
            curl_setopt($curl, CURLOPT_HEADER, true);
            // 安全模式采用连续抓取
            curl_setopt($curl, CURLOPT_NOBODY, true);
        }
    } else {
        curl_setopt($curl, CURLOPT_HEADER, false);
        // 允许跳转10次
        curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
        // 使用自动跳转，返回最后的Location
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    }
    $ua1 = 'Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1';
    $ua = empty($_SERVER["HTTP_USER_AGENT"]) ? $ua1 : $_SERVER["HTTP_USER_AGENT"];
    curl_setopt($curl, CURLOPT_USERAGENT, $ua);
    // 兼容HTTPS
    if (FALSE !== stripos($url, 'https://')) {
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        //ssl版本控制
        //curl_setopt($curl, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
        curl_setopt($curl, CURLOPT_SSLVERSION, true);
    }
    $header = array('Content-type: application/x-www-form-urlencoded;charset=UTF-8', 'X-Requested-With: XMLHttpRequest');
    $cookie and $header[] = "Cookie: $cookie";
    curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
    if ($post) {
        // POST
        curl_setopt($curl, CURLOPT_POST, true);
        // 自动设置Referer
        curl_setopt($curl, CURLOPT_AUTOREFERER, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
    }
    if ($ms) {
        curl_setopt($curl, CURLOPT_NOSIGNAL, true); // 设置毫秒超时
        curl_setopt($curl, CURLOPT_TIMEOUT_MS, intval($timeout)); // 超时毫秒
    } else {
        curl_setopt($curl, CURLOPT_TIMEOUT, intval($timeout)); // 秒超时
    }
    //优先解析 IPv6 超时后IPv4
    //curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    curl_setopt($curl, CURLOPT_ENCODING, 'gzip');
    // 返回执行结果
    $output = curl_exec($curl);
    // 有效URL，输出URL非URL页面内容 CURLOPT_RETURNTRANSFER 必须为false
    'GET' == $post and $output = curl_getinfo($curl, CURLINFO_EFFECTIVE_URL);
    curl_close($curl);
    return $output;
}
function save_image($img)
{
    $ch = curl_init();
    // 设定请求的RUL
    curl_setopt($ch, CURLOPT_URL, $img);
    // 设定返回信息中包含响应信息头 启用时会将头文件的信息作为数据流输出
    //curl_setopt($ch, CURLOPT_HEADER, false);
    //curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER["HTTP_USER_AGENT"]);
    // true表示$html,false表示echo $html
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    //curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
    //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
    curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
}
// 计算字串宽度:剧中对齐(字体大小/字串内容/字体链接/背景宽度/倍数)
function calculate_str_width($size, $str, $font, $width, $multiple = 2)
{
    $box = imagettfbbox($size, 0, $font, $str);
    return ($width - $box[4] - $box[6]) / $multiple;
}
// 搜索目录下的文件 比对文件后缀
function search_directory($path)
{
    if (is_dir($path)) {
        $paths = scandir($path);
        foreach ($paths as $val) {
            $sub_path = $path . '/' . $val;
            if ('.' == $val || '..' == $val) {
                continue;
            } else if (is_dir($sub_path)) {
                //echo '目录名:' . $val . '<br/>';
                search_directory($sub_path);
            } else {
                //echo ' 最底层文件: ' . $path . '/' . $val . ' <hr/>';
                $ext = strtolower(file_ext($sub_path));
                if (in_array($ext, array('php', 'asp', 'jsp', 'cgi', 'exe', 'dll'), TRUE)) {
                    echo '异常文件：' . $sub_path . ' <hr/>';
                }
            }
        }
    }
}
// 一维数组转字符串 $sign待签名字符串 $url为urlencode转码GET参数字符串
function array_to_string($arr, &$sign = '', &$url = '')
{
    if (count($arr) != count($arr, 1)) throw new Exception('Does not support multi-dimensional array to string');
    // 注销签名
    unset($arr['sign']);
    // 排序
    ksort($arr);
    reset($arr);
    // 转字符串做签名
    $url = '';
    $sign = '';
    foreach ($arr as $key => $val) {
        if (empty($val) || is_array($val)) continue;
        $url .= $key . '=' . urlencode($val) . '&';
        $sign .= $key . '=' . $val . '&';
    }
    $url = substr($url, 0, -1);
    $url = htmlspecialchars($url);
    $sign = substr($sign, 0, -1);
}
// 私钥生成签名
function rsa_create_sign($data, $key, $sign_type = 'RSA')
{
    if (!function_exists('openssl_sign')) throw new Exception('OpenSSL extension is not enabled');
    if (!defined('OPENSSL_ALGO_SHA256')) throw new Exception('Only versions above PHP 5.4.8 support SHA256');
    $key = wordwrap($key, 64, "\n", true);
    if (FALSE === $key) throw new Exception('Private Key Error');
    $key = "-----BEGIN RSA PRIVATE KEY-----\n$key\n-----END RSA PRIVATE KEY-----";
    if ('RSA2' == $sign_type) {
        openssl_sign($data, $sign, $key, OPENSSL_ALGO_SHA256);
    } else {
        openssl_sign($data, $sign, $key, OPENSSL_ALGO_SHA1);
    }
    // 加密
    return base64_encode($sign);
}
// 公钥验证签名
function rsa_verify_sign($data, $sign, $key, $sign_type = 'RSA')
{
    $key = wordwrap($key, 64, "\n", true);
    if (FALSE === $key) throw new Exception('Public Key Error');
    $key = "-----BEGIN PUBLIC KEY-----\n$key\n-----END PUBLIC KEY-----";
    // 签名正确返回1 签名不正确返回0 错误-1
    if ('RSA2' == $sign_type) {
        $result = openssl_verify($data, base64_decode($sign), $key, OPENSSL_ALGO_SHA256);
    } else {
        $result = openssl_verify($data, base64_decode($sign), $key, OPENSSL_ALGO_SHA1);
    }
    return $result === 1;
}
// Array to xml array('appid' => 'appid', 'code' => 'success')
function array_to_xml($arr)
{
    if (!is_array($arr) || empty($arr)) throw new Exception('Array Error');
    $xml = "<xml>";
    foreach ($arr as $key => $val) {
        if (is_numeric($val)) {
            $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
        } else {
            $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
        }
    }
    $xml .= "</xml>";
    return $xml;
}
// Xml to array
function xml_to_array($xml)
{
    if (!$xml) throw new Exception('XML error');
    $old = libxml_disable_entity_loader(true);
    // xml解析
    $result = (array)simplexml_load_string($xml, null, LIBXML_NOCDATA | LIBXML_COMPACT);
    // 恢复旧值
    if (FALSE === $old) libxml_disable_entity_loader(false);
    return $result;
}
// 逐行读取
function bull_import($file)
{
    if ($handle = fopen($file, 'r')) {
        while (!feof($handle)) {
            yield trim(fgets($handle));
        }
        fclose($handle);
    }
}
// 计算总行数
function bull_import_total($file, $key = 'bull_import_total')
{
    static $cache = array();
    if (isset($cache[$key])) return $cache[$key];
    $count = cache_get($key);
    if (NULL === $count) {
        $count = 0;
        $globs = bull_import($file);
        while ($globs->valid()) {
            ++$count;
            $globs->next(); // 指向下一个
        }
        $count and cache_set($key, $count, 300);
    }
    return $cache[$key] = $count;
}
$g_dir_file = FALSE;
function bull_search_dir($path)
{
    global $g_dir_file;
    FALSE === $g_dir_file and $g_dir_file = array();
    if (is_dir($path)) {
        $paths = scandir($path);
        foreach ($paths as $val) {
            $sub_path = $path . '/' . $val;
            if ('.' == $val || '..' == $val) {
                continue;
            } else if (is_dir($sub_path)) {
                bull_search_dir($sub_path);
            } else {
                $g_dir_file[] = $sub_path;
            }
        }
    }
    return $g_dir_file;
}
// 无 Notice 方式的获取超级全局变量中的 key
function _GET($k, $def = NULL)
{
    return isset($_GET[$k]) ? $_GET[$k] : $def;
}
function _POST($k, $def = NULL)
{
    return isset($_POST[$k]) ? $_POST[$k] : $def;
}
function _COOKIE($k, $def = NULL)
{
    return isset($_COOKIE[$k]) ? $_COOKIE[$k] : $def;
}
function _REQUEST($k, $def = NULL)
{
    return isset($_REQUEST[$k]) ? $_REQUEST[$k] : $def;
}
function _ENV($k, $def = NULL)
{
    return isset($_ENV[$k]) ? $_ENV[$k] : $def;
}
function _SERVER($k, $def = NULL)
{
    return isset($_SERVER[$k]) ? $_SERVER[$k] : $def;
}
function GLOBALS($k, $def = NULL)
{
    return isset($GLOBALS[$k]) ? $GLOBALS[$k] : $def;
}
function G($k, $def = NULL)
{
    return isset($GLOBALS[$k]) ? $GLOBALS[$k] : $def;
}
function _SESSION($k, $def = NULL)
{
    global $g_session;
    return isset($_SESSION[$k]) ? $_SESSION[$k] : (isset($g_session[$k]) ? $g_session[$k] : $def);
}
//字符编码转换
function getKeywords($content = "", $num = 3)
{
    global $conf;
    if (empty($content)) {
        return '';
    }
    \PhpAnalysis::$loadInit = false;
    $pa = new \PhpAnalysis('utf-8', 'utf-8', false);
    $pa->LoadDict();
    $pa->SetSource($content);
    $pa->StartAnalysis(true);
    $tags = $pa->GetFinallyKeywords($num); // 获取文章中的n个关键字
    return $tags; //返回关键字
}
function unicodeEncode($str)
{
    preg_match_all('/./u', $str, $matches);
    $unicodeStr = "";
    foreach ($matches[0] as $m) {
        $unicodeStr .= "&#" . base_convert(bin2hex(iconv('UTF-8', "UCS-4", $m)), 16, 10);
    }
    return $unicodeStr;
}
function utf8Encode($str)
{
    preg_match_all('/./u', $str, $matches);
    $unicodeStr = "";
    foreach ($matches[0] as $m) {
        $unicodeStr .= strEncode($m);
    }
    return $unicodeStr;
}
function strEncode($str)
{
    $unicode_codes = convertStringToUnicodeCodePoints($str);
    $escaped = '';
    $prefix = 'x';
    for ($i = 0; $i < count($unicode_codes); ++$i) {
        $code = strtoupper($unicode_codes[$i]);
        $num_ref = "&#" . $prefix . $code . ";";
        $escaped .= $num_ref;
    }
    return $escaped;
}
function convertStringToUnicodeCodePoints($str)
{
    $surrogate_1st = 0;
    $unicode_codes = [];
    for ($i = 0; $i < mb_strlen($str); ++$i) {
        $utf16_code = charCodeAt($str, $i);
        if ($surrogate_1st != 0) {
            if ($utf16_code >= 0xDC00 && $utf16_code <= 0xDFFF) {
                $surrogate_2nd = $utf16_code;
                $unicode_code = ($surrogate_1st - 0xD800) * (1 << 10) + (1 << 16) + ($surrogate_2nd - 0xDC00);
                array_push($unicode_codes, $unicode_code);
            } else {
            }
            $surrogate_1st = 0;
        } else if ($utf16_code >= 0xD800 && $utf16_code <= 0xDBFF) {
            $surrogate_1st = $utf16_code;
        } else {
            array_push($unicode_codes, $utf16_code);
        }
    }
    return $unicode_codes;
}
function charCodeAt($str, $index)
{
    $char = mb_substr($str, $index, 1, 'UTF-8');
    if (mb_check_encoding($char, 'UTF-8')) {
        $ret = mb_convert_encoding($char, "UCS-4BE");
        $ret = unpack("N", $ret);
        $ret = dechex($ret[1]);
        return $ret;
    } else {
        return null;
    }
}
//随机干扰字符
function randchar($str)
{
    $content  = $str;
    $tempList = [' ', '  ', '  ', ' ', '  ', '  ', '  '];
    //，逗号
    $contentArray = explode('，', $content);
    if ($contentArray) {
        foreach ($contentArray as $key => $value) {
            $contentArray[$key] = $value . $tempList[array_rand($tempList, 1)];
        }
        $content = implode('，', $contentArray);
    }
    //。句号
    $contentArray = explode('。', $content);
    if ($contentArray) {
        foreach ($contentArray as $key => $value) {
            $contentArray[$key] = $value . $tempList[array_rand($tempList, 1)];
        }
        $content = implode('。', $contentArray);
    }
    //！感叹号
    $contentArray = explode('！', $content);
    if ($contentArray) {
        foreach ($contentArray as $key => $value) {
            $contentArray[$key] = $value . $tempList[array_rand($tempList, 1)];
        }
        $content = implode('！', $contentArray);
    }
    //？问号
    $contentArray = explode('？', $content);
    if ($contentArray) {
        foreach ($contentArray as $key => $value) {
            $contentArray[$key] = $value . $tempList[array_rand($tempList, 1)];
        }
        $content = implode('？', $contentArray);
    }
    return $content;
}
//蜘蛛标识
function get_robot($ua = '')
{
    $robots = array(
        'baidu' => '百度',
        'google' => 'Google',
        'yahoo' => 'Yahoo',
        'bing' => 'Bing',
        '360s' => '360',
        'soso' => '搜搜',
        'sogou' => '搜狗',
        'bytespider' => '头条',
        'YisouSpider' => '神马',
        'other' => '其他'
    );
    $UA = $ua ? $ua : $_SERVER['HTTP_USER_AGENT'];
    foreach ($robots as $k => $v) {
        if (stripos($UA, $k) !== false) return $v;
    }
    return '其他';
}
//蜘蛛标识结束
//用户UA
function get_os($ua = '')
{
    $UA = $ua ? $ua : $_SERVER['HTTP_USER_AGENT'];
    if (stripos($UA, 'Windows') !== false) {
        if (strpos($UA, 'NT 10.0') !== false) return 'Windows 10';
        if (strpos($UA, 'NT 6.1') !== false) return 'Windows 7';
        if (strpos($UA, 'NT 6.2') !== false) return 'Windows 8';
        if (strpos($UA, 'NT 6.3') !== false) return 'Windows 8.1';
        if (strpos($UA, 'NT 5.1') !== false) return 'Windows XP';
        if (strpos($UA, 'NT 6.0') !== false) return 'Windows Vista';
        if (strpos($UA, 'NT 5.0') !== false) return 'Windows 2000';
        if (strpos($UA, 'NT 5.2') !== false) return 'Windows 2003';
        if (strpos($UA, 'Me') !== false) return 'Windows Me';
        if (strpos($UA, '98') !== false) return 'Windows 98';
        if (strpos($UA, '95') !== false) return 'Windows 95';
        return 'Windows';
    } else if (stripos($UA, 'Android') !== false) {
        return 'Android';
    } else if (stripos($UA, 'Windows Phone OS') !== false) {
        return 'Windows Phone';
    } else if (stripos($UA, 'iPhone') !== false) {
        return 'iPhone';
    } else if (stripos($UA, 'iPad') !== false) {
        return 'iPad';
    } else if (stripos($UA, 'iPod') !== false) {
        return 'iPod';
    } else if (stripos($UA, 'Mac OS') !== false) {
        return 'Mac';
    } else if (stripos($UA, 'Linux') !== false) {
        return 'Linux';
    } else if (stripos($UA, 'Unix') !== false) {
        return 'Unix';
    } else if (stripos($UA, 'BSD') !== false) {
        return 'BSD';
    }
    return '';
}
function get_bd($ua = '')
{
    $UA = $ua ? $ua : $_SERVER['HTTP_USER_AGENT'];
    if (stripos($UA, 'Android') !== false) {
        if (stripos($UA, 'HUAWEI') !== false) {
            return 'HUAWEI';
        } else if (stripos($UA, 'HONOR') !== false) {
            return 'HONOR';
        } else if (stripos($UA, 'XiaoMi') !== false) {
            return 'XIAOMI';
        } else if (stripos($UA, 'Redmi') !== false) {
            return 'REDMI';
        } else if (stripos($UA, 'VIVO') !== false) {
            return 'VIVO';
        } else if (stripos($UA, 'OPPO') !== false) {
            return 'OPPO';
        } else if (stripos($UA, 'Nexus') !== false) {
            return 'NEXUS';
        } else if (stripos($UA, 'Nokia') !== false) {
            return 'NOKIA';
        } else if (stripos($UA, 'SAMSUNG') !== false || stripos($UA, 'SM-') !== false) {
            return 'SAMSUNG';
        }
        return '';
    } else if (stripos($UA, 'Windows Phone OS') !== false) {
        return 'Windows Phone';
    } else if (stripos($UA, 'iPhone') !== false) {
        return 'iPhone';
    } else if (stripos($UA, 'iPad') !== false) {
        return 'iPad';
    } else if (stripos($UA, 'iPod') !== false) {
        return 'iPod';
    }
    return '';
}
function get_bs($ua = '')
{
    $UA = $ua ? $ua : $_SERVER['HTTP_USER_AGENT'];
    if (stripos($UA, 'MSIE') !== false) {
        if (strpos($UA, 'MSIE 11.0') !== false) return 'IE11';
        if (strpos($UA, 'MSIE 10.0') !== false) return 'IE10';
        if (strpos($UA, 'MSIE 9.0') !== false) return 'IE9';
        if (strpos($UA, 'MSIE 8.0') !== false) return 'IE8';
        if (strpos($UA, 'MSIE 7.0') !== false) return 'IE7';
        if (strpos($UA, 'MSIE 6.0') !== false) return 'IE6';
        return 'IE';
    } else if (stripos($UA, 'rv:11.0') !== false) {
        return 'IE11';
    } else if (stripos($UA, 'IEMobile') !== false) {
        return 'IE Mobile';
    } else if (stripos($UA, 'MicroMessenger/') !== false) {
        return 'Wechat';
    } else if (stripos($UA, 'TIM/') !== false) {
        return 'TIM';
    } else if (stripos($UA, 'QQ/') !== false) {
        return 'QQ';
    } else if (stripos($UA, 'Alipay') !== false) {
        return 'Alipay';
    } else if (stripos($UA, 'DingTalk') !== false) {
        return 'DingTalk';
    } else if (stripos($UA, 'MiuiBrowser') !== false) {
        return 'MiuiBrowser';
    } else if (stripos($UA, 'baiduboxapp') !== false) {
        return 'Baidu';
    } else if (stripos($UA, 'Edge') !== false) {
        return 'Edge';
    } else if (stripos($UA, 'Firefox') !== false) {
        return 'Firefox';
    } else if (stripos($UA, 'Opera') !== false || stripos($UA, 'OPR') !== false) {
        return 'Opera';
    } else if (stripos($UA, 'UCWEB') !== false || stripos($UA, 'UCBrowser') !== false) {
        return 'UC';
    } else if (stripos($UA, '360SE') !== false) {
        return '360';
    } else if (stripos($UA, 'LBBROWSER') !== false) {
        return 'LieBao';
    } else if (stripos($UA, 'TaoBrowser') !== false) {
        return 'TaoBao';
    } else if (stripos($UA, 'Maxthon') !== false) {
        return 'Maxthon';
    } else if (stripos($UA, 'TheWorld') !== false) {
        return 'TheWorld';
    } else if (stripos($UA, 'Safari') !== false && stripos($UA, 'Chrome') === false) {
        return 'Safari';
    } else if (stripos($UA, 'Chrome') !== false) {
        return 'Chrome';
    }
    return '';
}
function is_word($s)
{
    $r = preg_match('#^\\w{1,32}$#', $s, $m);
    return $r;
}
function is_mobile($mobile, &$err)
{
    if (!preg_match('#^\d{11}$#', $mobile)) {
        $err = lang('mobile_format_mismatch');
        return FALSE;
    }
    return TRUE;
}
function is_email($email, &$err)
{
    $len = mb_strlen($email, 'UTF-8');
    if (strlen($len) > 32) {
        $err = lang('email_too_long', array('length' => $len));
        return FALSE;
    } elseif (!preg_match('/^[\w\-\.]+@[\w\-\.]+(\.\w+)+$/i', $email)) {
        $err = lang('email_format_mismatch');
        return FALSE;
    }
    return TRUE;
}
function is_username($username, &$err = '')
{
    $len = mb_strlen($username, 'UTF-8');
    if ($len > 16) {
        $err = lang('username_too_long', array('length' => $len));
        return FALSE;
    } elseif (FALSE !== strpos($username, ' ') || FALSE !== strpos($username, '　')) {
        $err = lang('username_cant_include_cn_space');
        return FALSE;
    } elseif (!preg_match('#^[\w\x{4E00}-\x{9FA5}\x{1100}-\x{11FF}\x{3130}-\x{318F}\x{AC00}-\x{D7AF}]+$#u', $username)) { 
        $err = lang('username_format_mismatch');
        return FALSE;
    }
    return TRUE;
}
function is_password($password, &$err = '')
{
    $len = strlen($password);
    if (0 == $len) {
        $err = lang('password_is_empty');
        return FALSE;
    } elseif (32 != $len) {
        $err = lang('password_length_incorrect');
        return FALSE;
    } elseif ($password == 'd41d8cd98f00b204e9800998ecf8427e') {
        $err = lang('password_is_empty');
        return FALSE;
    }
    return TRUE;
}
function form_radio_yes_no($name, $checked = 0)
{
    $checked = intval($checked);
    return form_radio($name, array(1 => lang('yes'), 0 => lang('no')), $checked);
}
function form_radio($name, $arr, $checked = 0, $disabled = FALSE)
{
    empty($arr) && $arr = array(lang('no'), lang('yes'));
    $s = '<div class="d-flex flex-wrap">';
    foreach ((array)$arr as $k => $v) {
        $add = $k == $checked ? ' checked="checked"' : '';
        $add .= FALSE !== $disabled ? ' disabled' : '';
        $s .= "<div class=\"custom-control custom-radio\"><input type=\"radio\" class=\"custom-control-input\" id=\"$name-$v\" name=\"$name\" value=\"$k\"$add /><label class=\"custom-control-label mr-2\" for=\"$name-$v\">$v</label></div>";
    }
    $s .= '</div>';
    return $s;
}
function form_checkbox($name, $checked = 0, $txt = '', $val = 1)
{
    $add = $checked ? ' checked="checked"' : '';
    $s = "<div class=\"custom-control custom-checkbox\"><input class=\"custom-control-input\" type=\"checkbox\" id=\"$name-$val\" name=\"$name\" value=\"$val\" $add /><label class=\"custom-control-label custom-checkbox mr-2\" for=\"$name-$val\">$txt</label></div>";
    return $s;
}
// form_multi_checkbox('flag', array('k1'=>'v1','k2'=>'v2'), array('k1','k2'))
// name  选项内容  被选中选项(选项内容的键名)
function form_multi_checkbox($name, $arr, $checked = array())
{
    $s = '<div class="d-flex flex-wrap">';
    foreach ($arr as $k => $v) {
        $ischecked = in_array($k, $checked) ? ' checked="checked"' : '';
        $_name = $name . '[' . $k . ']';
        $s .= "<div class=\"custom-control custom-checkbox\"><input class=\"custom-control-input\" type=\"checkbox\" id=\"$_name\" name=\"$name-$k\" value=\"$k\" $ischecked /><label class=\"custom-control-label custom-checkbox mr-2\" for=\"$name-$k\">$v</label></div>";
    }
    $s .= '</div>';
    return $s;
}
function form_select($name, $arr, $checked = 0, $id = TRUE, $disabled = FALSE, $multiple = FALSE)
{
    if (empty($arr)) return '';
    $idadd = TRUE == $id ? "id=\"$name\"" : ($id ? "id=\"$id\"" : '');
    $add = FALSE !== $disabled ? ' disabled="disabled"' : '';
    $multiple = FALSE != $multiple ? ' multiple' : '';
    $auto = FALSE == $multiple ? ' w-auto' : '';
    $s = "<select name=\"$name\" class=\"form-control form-select\" $multiple $idadd $add> \r\n";
    $s .= form_options($arr, $checked);
    $s .= "</select> \r\n";
    return $s;
}
function form_options($arr, $checked = 0)
{
    $s = '';
    foreach ((array)$arr as $k => $v) {
        $add = $k == $checked ? ' selected="selected"' : '';
        $s .= "<option value=\"$k\"$add>$v</option> \r\n";
    }
    return $s;
}
function form_text($name, $value, $width = FALSE, $holdplacer = '')
{
    $style = '';
    if (FALSE !== $width) {
        is_numeric($width) and $width .= 'px';
        $style = " style=\"width: $width\"";
    }
    $s = "<input type=\"text\" name=\"$name\" id=\"$name\" placeholder=\"$holdplacer\" value=\"$value\" class=\"form-control\"$style />";
    return $s;
}
function form_hidden($name, $value)
{
    $s = "<input type=\"hidden\" name=\"$name\" id=\"$name\" value=\"$value\" />";
    return $s;
}
function form_textarea($name, $value, $holdplacer = '', $width = FALSE, $height = FALSE)
{
    $style = '';
    if (FALSE !== $width) {
        is_numeric($width) and $width .= 'px';
        is_numeric($height) and $height .= 'px';
        $style = " style=\"width: $width; height: $height; \"";
    }
    $s = "<textarea name=\"$name\" id=\"$name\" placeholder=\"$holdplacer\" class=\"form-control\" $style>$value</textarea>";
    return $s;
}
function form_password($name, $value, $width = FALSE)
{
    $style = '';
    if (FALSE !== $width) {
        is_numeric($width) and $width .= 'px';
        $style = " style=\"width: $width\"";
    }
    $s = "<input type=\"password\" name=\"$name\" id=\"$name\" class=\"form-control\" value=\"$value\" $style />";
    return $s;
}
// form_time('start', '18:00') 为空则当前时间
function form_time($name, $value = 0, $width = FALSE)
{
    $style = '';
    if (FALSE !== $width) {
        is_numeric($width) and $width .= 'px';
        $style = " style=\"width: $width\"";
    }
    $value = $value ? $value : date('H:i');
    $s = "<input type=\"time\" name=\"$name\" id=\"$name\" class=\"form-control\" value=\"$value\" $style />";
    return $s;
}
// form_date('start', '2018-07-05') 为空则当前日期
function form_date($name, $value = 0, $width = FALSE)
{
    $style = '';
    if (FALSE !== $width) {
        is_numeric($width) and $width .= 'px';
        $style = " style=\"width: $width\"";
    }
    $value = $value ? $value : date('Y-m-d');
    $s = "<input type=\"date\" name=\"$name\" id=\"$name\" class=\"form-control\" value=\"$value\" $style />";
    return $s;
}
/**用法
 *
 * echo form_radio_yes_no('radio1', 0);
 * echo form_checkbox('aaa', array('无', '有'), 0);
 *
 * echo form_radio_yes_no('aaa', 0);
 * echo form_radio('aaa', array('无', '有'), 0);
 * echo form_radio('aaa', array('a'=>'aaa', 'b'=>'bbb', 'c'=>'ccc', ), 'b');
 *
 * echo form_select('aaa', array('a'=>'aaa', 'b'=>'bbb', 'c'=>'ccc', ), 'a');
 */
?>
