<?php
/**
 * 前台广告位 + 点击统计
 *
 * 版位 slot_key 与模板中 $nncms.ad_slot_* 一一对应，勿随意改名。
 *
 * 广告图加密（可选，ad_image_encrypt=1）：
 * - 密文：upload/encrypt_image/d/{token}.enc
 * - 前台：adimg-{token} 解密输出；加密失败时后台上传会自动改存明文 /upload/ad/
 * - 手填图片 URL 始终按原样输出，不经加密。
 */

function ad_table_ready()
{
    static $ok = null;
    if ($ok !== null) {
        return $ok;
    }
    $db = isset($_SERVER['db']) ? $_SERVER['db'] : null;
    if (!$db || empty($db->tablepre)) {
        return $ok = false;
    }
    $like = addslashes($db->tablepre . 'ad');
    $row = db_sql_find_one("SHOW TABLES LIKE '{$like}'", $db);
    return $ok = !empty($row);
}

/**
 * @return array<string,array{name:string,hint:string,size:string,group?:string}>
 */
function ad_slot_meta()
{
    return array(
        'header_under' => array(
            'group' => '全站 · 顶区',
            'name' => '导航下通栏',
            'hint' => '全站导航下方（nva.html）',
            'size' => '通栏横图约 1180×90 px（或 728×90）；勿过高以免压内容。',
        ),
        'footer_above' => array(
            'group' => '全站 · 底区',
            'name' => '页脚上方通栏',
            'hint' => '全站页脚免责声明上方（foot.html）',
            'size' => '通栏约 1180×60～100 px，宜扁。',
        ),
        'float_fab' => array(
            'group' => '全站 · 悬浮',
            'name' => '右下角悬浮',
            'hint' => '小图/按钮，可关闭（foot.html）',
            'size' => '约 80×80～120×120 px。',
        ),
        'index_slider_under' => array(
            'group' => '首页',
            'name' => '幻灯下方',
            'hint' => '首页大幻灯与主内容之间（index_top.html）',
            'size' => '与主内容同宽约 1180×120～180 px。',
        ),
        'index_sidebar_top' => array(
            'group' => '首页',
            'name' => '侧栏顶部',
            'hint' => '侧栏「随机推荐」上方（index_top.html）',
            'size' => '侧栏宽约 240px：240×120～200 px。',
        ),
        'index_bottom_banner' => array(
            'group' => '首页',
            'name' => '首页底部通栏',
            'hint' => '首页分类列表区域下（index_top.html）',
            'size' => '通栏约 1180×100～120 px。',
        ),
        'list_top_banner' => array(
            'group' => '分类 / 列表 / 搜索',
            'name' => '列表顶横幅',
            'hint' => '分类、列表、搜索页标题下',
            'size' => '约 1180×90～120 px。',
        ),
        'video_intro_under' => array(
            'group' => '视频详情',
            'name' => '简介下方',
            'hint' => '剧情介绍区域下（video.html）',
            'size' => '主内容区宽约 900～1180：900×100～150 px。',
        ),
        'video_sidebar' => array(
            'group' => '视频详情',
            'name' => '右侧栏',
            'hint' => '详情页右侧栏（video.html）',
            'size' => '约 300×250 或 300×300。',
        ),
        'player_below' => array(
            'group' => '播放页',
            'name' => '播放器下方',
            'hint' => '播放页 iframe 下（player.html）',
            'size' => '通栏约 1180×100～120 px。',
        ),
    );
}

/** @return array<string,array<string,array>> 按 group 分组，供后台 optgroup */
function ad_slot_meta_groups()
{
    $meta = ad_slot_meta();
    $out = array();
    foreach ($meta as $k => $m) {
        $g = isset($m['group']) ? $m['group'] : '其它';
        if (!isset($out[$g])) {
            $out[$g] = array();
        }
        $out[$g][$k] = $m;
    }
    return $out;
}

function ad__read($id, $d = null)
{
    if (!ad_table_ready()) {
        return array();
    }
    return db_find_one('ad', array('id' => intval($id)), array(), array(), $d);
}

function ad__find($cond, $orderby, $page, $pagesize, $d = null)
{
    if (!ad_table_ready()) {
        return array();
    }
    return db_find('ad', $cond, $orderby, $page, $pagesize, 'id', array(), $d);
}

function ad__count($cond, $d = null)
{
    if (!ad_table_ready()) {
        return 0;
    }
    return db_count('ad', $cond, $d);
}

function ad__create($arr, $d = null)
{
    if (!ad_table_ready()) {
        return FALSE;
    }
    return db_insert('ad', $arr, $d);
}

function ad__update($id, $update, $d = null)
{
    if (!ad_table_ready()) {
        return FALSE;
    }
    return db_update('ad', array('id' => intval($id)), $update, $d);
}

function ad__delete($id, $d = null)
{
    if (!ad_table_ready()) {
        return FALSE;
    }
    return db_delete('ad', array('id' => intval($id)), $d);
}

/** @param int[] $ids */
function ad__batch_set_status(array $ids, $status, $d = null)
{
    if (!ad_table_ready()) {
        return FALSE;
    }
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
    if (empty($ids)) {
        return FALSE;
    }
    $ids = array_slice($ids, 0, 200);
    $status = $status ? 1 : 0;
    $now = (int) $GLOBALS['time'];
    return db_update('ad', array('id' => $ids), array('status' => $status, 'update_date' => $now), $d);
}

/**
 * 批量设置跳转 URL，仅处理 ad_type=1（图片+链接）。
 *
 * @return array{n:int,skip:int}|false 失败返回 false；无图片类可更新时返回 n=0
 */
function ad__batch_set_url(array $ids, $url, $d = null)
{
    if (!ad_table_ready()) {
        return FALSE;
    }
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
    if (empty($ids)) {
        return FALSE;
    }
    $ids = array_slice($ids, 0, 200);
    $sel_count = count($ids);
    $url = strip_tags(trim((string) $url));
    if (strlen($url) > 800) {
        $url = substr($url, 0, 800);
    }
    $rows = db_find('ad', array('id' => $ids, 'ad_type' => 1), array(), 1, 250, 'id', array(), $d);
    $target = array();
    foreach ((array) $rows as $r) {
        if (isset($r['id'])) {
            $target[] = (int) $r['id'];
        }
    }
    if (empty($target)) {
        return array('n' => 0, 'skip' => $sel_count);
    }
    $now = (int) $GLOBALS['time'];
    if (db_update('ad', array('id' => $target), array('url' => $url, 'update_date' => $now), $d) === FALSE) {
        return FALSE;
    }
    return array('n' => count($target), 'skip' => $sel_count - count($target));
}

function ad_image_encrypt_on()
{
    global $conf;
    return !empty($conf['ad_image_encrypt']);
}

function ad_image_aes_key()
{
    global $conf;
    $raw = trim((string) array_value($conf, 'ad_image_secret', ''));
    $src = $raw !== '' ? $raw : (function_exists('xn_key') ? xn_key() : '') . 'nncms_ad_aes_v1';
    return hash('sha256', $src, true);
}

function ad_image_encrypt_blob($plain)
{
    if (!function_exists('openssl_encrypt')) {
        return FALSE;
    }
    $iv = openssl_random_pseudo_bytes(16);
    if ($iv === FALSE || strlen($iv) !== 16) {
        return FALSE;
    }
    $ct = openssl_encrypt($plain, 'AES-256-CBC', ad_image_aes_key(), OPENSSL_RAW_DATA, $iv);
    if ($ct === FALSE) {
        return FALSE;
    }
    return $iv . $ct;
}

function ad_image_decrypt_blob($blob)
{
    if (!function_exists('openssl_decrypt') || strlen($blob) < 33) {
        return FALSE;
    }
    $iv = substr($blob, 0, 16);
    $ct = substr($blob, 16);
    $plain = openssl_decrypt($ct, 'AES-256-CBC', ad_image_aes_key(), OPENSSL_RAW_DATA, $iv);
    return ($plain !== FALSE && $plain !== '') ? $plain : FALSE;
}

function ad_image_detect_mime($bin)
{
    if (strncmp($bin, "\xFF\xD8\xFF", 3) === 0) {
        return 'image/jpeg';
    }
    if (strncmp($bin, "\x89PNG\r\n\x1a\n", 8) === 0) {
        return 'image/png';
    }
    if (strncmp($bin, 'GIF87a', 6) === 0 || strncmp($bin, 'GIF89a', 6) === 0) {
        return 'image/gif';
    }
    if (strncmp($bin, 'RIFF', 4) === 0 && strlen($bin) > 12 && substr($bin, 8, 4) === 'WEBP') {
        return 'image/webp';
    }
    if (function_exists('finfo_open')) {
        $fi = finfo_open(FILEINFO_MIME_TYPE);
        if ($fi) {
            $m = finfo_buffer($fi, $bin);
            finfo_close($fi);
            if ($m && strpos($m, 'image/') === 0) {
                return $m;
            }
        }
    }
    return 'application/octet-stream';
}

/** 将明文图片二进制落盘为加密文件，返回前台可访问的 adimg URL（相对或绝对由 url() 决定） */
function ad_image_save_encrypted_upload($plain_binary)
{
    global $conf;
    $pack = ad_image_encrypt_blob($plain_binary);
    if ($pack === FALSE) {
        return FALSE;
    }
    if (function_exists('random_bytes')) {
        $token = bin2hex(random_bytes(16));
    } else {
        $token = bin2hex(openssl_random_pseudo_bytes(16));
    }
    $subdir = 'encrypt_image/d/';
    $base = rtrim(str_replace('\\', '/', $conf['upload_path']), '/');
    $dir = $base . '/' . $subdir;
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
        return FALSE;
    }
    $full = $dir . $token . '.enc';
    if (file_put_contents($full, $pack) === FALSE) {
        return FALSE;
    }
    return url('adimg-' . $token, '', 2);
}

function ad_serve_encrypted_image()
{
    global $conf;
    $token = strtolower(preg_replace('/[^a-f0-9]/', '', (string) param(1, '')));
    if (strlen($token) !== 32) {
        header('HTTP/1.0 404 Not Found');
        exit;
    }
    $base = rtrim(str_replace('\\', '/', $conf['upload_path']), '/');
    $full = $base . '/encrypt_image/d/' . $token . '.enc';
    if (!is_file($full)) {
        header('HTTP/1.0 404 Not Found');
        exit;
    }
    $blob = file_get_contents($full);
    if ($blob === FALSE || $blob === '') {
        header('HTTP/1.0 404 Not Found');
        exit;
    }
    $plain = ad_image_decrypt_blob($blob);
    if ($plain === FALSE) {
        header('HTTP/1.0 500 Internal Server Error');
        exit;
    }
    $mime = ad_image_detect_mime($plain);
    header('Content-Type: ' . $mime);
    header('Cache-Control: public, max-age=86400');
    header('X-Content-Type-Options: nosniff');
    echo $plain;
    exit;
}

function ad_admin_list($slot_key, $page, $pagesize = 20)
{
    $cond = array();
    if ($slot_key !== '' && $slot_key !== null) {
        $cond['slot_key'] = $slot_key;
    }
    $orderby = array('weight' => -1, 'id' => -1);
    return ad__find($cond, $orderby, $page, $pagesize);
}

function ad_admin_count($slot_key)
{
    $cond = array();
    if ($slot_key !== '' && $slot_key !== null) {
        $cond['slot_key'] = $slot_key;
    }
    return ad__count($cond);
}

function ad_active_for_slot($slot_key, $d = null)
{
    if (!ad_table_ready()) {
        return array();
    }
    $t = (int) $GLOBALS['time'];
    $cond = array(
        'slot_key' => $slot_key,
        'status' => 1,
    );
    $arr = db_find('ad', $cond, array('weight' => -1, 'id' => -1), 1, 50, 'id', array(), $d);
    if (empty($arr)) {
        return array();
    }
    $out = array();
    foreach ($arr as $row) {
        $st = (int) array_value($row, 'starttime', 0);
        $en = (int) array_value($row, 'endtime', 0);
        if ($st > 0 && $t < $st) {
            continue;
        }
        if ($en > 0 && $t > $en) {
            continue;
        }
        $out[] = $row;
    }
    return $out;
}

function ad_click_url($id)
{
    return url('adclick-' . intval($id), '', 2);
}

function ad_escape_attr($s)
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

function ad_render_slot_html($slot_key)
{
    global $time;
    $rows = ad_active_for_slot($slot_key);
    if (empty($rows)) {
        return '';
    }
    $parts = array();
    foreach ($rows as $row) {
        $id = (int) $row['id'];
        $type = (int) array_value($row, 'ad_type', 1);
        if (2 === $type) {
            $html = array_value($row, 'content_html', '');
            if ($html !== '') {
                $parts[] = '<div class="nn-ad nn-ad-' . ad_escape_attr($slot_key) . ' nn-ad-html">' . $html . '</div>';
            }
            continue;
        }
        $img = trim(array_value($row, 'image', ''));
        $link = trim(array_value($row, 'url', ''));
        if ($img === '') {
            continue;
        }
        $name = ad_escape_attr(array_value($row, 'name', ''));
        if ($link !== '' && preg_match('#^https?://#i', $link)) {
            $go = ad_click_url($id);
            $parts[] = '<div class="nn-ad nn-ad-' . ad_escape_attr($slot_key) . '"><a href="' . ad_escape_attr($go) . '" target="_blank" rel="noopener nofollow" title="' . $name . '"><img src="' . ad_escape_attr($img) . '" alt="' . $name . '" loading="lazy" style="max-width:100%;height:auto;display:block;margin:0 auto;"></a></div>';
        } else {
            $parts[] = '<div class="nn-ad nn-ad-' . ad_escape_attr($slot_key) . '"><img src="' . ad_escape_attr($img) . '" alt="' . $name . '" loading="lazy" style="max-width:100%;height:auto;display:block;margin:0 auto;"></div>';
        }
    }
    $html = implode("\n", $parts);
    if ($slot_key === 'float_fab' && $html !== '') {
        $html = '<div id="nnFabAd" class="nn-fab-ad">' . $html . '<button type="button" class="nn-fab-ad-close" aria-label="关闭">×</button></div>';
    }
    return $html;
}

function ad_inject_nncms(&$nncms, &$maccms)
{
    $meta = ad_slot_meta();
    if (!ad_table_ready()) {
        foreach ($meta as $key => $_) {
            $k = 'ad_slot_' . $key;
            $nncms[$k] = '';
            $maccms[$k] = '';
        }
        return;
    }
    foreach ($meta as $key => $_) {
        $html = ad_render_slot_html($key);
        $k = 'ad_slot_' . $key;
        $nncms[$k] = $html;
        $maccms[$k] = $html;
    }
}

function ad_safe_redirect_url($url)
{
    $url = trim((string) $url);
    if (preg_match('#^https?://#i', $url)) {
        return $url;
    }
    return '';
}

function ad_process_click($id)
{
    global $time, $longip;
    $id = intval($id);
    if ($id < 1) {
        return '';
    }
    if (!ad_table_ready()) {
        return '';
    }
    $row = ad__read($id);
    if (empty($row) || empty($row['status'])) {
        return '';
    }
    $t = (int) $time;
    $st = (int) array_value($row, 'starttime', 0);
    $en = (int) array_value($row, 'endtime', 0);
    if ($st > 0 && $t < $st) {
        return '';
    }
    if ($en > 0 && $t > $en) {
        return '';
    }
    $target = ad_safe_redirect_url(array_value($row, 'url', ''));
    db_update('ad', array('id' => $id), array('clicks+' => 1));
    db_insert('ad_click', array(
        'ad_id' => $id,
        'slot_key' => array_value($row, 'slot_key', ''),
        'longip' => (int) $longip,
        'create_date' => $t,
    ));
    return $target !== '' ? $target : http_url_path();
}

function ad_stats_by_slot()
{
    if (!ad_table_ready()) {
        return array();
    }
    $sql = "SELECT slot_key, COUNT(*) AS cnt, SUM(clicks) AS sum_clicks, SUM(`views`) AS sum_views FROM {$GLOBALS['db']->tablepre}ad GROUP BY slot_key";
    $r = db_sql_find($sql);
    return is_array($r) ? $r : array();
}

function ad_stats_top_ads($limit = 30)
{
    if (!ad_table_ready()) {
        return array();
    }
    $limit = max(1, min(200, (int) $limit));
    $pre = $GLOBALS['db']->tablepre;
    $sql = "SELECT id, slot_key, name, clicks, views, status FROM {$pre}ad ORDER BY clicks DESC, id DESC LIMIT {$limit}";
    $r = db_sql_find($sql);
    return is_array($r) ? $r : array();
}

function ad_stats_clicks_by_day($slot_key = '', $days = 14)
{
    if (!ad_table_ready()) {
        return array();
    }
    $days = max(1, min(90, (int) $days));
    $since = (int) $GLOBALS['time'] - $days * 86400;
    $pre = $GLOBALS['db']->tablepre;
    $slot_key = trim((string) $slot_key);
    $meta = ad_slot_meta();
    if ($slot_key !== '' && !isset($meta[$slot_key])) {
        $slot_key = '';
    }
    if ($slot_key !== '') {
        $sk = addslashes($slot_key);
        $sql = "SELECT FROM_UNIXTIME(create_date, '%Y-%m-%d') AS d, COUNT(*) AS c FROM {$pre}ad_click WHERE create_date >= {$since} AND slot_key='{$sk}' GROUP BY d ORDER BY d DESC";
    } else {
        $sql = "SELECT FROM_UNIXTIME(create_date, '%Y-%m-%d') AS d, COUNT(*) AS c FROM {$pre}ad_click WHERE create_date >= {$since} GROUP BY d ORDER BY d DESC";
    }
    $r = db_sql_find($sql);
    return is_array($r) ? $r : array();
}
