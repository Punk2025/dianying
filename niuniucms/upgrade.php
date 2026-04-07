<?php
define('SKIP_ROUTE', 1);
include './index.php';
set_time_limit(0);
$tablepre = $db->tablepre;
if (!db_find_table($db->tablepre . 'vod_top')) {
    $sql = "CREATE TABLE `{$tablepre}vod_top` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `vid` int(11) NOT NULL DEFAULT '0' COMMENT '视频 ID',
        `cid` int(11) NOT NULL DEFAULT '0' COMMENT '分类 ID',
        `create_date` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
        PRIMARY KEY (`id`),
        KEY `vid` (`vid`),
        KEY `cid_vid` (`cid`,`vid`),
        KEY `create_date` (`create_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='视频热门记录';
    ";
    db_exec($sql);
}
if (!db_find_table($db->tablepre . 'vod_top_ip')) {
    $sql = "CREATE TABLE `{$tablepre}vod_top_ip` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `vid` int(11) NOT NULL DEFAULT '0' COMMENT '视频 ID',
        `ip` decimal(39,0) NOT NULL DEFAULT '0' COMMENT 'IP 地址',
        `create_date` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
        PRIMARY KEY (`id`),
        KEY `vid` (`vid`),
        KEY `ip` (`ip`),
        KEY `create_date` (`create_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='视频热门 IP 记录';
    ";
    db_exec($sql);
}
if (!db_find_table($db->tablepre . 'art_top')) {
    $sql = "CREATE TABLE `{$tablepre}art_top` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `aid` int(11) NOT NULL DEFAULT '0' COMMENT '文章 ID',
        `cid` int(11) NOT NULL DEFAULT '0' COMMENT '分类 ID',
        `create_date` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
        PRIMARY KEY (`id`),
        KEY `aid` (`aid`),
        KEY `cid_aid` (`cid`,`aid`),
        KEY `create_date` (`create_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='文章热门记录';
    ";
    db_exec($sql);
}
if (!db_find_table($db->tablepre . 'art_top_ip')) {
    $sql = "CREATE TABLE `{$tablepre}art_top_ip` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `aid` int(11) NOT NULL DEFAULT '0' COMMENT '文章 ID',
        `ip` decimal(39,0) NOT NULL DEFAULT '0' COMMENT 'IP 地址',
        `create_date` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
        PRIMARY KEY (`id`),
        KEY `aid` (`aid`),
        KEY `ip` (`ip`),
        KEY `create_date` (`create_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='文章热门 IP 记录';
    ";
    db_exec($sql);
}
$sql = "ALTER TABLE `{$tablepre}art` ADD INDEX `cid_views` (`cid`, `views`)";
$r = db_exec($sql);
$sql = "ALTER TABLE `{$tablepre}vod` ADD INDEX `cid_views` (`cid`, `views`)";
$r = db_exec($sql);
$sql = "ALTER TABLE  `{$db->tablepre}group` ADD  `manageuser` int(10) unsigned NOT NULL DEFAULT '0'";
$r = db_exec($sql);
$sql = "ALTER TABLE  `{$db->tablepre}group` ADD  `managecreateuser` int(10) unsigned NOT NULL DEFAULT '0'";
$r = db_exec($sql); 
$sql = "ALTER TABLE  `{$db->tablepre}art` ADD  `favorites` int(10) unsigned NOT NULL DEFAULT '0'";
$r = db_exec($sql);
$sql = "ALTER TABLE  `{$db->tablepre}art` ADD  `likes` int(10) unsigned NOT NULL DEFAULT '0'";
$r = db_exec($sql);
$sql = "ALTER TABLE  `{$db->tablepre}like` ADD  `model` int(10) unsigned NOT NULL DEFAULT '0'";
$r = db_exec($sql);
$sql = "ALTER TABLE  `{$db->tablepre}favorites` ADD  `model` int(10) unsigned NOT NULL DEFAULT '0'";
$r = db_exec($sql);
if (!db_find_table($db->tablepre . 'danmu')) {
    $sql = "CREATE TABLE `{$tablepre}danmu` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '弹幕ID',
  `vid` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '视频ID',
  `uid` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '用户ID',
  `username` varchar(30) NOT NULL DEFAULT '' COMMENT '用户名',
  `text` varchar(255) NOT NULL DEFAULT '' COMMENT '弹幕内容',
  `time` float(10,2) NOT NULL DEFAULT '0.00' COMMENT '弹幕时间(秒)',
  `color` varchar(7) NOT NULL DEFAULT '#FFFFFF' COMMENT '弹幕颜色',
  `mode` int(11) NOT NULL DEFAULT '0' COMMENT '弹幕模式',
  `ip` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '用户IP',
  `create_time` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '创建时间',
  `status` tinyint(1) UNSIGNED NOT NULL DEFAULT '1' COMMENT '状态(0=禁用,1=启用)', 
  PRIMARY KEY(id),
  KEY(vid),
  KEY(uid),
  KEY(create_time)
  )ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='弹幕表';
  ";
    db_exec($sql);
}
$replace = array();
$replace['fast_api'] = 1; 
$replace['danmu'] = array(
    'on' => 0,
    'cdn' => 1,
    'start_ad' => 0,
    'danmu_toggle' => 0,
    'pause_ad' => 0,
    'selec_col' => 0,
    'start_ad_on' => 0,
    'start_img_ad' => '/static/stop.png',
    'start_mp4_ad' => '/static/test1.mp4',
    'start_ad_url' => 'https://baidu.com',
    'start_all_time' => 4,
    'start_close_time' => 2,
    'stop_ad' => '/static/stop.png',
    'stop_ad_url' => 'https://baidu.com',
); 
file_replace_var(APP_PATH . 'config/config.php', $replace);
echo '升级成功';
?>