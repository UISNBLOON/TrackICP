<?php
// 数据库初始化脚本
// 安全检查：如果系统已安装，禁止访问
if (file_exists('.installed')) {
    die('系统已安装。数据库初始化已被禁用。');
}

// 正确加载配置
$config = include 'config.php';
if (!$config || !is_array($config)) {
    die('配置文件加载失败');
}

// 数据库连接函数
function getDatabaseConnection() {
    global $config;
    try {
        if ($config['database_type'] === 'mysql') {
            $dsn = "mysql:host={$config['database_config']['host']};port={$config['database_config']['port']};dbname={$config['database_config']['name']};charset=utf8mb4";
            return new PDO($dsn, $config['database_config']['user'], $config['database_config']['password']);
        } else if ($config['database_type'] === 'sqlite') {
            $dsn = "sqlite:{$config['database_config']['path']}";
            return new PDO($dsn);
        }
    } catch (PDOException $e) {
        die('数据库连接失败: ' . $e->getMessage());
    }
}

// 连接数据库
$pdo = getDatabaseConnection();

// 根据数据库类型选择合适的自增语法
$autoIncrement = $config['database_type'] === 'mysql' ? 'AUTO_INCREMENT' : 'AUTOINCREMENT';

$queries = [
    // 创建管理员表
    "CREATE TABLE IF NOT EXISTS admins (
        id INTEGER PRIMARY KEY $autoIncrement,
        username VARCHAR(50) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",

    // 创建网站信息表
    "CREATE TABLE IF NOT EXISTS site_info (
        id INTEGER PRIMARY KEY $autoIncrement,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",

    // 创建备案申请表
    "CREATE TABLE IF NOT EXISTS registrations (
        id INTEGER PRIMARY KEY $autoIncrement,
        website_name VARCHAR(255) NOT NULL,
        website_url VARCHAR(255) NOT NULL,
        contact_person VARCHAR(100) NOT NULL,
        contact_email VARCHAR(255) NOT NULL,
        contact_phone VARCHAR(255) NOT NULL,
        website_category VARCHAR(100) NOT NULL,
        website_description TEXT NOT NULL,
        status VARCHAR(20) DEFAULT 'pending',
        reason TEXT,
        registration_number VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        processed_at TIMESTAMP
    )"
];

// 执行SQL语句
try {
    foreach ($queries as $query) {
        $pdo->exec($query);
    }
    echo "数据库表结构初始化完成<br>";
} catch (PDOException $e) {
    die('创建表结构失败: ' . $e->getMessage());
}
?>