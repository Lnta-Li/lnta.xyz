<?php
if(!defined('DEDEINC')) exit('DedeCMS Error: Request Error!');

if(isset($_FILES['GLOBALS'])) exit('Request not allow!');

require_once(DEDEINC."/common.func.php");

//为了防止用户通过注入的可能性改动了数据库
//这里强制限定的某些文件类型禁止上传
$cfg_not_allowall = "php|pl|cgi|asp|aspx|jsp|php3|shtm|shtml";
$keyarr = array('name', 'type', 'tmp_name', 'size');
if ($GLOBALS['cfg_html_editor']=='ckeditor' && isset($_FILES['upload']))
{
    $_FILES['imgfile'] = $_FILES['upload'];
    $CKUpload = TRUE;
    unset($_FILES['upload']);
}
foreach($_FILES as $_key=>$_value)
{
    foreach($keyarr as $k)
    {
        if(!isset($_FILES[$_key][$k]))
        {
            exit('DedeCMS Error: Request Error!');
        }
    }
    if( preg_match('#^(cfg_|GLOBALS)#', $_key) )
    {
        exit('Request var not allow for uploadsafe!');
    }
    $$_key = $_FILES[$_key]['tmp_name'];
    ${$_key.'_name'} = $_FILES[$_key]['name'];
    ${$_key.'_type'} = $_FILES[$_key]['type'] = preg_replace('#[^0-9a-z\./]#i', '', $_FILES[$_key]['type']);
    ${$_key.'_size'} = $_FILES[$_key]['size'] = preg_replace('#[^0-9]#','',$_FILES[$_key]['size']);
    if(!empty(${$_key.'_name'}) && (in_array(pathinfo(${$_key.'_name'}, PATHINFO_EXTENSION), explode("|", $cfg_not_allowall), true) === TRUE || !preg_match("#\.#", ${$_key.'_name'})) )
    {
        if(!defined('DEDEADMIN'))
        {
            exit('Not Admin Upload filetype not allow !');
        }
    }
    if(empty(${$_key.'_size'}))
    {
        ${$_key.'_size'} = @filesize($$_key);
    }

    // 如果是图片
    if (preg_match('#image/([a-z]+)#i', ${$_key . '_type'}) || preg_match("#{$cfg_imgtype}#i", pathinfo(${$_key . '_name'}, PATHINFO_EXTENSION))) {
        if (${$_key . '_type'} === 'image/jpeg' || preg_match("#jpg|jpeg#i", pathinfo(${$_key . '_name'}, PATHINFO_EXTENSION))) {
            $image = imagecreatefromjpeg($$_key);
            imagejpeg($image, $$_key, 85);
            imagedestroy($image);
        } else if (${$_key . '_type'} === 'image/png' || preg_match("#png#i", pathinfo(${$_key . '_name'}, PATHINFO_EXTENSION))) {
            $image = imagecreatefrompng($$_key);
            imagesavealpha($image, true);
            header('Content-type: image/png');
            imagepng($image, $$_key);
            imagedestroy($image);
        }
    } else if (file_exists($$_key)) {
        $fp = fopen($$_key, 'rb');
        $content = fread($fp, ${$_key . '_size'});
        fclose($fp);

        // 不允许这些字符
        $content = preg_replace("#(/\*)[\s\S]*(\*/)#i", '', $content);

        global $cfg_disable_funs;
        $cfg_disable_funs = isset($cfg_disable_funs) ? $cfg_disable_funs : 'phpinfo,eval,assert,exec,passthru,shell_exec,system,proc_open,popen,curl_exec,curl_multi_exec,parse_ini_file,show_source,file_put_contents,fsockopen,fopen,fwrite,preg_replace';
        $cfg_disable_funs = $cfg_disable_funs.',[$]GLOBALS,[$]_GET,[$]_POST,[$]_REQUEST,[$]_FILES,[$]_COOKIE,[$]_SERVER,include,require,create_function,array_map,call_user_func,call_user_func_array,array_filert';
        foreach (explode(",", $cfg_disable_funs) as $value) {
            $value = str_replace(" ", "", $value);
            if(!empty($value) && preg_match("#[^a-z]+['\"]*{$value}['\"]*[\s]*[([{']#i", " {$content}") == TRUE) {
                $content = dede_htmlspecialchars($content);
                die("DedeCMS提示：当前上传的文件中存在恶意代码！<pre>{$content}</pre>");
            }
            if(!empty($value) && preg_match("#(<)[\s]*(script)[\s\S]*(src)[\s]*(=)[\s]*[\"|']#i", " {$content}") == TRUE) {
                preg_match_all("#(src)[\s]*(=)[\s]*[\"|'][\s]*((http|https)(:\/\/)[\S]*)[\"|']#i", " {$content}", $subject);
                foreach ($subject[3] as $url) {
                    if (preg_match("#^(http|https):\/\/#i", $url) && !preg_match("#^(http|https):\/\/{$_SERVER['HTTP_HOST']}#i", $url)) {
                        die("DedeCMS提示：非本站资源无法访问！<pre>{$url}</pre>");
                    }
                }
            }
        }

         if(preg_match("#^[\s\S]+<\?(php|=)?[\s]+#i", " {$content}") == TRUE && json_encode($content) !== FALSE) {
            if(preg_match("#[$][_0-9a-z]+[\s]*[(][\s\S]*[)][\s]*[;]#iU", " {$content}") == TRUE) {
                $content = dede_htmlspecialchars($content);
                die("DedeCMS提示：当前上传的文件中存在木马！<pre>{$content}</pre>");
            }
            if(preg_match("#[@][$][_0-9a-z]+[\s]*[(][\s\S]*[)]#iU", " {$content}") == TRUE) {
                $content = dede_htmlspecialchars($content);
                die("DedeCMS提示：当前上传的文件中存在木马！<pre>{$content}</pre>");
            }
            if(preg_match("#[`][\s\S]*[`]#i", " {$content}") == TRUE) {
                $content = dede_htmlspecialchars($content);
                die("DedeCMS提示：当前上传的文件中存在后门！<pre>{$content}</pre>");
            }
            if(preg_match("#['\"][\^]['\"]#i", " {$content}") == TRUE) {
                $content = dede_htmlspecialchars($content);
                die("DedeCMS提示：当前上传的文件中存在后门！<pre>{$content}</pre>");
            }
        }
    }
}
?>