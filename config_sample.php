<?php
// 网站配置文件示例
// 安装程序会根据您的输入生成实际的config.php文件
return [
    'installed' => false,
    'database_type' => 'mysql', // 或 'sqlite'
    'database_config' => [
        // MySQL配置
        'host' => 'localhost',
        'port' => '3306',
        'name' => 'icp_system',
        'user' => 'root',
        'password' => '',
        // SQLite配置
        // 'path' => 'data/icp_system.db'
    ],
    'site_name' => 'ICP备案管理系统',
    'site_description' => 'ICP备案管理系统，用于管理网站备案信息'
];