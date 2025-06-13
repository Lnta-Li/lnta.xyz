<?php
/**
 * 上传图片
 *
 * @version        $Id: select_images_post_wangEditor.php 1 9:43 2010年7月8日 $
 * @package        DedeCMS.Dialog
 * @founder        IT柏拉图, https://weibo.com/itprato
 * @author         DedeCMS团队
 * @copyright      Copyright (c) 2004 - 2024, 上海卓卓网络科技有限公司 (DesDev, Inc.)
 * @license        http://help.dedecms.com/usersguide/license.html
 * @link           http://www.dedecms.com
 */
require_once(dirname(__FILE__)."/config.php");
require_once(dirname(__FILE__)."/../image.func.php");


if(empty($activepath))
{
    $activepath ='';
    $activepath = preg_replace("/[.]+[\/\\\\]+/", "/", $activepath);
    $activepath = preg_replace("#\/{1,}#", '/', $activepath);
    if(strlen($activepath) < strlen($cfg_image_dir))
    {
        $activepath = $cfg_image_dir;
    }
}

if(empty($imgfile))
{
    $imgfile='';
}
if(!is_uploaded_file($imgfile))
{
    $data = array(
        'errno' => 1,
        'message' => "您没有选择上传的文件！"
    );
    exit(json_encode($data));
}
$CKEditorFuncNum = (isset($CKEditorFuncNum))? $CKEditorFuncNum : 1;
$imgfile_name = trim(preg_replace("#[ \r\n\t\*\%\\\/\?><\|\":]{1,}#", '', $imgfile_name));

if(in_array(pathinfo($imgfile_name, PATHINFO_EXTENSION), explode("|", $cfg_imgtype), true) === FALSE)
{
    $data = array(
        'errno' => 1,
        'message' => "您所上传的图片类型不在许可列表，请更改系统对扩展名限定的配置！"
    );
    exit(json_encode($data));
}
$nowtme = time();
$sparr = Array("image/pjpeg", "image/jpeg", "image/gif", "image/png", "image/xpng", "image/wbmp");
$imgfile_type = strtolower(trim($imgfile_type));
if(!in_array($imgfile_type, $sparr))
{
    $data = array(
        'errno' => 1,
        'message' => "上传的图片格式错误，请使用JPEG、GIF、PNG、WBMP格式的其中一种！"
    );
    exit(json_encode($data));
}
$mdir = MyDate($cfg_addon_savetype, $nowtme);
if(!is_dir($cfg_basedir.$activepath."/$mdir"))
{
    MkdirAll($cfg_basedir.$activepath."/$mdir",$cfg_dir_purview);
    CloseFtp();
}
$filename_name = $cuserLogin->getUserID().'-'.dd2char(MyDate("ymdHis", $nowtme).mt_rand(100,999));
$filename = $mdir.'/'.$filename_name;
$fs = explode('.', $imgfile_name);
$filename = $filename.'.'.$fs[count($fs)-1];
$filename_name = $filename_name.'.'.$fs[count($fs)-1];
$fullfilename = $cfg_basedir.$activepath."/".$filename;

$data = array(
    'errno' => 1,
    'message' => "上传文件到 $fullfilename 失败！"
);
move_uploaded_file($imgfile, $fullfilename) or exit(json_encode($data));
if($cfg_remote_site=='Y' && $remoteuploads == 1)
{
    //分析远程文件路径
    $remotefile = str_replace(DEDEROOT, '', $fullfilename);
    $localfile = '../..'.$remotefile;
    //创建远程文件夹
    $remotedir = preg_replace('/[^\/]*\.(jpg|gif|bmp|png)/', '', $remotefile);
    $ftp->rmkdir($remotedir);
    $ftp->upload($localfile, $remotefile);
}
@unlink($imgfile);
if(empty($resize))
{
    $resize = 0;
}
if($resize==1)
{
    if(in_array($imgfile_type, $cfg_photo_typenames))
    {
        ImageResize($fullfilename, $iwidth, $iheight);
    }
}
else
{
    if(in_array($imgfile_type, $cfg_photo_typenames))
    {
        WaterImg($fullfilename, 'up');
    }
}

$info = '';
$sizes[0] = 0; $sizes[1] = 0;
$sizes = getimagesize($fullfilename, $info);
$imgwidthValue = $sizes[0];
$imgheightValue = $sizes[1];
$imgsize = filesize($fullfilename);
$inquery = "INSERT INTO `#@__uploads`(arcid,title,url,mediatype,width,height,playtime,filesize,uptime,mid)
  VALUES ('0','$filename','".$activepath."/".$filename."','1','$imgwidthValue','$imgheightValue','0','{$imgsize}','{$nowtme}','".$cuserLogin->getUserID()."'); ";
$dsql->ExecuteNoneQuery($inquery);
$fid = $dsql->GetLastID();
AddMyAddon($fid, $activepath.'/'.$filename);

if ($GLOBALS['cfg_html_editor']=='wangEditor')
{
    $fileurl = $activepath.'/'.$filename;

    $data = array(
        'errno' => 0,
        'data' => array(
            'url' => $fileurl,
            'alt' => "",
            'href' => ""
        )
    );
    exit(json_encode($data));
}

exit();