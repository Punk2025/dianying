-- 牛牛 CMS 广告位（dabao 前台 + 后台管理）
-- 表前缀与 config 中 tablepre 一致，默认 nncms_

CREATE TABLE IF NOT EXISTS `nncms_ad` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `slot_key` varchar(40) NOT NULL DEFAULT '' COMMENT '版位标识',
  `name` varchar(120) NOT NULL DEFAULT '' COMMENT '内部名称',
  `ad_type` tinyint(2) unsigned NOT NULL DEFAULT '1' COMMENT '1图片+链接 2HTML代码',
  `image` varchar(500) NOT NULL DEFAULT '',
  `url` varchar(800) NOT NULL DEFAULT '',
  `content_html` mediumtext COMMENT 'HTML广告',
  `weight` int(11) NOT NULL DEFAULT '0' COMMENT '越大越靠前',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1上架0下架',
  `starttime` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '0不限制',
  `endtime` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '0不限制',
  `clicks` int(11) unsigned NOT NULL DEFAULT '0',
  `views` int(11) unsigned NOT NULL DEFAULT '0',
  `create_date` int(11) unsigned NOT NULL DEFAULT '0',
  `update_date` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `slot_status` (`slot_key`,`status`,`weight`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COMMENT='广告投放';

CREATE TABLE IF NOT EXISTS `nncms_ad_click` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ad_id` int(11) unsigned NOT NULL DEFAULT '0',
  `slot_key` varchar(40) NOT NULL DEFAULT '',
  `longip` bigint(20) NOT NULL DEFAULT '0',
  `create_date` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `ad_day` (`ad_id`,`create_date`),
  KEY `slot_day` (`slot_key`,`create_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COMMENT='广告点击日志';
