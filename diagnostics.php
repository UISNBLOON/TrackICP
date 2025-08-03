<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>网站备案系统 - 诊断工具</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 10px;
        }
        .tool-card {
            margin: 20px 0;
            padding: 15px;
            border-left: 4px solid #4CAF50;
            background-color: #f9f9f9;
            border-radius: 0 4px 4px 0;
        }
        .tool-card h2 {
            margin-top: 0;
            color: #4CAF50;
        }
        .tool-card p {
            color: #666;
        }
        .tool-link {
            display: inline-block;
            margin-top: 10px;
            padding: 8px 16px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        .tool-link:hover {
            background-color: #45a049;
        }
        .warning {
            color: #ff9800;
            margin-top: 20px;
            padding: 10px;
            background-color: #fff3cd;
            border-radius: 4px;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            color: #777;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>网站备案系统 - 诊断工具</h1>

        <div class="tool-card">
            <h2>服务器环境检查</h2>
            <p>检查PHP版本、必要扩展、数据库配置和文件权限等基本环境信息。</p>
            <a href="server_check.php" class="tool-link" target="_blank">运行环境检查</a>
        </div>

        <div class="tool-card">
            <h2>故障排查工具</h2>
            <p>查看详细的PHP错误日志、文件权限和数据库连接测试结果。</p>
            <a href="troubleshoot.php" class="tool-link" target="_blank">运行故障排查</a>
        </div>

        <div class="tool-card">
            <h2>数据库初始化</h2>
            <p>重新初始化数据库表结构和默认管理员账户。</p>
            <a href="db_init.php" class="tool-link" target="_blank">运行数据库初始化</a>
        </div>

        <div class="warning">
            <strong>注意：</strong> 这些工具仅用于诊断和解决部署问题。在生产环境中，建议限制对这些文件的访问权限或在问题解决后删除这些文件。
        </div>

        <div class="footer">
            <p>网站备案系统 © 2023</p>
        </div>
    </div>
</body>
</html>