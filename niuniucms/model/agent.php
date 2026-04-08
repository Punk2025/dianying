<?php
/**
 * 代理线：代理账号、入口跳转、基础日志
 */

function agent_table_ready()
{
    static $ok = null;
    if ($ok !== null) {
        return $ok;
    }
    $db = isset($_SERVER['db']) ? $_SERVER['db'] : null;
    if (!$db || empty($db->tablepre)) {
        return $ok = false;
    }
    $like = addslashes($db->tablepre . 'agent');
    $row = db_sql_find_one("SHOW TABLES LIKE '{$like}'", $db);
    return $ok = !empty($row);
}

function agent_log_table_ready()
{
    static $ok = null;
    if ($ok !== null) {
        return $ok;
    }
    $db = isset($_SERVER['db']) ? $_SERVER['db'] : null;
    if (!$db || empty($db->tablepre)) {
        return $ok = false;
    }
    $like = addslashes($db->tablepre . 'agent_log');
    $row = db_sql_find_one("SHOW TABLES LIKE '{$like}'", $db);
    return $ok = !empty($row);
}

function agent__read($id, $d = null)
{
    if (!agent_table_ready()) {
        return array();
    }
    return db_find_one('agent', array('id' => intval($id)), array(), array(), $d);
}

function agent__read_by_code($code, $d = null)
{
    if (!agent_table_ready()) {
        return array();
    }
    $code = strtoupper(trim((string) $code));
    if ($code === '') {
        return array();
    }
    return db_find_one('agent', array('code' => $code), array(), array(), $d);
}

function agent__find($cond, $orderby, $page, $pagesize, $d = null)
{
    if (!agent_table_ready()) {
        return array();
    }
    return db_find('agent', $cond, $orderby, $page, $pagesize, 'id', array(), $d);
}

function agent__count($cond, $d = null)
{
    if (!agent_table_ready()) {
        return 0;
    }
    return db_count('agent', $cond, $d);
}

function agent__create($arr, $d = null)
{
    if (!agent_table_ready()) {
        return false;
    }
    return db_insert('agent', $arr, $d);
}

function agent__update($id, $arr, $d = null)
{
    if (!agent_table_ready()) {
        return false;
    }
    return db_update('agent', array('id' => intval($id)), $arr, $d);
}

function agent__delete($id, $d = null)
{
    if (!agent_table_ready()) {
        return false;
    }
    return db_delete('agent', array('id' => intval($id)), $d);
}

function agent_admin_list($page, $pagesize = 30)
{
    return agent__find(array(), array('id' => -1), $page, $pagesize);
}

function agent_admin_count()
{
    return agent__count(array());
}

function agent_generate_code($prefix = 'A')
{
    $prefix = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) $prefix));
    if ($prefix === '') {
        $prefix = 'A';
    }
    return $prefix . strtoupper(substr(md5(uniqid((string) mt_rand(), true)), 0, 8));
}

/** @return string[] */
function agent_split_list($s)
{
    $s = str_replace(array('，', "\r", "\n", "\t"), array(',', ',', ',', ''), (string) $s);
    $arr = array_filter(array_map('trim', explode(',', $s)));
    return array_values(array_unique($arr));
}

/** 将 IPv4 转为无符号整数（字符串），失败返回 '' */
function agent_ipv4_to_u32($ip)
{
    $ip = trim((string) $ip);
    if ($ip === '' || !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        return '';
    }
    $n = @ip2long($ip);
    if ($n === false) {
        return '';
    }
    if ($n < 0) {
        $n = sprintf('%u', $n);
    }
    return (string) $n;
}

/** 判断 IPv4 是否命中 CIDR（如 1.2.3.0/24） */
function agent_ipv4_in_cidr($ip, $cidr)
{
    $cidr = trim((string) $cidr);
    if ($cidr === '' || strpos($cidr, '/') === false) {
        return false;
    }
    $parts = explode('/', $cidr, 2);
    $base_ip = trim((string) array_value($parts, 0, ''));
    $prefix = intval(array_value($parts, 1, -1));
    if ($prefix < 0 || $prefix > 32) {
        return false;
    }
    $ip_u = agent_ipv4_to_u32($ip);
    $base_u = agent_ipv4_to_u32($base_ip);
    if ($ip_u === '' || $base_u === '') {
        return false;
    }
    $ip_n = (float) $ip_u;
    $base_n = (float) $base_u;
    $size = pow(2, 32 - $prefix);
    if ($size <= 0) {
        return false;
    }
    return floor($ip_n / $size) === floor($base_n / $size);
}

/** 判断 IP 是否命中列表（支持单 IP、CIDR） */
function agent_ip_in_list($ip, $list_text)
{
    $ip = trim((string) $ip);
    if ($ip === '') {
        return false;
    }
    $items = agent_split_list($list_text);
    if (empty($items)) {
        return false;
    }
    foreach ($items as $one) {
        $rule = trim((string) $one);
        if ($rule === '') {
            continue;
        }
        if (strpos($rule, '/') !== false) {
            if (agent_ipv4_in_cidr($ip, $rule)) {
                return true;
            }
            continue;
        }
        if ($ip === $rule) {
            return true;
        }
    }
    return false;
}

/** 配置里可以填 host 或 url，这里统一提取成 host */
function agent_extract_host($item)
{
    $item = trim((string) $item);
    if ($item === '') {
        return '';
    }
    if (!preg_match('#^https?://#i', $item)) {
        $item = 'https://' . $item;
    }
    $host = parse_url($item, PHP_URL_HOST);
    if (!$host) {
        return '';
    }
    return strtolower(trim($host));
}

function agent_pick_front_host($pool, $fallback = '')
{
    $arr = agent_split_list($pool);
    $hosts = array();
    foreach ($arr as $one) {
        $h = agent_extract_host($one);
        if ($h !== '') {
            $hosts[] = $h;
        }
    }
    if (empty($hosts)) {
        $h = agent_extract_host($fallback);
        return $h !== '' ? $h : '';
    }
    return $hosts[array_rand($hosts)];
}

function agent_rand_label($len = 6)
{
    $len = max(4, min(12, (int) $len));
    $seed = 'abcdefghijklmnopqrstuvwxyz0123456789';
    $max = strlen($seed) - 1;
    $out = '';
    for ($i = 0; $i < $len; $i++) {
        $out .= $seed[mt_rand(0, $max)];
    }
    return $out;
}

function agent_log_add($agent_id, $code, $type, $memo = '')
{
    global $time, $longip;
    if (!agent_log_table_ready()) {
        return false;
    }
    $uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
    $ua = isset($_SERVER['HTTP_USER_AGENT']) ? (string) $_SERVER['HTTP_USER_AGENT'] : '';
    $row = array(
        'agent_id' => (int) $agent_id,
        'code' => strtoupper(trim((string) $code)),
        'type' => substr(trim((string) $type), 0, 32),
        'memo' => substr(trim((string) $memo), 0, 255),
        'longip' => (string) (int) $longip,
        'uri' => substr($uri, 0, 500),
        'ua' => substr($ua, 0, 255),
        'create_date' => (int) $time,
    );
    $r = db_insert('agent_log', $row);
    if ($r !== false && $type !== 'ip_block' && function_exists('agent_ip_geo_enqueue')) {
        $ip = trim((string) agent_longip_to_text((string) (int) $longip));
        agent_ip_geo_enqueue($ip);
    }
    return $r;
}

/** 支持传 IPv4 字符串或 longip 数字，返回 longip 字符串；失败返回 '' */
function agent_ip_to_long($ip_or_long)
{
    $s = trim((string) $ip_or_long);
    if ($s === '') {
        return '';
    }
    if (preg_match('/^\d+$/', $s)) {
        return $s;
    }
    $n = @ip2long($s);
    if ($n === false) {
        return '';
    }
    if ($n < 0) {
        $n = sprintf('%u', $n);
    }
    return (string) $n;
}

function agent_longip_to_text($longip)
{
    if (function_exists('safe_long2ip')) {
        return safe_long2ip($longip);
    }
    $s = trim((string) $longip);
    if (preg_match('/^\d+$/', $s) && strlen($s) <= 10) {
        return @long2ip((int) $s);
    }
    return $s;
}

/** @return array<string,string> */
function agent_ip_geo_cache_load()
{
    global $conf;
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $cache = array();
    $path = rtrim((string) array_value($conf, 'cache_path', './runtime/cache/'), '/\\') . '/agent_ip_geo.json';
    if (is_file($path)) {
        $raw = @file_get_contents($path);
        if ($raw !== false && $raw !== '') {
            $arr = @json_decode($raw, true);
            if (is_array($arr)) {
                $cache = $arr;
            }
        }
    }
    return $cache;
}

function agent_ip_geo_cache_save($cache)
{
    global $conf;
    if (!is_array($cache)) {
        return false;
    }
    $path = rtrim((string) array_value($conf, 'cache_path', './runtime/cache/'), '/\\') . '/agent_ip_geo.json';
    return @file_put_contents($path, json_encode($cache, JSON_UNESCAPED_UNICODE)) !== false;
}

function agent_ip_geo_queue_path()
{
    global $conf;
    return rtrim((string) array_value($conf, 'cache_path', './runtime/cache/'), '/\\') . '/agent_ip_geo_queue.json';
}

/** @return array<string,array> */
function agent_ip_geo_queue_load()
{
    static $queue = null;
    if ($queue !== null) {
        return $queue;
    }
    $queue = array();
    $path = agent_ip_geo_queue_path();
    if (is_file($path)) {
        $raw = @file_get_contents($path);
        if ($raw !== false && $raw !== '') {
            $arr = @json_decode($raw, true);
            if (is_array($arr)) {
                $queue = $arr;
            }
        }
    }
    return $queue;
}

function agent_ip_geo_queue_save($queue)
{
    if (!is_array($queue)) {
        return false;
    }
    $path = agent_ip_geo_queue_path();
    return @file_put_contents($path, json_encode($queue, JSON_UNESCAPED_UNICODE)) !== false;
}

/** 记录待解析 IP：status=0, fail_count 默认 0 */
function agent_ip_geo_enqueue($ip)
{
    global $time;
    $ip = trim((string) $ip);
    if ($ip === '' || !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        return false;
    }
    if ($ip === '127.0.0.1' || strpos($ip, '192.168.') === 0 || strpos($ip, '10.') === 0 || strpos($ip, '172.16.') === 0) {
        return false;
    }
    $cache = agent_ip_geo_cache_load();
    if (isset($cache[$ip]) && $cache[$ip] !== '') {
        return false;
    }
    $queue = agent_ip_geo_queue_load();
    $changed = false;
    if (!isset($queue[$ip]) || !is_array($queue[$ip])) {
        $queue[$ip] = array(
            'ip' => $ip,
            'status' => 0,
            'fail_count' => 0,
            'last_try_at' => 0,
            'last_seen_at' => (int) $time,
            'create_date' => (int) $time,
        );
        $changed = true;
    } else {
        // 已存在则仅刷新最后出现时间，降低写频率（10 分钟更新一次）
        $old_seen = intval(array_value($queue[$ip], 'last_seen_at', 0));
        if ($old_seen < ((int) $time - 600)) {
            $queue[$ip]['last_seen_at'] = (int) $time;
            $changed = true;
        }
    }
    if (!$changed) {
        return false;
    }
    if (count($queue) > 20000) {
        uasort($queue, function ($a, $b) {
            return intval(array_value($a, 'last_seen_at', 0)) <=> intval(array_value($b, 'last_seen_at', 0));
        });
        $queue = array_slice($queue, -15000, null, true);
    }
    return agent_ip_geo_queue_save($queue);
}

/**
 * 返回：IP / 地区（仅读取本地缓存；未命中显示“待解析”）
 */
function agent_longip_with_region($longip)
{
    static $req_cache = array();
    $ip = trim((string) agent_longip_to_text($longip));
    if ($ip === '') {
        return '';
    }
    if (isset($req_cache[$ip])) {
        return $req_cache[$ip];
    }
    if ($ip === '127.0.0.1' || strpos($ip, '192.168.') === 0 || strpos($ip, '10.') === 0 || strpos($ip, '172.16.') === 0) {
        return $req_cache[$ip] = $ip . ' / 内网';
    }
    $cache = agent_ip_geo_cache_load();
    if (isset($cache[$ip]) && $cache[$ip] !== '') {
        return $req_cache[$ip] = $ip . ' / ' . $cache[$ip];
    }
    return $req_cache[$ip] = $ip . ' / 待解析';
}

/** 兜底：从最近日志补齐队列（避免老数据未入队） */
function agent_ip_geo_bootstrap_queue($limit = 200, $days = 30)
{
    if (!agent_log_table_ready()) {
        return 0;
    }
    $limit = max(1, min(1000, (int) $limit));
    $days = max(1, min(180, (int) $days));
    $since = (int) $GLOBALS['time'] - $days * 86400;
    $pre = $GLOBALS['db']->tablepre;
    $raw_limit = $limit * 6;
    $sql = "SELECT longip, MAX(create_date) AS last_date "
        . "FROM {$pre}agent_log WHERE create_date>={$since} "
        . "GROUP BY longip ORDER BY last_date DESC LIMIT {$raw_limit}";
    $rows = db_sql_find($sql);
    if (!is_array($rows) || empty($rows)) {
        return 0;
    }
    $n = 0;
    foreach ($rows as $row) {
        $ip = trim((string) agent_longip_to_text(array_value($row, 'longip', '')));
        if ($ip === '' || !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            continue;
        }
        if (agent_ip_geo_enqueue($ip)) {
            $n++;
            if ($n >= $limit) {
                break;
            }
        }
    }
    return $n;
}

/** 定时任务调用：查 status=0 的 IP 批量解析，失败 fail_count + 1 */
function agent_ip_geo_resolve_batch($limit = 30, $days = 30, $max_fail = 5)
{
    global $conf, $time;
    $limit = max(1, min(200, (int) $limit));
    $days = max(1, min(180, (int) $days));
    $max_fail = max(1, min(20, (int) $max_fail));
    $bootstrap_added = agent_ip_geo_bootstrap_queue($limit * 2, $days);

    $queue = agent_ip_geo_queue_load();
    if (!is_array($queue)) {
        $queue = array();
    }
    $targets = array();
    foreach ($queue as $ip => $item) {
        if (!is_array($item)) {
            continue;
        }
        if (intval(array_value($item, 'status', 0)) !== 0) {
            continue;
        }
        $targets[$ip] = $item;
    }
    uasort($targets, function ($a, $b) {
        return intval(array_value($b, 'last_seen_at', 0)) <=> intval(array_value($a, 'last_seen_at', 0));
    });
    if (count($targets) > $limit) {
        $targets = array_slice($targets, 0, $limit, true);
    }

    $stat = array(
        'bootstrap_added' => (int) $bootstrap_added,
        'picked' => count($targets),
        'resolved' => 0,
        'failed' => 0,
        'marked_failed' => 0,
    );
    if (empty($targets)) {
        return $stat;
    }

    $cache = agent_ip_geo_cache_load();
    $timeout = floatval(array_value($conf, 'agent_ip_geo_timeout', 0.8));
    if ($timeout <= 0 || $timeout > 3) {
        $timeout = 0.8;
    }
    foreach ($targets as $ip => $item) {
        $region = '';
        $ok = false;
        $url = 'http://ip-api.com/json/' . urlencode($ip) . '?lang=zh-CN&fields=status,country,regionName,city';
        $ctx = stream_context_create(array(
            'http' => array(
                'timeout' => $timeout,
            ),
        ));
        $raw = @file_get_contents($url, false, $ctx);
        if ($raw !== false && $raw !== '') {
            $j = @json_decode($raw, true);
            if (is_array($j) && array_value($j, 'status', '') === 'success') {
                $parts = array_filter(array(
                    trim((string) array_value($j, 'country', '')),
                    trim((string) array_value($j, 'regionName', '')),
                    trim((string) array_value($j, 'city', '')),
                ));
                $region = !empty($parts) ? implode(' ', $parts) : '未知';
                $ok = true;
            }
        }
        if ($ok) {
            $cache[$ip] = $region;
            $queue[$ip]['status'] = 1;
            $queue[$ip]['region'] = $region;
            $queue[$ip]['last_try_at'] = (int) $time;
            $queue[$ip]['resolved_at'] = (int) $time;
            $stat['resolved']++;
        } else {
            $fc = intval(array_value($queue[$ip], 'fail_count', 0)) + 1;
            $queue[$ip]['fail_count'] = $fc;
            $queue[$ip]['last_try_at'] = (int) $time;
            if ($fc >= $max_fail) {
                $queue[$ip]['status'] = 2;
                $stat['marked_failed']++;
            } else {
                $queue[$ip]['status'] = 0;
            }
            $stat['failed']++;
        }
    }
    if (count($cache) > 10000) {
        $cache = array_slice($cache, -10000, null, true);
    }
    agent_ip_geo_cache_save($cache);
    agent_ip_geo_queue_save($queue);
    return $stat;
}

/** 手动解析单个 IP（可用于后台按钮） */
function agent_ip_geo_resolve_one($ip, $max_fail = 5)
{
    global $conf, $time;
    $ip = trim((string) $ip);
    if (preg_match('/^\d+$/', $ip)) {
        $ip = trim((string) agent_longip_to_text($ip));
    }
    if ($ip === '' || !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        return array('ok' => false, 'message' => '仅支持 IPv4 地址');
    }
    $max_fail = max(1, min(20, (int) $max_fail));
    agent_ip_geo_enqueue($ip);
    $queue = agent_ip_geo_queue_load();
    if (!isset($queue[$ip]) || !is_array($queue[$ip])) {
        $queue[$ip] = array(
            'ip' => $ip,
            'status' => 0,
            'fail_count' => 0,
            'last_try_at' => 0,
            'last_seen_at' => (int) $time,
            'create_date' => (int) $time,
        );
    }
    $cache = agent_ip_geo_cache_load();
    if (isset($cache[$ip]) && $cache[$ip] !== '') {
        $queue[$ip]['status'] = 1;
        $queue[$ip]['region'] = $cache[$ip];
        $queue[$ip]['resolved_at'] = (int) $time;
        agent_ip_geo_queue_save($queue);
        return array('ok' => true, 'message' => '已命中缓存', 'ip' => $ip, 'region' => $cache[$ip], 'from_cache' => 1);
    }
    $timeout = floatval(array_value($conf, 'agent_ip_geo_timeout', 0.8));
    if ($timeout <= 0 || $timeout > 3) {
        $timeout = 0.8;
    }
    $region = '未知';
    $ok = false;
    $url = 'http://ip-api.com/json/' . urlencode($ip) . '?lang=zh-CN&fields=status,country,regionName,city';
    $ctx = stream_context_create(array('http' => array('timeout' => $timeout)));
    $raw = @file_get_contents($url, false, $ctx);
    if ($raw !== false && $raw !== '') {
        $j = @json_decode($raw, true);
        if (is_array($j) && array_value($j, 'status', '') === 'success') {
            $parts = array_filter(array(
                trim((string) array_value($j, 'country', '')),
                trim((string) array_value($j, 'regionName', '')),
                trim((string) array_value($j, 'city', '')),
            ));
            $region = !empty($parts) ? implode(' ', $parts) : '未知';
            $ok = true;
        }
    }
    if ($ok) {
        $cache[$ip] = $region;
        if (count($cache) > 10000) {
            $cache = array_slice($cache, -10000, null, true);
        }
        agent_ip_geo_cache_save($cache);
        $queue[$ip]['status'] = 1;
        $queue[$ip]['region'] = $region;
        $queue[$ip]['resolved_at'] = (int) $time;
        $queue[$ip]['last_try_at'] = (int) $time;
        agent_ip_geo_queue_save($queue);
        return array('ok' => true, 'message' => '解析成功', 'ip' => $ip, 'region' => $region, 'from_cache' => 0);
    }
    $fc = intval(array_value($queue[$ip], 'fail_count', 0)) + 1;
    $queue[$ip]['fail_count'] = $fc;
    $queue[$ip]['last_try_at'] = (int) $time;
    $queue[$ip]['status'] = ($fc >= $max_fail) ? 2 : 0;
    agent_ip_geo_queue_save($queue);
    return array('ok' => false, 'message' => '解析失败', 'ip' => $ip, 'fail_count' => $fc);
}

/** 是否可疑扫描路径（常见探测文件/目录） */
function agent_is_suspicious_uri($uri)
{
    $u = strtolower(trim((string) $uri));
    if ($u === '') {
        return false;
    }
    $patterns = array(
        '/\\.git',
        '/\\.env',
        '/phpinfo.php',
        '/vendor/',
        '/composer.json',
        '/composer.lock',
        '/\\.svn',
        '/\\.ds_store',
        '/wp-admin',
        '/wp-login.php',
        '/adminer',
        '/mysql',
        '/phpmyadmin',
    );
    foreach ($patterns as $p) {
        if (strpos($u, $p) !== false) {
            return true;
        }
    }
    return false;
}

/** 从 query/header/cookie 解析当前请求代理，返回代理行（需 status=1） */
function agent_resolve_current_agent($set_cookie = true)
{
    global $time;
    if (!agent_table_ready()) {
        return array();
    }
    $code = '';
    $from_param = strtoupper(trim((string) param('agent', '', false)));
    if ($from_param !== '') {
        $code = $from_param;
    } elseif (!empty($_SERVER['HTTP_AGENT'])) {
        $code = strtoupper(trim((string) $_SERVER['HTTP_AGENT']));
    } elseif (!empty($_COOKIE['nn_agent_code'])) {
        $code = strtoupper(trim((string) $_COOKIE['nn_agent_code']));
    }
    if ($code === '' || !preg_match('/^[A-Z0-9_\\-]{4,32}$/', $code)) {
        return array();
    }
    $row = agent__read_by_code($code);
    if (empty($row) || empty($row['status'])) {
        return array();
    }
    if ($set_cookie && !empty($code)) {
        setcookie('nn_agent_code', $code, $time + 86400 * 30, '/');
    }
    return $row;
}

/** 前台访问行为日志：浏览列表、详情、播放等 */
function agent_track_visit($route)
{
    if (defined('SKIP_ROUTE') && SKIP_ROUTE) {
        return false;
    }
    $route = trim((string) $route);
    if ($route === '' || in_array($route, array('admin', 'adimg', 'adenc', 'adclick', 'api'), true)) {
        return false;
    }
    $agent = agent_resolve_current_agent(true);
    $memo = 'route=' . $route;
    return agent_log_add((int) array_value($agent, 'id', 0), (string) array_value($agent, 'code', ''), 'visit', $memo);
}

/** 广告点击行为日志 */
function agent_track_ad_click($ad_id, $slot_key, $target = '')
{
    $agent = agent_resolve_current_agent(false);
    $memo = 'ad_id=' . (int) $ad_id . ';slot=' . trim((string) $slot_key);
    if ($target !== '') {
        $memo .= ';target=' . substr($target, 0, 120);
    }
    return agent_log_add((int) array_value($agent, 'id', 0), (string) array_value($agent, 'code', ''), 'ad_click', $memo);
}

/**
 * 访问 IP 限制（白名单优先，其次黑名单；默认不限制 admin）
 */
function agent_access_ip_block_if_needed($route = '')
{
    global $conf, $longip;
    if (empty($conf['agent_ip_acl_on'])) {
        return false;
    }
    $route = trim((string) $route);
    $ip = trim((string) agent_longip_to_text($longip));
    if ($ip === '' || !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        return false;
    }
    $allow = (string) array_value($conf, 'agent_ip_allowlist', '');
    if ($allow !== '' && agent_ip_in_list($ip, $allow)) {
        return false;
    }
    $block = (string) array_value($conf, 'agent_ip_blocklist', '');
    if ($block === '' || !agent_ip_in_list($ip, $block)) {
        return false;
    }
    $msg = trim((string) array_value($conf, 'agent_ip_deny_message', '访问受限'));
    if ($msg === '') {
        $msg = '访问受限';
    }
    agent_log_add(0, '', 'ip_block', 'ip=' . $ip . ';route=' . $route);
    http_response_code(403);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html><head><meta charset="utf-8"><title>403 Forbidden</title></head><body><h3>403 Forbidden</h3><p>' . htmlspecialchars($msg, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p></body></html>';
    exit;
}

/** 代理统计总览（访问PV/访问IP/广告点击） */
function agent_stats_overview($days = 30)
{
    if (!agent_log_table_ready() || !agent_table_ready()) {
        return array();
    }
    $days = max(1, min(180, (int) $days));
    $since = (int) $GLOBALS['time'] - $days * 86400;
    $pre = $GLOBALS['db']->tablepre;
    $sql = "SELECT a.id,a.code,a.name,a.jump_mode,a.status,"
        . "SUM(CASE WHEN l.type='visit' THEN 1 ELSE 0 END) AS visit_pv,"
        . "COUNT(DISTINCT CASE WHEN l.type='visit' THEN l.longip END) AS visit_ip,"
        . "SUM(CASE WHEN l.type='ad_click' THEN 1 ELSE 0 END) AS ad_clicks,"
        . "MAX(l.create_date) AS last_log_date "
        . "FROM {$pre}agent a "
        . "LEFT JOIN {$pre}agent_log l ON l.agent_id=a.id AND l.create_date>={$since} "
        . "GROUP BY a.id ORDER BY a.id DESC";
    $r = db_sql_find($sql);
    return is_array($r) ? $r : array();
}

/** IP 维度汇总（访问PV/IP、广告点击） */
function agent_stats_ip_overview($code = '', $days = 7, $limit = 500)
{
    if (!agent_log_table_ready()) {
        return array();
    }
    $days = max(1, min(90, (int) $days));
    $limit = max(1, min(1000, (int) $limit));
    $since = (int) $GLOBALS['time'] - $days * 86400;
    $code = strtoupper(trim((string) $code));
    $pre = $GLOBALS['db']->tablepre;
    $where = "create_date >= {$since}";
    if ($code !== '') {
        $c = addslashes($code);
        $where .= " AND code='{$c}'";
    }
    $sql = "SELECT longip,"
        . "SUM(CASE WHEN type='visit' THEN 1 ELSE 0 END) AS visit_pv,"
        . "SUM(CASE WHEN type='ad_click' THEN 1 ELSE 0 END) AS ad_clicks,"
        . "SUM(CASE WHEN type='ip_block' THEN 1 ELSE 0 END) AS blocked_hits,"
        . "SUM(CASE WHEN type<>'ip_block' THEN 1 ELSE 0 END) AS actions,"
        . "MAX(create_date) AS last_date "
        . "FROM {$pre}agent_log WHERE {$where} "
        . "GROUP BY longip ORDER BY actions DESC, last_date DESC LIMIT {$limit}";
    $r = db_sql_find($sql);
    return is_array($r) ? $r : array();
}

/** 行为明细 */
function agent_stats_behavior_count($code = '', $days = 7, $ip = '')
{
    if (!agent_log_table_ready()) {
        return 0;
    }
    $days = max(1, min(90, (int) $days));
    $since = (int) $GLOBALS['time'] - $days * 86400;
    $code = strtoupper(trim((string) $code));
    $pre = $GLOBALS['db']->tablepre;
    $where = "create_date >= {$since}";
    if ($code !== '') {
        $c = addslashes($code);
        $where .= " AND code='{$c}'";
    }
    $ipl = agent_ip_to_long($ip);
    if ($ipl !== '') {
        $where .= " AND longip='{$ipl}'";
    }
    $sql = "SELECT COUNT(*) AS c FROM {$pre}agent_log WHERE {$where}";
    $row = db_sql_find_one($sql);
    return intval(array_value((array) $row, 'c', 0));
}

function agent_stats_behaviors($code = '', $days = 7, $page = 1, $pagesize = 100, $ip = '')
{
    if (!agent_log_table_ready()) {
        return array();
    }
    $days = max(1, min(90, (int) $days));
    $page = max(1, (int) $page);
    $pagesize = max(1, min(200, (int) $pagesize));
    $since = (int) $GLOBALS['time'] - $days * 86400;
    $offset = ($page - 1) * $pagesize;
    $code = strtoupper(trim((string) $code));
    $pre = $GLOBALS['db']->tablepre;
    $where = "create_date >= {$since}";
    if ($code !== '') {
        $c = addslashes($code);
        $where .= " AND code='{$c}'";
    }
    $ipl = agent_ip_to_long($ip);
    if ($ipl !== '') {
        $where .= " AND longip='{$ipl}'";
    }
    $sql = "SELECT * FROM {$pre}agent_log WHERE {$where} ORDER BY id DESC LIMIT {$offset},{$pagesize}";
    $r = db_sql_find($sql);
    return is_array($r) ? $r : array();
}

function agent_should_skip_entry_jump()
{
    global $conf;
    if (defined('SKIP_ROUTE') && SKIP_ROUTE) {
        return true;
    }
    if (!empty($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD']) !== 'GET') {
        return true;
    }
    $req = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
    if (strpos($req, '/admin/') === 0 || strpos($req, '/api') === 0) {
        return true;
    }
    $host = strtolower(trim((string) array_value($_SERVER, 'HTTP_HOST', '')));
    $skip = agent_split_list((string) array_value($conf, 'agent_skip_hosts', ''));
    foreach ($skip as $one) {
        $h = agent_extract_host($one);
        if ($h !== '' && $h === $host) {
            return true;
        }
    }
    return false;
}

/**
 * 根路径带 ?agent= 时跳转到代理线落地（H5 / 下载）
 */
function agent_entry_jump_if_needed($route)
{
    global $conf, $time;
    if (agent_should_skip_entry_jump()) {
        return;
    }
    if ($route !== 'index' || !agent_table_ready()) {
        return;
    }
    $agent_code = strtoupper(trim((string) param('agent', '', false)));
    if ($agent_code === '') {
        return;
    }
    $agent = agent__read_by_code($agent_code);
    if (empty($agent) || empty($agent['status'])) {
        return;
    }
    $front_host = agent_pick_front_host((string) array_value($conf, 'agent_qd_url', ''), (string) array_value($conf, 'qd_url', ''));
    if ($front_host === '') {
        return;
    }
    $jump_mode = (int) array_value($agent, 'jump_mode', 1);
    $download_path = trim((string) array_value($conf, 'agent_download_path', '#/pages/download/download2'));
    if ($download_path === '') {
        $download_path = '#/pages/download/download2';
    }
    if ($download_path[0] !== '#') {
        $download_path = '#' . ltrim($download_path, '/');
    }
    $target = '';
    if ($jump_mode === 2) {
        $target = 'https://' . $front_host . '/' . ltrim($download_path, '/') . '?agent=' . urlencode($agent_code);
    } else {
        $pool = trim((string) array_value($conf, 'agent_h5_domain_pool', ''));
        if ($pool === '') {
            $pool = trim((string) array_value($conf, 'domain', ''));
        }
        $h5_main = agent_pick_front_host($pool, $front_host);
        if ($h5_main !== '') {
            $target = 'http://' . agent_rand_label(6) . '.' . $h5_main . '/#/?agent=' . urlencode($agent_code);
        } else {
            $target = 'https://' . $front_host . '/#/?agent=' . urlencode($agent_code);
        }
    }
    if ($target !== '') {
        setcookie('nn_agent_code', $agent_code, $time + 86400 * 30, '/');
        agent_log_add((int) array_value($agent, 'id', 0), $agent_code, 'entry_jump', $target);
        header('Location: ' . $target, true, 302);
        exit;
    }
}
