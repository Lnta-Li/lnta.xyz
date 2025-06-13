<?php
/**
 * 文档digg处理ajax文件
 * 功能：处理文章点赞(顶)和点踩(踩)的AJAX请求
 * 工作流程：
 * 1. 接收前端传递的action(good/bad)、文章ID和状态参数
 * 2. 验证参数有效性
 * 3. 根据系统配置(cfg_digg_update)决定使用缓存模式还是直接更新模式
 * 4. 更新数据库中的点赞/点踩计数
 * 5. 计算并返回点赞/点踩百分比
 *
 * @version        $Id: digg_ajax.php 2 13:00 2011/11/25 $
 * @package        DedeCMS.Plus
 * @founder        IT柏拉图, https://weibo.com/itprato
 * @author         DedeCMS团队
 * @copyright      Copyright (c) 2004 - 2024, 上海卓卓网络科技有限公司 (DesDev, Inc.)
 * @license        http://help.dedecms.com/usersguide/license.html
 * @link           http://www.dedecms.com
 */
require_once(dirname(__FILE__)."/../include/common.inc.php");

// 输入验证
$action = isset($action) ? trim($action) : '';  // 操作类型：good(点赞)或bad(点踩)
$id = empty($id) ? 0 : intval(preg_replace("/[^\d]/",'', $id));  // 文章ID，过滤非数字字符
$state = isset($state) ? intval($state) : 0;  // 用户状态：1(点赞), -1(点踩), 0(取消)

// 验证文章ID有效性
if($id < 1) {
    exit(json_encode(['error' => 'Invalid article ID']));
}

// 验证action参数，必须是good或bad
if($action && !in_array($action, ['good', 'bad'])) {
    exit(json_encode(['error' => 'Invalid action']));
}

// 验证state参数，必须是-1、0或1
if(!in_array($state, [-1, 0, 1])) {
    exit(json_encode(['error' => 'Invalid state']));
}

// 加载缓存助手
helper('cache');

// 设置缓存相关参数
$maintable = '#@__archives';  // 主表名
$prefix = 'diggCache';       // 缓存前缀
$key = 'aid-'.$id;           // 缓存键，基于文章ID

// 获取缓存数据
$row = GetCache($prefix, $key);

/**
 * 缓存无效或直接更新模式处理
 * 当缓存不存在或系统配置为直接更新模式(cfg_digg_update=0)时执行
 */
if(!is_array($row) || $cfg_digg_update==0) {
    // 从数据库获取文章当前的点赞/点踩数据
    $row = $dsql->GetOne("SELECT goodpost,badpost,scores FROM `$maintable` WHERE id='$id'");
    if(!$row) {
        exit(json_encode(['error' => 'Article not found']));
    }
    
    // 直接更新模式处理
    if($cfg_digg_update == 0) {
        // 根据用户操作状态更新数据
        if($state == 1) {  // 点赞操作
            $row['goodpost']++;  // 增加点赞计数
            // 更新数据库：增加分数、点赞数，并更新最后操作时间
            $dsql->ExecuteNoneQuery("UPDATE `$maintable` SET scores = scores + {$cfg_caicai_add}, goodpost=goodpost+1, lastpost=".time()." WHERE id='$id'");
        } else if($state == -1) {  // 点踩操作
            $row['badpost']++;  // 增加点踩计数
            // 更新数据库：减少分数、增加点踩数，并更新最后操作时间
            $dsql->ExecuteNoneQuery("UPDATE `$maintable` SET scores = scores - {$cfg_caicai_sub}, badpost=badpost+1, lastpost=".time()." WHERE id='$id'");
        } else if($state == 0) {  // 取消操作
            if($action == 'good') {
                $row['goodpost']--;  // 减少点赞计数
                // 更新数据库：减少分数、点赞数，并更新最后操作时间
                $dsql->ExecuteNoneQuery("UPDATE `$maintable` SET scores = scores - {$cfg_caicai_add}, goodpost=goodpost-1, lastpost=".time()." WHERE id='$id'");
            } else if($action == 'bad') {
                $row['badpost']--;  // 减少点踩计数
                // 更新数据库：增加分数、减少点踩数，并更新最后操作时间
                $dsql->ExecuteNoneQuery("UPDATE `$maintable` SET scores = scores + {$cfg_caicai_sub}, badpost=badpost-1, lastpost=".time()." WHERE id='$id'");
            }
        }
        // 删除缓存以确保数据一致性
        DelCache($prefix, $key);
    }
    // 设置新的缓存数据
    SetCache($prefix, $key, $row, 0);
} 
// 缓存模式
else {
    if($state == 1) {  // 点赞
        $row['goodpost']++;
        $row['scores'] += $cfg_caicai_sub;
        if($row['goodpost'] % $cfg_digg_update == 0) {
            $add_caicai_sub = $cfg_digg_update * $cfg_caicai_sub;
            $dsql->ExecuteNoneQuery("UPDATE `$maintable` SET scores = scores + {$add_caicai_sub}, goodpost=goodpost+{$cfg_digg_update} WHERE id='$id'");
            DelCache($prefix, $key);
        }
    } else if($state == -1) {  // 踩
        $row['badpost']++;
        $row['scores'] -= $cfg_caicai_sub;
        if($row['badpost'] % $cfg_digg_update == 0) {
            $add_caicai_sub = $cfg_digg_update * $cfg_caicai_sub;
            $dsql->ExecuteNoneQuery("UPDATE `$maintable` SET scores = scores - {$add_caicai_sub}, badpost=badpost+{$cfg_digg_update} WHERE id='$id'");
            DelCache($prefix, $key);
        }
    } else if($state == 0) {  // 取消操作
        if($action == 'good') {
            $row['goodpost']--;
            $row['scores'] -= $cfg_caicai_sub;
        } else if($action == 'bad') {
            $row['badpost']--;
            $row['scores'] += $cfg_caicai_sub;
        }
    }
    SetCache($prefix, $key, $row, 0);
}

// 计算点赞/点踩百分比
if($row['goodpost'] + $row['badpost'] == 0) {
    // 如果没有任何投票，百分比设为0
    $row['goodper'] = $row['badper'] = 0;
} else {
    // 计算点赞百分比(保留3位小数)
    $row['goodper'] = number_format($row['goodpost'] / ($row['goodpost'] + $row['badpost']), 3) * 100;
    // 点踩百分比 = 100% - 点赞百分比
    $row['badper'] = 100 - $row['goodper'];
}

// 格式化百分比数据，保留2位小数
$row['goodper'] = trim(sprintf("%4.2f", $row['goodper']));
$row['badper'] = trim(sprintf("%4.2f", $row['badper']));

// 返回数据
AjaxHead();
if($formurl == 'caicai') {
    echo $action == 'good' ? $row['goodpost'] : $row['badpost'];
} else {
    echo json_encode([
        'goodpost' => $row['goodpost'],
        'badpost' => $row['badpost'],
        'goodper' => $row['goodper'],
        'badper' => $row['badper'],
        'userState' => $state  // 返回用户当前状态
    ]);
}
exit();
