<?php
/**
 * 上传视频
 *
 * @version        $Id: select_media_post_wangEditor.php 1 9:43 2010年7月8日 $
 * @package        DedeCMS.Dialog
 * @founder        IT柏拉图, https://weibo.com/itprato
 * @author         DedeCMS团队
 * @copyright      Copyright (c) 2004 - 2024, 上海卓卓网络科技有限公司 (DesDev, Inc.)
 * @license        http://help.dedecms.com/usersguide/license.html
 * @link           http://www.dedecms.com
 */
require_once(dirname(__FILE__)."/config.php");

$uptime = time();
$adminid = $cuserLogin->getUserID();

if(isset($upfile) && is_uploaded_file($upfile))
{
    $dpath = MyDate("ymd", $uptime);

    if(!in_array(strtolower(pathinfo($upfile_name, PATHINFO_EXTENSION)), explode("|", $cfg_mediatype), true)) {
        $data = array(
            'errno' => 1,
            'message' => "您上传的文件扩展名不在许可列表 [{$cfg_mediatype}]，请更改系统配置的参数！"
        );
        exit(json_encode($data));
    }

    if(!preg_match('#audio|media|video#i', $upfile_type)) {
        $data = array(
            'errno' => 1,
            'message' => "您上传的文件存在问题，请检查文件类型！"
        );
        exit(json_encode($data));
    }
    
    $savePath = $cfg_other_medias."/".$dpath;

    $filename = "{$adminid}_".MyDate("His",$uptime).mt_rand(100,999);
    $fs = explode(".", $upfile_name);
    $filename = $filename.".".$fs[count($fs)-1];
    $filename = $savePath."/".$filename;
    if(!is_dir($cfg_basedir.$savePath))
    {
        MkdirAll($cfg_basedir.$savePath, 777);
        CloseFtp();
    }
    $fullfilename = $cfg_basedir.$filename;
    
    @move_uploaded_file($upfile, $fullfilename);

    $inquery = "INSERT INTO `#@__uploads`(arcid,title,url,mediatype,width,height,playtime,filesize,uptime,mid)
    VALUES ('0','$upfile_name','$filename','3','0','0','0','$upfile_size','$uptime','$adminid'); ";
    $dsql->ExecuteNoneQuery($inquery);
    $fid = $dsql->GetLastID();
    AddMyAddon($fid, $filename);
}

$data = array(
    'errno' => 0,
    'data' => array(
        'url' => $filename,
    )
);
exit(json_encode($data));