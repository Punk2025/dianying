<?php
function array_value($arr, $key, $default = '')
{
    return isset($arr[$key]) ? $arr[$key] : $default;
}
function array_filter_empty($arr)
{
    foreach ($arr as $k => $v) {
        if (empty($v)) unset($arr[$k]);
    }
    return $arr;
}
/*
function array_isset_push(&$arr, $key, $value) {
	!isset($arr[$key]) AND $arr[$key] = array();
	$arr[$key][] = $value;
}
*/
function array_addslashes(&$var)
{
    if (is_array($var)) {
        foreach ($var as $k => &$v) {
            array_addslashes($v);
        }
    } else {
        $var = isset($var) ? $var : '';
        $var = addslashes($var);
    }
    return $var;
}
function array_stripslashes(&$var)
{
    if (is_array($var)) {
        foreach ($var as $k => &$v) {
            array_stripslashes($v);
        }
    } else {
        $var = stripslashes($var);
    }
    return $var;
}
function array_htmlspecialchars(&$var)
{
    if (is_array($var)) {
        foreach ($var as $k => &$v) {
            array_htmlspecialchars($v);
        }
    } else {
        $var = str_replace(array('&', '"', '<', '>'), array('&amp;', '&quot;', '&lt;', '&gt;'), $var);
    }
    return $var;
}
function array_trim(&$var)
{
    if (is_array($var)) {
        foreach ($var as $k => &$v) {
            array_trim($v);
        }
    } else {
        $var = trim($var);
    }
    return $var;
}
// 比较数组的值，如果不相同则保留，以第一个数组为准
function array_diff_value($arr1, $arr2)
{
    foreach ($arr1 as $k => $v) {
        if (isset($arr2[$k]) && $arr2[$k] == $v) unset($arr1[$k]);
    }
    return $arr1;
}
/*
	$data = array();
	$data[] = array('volume' => 67, 'edition' => 2);
	$data[] = array('volume' => 86, 'edition' => 1);
	$data[] = array('volume' => 85, 'edition' => 6);
	$data[] = array('volume' => 98, 'edition' => 2);
	$data[] = array('volume' => 86, 'edition' => 6);
	$data[] = array('volume' => 67, 'edition' => 7);
	arrlist_multisort($data, 'edition', TRUE);
*/
// 对多维数组排序
function arrlist_multisort($arrlist, $col, $asc = TRUE)
{
    $colarr = array();
    foreach ($arrlist as $k => $arr) {
        $colarr[$k] = $arr[$col];
    }
    $asc = $asc ? SORT_ASC : SORT_DESC;
    array_multisort($colarr, $asc, $arrlist);
    return $arrlist;
}
// 对数组进行查找，排序，筛选，支持多种条件排序
function arrlist_cond_orderby($arrlist, $cond = array(), $orderby = array(), $page = 1, $pagesize = 20)
{
    $resultarr = array();
    if (empty($arrlist)) return $arrlist;
    // 根据条件，筛选结果
    if ($cond) {
        foreach ($arrlist as $key => $val) {
            $ok = TRUE;
            foreach ($cond as $k => $v) {
                if (!isset($val[$k])) {
                    $ok = FALSE;
                    break;
                }
                if (!is_array($v)) {
                    if ($val[$k] != $v) {
                        $ok = FALSE;
                        break;
                    }
                } else {
                    foreach ($v as $k3 => $v3) {
                        if (
                            ($k3 == '>' && $val[$k] <= $v3) ||
                            ($k3 == '<' && $val[$k] >= $v3) ||
                            ($k3 == '>=' && $val[$k] < $v3) ||
                            ($k3 == '<=' && $val[$k] > $v3) ||
                            ($k3 == '==' && $val[$k] != $v3) ||
                            ($k3 == 'LIKE' && stripos($val[$k], $v3) === FALSE)
                        ) {
                            $ok = FALSE;
                            break 2;
                        }
                    }
                }
            }
            if ($ok) $resultarr[$key] = $val;
        }
    } else {
        $resultarr = $arrlist;
    }
    if ($orderby) {
        // php 7.2 deprecated each()
        //list($k, $v) = each($orderby);
        $k = key($orderby);
        $v = current($orderby);
        $resultarr = arrlist_multisort($resultarr, $k, $v == 1);
    }
    $start = ($page - 1) * $pagesize;
    $resultarr = array_assoc_slice($resultarr, $start, $pagesize);
    return $resultarr;
}
// 取一维或二维数组指定数量的数据 并按之前排序
function array_assoc_slice($arrlist, $start, $length = 0)
{
    if (isset($arrlist[0])) return array_slice($arrlist, $start, $length);
    $keys = array_keys($arrlist);
    $keys2 = array_slice($keys, $start, $length);
    $retlist = array();
    foreach ($keys2 as $key) {
        $retlist[$key] = $arrlist[$key];
    }
    return $retlist;
}
// 从一个二维数组中取出一个 key=>value 格式的一维数组
function arrlist_key_values($arrlist, $key, $value = NULL, $pre = '')
{
    $return = array();
    if ($key) {
        foreach ((array)$arrlist as $k => $arr) {
            $return[$pre . $arr[$key]] = $value ? $arr[$value] : $k;
        }
    } else {
        foreach ((array)$arrlist as $arr) {
            $return[] = $arr[$value];
        }
    }
    return $return;
}
/* php 5.5:
function array_column($arrlist, $key) {
	return arrlist_values($arrlist, $key);
}
*/
// @从一个二维数组中取出一个 values() 格式的一维数组，某一列key，$index_key数组的索引或键的列
function arrlist_values($arrlist, $key, $index_key = NULL)
{
    if (!$arrlist) return array();
    if (version_compare(PHP_VERSION, '5.5', '<')) {
        $return = array();
        foreach ($arrlist as &$arr) {
            $return[] = $arr[$key];
        }
    } else {
        $return = array_column($arrlist, $key, $index_key);
    }
    return $return;
}
// 从一个二维数组中对某一列求和
function arrlist_sum($arrlist, $key)
{
    if (!$arrlist) return 0;
    $n = 0;
    foreach ($arrlist as &$arr) {
        $n += $arr[$key];
    }
    return $n;
}
// 从一个二维数组中对某一列求最大值
function arrlist_max($arrlist, $key)
{
    if (!$arrlist) return 0;
    $first = array_pop($arrlist);
    $max = $first[$key];
    foreach ($arrlist as &$arr) {
        if ($arr[$key] > $max) {
            $max = $arr[$key];
        }
    }
    return $max;
}
// 从一个二维数组中对某一列求最大值
function arrlist_min($arrlist, $key)
{
    if (!$arrlist) return 0;
    $first = array_pop($arrlist);
    $min = $first[$key];
    foreach ($arrlist as &$arr) {
        if ($min > $arr[$key]) {
            $min = $arr[$key];
        }
    }
    return $min;
}
// 将 key 更换为某一列的值，在对多维数组排序后，数字key会丢失，需要此函数
function arrlist_change_key($arrlist, $key = '', $pre = '')
{
    $return = array();
    if (empty($arrlist)) return $return;
    foreach ($arrlist as &$arr) {
        if (empty($key)) {
            $return[] = $arr;
        } else {
            $return[$pre . '' . $arr[$key]] = $arr;
        }
    }
    //$arrlist = $return;
    return $return;
}
// 保留指定的 key
function arrlist_keep_keys($arrlist, $keys = array())
{
    !is_array($keys) AND $keys = array($keys);
    foreach ($arrlist as &$v) {
        $arr = array();
        foreach ($keys as $key) {
            $arr[$key] = isset($v[$key]) ? $v[$key] : NULL;
        }
        $v = $arr;
    }
    return $arrlist;
}
// 根据某一列的值进行 chunk
function arrlist_chunk($arrlist, $key)
{
    $r = array();
    if (empty($arrlist)) return $r;
    foreach ($arrlist as &$arr) {
        !isset($r[$arr[$key]]) AND $r[$arr[$key]] = array();
        $r[$arr[$key]][] = $arr;
    }
    return $r;
}
function array_multisort_key($arrlist, $col, $asc = TRUE, $key = NULL)
{
    if (empty($arrlist)) return array();
    $colarr = array();
    foreach ($arrlist as $k => $v) {
        if (!isset($v[$col])) continue;
        $colarr[$k] = $v[$col];
    }
    if (empty($colarr)) return $arrlist;
    $asc = $asc ? SORT_ASC : SORT_DESC;
    array_multisort($colarr, $asc, $arrlist);
    unset($colarr);
    $key AND $arrlist = array_change_key($arrlist, $key);
    return $arrlist;
}
// 更改二维数组key
function array_change_key($arrlist, $key = NULL)
{
    if (empty($arrlist) || empty($key)) return $arrlist;
    $arr = array();
    foreach ($arrlist as $k => $v) {
        $arr[$v[$key]] = $v;
    }
    return $arr;
}
// 二维数组分页，对排序的整个数组分页获取数据
function array_pagination($arrlist, $page = 1, $pagesize = 20)
{
    if (empty($arrlist)) return array();
    $page = intval($page);
    $pagesize = intval($pagesize);
    // 输出开始位置 第二页开始 +1
    $start = ($page - 1) * $pagesize + ($page > 1 ? 1 : 0);
    // 输出结束位置 当前页数*每页数量
    $end = $page * $pagesize;
    $arr = array();
    $i = 0;
    foreach ($arrlist as $key => $val) {
        ++$i;
        if ($i >= $start && $i <= $end) {
            $arr[$key] = $val;
        }
    }
    return $arr;
}
// 倒叙 二维关联数组整理一维关联数组 col排序列 关联key=>value
function array_rank_key($arr = array(), $col = NULL, $key = NULL, $value = NULL)
{
    if (!empty($arr) && $col && $key && $value) {
        $arr = arrlist_multisort($arr, $col, FALSE);
        $arr = arrlist_key_values($arr, $key, $value);
    }
    return $arr;
}
// 移除二维数组中的重复的值，并返回结果数组。
function unique_array($array2D, $stkeep = FALSE, $ndformat = TRUE)
{
    // 判断是否保留一级数组键 (一级数组键可以为非数字)
    $starr = $stkeep ? array_keys($array2D) : array();
    // 判断是否保留二级数组键 (所有二级数组键必须相同)
    $ndarr = $ndformat ? array_keys(end($array2D)) : array();
    // 降维,也可以用implode,将一维数组转换为用逗号连接的字符串
    $temp = array();
    foreach ($array2D as $v) {
        $v = implode(",", $v);
        $temp[] = $v;
    }
    // 去掉重复的字符串,也就是重复的一维数组
    $temp = array_unique($temp);
    // 再将拆开的数组重新组装
    $output = array();
    foreach ($temp as $k => $v) {
        if ($stkeep) $k = $starr[$k];
        if ($ndformat) {
            $temparr = explode(",", $v);
            foreach ($temparr as $ndkey => $ndval) $output[$k][$ndarr[$ndkey]] = $ndval;
        } else $output[$k] = explode(",", $v);
    }
    return $output;
}
// 合并二维数组 如重复 值以第一个数组值为准
function array2_merge($array1, $array2, $key = '')
{
    if (empty($array1) || empty($array2)) return NULL;
    $arr = array();
    foreach ($array1 as $k => $v) {
        isset($v[$key]) ? $arr[$v[$key]] = array_merge($v, $array2[$k]) : $arr[] = array_merge($v, $array2[$k]);
    }
    return $arr;
}
/*
 * 对二维数组排序 两个数组必须有一个相同的键值
 * $array1 需要排序数组
 * $array2 按照该数组key排序
 * */
function array2_sort_key($array1, $array2, $key = '')
{
    if (empty($array1) || empty($array2)) return NULL;
    $arr = array();
    foreach ($array2 as $k => $v) {
        if (isset($v[$key]) && $v[$key] == $array1[$v[$key]][$key]) {
            $arr[$v[$key]] = $array1[$v[$key]];
        } else {
            $arr[] = $v;
        }
    }
    return $arr;
}
function array_reindex_by($array, $key_column, $value_column) {
    $result = [];
    foreach ($array as $item) {
        if (isset($item[$key_column]) && isset($item[$value_column])) {
            $result[$item[$key_column]] = [$value_column => $item[$value_column]];
        }
    }
    return $result;
}
?>