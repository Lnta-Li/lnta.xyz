<?php
/**
 * 图集编辑
 *
 * @version        $Id: album_edit.php 1 8:26 2010年7月12日 $
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

if(empty($dopost)) $dopost = '';

if($dopost!='save')
{
    require_once(DEDEADMIN."/inc/inc_catalog_options.php");
    require_once(DEDEINC."/dedetag.class.php");
    ClearMyAddon();
    $aid = intval($aid);

    //读取归档信息
    $arcQuery = "SELECT ch.typename as channelname,ar.membername as rankname,arc.*
    FROM `#@__archives` arc
    LEFT JOIN `#@__channeltype` ch ON ch.id=arc.channel
    LEFT JOIN `#@__arcrank` ar ON ar.rank=arc.arcrank WHERE arc.id='$aid' ";
    $arcRow = $dsql->GetOne($arcQuery);
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
    $channelid = $arcRow['channel'];
    $imgurls = $addRow["imgurls"];
    $maxwidth = $addRow["maxwidth"];
    $pagestyle = $addRow["pagestyle"];
    $irow = $addRow["row"];
    $icol = $addRow["col"];
    $isrm = $addRow["isrm"];
    $body = $addRow["body"];
    $ddmaxwidth = $addRow["ddmaxwidth"];
    $pagepicnum = $addRow["pagepicnum"];
    $tags = GetTags($aid);

    // 读取审核意见
    $row = $dsql->GetOne("SELECT * FROM `#@__archives_log_detail` WHERE `archives_id` = '{$aid}' AND `remark` != '' ORDER BY `id` DESC");
    $remark = $row['remark'];

    include DedeInclude("templets/album_edit.htm");
    exit();
}
/*--------------------------------
function __save(){  }
-------------------------------*/
else if($dopost=='save')
{
    require_once(DEDEINC.'/image.func.php');
    require_once(DEDEINC.'/oxwindow.class.php');

    $flag = isset($flags) ? join(',', $flags) : '';
    $notpost = isset($notpost) && $notpost == 1 ? 1: 0;
    if(empty($typeid2)) $typeid2 = 0;
    if(!isset($autokey)) $autokey = 0;
    if(!isset($remote)) $remote = 0;
    if(!isset($dellink)) $dellink = 0;
    if(!isset($autolitpic)) $autolitpic = 0;
    if(!isset($formhtml)) $formhtml = 0;
    if(!isset($formzip)) $formzip = 0;
    if(!isset($ddisfirst)) $ddisfirst = 0;
    if(!isset($delzip)) $delzip = 0;

    $id = intval($id);

    if($typeid==0)
    {
        ShowMsg("请指定文档的栏目！","-1");
        exit();
    }
    if(empty($channelid))
    {
        ShowMsg("文档为非指定的类型，请检查你发布内容的表单是否合法！","-1");
        exit();
    }
    
    // 获取频道模型附加表
    $cInfos = $dsql->GetOne("SELECT addtable FROM `#@__channeltype` WHERE id='$channelid'");
    if(!is_array($cInfos)) {
        ShowMsg("获取频道模型信息失败！","javascript:;");
        exit();
    }
    $addtable = $cInfos['addtable'];
    if(empty($addtable)) {
        ShowMsg("频道模型附加表不存在！","javascript:;");
        exit();
    }
    
    if(!CheckChannel($typeid,$channelid))
    {
        ShowMsg("你所选择的栏目与当前模型不相符，请选择白色的选项！","-1");
        exit();
    }
    if(!TestPurview('a_Edit'))
    {
        if(TestPurview('a_AccEdit'))
        {
            CheckCatalog($typeid,"对不起，你没有操作栏目 {$typeid} 的文档权限！");
        }
        else
        {
            CheckArcAdmin($id,$cuserLogin->getUserID());
        }
    }

    //对保存的内容进行处理
    $pubdate = GetMkTime($pubdate);
    $sortrank = AddDay($pubdate,$sortup);
    $ismake = $ishtml==0 ? -1 : 0;
    $title = cn_substrR($title,$cfg_title_maxlen);
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
    $litpic = GetDDImage('none', $picname, $ddisremote);

    // 处理横板缩略图上传 (编辑逻辑)
    $existing_heimg = isset($arcRow['heimg']) ? $arcRow['heimg'] : ''; 
    $final_heimg_path = $existing_heimg; 
    $heimg_path_from_form = isset($_POST['heimg_path']) ? trim($_POST['heimg_path']) : '';
    $current_aid = $id; // 在编辑时，$id 就是文章ID

    if (isset($_FILES['heimg_file']) && $_FILES['heimg_file']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['heimg_file']['error'] === UPLOAD_ERR_OK) {
            $heimg_base_upload_dir = '/uploads/heimg'; // 相对于网站根
            $heimg_upload_dir_abs = rtrim($cfg_basedir, '/') . $heimg_base_upload_dir;

            if (!is_dir($heimg_upload_dir_abs)) {
                CreateDir($heimg_upload_dir_abs);
            }
            if (!is_dir($heimg_upload_dir_abs) || !is_writable($heimg_upload_dir_abs)) {
                ShowMsg("横板缩略图主目录创建失败或不可写！请检查路径：{$heimg_upload_dir_abs} 的权限。", "-1");
                exit();
            }
            
            $heimg_extension = strtolower(pathinfo($_FILES['heimg_file']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

            if (in_array($heimg_extension, $allowed_extensions)) {
                $heimg_filename = 'heimg_' . $current_aid . '.' . $heimg_extension;
                $heimg_target_file_abs = $heimg_upload_dir_abs . '/' . $heimg_filename;
                $heimg_target_file_rel = $heimg_base_upload_dir . '/' . $heimg_filename;

                // 如果旧的 heimg 存在且与新的不同（或者旧heimg存在，新heimg路径虽然相同但上传了新文件），先删除旧文件
                if (!empty($existing_heimg) && file_exists($cfg_basedir.$existing_heimg)) {
                    if ($existing_heimg != $heimg_target_file_rel || ($existing_heimg == $heimg_target_file_rel && $_FILES['heimg_file']['size'] > 0) ) {
                         @unlink($cfg_basedir.$existing_heimg);
                    }
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
        if (empty($final_heimg_path) && !empty($existing_heimg) && file_exists($cfg_basedir.$existing_heimg)) {
             @unlink($cfg_basedir.$existing_heimg);
        }
    }
    
    //分析body里的内容
    $body = AnalyseHtmlBody($body, $description, $litpic, $keywords, 'htmltext');

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
    
    // 生成小尺寸缩略图
    $subpic = '';
    if(!empty($litpic) && isset($make_subpic) && $make_subpic==1) {
        require_once(DEDEINC.'/extend.func.php');
        $subpic = createSubPic($litpic, 0, 0, $id);
    }
    
    // 处理主题模式
    $theme_mode = isset($theme_mode) ? intval($theme_mode) : '';
    
    //更新数据库的SQL语句
    $query = "
    UPDATE `#@__archives` SET
    typeid='$typeid',
    typeid2='$typeid2',
    sortrank='$sortrank',
    flag='$flag',
    click='$click',
    ismake='$ismake',
    arcrank='$arcrank',
    money='$money',
    title='$title',
    color='$color',
    source='$source',
    writer='$writer',
    litpic='$litpic',
    heimg='$final_heimg_path',
    subpic='$subpic',
    pubdate='$pubdate',
    notpost='$notpost',
    description='$description',
    keywords='$keywords',
    shorttitle='$shorttitle',
    filename='$filename',
    dutyadmin='$adminid',
    weight='$weight',
    hide_thumb='$hide_thumb',
    small_img='$small_img'
    WHERE id='$id'; ";

    if(!$dsql->ExecuteNoneQuery($query))
    {
        ShowMsg("更新数据库archives表时出错，请检查！".$dsql->GetError(),"javascript:;");
        exit();
    }

    $imgurls = "{dede:pagestyle maxwidth='$maxwidth' pagepicnum='$pagepicnum' ddmaxwidth='$ddmaxwidth' row='$row' col='$col' value='$pagestyle'/}\r\n";
    $hasone = false;

    //----------------------------------------
    //检查旧的图片是否有更新，并保存
    //-----------------------------------------
    $ids = json_decode(stripslashes($albumIds), true);
    if (!empty($ids)) {
        foreach ($ids as $i) {
            $i = (int) $i;

            $iurl = stripslashes(${'imgurl'.$i});
            $ddurl = stripslashes(${'imgddurl'.$i});
            if(preg_match("#swfupload#i", $ddurl)) $ddurl = '';
            $imgfile = $cfg_basedir.$iurl;
            $litimgfile = $cfg_basedir.$ddurl;
            if( !isset(${'imgurl'.$i}) ) {
                unlink($imgfile);
                $dsql->ExecuteNoneQuery("DELETE FROM `#@__uploads` WHERE `url` = '{$iurl}';");
                continue;
            }
            $info = '';
            $iinfo = str_replace("'", "`", stripslashes(${'imgmsg'.$i}));
            //有上传文件的情况
            if( isset(${'imgfile'.$i}) && is_uploaded_file(${'imgfile'.$i}) )
            {
                $tmpFile = ${'imgfile'.$i};
                //检测上传的图片， 如果类型不对，保留原来图片
                $imgInfos = @GetImageSize($tmpFile, $info);
                if(!is_array($imgInfos))
                {
                    $imgInfos = @GetImageSize($imgfile, $info);
                    $imgurls .= "{dede:img ddimg='$ddurl' text='$iinfo' width='".$imgInfos[0]."' height='".$imgInfos[1]."'} $iurl {/dede:img}\r\n";
                    continue;
                }
                move_uploaded_file($tmpFile, $imgfile);
                $imgInfos = @GetImageSize($imgfile, $info);
                if($ddurl==$iurl)
                {
                    $litpicname = $pagestyle > 2 ? GetImageMapDD($iurl, $cfg_ddimg_width) : $iurl;
                    $litimgfile = $cfg_basedir.$litpicname;
                }
                else
                {
                    if($cfg_ddimg_full=='Y') ImageResizeNew($imgfile, $cfg_ddimg_width, $cfg_ddimg_height, $litimgfile);
                    else ImageResize($imgfile, $cfg_ddimg_width, $cfg_ddimg_height, $litimgfile);
                    $litpicname = $ddurl;
                }
                $imgurls .= "{dede:img ddimg='$litpicname' text='$iinfo' width='".$imgInfos[0]."' height='".$imgInfos[1]."'} $iurl {/dede:img}\r\n";
            }
            //没上传图片(只修改msg信息)
            else
            {
                $iinfo = str_replace("'", "`", stripslashes(${'imgmsg'.$i}));
                $iurl = stripslashes(${'imgurl'.$i});
                $ddurl = stripslashes(${'imgddurl'.$i});
                if(preg_match("#swfupload#i", $ddurl))
                {
                    $ddurl = $pagestyle > 2 ? GetImageMapDD($iurl, $cfg_ddimg_width) : $iurl;
                }
                $imgInfos = @GetImageSize($imgfile, $info);
                $imgurls .= "{dede:img ddimg='$ddurl' text='$iinfo' width='".$imgInfos[0]."' height='".$imgInfos[1]."'} $iurl {/dede:img}\r\n";
            }
        }
    }

    //----------------------------
    //从HTML中获取新图片
    //----------------------------
    if($formhtml==1 && !empty($imagebody))
    {
        $imagebody = stripslashes($imagebody);
        $imgurls .= GetCurContentAlbum($imagebody,$copysource,$litpicname);
        if($ddisfirst==1 && $litpic=="" && !empty($litpicname))
        {
            $litpic = $litpicname;
            $hasone = true;
        }
    }
    /*---------------------
    function _getformzip()
    从ZIP文件中获取新图片
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
                    if(!$hasone && $ddisfirst==1
                    && $litpic=="" && !empty($litpicname))
                    {
                        if( file_exists($cfg_basedir.$litpicname) )
                        {
                            $litpic = $litpicname;
                            $hasone = true;
                        }
                    }
                }
            }
            if($delzip==1 && count($imgs) > 0)
            {
                unlink($zipfile);
            }
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

    // 检查图集是否存在图片
    $has_pics = 0;
    if(preg_match('/\{dede:img/i', $imgurls)) {
        $has_pics = 1;
    }

    //分析处理附加表数据
    $inadd_f = '';
    $inadd_v = '';
    if(!empty($dede_addonfields))
    {
        $addonfields = explode(';', $dede_addonfields);
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
                $vs = explode(',', $v);
                if($vs[1]=='htmltext'||$vs[1]=='textdata') //HTML文本特殊处理
                {
                    ${$vs[0]} = AnalyseHtmlBody(${$vs[0]}, $description, $litpic, $keywords, $vs[1]);
                }
                else
                {
                    if(!isset(${$vs[0]}))
                    {
                        ${$vs[0]} = '';
                    }
                    ${$vs[0]} = GetFieldValueA(${$vs[0]}, $vs[1], $id);
                }
                $inadd_f .= ',`'.$vs[0].'`';
                $inadd_v .= ",'".${$vs[0]}."'";
            }
        }
    }
    // 这两个字段禁止修改
    $inColumn = '';
    $has_picsColumn = '';

    // 获取用户IP
    $userip = GetIP();

    //更新附加表SQL语句
    $query = "UPDATE `$addtable` SET typeid='$typeid',pagestyle='$pagestyle',maxwidth='$maxwidth',
            row='$row',col='$col',isrm='$isrm',ddmaxwidth='$ddmaxwidth',pagepicnum='$pagepicnum',body='$body',userip='$userip',redirecturl='$redirecturl',theme='$theme_mode',has_pics='$has_pics',imgurls='$imgurls'{$inColumn}{$inadd_f} WHERE aid='$id';";
    if(!$dsql->ExecuteNoneQuery($query))
    {
        ShowMsg("更新附加表 `$addtable` 时出错，请检查原因！".$dsql->GetError(),"javascript:;");
        exit();
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
    UpIndexKey($id,$arcrank,$typeid,$sortrank,$tags);
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
    $arcUrl = MakeArt($id,true,true,$isremote);
    if($arcUrl=='')
    {
        $arcUrl = $cfg_phpurl."/view.php?aid=$id";
    }
    ClearMyAddon($id, $title);
    //返回成功信息
    $msg =
    " 　　请选择你的后续操作：
    <a href='album_add.php?cid=$typeid'><u>继续发布图片</u></a>
    &nbsp;&nbsp;
    <a href='archives_do.php?aid=".$id."&dopost=editArchives'><u>查看更改</u></a>
    &nbsp;&nbsp;
    <a href='$arcUrl' target='_blank'><u>预览文档</u></a>
    &nbsp;&nbsp;
    <a href='catalog_do.php?cid=$typeid&dopost=listArchives'><u>管理已发布图片</u></a>
    &nbsp;&nbsp;
    $backurl
    ";

    $wintitle = "成功更改图集！";
    $wecome_info = "文章管理::更改图集";
    $win = new OxWindow();
    $win->AddTitle("成功更改一个图集：");
    $win->AddMsgItem($msg);
    $winform = $win->GetWindow("hand","&nbsp;",false);
    $win->Display();
}