<?php
/**
 * 文档随机模板
 *
 * @version        $Id: article_template_rand.php 1 14:31 2010年7月12日 $
 * @package        DedeCMS.Administrator
 * @founder        IT柏拉图, https://weibo.com/itprato
 * @author         DedeCMS团队
 * @copyright      Copyright (c) 2004 - 2024, 上海卓卓网络科技有限公司 (DesDev, Inc.)
 * @license        http://help.dedecms.com/usersguide/license.html
 * @link           http://www.dedecms.com
 */
require_once(dirname(__FILE__).'/config.php');
require_once(DEDEINC.'/oxwindow.class.php');
CheckPurview('sys_StringMix');
if(empty($dopost)) $dopost = '';
$templates = empty($templates) ? '' : stripslashes($templates);
$m_file = DEDEDATA.'/template.rand.php';

//----------------------action
$okmsg = '';
//保存配置
if($dopost=='save')
{
    csrf_check();

    $fp = fopen($m_file,'w');
    flock($fp,3);
    fwrite($fp,$templates);
    fclose($fp);

    global $cfg_basehost, $cfg_cmspath;
    $content = file_get_contents($cfg_basehost.$cfg_cmspath.'/data/template.rand.php');

    // 不允许这些字符
    $content = preg_replace("#(/\*)[\s\S]*(\*/)#i", '', $content);
    
    global $cfg_disable_funs;
    $cfg_disable_funs = isset($cfg_disable_funs) ? $cfg_disable_funs : 'phpinfo,eval,assert,exec,passthru,shell_exec,system,proc_open,popen,curl_exec,curl_multi_exec,parse_ini_file,show_source,file_put_contents,fsockopen,fopen,fwrite,preg_replace';
    $cfg_disable_funs = $cfg_disable_funs.',[$]GLOBALS,[$]_GET,[$]_POST,[$]_REQUEST,[$]_FILES,[$]_COOKIE,[$]_SERVER,include,require,create_function,array_map,call_user_func,call_user_func_array,array_filert';
    foreach (explode(",", $cfg_disable_funs) as $value) {
        $value = str_replace(" ", "", $value);
        if(!empty($value) && preg_match("#[^a-z]+['\"]*{$value}['\"]*[\s]*[([{']#i", " {$content}") == TRUE) {
            $content = dede_htmlspecialchars($content);
            $fp = fopen($m_file,'w');
            fwrite($fp, '');
            fclose($fp);
            die("DedeCMS提示：当前页面中存在恶意代码！<pre>{$content}</pre>");
        }
    }

    if(preg_match("#^[\s\S]+<\?(php|=)?[\s]+#i", " {$content}") == TRUE) {
        if(preg_match("#[$][_0-9a-z]+[\s]*[(][\s\S]*[)][\s]*[;]#iU", " {$content}") == TRUE) {
            $content = dede_htmlspecialchars($content);
            $fp = fopen($m_file,'w');
            fwrite($fp, '');
            fclose($fp);
            die("DedeCMS提示：当前页面中存在恶意代码！<pre>{$content}</pre>");
        }
        if(preg_match("#[@][$][_0-9a-z]+[\s]*[(][\s\S]*[)]#iU", " {$content}") == TRUE) {
            $content = dede_htmlspecialchars($content);
            $fp = fopen($m_file,'w');
            fwrite($fp, '');
            fclose($fp);
            die("DedeCMS提示：当前页面中存在恶意代码！<pre>{$content}</pre>");
        }
        if(preg_match("#[`][\s\S]*[`]#i", " {$content}") == TRUE) {
            $content = dede_htmlspecialchars($content);
            $fp = fopen($m_file,'w');
            fwrite($fp, '');
            fclose($fp);
            die("DedeCMS提示：当前页面中存在恶意代码！<pre>{$content}</pre>");
        }
    }

    $okmsg = '成功保存配置信息 AT:('.MyDate('H:i:s', time()).')';
}
//对旧文档进行随机模板处理
else if($dopost=='makeold')
{
    csrf_check();
    set_time_limit(3600);
    if(!file_exists($m_file))
    {
        AjaxHead();
        echo "配置文件不存在！";
        exit();
    }
    require_once($m_file);
    if($cfg_tamplate_rand==0)
    {
        AjaxHead();
        echo "系统没开启允许随机模板的选项！";
        exit();
    }
    $totalTmp = count($cfg_tamplate_arr) - 1;
    if( $totalTmp < 1 )
    {
        AjaxHead();
        echo "随机模板的数量必须为2个或以上！";
        exit();
    }
    for($i=0; $i < 10; $i++)
    {
        $temp = $cfg_tamplate_arr[mt_rand(0, $totalTmp)];
        $dsql->ExecuteNoneQuery(" Update `#@__addonarticle` set templet='$temp' where RIGHT(aid, 1)='$i' ");
    }
    AjaxHead();
    echo "全部随机操作成功！";
    exit();
}
//清除全部的指定模板
else if($dopost=='clearold')
{
    csrf_check();
    $dsql->ExecuteNoneQuery(" Update `#@__addonarticle` set templet='' ");
    $dsql->ExecuteNoneQuery(" OPTIMIZE TABLE `#@__addonarticle` ");
    AjaxHead();
    echo "全部清除操作成功！";
    exit();
}

//-------------------------read
//读出
if(empty($templates) && filesize($m_file)>0)
{
    $fp = fopen($m_file,'r');
    $templates = fread($fp,filesize($m_file));
    fclose($fp);
}
$wintitle = "随机模板防采集设置";
$wecome_info = "随机模板防采集设置";
make_hash();
$msg = "
<link href='images/base.css' rel='stylesheet' type='text/css' />
<script language='javascript' src='js/main.js'></script>
<script language='javascript' src='../include/dedeajax2.js'></script>
<script language='javascript'>
function DoRand(jobname)
{
    ChangeFullDiv('show');
    \$DE('loading').style.display = 'block';
    var myajax = new DedeAjax(\$DE('tmpct'));
    myajax.SendGet2('article_template_rand.php?dopost='+jobname+'&token={$_SESSION['token']}');
    \$DE('loading').style.display = 'none';
    ChangeFullDiv('hide');
}
</script>
<div id='loading' style='z-index:3000;top:160;left:300;position:absolute;display:none;'>
    <img src='images/loadinglit.gif' /> 请稍后，正在操作中...
</div>
<table width='98%' align='center'>
<tr>
    <td height='28'>
    如果你想对旧的文章应用随机模板设置，请点击此对旧文章进行处理(必须设置好模板项)！
    &nbsp; <a href='#' onclick='DoRand(\"makeold\")'>[<u>设置全部</u>]</a>
    &nbsp; <a href='#' onclick='DoRand(\"clearold\")'>[<u>取消全部</u>]</a>
    &nbsp; <span id='tmpct' style='color:red;font-weight:bold'>$okmsg</span>
    </td>
</tr>
<tr>
    <td bgcolor='#F9FCEF'><b>请按说明修改设置：</b></td>
</tr>
<tr>
    <td><textarea name='templates' id='templates' style='width:100%;height:250px'>$templates</textarea></td>
</tr>
</table>";

$win = new OxWindow();
$win->Init('article_template_rand.php','js/blank.js','POST');
$win->AddHidden('dopost','save');
$win->AddHidden('token',$_SESSION['token']);
$win->AddTitle("本设置仅适用于系统默认的文章模型，设置后发布文章时会自动按指定的模板随机获取一个，如果不想使用此功能，把它设置为空即可！");
$win->AddMsgItem($msg);
$winform = $win->GetWindow('ok');
$win->Display();