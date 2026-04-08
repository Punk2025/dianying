-- 代理线与入口跳转
-- 表前缀与 config.php 中 tablepre 一致，默认 nncms_

CREATE TABLE IF NOT EXISTS `nncms_agent` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(32) NOT NULL DEFAULT '' COMMENT '代理码（唯一）',
  `name` varchar(80) NOT NULL DEFAULT '' COMMENT '代理名称',
  `jump_mode` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '1 H5线 2 下载线',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '1启用0停用',
  `note` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `create_date` int(11) unsigned NOT NULL DEFAULT '0',
  `update_date` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `status_mode` (`status`,`jump_mode`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COMMENT='代理账号';

CREATE TABLE IF NOT EXISTS `nncms_agent_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `agent_id` int(11) unsigned NOT NULL DEFAULT '0',
  `code` varchar(32) NOT NULL DEFAULT '',
  `type` varchar(32) NOT NULL DEFAULT '' COMMENT 'entry_jump/ad_click/register/download...',
  `memo` varchar(255) NOT NULL DEFAULT '',
  `longip` decimal(39,0) NOT NULL DEFAULT '0',
  `uri` varchar(500) NOT NULL DEFAULT '',
  `ua` varchar(255) NOT NULL DEFAULT '',
  `create_date` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `agent_day` (`agent_id`,`create_date`),
  KEY `code_type_day` (`code`,`type`,`create_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COMMENT='代理线日志';
