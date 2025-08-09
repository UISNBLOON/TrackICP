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
        body {
            background-image: url('img/Camera_XHS_17522965447511000g0082k8vvumgii0505o57.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            background-color: #f0f2f5;
        }
        .container {
            margin-top: 90px; /* 为固定的页眉留出空间 */
        }
        /* 页面特定样式 */
        .public-list-container {
            margin-top: 30px;
        }
        .public-title {
            text-align: center;
            margin-bottom: 30px;
        }
        .public-title h1 {
            color: #7873f5;
            text-shadow: none;
            margin-bottom: 10px;
        }
        .public-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }
        .public-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        .public-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .public-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: #f0f2f5;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            font-size: 24px;
            color: #7873f5;
        }
        .public-name {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }
        .public-id {
            font-size: 0.9rem;
            color: #777;
        }
        .no-records {
            text-align: center;
            padding: 40px;
            color: #999;
            font-size: 1.1rem;
        }
    </style>

    <div class="public-list-container">
        <div class="public-title">
            <h1>云团子ICP备案中心—备案信息公示</h1>
            <p class="subtitle">安全·可爱·高效的二次元虚拟备案</p>
        </div>

        <div class="public-cards">
            <!-- 备案信息卡片 -->
            <?php
// 初始化数据库连接
require_once 'db_init.php';

// 从数据库获取最多12条随机备案信息
$stmt = $pdo->query("SELECT website_name, registration_number FROM registrations ORDER BY RAND() LIMIT 12");
$registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 如果没有备案信息，显示提示
if (empty($registrations)) {
    echo '<div class="no-records">暂无备案信息</div>';
} else {
    // 循环生成备案信息卡片
    foreach ($registrations as $reg) {
        // 获取网站名称的第一个字作为图标
        $firstChar = mb_substr($reg['website_name'], 0, 1, 'UTF-8');
        
        echo '<div class="public-card">';
        echo '    <div class="public-icon">' . $firstChar . '</div>';
        echo '    <div class="public-name">' . $reg['website_name'] . '</div>';
        echo '    <div class="public-id">初ICP备' . $reg['registration_number'] . '备</div>';
        echo '</div>';
    }
}
            ?>
        </div>
    </div>
</div>

<!-- 页脚已删除 -->