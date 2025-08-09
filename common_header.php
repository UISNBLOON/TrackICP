<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($config['site_name']) ? htmlspecialchars($config['site_name']) : '二次元网站备案系统'; ?></title>
    <style>
        /* 字体定义 */
        @font-face {
            font-family: 'ZD';
            src: url('zd.ttf') format('truetype');
        }
        
        /* 全局字体设置 */
        * {
            font-family: 'ZD', sans-serif;
        }
        /* 页眉样式 */
        header {
            background: rgba(248, 249, 250, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            padding: 10px 0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            height: 60px;
            display: flex;
            align-items: center;
        }

        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: bold;
            text-decoration: none;
            color: #333;
        }

        /* 导航菜单 */
        nav {
            display: flex;
            gap: 20px;
        }

        .nav-link {
            color: #333;
            text-decoration: none;
            padding: 5px 10px;
            font-size: 0.95rem;
        }

        .nav-link:hover {
            color: #7873f5;
        }

        /* 响应式样式 */
        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
                text-align: center;
                padding: 10px;
            }

            nav {
                margin: 10px 0;
                flex-wrap: wrap;
                justify-content: center;
            }

            .logo {
                font-size: 1.3rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-container">
            <a href="index.php" class="logo">TrackICP</a>

            <nav>
                <a href="index.php" class="nav-link">🏠首页</a>
                <a href="public_list.php" class="nav-link">公示</a>
                <a href="register.php" class="nav-link">加入</a>
                <a href="https://icp.9d9c.ink" class="nav-link">官网</a>
            </nav>
        </div>
    </header>