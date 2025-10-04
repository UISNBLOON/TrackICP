<?php
session_start();

// 加载配置
$config = include '../config.php';

// 数据库连接函数
function getDatabaseConnection() {
    global $config;
    try {
        if ($config['database_type'] === 'mysql') {
            $dsn = "mysql:host={$config['database_config']['host']};port={$config['database_config']['port']};dbname={$config['database_config']['name']};charset=utf8mb4";
            $pdo = new PDO($dsn, $config['database_config']['user'], $config['database_config']['password']);
        } else if ($config['database_type'] === 'sqlite') {
            $dsn = "sqlite:{$config['database_config']['path']}";
            $pdo = new PDO($dsn);
        }
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        die('数据库连接失败');
    }
}

// 处理注销请求
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_unset();
    session_destroy();
    header('Location: admin_login.php');
    exit;
}

// 检查是否已登录
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: admin_dashboard.php');
    exit;
}

// 防止暴力破解：记录失败次数
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt'] = time();
}

// 重置计数器（5分钟后）
if (time() - $_SESSION['last_attempt'] > 300) {
    $_SESSION['login_attempts'] = 0;
}

$error = '';

// 处理登录请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 检查是否超过最大尝试次数（5次）
    if ($_SESSION['login_attempts'] >= 5) {
        $error = '登录尝试次数过多，请5分钟后重试';
    } else {
        // 验证CSRF令牌
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $error = '安全验证失败，请重新登录';
        } else {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';

            // 输入验证
            if (empty($username) || empty($password)) {
                $error = '用户名和密码不能为空';
            } else {
                // 连接数据库
                $pdo = getDatabaseConnection();

                // 查询管理员信息
                $stmt = $pdo->prepare("SELECT id, password_hash FROM admins WHERE username = ?");
                $stmt->execute([$username]);
                $admin = $stmt->fetch(PDO::FETCH_ASSOC);

                // 验证密码
                if ($admin && password_verify($password, $admin['password_hash'])) {
                    // 登录成功，重置尝试次数
                    $_SESSION['login_attempts'] = 0;
                    
                    // 设置会话变量
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_username'] = $username;
                    $_SESSION['last_activity'] = time();
                    
                    // 重新生成会话ID
                    session_regenerate_id(true);
                    
                    header('Location: admin_dashboard.php');
                    exit;
                } else {
                    $_SESSION['login_attempts']++;
                    $_SESSION['last_attempt'] = time();
                    $error = '用户名或密码错误';
                }
            }
        }
    }
}

// 生成CSRF令牌
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理员登录 - 二次元网站备案系统</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            background-color: #f0f2f5;
            color: #333;
            line-height: 1.6;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .login-container {
            background: white;
            border-radius: 10px;
            padding: 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        h1 {
            color: #7873f5;
            margin-bottom: 30px;
            text-align: center;
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
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
            transition: border 0.3s ease;
        }
        input[type="text"]:focus,
        input[type="password"]:focus {
            border-color: #7873f5;
            outline: none;
            box-shadow: 0 0 0 3px rgba(120, 115, 245, 0.2);
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
            width: 100%;
        }
        .btn:hover {
            background: #605acf;
        }
        .error {
            color: #e74c3c;
            font-size: 0.9rem;
            margin-top: 15px;
            text-align: center;
        }
        .info {
            color: #666;
            font-size: 0.85rem;
            margin-top: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>管理员登录</h1>
        <form method="post" class="login-form">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="form-group">
                <label for="username">用户名</label>
                <input type="text" id="username" name="username" required placeholder="请输入管理员用户名" autocomplete="username">
            </div>
            
            <div class="form-group">
                <label for="password">密码</label>
                <input type="password" id="password" name="password" required placeholder="请输入管理员密码" autocomplete="current-password">
            </div>
            
            <button type="submit" class="btn">登录</button>
            
            <?php if (!empty($error)): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($_SESSION['login_attempts'] >= 3): ?>
                <div class="info">
                    剩余尝试次数: <?php echo 5 - $_SESSION['login_attempts']; ?>
                </div>
            <?php endif; ?>
        </form>
    </div>
</body>
</html>