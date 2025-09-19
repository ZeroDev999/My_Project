<?php
require_once '../config/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ' . BASE_URL . 'dashboard/index.php');
    exit();
}

$error_message = '';
$success_message = '';
$token = $_GET['token'] ?? '';

// Validate token
if (empty($token)) {
    $error_message = 'ลิงก์รีเซ็ตรหัสผ่านไม่ถูกต้อง';
} else {
    try {
        $stmt = $db->prepare("SELECT id, first_name, last_name FROM users WHERE password_reset_token = ? AND password_reset_expires > NOW() AND is_active = 1");
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        
        if (!$user) {
            $error_message = 'ลิงก์รีเซ็ตรหัสผ่านไม่ถูกต้องหรือหมดอายุแล้ว';
        }
    } catch (Exception $e) {
        error_log("Reset password token validation error: " . $e->getMessage());
        $error_message = 'เกิดข้อผิดพลาดในการตรวจสอบลิงก์';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error_message) {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($password) || empty($confirm_password)) {
        $error_message = 'กรุณากรอกรหัสผ่านใหม่';
    } elseif (strlen($password) < PASSWORD_MIN_LENGTH) {
        $error_message = 'รหัสผ่านต้องมีอย่างน้อย ' . PASSWORD_MIN_LENGTH . ' ตัวอักษร';
    } elseif ($password !== $confirm_password) {
        $error_message = 'รหัสผ่านไม่ตรงกัน';
    } else {
        try {
            // Hash new password
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Update password and clear reset token
            $stmt = $db->prepare("UPDATE users SET password_hash = ?, password_reset_token = NULL, password_reset_expires = NULL WHERE id = ?");
            $stmt->execute([$password_hash, $user['id']]);
            
            // Log activity
            logActivity('password_reset', 'Password reset completed for user ID: ' . $user['id']);
            
            $success_message = 'รีเซ็ตรหัสผ่านสำเร็จ! กรุณาเข้าสู่ระบบด้วยรหัสผ่านใหม่';
        } catch (Exception $e) {
            error_log("Reset password error: " . $e->getMessage());
            $error_message = 'เกิดข้อผิดพลาดในการรีเซ็ตรหัสผ่าน';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รีเซ็ตรหัสผ่าน - Task Tracking System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Sarabun', sans-serif;
        }
        .reset-card {
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
                <div class="reset-card p-5">
                    <div class="text-center mb-4">
                        <i class="fas fa-lock fa-3x text-primary mb-3"></i>
                        <h3 class="fw-bold">รีเซ็ตรหัสผ่าน</h3>
                        <p class="text-muted">กรุณากรอกรหัสผ่านใหม่</p>
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
                    
                    <?php if (!$error_message && !$success_message): ?>
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="password" class="form-label">
                                    <i class="fas fa-lock me-2"></i>รหัสผ่านใหม่
                                </label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <div class="form-text">รหัสผ่านต้องมีอย่างน้อย <?php echo PASSWORD_MIN_LENGTH; ?> ตัวอักษร</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">
                                    <i class="fas fa-lock me-2"></i>ยืนยันรหัสผ่านใหม่
                                </label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-reset">
                                    <i class="fas fa-save me-2"></i>รีเซ็ตรหัสผ่าน
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                    
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
