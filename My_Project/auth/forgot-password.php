<?php
require_once '../config/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ' . BASE_URL . 'dashboard/index.php');
    exit();
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email'] ?? '');
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'กรุณากรอกอีเมลที่ถูกต้อง';
    } else {
        try {
            $stmt = $db->prepare("SELECT id, first_name, last_name FROM users WHERE email = ? AND is_active = 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Generate reset token
                $reset_token = bin2hex(random_bytes(32));
                $reset_expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour
                
                // Update user with reset token
                $stmt = $db->prepare("UPDATE users SET password_reset_token = ?, password_reset_expires = ? WHERE id = ?");
                $stmt->execute([$reset_token, $reset_expires, $user['id']]);
                
                // Send reset email (simplified - in production, use proper email service)
                $reset_link = BASE_URL . "auth/reset-password.php?token=" . $reset_token;
                
                // Log activity
                logActivity('forgot_password', 'Password reset requested for: ' . $email);
                
                $success_message = 'เราได้ส่งลิงก์รีเซ็ตรหัสผ่านไปยังอีเมลของคุณแล้ว';
            } else {
                $error_message = 'ไม่พบอีเมลนี้ในระบบ';
            }
        } catch (Exception $e) {
            error_log("Forgot password error: " . $e->getMessage());
            $error_message = 'เกิดข้อผิดพลาดในการส่งอีเมลรีเซ็ตรหัสผ่าน';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ลืมรหัสผ่าน - Task Tracking System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Sarabun', sans-serif;
        }
        .forgot-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }
        .btn-reset {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            border-radius: 25px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-reset:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        .form-control {
            border-radius: 15px;
            border: 2px solid #e9ecef;
            padding: 12px 20px;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6 col-lg-4">
                <div class="forgot-card p-5">
                    <div class="text-center mb-4">
                        <i class="fas fa-key fa-3x text-primary mb-3"></i>
                        <h3 class="fw-bold">ลืมรหัสผ่าน</h3>
                        <p class="text-muted">กรุณากรอกอีเมลเพื่อรีเซ็ตรหัสผ่าน</p>
                    </div>
                    
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?php echo $error_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success_message): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo $success_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope me-2"></i>อีเมล
                            </label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-reset">
                                <i class="fas fa-paper-plane me-2"></i>ส่งลิงก์รีเซ็ต
                            </button>
                        </div>
                    </form>
                    
                    <div class="text-center mt-4">
                        <a href="login.php" class="text-decoration-none">
                            <i class="fas fa-arrow-left me-1"></i>กลับไปเข้าสู่ระบบ
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
