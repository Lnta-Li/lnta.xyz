<?php
/**
 *
 * Ajax评论
 *
 * @version        $Id: feedback_ajax.php 1 15:38 2010年7月8日 $
 * @package        DedeCMS.Site
 * @founder        IT柏拉图, https://weibo.com/itprato
 * @author         DedeCMS团队
 * @copyright      Copyright (c) 2004 - 2024, 上海卓卓网络科技有限公司 (DesDev, Inc.)
 * @license        http://help.dedecms.com/usersguide/license.html
 * @link           http://www.dedecms.com
 */
require_once(dirname(__FILE__).'/../include/common.inc.php');
require_once(DEDEINC.'/channelunit.func.php');
AjaxHead();

if($cfg_feedback_forbid=='Y') exit('系统已经禁止评论功能！');

$aid = intval($aid);
if(empty($aid)) exit('没指定评论文档的ID，不能进行操作！');

include_once(DEDEINC.'/memberlogin.class.php');
$cfg_ml = new MemberLogin();

if(empty($dopost)) $dopost = '';
$page = empty($page) || $page<1 ? 1 : intval($page);
$pagesize = 20;

if($dopost=='getlist')
{
    $totalcount = GetList($page);
    GetPageList($pagesize, $totalcount);
    exit();
}
else if($dopost=='send')
{
    require_once(DEDEINC.'/charset.func.php');
    
    // 已移除验证码检查
    
    $arcRow = GetOneArchive($aid);
    if(empty($arcRow['aid']))
    {
        echo '<font color="red">无法查看未知文档的评论!</font>';
        exit();
    }
    if(isset($arcRow['notpost']) && $arcRow['notpost']==1)
    {
        echo '<font color="red">这篇文档禁止评论!</font>';
        exit();
    }
    
    if($cfg_soft_lang != 'utf8')
    {
        $msg = UnicodeUrl2Gbk($msg);
        if(!empty($username)) $username = UnicodeUrl2Gbk($username);
    }

    if($cfg_notallowstr != '')
    {
        if(preg_match("#".$cfg_notallowstr."#i", $msg))
        {
            echo "<font color='red'>评论内容含有禁用词汇！</font>";
            exit();
        }
    }
    if($cfg_replacestr != '')
    {
        $msg = preg_replace("#".$cfg_replacestr."#i", '***', $msg);
    }
    if(empty($msg))
    {
        echo "<font color='red'>评论内容可能不合法或为空！</font>";
        exit();
    }
    if($cfg_feedback_guest == 'N' && $cfg_ml->M_ID < 1)
    {
        echo "<font color='red'>管理员禁用了游客评论！<a href='{$cfg_cmspath}/member/login.php'>点击登录</a></font>";
        exit();
    }

    $username = empty($username) ? '游客' : $username;
    if(empty($notuser)) $notuser = 0;
    if($notuser==1)
    {
        $username = $cfg_ml->M_ID > 0 ? '匿名' : '游客';
    }
    else if($cfg_ml->M_ID > 0)
    {
        $username = $cfg_ml->M_UserName;
    }
    else if($username!='' && $pwd!='')
    {
        $rs = $cfg_ml->CheckUser($username, $pwd);
        if($rs==1)
        {
            $dsql->ExecuteNoneQuery("Update `#@__member` set logintime='".time()."',loginip='".GetIP()."' where mid='{$cfg_ml->M_ID}'; ");
        }
        $cfg_ml = new MemberLogin();
    }
    
    $ip = GetIP();
    $dtime = time();
    if(!empty($cfg_feedback_time))
    {
        $where = ($cfg_ml->M_ID > 0 ? "WHERE `mid` = '$cfg_ml->M_ID' " : "WHERE `ip` = '$ip' ");
        $row = $dsql->GetOne("SELECT dtime FROM `#@__feedback` $where ORDER BY `id` DESC ");
        if(is_array($row) && $dtime - $row['dtime'] < $cfg_feedback_time)
        {
            ResetVdValue();
            echo '<font color="red">管理员设置了评论间隔时间，请稍等休息一下！</font>';
            exit();
        }
    }

    $msg = cn_substrR(TrimMsg($msg), 500);
    $username = cn_substrR(HtmlReplace($username,2), 20);
    $ischeck = ($cfg_feedbackcheck=='Y' ? 0 : 1);
    $arctitle = addslashes(RemoveXSS($title));
    $typeid = intval($typeid);

    $inquery = "INSERT INTO `#@__feedback`(`aid`,`typeid`,`username`,`arctitle`,`ip`,`ischeck`,`dtime`,`mid`,`msg`) 
               VALUES ('$aid','$typeid','$username','$arctitle','$ip','$ischeck','$dtime','{$cfg_ml->M_ID}','$msg');";
    $rs = $dsql->ExecuteNoneQuery($inquery);
    if(!$rs)
    {
        echo "<font color='red'>发表评论出错了！</font>";
        exit();
    }

    if($cfg_ml->M_ID > 0)
    {
        $dsql->ExecuteNoneQuery("UPDATE `#@__member` set scores=scores+{$cfg_sendfb_scores} WHERE mid='{$cfg_ml->M_ID}' ");
        $row = $dsql->GetOne("SELECT COUNT(*) AS nums FROM `#@__feedback` WHERE `mid`='{$cfg_ml->M_ID}'");
        $dsql->ExecuteNoneQuery("UPDATE `#@__member_tj` SET `feedback`='$row[nums]' WHERE `mid`='{$cfg_ml->M_ID}'");
    }

    $_SESSION['sedtime'] = time();
    if($ischeck==0)
    {
        echo '<font color="red">成功发表评论，但需审核后才会显示你的评论!</font>';
        exit();
    }
    else
    {
        $newid = $dsql->GetLastID();
        $spaceurl = '#';
        if($cfg_ml->M_ID > 0) $spaceurl = "{$cfg_memberurl}/index.php?uid=".urlencode($cfg_ml->M_LoginID);

        $msg = stripslashes($msg);
        $msg = str_replace('<', '&lt;', $msg);
        $msg = str_replace('>', '&gt;', $msg);
        helper('smiley');
        $msg = RemoveXSS(Quote_replace(parseSmileys($msg, $cfg_cmspath.'/images/smiley')));

        if($cfg_ml->M_ID==""){
            $mface=$cfg_cmspath."/member/templets/images/dfboy.png";
        } else {
            $row = $dsql->GetOne("SELECT face,sex FROM `#@__member` WHERE mid={$cfg_ml->M_ID} ");
            if(empty($row['face']))
            {
                if($row['sex']=="女") $mface=$cfg_cmspath."/member/templets/images/dfgirl.png";
                else $mface=$cfg_cmspath."/member/templets/images/dfboy.png";
            }
        }
?>
<div class="decmt-box2" style="margin-right: 16px;">
<img src='<?php echo $mface;?>' height='40' width='40'/>
<div class="content">
<span class="fl"><?php echo $username; ?> · <?php echo GetDateMk($dtime); ?></span>
<div class="text">刚刚：<?php echo ubb($msg); ?></div>
</div>
</div>


<?php
    }
    exit();
}

function GetList($page=1)
{
    global $dsql, $aid, $pagesize, $cfg_templeturl, $cfg_cmspath;
    $querystring = "SELECT fb.*,mb.userid,mb.face as mface,mb.sex FROM `#@__feedback` fb
                 LEFT JOIN `#@__member` mb on mb.mid = fb.mid WHERE fb.aid='$aid' AND fb.ischeck='1' ORDER BY fb.id DESC";
    $row = $dsql->GetOne("SELECT COUNT(*) AS dd FROM `#@__feedback` WHERE aid='$aid' AND ischeck='1' ");
    $totalcount = (empty($row['dd']) ? 0 : $row['dd']);
    
    // 添加无评论时的提示信息
    if($totalcount == 0) {
        echo '<div class="decmt-box2 no-comments">
            <div class="content">
                <div class="text">期待与您交流  Anticipating discourse with you</div>
            </div>
        </div>';
        return 0;
    }
    
    $startNum = $pagesize * ($page-1);
    if($startNum > $totalcount)
    {
        echo "参数错误！";
        return $totalcount;
    }
    $dsql->Execute('fb', $querystring." LIMIT $startNum, $pagesize ");
    while($fields = $dsql->GetArray('fb'))
    {
        if($fields['userid']!='') $spaceurl = $GLOBALS['cfg_memberurl'].'/index.php?uid='.$fields['userid'];
        else $spaceurl = '#';
        if($fields['username']=='匿名') $spaceurl = '#';

        if(empty($fields['mface']))
        {
            if($fields['sex']=="女") $fields['mface']=$cfg_cmspath."/member/templets/images/dfgirl.png";
            else $fields['mface']=$cfg_cmspath."/member/templets/images/dfboy.png";
        }

        $fields['msg'] = str_replace('<', '&lt;', $fields['msg']);
        $fields['msg'] = str_replace('>', '&gt;', $fields['msg']);
        helper('smiley');
        $fields['msg'] = RemoveXSS(Quote_replace(parseSmileys($fields['msg'], $cfg_cmspath.'/images/smiley')));
        extract($fields, EXTR_OVERWRITE);
?>
<div class="decmt-box2">
<img src='<?php echo $mface;?>' height='40' width='40'/>
<div class="content">
<span class="fl"><?php echo $username; ?> · <?php echo GetDateMk($dtime); ?></span>
<div class="text"><?php echo ubb($msg); ?></div>
</div>
</div>
<?php
    }
    return $totalcount;            
}

function GetPageList($pagesize, $totalcount)
{
    global $page;
    $curpage = empty($page) ? 1 : intval($page);
    $allpage = ceil($totalcount / $pagesize);
    if($allpage < 2) 
    {
        echo '';
        return;
    }
    echo "<div id='commetpages'>";
    echo "<span>Total: {$allpage} Page/{$totalcount} Item</span> ";
    $listsize = 5;
    $total_list = $listsize * 2 + 1;
    $totalpage = $allpage;
    if($curpage-1 > 0)
    {
        echo "<a href='#commettop' onclick='LoadCommets(".($curpage-1).");'>Pre</a> ";
    }
    if($curpage >= $total_list)
    {
        $j = $curpage - $listsize;
        $total_list = $curpage + $listsize;
        if($total_list > $totalpage)
        {
            $total_list = $totalpage;
        }
    }
    else
    {
        $j = 1;
        if($total_list > $totalpage) $total_list = $totalpage;
    }
    for($j; $j <= $total_list; $j++)
    {
        echo ($j==$curpage ? "<strong>$j</strong> " : "<a href='#commettop' onclick='LoadCommets($j);'>{$j}</a> ");
    }
    if($curpage+1 <= $totalpage)
    {
        echo "<a href='#commettop' onclick='LoadCommets(".($curpage+1).");'>Next</a> ";
    }
    echo "</div>";
}