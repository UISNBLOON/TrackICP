<?php
// 检查是否已登录
if (!isset($_COOKIE['admin_logged_in']) || $_COOKIE['admin_logged_in'] !== 'true') {
    header('Location: admin_login.php');
    exit;
}

// 检查请求参数
if (!isset($_GET['id']) || !isset($_GET['action'])) {
    die('无效的请求');
}

$registrationId = $_GET['id'];
$action = $_GET['action']; // 'approve' 或 'reject'
$file = 'registrations.txt';
$tempFile = 'registrations_temp.txt';

// 验证操作类型
if ($action !== 'approve' && $action !== 'reject') {
    die('无效的操作类型');
}

// 读取并更新备案数据
if (file_exists($file)) {
    $lines = file($file, FILE_IGNORE_NEW_LINES);
    $found = false;
    $output = [];

    foreach ($lines as $line) {
        $data = json_decode($line, true);
        if ($data && $data['registration_id'] === $registrationId) {
            // 更新状态和处理日期
            $data['status'] = $action === 'approve' ? 'approved' : 'rejected';
            $data['processing_date'] = date('Y-m-d H:i:s');
            $found = true;
        }
        $output[] = json_encode($data);
    }

    if ($found) {
        // 写入更新后的数据
        file_put_contents($tempFile, implode(PHP_EOL, $output));
        rename($tempFile, $file);

        // 重定向回控制面板
        header('Location: admin_dashboard.php?status=success');
        exit;
    } else {
        die('未找到该备案申请');
    }
} else {
    die('备案数据文件不存在');
}
?>