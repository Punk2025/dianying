<?php
!defined('DEBUG') and exit('Access Denied.');
function art_like($aid, $uid)
{
    $data = array(
        'uid' => $uid,
        'aid' => $aid,
        'model' => 1,
        'create_date' => time(),
    );
    db_insert('like', $data);
}
function art_unlike($aid, $uid)
{
    db_delete('like', array('uid' => $uid, 'aid' => $aid, 'model' => 1));
}
function art_like_view($aid)
{
    global   $db;
    $tablepre = $db->tablepre;
    db_exec("UPDATE  `{$tablepre}art` SET likes=likes+1 WHERE aid='$aid'");
}
function art_like_check($aid, $uid)
{
    return db_count('like', array('uid' => $uid, 'aid' => $aid, 'model' => 1)) > 0;
}
function art_like_del($aid, $uid)
{
    db_delete('like', array('uid' => $uid, 'aid' => $aid, 'model' => 1));
}
function art_unlike_view($aid)
{
    global   $db;
    $tablepre = $db->tablepre;
    db_exec("UPDATE  `{$tablepre}art` SET likes=likes-1 WHERE aid='$aid'");
}
function vod_like_view($aid)
{
    global   $db;
    $tablepre = $db->tablepre;
    db_exec("UPDATE  `{$tablepre}vod` SET likes=likes+1 WHERE vid='$aid'");
}
function vod_like_check($aid, $uid)
{
    return db_count('like', array('uid' => $uid, 'aid' => $aid, 'model' => 0)) > 0;
}
function vod_like_del($aid, $uid)
{
    db_delete('like', array('uid' => $uid, 'aid' => $aid, 'model' => 0));
}
function vod_unlike_view($aid)
{
    global   $db;
    $tablepre = $db->tablepre;
    db_exec("UPDATE  `{$tablepre}vod` SET likes=likes-1 WHERE vid='$aid'");
}
function vod_like($aid, $uid)
{
    $data = array(
        'uid' => $uid,
        'aid' => $aid,
        'model' => 0,
        'create_date' => time(),
    );
    db_insert('like', $data);
}
function vod_unlike($aid, $uid)
{
    db_delete('like', array('uid' => $uid, 'aid' => $aid, 'model' => 0));
}

function art_like_count($uid){
    return db_count('like',array('uid'=>$uid,'model'=>1));
}
function vod_like_count($uid){
    return db_count('like',array('uid'=>$uid,'model'=>0));
}
function art_like_list($uid,$page,$limit=20){
    return db_find('like',array('uid'=>$uid,'model'=>1),array(),$page,$limit);
}
function vod_like_list($uid,$page,$limit=20){
    return db_find('like',array('uid'=>$uid,'model'=>0),array(),$page,$limit);
}


 

