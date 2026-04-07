<?php
!defined('DEBUG') and exit('Access Denied.');
$cid = 0;
$action = param(1, 'list');
$meta = ad_slot_meta();

switch ($action) {
    case 'image_upload':
        if ('POST' !== $method) {
            message(4, lang('method_error'));
        }
        empty($_FILES['ad_image']) && message(1, '请选择图片');
        $f = $_FILES['ad_image'];
        if (empty($f['tmp_name']) || !is_uploaded_file($f['tmp_name'])) {
            message(1, '上传无效');
        }
        if (isset($f['error']) && (int) $f['error'] !== UPLOAD_ERR_OK) {
            message(1, '上传失败');
        }
        $max_bytes = 5242880;
        if (isset($f['size']) && (int) $f['size'] > $max_bytes) {
            message(1, '图片不能超过 5MB');
        }
        $imginfo = @getimagesize($f['tmp_name']);
        if ($imginfo === false) {
            message(1, '不是有效的图片文件');
        }
        $mime_map = array(
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG => 'png',
            IMAGETYPE_GIF => 'gif',
            IMAGETYPE_WEBP => 'webp',
        );
        if (!isset($mime_map[$imginfo[2]])) {
            message(1, '仅支持 jpg、png、gif、webp');
        }
        $ext = $mime_map[$imginfo[2]];
        if (function_exists('finfo_open')) {
            $fi = finfo_open(FILEINFO_MIME_TYPE);
            if ($fi) {
                $mime = finfo_file($fi, $f['tmp_name']);
                finfo_close($fi);
                $mime_ok = array('image/jpeg', 'image/png', 'image/gif', 'image/webp');
                if ($mime && !in_array($mime, $mime_ok, true)) {
                    message(1, '文件类型校验未通过');
                }
            }
        }
        if (ad_image_encrypt_on()) {
            $plain = @file_get_contents($f['tmp_name']);
            if ($plain !== FALSE && $plain !== '') {
                $pub = ad_image_save_encrypted_upload($plain);
                if ($pub !== FALSE) {
                    message(0, '上传成功（已加密存储）', array('url' => $pub));
                }
            }
        }
        $subdir = 'ad/' . date('Ym') . '/';
        $base = rtrim(str_replace('\\', '/', $conf['upload_path']), '/');
        $dest_dir = $base . '/' . $subdir;
        if (!is_dir($dest_dir) && !@mkdir($dest_dir, 0755, true)) {
            message(-1, '无法创建上传目录');
        }
        $basename = 'ad_' . date('YmdHis') . '_' . substr(md5(uniqid((string) mt_rand(), true)), 0, 8) . '.' . $ext;
        $dest = $dest_dir . $basename;
        if (!move_upload_file($f['tmp_name'], $dest)) {
            message(-1, '保存文件失败');
        }
        $pub = rtrim(str_replace('\\', '/', $conf['upload_url']), '/') . '/' . $subdir . $basename;
        message(0, '上传成功', array('url' => $pub));
        break;

    case 'stat':
        if ('GET' == $method) {
            $slot_filter = param('slot', '');
            $days = param('days', 14);
            $by_slot = ad_stats_by_slot();
            $top_ads = ad_stats_top_ads(50);
            $by_day = ad_stats_clicks_by_day($slot_filter, $days);
            $header['title'] = '广告统计';
            include _include(APP_PATH . 'admin/html/ad_stat.html');
        }
        break;

    case 'delete':
        if ('POST' == $method) {
            $id = intval(param('id', 0));
            $id < 1 && message(1, '参数错误');
            ad__delete($id) !== FALSE ? message(0, lang('delete_successfully')) : message(-1, lang('delete_failed'));
        }
        break;

    case 'batchstatus':
        if ('POST' == $method) {
            $ids = param('ids', array(0), FALSE);
            if (!is_array($ids)) {
                $ids = array();
            }
            $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
            empty($ids) && message(1, '请先勾选要操作的广告');
            $status = intval(param('status', 0)) ? 1 : 0;
            ad__batch_set_status($ids, $status) !== FALSE ? message(0, $status ? '已批量上架' : '已批量下架') : message(-1, '操作失败');
        }
        break;

    case 'batchurl':
        if ('POST' == $method) {
            $ids = param('ids', array(0), FALSE);
            if (!is_array($ids)) {
                $ids = array();
            }
            $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
            empty($ids) && message(1, '请先勾选要操作的广告');
            $url = trim((string) param('url', '', FALSE));
            $r = ad__batch_set_url($ids, $url);
            FALSE === $r && message(-1, '操作失败');
            if (0 === (int) $r['n']) {
                message(1, '所选记录中没有图片+链接类广告，无法批量更换跳转链接');
            }
            $msg = '已将 ' . (int) $r['n'] . ' 条图片广告的跳转链接更新为新地址';
            if (!empty($r['skip'])) {
                $msg .= '（另有 ' . (int) $r['skip'] . ' 条为 HTML 类型，未修改）';
            }
            message(0, $msg);
        }
        break;

    case 'create':
    case 'update':
        $id = intval(param(2, 0));
        if ('GET' == $method) {
            $row = array(
                'id' => 0,
                'slot_key' => param('slot', 'header_under'),
                'name' => '',
                'ad_type' => 1,
                'image' => '',
                'url' => '',
                'content_html' => '',
                'weight' => 0,
                'status' => 1,
                'starttime' => 0,
                'endtime' => 0,
            );
            if ('update' == $action && $id > 0) {
                $one = ad__read($id);
                empty($one) && message(-1, '记录不存在');
                $row = is_object($one) ? (array) $one : $one;
            }
            $header['title'] = 'update' == $action ? '编辑广告' : '新增广告';
            include _include(APP_PATH . 'admin/html/ad_edit.html');
        } elseif ('POST' == $method) {
            $id = intval(param('id', 0));
            $slot_key = trim((string) param('slot_key', ''));
            empty($slot_key) && message(1, '请选择版位');
            isset($meta[$slot_key]) OR message(1, '版位无效');
            $name = strip_tags(trim((string) param('name', '')));
            $ad_type = intval(param('ad_type', 1));
            $image = trim((string) param('image', '', FALSE));
            $url = trim((string) param('url', '', FALSE));
            $content_html = param('content_html', '', FALSE);
            $weight = intval(param('weight', 0));
            $status = intval(param('status', 1)) ? 1 : 0;
            $starttime = trim((string) param('starttime_str', ''));
            $endtime = trim((string) param('endtime_str', ''));
            $st = $starttime === '' ? 0 : intval(@strtotime($starttime));
            $en = $endtime === '' ? 0 : intval(@strtotime($endtime));
            if ($ad_type === 1) {
                $content_html = '';
            } else {
                $image = '';
                $url = '';
            }
            $now = (int) $time;
            $data = array(
                'slot_key' => $slot_key,
                'name' => $name,
                'ad_type' => $ad_type,
                'image' => strip_tags($image),
                'url' => strip_tags($url),
                'content_html' => $ad_type === 2 ? $content_html : '',
                'weight' => $weight,
                'status' => $status,
                'starttime' => $st,
                'endtime' => $en,
                'update_date' => $now,
            );
            if ('create' == $action) {
                $data['create_date'] = $now;
                $data['clicks'] = 0;
                $data['views'] = 0;
                ad__create($data) !== FALSE ? message(0, jump(lang('create_successfully'), url('ad-list', '', TRUE), 1)) : message(-1, lang('create_failed'));
            } else {
                $id < 1 && message(1, '参数错误');
                ad__update($id, $data) !== FALSE ? message(0, jump(lang('modify_successfully'), url('ad-list', '', TRUE), 1)) : message(-1, lang('update_failed'));
            }
        }
        break;

    case 'list':
    default:
        if ('GET' == $method) {
            $page = param('page', 1);
            $pagesize = 30;
            $slot_key = param('slot', '');
            if ($slot_key !== '' && !isset($meta[$slot_key])) {
                $slot_key = '';
            }
            $n = ad_admin_count($slot_key);
            $arrlist = ad_admin_list($slot_key, $page, $pagesize);
            $extra = array('page' => '{page}');
            if ($slot_key !== '') {
                $extra['slot'] = $slot_key;
            }
            $pagination = pagination(url('ad-list', $extra, TRUE), $n, $page, $pagesize);
            $header['title'] = '广告管理';
            include _include(APP_PATH . 'admin/html/ad_list.html');
        }
        break;
}
