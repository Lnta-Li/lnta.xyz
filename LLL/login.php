<?php
/**
 * 后台登录
 *
 * @version        $Id: login.php 1 8:48 2010年7月13日 $
 * @package        DedeCMS.Administrator
 * @founder        IT柏拉图, https://weibo.com/itprato
 * @author         DedeCMS团队
 * @copyright      Copyright (c) 2004 - 2024, 上海卓卓网络科技有限公司 (DesDev, Inc.)
 * @license        http://help.dedecms.com/usersguide/license.html
 * @link           http://www.dedecms.com
 */
require_once(dirname(__FILE__).'/../include/common.inc.php');
require_once(DEDEINC.'/userlogin.class.php');
if(empty($dopost)) $dopost = '';

//检测安装目录安全性
if( is_dir(dirname(__FILE__).'/../install') )
{
    if(!file_exists(dirname(__FILE__).'/../install/install_lock.txt') )
    {
      $fp = fopen(dirname(__FILE__).'/../install/install_lock.txt', 'w') or die('安装目录无写入权限，无法进行写入锁定文件，请安装完毕删除安装目录！');
      fwrite($fp,'ok');
      fclose($fp);
    }
    //为了防止未知安全性问题，强制禁用安装程序的文件
    if( file_exists("../install/index.php") ) {
        @rename("../install/index.php", "../install/index.php.bak");
    }
    if( file_exists("../install/module-install.php") ) {
        @rename("../install/module-install.php", "../install/module-install.php.bak");
    }
	$fileindex = "../install/index.html";
	if( !file_exists($fileindex) ) {
		$fp = @fopen($fileindex,'w');
		fwrite($fp,'dir');
		fclose($fp);
	}
}

//更新服务器
require_once (DEDEDATA.'/admin/config_update.php');


//检测后台目录是否更名
$cururl = GetCurUrl();
if(preg_match('/dede\/login/i',$cururl))
{
    $redmsg = '<div class=\'safe-tips\'>您的管理目录的名称中包含默认名称dede，建议在FTP里把它修改为其它名称，那样会更安全！</div>';
}
else
{
    $redmsg = '';
}

//登录检测
$admindirs = explode('/',str_replace("\\",'/',dirname(__FILE__)));
$admindir = $admindirs[count($admindirs)-1];
if($dopost=='login')
{
    //只允许用户名和密码用0-9,a-z,A-Z,'@','_','.','-'这些字符
    $userid = preg_replace("/[^0-9a-zA-Z_@!\.-]/", '', $userid);
    $pwd = preg_replace("/[^0-9a-zA-Z_@!\.-]/", '', $pwd);

    $cfg_fail_limit = isset($cfg_fail_limit) ? $cfg_fail_limit : 5;
    $cfg_lock_time = isset($cfg_lock_time) ? $cfg_lock_time : 3600;

    // 连续登录失败用户锁定
    $fail_limit = $cfg_fail_limit;
    $lock_time = $cfg_lock_time;
    $arr_login = array();
    $filename = DEDEDATA.'/login.data.php';
    if (file_exists($filename)) {
        require_once(DEDEDATA . '/login.data.php');
        $arr_login = json_decode($str_login, true);
        if ($arr_login[$userid] != '') {
            $count = explode(',', $arr_login[$userid])[0];
            $timestamp = explode(',', $arr_login[$userid])[1];
            if ($count == $fail_limit && $timestamp + $lock_time > time()) {
                $date = date('Y-m-d H:i:s', $timestamp + $lock_time);
                ResetVdValue();
                ShowMsg("连续登录失败用户锁定！解锁时间：{$date}", 'login.php', 0, 5000);
                exit;
            }
        }
    }

    $validate = empty($validate) ? '' : strtolower(trim($validate));
    $svali = strtolower(GetCkVdValue());
    if(($validate=='' || $validate != $svali) && preg_match("/6/",$safe_gdopen)){
        ResetVdValue();
        ShowMsg('验证码不正确!','login.php',0,1000);
        exit;
    } else {
        $cuserLogin = new userLogin($admindir);
        if(!empty($userid) && !empty($pwd))
        {
            $res = $cuserLogin->checkUser($userid,$pwd);

            //success
            if($res==1)
            {
                // 连续登录失败用户锁定
                $count = 0;
                $timestamp = time();
                $arr_login[$userid] = "{$count},{$timestamp}";
                $content = "<?php\r\n\$str_login='".json_encode($arr_login)."';";

                $fp = fopen($filename, 'w') or die("写入文件失败，请检查权限！");
                fwrite($fp, $content);
                fclose($fp);

                $cuserLogin->keepUser();
                if(!empty($gotopage))
                {
                    if (preg_match("#^(http|https):\/\/#i", $gotopage) && !preg_match("#^(http|https):\/\/{$_SERVER['HTTP_HOST']}#i", $gotopage)) {
                        ShowMsg('非本站资源无法访问', 'javascript:;');
                        exit();
                    }
                    ShowMsg('成功登录，正在转向管理管理主页！',dede_htmlspecialchars($gotopage));
                    exit();
                }
                else
                {
                    ShowMsg('成功登录，正在转向管理管理主页！',"index.php");
                    exit();
                }
            }
            //error
            else if($res==-1)
            {
                ResetVdValue();
                ShowMsg('用户名或者密码错误!','login.php',0,1000);
                exit;
            }
            else
            {
                // 连续登录失败用户锁定
                $count = 1;
                $timestamp = time();
                if ($arr_login[$userid] != '') {
                    $count = explode(',', $arr_login[$userid])[0] + 1;
                    if ($count > $fail_limit) {
                        $count = 1;
                    }
                }
                $arr_login[$userid] = "{$count},{$timestamp}";
                $content = "<?php\r\n\$str_login='".json_encode($arr_login)."';";

                $fp = fopen($filename, 'w') or die("写入文件失败，请检查权限！");
                fwrite($fp, $content);
                fclose($fp);

                ResetVdValue();
                ShowMsg('用户名或者密码错误!','login.php',0,1000);
				exit;
            }
        }

        //password empty
        else
        {
            ResetVdValue();
            ShowMsg('用户名和密码没填写完整!','login.php',0,1000);
			exit;
        }
    }
}

include('templets/login.htm');