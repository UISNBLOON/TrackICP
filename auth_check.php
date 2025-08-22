<?php
// 统一身份验证和安全检查模块
session_start();

// 验证管理员登录状态
function checkAdminAuth() {
    // 检查 session 而不是 cookie
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        header('Location: /admin/admin_login.php');
        exit;
    }
    
    // 检查会话超时（1小时）
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 3600)) {
        session_unset();
        session_destroy();
        header('Location: /admin/admin_login.php?timeout=1');
        exit;
    }
    
    $_SESSION['last_activity'] = time();
    
    // 重新生成会话ID以防止会话固定攻击
    if (!isset($_SESSION['regenerated'])) {
        session_regenerate_id(true);
        $_SESSION['regenerated'] = true;
    }
}

// 生成CSRF令牌
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// 验证CSRF令牌
function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        die('CSRF token validation failed');
    }
}
?>