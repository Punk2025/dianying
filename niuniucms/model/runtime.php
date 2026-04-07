<?php
function runtime_init()
{
    global $conf;
    $runtime = 'mysql' == $conf['cache']['type'] ? website_get('runtime') : cache_get('runtime');
    if (NULL === $runtime || empty($runtime['users'])) {
        $runtime = array();
        $runtime['users'] = user_count();
        $runtime['vod'] = video_count();
        $runtime['vod_day'] = video_count_day();
        $runtime['art'] = art_count();
        $runtime['art_day'] = art_count_day();
        $runtime['onlines'] = max(1, online_count());
        $runtime['cron_1_last_date'] = 0;
        $runtime['cron_2_last_date'] = 0;
        art_del();
        vod_del();
        'mysql' == $conf['cache']['type'] ? website_set('runtime', $runtime) : cache_set('runtime', $runtime);
    }
    return $runtime;
}
function runtime_get($k)
{
    global $runtime;
    return array_value($runtime, $k, NULL);
}
function runtime_set($k, $v)
{
    global $conf, $runtime;
    $op = substr($k, -1);
    if ('+' == $op || '-' == $op) {
        $k = substr($k, 0, -1);
        isset($runtime[$k]) || $runtime[$k] = 0;
        $v = '+' == $op ? ($runtime[$k] + $v) : ($runtime[$k] - $v);
    }
    $runtime[$k] = $v;
    return TRUE;
}
function runtime_delete($k)
{
    global $conf, $runtime;
    unset($runtime[$k]);
    runtime_save();
    return TRUE;
}
function runtime_save()
{
    global $conf, $runtime;
    function_exists('chdir') and chdir(APP_PATH);
    $r = 'mysql' == $conf['cache']['type'] ? website_set('runtime', $runtime) : cache_set('runtime', $runtime);
}
function runtime_truncate()
{
    global $conf;
    'mysql' == $conf['cache']['type'] ? website_set('runtime', '') : cache_delete('runtime');
}
register_shutdown_function('runtime_save');
?>