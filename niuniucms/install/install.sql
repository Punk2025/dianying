#用户组 
DROP TABLE IF EXISTS `niuniucms_user`;
CREATE TABLE `niuniucms_user` (
  `uid` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '用户编号',
  `gid` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '用户组编号',
  `email` char(40) NOT NULL DEFAULT '' COMMENT '邮箱',
  `username` char(32) NOT NULL DEFAULT '' COMMENT '用户名',
  `password` char(32) NOT NULL DEFAULT '' COMMENT '密码',
  `salt` char(16) NOT NULL DEFAULT '' COMMENT '密码混杂',
  `create_date` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `login_date` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `login_ip` decimal(39,0) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `logins` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `create_ip` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `likes` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '赞数量',
  `get_likes` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '赞数量',
  `favorites` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '收藏数量',
  `get_favorites` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '收藏数量',
  `invitations` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '邀请码',
  `invitation_useds` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '邀请码使用',
  PRIMARY KEY (`uid`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `gid` (`gid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='用户表';
INSERT INTO `niuniucms_user` SET uid=1, gid=1, email='admin@admin.com', username='admin',`password`='d98bb50e808918dd45a8d92feafc4fa3',salt='123456';
#会员组
DROP TABLE IF EXISTS `niuniucms_group`;
CREATE TABLE `niuniucms_group` (
 `gid` smallint(6) unsigned NOT NULL,
  `name` varchar(20) NOT NULL DEFAULT '',
  `intoadmin` tinyint(1) NOT NULL DEFAULT '0',
  `allowread` tinyint(1) NOT NULL DEFAULT '0',
  `manageuser` int(10) unsigned NOT NULL DEFAULT '0',
  `managecreateuser` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`gid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='用户表';
INSERT INTO `niuniucms_group` (`gid`, `name`, `intoadmin`,`allowread`) VALUES
(0, '游客组', 0,1),
(1, '管理员', 1,1),
(2, '普通会员',0,1);
DROP TABLE IF EXISTS `niuniucms_cache`;
CREATE TABLE `niuniucms_cache` (
  `k` char(32) NOT NULL default '',
  `v` mediumtext NOT NULL,
  `expiry` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY(`k`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
DROP TABLE IF EXISTS `niuniucms_kv`;
CREATE TABLE `niuniucms_kv` (
  `k` char(32) NOT NULL default '',
  `v` mediumtext NOT NULL,
  `expiry` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY(`k`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
INSERT INTO `niuniucms_kv` (`k`, `v`, `expiry`) VALUES ('setting', '{"conf":{"name":"niuniucms","installed":0,"setting":{"website_mode":2,"tpl_mode":0,"map":"map","verify_video":0,"verify_post":0,"verify_special":0,"thumbnail_on":1,"save_image_on":1},"picture_size":{"width":400,"height":280},"theme":"default","shield":[],"index_stickys":0}}', 0);
# 分类
DROP TABLE IF EXISTS `niuniucms_cate`;
CREATE TABLE `niuniucms_cate` (
  `cid` smallint(6) unsigned NOT NULL AUTO_INCREMENT,
  `cup` int(11) unsigned NOT NULL DEFAULT '0',
  `son` int(11) NOT NULL DEFAULT '0',
  `name` varchar(60) NOT NULL DEFAULT '',
  `type` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `model` tinyint(2) unsigned NOT NULL DEFAULT '0',
  `category` tinyint(2) unsigned NOT NULL DEFAULT '0',
  `alias` varchar(80) NOT NULL DEFAULT '',
  `rank` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `display` tinyint(1) NOT NULL DEFAULT '0',
  `nav_display` tinyint(1) NOT NULL DEFAULT '0',
  `create_date` int(11) unsigned NOT NULL DEFAULT '0',
  `seo_key` varchar(255) NOT NULL DEFAULT '',
  `seo_des` varchar(255) NOT NULL DEFAULT '',
  `seo_title` varchar(255) NOT NULL DEFAULT '',
  `videos` tinyint(1) NOT NULL DEFAULT '0',
  `arts` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`cid`) 
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='分类表'; 
DROP TABLE IF EXISTS `niuniucms_session`;
CREATE TABLE `niuniucms_session` (
  `sid` char(32) NOT NULL default '0',  
  `uid` int(11) unsigned NOT NULL default '0', 
  `tid` int(11) unsigned NOT NULL default '0', 
  `cid` int(11) unsigned NOT NULL default '0', 
  `url` char(32) NOT NULL default '',  
  `ip` decimal(39,0) unsigned NOT NULL default '0', 
  `useragent` char(128) NOT NULL default '', 
  `data` char(255) NOT NULL default '',  
  `bigdata` tinyint(1) NOT NULL default '0',

  `last_date` int(11) unsigned NOT NULL default '0', 
  PRIMARY KEY (`sid`),
  KEY `ip` (`ip`),
  KEY `last_date` (`last_date`),
  KEY `uid_last_date` (`uid`, `last_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
DROP TABLE IF EXISTS `niuniucms_session_data`;
CREATE TABLE `niuniucms_session_data` (
  `sid` char(32) NOT NULL default '0',
  `last_date` int(11) unsigned NOT NULL default '0',
  `data` text NOT NULL,
  PRIMARY KEY (`sid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
#视频
DROP TABLE IF EXISTS `niuniucms_vod`;
CREATE TABLE `niuniucms_vod` (
  `vid` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '视频vid',
  `cid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '分类id',
  `views` int(11) NOT NULL DEFAULT '0' COMMENT '查看次数',
  `sticky` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `create_date` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '发布时间',
  `alias` varchar(32) NOT NULL DEFAULT '' COMMENT 'URL别名',
  `tag` varchar(255) NOT NULL DEFAULT '' COMMENT 'tag',
  `status` tinyint(2) NOT NULL DEFAULT '0' COMMENT '0:通过 1~9审核:1待审核 2草稿',
  `uptime` varchar(255) NOT NULL DEFAULT '' COMMENT '更新时间',
  `vod_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'MacCms id',
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '视频名称',
  `sub` varchar(255) NOT NULL DEFAULT '' COMMENT '副标',
  `en` varchar(255) NOT NULL DEFAULT '' COMMENT '拼音',
  `letter` char(1) NOT NULL DEFAULT '' COMMENT '首字母',
  `pic` varchar(1024) NOT NULL DEFAULT '' COMMENT '图片',
  `pic_slide` varchar(1024) NOT NULL DEFAULT '' COMMENT '海报',
  `actor` varchar(255) NOT NULL DEFAULT '' COMMENT '演员',
  `director` varchar(255) NOT NULL DEFAULT '' COMMENT '导演',
  `writer` varchar(100) NOT NULL DEFAULT '' COMMENT '编剧',
  `behind` varchar(100) NOT NULL DEFAULT '' COMMENT '幕后',
  `blurb` varchar(255) NOT NULL DEFAULT '' COMMENT '简介',
  `remarks` varchar(100) NOT NULL DEFAULT '' COMMENT '备注',
  `pubdate` varchar(100) NOT NULL DEFAULT '' COMMENT '上映日期',
  `total` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '总集数',
  `serial` varchar(20) NOT NULL DEFAULT '0' COMMENT '连载数',
  `area` varchar(20) NOT NULL DEFAULT '' COMMENT '地区',
  `lang` varchar(10) NOT NULL DEFAULT '' COMMENT '语言',
  `year` varchar(10) NOT NULL DEFAULT '' COMMENT '年份',
  `version` varchar(30) NOT NULL DEFAULT '' COMMENT '版本',
  `level` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '推荐等级',
  `douban_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '豆瓣id',
  `douban_score` decimal(3,1) unsigned NOT NULL DEFAULT '0.0' COMMENT '豆瓣评分',
  `content` mediumtext NOT NULL COMMENT '内容',
  `play_from` varchar(255) NOT NULL DEFAULT '' COMMENT '播放器来源',
  `play_server` varchar(255) NOT NULL DEFAULT '' COMMENT '播放服务器',
  `play_note` varchar(255) NOT NULL DEFAULT '' COMMENT '播放说明',
  `play_url` mediumtext NOT NULL COMMENT '播放地址',
  `cup` int(10) unsigned NOT NULL DEFAULT '0',
  `favorites` int(10) unsigned NOT NULL DEFAULT '0',
  `likes` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`vid`),
  KEY `alias` (`alias`),
  KEY `year` (`year`),
  KEY `area` (`area`),
  KEY `lang` (`lang`),
  KEY `cid_views` (`cid`, `views`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='视频表';
DROP TABLE IF EXISTS `niuniucms_vod_sticky`;
CREATE TABLE `niuniucms_vod_sticky` (
  `vid` int(11) unsigned NOT NULL DEFAULT '0',
  `cid` int(11) unsigned NOT NULL DEFAULT '0',
  `sticky` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `create_date` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`vid`),
  KEY `sticky_vid` (`sticky`,`vid`),
  KEY `cid_sticky` (`cid`,`sticky`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='视频推荐表';
DROP TABLE IF EXISTS `niuniucms_vod_vid`;
CREATE TABLE `niuniucms_vod_vid` (
  `vid` int(11) unsigned NOT NULL,
  `cid` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`vid`),
  KEY `cid_vid` (`cid`,`vid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='视频vid表';
DROP TABLE IF EXISTS `niuniucms_art`;
CREATE TABLE `niuniucms_art` (
  `aid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cid` smallint(6) unsigned NOT NULL DEFAULT '0' ,
  `name` varchar(255) NOT NULL DEFAULT '' ,
  `tag` varchar(255) NOT NULL DEFAULT '' ,
  `pic` varchar(1024) NOT NULL DEFAULT '' ,
  `views` int(11) NOT NULL DEFAULT '0',
  `content` longtext NOT NULL,
  `create_date` int(11) unsigned NOT NULL DEFAULT '0',
  `favorites` int(10) unsigned NOT NULL DEFAULT '0',
  `likes` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`aid`),
  KEY `cid_views` (`cid`, `views`)
)ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='文章表';

DROP TABLE IF EXISTS `niuniucms_art_aid`;
CREATE TABLE `niuniucms_art_aid` (
  `aid` int(11) UNSIGNED NOT NULL,
  `cid` int(11) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`aid`),
  INDEX `cid_aid`(`cid`, `aid`) 
)ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='文章aid表';


DROP TABLE IF EXISTS `niuniucms_tag`;
CREATE TABLE `niuniucms_tag` (
  `tagid` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` char(64) NOT NULL DEFAULT '',
  `en` char(32) NOT NULL DEFAULT '',
  `count` int(11) NOT NULL DEFAULT '0',
  `seo_title` varchar(255) NOT NULL DEFAULT '' COMMENT 'SEO标题',
  `seo_key` varchar(255) NOT NULL DEFAULT '' COMMENT 'SEO关键词',
  `seo_des` varchar(255) NOT NULL DEFAULT '' COMMENT 'SEO描述',
  PRIMARY KEY (`tagid`),
  KEY `name` (`name`),
  KEY `en` (`en`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='标签表';
DROP TABLE IF EXISTS `niuniucms_tag_id`;
CREATE TABLE `niuniucms_tag_id` (
`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
`tagid` int(11) unsigned NOT NULL DEFAULT '0',
`vid` int(11) unsigned NOT NULL DEFAULT '0',
PRIMARY KEY (`id`),
KEY `vid` (`vid`),
KEY `tagid_id` (`tagid`,`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='标签数据表';
DROP TABLE IF EXISTS `niuniucms_link`;
CREATE TABLE `niuniucms_link` (
   `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `rank` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `name` varchar(24) NOT NULL DEFAULT '',
  `url` varchar(120) NOT NULL DEFAULT '',
  `create_date` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `rank` (`rank`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='友情链接表';
DROP TABLE IF EXISTS `niuniucms_like`;
CREATE TABLE `niuniucms_like` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(11) unsigned NOT NULL DEFAULT '0',
  `touid` int(11) unsigned NOT NULL DEFAULT '0',
  `vid` int(11) unsigned NOT NULL DEFAULT '0', # 视频
  `aid` int(11) unsigned NOT NULL DEFAULT '0', # 文章
  `type` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `create_date` int(11) unsigned NOT NULL DEFAULT '0',
  `model` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `vid_uid` (`vid`,`uid`),
  KEY `aid_uid` (`aid`,`uid`),
  KEY `touid_id` (`touid`,`id`),
  KEY `uid_type_id` (`uid`,`type`,`id`)
)ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='点赞表';
DROP TABLE IF EXISTS `niuniucms_favorites`;
CREATE TABLE `niuniucms_favorites` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(11) unsigned NOT NULL DEFAULT '0',
  `touid` int(11) unsigned NOT NULL DEFAULT '0',
  `vid` int(11) unsigned NOT NULL DEFAULT '0',
  `aid` int(11) unsigned NOT NULL DEFAULT '0',
  `type` tinyint(2) unsigned NOT NULL DEFAULT '0',
  `create_date` int(11) unsigned NOT NULL DEFAULT '0',
  `model` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `vid_uid` (`vid`,`uid`),
  KEY `aid_uid` (`aid`,`uid`),
  KEY `touid_id` (`touid`,`id`),
  KEY `uid_id` (`uid`,`id`)
)ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='收藏表';

DROP TABLE IF EXISTS `niuniucms_invitation`;
CREATE TABLE `niuniucms_invitation` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(11) unsigned NOT NULL DEFAULT '0', 
  `touid` int(11) unsigned NOT NULL DEFAULT '0',  
  `code` char(32) NOT NULL default '', 
  `state` tinyint(1) unsigned NOT NULL DEFAULT '0', 
  `use_date` int(11) unsigned NOT NULL default '0', 
  `create_date` int(11) unsigned NOT NULL DEFAULT '0', 
  PRIMARY KEY (`id`),
  KEY `code` (`code`),
  KEY `uid_id` (`uid`,`id`) 
)ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='邀请码注册';

DROP TABLE IF EXISTS `niuniucms_vod_top`;
CREATE TABLE `niuniucms_vod_top` (
  id INT(11) NOT NULL AUTO_INCREMENT,
  vid INT(11) NOT NULL,
  cid INT(11) NOT NULL,
  create_date INT(11) NOT NULL,
  PRIMARY KEY(id),
  KEY(vid),
  KEY(cid),
  KEY(create_date) 
)ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='视频热门表';

DROP TABLE IF EXISTS `niuniucms_vod_top_ip`;
CREATE TABLE `niuniucms_vod_top_ip` (
  id INT(11) NOT NULL AUTO_INCREMENT,
  vid INT(11) NOT NULL,
  ip DECIMAL(39,0) NOT NULL DEFAULT '0',
  create_date INT(11) NOT NULL,
  PRIMARY KEY(id),
  KEY(vid),
  KEY(ip),
  KEY(create_date)
)ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='视频热门表ip';


DROP TABLE IF EXISTS `niuniucms_art_top`;
CREATE TABLE `niuniucms_art_top` (
  id INT(11) NOT NULL AUTO_INCREMENT,
  aid INT(11) NOT NULL,
  cid INT(11) NOT NULL,
  create_date INT(11) NOT NULL,
  PRIMARY KEY(id),
  KEY(aid),
  KEY(cid),
  KEY(create_date) 
)ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='文章热门表';

DROP TABLE IF EXISTS `niuniucms_art_top_ip`;
CREATE TABLE `niuniucms_art_top_ip` (
  id INT(11) NOT NULL AUTO_INCREMENT,
  aid INT(11) NOT NULL,
  ip DECIMAL(39,0) NOT NULL DEFAULT '0',
  create_date INT(11) NOT NULL,
  PRIMARY KEY(id),
  KEY(aid),
  KEY(ip),
  KEY(create_date)
)ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='文章热门表ip';

DROP TABLE IF EXISTS `niuniucms_danmu`;
CREATE TABLE `niuniucms_danmu` (
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
