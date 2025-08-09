<?php
// 检查是否已安装
if (!file_exists('config.php')) {
    header('Location: install.php');
    exit;
}

// 加载配置
$config = include 'config.php';

// 初始化数据库连接
require_once 'db_init.php';

// 处理表单提交
$success = '';
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 验证表单数据
    $data = [];

    // 验证网站名称
    if (empty($_POST['website_name'])) {
        $errors[] = '网站名称不能为空';
    } else {
        $data['website_name'] = trim($_POST['website_name']);
    }

    // 验证网站类型
    if (empty($_POST['website_category'])) {
        $errors[] = '请选择网站类型';
    } else {
        $data['website_category'] = $_POST['website_category'];
    }

    // 验证网站负责人
    if (empty($_POST['contact_person'])) {
        $errors[] = '网站负责人不能为空';
    } else {
        $data['contact_person'] = trim($_POST['contact_person']);
    }

    // 验证联系电话
    if (empty($_POST['contact_phone'])) {
        $errors[] = '联系电话不能为空';
    } else {
        $data['contact_phone'] = trim($_POST['contact_phone']);
    }

    // 验证联系邮箱
    if (empty($_POST['contact_email'])) {
        $errors[] = '联系邮箱不能为空';
    } elseif (!filter_var($_POST['contact_email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = '请输入有效的邮箱地址';
    } else {
        $data['contact_email'] = trim($_POST['contact_email']);
    }

    // 验证网站地址
    if (empty($_POST['website_url'])) {
        $errors[] = '网站地址不能为空';
    } else {
        $website = trim($_POST['website_url']);
        $website = preg_replace('#^https?://#', '', $website); // 统一格式
        $data['website_url'] = $website;
    }

    // 验证网站描述
    if (empty($_POST['website_description'])) {
        $errors[] = '网站描述不能为空';
    } else {
        $data['website_description'] = trim($_POST['website_description']);
    }

    // 如果没有错误，保存数据
    if (empty($errors)) {
        // 生成唯一备案编号 (ICP-年月日-6位ID)
        $data['registration_number'] = 'ICP-' . date('Ymd') . '-' . str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['status'] = 'pending'; // 默认为待审核
        $data['reason'] = '';

        try {
            // 插入数据到数据库
            $stmt = $pdo->prepare("INSERT INTO registrations (website_name, website_url, contact_person, contact_email, contact_phone, website_category, website_description, status, reason, registration_number, created_at, processed_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $data['website_name'],
                $data['website_url'],
                $data['contact_person'],
                $data['contact_email'],
                $data['contact_phone'],
                $data['website_category'],
                $data['website_description'],
                $data['status'],
                $data['reason'],
                $data['registration_number'],
                $data['created_at'],
                null
            ]);

            $success = '备案信息添加成功！备案编号: ' . $data['registration_number'];
        } catch (PDOException $e) {
            $errors[] = '添加备案信息失败: ' . $e->getMessage();
        }
    }
}

// 从数据库获取网站信息
$stmt = $pdo->query("SELECT name, description FROM site_info LIMIT 1");
$siteInfo = $stmt->fetch(PDO::FETCH_ASSOC);

// 如果找不到网站信息，使用配置文件中的默认值
if (!$siteInfo) {
    $siteInfo = [
        'name' => $config['site_name'] ?? '网站备案系统',
        'description' => $config['site_description'] ?? 'ICP备案管理平台'
    ];
}
?>
<?php include 'common_header.php'; ?>

<style>
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            margin-top: 20px;
        }
        .header-content {
            background: linear-gradient(135deg, #ff6ec7, #7873f5);
            color: white;
            padding: 40px 0;
            text-align: center;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        .subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
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
        @media (max-width: 768px) {
            #randomImage {
                max-height: 200px;
            }
        }
    </style>
</head>
<body>
        <div class="header-content">
            <h1>网站备案申请</h1>
            <p class="subtitle">填写以下信息完成网站备案申请</p>
        </div>

        <div class="card">
            <h2>网站备案申请</h2>

            <?php if ($success): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <?php foreach ($errors as $error): ?>
                    <div class="error"><?php echo $error; ?></div>
                <?php endforeach; ?>
            <?php endif; ?>

            <form method="post">
                <div class="form-group">
                    <label for="website_name">网站名称 *</label>
                    <input type="text" id="website_name" name="website_name" required placeholder="请输入网站的名称">
                </div>

                <div class="form-group">
                    <label for="website_category">网站类型 *</label>
                    <select id="website_category" name="website_category" required>
                        <option value="">请选择</option>
                        <option value="anime">动漫网站</option>
                        <option value="game">游戏网站</option>
                        <option value="blog">个人博客</option>
                        <option value="other">其他类型</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="contact_person">网站负责人 *</label>
                    <input type="text" id="contact_person" name="contact_person" required placeholder="请输入网站负责人姓名">
                </div>

                <div class="form-group">
                    <label for="contact_phone">联系电话 *</label>
                    <input type="text" id="contact_phone" name="contact_phone" required placeholder="请输入联系电话">
                </div>

                <div class="form-group">
                    <label for="contact_email">联系邮箱 *</label>
                    <input type="email" id="contact_email" name="contact_email" required placeholder="请输入联系邮箱">
                </div>

                <div class="form-group">
                    <label for="website_url">网站地址 *</label>
                    <input type="text" id="website_url" name="website_url" required placeholder="请输入网站域名，不带http://">
                </div>

                <div class="form-group">
                    <label for="website_description">网站描述 *</label>
                    <textarea id="website_description" name="website_description" required placeholder="请简要描述网站内容"></textarea>
                </div>

                <div class="btn-container">
                    <button type="submit" class="btn">提交备案</button>
                    <a href="index.php" class="back-link">返回首页</a>
                </div>
            </form>
        </div>
    </div>

    <!-- common_footer.php 文件不存在，已移除引用 -->

</div>
</body>
</html>