<?php
// 邮件工具类
class EmailUtils {
    private $pdo;
    private $config;

    // 构造函数
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->loadEmailConfig();
    }

    // 加载邮件配置
    private function loadEmailConfig() {
        try {
            $stmt = $this->pdo->query("SELECT * FROM email_config LIMIT 1");
            $this->config = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$this->config) {
                throw new Exception('未找到邮件配置');
            }
        } catch (PDOException $e) {
            throw new Exception('加载邮件配置失败: ' . $e->getMessage());
        }
    }

    // 发送拒绝邮件
    public function sendRejectionEmail($registration) {
        if (!$this->config) {
            throw new Exception('邮件配置未加载');
        }

        // 确保所有必要的字段都存在
        $requiredFields = ['contact_email', 'website_url', 'contact_person', 'processed_at', 'reason'];
        foreach ($requiredFields as $field) {
            if (!isset($registration[$field]) || empty($registration[$field])) {
                throw new Exception("缺少必要字段: $field");
            }
        }

        // 邮件主题
        $subject = '您的网站备案申请未通过审核';

        // 邮件内容模板
        $message = <<<HTML
<html>
<head>
    <title>您的网站备案申请未通过审核</title>
    <style>
        body {
            font-family: 'ZD', sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #eee;
            border-radius: 10px;
            background-color: #f9f9f9;
        }
        .header {
            background-color: #f57373;
            color: white;
            padding: 15px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            padding: 20px;
            background-color: white;
            border-radius: 0 0 8px 8px;
        }
        /* 页脚已删除 */
        .btn {
            display: inline-block;
            background-color: #f57373;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>网站备案申请未通过审核</h2>
        </div>
        <div class="content">
            <p>尊敬的 {$registration['contact_person']}：</p>
            <p>您好！很遗憾地通知您，您的网站备案申请未通过审核，具体原因如下：</p>
            <p><strong>拒绝原因：</strong> {$registration['reason']}</p>
            <p><strong>网站URL：</strong> <a href="{$registration['website_url']}">{$registration['website_url']}</a></p>
            <p><strong>处理日期：</strong> {$registration['processed_at']}</p>
            <p>您可以根据上述原因修改您的申请信息后重新提交。如有任何疑问，请随时联系我们。</p>
            <a href="https://icp.example.com/register.php" class="btn">重新提交申请</a>
        </div>
        <!-- 页脚已删除 -->
    </div>
</body>
</html>
HTML;

        // 发送邮件
        return $this->sendEmail($registration['contact_email'], $subject, $message);
    }

    // 发送审核通过邮件
    public function sendApprovalEmail($registration) {
        if (!$this->config) {
            throw new Exception('邮件配置未加载');
        }

        // 确保所有必要的字段都存在
        $requiredFields = ['contact_email', 'website_url', 'contact_person', 'processed_at', 'registration_number', 'website_name'];
        foreach ($requiredFields as $field) {
            if (!isset($registration[$field]) || empty($registration[$field])) {
                throw new Exception("缺少必要字段: $field");
            }
        }

        // 邮件主题
        $subject = '您的网站备案申请已通过审核';

        // 邮件内容模板
        $message = <<<HTML
<html>
<head>
    <title>您的网站备案申请已通过审核</title>
    <style>
        body {
            font-family: 'ZD', sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #eee;
            border-radius: 10px;
            background-color: #f9f9f9;
        }
        .header {
            background-color: #7873f5;
            color: white;
            padding: 15px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            padding: 20px;
            background-color: white;
            border-radius: 0 0 8px 8px;
        }
        /* 页脚已删除 */
        .btn {
            display: inline-block;
            background-color: #7873f5;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>网站备案申请已通过审核</h2>
        </div>
        <div class="content">
            <p>尊敬的 {$registration['contact_person']}：</p>
            <p>您好！您的网站备案申请已通过审核，相关信息如下：</p>
            <p><strong>网站名称：</strong> {$registration['website_name']}</p>
            <p><strong>网站URL：</strong> <a href="{$registration['website_url']}">{$registration['website_url']}</a></p>
            <p><strong>备案编号：</strong> 初ICP备{$registration['registration_number']}备</p>
            <p><strong>审核日期：</strong> {$registration['processed_at']}</p>
            <p>感谢您的耐心等待，如有任何问题，请随时联系我们。</p>
            <a href="{$registration['website_url']}" class="btn">访问网站</a>
        </div>
        <!-- 页脚已删除 -->
    </div>
</body>
</html>
HTML;

        // 发送邮件
        return $this->sendEmail($registration['contact_email'], $subject, $message);
    }

    // 发送邮件的核心函数
    private function sendEmail($to, $subject, $message) {
        // 创建邮件头部
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=utf-8\r\n";
        $headers .= "From: {$this->config['from_name']} <{$this->config['from_email']}>\r\n";

        // 根据配置选择不同的发送方式
        if ($this->config['smtp_encryption'] === 'ssl' || $this->config['smtp_encryption'] === 'tls') {
            // 使用SMTP发送
            return $this->sendSmtpEmail($to, $subject, $message, $headers);
        } else {
            // 使用PHP内置的mail函数发送
            return mail($to, $subject, $message, $headers);
        }
    }

    // 使用SMTP发送邮件
    private function sendSmtpEmail($to, $subject, $message, $headers) {
        try {
            // 建立SMTP连接
            $socket = fsockopen(
                $this->config['smtp_host'], 
                $this->config['smtp_port'], 
                $errno, 
                $errstr, 
                10
            );

            if (!$socket) {
                throw new Exception("无法连接到SMTP服务器: $errstr ($errno)");
            }

            // 检查服务器响应
            $this->checkResponse($socket, '220');

            // 发送EHLO命令
            fwrite($socket, "EHLO localhost\r\n");
            $this->checkResponse($socket, '250');

            // 开始TLS加密（如果需要）
            if ($this->config['smtp_encryption'] === 'tls') {
                fwrite($socket, "STARTTLS\r\n");
                $this->checkResponse($socket, '220');
                stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                fwrite($socket, "EHLO localhost\r\n");
                $this->checkResponse($socket, '250');
            }

            // 登录认证
            fwrite($socket, "AUTH LOGIN\r\n");
            $this->checkResponse($socket, '334');
            fwrite($socket, base64_encode($this->config['smtp_username']) . "\r\n");
            $this->checkResponse($socket, '334');
            fwrite($socket, base64_encode($this->config['smtp_password']) . "\r\n");
            $this->checkResponse($socket, '235');

            // 设置发件人和收件人
            fwrite($socket, "MAIL FROM: <{$this->config['from_email']}>\r\n");
            $this->checkResponse($socket, '250');
            fwrite($socket, "RCPT TO: <$to>\r\n");
            $this->checkResponse($socket, '250');

            // 发送邮件内容
            fwrite($socket, "DATA\r\n");
            $this->checkResponse($socket, '354');
            fwrite($socket, "Subject: $subject\r\n");
            fwrite($socket, $headers . "\r\n");
            fwrite($socket, "$message\r\n.\r\n");
            $this->checkResponse($socket, '250');

            // 退出会话
            fwrite($socket, "QUIT\r\n");
            $this->checkResponse($socket, '221');

            // 关闭连接
            fclose($socket);

            return true;
        } catch (Exception $e) {
            // 关闭连接
            if (isset($socket) && is_resource($socket)) {
                fclose($socket);
            }
            throw new Exception('发送邮件失败: ' . $e->getMessage());
        }
    }

    // 检查服务器响应
    private function checkResponse($socket, $expectedCode) {
        $response = fgets($socket, 515);
        if (substr($response, 0, 3) !== $expectedCode) {
            throw new Exception("SMTP错误: $response");
        }
        return $response;
    }
}
?>