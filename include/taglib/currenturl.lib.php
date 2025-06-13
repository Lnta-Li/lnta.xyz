<?php if (!defined('DEDEINC')) exit('DedeCMS Error: Request Error!');
/**
 * 当前URL
 *
 * @version        $Id: currenturl.lib.php 1 9:29 2010年7月6日 $
 * @package        DedeCMS.Taglib
 * @founder        IT柏拉图, https://weibo.com/itprato
 * @author         DedeCMS团队
 * @copyright      Copyright (c) 2004 - 2024, 上海卓卓网络科技有限公司 (DesDev, Inc.)
 * @license        http://help.dedecms.com/usersguide/license.html
 * @link           http://www.dedecms.com
 */

/*>>dede>>
<name>当前URL</name>
<type>全局标记</type>
<for>V57</for>
<description>获取当前URL</description>
<demo>
{dede:currenturl /}
</demo>
>>dede>>*/

function lib_currenturl(&$ctag, &$refObj)
{
    global $dsql, $_sys_globals, $cfg_cmsurl, $cfg_basehost;
    $currenturl = '';
    $curfile = $_sys_globals['curfile'];
    $aid = $refObj->Fields['aid'];
    $tid = $refObj->Fields['id'];

    if ($curfile == 'archives' && !empty($aid)) {
        $query = "SELECT arc.*,tp.typedir,tp.typename,tp.corank,tp.isdefault,tp.defaultname,tp.namerule,tp.namerule2,tp.ispart,
            tp.moresite,tp.siteurl,tp.sitepath
            FROM `#@__archives` `arc` LEFT JOIN `#@__arctype` `tp` ON `arc`.`typeid` = `tp`.`id`
            WHERE `arc`.`id` = '{$aid}'";
        $row = $dsql->GetOne($query);
        $currenturl = GetFileUrl(
            $row['id'],
            $row['typeid'],
            $row['senddate'],
            $row['title'],
            $row['ismake'],
            $row['arcrank'],
            $row['namerule'],
            $row['typedir'],
            $row['money'],
            $row['filename'],
            $row['moresite'],
            $row['siteurl'],
            $row['sitepath']
        );
    } else if ($curfile == 'list' && !empty($tid)) {
        $query = "SELECT * FROM `#@__arctype` WHERE `id` = '{$tid}'";
        $row = $dsql->GetOne($query);
        $currenturl = GetOneTypeUrlA($row);
    } else if ($curfile == 'partview') {
        $query = "SELECT * FROM `#@__homepageset`";
        $row = $dsql->GetOne($query);
        if ($row['showmod'] == 1) {
            $currenturl = $row['position'];
            $currenturl = str_replace('../', '/', $currenturl);
            $currenturl = str_replace('./', '/', $currenturl);
        } else {
            $currenturl = '/index.php';
        }
        $currenturl = $cfg_cmsurl . $currenturl;
    }

    return $cfg_basehost . $currenturl;
}
