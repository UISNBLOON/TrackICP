<?php
// 网站配置文件
// 基于config_sample.php创建
return [
    'installed' => true,
    'database_type' => 'sqlite', // 使用SQLite数据库
    'database_config' => [
        // SQLite配置
        'path' => 'database.db'
    ],
    'site_name' => '二次元网站备案系统',
    'site_description' => '二次元网站ICP备案管理平台',
    'admin' => [
        'username' => 'admin',
        'password' => 'admin123'
    ],
    'email_config' => [
        'smtp_host' => '',
        'smtp_port' => '',
        'smtp_user' => '',
        'smtp_password' => '',
        'from_email' => ''
    ]
];
?>