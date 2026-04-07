<?php
return array(
  'db' => array(
    'type' => 'mysql',
    'mysql' => array(
      'master' => array(
        'host' => 'localhost',
        'user' => 'root',
        'password' => 'root',
        'name' => 'test',
        'tablepre' => 'bull_',
        'charset' => 'utf8',
        'engine' => 'innodb',
      ),
      'slaves' => array(),
    ),
    'pdo_mysql' => array(
      'master' => array(
        'host' => 'localhost',
        'user' => 'root',
        'password' => 'root',
        'name' => 'test',
        'tablepre' => 'bull_',
        'charset' => 'utf8',
        'engine' => 'innodb',
      ),
      'slaves' => array(),
    ),
  ),
  'cache' => array(
    'enable' => true,
    'type' => 'mysql',
    'memcached' => array(
      'host' => 'localhost',
      'port' => '11211',
      'cachepre' => 'bull_',
    ),
    'redis' => array(
      'host' => 'localhost',
      'port' => '6379',
      'cachepre' => 'bull_',
    ),
    'yac' => array(
      'cachepre' => 'bull_',
    ),
    'mysql' => array(
      'cachepre' => 'bull_',
    ),
  ),
  'cache_path' => './runtime/cache/',
  'log_path' => './runtime/log/',
  'upload_url' => '/upload/',
  'upload_path' => './upload/',
  'sitename' => 'Website12',
  'view_url' => 'template/',
  'path' => './', // 前台路径 "/" 结尾
  'logo' => '',
  'logo_m' => '/static/logo.png',
  'logo_pc' => '/static/logo.png',
  'timezone' => 'Asia/Shanghai',  // 时区，默认中国
  'cms_lang' => 'zh-cn',
  'runlevel' => 5,    // 0: 站点关闭; 1: 管理员可读写; 2: 会员可读;  3: 会员可读写; 4：所有人只读; 5: 所有人可读写
  'runlevel_reason' => '网站正在维护中，请稍后访问.',
  'cookie_pre' => 'bull_', // cookie 前缀
  'cookie_domain' => '',   // cookie使用的域名，为空表示当前域名
  'cookie_path' => '',     // 为空则表示当前目录和子目录
  'cookie_lifetime' => '8640000', // cookie生命期8640000为100天
  'auth_key' => '',
  'auth_salt' => '1KJ5B3M7BG2G6BhhIgHEfgiXB5xcyLpP/yyx6ybVUaI=',
  'pagesize' => 20,
  'postlist_pagesize' => 100,
  'listsize' => 10000,  // 大数据量控制显示100页
  'linksize' => 20,   // 友情链接调用数量 排序数值最大的20个
  'tagsize' => 60,    // 显示tag的数量 
  'tag_num' => 10,   //tag分词数量
  'online_update_span' => 120, // 在线更新频度，大站设置的长一些
  'online_hold_time' => 3600,  // 在线的时间
  'session_delay_update' => 0, // 开启 session 延迟更新，减轻压力，会导致不重要的数据(useragent,url)显示有些延迟，单位为秒。
  'upload_image_width' => 927,    // 上传图片自动缩略的最大宽度
  'upload_resize' => 'thumb',      // 上传图片clip裁切 thumb缩略
  'order_default' => 'lastpid',   // 作为回复排序
  'attach_dir_save_rule' => 'Ymd', // 附件存放规则，附件多用：Ymd，附件少：Ym 
  'user_create_on' => 1,
  'user_resetpw_on' => 0,
  'admin_bind_ip' => 0, // 后台是否绑定 IP 0关闭 1启用
  'login_only' => 0,
  'login_ip' => 0,
  'login_ua' => 0,
  'cdn_on' => 0,
  'api_on' => 0, // 默认关闭，打开后前台部分页面可通过api获取数据
  'search_on' => 1,
  'sitemap_play' => 1,
  'sitemap_tag' => 1,
  'sitemap_num' => 1000,
  'url_rewrite_on' => 0,
  'disabled_plugin' => 0,
  'version' => '1.0.1',
  'installed' => 0,
  'compress' => 1, // 代码压缩 0关闭 1仅压缩php、html代码(不压缩js代码) 2压缩全部代码 如果启用压缩出现错误，请关闭，删除html中的所有注释，并且js代码按照英文分号结束的地方加上分号; 
  'down_pic' => 0,
  'lang' => '国语,英语,粤语,闽南语,韩语,日语,法语,德语,其它',
  'area' => '中国大陆,中国香港,中国台湾,美国,韩国,日本,泰国,新加坡,马来西亚,印度,英国,法国,加拿大,西班牙,俄罗斯,其它',
  'year' => '2025,2024,2023,2022,2021,2020,2019,2018,2017,2016,2015,2014,2013,2012,2011,2010,2009,2008,2007,2006,2005,2004,2003,2002,2001,2000',
  'agg' => '',
  'tg' => 'tg',
  'email' => 'admina@qq.com',
  'fast_api' => 1,
  'danmu' => 
  array (
    'on' => 0,
    'cdn' => 1,
    'start_ad' => 0,
    'danmu_toggle' => 1,
    'pause_ad' => 1,
    'selec_col' => 1,
    'start_ad_on' => 1,
    'start_img_ad' => '/static/stop.png',
    'start_mp4_ad' => '/static/test1.mp4',
    'start_ad_url' => '',
    'start_all_time' => 4,
    'start_close_time' => 2,
    'stop_ad' => '/static/stop.png',
    'stop_ad_url' => '',
  ),
  'route' =>
  array(
    'cate' => 'cates',
    'list' => 'lists',
    'vod' => 'vodss',
    'play' => 'plays',
    'type' => 'types',
    'tag' => 'tagss',
    'art' => 'arts',
  ),
  'seo' =>
  array(
    'index' =>
    array(
      'seo_title' => '2025年最新电影电视剧无广告免费观看完整未删减-{$sitename}最新网站',
      'seo_des' => '{$sitename}是一家免费VIP影视观看网站，专业全网收集好看的电视剧电影{$sitename}，体验最近热播的犯罪片、家庭片、青春片、综艺片、BD1080P版、极致的观看体验、便捷的高速播放、更多电影高清在线尽在{$sitename}',
      'seo_key' => '{$sitename},免费电影在线影视网站，BD加长，枪战片,字幕组,战争片,推荐短剧',
    ),
    'list' =>
    array(
      'seo_title' => '{$name}推荐-好看的{$name}-最新{$name} - 第{$page}页 - {$sitename}',
      'seo_des' => '{$name}栏目收集整理最新好看的{$name}大全，提供{$name}排行榜前十推荐，{$page}为网友提供高清全集完整版画质的{$name}，同时支持手机无广告在线免费播放',
      'seo_key' => '好看的{$name},{$name},{$name}排行榜,最新{$name},经典{$name}',
    ),
    'art_list' =>
    array(
      'seo_title' => '{$name}推荐-{$name}-最新{$name} - {$sitename}',
      'seo_des' => '{$name}栏目收集整理最新好看的{$name}大全，提供{$name}排行榜前十推荐，{$name}，同时支持手机无广告在线浏览',
      'seo_key' => '好看的{$name},{$name}排行榜,最新{$name},{$name}',
    ),
    'art' =>
    array(
      'seo_title' => '《{$name}》{$cate_name},{$name}-最新资讯- {$sitename}',
      'seo_des' => '{$name}栏目收集整理最新好看的{$name}大全，提供{$name}排行榜前十推荐，{$name}，同时支持手机无广告在线浏览',
      'seo_key' => '{$des}',
    ),
    'type' =>
    array(
      'seo_title' => '{$year}{$area}{$lang}{$name}推荐-好看的{$name}-{$lang}{$area}{$year}最新{$name}  - {$sitename}',
      'seo_key' => '好看的{$name},{$lang}{$area}{$year},{$name},{$name}排行榜,最新{$name},经典{$name}',
      'seo_des' => '{$name}栏目收集整理最新好看的{$year}{$lang}{$area}{$name}大全，提供{$name}排行榜前十推荐，为网友提供高清全集完整版画质的{$name}，同时支持手机无广告在线免费播放',
    ),
    'vod' =>
    array(
      'seo_title' => '《{$name}》{$cate_name}免费在线观看,{$name}-{$remarks}完整版- {$sitename}',
      'seo_key' => '{$name}高清在线观看,{$name}免费完整版,{$name}剧情介绍,{$sitename}',
      'seo_des' => '{$des}',
    ),
    'play' =>
    array(
      'seo_title' => '《{$name}》{$play_name}免费在线观看完整版- {$sitename}',
      'seo_key' => '{$name}{$play_name}高清在线观看,在线观看,{$name}免费播放,{$sitename}',
      'seo_des' => '{$sitename}为您带来《{$name}》{$play_name}高清完整版免费在线观看。',
    ),
    'tag' =>
    array(
      'seo_title' => '{$name}电影推荐-好看的{$name}电影-最新{$name}综艺 - 第{$page}页 - {$sitename}',
      'seo_key' => '好看的{$name}视频,{$name},{$name}电视剧排行榜,最新{$name}电影,经典{$name}综艺',
      'seo_des' => '{$name}栏目收集整理最新好看的{$name}电视剧大全，提供{$name}电影排行榜前十推荐，为网友提供高清全集完整版画质的{$name}，同时支持手机无广告在线免费播放',
    ),
    'tag_list' =>
    array(
      'seo_title' => '2025年最好看的电影，免费观看高清电影在线观看大全_2025年最新热映电影排行榜推荐_高清免费观看电影-{$sitename}',
      'seo_key' => '2025年最好看的电影，免费观看高清电影大全,最近好看的电影在线观看大全,2025年最新热映电影排行榜推荐,{$sitename}',
      'seo_des' => '2025年最好看的电影，免费观看高清电影大全{$sitename}为你提供好看的电影大全，最新热映电影高清免费观看推荐及相关排行榜等影视电影院资源。',
    ),
    'searchs' =>
    array(
      'seo_title' => '{$name} 相关影视电影院资源搜索结果 -{$sitename}',
      'seo_key' => '{$name}相关影视电影院资源搜索结果,免费观看电视剧高清电影,星辰影院,青丝影院,青柠影院,八戒影院,天狼影院',
      'seo_des' => '2025年最好看的电影，免费观看高清电影大全为您找到关于{$name}的{$num}条相关影视电影院资源。',
    ),
  ),
  'api_api' =>  array(
    'vod' =>
    array(
      'status' => '1', //审核状态
      'hits_start' => '1', //随机点击
      'hits_end' => '1000', //随机点击
      'updown_start' => '1', //随机顶
      'updown_end' => '1000', //随机顶踩
      'score' => '1', //随机评分
      'pic' => '1', //自动图片下周
      'tag' => '1', //自动tag
      'class_filter' => '0', //将自动过滤扩分类名称里的[片,剧],例如动作片会变为动作;欧美剧会变成欧美;
      'psename' => '0', //自动转换名称同义词，降低重复率。例如:第1季=第一季;
      'psernd' => '0', //详情随机插入语句
      'psesyn' => '0', //详情同义词替换
      'pseplayer' => '0', //播放器同义词替换
      'psearea' => '0', //地区同义词替换
      'pselang' => '0', //语言同义词替换
      'urlrole' => '1', //地址二更规则 二次更新地址遇到同类型播放器。替换：只保留新提交的地址。合并：整合原有地址和新地址去重。
      'uprule' => ',a,c,d', //二次更新规则
      'filter' => '色戒,色即是空', //数据过滤
      'namewords' => '第1季=第一季#第2季=第二季#第3季=第三季#第4季=第四季',
      'thesaurus' => ' =',
      'playerwords' => '',
      'areawords' => '',
      'langwords' => '',
      'words' => '',
      'inrule' => ',a',
    ),
    'art' =>
    array(
      'status' => '1',
      'hits_start' => '1',
      'hits_end' => '1000',
      'updown_start' => '1',
      'updown_end' => '1000',
      'score' => '1',
      'pic' => '0',
      'tag' => '0',
      'psernd' => '0',
      'psesyn' => '0',
      'inrule' => ',b',
      'uprule' => ',a,d',
      'filter' => '无奈的人',
      'thesaurus' => '',
      'words' => '',
    ),
  ),
  'vodplayer' => array(
    'default' =>
    array(
      'status' => '1',
      'from' => 'default',
      'show' => '默认播放器',
      'des' => '默认播放器',
      'target' => '_self',
      'ps' => '1',
      'parse' => 'https://jiexi.kuaichezy.info/m3u8/?url=',
      'sort' => 100,
      'tip' => '无需安装任何插件',
    ),
  ),
  'api' => array(),
  'bind' => array(),
);
?>