<?php
!defined('DEBUG') and exit('Access Denied.');
$id = param(1, 0);
$url = ad_process_click($id);
http_location($url !== '' ? $url : http_url_path());
