<?php
/**
 * 安装前环境检查脚本
 * 用于在安装前确认服务器环境是否满足要求
 */

echo '<!DOCTYPE html><html><head><title>安装前环境检查</title><style>body{font-family:Arial,sans-serif;max-width:800px;margin:0 auto;padding:20px;} .success{color:green;} .error{color:red;} .warning{color:orange;} table{border-collapse:collapse;width:100%;} th,td{border:1px solid #ddd;padding:8px;} th{background-color:#f2f2f2;}</style></head><body>';
echo '<h1>安装前环境检查</h1>';

echo '<p>本脚本将检查您的服务器环境是否满足备案系统的安装要求。请在继续安装前确保所有必要条件都已满足。</p>';

// 检查PHP版本
$requiredPhpVersion = '7.4.0';
echo '<h2>PHP环境</h2>';
echo '<p>当前PHP版本: ' . phpversion() . '</p>';
if (version_compare(phpversion(), $requiredPhpVersion, '>=')) {
    echo '<p class="success">PHP版本满足要求 (需要 ' . $requiredPhpVersion . ' 或更高版本)</p>';
} else {
    echo '<p class="error">PHP版本不满足要求! 需要 ' . $requiredPhpVersion . ' 或更高版本，请升级PHP</p>';
}

// 检查必要扩展
$requiredExtensions = ['pdo', 'json', 'filter', 'mbstring']; // 添加mbstring到必需扩展
$missingExtensions = [];
echo '<h3>必要扩展</h3>';
echo '<table>';
echo '<tr><th>扩展</th><th>状态</th></tr>';
foreach ($requiredExtensions as $ext) {
    if (extension_loaded($ext)) {
        $status = '<span class="success">已加载</span>';
    } else {
        $status = '<span class="error">未加载</span>';
        $missingExtensions[] = $ext;
    }
    echo '<tr><td>' . $ext . '</td><td>' . $status . '</td></tr>';
}
echo '</table>';
if (!empty($missingExtensions)) {
    echo '<p class="error">请安装以下缺失的扩展: ' . implode(', ', $missingExtensions) . '</p>';
}

// 检查配置文件
$configFile = 'config.php';
$configSampleFile = 'config_sample.php';
echo '<h2>配置文件检查</h2>';
if (file_exists($configFile)) {
    echo '<p class="warning">已找到配置文件: ' . $configFile . '</p>';
    echo '<p>如果您是首次安装，请先删除此文件或重命名为其他名称</p>';
} else {
    echo '<p class="success">未找到配置文件: ' . $configFile . ' (首次安装预期状态)</p>';
    if (file_exists($configSampleFile)) {
        echo '<p class="success">找到配置示例文件: ' . $configSampleFile . '</p>';
    } else {
        echo '<p class="error">未找到配置示例文件: ' . $configSampleFile . '，请确保文件存在</p>';
    }
}

// 检查数据库文件或配置
$databaseFile = 'database.db';
echo '<h2>数据库准备检查</h2>';
if (file_exists($databaseFile)) {
    echo '<p class="warning">已找到数据库文件: ' . $databaseFile . '</p>';
    echo '<p>如果您是首次安装，这可能是旧版本的数据文件，请考虑备份并删除</p>';
} else {
    echo '<p class="success">未找到数据库文件: ' . $databaseFile . ' (首次安装预期状态)</p>';
    // 检查目录权限
    $dir = '.';
    if (is_writable($dir)) {
        echo '<p class="success">当前目录可写入，能够创建数据库文件</p>';
    } else {
        echo '<p class="error">当前目录不可写入，无法创建数据库文件，请更改目录权限</p>';
    }
}

// 检查安装所需的其他文件
$requiredFiles = ['db_init.php', 'install.php', 'register.php', 'admin_login.php', 'config_sample.php'];
$missingFiles = [];
echo '<h2>必要文件检查</h2>';
echo '<table>';
echo '<tr><th>文件</th><th>状态</th></tr>';
foreach ($requiredFiles as $file) {
    if (file_exists($file)) {
        $status = '<span class="success">存在</span>';
    } else {
        $status = '<span class="error">缺失</span>';
        $missingFiles[] = $file;
    }
    echo '<tr><td>' . $file . '</td><td>' . $status . '</td></tr>';
}
echo '</table>';
if (!empty($missingFiles)) {
    echo '<p class="error">请确保以下必要文件存在: ' . implode(', ', $missingFiles) . '</p>';
}

// 显示安装前指导

echo '<h2>安装前准备指导</h2>';
echo '<ul>';
echo '<li>确保PHP版本 >= 7.4.0</li>';
echo '<li>确保已安装所有必要扩展: ' . implode(', ', $requiredExtensions) . '</li>';
echo '<li>确保当前目录具有写入权限</li>';
echo '<li>确保所有必要文件都已上传到服务器</li>';
echo '<li>首次安装前请勿创建config.php文件，安装程序将引导您完成配置</li>';
echo '</ul>';

echo '<h2>检查结果</h2>';
if (empty($missingExtensions) && version_compare(phpversion(), $requiredPhpVersion, '>=') && empty($missingFiles) && is_writable('.')) {
    echo '<p class="success">恭喜！您的服务器环境满足安装要求。请访问 <a href="install.php">install.php</a> 开始安装。</p>';
} else {
    echo '<p class="error">您的服务器环境不满足安装要求，请解决上述问题后再尝试安装。</p>';
}

echo '</body></html>';
?>