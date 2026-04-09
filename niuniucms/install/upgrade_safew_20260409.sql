-- SafeW 频道通知
-- 表前缀需与 config.php tablepre 一致（默认 nncms_）

CREATE TABLE IF NOT EXISTS `nncms_safew_chat` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `chat_id` varchar(32) NOT NULL DEFAULT '' COMMENT 'SafeW chat_id（群/频道）',
  `chat_type` varchar(20) NOT NULL DEFAULT '' COMMENT 'private/group/supergroup/channel',
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT '群/频道名称',
  `username` varchar(120) NOT NULL DEFAULT '' COMMENT '@username',
  `enabled` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '1启用0停用',
  `last_seen_date` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '最近发现时间',
  `create_date` int(11) unsigned NOT NULL DEFAULT '0',
  `update_date` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `chat_id` (`chat_id`),
  KEY `enabled_update` (`enabled`,`update_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COMMENT='SafeW频道/群绑定';

CREATE TABLE IF NOT EXISTS `nncms_safew_push_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `chat_id` varchar(32) NOT NULL DEFAULT '',
  `vid` int(11) unsigned NOT NULL DEFAULT '0',
  `msg_key` varchar(80) NOT NULL DEFAULT '' COMMENT '去重键',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '1成功0失败',
  `error_msg` varchar(500) NOT NULL DEFAULT '',
  `create_date` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `chat_msg` (`chat_id`,`msg_key`),
  KEY `vid_date` (`vid`,`create_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COMMENT='SafeW发送日志';

