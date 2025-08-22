<?php
session_start();
require_once '../auth_check.php';
checkAdminAuth();

error_reporting(E_ALL);
ini_set('display_errors', 1);

// 正确加载配置
$config = include '../config.php';
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

// 获取所有管理员账户
function getAllAdmins($pdo) {
    $stmt = $pdo->query("SELECT id, username, created_at FROM admins");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 检查用户名是否已存在
function checkUsernameExists($pdo, $username) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    return $stmt->fetchColumn() > 0;
}

// 添加新管理员
function addAdmin($pdo, $username, $password) {
    if (checkUsernameExists($pdo, $username)) {
        return ['success' => false, 'message' => '用户名已存在'];
    }

    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    try {
        $stmt = $pdo->prepare("INSERT INTO admins (username, password_hash) VALUES (?, ?)");
        $stmt->execute([$username, $password_hash]);
        return ['success' => true, 'message' => '管理员添加成功'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => '添加失败: ' . $e->getMessage()];
    }
}

// 删除管理员
function deleteAdmin($pdo, $id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM admins WHERE id = ?");
        $stmt->execute([$id]);
        return ['success' => true, 'message' => '管理员删除成功'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => '删除失败: ' . $e->getMessage()];
    }
}

// 处理表单提交
$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 验证CSRF令牌
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = '安全验证失败';
    } else {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'add':
                    $username = trim($_POST['username']);
                    $password = trim($_POST['password']);
                    $confirm_password = trim($_POST['confirm_password']);

                    if (empty($username) || empty($password)) {
                        $message = '用户名和密码不能为空';
                    } elseif ($password !== $confirm_password) {
                        $message = '两次输入的密码不一致';
                    } elseif (strlen($password) < 6) {
                        $message = '密码长度不能少于6位';
                    } else {
                        $result = addAdmin($pdo, $username, $password);
                        $success = $result['success'];
                        $message = $result['message'];
                    }
                    break;

                case 'delete':
                    $id = (int)$_POST['id'];
                    // 防止删除自己
                    if ($id == $_SESSION['admin_id']) {
                        $message = '不能删除当前登录的管理员账户';
                    } else {
                        $result = deleteAdmin($pdo, $id);
                        $success = $result['success'];
                        $message = $result['message'];
                    }
                    break;
            }
        }
    }
}

// 生成CSRF令牌
$csrf_token = generateCSRFToken();

// 获取所有管理员
$admins = getAllAdmins($pdo);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理员账户管理</title>
    <style>
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
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #7873f5;
            margin-bottom: 20px;
            text-align: center;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .btn {
            display: inline-block;
            background: #7873f5;
            color: white;
            padding: 8px 15px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: bold;
            transition: background 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
        }
        .btn:hover {
            background: #605acf;
        }
        .btn-danger {
            background: #e74c3c;
        }
        .btn-danger:hover {
            background: #c0392b;
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
        }
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-weight: bold;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>管理员账户管理</h1>

        <?php if (!empty($message)): ?>
            <div class="message <?php echo $success ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <h2>当前管理员账户</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>用户名</th>
                        <th>创建时间</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($admins as $admin): ?>
                        <tr>
                            <td><?php echo $admin['id']; ?></td>
                            <td><?php echo htmlspecialchars($admin['username']); ?></td>
                            <td><?php echo $admin['created_at']; ?></td>
                            <td>
                                <?php if ($admin['id'] != $_SESSION['admin_id']): ?>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $admin['id']; ?>">
                                        <button type="submit" class="btn btn-danger" onclick="return confirm('确定要删除这个管理员账户吗？');">删除</button>
                                    </form>
                                <?php else: ?>
                                    <span style="color: #999;">当前账户</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="card">
            <h2>添加新管理员</h2>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label for="username">用户名</label>
                    <input type="text" id="username" name="username" required placeholder="输入新管理员用户名">
                </div>
                <div class="form-group">
                    <label for="password">密码</label>
                    <input type="password" id="password" name="password" required placeholder="输入密码（至少6位）">
                </div>
                <div class="form-group">
                    <label for="confirm_password">确认密码</label>
                    <input type="password" id="confirm_password" name="confirm_password" required placeholder="再次输入密码">
                </div>
                <button type="submit" class="btn">添加管理员</button>
            </form>
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <a href="admin_dashboard.php" class="btn">返回管理面板</a>
        </div>
    </div>
</body>
</html>