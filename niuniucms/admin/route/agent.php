<?php
!defined('DEBUG') and exit('Access Denied.');
$cid = 0;
$action = param(1, 'list');

switch ($action) {
    case 'stat':
        if ('GET' == $method) {
            $days = intval(param('days', 7));
            $code = strtoupper(trim((string) param('code', '')));
            $ip = trim((string) param('ip', ''));
            $page = max(1, intval(param('page', 1)));
            $pagesize = 100;
            $overview = agent_stats_overview($days);
            $ip_overview = agent_stats_ip_overview($code, $days, 500);
            $n = agent_stats_behavior_count($code, $days, $ip);
            $logs = agent_stats_behaviors($code, $days, $page, $pagesize, $ip);
            $pagination = pagination(url('agent-stat', array('days' => $days, 'code' => $code, 'ip' => $ip, 'page' => '{page}'), TRUE), $n, $page, $pagesize);
            $header['title'] = '代理统计';
            include _include(APP_PATH . 'admin/html/agent_stat.html');
        }
        break;

    case 'delete':
        if ('POST' == $method) {
            $id = intval(param('id', 0));
            $id < 1 && message(1, '参数错误');
            agent__delete($id) !== FALSE ? message(0, lang('delete_successfully')) : message(-1, lang('delete_failed'));
        }
        break;

    case 'settings':
        if ('POST' == $method) {
            $replace = array();
            $replace['qd_url'] = trim((string) param('qd_url', '', FALSE));
            $replace['agent_qd_url'] = trim((string) param('agent_qd_url', '', FALSE));
            $replace['agent_h5_domain_pool'] = trim((string) param('agent_h5_domain_pool', '', FALSE));
            $replace['agent_download_path'] = trim((string) param('agent_download_path', '#/pages/download/download2', FALSE));
            $replace['agent_skip_hosts'] = trim((string) param('agent_skip_hosts', '', FALSE));
            $replace['agent_ip_acl_on'] = intval(param('agent_ip_acl_on', 0)) ? 1 : 0;
            $replace['agent_ip_allowlist'] = trim((string) param('agent_ip_allowlist', '', FALSE));
            $replace['agent_ip_blocklist'] = trim((string) param('agent_ip_blocklist', '', FALSE));
            $replace['agent_ip_deny_message'] = trim((string) param('agent_ip_deny_message', '访问受限', FALSE));
            file_replace_var(APP_PATH . 'config/config.php', $replace);
            message(0, lang('modify_successfully'));
        }
        break;
    case 'blockip':
        if ('POST' == $method) {
            $raw = trim((string) param('ip', ''));
            if ($raw === '') {
                message(1, 'IP 不能为空');
            }
            $ip = $raw;
            if (preg_match('/^\d+$/', $raw) && function_exists('agent_longip_to_text')) {
                $ip = trim((string) agent_longip_to_text($raw));
            }
            if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                message(1, '仅支持 IPv4 地址');
            }
            $old = trim((string) array_value($conf, 'agent_ip_blocklist', ''));
            if (function_exists('agent_split_list')) {
                $arr = agent_split_list($old);
            } else {
                $arr = array_filter(array_map('trim', explode(',', str_replace(array('，', "\r", "\n"), ',', $old))));
            }
            if (!in_array($ip, $arr, true)) {
                $arr[] = $ip;
            }
            $replace = array();
            $replace['agent_ip_blocklist'] = implode(',', array_values(array_unique($arr)));
            $replace['agent_ip_acl_on'] = 1;
            file_replace_var(APP_PATH . 'config/config.php', $replace);
            $msg = '已拉黑 ' . $ip . '，并自动启用 IP 限制';
            if (function_exists('agent_ip_in_list') && agent_ip_in_list($ip, (string) array_value($conf, 'agent_ip_allowlist', ''))) {
                $msg .= '（注意：该 IP 同时命中白名单，白名单优先）';
            }
            message(0, $msg, array('ip' => $ip));
        }
        break;
    case 'resolvegeo':
        if ('POST' == $method) {
            $limit = intval(param('limit', 30));
            $days = intval(param('days', 30));
            $max_fail = intval(param('max_fail', 5));
            if (!function_exists('agent_ip_geo_resolve_batch')) {
                message(-1, '解析函数不存在');
            }
            $stat = agent_ip_geo_resolve_batch($limit, $days, $max_fail);
            $msg = '批量解析完成：取出 ' . intval(array_value($stat, 'picked', 0))
                . '，成功 ' . intval(array_value($stat, 'resolved', 0))
                . '，失败 ' . intval(array_value($stat, 'failed', 0));
            message(0, $msg, $stat);
        }
        break;
    case 'resolvegeoone':
        if ('POST' == $method) {
            $ip = trim((string) param('ip', ''));
            $max_fail = intval(param('max_fail', 5));
            if (!function_exists('agent_ip_geo_resolve_one')) {
                message(-1, '解析函数不存在');
            }
            $ret = agent_ip_geo_resolve_one($ip, $max_fail);
            if (!empty($ret['ok'])) {
                $msg = '解析成功：' . array_value($ret, 'ip', '') . ' / ' . array_value($ret, 'region', '未知');
                message(0, $msg, $ret);
            } else {
                message(1, (string) array_value($ret, 'message', '解析失败'), $ret);
            }
        }
        break;

    case 'create':
    case 'update':
        $id = intval(param(2, 0));
        if ('GET' == $method) {
            $row = array(
                'id' => 0,
                'code' => agent_generate_code('A'),
                'name' => '',
                'jump_mode' => 1,
                'status' => 1,
                'note' => '',
            );
            if ('update' == $action && $id > 0) {
                $one = agent__read($id);
                empty($one) && message(-1, '记录不存在');
                $row = is_object($one) ? (array) $one : $one;
            }
            $header['title'] = 'update' == $action ? '编辑代理' : '新增代理';
            include _include(APP_PATH . 'admin/html/agent_edit.html');
        } elseif ('POST' == $method) {
            $id = intval(param('id', 0));
            $code = strtoupper(trim((string) param('code', '')));
            $name = strip_tags(trim((string) param('name', '')));
            $jump_mode = intval(param('jump_mode', 1)) == 2 ? 2 : 1;
            $status = intval(param('status', 1)) ? 1 : 0;
            $note = strip_tags(trim((string) param('note', '', FALSE)));
            if ($code === '' || !preg_match('/^[A-Z0-9_\\-]{4,32}$/', $code)) {
                message(1, '代理码仅支持 A-Z 0-9 _ -，长度 4~32');
            }
            $dup = agent__read_by_code($code);
            if (!empty($dup) && intval(array_value($dup, 'id', 0)) !== $id) {
                message(1, '代理码已存在');
            }
            $now = (int) $time;
            $data = array(
                'code' => $code,
                'name' => $name,
                'jump_mode' => $jump_mode,
                'status' => $status,
                'note' => $note,
                'update_date' => $now,
            );
            if ('create' == $action) {
                $data['create_date'] = $now;
                agent__create($data) !== FALSE ? message(0, jump(lang('create_successfully'), url('agent-list', '', TRUE), 1)) : message(-1, lang('create_failed'));
            } else {
                $id < 1 && message(1, '参数错误');
                agent__update($id, $data) !== FALSE ? message(0, jump(lang('modify_successfully'), url('agent-list', '', TRUE), 1)) : message(-1, lang('update_failed'));
            }
        }
        break;

    case 'list':
    default:
        if ('GET' == $method) {
            $page = param('page', 1);
            $pagesize = 30;
            $n = agent_admin_count();
            $arrlist = agent_admin_list($page, $pagesize);
            $pagination = pagination(url('agent-list', array('page' => '{page}'), TRUE), $n, $page, $pagesize);
            $header['title'] = '代理线管理';
            include _include(APP_PATH . 'admin/html/agent_list.html');
        }
        break;
}
