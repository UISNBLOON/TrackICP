<?php
// 管理员拒绝备案申请

// 检查是否已登录
if (!isset($_COOKIE['admin_logged_in']) || $_COOKIE['admin_logged_in'] !== 'true') {
    header('Location: admin_login.php');
    exit;
}

// 检查是否提供了申请ID
if (!isset($_POST['registration_id'])) {
    die('缺少备案申请ID');
}

$registrationId = $_POST['registration_id'];
$reason = $_POST['reason'] ?? '';

if (empty($reason)) {
    die('请提供拒绝原因');
}

// 加载配置
$config = include 'config.php';

// 初始化数据库连接
require_once 'db_init.php';
require_once 'email_utils.php';

// 更新备案申请状态为拒绝
try {
    // 开始事务
    $pdo->beginTransaction();

    // 获取备案信息
    $stmt = $pdo->prepare("SELECT * FROM registrations WHERE id = ?");
    $stmt->execute([$registrationId]);
    $registration = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$registration) {
        die('未找到该备案申请');
    }

    // 更新状态
    $stmt = $pdo->prepare("UPDATE registrations SET status = 'rejected', processed_at = NOW(), reason = ? WHERE id = ?");
    $stmt->execute([$reason, $registrationId]);

    // 提交事务
    $pdo->commit();

    // 发送邮件通知
    try {
        $emailUtils = new EmailUtils($config);
        $emailUtils->sendRejectionEmail($registration);
    } catch (Exception $e) {
        // 邮件发送失败，记录日志但不影响主流程
        error_log('发送拒绝通知邮件失败: ' . $e->getMessage());
    }

    // 重定向回管理员面板
    header('Location: admin_dashboard.php?success=1&message=备案申请已拒绝');
    exit;
} catch (PDOException $e) {
    // 回滚事务
    $pdo->rollBack();
    die('更新备案申请状态失败: ' . $e->getMessage());
}
?>