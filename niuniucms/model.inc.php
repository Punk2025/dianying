<?php
!defined('DEBUG') AND exit('Forbidden'); 
$include_model_files = array(
    APP_PATH . 'model/runtime.php',
    APP_PATH . 'model/kv.php',
    APP_PATH . 'model/user.php',
    APP_PATH . 'model/session.php',
    APP_PATH . 'model/cate.php',
    APP_PATH . 'model/vod.php', 
    APP_PATH . 'model/art.php',   
    APP_PATH . 'model/tag.php', 
    APP_PATH . 'model/like.php',
    APP_PATH . 'model/favorites.php',
    APP_PATH . 'model/link.php', 
    APP_PATH . 'model/view.php',
    APP_PATH . 'model/block.php',
    APP_PATH . 'model/phpanalysis.php',
    APP_PATH . 'model/phpanalys.php',
    APP_PATH . 'model/top.php',
);
if (DEBUG) {
    foreach ($include_model_files as $model_files) {
        include _include($model_files);
    }
} else {
    $model_min_file = $conf['cache_path'] . 'model.min.php';
    $model_min_mtime = is_file($model_min_file) ? filemtime($model_min_file) : 0;
    $need_build = empty($model_min_mtime);
    if (!$need_build) {
        foreach ($include_model_files as $model_files) {
            $compiled = _include($model_files);
            if (is_file($compiled) && filemtime($compiled) > $model_min_mtime) {
                $need_build = true;
                break;
            }
        }
    }
    if ($need_build) {
        $lockname = 'build_model_min';
        if (xn_lock_start($lockname, 30)) {
            $s = '';
            foreach ($include_model_files as $model_files) {
                $t = file_get_contents(_include($model_files));
                $t = trim($t);
                $t = ltrim($t, '<?php');
                $t = rtrim($t, '?>');
                $s .= "<?php\r\n" . $t . "\r\n?>";
            }
            file_put_contents_try($model_min_file, $s);
            xn_lock_end($lockname);
        } else {
            $wait = 20;
            while ($wait-- > 0) {
                if (is_file($model_min_file) && filesize($model_min_file) > 0) break;
                usleep(200000);
            }
        }
    }
    $use_min = is_file($model_min_file) && filesize($model_min_file) > 0;
    if ($use_min) {
        include $model_min_file;
    } else {
        foreach ($include_model_files as $model_files) {
            include _include($model_files);
        }
    }
}
?>
