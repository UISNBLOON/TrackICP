<?php
// 检查是否已安装（通过锁文件）
if (file_exists('.installed')) {
    die('系统已安装。如需重新安装，请删除 .installed 文件。');
}

// 检查是否已安装，允许通过?force=1参数强制进入安装
if (file_exists('config.php') && (!isset($_GET['force']) || $_GET['force'] !== '1')) {
    header('Location: index.php');
    exit;
}

// 处理表单提交
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 验证网站信息
    $site_name = trim($_POST['site_name']);
    $site_description = trim($_POST['site_description']);

    if (empty($site_name)) {
        $errors[] = '网站名称不能为空';
    }

    // 验证数据库配置
    $database_type = $_POST['database_type'];
    $db_config = [];

    if ($database_type === 'mysql') {
        $db_config['host'] = trim($_POST['db_host']);
        $db_config['port'] = trim($_POST['db_port']);
        $db_config['name'] = trim($_POST['db_name']);
        $db_config['user'] = trim($_POST['db_user']);
        $db_config['password'] = trim($_POST['db_password']);

        if (empty($db_config['name'])) {
            $errors[] = '数据库名称不能为空';
        }
        if (empty($db_config['user'])) {
            $errors[] = '数据库用户名不能为空';
        }
    } else if ($database_type === 'sqlite') {
        $db_config['path'] = trim($_POST['sqlite_path']);
        // 确保目录存在
        $dir = dirname($db_config['path']);
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            $errors[] = '无法创建SQLite数据库目录';
        }
    }

    // 验证管理员账户信息
    $admin_username = trim($_POST['admin_username']);
    $admin_password = trim($_POST['admin_password']);
    $admin_password_confirm = trim($_POST['admin_password_confirm']);

    if (empty($admin_username)) {
        $errors[] = '管理员用户名不能为空';
    }

    if (empty($admin_password)) {
        $errors[] = '管理员密码不能为空';
    } elseif (strlen($admin_password) < 6) {
        $errors[] = '管理员密码长度不能少于6位';
    }

    if ($admin_password !== $admin_password_confirm) {
        $errors[] = '两次输入的密码不一致';
    }

    // 如果没有错误，创建配置文件
    if (empty($errors)) {
        // 生成密码哈希
        $password_hash = password_hash($admin_password, PASSWORD_DEFAULT);

        // 连接数据库并存储管理员信息和网站配置
        try {
            if ($database_type === 'mysql') {
                // MySQL连接
                $dsn = "mysql:host={$db_config['host']};port={$db_config['port']};dbname={$db_config['name']};charset=utf8mb4";
                $pdo = new PDO($dsn, $db_config['user'], $db_config['password']);
            } else if ($database_type === 'sqlite') {
                // SQLite连接
                $dsn = "sqlite:{$db_config['path']}";
                $pdo = new PDO($dsn);
            }

            // 设置错误模式
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // 根据数据库类型选择自增关键字和整数类型
            if ($database_type === 'mysql') {
                $auto_increment = 'AUTO_INCREMENT';
                $int_type = 'INT';
            } else {
                $auto_increment = 'AUTOINCREMENT';
                $int_type = 'INTEGER';
            }

            // 创建管理员表
            $pdo->exec("CREATE TABLE IF NOT EXISTS admins (
                id $int_type PRIMARY KEY $auto_increment,
                username VARCHAR(50) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");

            // 创建网站信息表
            $pdo->exec("CREATE TABLE IF NOT EXISTS site_info (
                id $int_type PRIMARY KEY $auto_increment,
                name VARCHAR(100) NOT NULL,
                description TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");

            // 创建备案申请表
            $pdo->exec("CREATE TABLE IF NOT EXISTS registrations (
                id $int_type PRIMARY KEY $auto_increment,
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
            )");

            // 插入管理员信息
            $stmt = $pdo->prepare("INSERT INTO admins (username, password_hash) VALUES (?, ?)");
            $stmt->execute([$admin_username, $password_hash]);

            // 插入网站信息
            $stmt = $pdo->prepare("INSERT INTO site_info (name, description) VALUES (?, ?)");
            $stmt->execute([$site_name, $site_description]);

            // 创建配置文件内容（不包含明文密码）
            $config_content = <<<EOT
<?php
/**
 * 网站备案系统配置文件
 * 安装时间: " . date('Y-m-d H:i:s') . "
 */

return [
    // 网站基本信息
    'site_name' => '$site_name',
    'site_description' => '$site_description',

    // 数据库配置
    'database_type' => '$database_type',
    'database_config' => [
EOT;

            // 添加数据库特定配置
            if ($database_type === 'mysql') {
                $config_content .= <<<EOT
        'host' => '{$db_config['host']}',
        'port' => '{$db_config['port']}',
        'name' => '{$db_config['name']}',
        'user' => '{$db_config['user']}',
        'password' => '{$db_config['password']}'
EOT;
            } else if ($database_type === 'sqlite') {
                $config_content .= <<<EOT
        'path' => '{$db_config['path']}'
EOT;
            }

            $config_content .= <<<EOT
    ],

    // 邮件配置（请在系统设置中配置）
    'email' => [
        'smtp_host' => '',
        'smtp_port' => 465,
        'smtp_username' => '',
        'smtp_password' => '',
        'smtp_encryption' => 'ssl',
        'from_email' => '',
        'from_name' => '网站备案系统'
    ]
];
EOT;

            // 写入配置文件
            if (file_put_contents('config.php', $config_content)) {
                // 创建数据存储目录
                if (!is_dir('data')) {
                    mkdir('data', 0755);
                }

                // 创建安装锁文件
                file_put_contents('.installed', date('Y-m-d H:i:s'));
                
                // 尝试删除安装文件
                @unlink(__FILE__);
                
                // 如果存在db_init.php，也删除它
                @unlink('db_init.php');

                // 安装完成，显示提示页面
                echo '<!DOCTYPE html>
                <html lang="zh-CN">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>安装完成</title>
                    <style>
                        body {
                            font-family: \'ZD\', sans-serif;
                            line-height: 1.6;
                            color: #333;
                            background-color: #f5f5f5;
                            padding: 20px;
                            text-align: center;
                        }
                        .container {
                            max-width: 600px;
                            margin: 50px auto;
                            background-color: #fff;
                            border-radius: 8px;
                            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
                            padding: 30px;
                        }
                        .warning {
                            color: #d9534f;
                            font-weight: bold;
                            margin-bottom: 20px;
                        }
                        .btn {
                            display: inline-block;
                            background-color: #4CAF50;
                            color: white;
                            padding: 12px 25px;
                            border-radius: 4px;
                            text-decoration: none;
                            font-weight: bold;
                            transition: background-color 0.3s;
                            margin-top: 20px;
                        }
                        .btn:hover {
                            background-color: #45a049;
                        }
                        .permissions {
                            text-align: left;
                            background: #f9f9f9;
                            padding: 15px;
                            border-radius: 5px;
                            margin: 20px 0;
                        }
                        .permissions h3 {
                            margin-top: 0;
                        }
                        .permissions code {
                            background: #eee;
                            padding: 2px 5px;
                            border-radius: 3px;
                        }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <h1>安装完成！</h1>
                        <div class="warning">
                            <p>重要：请立即设置正确的文件权限！</p>
                        </div>
                        <div class="permissions">
                            <h3>请在服务器上执行以下命令：</h3>
                            <p>1. 设置目录权限：</p>
                            <p><code>chmod 755 /path/to/your/site</code></p>
                            <p><code>chmod 750 /path/to/your/site/data</code></p>
                            <p><code>chmod 640 /path/to/your/site/config.php</code></p>
                            <p><code>chmod 640 /path/to/your/site/.htaccess</code></p>
                            <p><code>chmod 640 /path/to/your/site/.installed</code></p>
                            <br>
                            <p>2. 设置文件所有者（假设Web服务器用户为www-data）：</p>
                            <p><code>chown -R your-user:www-data /path/to/your/site</code></p>
                            <br>
                            <p>3. 如果install.php和db_init.php未自动删除，请手动删除：</p>
                            <p><code>rm -f /path/to/your/site/install.php</code></p>
                            <p><code>rm -f /path/to/your/site/db_init.php</code></p>
                        </div>
                        <p>管理员账户已创建：<strong>' . htmlspecialchars($admin_username) . '</strong></p>
                        <p>请妥善保管您的登录凭据。</p>
                        <a href="index.php" class="btn">前往首页</a>
                    </div>
                </body>
                </html>';
                exit;
            } else {
                $errors[] = '创建配置文件失败，请检查目录权限';
            }

        } catch (PDOException $e) {
            $errors[] = '数据库连接或操作失败: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>网站安装</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'ZD', sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f5f5;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }

        h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }

        input[type="text"],
        input[type="password"],
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }

        textarea {
            height: 100px;
            resize: vertical;
        }

        .btn {
            display: block;
            width: 100%;
            padding: 12px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .btn:hover {
            background-color: #45a049;
        }

        .error-message {
            color: #d9534f;
            background-color: #f2dede;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .success-message {
            color: #5cb85c;
            background-color: #dff0d8;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .installation-steps {
            margin-bottom: 30px;
            text-align: center;
        }

        .step {
            display: inline-block;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: #e0e0e0;
            color: #777;
            line-height: 30px;
            margin: 0 10px;
        }

        .step.active {
            background-color: #4CAF50;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>网站安装向导</h1>

        <div class="installation-steps">
            <div class="step active">1</div>
            <div class="step">2</div>
            <div class="step">3</div>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="error-message">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="install.php">
            <div class="form-group">
                <label for="site_name">网站名称</label>
                <input type="text" id="site_name" name="site_name" required value="<?php echo isset($_POST['site_name']) ? htmlspecialchars($_POST['site_name']) : 'ICP备案管理系统'; ?>">
            </div>

            <div class="form-group">
                <label for="site_description">网站描述</label>
                <textarea id="site_description" name="site_description"><?php echo isset($_POST['site_description']) ? htmlspecialchars($_POST['site_description']) : 'ICP备案管理系统，用于管理网站备案信息'; ?></textarea>
            </div>

            <div class="form-group">
                <label for="database_type">数据库类型</label>
                <select id="database_type" name="database_type" required>
                    <option value="mysql" <?php echo isset($_POST['database_type']) && $_POST['database_type'] === 'mysql' ? 'selected' : ''; ?>>MySQL</option>
                    <option value="sqlite" <?php echo isset($_POST['database_type']) && $_POST['database_type'] === 'sqlite' ? 'selected' : ''; ?>>SQLite</option>
                </select>
            </div>

            <div id="mysql_config" style="<?php echo isset($_POST['database_type']) && $_POST['database_type'] === 'mysql' ? 'display:block' : 'display:none'; ?>">
                <div class="form-group">
                    <label for="db_host">数据库主机</label>
                    <input type="text" id="db_host" name="db_host" value="<?php echo isset($_POST['db_host']) ? htmlspecialchars($_POST['db_host']) : 'localhost'; ?>">
                </div>
                <div class="form-group">
                    <label for="db_port">数据库端口</label>
                    <input type="text" id="db_port" name="db_port" value="<?php echo isset($_POST['db_port']) ? htmlspecialchars($_POST['db_port']) : '3306'; ?>">
                </div>
                <div class="form-group">
                    <label for="db_name">数据库名称</label>
                    <input type="text" id="db_name" name="db_name" value="<?php echo isset($_POST['db_name']) ? htmlspecialchars($_POST['db_name']) : 'icp_system'; ?>">
                </div>
                <div class="form-group">
                    <label for="db_user">数据库用户名</label>
                    <input type="text" id="db_user" name="db_user" value="<?php echo isset($_POST['db_user']) ? htmlspecialchars($_POST['db_user']) : 'root'; ?>">
                </div>
                <div class="form-group">
                    <label for="db_password">数据库密码</label>
                    <input type="password" id="db_password" name="db_password" value="<?php echo isset($_POST['db_password']) ? htmlspecialchars($_POST['db_password']) : ''; ?>">
                </div>
            </div>

            <div id="sqlite_config" style="<?php echo isset($_POST['database_type']) && $_POST['database_type'] === 'sqlite' ? 'display:block' : 'display:none'; ?>">
                <div class="form-group">
                    <label for="sqlite_path">SQLite 数据库文件路径</label>
                    <input type="text" id="sqlite_path" name="sqlite_path" value="<?php echo isset($_POST['sqlite_path']) ? htmlspecialchars($_POST['sqlite_path']) : 'data/icp_system.db'; ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="admin_username">管理员用户名</label>
                <input type="text" id="admin_username" name="admin_username" required value="<?php echo isset($_POST['admin_username']) ? htmlspecialchars($_POST['admin_username']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="admin_password">管理员密码</label>
                <input type="password" id="admin_password" name="admin_password" required>
            </div>

            <div class="form-group">
                <label for="admin_password_confirm">确认管理员密码</label>
                <input type="password" id="admin_password_confirm" name="admin_password_confirm" required>
            </div>

            <button type="submit" class="btn">安装</button>
        </form>
    </div>
<script>
    // 处理数据库类型选择
    document.addEventListener('DOMContentLoaded', function() {
        const databaseType = document.getElementById('database_type');
        const mysqlConfig = document.getElementById('mysql_config');
        const sqliteConfig = document.getElementById('sqlite_config');

        function updateDatabaseConfig() {
            if (databaseType.value === 'mysql') {
                mysqlConfig.style.display = 'block';
                sqliteConfig.style.display = 'none';
            } else {
                mysqlConfig.style.display = 'none';
                sqliteConfig.style.display = 'block';
            }
        }

        // 初始化显示
        updateDatabaseConfig();

        // 添加事件监听
        databaseType.addEventListener('change', updateDatabaseConfig);
    });
</script>
</body>
</html>