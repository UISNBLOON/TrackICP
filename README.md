# 网站备案系统安装说明

## 系统概述
本系统是一个二次元网站备案管理系统，提供网站备案申请、审核和查询功能。

## 安装步骤
1. 将系统文件上传到您的Web服务器
2. 确保服务器已满足以下环境要求：
   - PHP 7.0 及以上版本
   - 启用 PHP 扩展：PDO（MySQL/SQLite 驱动）、mbstring、openssl、filter
   - 数据库：MySQL 或 SQLite（系统将自动适配）
3. 访问网站首页（index.php），系统将自动跳转到安装程序
4. 按照安装向导完成配置：
   - 网站基本信息（名称、描述）
   - 管理员账户（用户名+密码，密码需包含大小写字母、数字及特殊字符）
   - 数据库配置（MySQL 主机/端口/账户 或 SQLite 文件路径）
   - 邮件服务器配置（SMTP 主机/端口/账户，用于发送审核通知）
5. 点击"安装"按钮，系统将自动完成安装

## 系统功能
1. **用户功能**
   - 提交网站备案申请
   - 通过备案编号、网站地址查询备案状态

2. **管理员功能**
   - 登录后台管理系统
   - 审核备案申请（通过/拒绝，需填写审核原因）
   - 管理管理员账户（新增、删除子管理员）
   - 配置系统参数（网站信息、邮件服务）

## 访问路径
- 前台首页: `index.php`
- 备案申请: {insert\_element\_1\_YHJlZ2lzdGVyLnBocGA=}
- 备案查询: {insert\_element\_2\_YHNlYXJjaC5waHBg}
- 管理员登录: {insert\_element\_3\_YGFkbWluX2xvZ2luLnBocGA=} 或访问 `/admin` 路径
- 管理员控制面板: {insert\_element\_4\_YGFkbWluX2Rhc2hib2FyZC5waHBg}
- 管理员账户管理: {insert\_element\_5\_YG1hbmFnZV9hZG1pbnMucGhwYA==}
- 系统设置: {insert\_element\_6\_YHNldHRpbmdzLnBocGA=}

## 注意事项
1. 安装完成后，请立即修改默认管理员密码并妥善保管账户信息
2. 数据备份：
   - MySQL 用户：定期通过数据库工具导出数据
   - SQLite 用户：备份数据库文件（默认路径为 `database.db`）
3. 安全建议：
   - 生产环境中删除或限制 {insert\_element\_7\_YGRpYWdub3N0aWNzLnBocGA=}、{insert\_element\_8\_YHRyb3VibGVzaG9vdC5waHBg} 等诊断工具的访问
   - 确保 Web 服务器用户（如 `www-data`）对数据库文件及配置文件有正确的读写权限
   - 定期更新 PHP 及服务器软件至最新版本
4. 重新安装流程：
   - 删除 {insert\_element\_9\_YGNvbmZpZy5waHBg} 文件
   - 手动清理数据库（MySQL 需删除对应数据库，SQLite 需删除 `.db` 文件）
   - 重新访问首页启动安装向导