<?php
!defined('DEBUG') and exit('Access Denied.');
ob_clean();
$action = param(1);
switch($action){
    case 'list':
        $vid=param('vid');
        $max=param('max', 100); // 设置默认值
        $format=param('format');
        $pre=$conf['db']['mysql']['master']['tablepre'];
        $sql="select * from {$pre}danmu where vid=$vid and status=1 order by time asc limit 0,$max";
        $danmu_list=db_sql_find($sql);
        $danmu_data = formatDanmuData($danmu_list);
        if($format=='xml') {
            ob_end_clean();
            header('Content-Type: text/xml; charset=utf-8');
            header('Cache-Control: no-cache, must-revalidate');
            echo formatDanmuXml($danmu_data, $vid);
            exit;
        } else {
            // 返回JSON格式数据 
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['code' => 1, 'data' => $danmu_data, 'total' => count($danmu_data)]);
            exit;
        }
        break;
    case 'send': 
        if($_SERVER['REQUEST_METHOD'] === 'GET') {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['code' => 0, 'msg' => '不允许GET访问']);
            exit;
        } 
        $vid=param('vid');
         if(empty($vid) && isset($_REQUEST['vod_id'])) {
            $vid = param('vod_id');
        }
        $text=param('text');
        $color=param('color'); 
        if(empty($vid) || empty($text) || empty($color)) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['code' => 0, 'msg' => '缺少必要参数：vid、text和color']);
            exit;
        } 
        $user_id=$uid;
        // 安全地获取username，避免Undefined index错误
        $username = isset($user) && isset($user['username']) && $user['username'] ? $user['username'] : '游客';
        $time=param('time', 0);
        $mode=param('mode', 0);
        $id=param('id'); 
        $ip_long = ip2long($_SERVER['REMOTE_ADDR']);
        $danmu_cache_key = 'danmu_check_' . md5($ip . $text);
        $check_result = cache_get($danmu_cache_key);
        if($check_result){
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['code' => 0, 'msg' => '请勿重复发送相同弹幕']);
            exit;
        }
        cache_set($danmu_cache_key, 1, 120); 
        $data = [
            'vid' => $vid,
            'uid' => $uid,
            'username' => $username,
            'text' => $text,
            'time' => $time,
            'color' => $color,
            'mode' => $mode,
            'ip' => $ip_long > 0 ? $ip_long : 0,
            'create_time' => time(),
            'status' => 1 
        ];
        $r=db_insert('danmu', $data);
        if($r) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['code' => 1, 'msg' => '发送成功', 'data' => $data]);
        } else {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['code' => 0, 'msg' => '发送失败']);
        }
        exit;
        break;
}
?>