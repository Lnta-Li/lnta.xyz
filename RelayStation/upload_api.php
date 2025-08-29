<?php
// 允许跨域请求
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// 检查请求方法是否为POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// 跳过API密钥验证（仅个人使用）


// 检查是否有文件上传
if (!isset($_FILES['image'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No image uploaded']);
    exit;
}

$image = $_FILES['image'];

// 检查文件是否有效
if ($image['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'Failed to upload image']);
    exit;
}

// 定义上传目录
$uploadDir = __DIR__ . '/image/';

// 确保目录存在
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// 删除十分钟之前的图片
$tenMinutesAgo = time() - 10 * 60; // 10分钟前的时间戳
$files = glob($uploadDir . '*');
foreach ($files as $file) {
    if (is_file($file) && filemtime($file) < $tenMinutesAgo) {
        unlink($file);
    }
}

// 生成唯一文件名
$fileName = uniqid() . '.' . pathinfo($image['name'], PATHINFO_EXTENSION);
$filePath = $uploadDir . $fileName;

// 移动上传文件
if (!move_uploaded_file($image['tmp_name'], $filePath)) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save image']);
    exit;
}

// 生成图片URL
$imageUrl = 'http://www.lnta.xyz/RelayStation/image/' . $fileName;

// 返回结果
http_response_code(200);
echo json_encode(['success' => true, 'image_url' => $imageUrl]);

exit;
?>