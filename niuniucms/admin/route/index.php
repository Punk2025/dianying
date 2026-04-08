<?php
!defined('DEBUG') and exit('Access Denied.');
$action = param(1);

function admin_home_day_range($start_str, $end_str)
{
    global $time;
    $today0 = strtotime(date('Y-m-d 00:00:00', $time));
    $default_start = strtotime('-6 day', $today0);
    $default_end = $today0 + 86400 - 1;
    $start = $default_start;
    $end = $default_end;
    if ($start_str !== '') {
        $t = @strtotime($start_str . ' 00:00:00');
        if ($t !== false) {
            $start = (int) $t;
        }
    }
    if ($end_str !== '') {
        $t = @strtotime($end_str . ' 23:59:59');
        if ($t !== false) {
            $end = (int) $t;
        }
    }
    if ($start > $end) {
        $tmp = $start;
        $start = $end;
        $end = $tmp;
    }
    $max_span = 60 * 86400;
    if ($end - $start > $max_span) {
        $start = $end - $max_span;
    }
    return array($start, $end);
}

function admin_home_date_labels($start, $end)
{
    $labels = array();
    $d0 = strtotime(date('Y-m-d 00:00:00', (int) $start));
    $d1 = strtotime(date('Y-m-d 00:00:00', (int) $end));
    for ($t = $d0; $t <= $d1; $t += 86400) {
        $labels[] = date('Y-m-d', $t);
    }
    return $labels;
}

function admin_home_geo_name($region)
{
    $region = trim((string) $region);
    if ($region === '' || $region === '未知') {
        return '未知';
    }
    $parts = array_values(array_filter(explode(' ', $region)));
    if (empty($parts)) {
        return '未知';
    }
    $country = (string) array_value($parts, 0, '');
    if ($country !== '中国') {
        return $country !== '' ? $country : '海外';
    }
    $prov = (string) array_value($parts, 1, '');
    if ($prov === '') {
        return '中国';
    }
    $map = array(
        '内蒙古自治区' => '内蒙古',
        '广西壮族自治区' => '广西',
        '西藏自治区' => '西藏',
        '宁夏回族自治区' => '宁夏',
        '新疆维吾尔自治区' => '新疆',
        '香港特别行政区' => '香港',
        '澳门特别行政区' => '澳门',
    );
    if (isset($map[$prov])) {
        return $map[$prov];
    }
    $prov = preg_replace('/(省|市|自治区|特别行政区)$/u', '', $prov);
    return $prov !== '' ? $prov : '中国';
}

switch ($action) {
    case 'login':
        if ('GET' == $method) {
            $header['title'] = lang('admin_login');
            include _include(ADMIN_PATH . "html/index_login.html");
        } elseif ('POST' == $method) {
            $password = param('password');
            if (md5($password . $user['salt']) != $user['password']) {
                xn_log('password error. uid:' . $user['uid'] . ' - ******' . substr($password, -6), 'admin_login_error');
                message('password', lang('password_incorrect'));
            }
            admin_token_set();
            xn_log('login successed. uid:' . $user['uid'], 'admin_login');
            message(0, jump(lang('login_successfully'), '.'));
        }
        break;
    case 'logout':
        admin_token_clean();
        message(0, jump(lang('logout_successfully'), $conf['path']));
        break;
    default:
        $start_date = trim((string) param('start_date', ''));
        $end_date = trim((string) param('end_date', ''));
        $region_metric = strtolower(trim((string) param('region_metric', 'pv')));
        if (!in_array($region_metric, array('pv', 'uv'), true)) {
            $region_metric = 'pv';
        }
        list($ts_start, $ts_end) = admin_home_day_range($start_date, $end_date);
        $start_date = date('Y-m-d', $ts_start);
        $end_date = date('Y-m-d', $ts_end);
        $info = array();
        $info['disable_functions'] = ini_get('disable_functions');
        $info['allow_url_fopen'] = ini_get('allow_url_fopen') ? lang('yes') : lang('no');
        $info['safe_mode'] = ini_get('safe_mode') ? lang('yes') : lang('no');
        empty($info['disable_functions']) && $info['disable_functions'] = lang('none');
        $info['upload_max_filesize'] = ini_get('upload_max_filesize');
        $info['post_max_size'] = ini_get('post_max_size');
        $info['memory_limit'] = ini_get('memory_limit');
        $info['max_execution_time'] = ini_get('max_execution_time');
        $info['dbversion'] = $db->version();
        $info['SERVER_SOFTWARE'] = _SERVER('SERVER_SOFTWARE');
        $info['HTTP_X_FORWARDED_FOR'] = _SERVER('HTTP_X_FORWARDED_FOR');
        $info['REMOTE_ADDR'] = _SERVER('REMOTE_ADDR');
        $stat = array();
        $stat['art'] = art_count();
        $stat['vod'] = video_count();
        $stat['tag'] = tag_count();
        $stat['users'] = isset($runtime['users']) ? $runtime['users'] : 0;
        $stat['disk_free_space'] = function_exists('disk_free_space') ? humansize(disk_free_space(APP_PATH)) : lang('unknown');
        $stat_home = array(
            'online_ip' => 0,
            'today_uv' => 0,
            'yesterday_uv' => 0,
            'avg_visit_sec' => 0,
            'today_ad_clicks' => 0,
            'trend_labels' => array(),
            'trend_pv' => array(),
            'trend_uv' => array(),
            'trend_ad_clicks' => array(),
            'region_map' => array(),
            'region_top' => array(),
            'region_map_pv' => array(),
            'region_top_pv' => array(),
            'region_map_uv' => array(),
            'region_top_uv' => array(),
        );
        if (function_exists('ad_table_ready') && ad_table_ready()) {
            $pre = $db->tablepre;
            $today0 = strtotime(date('Y-m-d 00:00:00', (int) $time));
            $row = db_sql_find_one("SELECT COUNT(*) AS c FROM {$pre}ad_click WHERE create_date>={$today0}");
            $stat_home['today_ad_clicks'] = intval(array_value((array) $row, 'c', 0));
        }
        if (function_exists('agent_log_table_ready') && agent_log_table_ready()) {
            $pre = $db->tablepre;
            $now = (int) $time;
            $today0 = strtotime(date('Y-m-d 00:00:00', $now));
            $yesterday0 = $today0 - 86400;
            $online_since = $now - 300;
            $row = db_sql_find_one("SELECT COUNT(DISTINCT longip) AS c FROM {$pre}agent_log WHERE type='visit' AND create_date>={$online_since}");
            $stat_home['online_ip'] = intval(array_value((array) $row, 'c', 0));
            $row = db_sql_find_one("SELECT COUNT(DISTINCT longip) AS c FROM {$pre}agent_log WHERE type='visit' AND create_date>={$today0}");
            $stat_home['today_uv'] = intval(array_value((array) $row, 'c', 0));
            $row = db_sql_find_one("SELECT COUNT(DISTINCT longip) AS c FROM {$pre}agent_log WHERE type='visit' AND create_date>={$yesterday0} AND create_date<{$today0}");
            $stat_home['yesterday_uv'] = intval(array_value((array) $row, 'c', 0));
            $avg_sql = "SELECT AVG(span_sec) AS avg_sec FROM ("
                . "SELECT (MAX(create_date)-MIN(create_date)) AS span_sec, COUNT(*) AS cnt "
                . "FROM {$pre}agent_log WHERE type='visit' AND create_date BETWEEN {$ts_start} AND {$ts_end} "
                . "GROUP BY longip, FROM_UNIXTIME(create_date, '%Y-%m-%d')"
                . ") x WHERE x.cnt>=2";
            $row = db_sql_find_one($avg_sql);
            $stat_home['avg_visit_sec'] = intval(array_value((array) $row, 'avg_sec', 0));

            $labels = admin_home_date_labels($ts_start, $ts_end);
            $stat_home['trend_labels'] = $labels;
            $pv_by_day = array();
            $uv_by_day = array();
            $ad_by_day = array();
            $sql = "SELECT FROM_UNIXTIME(create_date, '%Y-%m-%d') AS d, COUNT(*) AS pv, COUNT(DISTINCT longip) AS uv "
                . "FROM {$pre}agent_log WHERE type='visit' AND create_date BETWEEN {$ts_start} AND {$ts_end} GROUP BY d";
            $arr = db_sql_find($sql);
            if (is_array($arr)) {
                foreach ($arr as $v) {
                    $d = (string) array_value($v, 'd', '');
                    if ($d !== '') {
                        $pv_by_day[$d] = intval(array_value($v, 'pv', 0));
                        $uv_by_day[$d] = intval(array_value($v, 'uv', 0));
                    }
                }
            }
            if (function_exists('ad_table_ready') && ad_table_ready()) {
                $sql = "SELECT FROM_UNIXTIME(create_date, '%Y-%m-%d') AS d, COUNT(*) AS c FROM {$pre}ad_click "
                    . "WHERE create_date BETWEEN {$ts_start} AND {$ts_end} GROUP BY d";
                $arr = db_sql_find($sql);
                if (is_array($arr)) {
                    foreach ($arr as $v) {
                        $d = (string) array_value($v, 'd', '');
                        if ($d !== '') {
                            $ad_by_day[$d] = intval(array_value($v, 'c', 0));
                        }
                    }
                }
            }
            foreach ($labels as $d) {
                $stat_home['trend_pv'][] = intval(array_value($pv_by_day, $d, 0));
                $stat_home['trend_uv'][] = intval(array_value($uv_by_day, $d, 0));
                $stat_home['trend_ad_clicks'][] = intval(array_value($ad_by_day, $d, 0));
            }

            if (function_exists('agent_ip_geo_cache_load')) {
                $geo_cache = agent_ip_geo_cache_load();
                $sql = "SELECT longip, COUNT(*) AS c FROM {$pre}agent_log WHERE type='visit' AND create_date BETWEEN {$ts_start} AND {$ts_end} GROUP BY longip";
                $arr = db_sql_find($sql);
                $regions_pv = array();
                $regions_uv = array();
                if (is_array($arr)) {
                    foreach ($arr as $v) {
                        $lip = array_value($v, 'longip', '');
                        $ip = function_exists('agent_longip_to_text') ? trim((string) agent_longip_to_text($lip)) : trim((string) $lip);
                        if ($ip === '') {
                            continue;
                        }
                        $region_raw = (string) array_value($geo_cache, $ip, '待解析');
                        $name = admin_home_geo_name($region_raw);
                        if (!isset($regions_pv[$name])) {
                            $regions_pv[$name] = 0;
                        }
                        if (!isset($regions_uv[$name])) {
                            $regions_uv[$name] = 0;
                        }
                        $regions_pv[$name] += intval(array_value($v, 'c', 0));
                        $regions_uv[$name] += 1;
                    }
                }
                arsort($regions_pv);
                arsort($regions_uv);
                $top_pv = array_slice($regions_pv, 0, 12, true);
                $top_uv = array_slice($regions_uv, 0, 12, true);
                $region_top_pv = array();
                $region_top_uv = array();
                foreach ($top_pv as $name => $cnt) {
                    $region_top_pv[] = array('name' => (string) $name, 'value' => (int) $cnt);
                }
                foreach ($top_uv as $name => $cnt) {
                    $region_top_uv[] = array('name' => (string) $name, 'value' => (int) $cnt);
                }
                $map_pv = array();
                $map_uv = array();
                foreach ($regions_pv as $name => $cnt) {
                    if ($name === '未知' || $name === '待解析' || $name === '海外' || $name === '中国') {
                        continue;
                    }
                    $map_pv[] = array('name' => (string) $name, 'value' => (int) $cnt);
                }
                foreach ($regions_uv as $name => $cnt) {
                    if ($name === '未知' || $name === '待解析' || $name === '海外' || $name === '中国') {
                        continue;
                    }
                    $map_uv[] = array('name' => (string) $name, 'value' => (int) $cnt);
                }
                $stat_home['region_top_pv'] = $region_top_pv;
                $stat_home['region_top_uv'] = $region_top_uv;
                $stat_home['region_map_pv'] = $map_pv;
                $stat_home['region_map_uv'] = $map_uv;
                if ($region_metric === 'uv') {
                    $stat_home['region_top'] = $region_top_uv;
                    $stat_home['region_map'] = $map_uv;
                } else {
                    $stat_home['region_top'] = $region_top_pv;
                    $stat_home['region_map'] = $map_pv;
                }
            }
        }
        $stat_home['avg_visit_hms'] = gmdate('H:i:s', max(0, (int) $stat_home['avg_visit_sec']));
        //登陆后台清理
        art_del();
        vod_del();
        $header['title'] = lang('admin_page');
        include _include(APP_PATH . 'admin/html/index.html');
        break;
}
