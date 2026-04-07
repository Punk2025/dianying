<?php 
function link__create($arr = array(), $d = NULL)
{
    $r = db_replace('link', $arr, $d);
    return $r;
}
function link__update($cond = array(), $update = array(), $d = NULL)
{
    $r = db_update('link', $cond, $update, $d);
    return $r;
}
function link__find($cond = array(), $orderby = array(), $page = 1, $pagesize = 20, $key = 'id', $col = array(), $d = NULL)
{
    $arr = db_find('link', $cond, $orderby, $page, $pagesize, $key, $col, $d);
    return $arr;
}
function link__delete($cond = array(), $d = NULL)
{
    $r = db_delete('link', $cond, $d);
    return $r;
}
function link_count($cond = array(), $d = NULL)
{
    $n = db_count('link', $cond, $d);
    return $n;
}
function link_create($arr)
{
    if (empty($arr)) return FALSE;
    $r = link__create($arr);
    link_delete_cache();
    return $r;
}
function link_update($id, $update)
{
    if (empty($id) || empty($update)) return FALSE;
    $r = link__update(array('id' => $id), $update);
    link_delete_cache();
    return $r;
}
function link_find($page = 1, $pagesize = 100)
{
    $arr = link__find($cond = array(), array('rank' => -1), $page, $pagesize);
    return $arr;
}
function link_delete($id)
{
    if (empty($id)) return FALSE;
    $r = link__delete(array('id' => $id));
    link_delete_cache();
    return $r;
}
function link_get($page = 1, $pagesize = 100)
{
    $g_link = website_get('friends_link');
    if (empty($g_link)) {
        $g_link = link_find($page, $pagesize);
        $g_link AND website_set('friends_link', $g_link);
    }
    return $g_link;
}
function link_delete_cache()
{
    website_set('friends_link', '');
    return TRUE;
}
?>