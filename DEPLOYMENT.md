# 网站备案系统部署指南

## 系统要求
- PHP 7.4 或更高版本
- PDO 扩展
- JSON 扩展
- 支持 MySQL 5.6+ 或 SQLite 3
- Web 服务器 (Apache/Nginx)

## 部署步骤

### 1. 准备代码
将项目代码上传到服务器的网站目录，例如 `/var/www/html/icp`。

### 2. 配置文件设置
- 复制 `config_sample.php` 为 `config.php`
- 根据服务器环境修改 `config.php` 中的配置
  - 数据库类型 (`mysql` 或 `sqlite`)
  - 数据库连接信息
  - 管理员账户密码
  - 邮件服务器配置

### 3. 数据库初始化
- 访问 `http://your-domain/db_init.php` 或在命令行执行 `php db_init.php`
- 这将创建必要的数据库表结构并初始化管理员账户

### 4. 文件权限设置
确保以下文件和目录具有写入权限：
- SQLite 数据库文件 (如果使用 SQLite)
- 整个项目目录 (建议仅在初始化时设置，之后限制权限)

### 5. 配置 Web 服务器
#### Apache 配置
```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /var/www/html/icp
    <Directory /var/www/html/icp>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

#### Nginx 配置
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/html/icp;
    index index.php index.html;

    location / {
        try_files $uri $uri/ =404;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php7.4-fpm.sock;
    }
}
```

### 6. 完成部署
- 访问 `http://your-domain/admin_login.php`
- 使用配置文件中设置的管理员账户登录
- 建议立即修改管理员密码

## 常见问题排查

### 1. 数据库连接错误
- 检查 `config.php` 中的数据库配置是否正确
- 确保数据库服务正在运行
- 验证数据库用户权限

### 2. 文件权限问题
- 确保 Web 服务器用户 (如 `www-data`) 对必要文件有写入权限
- 对于 SQLite，确保数据库文件和所在目录可写入

### 3. 页面显示空白
- 检查 PHP 错误日志
- 确保 PHP 扩展已正确安装
- 验证文件权限

### 4. 邮件发送失败
- 检查邮件服务器配置
- 确保服务器可以连接到邮件服务器
- 验证邮箱账户和密码

## 安全建议
- 定期备份数据库
- 不要使用默认管理员密码
- 限制对敏感文件的访问
- 保持 PHP 和服务器软件更新到最新版本

## 环境检查
可以通过访问 `http://your-domain/server_check.php` 运行环境检查脚本，诊断部署问题。