<?php   if(!defined('DEDEINC')) exit('DedeCMS Error: Request Error!');
/**
 * 主题模式标签
 *
 * @version        $Id: theme_mode.lib.php 1 9:29 2023年10月10日 $
 * @package        DedeCMS.Taglib
 * @founder        IT柏拉图, https://weibo.com/itprato
 * @author         DedeCMS团队
 * @copyright      Copyright (c) 2004 - 2024, 上海卓卓网络科技有限公司 (DesDev, Inc.)
 * @license        http://help.dedecms.com/usersguide/license.html
 * @link           http://www.dedecms.com
 */
 
/*>>dede>>
<n>主题模式</n>
<type>全局标记</type>
<for>V55,V56,V57</for>
<description>获取当前内容的主题模式设置（0为浅色模式，1为深色模式，3为默认背景模式）</description>
<demo>
{dede:theme_mode/}
</demo>
<attributes>
</attributes> 
>>dede>>*/
 
function lib_theme_mode(&$ctag, &$refObj)
{
    global $dsql;
    
    // 如果不在内容页面，直接返回空
    if(!isset($refObj->Fields['aid']) || empty($refObj->Fields['aid'])) {
        return '';
    }
    
    $aid = $refObj->Fields['aid'];
    $channelid = $refObj->Fields['channel'];
    
    // 获取当前模型的附加表
    $row = $dsql->GetOne("SELECT addtable FROM `#@__channeltype` WHERE id='$channelid'");
    if(!$row) {
        return '';
    }
    
    $addtable = $row['addtable'];
    
    // 从附加表获取theme值
    $row = $dsql->GetOne("SELECT theme FROM `$addtable` WHERE aid='$aid'");
    if(!$row || !isset($row['theme'])) {
        return '';
    }
    
    return $row['theme'];
} 