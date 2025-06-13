<?php
/**
 * @version        $Id: tags.php 1 2010-06-30 11:43:09 $
 * @package        DedeCMS.Site
 * @founder        IT柏拉图, https://weibo.com/itprato
 * @author         DedeCMS团队
 * @copyright      Copyright (c) 2004 - 2024, 上海卓卓网络科技有限公司 (DesDev, Inc.)
 * @license        http://help.dedecms.com/usersguide/license.html
 * @link           http://www.dedecms.com
 */
require_once (dirname(__FILE__) . "/include/common.inc.php");
require_once (DEDEINC . "/arc.taglist.class.php");
$PageNo = 1;

if(isset($_SERVER['QUERY_STRING']))
{
    $tag = trim($_SERVER['QUERY_STRING']);
    $tags = explode('/', $tag);

    if(isset($tags[1])) {
        if($tags[1] === 'alias') {
            if(isset($tags[2])) $tag = $tags[2];
            if(isset($tags[3])) $PageNo = intval($tags[3]);

            global $dsql;
            $tag_alias = FilterSearch(urldecode($tag));
            $row = $dsql->GetOne("Select * From `#@__tagindex` where tag_alias = '{$tag_alias}'");
            $tag = $row['tag'];
        } else {
            if(isset($tags[1])) $tag = $tags[1];
            if(isset($tags[2])) $PageNo = intval($tags[2]);
        }
    }
}
else
{
    $tag = '';
}

$tag = FilterSearch(urldecode($tag));
if($tag != addslashes($tag)) $tag = '';
if($tag == '') {
    $dlist = new TagList($tag, 'tag.htm');
} else {
    global $dsql;
    $count = $dsql->GetOne("SELECT COUNT(*) as num FROM `#@__taglist` WHERE tag = '{$tag}'");
    $template = ($count['num'] > 8) ? 'taglist-瀑布流.htm' : 'taglist-列表页.htm';
    $dlist = new TagList($tag, $template);
}
$dlist->Display();
exit();