<?php
function get_tag($content = "",$num = 3) {
        global $conf;
        if (empty ( $content )) {
            return '';
        }
        \PhpAnalysis::$loadInit = false;
        $pa = new \PhpAnalysis ( 'utf-8', 'utf-8', false );
        $pa->LoadDict ();
        $pa->SetSource ($content);
        $pa->StartAnalysis ( true );
        $tags = $pa->GetFinallyKeywords ($conf['tag_num']??5); // 获取文章中的n个关键字
        return $tags;//返回关键字
    }
    ?>