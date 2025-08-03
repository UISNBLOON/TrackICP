<!DOCTYPE html>
<?php
// 检查是否已安装
if (!file_exists('config.php')) {
    // 调试信息
    error_log('index.php: config.php不存在，重定向到install.php');
    header('Location: install.php');
    exit;
}

// 加载配置
$config = include 'config.php';
?>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $config['site_name'] ?? '网站备案系统'; ?></title>
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
            transition: transform 0.3s ease, box-shadow 0.3s ease;
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
            margin-top: 50px;
        }
        @media (max-width: 768px) {
            h1 {
                font-size: 2rem;
            }
            .container {
                padding: 15px;
            }
        }
    </style>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 登录按钮已经通过链接直接跳转，无需JavaScript处理
        // 注册按钮已经通过链接直接跳转，无需JavaScript处理
        // 查询备案按钮已经通过链接直接跳转，无需JavaScript处理
    });
</script>
</head>
<body>
    <div class="header-frosted">
            <h3><?php echo $config['site_name'] ?? '网站备案系统'; ?></h3>
        <div class="header-nav">
            <a href="admin_login.php" style="text-decoration: none;"><span id="login-btn">登录</span></a>
            <a href="register.php" style="text-decoration: none;"><span id="register-btn">注册</span></a>
            <a href="search.php" style="text-decoration: none;"><span id="search-btn">查询备案</span></a>
        </div>
    </div>
    <div class="container">
        <header>
            <h1><?php echo $config['site_name'] ?? '网站备案系统'; ?></h1>
            <p class="subtitle"><?php echo $config['site_description'] ?? 'ICP备案管理平台'; ?></p>
        </header>

        <div class="card">
            <h2>欢迎使用</h2>
            <p>本系统提供网站备案服务，让您的网站拥有合法的"身份凭证"。无论您是创作者、爱好者还是企业，都可以通过本系统为您的网站进行官方备案。</p>
            <div style="text-align: center; margin-top: 30px;">
                <a href="register.php" class="btn">立即备案</a>
            </div>
        </div>

        <div class="card">
            <h2>系统功能</h2>
            <div class="features">
                <div class="feature-item">
                    <h3>网站备案</h3>
                    <p>为您的网站提交备案信息，获取唯一备案编号。</p>
                </div>
                <div class="feature-item">
                    <h3>备案信息查询</h3>
                    <p>通过备案编号或网站地址查询已备案的网站信息。</p>
                </div>
                <div class="feature-item">
                    <h3>备案证书生成</h3>
                    <p>自动生成虚拟角色备案证书，支持下载和分享。</p>
                </div>
            </div>
        </div>

        <footer>
            <p>© 2025</p>
        </footer>
    </div>
</body>
</html>