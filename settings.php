<?php
// 加载配置
$config = include 'config.php';

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

// 从数据库获取网站信息
$stmt = $pdo->query("SELECT name, description FROM site_info LIMIT 1");
$siteInfo = $stmt->fetch(PDO::FETCH_ASSOC);

// 如果找不到网站信息，使用配置文件中的默认值
if (!$siteInfo) {
    $siteInfo = [
        'name' => $config['site_name'] ?? '二次元网站备案系统',
        'description' => $config['site_description'] ?? '管理和审核网站备案申请'
    ];
}

// 从数据库获取邮件配置
$stmt = $pdo->query("SELECT * FROM email_config LIMIT 1");
$emailConfig = $stmt->fetch(PDO::FETCH_ASSOC);

// 如果找不到邮件配置，使用默认值
if (!$emailConfig) {
    $emailConfig = [
        'smtp_host' => '',
        'smtp_port' => 465,
        'smtp_username' => '',
        'smtp_password' => '',
        'smtp_encryption' => 'ssl',
        'from_email' => '',
        'from_name' => $siteInfo['name']
    ];
}

// 处理表单提交
$success = '';
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 处理站点设置
    $siteName = trim($_POST['site_name']);
    $siteDescription = trim($_POST['site_description']);

    // 处理邮件设置
    $smtpHost = trim($_POST['smtp_host']);
    $smtpPort = (int)$_POST['smtp_port'];
    $smtpUsername = trim($_POST['smtp_username']);
    $smtpPassword = trim($_POST['smtp_password']);
    $smtpEncryption = $_POST['smtp_encryption'];
    $fromEmail = trim($_POST['from_email']);
    $fromName = trim($_POST['from_name']);

    // 验证必填字段
    if (empty($siteName)) {
        $errors[] = '站点名称不能为空';
    }

    if (empty($smtpHost) || empty($smtpUsername) || empty($smtpPassword) || empty($fromEmail)) {
        $errors[] = '邮件配置的必填字段不能为空';
    }

    if (empty($errors)) {
        try {
            // 开始事务
            $pdo->beginTransaction();

            // 更新站点信息
            if ($siteInfo) {
                $stmt = $pdo->prepare("UPDATE site_info SET name = ?, description = ?");
                $stmt->execute([$siteName, $siteDescription]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO site_info (name, description) VALUES (?, ?)");
                $stmt->execute([$siteName, $siteDescription]);
            }

            // 更新邮件配置
            if ($emailConfig) {
                $stmt = $pdo->prepare("UPDATE email_config SET smtp_host = ?, smtp_port = ?, smtp_username = ?, smtp_password = ?, smtp_encryption = ?, from_email = ?, from_name = ?");
                $stmt->execute([$smtpHost, $smtpPort, $smtpUsername, $smtpPassword, $smtpEncryption, $fromEmail, $fromName]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO email_config (smtp_host, smtp_port, smtp_username, smtp_password, smtp_encryption, from_email, from_name) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$smtpHost, $smtpPort, $smtpUsername, $smtpPassword, $smtpEncryption, $fromEmail, $fromName]);
            }

            // 提交事务
            $pdo->commit();

            $success = '设置已成功保存';

            // 更新本地变量以反映更改
            $siteInfo['name'] = $siteName;
            $siteInfo['description'] = $siteDescription;
            $emailConfig = [
                'smtp_host' => $smtpHost,
                'smtp_port' => $smtpPort,
                'smtp_username' => $smtpUsername,
                'smtp_password' => $smtpPassword,
                'smtp_encryption' => $smtpEncryption,
                'from_email' => $fromEmail,
                'from_name' => $fromName
            ];
        } catch (PDOException $e) {
            // 回滚事务
            $pdo->rollBack();
            $errors[] = '保存设置失败: ' . $e->getMessage();
        }
    }
}

// 确保email_config表存在
function ensureEmailConfigTableExists($pdo) {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS email_config (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            smtp_host VARCHAR(255) NOT NULL,
            smtp_port INTEGER NOT NULL,
            smtp_username VARCHAR(255) NOT NULL,
            smtp_password VARCHAR(255) NOT NULL,
            smtp_encryption VARCHAR(10) NOT NULL,
            from_email VARCHAR(255) NOT NULL,
            from_name VARCHAR(255) NOT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    } catch (PDOException $e) {
        die('创建email_config表失败: ' . $e->getMessage());
    }
}

// 确保表存在
ensureEmailConfigTableExists($pdo);
?>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>系统设置 - <?php echo $siteInfo['name']; ?></title>
    <style>
        @font-face {
            font-family: 'ZD';
            src: url('zd.ttf') format('truetype');
            font-weight: normal;
            font-style: normal;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'ZD', sans-serif;
        }
        body {
            background-color: #f0f2f5;
            color: #333;
            line-height: 1.6;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .header-frosted {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            color: #333;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 1000;
        }
        .header-nav {
            display: flex;
            gap: 20px;
        }
        .header-nav span {
            cursor: pointer;
            color: #7873f5;
            font-weight: bold;
            transition: color 0.3s ease;
        }
        .header-nav span:hover {
            color: #605acf;
        }
        header {
            background: linear-gradient(135deg, #ff6ec7, #7873f5);
            color: white;
            padding: 80px 0 40px;
            text-align: center;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            margin-top: 60px;
        }
        h1 {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        .card {
            background: white;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        h2 {
            color: #7873f5;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #555;
        }
        input[type="text"],
        input[type="email"],
        input[type="password"],
        textarea,
        select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
            transition: border 0.3s ease;
        }
        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus,
        textarea:focus,
        select:focus {
            border-color: #7873f5;
            outline: none;
            box-shadow: 0 0 0 3px rgba(120, 115, 245, 0.2);
        }
        textarea {
            height: 150px;
            resize: vertical;
        }
        .btn {
            display: inline-block;
            background: #7873f5;
            color: white;
            padding: 12px 25px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: bold;
            transition: background 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 1rem;
        }
        .btn:hover {
            background: #605acf;
        }
        .btn-container {
            text-align: center;
            margin-top: 30px;
        }
        .back-link {
            display: inline-block;
            margin-top: 15px;
            color: #7873f5;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        .error {
            color: #e74c3c;
            font-size: 0.9rem;
            margin-top: 5px;
        }
        .success {
            color: #2ecc71;
            padding: 15px;
            background: #f1f9f1;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid #2ecc71;
        }
        .logout-btn {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 30px;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.3s ease;
        }
        .logout-btn:hover {
            background: #c0392b;
        }
        .tab-container {
            margin-bottom: 20px;
        }
        .tab {
            display: inline-block;
            padding: 10px 20px;
            background: #f0f0f0;
            border-radius: 5px 5px 0 0;
            cursor: pointer;
            font-weight: bold;
            color: #777;
            transition: all 0.3s ease;
        }
        .tab.active {
            background: white;
            color: #7873f5;
            border-top: 2px solid #7873f5;
        }
        .tab-content {
            display: none;
            background: white;
            padding: 20px;
            border-radius: 0 5px 5px 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .tab-content.active {
            display: block;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 选项卡切换
            const tabs = document.querySelectorAll('.tab');
            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    // 移除所有active类
                    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));

                    // 添加active类到当前选项卡
                    this.classList.add('active');
                    const target = this.getAttribute('data-target');
                    document.getElementById(target).classList.add('active');
                });
            });
        });
    </script>
</head>
<body>
    <div class="header-frosted">
        <h3><?php echo $siteInfo['name']; ?> - 管理员面板</h3>
        <div class="header-nav">
            <span onclick="window.location.href='admin_dashboard.php'">控制面板</span>
            <span onclick="window.location.href='admin_dashboard.php?view=all'">所有备案</span>
            <span onclick="window.location.href='admin_dashboard.php?view=pending'">待审核备案</span>
            <span onclick="window.location.href='add_registration.php'">添加备案</span>
            <span onclick="window.location.href='settings.php'">系统设置</span>
            <button class="logout-btn" onclick="window.location.href='admin_login.php?action=logout'">退出登录</button>
        </div>
    </div>
    <div class="container">
        <header>
            <h1><?php echo $siteInfo['name']; ?> - 系统设置</h1>
            <p>配置站点信息和邮件设置</p>
        </header>

        <div class="card">
            <h2>系统设置</h2>

            <?php if ($success): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <?php foreach ($errors as $error): ?>
                    <div class="error"><?php echo $error; ?></div>
                <?php endforeach; ?>
            <?php endif; ?>

            <div class="tab-container">
                <div class="tab active" data-target="site-settings">站点设置</div>
                <div class="tab" data-target="email-settings">邮件设置</div>
            </div>

            <form method="post">
                <div id="site-settings" class="tab-content active">
                    <div class="form-group">
                        <label for="site_name">站点名称 *</label>
                        <input type="text" id="site_name" name="site_name" required value="<?php echo htmlspecialchars($siteInfo['name']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="site_description">站点描述</label>
                        <textarea id="site_description" name="site_description"><?php echo htmlspecialchars($siteInfo['description']); ?></textarea>
                    </div>
                </div>

                <div id="email-settings" class="tab-content">
                    <div class="form-group">
                        <label for="smtp_host">SMTP 服务器 *</label>
                        <input type="text" id="smtp_host" name="smtp_host" required value="<?php echo htmlspecialchars($emailConfig['smtp_host']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="smtp_port">SMTP 端口 *</label>
                        <input type="text" id="smtp_port" name="smtp_port" required value="<?php echo htmlspecialchars($emailConfig['smtp_port']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="smtp_encryption">加密方式 *</label>
                        <select id="smtp_encryption" name="smtp_encryption" required>
                            <option value="ssl" <?php echo $emailConfig['smtp_encryption'] === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                            <option value="tls" <?php echo $emailConfig['smtp_encryption'] === 'tls' ? 'selected' : ''; ?>>TLS</option>
                            <option value="none" <?php echo $emailConfig['smtp_encryption'] === 'none' ? 'selected' : ''; ?>>无</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="smtp_username">SMTP 用户名 *</label>
                        <input type="text" id="smtp_username" name="smtp_username" required value="<?php echo htmlspecialchars($emailConfig['smtp_username']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="smtp_password">SMTP 密码 *</label>
                        <input type="password" id="smtp_password" name="smtp_password" required value="<?php echo htmlspecialchars($emailConfig['smtp_password']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="from_email">发件人邮箱 *</label>
                        <input type="email" id="from_email" name="from_email" required value="<?php echo htmlspecialchars($emailConfig['from_email']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="from_name">发件人名称 *</label>
                        <input type="text" id="from_name" name="from_name" required value="<?php echo htmlspecialchars($emailConfig['from_name']); ?>">
                    </div>
                </div>

                <div class="btn-container">
                    <button type="submit" class="btn">保存设置</button>
                    <a href="admin_dashboard.php" class="back-link">返回控制面板</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>