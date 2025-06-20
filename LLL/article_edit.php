<?php
/**
 * 文档编辑
 *
 * @version        $Id: article_edit.php 1 14:12 2010年7月12日 $
 * @package        DedeCMS.Administrator
 * @founder        IT柏拉图, https://weibo.com/itprato
 * @author         DedeCMS团队
 * @copyright      Copyright (c) 2004 - 2024, 上海卓卓网络科技有限公司 (DesDev, Inc.)
 * @license        http://help.dedecms.com/usersguide/license.html
 * @link           http://www.dedecms.com
 */
require_once(dirname(__FILE__)."/config.php");
CheckPurview('a_Edit,a_AccEdit,a_MyEdit');
require_once(DEDEINC."/customfields.func.php");
require_once(DEDEADMIN."/inc/inc_archives_functions.php");
if(file_exists(DEDEDATA.'/template.rand.php'))
{
    require_once(DEDEDATA.'/template.rand.php');
}
if(empty($dopost)) $dopost = '';


$aid = isset($aid) && is_numeric($aid) ? $aid : 0;
if($dopost!='save')
{
    require_once(DEDEADMIN."/inc/inc_catalog_options.php");
    require_once(DEDEINC."/dedetag.class.php");
    ClearMyAddon();

    //读取归档信息
    $query = "SELECT ch.typename AS channelname,ar.membername AS rankname,arc.*
    FROM `#@__archives` arc
    LEFT JOIN `#@__channeltype` ch ON ch.id=arc.channel
    LEFT JOIN `#@__arcrank` ar ON ar.rank=arc.arcrank WHERE arc.id='$aid' ";
    $arcRow = $dsql->GetOne($query);
    if(!is_array($arcRow))
    {
        ShowMsg("读取档案基本信息出错!","-1");
        exit();
    }
    $query = "SELECT * FROM `#@__channeltype` WHERE id='".$arcRow['channel']."'";
    $cInfos = $dsql->GetOne($query);
    if(!is_array($cInfos))
    {
        ShowMsg("读取频道配置信息出错!","javascript:;");
        exit();
    }
    $addtable = $cInfos['addtable'];
    $addRow = $dsql->GetOne("SELECT * FROM `$addtable` WHERE aid='$aid'");
    if(!is_array($addRow))
    {
        ShowMsg("读取附加信息出错!","javascript:;");
        exit();
    }
    $channelid = $arcRow['channel'];
    $tags = GetTags($aid);

    // 读取审核意见
    $row = $dsql->GetOne("SELECT * FROM `#@__archives_log_detail` WHERE `archives_id` = '{$aid}' AND `remark` != '' ORDER BY `id` DESC");
    $remark = $row['remark'];

    include DedeInclude("templets/article_edit.htm");
    exit();
}
/*--------------------------------
function __save(){  }
-------------------------------*/
else if($dopost=='save')
{
    require_once(DEDEINC.'/image.func.php');
    require_once(DEDEINC.'/oxwindow.class.php');
    $flag = isset($flags) ? join(',',$flags) : '';
    $notpost = isset($notpost) && $notpost == 1 ? 1: 0;
    
    if(empty($typeid2)) $typeid2 = 0;
    if(!isset($autokey)) $autokey = 0;
    if(!isset($remote)) $remote = 0;
    if(!isset($dellink)) $dellink = 0;
    if(!isset($autolitpic)) $autolitpic = 0;
    
    if(empty($typeid))
    {
        ShowMsg("请指定文档的栏目！", "-1");
        exit();
    }
    if(empty($channelid))
    {
        ShowMsg("文档为非指定的类型，请检查你发布内容的表单是否合法！", "-1");
        exit();
    }
    if(!CheckChannel($typeid, $channelid))
    {
        ShowMsg("你所选择的栏目与当前模型不相符，请选择白色的选项！", "-1");
        exit();
    }
    if(!TestPurview('a_Edit'))
    {
        if(TestPurview('a_AccEdit'))
        {
            CheckCatalog($typeid, "对不起，你没有操作栏目 {$typeid} 的文档权限！");
        }
        else
        {
            CheckArcAdmin($id, $cuserLogin->getUserID());
        }
    }

    $id = intval($id);
    $typeid = intval($typeid);
    $typeid2 = dede_htmlspecialchars($typeid2);
    $sortrank = intval($sortrank);
    $flag = dede_htmlspecialchars($flag);
    $click = intval($click);
    $ismake = intval($ismake);
    $arcrank = intval($arcrank);
    $money = intval($money);
    $title = preg_replace("#\"#", '＂', $title);
    $color = dede_htmlspecialchars($color);
    $writer = dede_htmlspecialchars($writer);
    $source = dede_htmlspecialchars($source);
    $litpic = dede_htmlspecialchars($litpic);
    $pubdate = dede_htmlspecialchars($pubdate);
    $voteid = intval($voteid);
    $notpost = intval($notpost);
    $description = dede_htmlspecialchars($description);
    $keywords = dede_htmlspecialchars($keywords);
    $shorttitle = dede_htmlspecialchars($shorttitle);
    $filename = dede_htmlspecialchars($filename);
    $adminid = intval($adminid);
    $weight = intval($weight);

    //对保存的内容进行处理
    $pubdate = GetMkTime($pubdate);
    $sortrank = AddDay($pubdate,$sortup);
    $ismake = $ishtml==0 ? -1 : 0;
    $autokey = 1;
    $title = dede_htmlspecialchars(cn_substrR($title,$cfg_title_maxlen));
    $shorttitle = cn_substrR($shorttitle,36);
    $color =  cn_substrR($color,7);
    $writer =  cn_substrR($writer,20);
    $source = cn_substrR($source,30);
    $description = cn_substrR($description,250);
    $keywords = trim(cn_substrR($keywords,60));
    $filename = trim(cn_substrR($filename,40));
    $isremote  = (empty($isremote)? 0  : $isremote);
    $serviterm=empty($serviterm)? "" : $serviterm;
    if(!TestPurview('a_Check,a_AccCheck,a_MyCheck'))
    {
        $arcrank = -1;
    }
    $adminid = $cuserLogin->getUserID();

    //处理上传的缩略图
    if(empty($ddisremote))
    {
        $ddisremote = 0;
    }
    $litpic = GetDDImage('none',$picname,$ddisremote);

    //分析body里的内容
    $body = AnalyseHtmlBody($body,$description,$litpic,$keywords,'htmltext');

    //分析处理附加表数据
    $inadd_f = '';
    $inadd_v = '';
    if(!empty($dede_addonfields))
    {
        $addonfields = explode(';',$dede_addonfields);
        $inadd_f = '';
        $inadd_v = '';
        if(is_array($addonfields))
        {
            foreach($addonfields as $v)
            {
                if($v=='')
                {
                    continue;
                }
                $vs = explode(',',$v);
                if($vs[1]=='htmltext'||$vs[1]=='textdata') //HTML文本特殊处理
                {
                    ${$vs[0]} = AnalyseHtmlBody(${$vs[0]},$description,$litpic,$keywords,$vs[1]);
                }else
                {
                    if(!isset(${$vs[0]}))
                    {
                        ${$vs[0]} = '';
                    }
                    ${$vs[0]} = GetFieldValueA(${$vs[0]},$vs[1],$id);
                }
                $inadd_f .= ",`{$vs[0]}` = '".${$vs[0]}."'";
            }
        }
    }

    //处理图片文档的自定义属性
    if($litpic!='' && !preg_match("#p#", $flag))
    {
        $flag = ($flag=='' ? 'p' : $flag.',p');
    }
    if($redirecturl!='' && !preg_match("#j#", $flag))
    {
        $flag = ($flag=='' ? 'j' : $flag.',j');
    }

    //跳转网址的文档强制为动态
    if(preg_match("#j#", $flag)) $ismake = -1;
    //处理隐藏缩略图选项
    $hide_thumb = isset($hide_thumb) ? 1 : 0;
    
    //处理小图模式选项  
    $small_img = isset($small_img) ? 1 : 0;

    //生成小尺寸缩略图
    $subpic = '';
    if(!empty($picname) && isset($make_subpic) && $make_subpic==1) {
        require_once(DEDEINC.'/extend.func.php');
        $subpic = createSubPic($picname, 0, 0, $id);
    }

    //更新数据库的SQL语句
    $query = "UPDATE `#@__archives` SET
    `typeid` = '{$typeid}',
    `typeid2` = '{$typeid2}',
    `sortrank` = '{$sortrank}',
    `flag` = '{$flag}',
    `click` = '{$click}',
    `ismake` = '{$ismake}',
    `arcrank` = '{$arcrank}',
    `money` = '{$money}',
    `title` = '{$title}',
    `color` = '{$color}',
    `writer` = '{$writer}',
    `source` = '{$source}',
    `litpic` = '{$litpic}',
    `pubdate` = '{$pubdate}',
    `voteid` = '{$voteid}',
    `notpost` = '{$notpost}',
    `description` = '{$description}',
    `keywords` = '{$keywords}',
    `shorttitle` = '{$shorttitle}',
    `filename` = '{$filename}',
    `dutyadmin` = '{$adminid}',
    `weight` = '{$weight}',
    `hide_thumb` = '{$hide_thumb}',
    `small_img` = '{$small_img}',
    `subpic` = '{$subpic}'
    WHERE `id` = '{$id}';";

    if(!$dsql->ExecuteNoneQuery($query))
    {
        ShowMsg('更新数据库archives表时出错，请检查',-1);
        exit();
    }
    
    $cts = $dsql->GetOne("SELECT addtable FROM `#@__channeltype` WHERE id='$channelid' ");
    $addtable = trim($cts['addtable']);
    if($addtable!='')
    {
        $useip = GetIP();
        $templet = empty($templet) ? '' : $templet;
        $iquery = "UPDATE `$addtable` SET typeid='$typeid',body='$body'{$inadd_f},redirecturl='$redirecturl',templet='$templet',userip='$useip',subpic='$subpic' WHERE aid='$id'";
        if(!$dsql->ExecuteNoneQuery($iquery))
        {
            ShowMsg("更新附加表 `$addtable`  时出错，请检查原因！","javascript:;");
            exit();
        }
    }

    // 文档日志
    if ($cfg_archives_log == 'Y') {
        $archives_id = $id;
        if (!TestPurview('a_Check,a_AccCheck,a_MyCheck')) {
            $type = "修改文档";
        } else if ($arcrank == "0") {
            $type = "审核通过";
        } else if ($remark != "") {
            $type = "审核文档";
        } else {
            $type = "修改文档";
        }
        $admin_id = $cuserLogin->getUserID();
        $ip = GetIP();
        $time = time();
        $dsql->ExecuteNoneQuery("UPDATE `#@__archives_log_list` SET `title` = '{$title}' WHERE `archives_id` = '{$archives_id}'");
        $dsql->ExecuteNoneQuery("INSERT INTO `#@__archives_log_detail` (`archives_id`, `title`, `body`, `remark`, `type`, `arcrank`, `admin_id`, `ip`, `time`)
        VALUES ('{$archives_id}', '{$title}', '{$body}', '{$remark}', '{$type}', '{$arcrank}', '{$admin_id}', '{$ip}', '{$time}')");
    }

    //生成HTML
    UpIndexKey($id, $arcrank, $typeid, $sortrank, $tags);
    if($cfg_remote_site=='Y' && $isremote=="1")
    {    
        if($serviterm!=""){
            list($servurl, $servuser, $servpwd) = explode(',', $serviterm);
            $config=array( 'hostname' => $servurl, 'username' => $servuser, 
                                           'password' => $servpwd,'debug' => 'TRUE');
        } else {
            $config=array();
        }
        if(!$ftp->connect($config)) exit('Error:None FTP Connection!');
    }
    $artUrl = MakeArt($id,true,true,$isremote);
    if($artUrl=='')
    {
        $artUrl = $cfg_phpurl."/view.php?aid=$id";
    }
    ClearMyAddon($id, $title);
    
    //返回成功信息
    $msg = "
    　　请选择你的后续操作：
    <a href='article_add.php?cid=$typeid'><u>发布新文章</u></a>
    &nbsp;&nbsp;
    <a href='archives_do.php?aid=".$id."&dopost=editArchives'><u>查看更改</u></a>
    &nbsp;&nbsp;
    <a href='$artUrl' target='_blank'><u>查看文章</u></a>
    &nbsp;&nbsp;
    <a href='catalog_do.php?cid=$typeid&dopost=listArchives'><u>管理文章</u></a>
    &nbsp;&nbsp;
    $backurl
    ";

    $wintitle = "成功更改文章！";
    $wecome_info = "文章管理::更改文章";
    $win = new OxWindow();
    $win->AddTitle("成功更改文章：");
    $win->AddMsgItem($msg);
    $winform = $win->GetWindow("hand","&nbsp;",false);
    $win->Display();
}