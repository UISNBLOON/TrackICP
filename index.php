<!DOCTYPE html>
<?php
// 检查是否已安装
if (!file_exists('config.php')) {
    header('Location: install.php');
    exit;
}

// 正确加载配置
$config = include 'config.php';
if (!$config || !is_array($config)) {
    die('配置文件加载失败');
}
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
            background-image: url('img/Camera_XHS_17522965447511000g0082k8vvumgii0505o57.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: #333;
            line-height: 1.6;
            background-color: #f0f2f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            margin-top: 90px;
        }
        h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            color: white;
            text-shadow: 0 2px 4px rgba(0,0,0,0.5);
        }
        .subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
            color: white;
            text-shadow: 0 1px 2px rgba(0,0,0,0.5);
        }
        .card {
            background: white;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
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
        .btn-container {
            text-align: center;
            margin-top: 30px;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        h2 {
            color: #7873f5;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
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
        .features {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 30px;
        }
        .feature-item {
            flex: 1 1 300px;
            background: #f9f9ff;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #7873f5;
        }
        .feature-item h3 {
            color: #7873f5;
            margin-bottom: 10px;
        }
        footer {
            text-align: center;
            padding: 20px;
            color: #777;
            margin-top: 20px;
        }
        @media (max-width: 768px) {
            h1 {
                font-size: 2rem;
            }
            .container {
                padding: 15px;
            }
            #randomImage {
                max-height: 200px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h2>备案查询</h2>
            <p style="margin-bottom: 20px;">输入备案编号或网站地址查询备案信息</p>

            <form method="get" action="search.php">
                <div class="form-group">
                    <label for="search_type">查询类型</label>
                    <select id="search_type" name="search_type">
                        <option value="registration_number">备案编号</option>
                        <option value="website">网站地址</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="search_query">查询内容</label>
                    <input type="text" id="search_query" name="search_query" placeholder="请输入查询内容" value="<?php if (isset($_GET['search_query'])) echo htmlspecialchars($_GET['search_query']); ?>">
                </div>

                <div class="btn-container">
                    <button type="submit" class="btn">查询</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>