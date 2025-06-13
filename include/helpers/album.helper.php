<?php
/**
 * 图集处理辅助函数
 *
 * @version        $Id: album.helper.php 1 2024-05-28 $
 * @package        DedeCMS.Helpers
 * @copyright      Copyright (c) 2004 - 2024, 上海卓卓网络科技有限公司 (DesDev, Inc.)
 * @license        http://help.dedecms.com/usersguide/license.html
 * @link           http://www.dedecms.com
 */

if(!defined('DEDEINC')) exit('Request Error!');

/**
 * 判断图集是否包含图片
 *
 * @param     int     $aid     文档ID
 * @return    bool
 */
if ( ! function_exists('HasAlbumPics'))
{
    function HasAlbumPics($aid)
    {
        global $dsql;
        $row = $dsql->GetOne("SELECT has_pics FROM `#@__addonimages` WHERE aid='$aid' LIMIT 0,1");
        if(is_array($row) && $row['has_pics'] == 1) {
            return true;
        } else {
            return false;
        }
    }
}

/**
 * 获取图集的图片数量
 *
 * @param     int     $aid     文档ID
 * @return    int
 */
if ( ! function_exists('GetAlbumPicCount'))
{
    function GetAlbumPicCount($aid)
    {
        global $dsql;
        $row = $dsql->GetOne("SELECT imgurls FROM `#@__addonimages` WHERE aid='$aid' LIMIT 0,1");
        if(!is_array($row)) {
            return 0;
        }
        $imgurls = $row['imgurls'];
        $matches = array();
        preg_match_all('/\{dede:img/i', $imgurls, $matches);
        return count($matches[0]);
    }
} 