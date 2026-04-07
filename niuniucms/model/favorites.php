<?php
!defined('DEBUG') and exit('Access Denied.');
function art_favorites($aid, $uid)
{
    $data = array(
        'uid' => $uid,
        'aid' => $aid,
        'model' => 1,
        'create_date' => time(),
    );
    db_insert('favorites', $data);
}
function art_unfavorites($aid, $uid)
{
    db_delete('favorites', array('uid' => $uid, 'aid' => $aid, 'model' => 1));
}
function art_favorites_view($aid)
{
    global   $db;
    $tablepre = $db->tablepre;
    db_exec("UPDATE  `{$tablepre}art` SET favorites=favorites+1 WHERE aid='$aid'");
}
function art_favorites_check($aid, $uid)
{
    return db_count('favorites', array('uid' => $uid, 'aid' => $aid, 'model' => 1)) > 0;
}
function art_favorites_del($aid, $uid)
{
    db_delete('favorites', array('uid' => $uid, 'aid' => $aid, 'model' => 1));
}
function art_unfavorites_view($aid)
{
    global   $db;
    $tablepre = $db->tablepre;
    db_exec("UPDATE  `{$tablepre}art` SET favorites=favorites-1 WHERE aid='$aid'");
}
function vod_favorites_view($aid)
{
    global   $db;
    $tablepre = $db->tablepre;
    db_exec("UPDATE  `{$tablepre}vod` SET favorites=favorites+1 WHERE vid='$aid'");
}
function vod_favorites_check($aid, $uid)
{
    return db_count('favorites', array('uid' => $uid, 'aid' => $aid, 'model' => 0)) > 0;
}
function vod_favorites_del($aid, $uid)
{
    db_delete('favorites', array('uid' => $uid, 'aid' => $aid, 'model' => 0));  
}
function vod_unfavorites_view($aid)
{
    global   $db;
    $tablepre = $db->tablepre;
    db_exec("UPDATE  `{$tablepre}vod` SET favorites=favorites-1 WHERE vid='$aid'");
}
function vod_favorites($aid, $uid)
{
    $data = array(
        'uid' => $uid,
        'aid' => $aid,
        'model' => 0,
        'create_date' => time(),
    );
    db_insert('favorites', $data);  
}
function vod_unfavorites($aid, $uid)
{
    db_delete('favorites', array('uid' => $uid, 'aid' => $aid, 'model' => 0));
}

function art_favorites_count($uid){
    return db_count('favorites',array('uid'=>$uid,'model'=>1));
}
function vod_favorites_count($uid){
    return db_count('favorites',array('uid'=>$uid,'model'=>0));         
}
function art_favorites_list($uid,$page,$limit=20){
    return db_find('favorites',array('uid'=>$uid,'model'=>1),array(),$page,$limit);
}
function vod_favorites_list($uid,$page,$limit=20){
    return db_find('favorites',array('uid'=>$uid,'model'=>0),array(),$page,$limit); 
}


 

