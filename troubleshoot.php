<?php
/**
 * 故障排查脚本
 * 用于获取详细错误信息
 */

echo '<!DOCTYPE html><html><head><title>故障排查</title><style>body{font-family:Arial,sans-serif;max-width:800px;margin:0 auto;padding:20px;} .success{color:green;} .error{color:red;} .warning{color:orange;} pre{background:#f5f5f5;padding:10px;border-radius:5px;overflow-x:auto;}</style></head><body>';
echo '<h1>故障排查信息</h1>';

// 检查PHP错误日志位置
 echo '<h2>PHP错误日志</h2>';
$errorLog = ini_get('error_log');
if ($errorLog) {
    echo '<p>错误日志位置: ' . $errorLog . '</p>';
    if (file_exists($errorLog) && is_readable($errorLog)) {
        echo '<h3>最新错误日志内容</h3>';
        $logs = tail($errorLog, 50); // 获取最后50行
        echo '<pre>' . htmlspecialchars($logs) . '</pre>';
    } else {
        echo '<p class="warning">无法读取错误日志文件</p>';
    }
} else {
    echo '<p class="warning">未设置PHP错误日志</p>';
    echo '<p>建议在php.ini中设置error_log指令</p>';
}

// 检查关键文件权限
 echo '<h2>关键文件权限检查</h2>';
$filesToCheck = [
    __DIR__ . '/config.php',
    __DIR__ . '/database.db',
    __DIR__ . '/db_init.php',
    __DIR__ . '/email_utils.php',
    __DIR__ . '/admin_login.php',
    __DIR__ . '/server_check.php'
];

echo '<table border="1"><tr><th>文件路径</th><th>存在性</th><th>可读性</th><th>可写性</th></tr>';
foreach ($filesToCheck as $file) {
    $exists = file_exists($file) ? '<span class="success">存在</span>' : '<span class="error">不存在</span>';
    $readable = is_readable($file) ? '<span class="success">可读</span>' : '<span class="error">不可读</span>';
    $writable = is_writable($file) ? '<span class="success">可写</span>' : '<span class="warning">不可写</span>';
    echo '<tr><td>' . $file . '</td><td>' . $exists . '</td><td>' . $readable . '</td><td>' . $writable . '</td></tr>';
}
echo '</table>';

// 检查当前用户
 echo '<h2>当前用户信息</h2>';
if (function_exists('posix_getpwuid') && function_exists('posix_geteuid')) {
    $userInfo = posix_getpwuid(posix_geteuid());
    echo '<p>当前用户: ' . $userInfo['name'] . ' (UID: ' . $userInfo['uid'] . ')</p>';
} else {
    echo '<p class="warning">无法获取用户信息</p>';
}

// 数据库连接测试
 echo '<h2>数据库连接详细测试</h2>';
if (file_exists('config.php')) {
    $config = include 'config.php';
    try {
        if ($config['database_type'] === 'mysql') {
            echo '<p>尝试连接MySQL数据库...</p>';
            $dsn = 'mysql:host=' . $config['database_config']['host'] . ';port=' . $config['database_config']['port'] . ';charset=utf8mb4';
            // 首先测试连接，不指定数据库
            $pdo = new PDO($dsn, $config['database_config']['user'], $config['database_config']['password']);
            echo '<p class="success">MySQL服务器连接成功</p>';
            // 测试数据库是否存在
            try {
                $pdo->query('USE ' . $config['database_config']['name']);
                echo '<p class="success">数据库 ' . $config['database_config']['name'] . ' 存在</p>';
            } catch (PDOException $e) {
                echo '<p class="error">数据库不存在: ' . $e->getMessage() . '</p>';
                echo '<p>尝试创建数据库...</p>';
                try {
                    $pdo->query('CREATE DATABASE ' . $config['database_config']['name']);
                    echo '<p class="success">数据库创建成功</p>';
                    $pdo->query('USE ' . $config['database_config']['name']);
                } catch (PDOException $e) {
                    echo '<p class="error">创建数据库失败: ' . $e->getMessage() . '</p>';
                }
            }
        } else if ($config['database_type'] === 'sqlite') {
            echo '<p>尝试连接SQLite数据库...</p>';
            $dsn = 'sqlite:' . $config['database_config']['path'];
            $pdo = new PDO($dsn);
            echo '<p class="success">SQLite数据库连接成功</p>';
            // 检查数据库表
            $tables = ['admins', 'site_info', 'registrations'];
            echo '<h3>检查数据库表</h3>';
            foreach ($tables as $table) {
                try {
                    $stmt = $pdo->query('SELECT COUNT(*) FROM ' . $table);
                    echo '<p class="success">表 ' . $table . ' 存在</p>';
                } catch (PDOException $e) {
                    echo '<p class="error">表不存在: ' . $table . ' - ' . $e->getMessage() . '</p>';
                }
            }
        }
    } catch (PDOException $e) {
        echo '<p class="error">数据库连接失败: ' . $e->getMessage() . '</p>';
        echo '<p>错误代码: ' . $e->getCode() . '</p>';
    }
} else {
    echo '<p class="error">未找到配置文件: config.php</p>';
}

// 帮助函数：获取文件最后几行
function tail($filename, $lines = 10) {
    $handle = fopen($filename, 'r');
    if (!$handle) return '无法打开文件';
    
    $buffer = [];
    $lineCount = 0;
    
    // 从文件末尾开始读取
    fseek($handle, 0, SEEK_END);
    $pos = ftell($handle);
    
    // 向后读取直到找到足够的行数
    while ($pos > 0 && $lineCount < $lines) {
        $pos--;
        fseek($handle, $pos);
        $char = fgetc($handle);
        
        if ($char === "\n" && $pos > 0) {
            $lineCount++;
        }
    }
    
    // 读取找到的行
    while (($line = fgets($handle)) !== false) {
        $buffer[] = $line;
    }
    
    fclose($handle);
    return implode('', $buffer);
}

echo '</body></html>';
?>