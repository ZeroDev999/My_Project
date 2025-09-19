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
    $username = sanitizeInput($_POST['username'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $first_name = sanitizeInput($_POST['first_name'] ?? '');
    $last_name = sanitizeInput($_POST['last_name'] ?? '');
    $role = sanitizeInput($_POST['role'] ?? 'dev');
    
    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($first_name) || empty($last_name)) {
        $error_message = 'กรุณากรอกข้อมูลให้ครบถ้วน';
    } elseif (strlen($password) < PASSWORD_MIN_LENGTH) {
        $error_message = 'รหัสผ่านต้องมีอย่างน้อย ' . PASSWORD_MIN_LENGTH . ' ตัวอักษร';
    } elseif ($password !== $confirm_password) {
        $error_message = 'รหัสผ่านไม่ตรงกัน';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'รูปแบบอีเมลไม่ถูกต้อง';
    } else {
        try {
            // Check if username or email already exists
            $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                $error_message = 'ชื่อผู้ใช้หรืออีเมลนี้มีอยู่แล้ว';
            } else {
                // Hash password
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert user with email_verified = 1 by default
                $stmt = $db->prepare("INSERT INTO users (username, email, password_hash, first_name, last_name, role, email_verified) VALUES (?, ?, ?, ?, ?, ?, 1)");
                $stmt->execute([$username, $email, $password_hash, $first_name, $last_name, $role]);
                
                // Log activity
                logActivity('register', 'New user registered: ' . $username);
                
                $success_message = 'สมัครสมาชิกสำเร็จ! ตอนนี้คุณสามารถเข้าสู่ระบบได้แล้ว';
            }
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            $error_message = 'เกิดข้อผิดพลาดในการสมัครสมาชิก';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิก - Task Tracking System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Sarabun', sans-serif;
        }
        .register-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }
        .btn-register {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            border-radius: 25px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-register:hover {
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
        <div class="row justify-content-center align-items-center min-vh-100 py-5">
            <div class="col-md-8 col-lg-6">
                <div class="register-card p-5">
                    <div class="text-center mb-4">
                        <i class="fas fa-user-plus fa-3x text-primary mb-3"></i>
                        <h3 class="fw-bold">สมัครสมาชิก</h3>
                        <p class="text-muted">Task Tracking System</p>
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
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="first_name" class="form-label">
                                    <i class="fas fa-user me-2"></i>ชื่อ
                                </label>
                                <input type="text" class="form-control" id="first_name" name="first_name" 
                                       value="<?php echo htmlspecialchars($first_name ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="last_name" class="form-label">
                                    <i class="fas fa-user me-2"></i>นามสกุล
                                </label>
                                <input type="text" class="form-control" id="last_name" name="last_name" 
                                       value="<?php echo htmlspecialchars($last_name ?? ''); ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="username" class="form-label">
                                <i class="fas fa-at me-2"></i>ชื่อผู้ใช้
                            </label>
                            <input type="text" class="form-control" id="username" name="username" 
                                   value="<?php echo htmlspecialchars($username ?? ''); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope me-2"></i>อีเมล
                            </label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="role" class="form-label">
                                <i class="fas fa-user-tag me-2"></i>บทบาท
                            </label>
                            <select class="form-control" id="role" name="role" required>
                                <option value="dev" <?php echo (($role ?? '') === 'dev') ? 'selected' : ''; ?>>Developer</option>
                                <option value="manager" <?php echo (($role ?? '') === 'manager') ? 'selected' : ''; ?>>Manager</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">
                                <i class="fas fa-lock me-2"></i>รหัสผ่าน
                            </label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <div class="form-text">รหัสผ่านต้องมีอย่างน้อย <?php echo PASSWORD_MIN_LENGTH; ?> ตัวอักษร</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">
                                <i class="fas fa-lock me-2"></i>ยืนยันรหัสผ่าน
                            </label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-register">
                                <i class="fas fa-user-plus me-2"></i>สมัครสมาชิก
                            </button>
                        </div>
                    </form>
                    
                    <div class="text-center mt-4">
                        <a href="login.php" class="text-decoration-none">
                            <i class="fas fa-sign-in-alt me-1"></i>เข้าสู่ระบบ
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
