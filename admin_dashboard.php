<!DOCTYPE html>
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

// 获取网站信息
$stmt = $pdo->query("SELECT name, description FROM site_info LIMIT 1");
$siteInfo = $stmt->fetch(PDO::FETCH_ASSOC);

// 如果找不到网站信息，使用配置文件中的默认值
if (!$siteInfo) {
    $siteInfo = [
        'name' => $config['site_name'] ?? '二次元网站备案系统',
        'description' => $config['site_description'] ?? '管理和审核网站备案申请'
    ];
}

// 处理备案状态更新
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status' && isset($_POST['id']) && isset($_POST['status'])) {
    $id = $_POST['id'];
    $status = $_POST['status'];
    $reason = $_POST['reason'] ?? '';

    try {
        // 开始事务
        $pdo->beginTransaction();

        // 获取备案信息
        $stmt = $pdo->prepare("SELECT * FROM registrations WHERE id = ?");
        $stmt->execute([$id]);
        $registration = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$registration) {
            throw new Exception('未找到该备案申请');
        }

        // 生成备案编号（如果通过审核）
        $registrationNumber = $registration['registration_number'];
        if ($status === 'approved' && empty($registrationNumber)) {
            $registrationNumber = 'ICP-' . date('Ymd') . '-' . str_pad($id, 6, '0', STR_PAD_LEFT);
        }

        // 更新备案状态
        $stmt = $pdo->prepare("UPDATE registrations SET status = ?, reason = ?, processed_at = NOW(), registration_number = ? WHERE id = ?");
        $stmt->execute([$status, $reason, $registrationNumber, $id]);

        // 如果审核通过，发送邮件
        if ($status === 'approved') {
            // 加载邮件工具
            require_once 'email_utils.php';

            // 更新备案信息中的处理日期和备案编号
            $registration['status'] = 'approved';
            $registration['processed_at'] = date('Y-m-d H:i:s');
            $registration['registration_number'] = $registrationNumber;
            
            // 确保contact_email字段存在
            if (!isset($registration['contact_email']) || empty($registration['contact_email'])) {
                throw new Exception('缺少联系邮箱，无法发送审核通过邮件');
            }

            try {
                // 创建邮件工具实例
                $emailUtils = new EmailUtils($pdo);
                // 发送审核通过邮件
                $emailUtils->sendApprovalEmail($registration);
            } catch (Exception $e) {
                // 记录邮件发送失败，但不影响审核流程
                error_log('发送审核通过邮件失败: ' . $e->getMessage());
            }
        }

        // 提交事务
        $pdo->commit();

        header("Location: admin_dashboard.php");
        exit();
    } catch (PDOException $e) {
        // 回滚事务
        $pdo->rollBack();
        die('更新备案状态失败: ' . $e->getMessage());
    } catch (Exception $e) {
        // 回滚事务
        $pdo->rollBack();
        die('处理失败: ' . $e->getMessage());
    }
}

// 确定要显示的备案类型
$view = $_GET['view'] ?? 'all';

// 根据视图类型获取备案申请
if ($view === 'pending') {
    // 获取待审核的备案申请
    $stmt = $pdo->prepare("SELECT * FROM registrations WHERE status = 'pending' ORDER BY created_at DESC");
    $stmt->execute();
    $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $title = '待审核备案申请';
} else if ($view === 'approved') {
    // 获取已通过的备案申请
    $stmt = $pdo->prepare("SELECT * FROM registrations WHERE status = 'approved' ORDER BY processed_at DESC");
    $stmt->execute();
    $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $title = '已通过备案申请';
} else if ($view === 'rejected') {
    // 获取已拒绝的备案申请
    $stmt = $pdo->prepare("SELECT * FROM registrations WHERE status = 'rejected' ORDER BY processed_at DESC");
    $stmt->execute();
    $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $title = '已拒绝备案申请';
} else {
    // 获取所有备案申请
    $stmt = $pdo->prepare("SELECT * FROM registrations ORDER BY created_at DESC");
    $stmt->execute();
    $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $title = '所有备案申请';
}

// 确保registrations表存在
function ensureRegistrationsTableExists($pdo) {
    try {
        // 根据数据库类型选择自增关键字
        global $config;
        $auto_increment = ($config['database_type'] === 'mysql') ? 'AUTO_INCREMENT' : 'AUTOINCREMENT';
        $int_type = ($config['database_type'] === 'mysql') ? 'INT' : 'INTEGER';

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
    } catch (PDOException $e) {
        die('创建registrations表失败: ' . $e->getMessage());
    }
}

// 确保site_info表存在
function ensureSiteInfoTableExists($pdo) {
    try {
        // 根据数据库类型选择自增关键字
        global $config;
        $auto_increment = ($config['database_type'] === 'mysql') ? 'AUTO_INCREMENT' : 'AUTOINCREMENT';
        $int_type = ($config['database_type'] === 'mysql') ? 'INT' : 'INTEGER';

        $pdo->exec("CREATE TABLE IF NOT EXISTS site_info (
            id $int_type PRIMARY KEY $auto_increment,
            name VARCHAR(255) NOT NULL,
            description TEXT NOT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    } catch (PDOException $e) {
        die('创建site_info表失败: ' . $e->getMessage());
    }
}

// 确保email_config表存在
function ensureEmailConfigTableExists($pdo) {
    try {
        // 根据数据库类型选择自增关键字
        global $config;
        $auto_increment = ($config['database_type'] === 'mysql') ? 'AUTO_INCREMENT' : 'AUTOINCREMENT';
        $int_type = ($config['database_type'] === 'mysql') ? 'INT' : 'INTEGER';

        $pdo->exec("CREATE TABLE IF NOT EXISTS email_config (
            id $int_type PRIMARY KEY $auto_increment,
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
ensureRegistrationsTableExists($pdo);
ensureSiteInfoTableExists($pdo);
ensureEmailConfigTableExists($pdo);
?>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理员控制面板 - <?php echo $siteInfo['name']; ?></title>
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
            max-width: 1200px;
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
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
        .status {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-approved {
            background-color: #d4edda;
            color: #155724;
        }
        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
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
        .btn-success {
            background: #28a745;
        }
        .btn-success:hover {
            background: #218838;
        }
        .btn-danger {
            background: #dc3545;
        }
        .btn-danger:hover {
            background: #c82333;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1001;
            align-items: center;
            justify-content: center;
        }
        .modal-content {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        .modal h3 {
            margin-bottom: 20px;
            color: #7873f5;
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
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            min-height: 100px;
            resize: vertical;
        }
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }
        .logout-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.3s ease;
        }
        .logout-btn:hover {
            background: #c82333;
        }
        .search-container {
            margin-bottom: 20px;
        }
        .search-input {
            padding: 10px;
            width: 300px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        .search-btn {
            padding: 10px 15px;
            background: #7873f5;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
    </style>
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
            <h1><?php echo $siteInfo['name']; ?> - 管理员控制面板</h1>
            <p><?php echo $siteInfo['description']; ?></p>
        </header>

        <div class="card">
            <h2><?php echo $title; ?></h2>
            
            <div class="search-container">
                <input type="text" class="search-input" id="searchInput" placeholder="搜索网站名称或URL...">
                <button class="search-btn" onclick="searchRegistrations()">搜索</button>
            </div>

            <table id="registrationsTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>网站名称</th>
                        <th>网站URL</th>
                        <th>联系人</th>
                        <th>提交时间</th>
                        <th>状态</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($registrations as $registration): ?>
                        <tr>
                            <td><?php echo $registration['id']; ?></td>
                            <td><?php echo htmlspecialchars($registration['website_name']); ?></td>
                            <td><?php echo htmlspecialchars($registration['website_url']); ?></td>
                            <td><?php echo htmlspecialchars($registration['contact_person']); ?></td>
                            <td><?php echo $registration['created_at']; ?></td>
                            <td>
                                <span class="status status-<?php echo $registration['status']; ?>">
                                    <?php 
                                    switch ($registration['status']) {
                                        case 'pending': echo '待审核'; break;
                                        case 'approved': echo '已通过'; break;
                                        case 'rejected': echo '已拒绝'; break;
                                    }
                                    ?>
                                </span>
                            </td>
                            <td class="action-buttons">
                                <?php if ($registration['status'] === 'pending'): ?>
                                    <button class="btn btn-success" onclick="openModal(<?php echo $registration['id']; ?>, 'approved')">通过</button>
                                    <button class="btn btn-danger" onclick="openModal(<?php echo $registration['id']; ?>, 'rejected')">拒绝</button>
                                <?php else: ?>
                                    <button class="btn" onclick="showDetails(<?php echo $registration['id']; ?>)">详情</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 审核操作模态框 -->
    <div id="actionModal" class="modal">
        <div class="modal-content">
            <h3 id="modalTitle">审核操作</h3>
            <form method="post" id="actionForm">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="id" id="registrationId">
                <input type="hidden" name="status" id="actionStatus">
                
                <div class="form-group" id="reasonGroup">
                    <label for="reason">审核说明</label>
                    <textarea id="reason" name="reason" placeholder="请输入审核说明（拒绝时必填）"></textarea>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" onclick="closeModal()">取消</button>
                    <button type="submit" class="btn" id="submitBtn">提交</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // 模态框相关操作
        const modal = document.getElementById('actionModal');
        const actionForm = document.getElementById('actionForm');
        const registrationIdInput = document.getElementById('registrationId');
        const actionStatusInput = document.getElementById('actionStatus');
        const modalTitle = document.getElementById('modalTitle');
        const reasonGroup = document.getElementById('reasonGroup');
        const submitBtn = document.getElementById('submitBtn');

        function openModal(id, status) {
            registrationIdInput.value = id;
            actionStatusInput.value = status;
            
            if (status === 'approved') {
                modalTitle.textContent = '通过备案申请';
                reasonGroup.style.display = 'block';
                submitBtn.className = 'btn btn-success';
            } else {
                modalTitle.textContent = '拒绝备案申请';
                reasonGroup.style.display = 'block';
                submitBtn.className = 'btn btn-danger';
            }
            
            modal.style.display = 'flex';
        }

        function closeModal() {
            modal.style.display = 'none';
        }

        // 点击模态框外部关闭
        window.onclick = function(event) {
            if (event.target === modal) {
                closeModal();
            }
        }

        // 搜索功能
        function searchRegistrations() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toUpperCase();
            const table = document.getElementById('registrationsTable');
            const tr = table.getElementsByTagName('tr');

            for (let i = 1; i < tr.length; i++) {
                const td1 = tr[i].getElementsByTagName('td')[1]; // 网站名称
                const td2 = tr[i].getElementsByTagName('td')[2]; // 网站URL
                let txtValue1 = td1.textContent || td1.innerText;
                let txtValue2 = td2.textContent || td2.innerText;
                
                if (txtValue1.toUpperCase().indexOf(filter) > -1 || txtValue2.toUpperCase().indexOf(filter) > -1) {
                    tr[i].style.display = '';
                } else {
                    tr[i].style.display = 'none';
                }
            }
        }

        // 显示详情功能
        function showDetails(id) {
            alert('备案ID: ' + id + ' 的详细信息\n此功能将在后续版本中完善');
            // 实际应用中可以跳转到详情页或显示更详细的模态框
        }
    </script>
</body>
</html>