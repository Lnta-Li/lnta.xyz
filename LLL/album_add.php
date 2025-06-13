<?php
/**
 * 图集发布
 *
 * @version        $Id: album_add.php 1 8:26 2010年7月12日 $
 * @package        DedeCMS.Administrator
 * @founder        IT柏拉图, https://weibo.com/itprato
 * @author         DedeCMS团队
 * @copyright      Copyright (c) 2004 - 2024, 上海卓卓网络科技有限公司 (DesDev, Inc.)
 * @license        http://help.dedecms.com/usersguide/license.html
 * @link           http://www.dedecms.com
 */
require_once(dirname(__FILE__)."/config.php");
CheckPurview('a_New,a_AccNew');
require_once(DEDEINC."/customfields.func.php");
require_once(DEDEADMIN."/inc/inc_archives_functions.php");

if(empty($dopost)) $dopost = '';

if($dopost != 'save')
{
    require_once(DEDEINC."/dedetag.class.php");
    require_once(DEDEADMIN."/inc/inc_catalog_options.php");
    ClearMyAddon();
    $channelid = empty($channelid) ? 0 : intval($channelid);
    $cid = empty($cid) ? 0 : intval($cid);

    //获得频道模型ID
    if($cid>0 && $channelid==0)
    {
        $row = $dsql->GetOne("SELECT `channeltype` FROM `#@__arctype` WHERE id='$cid'; ");
        $channelid = $row['channeltype'];
    }
    else
    {
        if($channelid==0) $channelid = 2;
    }

    //获得频道模型信息
    $cInfos = $dsql->GetOne(" SELECT * FROM  `#@__channeltype` WHERE id='$channelid' ");
    $channelid = $cInfos['id'];
    
    //获取文章最大id以确定当前权重
    $maxWright = $dsql->GetOne("SELECT COUNT(*) AS cc FROM #@__archives");
    include DedeInclude("templets/album_add.htm");
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
    if(empty($click)) $click = ($cfg_arc_click=='-1' ? mt_rand(50, 200) : $cfg_arc_click);
    
    if(!isset($typeid2)) $typeid2 = 0;
    if(!isset($autokey)) $autokey = 0;
    if(!isset($remote)) $remote = 0;
    if(!isset($dellink)) $dellink = 0;
    if(!isset($autolitpic)) $autolitpic = 0;
    if(!isset($formhtml)) $formhtml = 0;
    if(!isset($formzip)) $formzip = 0;
    if(!isset($ddisfirst)) $ddisfirst = 0;
    if(!isset($delzip)) $delzip = 0;
    if(empty($click)) $click = ($cfg_arc_click=='-1' ? mt_rand(50, 200) : $cfg_arc_click);

    if($typeid==0)
    {
        ShowMsg("请指定文档的栏目！", "-1");
        exit();
    }
    if(empty($channelid))
    {
        ShowMsg("文档为非指定的类型，请检查你发布内容的表单是否合法！","-1");
        exit();
    }
    if(!CheckChannel($typeid,$channelid) )
    {
        ShowMsg("你所选择的栏目与当前模型不相符，请选择白色的选项！","-1");
        exit();
    }
    if(!TestPurview('a_New'))
    {
        CheckCatalog($typeid,"对不起，你没有操作栏目 {$typeid} 的权限！");
    }

    //对保存的内容进行处理
    if(empty($writer))$writer=$cuserLogin->getUserName();
    if(empty($source))$source='未知';
    $pubdate = GetMkTime($pubdate);
    $senddate = time();
    $sortrank = AddDay($pubdate,$sortup);
    $ismake = $ishtml==0 ? -1 : 0;
    $title = preg_replace("#\"#", '＂', $title);
    $title = cn_substrR($title,$cfg_title_maxlen);
    $shorttitle = cn_substrR($shorttitle,36);
    $color =  cn_substrR($color,7);
    $writer =  cn_substrR($writer,20);
    $source = cn_substrR($source,30);
    $description = cn_substrR($description,$cfg_auot_description);
    $keywords = cn_substrR($keywords,60);
    $filename = trim(cn_substrR($filename,40));
    $userip = GetIP();
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

    // 提前生成 $arcID 以便用于文件名
    $arcID = GetIndexKey($arcrank,$typeid,$sortrank,$channelid,$senddate,$adminid);
    if(empty($arcID))
    {
        ShowMsg("无法获得主键(arcID)，因此无法进行后续操作！","-1");
        exit();
    }

    // 处理横板缩略图上传
    $final_heimg_path = '';
    $heimg_path_from_form = isset($_POST['heimg_path']) ? trim($_POST['heimg_path']) : '';

    if (isset($_FILES['heimg_file']) && $_FILES['heimg_file']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['heimg_file']['error'] === UPLOAD_ERR_OK) {
            // 新的保存路径：直接在 uploads/heimg/ 下
            // 假设 cfg_uploads_dir 是 'uploads' 或者 DedeCMS 有类似配置项，或者直接硬编码
            // 为了简单且符合您的目录结构，我们直接构建基于 cfg_basedir 的路径
            $heimg_base_upload_dir = '/uploads/heimg'; // 相对于网站根
            $heimg_upload_dir_abs = rtrim($cfg_basedir, '/') . $heimg_base_upload_dir;

            // 确保 uploads/heimg 目录存在且可写
            if (!is_dir($heimg_upload_dir_abs)) {
                CreateDir($heimg_upload_dir_abs); // 尝试创建 uploads/heimg
            }
            if (!is_dir($heimg_upload_dir_abs) || !is_writable($heimg_upload_dir_abs)) {
                ShowMsg("横板缩略图主目录创建失败或不可写！请检查路径：{$heimg_upload_dir_abs} 的权限。", "-1");
                exit();
            }
            
            $heimg_extension = strtolower(pathinfo($_FILES['heimg_file']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

            if (in_array($heimg_extension, $allowed_extensions)) {
                $heimg_filename = 'heimg_' . $arcID . '.' . $heimg_extension;
                $heimg_target_file_abs = $heimg_upload_dir_abs . '/' . $heimg_filename;
                $heimg_target_file_rel = $heimg_base_upload_dir . '/' . $heimg_filename; // 相对路径保存到数据库

                // 如果文件已存在，先删除旧文件 (处理重复提交或覆盖)
                if (file_exists($heimg_target_file_abs)) {
                    @unlink($heimg_target_file_abs);
                }

                if (move_uploaded_file($_FILES['heimg_file']['tmp_name'], $heimg_target_file_abs)) {
                    $final_heimg_path = $heimg_target_file_rel;
                } else {
                    $error_message = "上传横板缩略图失败！PHP报告文件已成功接收，但移动到最终位置 ({$heimg_target_file_abs}) 时失败。";
                    $error_message .= " 请重点检查目标目录 {$heimg_upload_dir_abs} 的写入权限。";
                    $error_message .= " PHP `move_uploaded_file`函数返回失败。临时文件: {$_FILES['heimg_file']['tmp_name']}";
                    ShowMsg($error_message, "-1");
                    exit();
                }
            } else {
                ShowMsg("横板缩略图格式不正确！仅支持jpg, jpeg, png, gif. 您上传的文件类型是: " . $heimg_extension, "-1");
                exit();
            }
        } else {
            $upload_error_code = $_FILES['heimg_file']['error'];
            $error_message = "横板缩略图上传时发生错误！错误代码: {$upload_error_code}. ";
             switch ($upload_error_code) {
                case UPLOAD_ERR_INI_SIZE: $error_message .= "文件大小超过了 php.ini 中 upload_max_filesize 的限制。"; break;
                case UPLOAD_ERR_FORM_SIZE: $error_message .= "文件大小超过了 HTML 表单中 MAX_FILE_SIZE 的限制。"; break;
                case UPLOAD_ERR_PARTIAL: $error_message .= "文件只有部分被上传。"; break;
                case UPLOAD_ERR_NO_TMP_DIR: $error_message .= "找不到PHP临时文件夹。请检查 php.ini 中的 upload_tmp_dir 设置。"; break;
                case UPLOAD_ERR_CANT_WRITE: $error_message .= "PHP文件写入磁盘失败（可能在临时目录）。"; break;
                case UPLOAD_ERR_EXTENSION: $error_message .= "一个PHP扩展停止了文件上传。"; break;
                default: $error_message .= "未知的PHP上传错误。";
            }
            ShowMsg($error_message, "-1");
            exit();
        }
    } else {
        $final_heimg_path = $heimg_path_from_form;
    }

    //使用第一张图作为缩略图
    if($ddisfirst==1 && $litpic=='')
    {
        if(isset($imgurl1))
        {
            $litpic = GetDDImage('ddfirst', $imgurl1, $isrm);
        }
    }
    
    //检测上传的图片是否存在问题
    if(!empty($_POST['picinfos']))
    {
        $dsql->SetQuery("SELECT aid FROM `#@__uploads` WHERE aid='{$_POST['picinfos']}' AND type='album' ");
        $row = $dsql->GetArray();
        if(!is_array($row))
        {
            ShowMsg("无法找到已上传的图片，请重新上传！", "-1");
            exit();
        }
    }
    
    //生成小尺寸缩略图
    $subpic = '';
    if(!empty($picname) && isset($make_subpic) && $make_subpic==1) {
        require_once(DEDEINC.'/extend.func.php');
        $subpic = createSubPic($picname, 0, 0, $arcID);
    }

    // 处理主题模式
    $theme_mode = isset($theme_mode) ? intval($theme_mode) : '';

    $imgurls = "{dede:pagestyle maxwidth='$maxwidth' pagepicnum='$pagepicnum' ddmaxwidth='$ddmaxwidth' row='$row' col='$col' value='$pagestyle'/}\r\n";
    $hasone = FALSE;

    //处理并保存从网上复制的图片
    /*---------------------
    function _getformhtml()
    ------------------*/
    if($formhtml==1)
    {
        $imagebody = stripslashes($imagebody);
        $imgurls .= GetCurContentAlbum($imagebody,$copysource,$litpicname);
        if($ddisfirst==1 && $litpic=='' && !empty($litpicname))
        {
            $litpic = $litpicname;
            $hasone = TRUE;
        }
    }
    /*---------------------
    function _getformzip()
    处理从ZIP中解压的图片
    ---------------------*/
    if($formzip==1)
    {
        include_once(DEDEINC."/zip.class.php");
        include_once(DEDEADMIN."/file_class.php");
        $zipfile = $cfg_basedir.str_replace($cfg_mainsite,'',$zipfile);
        $tmpzipdir = DEDEDATA.'/ziptmp/'.cn_substr(md5(ExecTime()),16);
        $ntime = time();
        if(file_exists($zipfile))
        {
            @mkdir($tmpzipdir,$GLOBALS['cfg_dir_purview']);
            @chmod($tmpzipdir,$GLOBALS['cfg_dir_purview']);
            $z = new zip();
            $z->ExtractAll($zipfile,$tmpzipdir);
            $fm = new FileManagement();
            $imgs = array();
            $fm->GetMatchFiles($tmpzipdir,"jpg|png|gif",$imgs);
            $i = 0;
            foreach($imgs as $imgold)
            {
                $i++;
                $savepath = $cfg_image_dir."/".MyDate("Y-m",$ntime);
                CreateDir($savepath);
                $iurl = $savepath."/".MyDate("d",$ntime).dd2char(MyDate("His",$ntime).'-'.$adminid."-{$i}".mt_rand(1000,9999));
                $iurl = $iurl.substr($imgold,-4,4);
                $imgfile = $cfg_basedir.$iurl;
                copy($imgold,$imgfile);
                unlink($imgold);

                if(is_file($imgfile))
                {
                    $litpicname = $pagestyle > 2 ? GetImageMapDD($iurl,$cfg_ddimg_width) : $iurl;
                    //指定了提取第一张为缩略图的情况强制使用第一张缩略图
                    if($i=='1')
                    {
                        if(!$hasone && $ddisfirst==1 && $litpic=='' && empty($litpicname))
                        {
                            $litpicname = GetImageMapDD($iurl,$cfg_ddimg_width);
                        }
                    }
                    $info = '';
                    $imgInfos = GetImageSize($imgfile,$info);
                    $imgText = dede_htmlspecialchars(pathinfo($imgold, PATHINFO_FILENAME));
                    $imgurls .= "{dede:img ddimg='$litpicname' text='$imgText' width='".$imgInfos[0]."' height='".$imgInfos[1]."'} $iurl {/dede:img}\r\n";

                    //把图片信息保存到媒体文档管理档案中
                    $inquery = "
                   INSERT INTO #@__uploads(title,url,mediatype,width,height,playtime,filesize,uptime,mid)
                    VALUES ('{$title}','{$iurl}','1','".$imgInfos[0]."','".$imgInfos[1]."','0','".filesize($imgfile)."','".$ntime."','$adminid');
                 ";
                    $dsql->ExecuteNoneQuery($inquery);
                    $fid = $dsql->GetLastID();
                    AddMyAddon($fid, $iurl);
                    
                    WaterImg($imgfile, 'up');

                    if(!$hasone && $ddisfirst==1 && $litpic=='')
                    {
                        if(empty($litpicname))
                        {
                            $litpicname = $iurl;
                            $litpicname = GetImageMapDD($iurl, $cfg_ddimg_width);
                        }
                        $litpic = $litpicname;
                        $hasone = TRUE;
                    }
                }
            }
            if($delzip==1 && count($imgs) > 0) unlink($zipfile);
            $fm->RmDirFiles($tmpzipdir);
        }
    }
    /*---------------------
    function _getformupload()
    通过正常上传的图片
    ---------------------*/
    if ($albumUploadFiles !== '') {
        $files = json_decode(stripslashes($albumUploadFiles), true);

        foreach ($files as $file) {
            $file['name'] = preg_replace("#([.]+[/]+)*#", "", $file['name']);
            $file['name'] = dede_htmlspecialchars($file['name']);
            $file['remark'] = dede_htmlspecialchars($file['remark']);

            $uploadTmp = DEDEDATA . '/uploadtmp';
            $tmpFile = $uploadTmp . '/' . $file['name'];

            $fileDir = $cfg_image_dir . '/' . MyDate($cfg_addon_savetype, time());
            CreateDir($fileDir);
            $filePath = $fileDir . '/' . $file['name'];
            $imgFile = $cfg_basedir . $filePath;

            copy($tmpFile, $imgFile);
            unlink($tmpFile);

            $imgInfos = getimagesize($imgFile);
            $imgText = $file['remark'];
            $litpicname = $pagestyle > 2 ? GetImageMapDD($filePath, $cfg_ddimg_width) : $filePath;

            $imgurls .= "{dede:img ddimg='$litpicname' text='$imgText' width='" . $imgInfos[0] . "' height='" . $imgInfos[1] . "'} $filePath {/dede:img}\r\n";

            $inquery = "INSERT INTO `#@__uploads`(title,url,mediatype,width,height,playtime,filesize,uptime,mid)
                VALUES ('{$imgText}','{$filePath}','1','{$imgInfos[0]}','{$imgInfos[1]}','0','" . filesize($imgFile) . "','" . time() . "','{$adminid}');";
            $dsql->ExecuteNoneQuery($inquery);
            $fid = $dsql->GetLastID();
            AddMyAddon($fid, $filePath);

            if (!$hasone && $ddisfirst == 1 && $litpic == '') {
                $litpic = empty($litpicname) ? GetImageMapDD($filePath, $cfg_ddimg_width) : $litpicname;
                $hasone = TRUE;
            }
        }
    }

    $imgurls = addslashes($imgurls);
    
    //处理body字段自动摘要、自动提取缩略图等
    $body = AnalyseHtmlBody($body,$description,$litpic,$keywords,'htmltext');

    //检查图集是否存在图片
    $has_pics = 0;
    if(!empty($imgurls) && preg_match('/\{dede:img/i', $imgurls)) {
        $has_pics = 1;
    }

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
                if(!isset(${$vs[0]}))
                {
                    ${$vs[0]} = '';
                }
                else if($vs[1]=='htmltext'||$vs[1]=='textdata') //HTML文本特殊处理
                {
                    ${$vs[0]} = AnalyseHtmlBody(${$vs[0]},$description,$litpic,$keywords,$vs[1]);
                }
                else
                {
                    if(!isset(${$vs[0]}))
                    {
                        ${$vs[0]} = '';
                    }
                    ${$vs[0]} = GetFieldValueA(${$vs[0]},$vs[1],$arcID);
                }
                $inadd_f .= ','.$vs[0];
                $inadd_v .= " ,'".${$vs[0]}."' ";
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
    
    //加入主档案表
    $okdd = 0;
    $inQuery = "INSERT INTO `#@__archives`(id,typeid,typeid2,sortrank,flag,ismake,channel,arcrank,click,money,title,shorttitle,
    color,writer,source,litpic,heimg,subpic,pubdate,senddate,mid,notpost,description,keywords,filename,dutyadmin,weight,hide_thumb,small_img)
    VALUES ('$arcID','$typeid','$typeid2','$sortrank','$flag','$ismake','$channelid','$arcrank','$click','$money',
    '$title','$shorttitle','$color','$writer','$source','$litpic','$final_heimg_path','$subpic','$pubdate','$senddate',
    '$adminid','$notpost','$description','$keywords','$filename','$adminid','$weight','$hide_thumb','$small_img');";
    if(!$dsql->ExecuteNoneQuery($inQuery))
    {
        $gerr = $dsql->GetError();
        $dsql->ExecuteNoneQuery("DELETE FROM `#@__arctiny` WHERE id='$arcID'");
        ShowMsg("把数据保存到数据库主表 `#@__archives` 时出错，请联系管理员。<br>错误详情：".str_replace('"','',$gerr),"javascript:;");
        exit();
    }

    //加入附加表
    $cts = $dsql->GetOne("SELECT `addtable` FROM `#@__channeltype` WHERE id='$channelid' ");
    $addtable = trim($cts['addtable']);
    if(empty($addtable))
    {
        $dsql->ExecuteNoneQuery("DELETE FROM `#@__archives` WHERE id='$arcID'");
        $dsql->ExecuteNoneQuery("DELETE FROM `#@__arctiny` WHERE id='$arcID'");
        ShowMsg("没找到当前模型[{$channelid}]的主表信息，无法完成操作！。","javascript:;");
        exit();
    }
    $daccess = isset($daccess) && is_numeric($daccess) ? $daccess : 0;
    $useip = GetIP();
    
    $inQuery = "INSERT INTO `{$addtable}`(aid,typeid,redirecturl,userip,pagestyle,maxwidth,imgurls,row,col,isrm,ddmaxwidth,pagepicnum,body,theme,has_pics)
          VALUES ('$arcID','$typeid','','$useip','$pagestyle','$maxwidth','$imgurls','$row','$col','$isrm','$ddmaxwidth','$pagepicnum','$body','$theme_mode','$has_pics');";
    if(!$dsql->ExecuteNoneQuery($inQuery))
    {
        $gerr = $dsql->GetError();
        $dsql->ExecuteNoneQuery("DELETE FROM `#@__archives` WHERE id='$arcID'");
        $dsql->ExecuteNoneQuery("DELETE FROM `#@__arctiny` WHERE id='$arcID'");
        ShowMsg("把数据保存到数据库附加表 `{$addtable}` 时出错，请把相关信息提交给DedeCMS官方。".str_replace('"','',$gerr),"javascript:;");
        exit();
    }

    // 文档日志
    if ($cfg_archives_log == 'Y') {
        $archives_id = $arcID;
        $admin_id = $cuserLogin->getUserID();
        $ip = GetIP();
        $time = time();
        $dsql->ExecuteNoneQuery("INSERT INTO `#@__archives_log_list` (`archives_id`, `title`)
        VALUES ('{$archives_id}', '{$title}')");
        $dsql->ExecuteNoneQuery("INSERT INTO `#@__archives_log_detail` (`archives_id`, `title`, `body`, `remark`, `type`, `arcrank`, `admin_id`, `ip`, `time`)
        VALUES ('{$archives_id}', '{$title}', '{$body}', '', '添加文档', '{$arcrank}', '{$admin_id}', '{$ip}', '{$time}')");
    }

    //生成HTML
    InsertTags($tags,$arcID);
    if($cfg_remote_site=='Y' && $isremote=="1")
    {    
        if($serviterm!=""){
            list($servurl,$servuser,$servpwd) = explode(',',$serviterm);
            $config=array( 'hostname' => $servurl, 'username' => $servuser, 'password' => $servpwd,'debug' => 'TRUE');
        }else{
            $config=array();
        }
        if(!$ftp->connect($config)) exit('Error:None FTP Connection!');
    }
    $artUrl = MakeArt($arcID, TRUE, TRUE, $isremote);
    if($artUrl=='')
    {
        $artUrl = $cfg_phpurl."/view.php?aid=$arcID";
    }
    ClearMyAddon($arcID, $title);
    //返回成功信息
    $msg = "
    　　请选择你的后续操作：
    <a href='album_add.php?cid=$typeid'><u>继续发布图片</u></a>
    &nbsp;&nbsp;
    <a href='archives_do.php?aid=".$arcID."&dopost=editArchives'><u>更改图集</u></a>
    &nbsp;&nbsp;
    <a href='$artUrl' target='_blank'><u>预览文档</u></a>
    &nbsp;&nbsp;
    <a href='catalog_do.php?cid=$typeid&dopost=listArchives'><u>已发布图片管理</u></a>
    &nbsp;&nbsp;
    $backurl
   ";
  $msg = "<div style=\"line-height:36px;height:36px\">{$msg}</div>".GetUpdateTest();
    
    $wintitle = "成功发布一个图集！";
    $wecome_info = "文章管理::发布图集";
    $win = new OxWindow();
    $win->AddTitle("成功发布一个图集：");
    $win->AddMsgItem($msg);
    $winform = $win->GetWindow("hand","&nbsp;",FALSE);
    $win->Display();


}