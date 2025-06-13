<?php
/**
 * 查询图集是否有图片
 * 
 * 调用方式: {dede:albumhaspics/}
 * 
 * 参数说明:
 * aid      图集ID
 * return='yes|no'  返回格式，默认根据是否有图返回yes或no
 * 
 * 示例：
 * {dede:albumhaspics aid='[field:id/]'/}  返回yes或no
 * {dede:albumhaspics aid='[field:id/]' return='0|1'/}  返回1或0
 *
 */
if(!defined('DEDEINC')) exit('Request Error!');

function lib_albumhaspics(&$ctag, &$refObj)
{
    global $dsql;
    
    //属性处理
    $attlist = "aid|0,return|yes|no";
    FillAttsDefault($ctag->CAttribute->Items, $attlist);
    extract($ctag->CAttribute->Items, EXTR_SKIP);
    
    // 自动获取当前文档ID
    if(empty($aid) || intval($aid) == 0) {
        if (is_object($refObj) && isset($refObj->Fields['id'])) {
            $aid = $refObj->Fields['id'];
        }
    }
    if(empty($aid) || intval($aid) == 0) return '';
    
    $row = $dsql->GetOne("SELECT has_pics FROM `#@__addonimages` WHERE aid='$aid' LIMIT 0,1");
    
    if(is_array($row)) {
        if($row['has_pics'] == 1) {
            if($return == 'yes|no') {
                return 'yes';
            } else {
                return '1';
            }
        } else {
            if($return == 'yes|no') {
                return 'no';
            } else {
                return '0';
            }
        }
    } else {
        if($return == 'yes|no') {
            return 'no';
        } else {
            return '0';
        }
    }
} 