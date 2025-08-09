<?php
// 检查是否已安装
if (!file_exists('config.php')) {
    header('Location: install.php');
    exit;
}

// 加载配置
$config = include 'config.php';
?>
<?php include 'common_header.php'; ?>

<div class="container">
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
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            margin-top: 20px;
        }
        .header-content {
            background: linear-gradient(135deg, #ff6ec7, #7873f5);
            color: white;
            padding: 20px 0;
            text-align: center;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        h1 {
            font-size: 1.8rem;
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
        select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
            transition: border 0.3s ease;
        }
        input[type="text"]:focus,
        select:focus {
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
        .search-results {
            margin-top: 30px;
        }
        .result-item {
            background: #f9f9ff;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #7873f5;
        }
        .result-item h3 {
            color: #7873f5;
            margin-bottom: 10px;
        }
        .result-item p {
            margin-bottom: 8px;
        }
        .result-label {
            font-weight: bold;
            color: #555;
        }
        .no-results {
            text-align: center;
            padding: 30px;
            color: #777;
        }
        @media (max-width: 768px) {
            #randomImage {
                max-height: 200px;
            }
        }
    </style>
        <div class="header-content">
            <h1>网站备案查询</h1>
            <p>输入备案编号或网站地址查询备案信息</p>
        </div>

        <div class="card">
            <h2>查询备案信息</h2>

            <form method="get">
                <div class="form-group">
                    <label for="search_type">查询类型</label>
                    <select id="search_type" name="search_type">
                        <option value="registration_number" <?php if (isset($_GET['search_type']) && $_GET['search_type'] == 'registration_number') echo 'selected'; ?>>备案编号</option>
                        <option value="website" <?php if (isset($_GET['search_type']) && $_GET['search_type'] == 'website') echo 'selected'; ?>>网站地址</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="search_query">查询内容</label>
                    <input type="text" id="search_query" name="search_query" placeholder="请输入查询内容" value="<?php if (isset($_GET['search_query'])) echo htmlspecialchars($_GET['search_query']); ?>">
                </div>

                <div class="btn-container">
                    <button type="submit" class="btn">查询</button>
                </div>
                <span class="back-link">返回首页</span>
            </form>

            <div class="search-results">
                <?php
// 加载配置
$config = include 'config.php';

// 设置默认配置值
$site_name = $config['site_name'] ?? '网站备案系统';
$site_description = $config['site_description'] ?? 'ICP备案管理平台';

// 初始化数据库连接
require_once 'db_init.php';

// 处理查询请求
                if (isset($_GET['search_query']) && !empty($_GET['search_query'])) {
                    $search_type = $_GET['search_type'];
                    $search_query = trim($_GET['search_query']);
                    $results = [];

                    // 检查数据库连接
                    if (isset($pdo) && $pdo) {
                        try {
                            // 准备SQL查询
                            if ($search_type === 'registration_number') {
                                $stmt = $pdo->prepare("SELECT * FROM registrations WHERE registration_number LIKE :query");
                                $stmt->execute(['query' => '%' . $search_query . '%']);
                            } elseif ($search_type === 'website') {
                                $stmt = $pdo->prepare("SELECT * FROM registrations WHERE website_url LIKE :query");
                                $stmt->execute(['query' => '%' . $search_query . '%']);
                            } elseif ($search_type === 'email') {
                                $stmt = $pdo->prepare("SELECT * FROM registrations WHERE contact_email LIKE :query");
                                $stmt->execute(['query' => '%' . $search_query . '%']);
                            }

                            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        } catch (PDOException $e) {
                            echo '<div class="error">查询失败: ' . $e->getMessage() . '</div>';
                        }
                    } else {
                        echo '<div class="error">数据库连接失败，请检查配置文件。</div>';
                    }

                    // 显示查询结果
                    if (!empty($results)) {
                        echo '<h3>查询结果 (共 ' . count($results) . ' 条)</h3>';
                        foreach ($results as $result) {
                            echo '<div class="result-item">';
                            echo '<h3>' . htmlspecialchars($result['website_name']) . '</h3>';
                            echo '<p><span class="result-label">备案编号：</span>' . htmlspecialchars($result['registration_number']) . '</p>';
                            
                            // 显示网站类型
                            $categoryMap = [
                                'anime' => '动漫网站',
                                'game' => '游戏网站',
                                'blog' => '个人博客',
                                'other' => '其他类型'
                            ];
                            echo '<p><span class="result-label">网站类型：</span>' . htmlspecialchars($categoryMap[$result['website_category']] ?? '未知类型') . '</p>';
                            
                            echo '<p><span class="result-label">网站负责人：</span>' . htmlspecialchars($result['contact_person']) . '</p>';
                            echo '<p><span class="result-label">联系电话：</span>' . htmlspecialchars($result['contact_phone']) . '</p>';
                            echo '<p><span class="result-label">联系邮箱：</span>' . htmlspecialchars($result['contact_email']) . '</p>';
                            echo '<p><span class="result-label">网站地址：</span><a href="http://' . htmlspecialchars($result['website_url']) . '" target="_blank">' . htmlspecialchars($result['website_url']) . '</a></p>';
                            echo '<p><span class="result-label">提交日期：</span>' . htmlspecialchars($result['created_at']) . '</p>';
                            echo '<p><span class="result-label">处理日期：</span>' . htmlspecialchars($result['processed_at'] ?? '未处理') . '</p>';
                            echo '<p><span class="result-label">状态：</span>' . ($result['status'] === 'pending' ? '待审核' : ($result['status'] === 'approved' ? '已通过' : '已拒绝')) . '</p>';
                            echo '<p><span class="result-label">网站描述：</span>' . nl2br(htmlspecialchars($result['website_description'])) . '</p>';
                            if (!empty($result['reason'])) {
                                echo '<p><span class="result-label">处理说明：</span>' . nl2br(htmlspecialchars($result['reason'])) . '</p>';
                            }
                            echo '</div>';
                        }
                    } else {
                        echo '<div class="no-results">';
                        echo '<p>没有找到符合条件的备案信息</p>';
                        echo '</div>';
                    }
                }
                ?>
            </div>
        </div>

    <?php include 'common_footer.php'; ?>

</div>
</body>
</html>
