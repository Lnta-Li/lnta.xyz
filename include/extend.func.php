<?php
/**
 * 自定义函数库
 * 截取字符串并在超过长度时添加省略号
 * @param string $str 需要处理的字符串
 * @param int $length 截取长度
 * @return string 处理后的字符串
 */
function cutStr($str, $length = 30)
{
    if (mb_strlen($str, 'utf-8') > $length) {
        return mb_substr($str, 0, $length, 'utf-8') . '...';
    } else {
        return $str;
    }
}

function litimgurls($imgid=0)
{
    global $lit_imglist,$dsql;
    //获取附加表
    $row = $dsql->GetOne("SELECT c.addtable FROM #@__archives AS a LEFT JOIN #@__channeltype AS c 
                                                            ON a.channel=c.id where a.id='$imgid'");
    $addtable = trim($row['addtable']);
    
    //获取图片附加表imgurls字段内容进行处理
    $row = $dsql->GetOne("Select imgurls From `$addtable` where aid='$imgid'");
    
    //调用inc_channel_unit.php中ChannelUnit类
    $ChannelUnit = new ChannelUnit(2,$imgid);
    
    //调用ChannelUnit类中GetlitImgLinks方法处理缩略图
    $lit_imglist = $ChannelUnit->GetlitImgLinks($row['imgurls']);
    
    //返回结果
    return $lit_imglist;
}

function translate_text($text, $from = 'auto', $to = 'en')
{
    if(empty($text)) {
        return '';
    }

    $api_url = 'http://api.fanyi.baidu.com/api/trans/vip/translate';
    global $translate_api_id, $translate_api_key;
    
    $app_id = $translate_api_id;
    $secret_key = $translate_api_key;

    $salt = rand(10000,99999);
    $sign = md5($app_id . $text . $salt . $secret_key);

    $args = array(
        'q' => $text,
        'appid' => $app_id,
        'salt' => $salt,
        'from' => $from,
        'to' => $to,
        'sign' => $sign
    );

    $url = $api_url . '?' . http_build_query($args);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    
    if(curl_errno($ch)) {
        error_log('Baidu Translate API Error: ' . curl_error($ch));
        curl_close($ch);
        return '';
    }
    
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    if(isset($result['error_code'])) {
        error_log('Baidu Translate API Error: ' . json_encode($result));
        return '';
    }
    
    if(isset($result['trans_result'][0]['dst'])) {
        return $result['trans_result'][0]['dst'];
    }
    
    return '';
}

// 自动检测语言并翻译
function auto_translate($text) {
    global $cfg_auto_translate;
    if ($cfg_auto_translate == 'N') {
        return '';
    }

    if(empty($text)) {
        return $text;
    }
    
    // 检测是否包含中文
    if (preg_match("/[\x{4e00}-\x{9fa5}]/u", $text)) {
        $translated = translate_text($text, 'zh', 'en');
        return ($translated !== $text) ? $translated : $text;
    } else {
        $translated = translate_text($text, 'en', 'zh');
        return ($translated !== $text) ? $translated : $text;
    }
}

/**
 * 生成小尺寸缩略图并返回路径
 * @param string $litpic 原缩略图路径
 * @param int $width 生成的小尺寸缩略图宽度，默认使用cfg_subpic_size配置
 * @param int $height 生成的小尺寸缩略图高度，默认使用cfg_subpic_size配置
 * @param int $aid 文章ID，用于绑定缩略图文件名和关联uploads表记录
 * @return string 小尺寸缩略图路径
 */
function createSubPic($litpic, $width = 0, $height = 0, $aid = 0)
{
    global $dsql, $cfg_subpic_size;
    
    // 如果未指定宽高，使用全局配置
    if($width == 0) $width = empty($cfg_subpic_size) ? 80 : $cfg_subpic_size;
    if($height == 0) $height = empty($cfg_subpic_size) ? 80 : $cfg_subpic_size;
    
    if(empty($litpic)) {
        return '';
    }
    
    // 检查原图是否存在
    $litpic = str_replace($GLOBALS['cfg_basehost'], '', $litpic);
    $fullLitpicPath = $GLOBALS['cfg_basedir'] . $litpic;
    
    if(!file_exists($fullLitpicPath)) {
        return '';
    }
    
    // 创建保存小尺寸缩略图的目录
    $subPicDir = $GLOBALS['cfg_basedir'] . '/uploads/subpic';
    if(!is_dir($subPicDir)) {
        mkdir($subPicDir, 0777, true);
    }
    
    // 生成子缩略图文件名（基于文章ID和宽高）
    $fileInfo = pathinfo($litpic);
    $fileExt = isset($fileInfo['extension']) ? $fileInfo['extension'] : 'jpg';
    
    if($aid > 0) {
        // 使用文档ID作为文件名前缀
        $fileName = 'subpic_' . $aid . '.' . $fileExt;
    } else {
        // 如果没有文档ID，使用哈希值
        $fileName = 'subpic_' . substr(md5($litpic), 0, 8) . '.' . $fileExt;
    }
    
    $subPicPath = '/uploads/subpic/' . $fileName;
    $fullSubPicPath = $GLOBALS['cfg_basedir'] . $subPicPath;
    
    // 如果文件已存在，直接返回路径
    if(file_exists($fullSubPicPath)) {
        return $subPicPath;
    }
    
    // 获取原图信息
    list($srcWidth, $srcHeight, $srcType) = getimagesize($fullLitpicPath);
    
    // 创建新图像
    $dst = imagecreatetruecolor($width, $height);
    
    // 根据原图类型创建图像资源
    switch($srcType) {
        case 1: // GIF
            $src = imagecreatefromgif($fullLitpicPath);
            break;
        case 2: // JPEG
            $src = imagecreatefromjpeg($fullLitpicPath);
            break;
        case 3: // PNG
            $src = imagecreatefrompng($fullLitpicPath);
            // 保留PNG透明度
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            break;
        case 18: // WEBP (PHP 7.1.0及以上支持)
            if(function_exists('imagecreatefromwebp')) {
                $src = imagecreatefromwebp($fullLitpicPath);
                // 保留WEBP透明度
                imagealphablending($dst, false);
                imagesavealpha($dst, true);
            } else {
                return '';
            }
            break;
        default:
            return '';
    }
    
    // 计算等比例缩放后的尺寸和裁切位置
    $ratio_orig = $srcWidth / $srcHeight;
    $ratio_target = $width / $height;
    
    if ($ratio_orig > $ratio_target) {
        // 原图较宽，高度缩放到目标高度，宽度按比例缩放，然后裁切多余宽度
        $temp_height = $height;
        $temp_width = ceil($height * $ratio_orig);
        $src_x = ceil(($temp_width - $width) / 2);
        $src_y = 0;
    } else {
        // 原图较高或相等，宽度缩放到目标宽度，高度按比例缩放，然后裁切多余高度
        $temp_width = $width;
        $temp_height = ceil($width / $ratio_orig);
        $src_x = 0;
        $src_y = ceil(($temp_height - $height) / 2);
    }
    
    // 创建临时画布进行等比例缩放
    $temp = imagecreatetruecolor($temp_width, $temp_height);
    
    // 对临时画布设置透明度处理
    if($srcType == 3 || $srcType == 18) {
        imagealphablending($temp, false);
        imagesavealpha($temp, true);
    }
    
    // 将原图等比例缩放到临时画布
    imagecopyresampled($temp, $src, 0, 0, 0, 0, $temp_width, $temp_height, $srcWidth, $srcHeight);
    
    // 将临时画布上的图像裁切到目标尺寸
    imagecopy($dst, $temp, 0, 0, $src_x, $src_y, $width, $height);
    
    // 释放临时资源
    imagedestroy($temp);
    
    // 保存缩略图
    switch($srcType) {
        case 1: // GIF
            imagegif($dst, $fullSubPicPath);
            break;
        case 2: // JPEG
            imagejpeg($dst, $fullSubPicPath, 70); // 质量为70
            break;
        case 3: // PNG
            imagepng($dst, $fullSubPicPath);
            break;
        case 18: // WEBP
            if(function_exists('imagewebp')) {
                imagewebp($dst, $fullSubPicPath, 70); // 质量为70
            }
            break;
    }
    
    // 释放资源
    imagedestroy($src);
    imagedestroy($dst);
    
    // 记录到uploads表中，便于管理和删除
    if($aid > 0) {
        // 获取文件大小
        $filesize = filesize($fullSubPicPath);
        $adminid = isset($GLOBALS['adminid']) ? $GLOBALS['adminid'] : 1;
        $title = "小尺寸缩略图(subpic_{$aid})";
        
        // 检查是否已经存在相同aid、url的记录
        $check_query = "SELECT `aid` FROM `#@__uploads` WHERE `arcid`='{$aid}' AND `url`='{$subPicPath}' LIMIT 0,1";
        $row = $dsql->GetOne($check_query);
        
        if(is_array($row)) {
            // 更新现有记录
            $update_query = "UPDATE `#@__uploads` SET 
                           `title`='{$title}',
                           `mediatype`='1',
                           `width`='{$width}',
                           `height`='{$height}',
                           `filesize`='{$filesize}',
                           `uptime`='".time()."'
                           WHERE `arcid`='{$aid}' AND `url`='{$subPicPath}'";
            $dsql->ExecuteNoneQuery($update_query);
        } else {
            // 插入新记录
            $insert_query = "INSERT INTO `#@__uploads`(title,url,mediatype,width,height,playtime,filesize,uptime,mid,arcid) 
                           VALUES('{$title}','{$subPicPath}','1','{$width}','{$height}','0','{$filesize}','".time()."','{$adminid}','{$aid}')";
            $dsql->ExecuteNoneQuery($insert_query);
        }
    }
    
    return $subPicPath;
}