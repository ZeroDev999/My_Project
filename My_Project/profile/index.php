<?php
require_once '../config/config.php';
requireLogin();

$page_title = 'โปรไฟล์ - Task Tracking System';

$error_message = '';
$success_message = '';

// Get user data
try {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user) {
        header('Location: ' . BASE_URL . 'auth/logout.php');
        exit();
    }
} catch (Exception $e) {
    error_log("Profile error: " . $e->getMessage());
    $error_message = 'เกิดข้อผิดพลาดในการโหลดข้อมูลโปรไฟล์';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $first_name = sanitizeInput($_POST['first_name'] ?? '');
        $last_name = sanitizeInput($_POST['last_name'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        
        if (empty($first_name) || empty($last_name) || empty($email)) {
            $error_message = 'กรุณากรอกข้อมูลให้ครบถ้วน';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = 'รูปแบบอีเมลไม่ถูกต้อง';
        } else {
            try {
                // Check if email is already used by another user
                $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$email, $_SESSION['user_id']]);
                if ($stmt->fetch()) {
                    $error_message = 'อีเมลนี้ถูกใช้งานแล้ว';
                } else {
                    // Update profile
                    $stmt = $db->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$first_name, $last_name, $email, $_SESSION['user_id']]);
                    
                    // Update session
                    $_SESSION['user_name'] = $first_name . ' ' . $last_name;
                    
                    // Log activity
                    logActivity('profile_update', 'Profile updated');
                    
                    $success_message = 'อัปเดตโปรไฟล์สำเร็จ';
                    
                    // Refresh user data
                    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $user = $stmt->fetch();
                }
            } catch (Exception $e) {
                error_log("Profile update error: " . $e->getMessage());
                $error_message = 'เกิดข้อผิดพลาดในการอัปเดตโปรไฟล์';
            }
        }
    } elseif ($action === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error_message = 'กรุณากรอกข้อมูลให้ครบถ้วน';
        } elseif (strlen($new_password) < PASSWORD_MIN_LENGTH) {
            $error_message = 'รหัสผ่านใหม่ต้องมีอย่างน้อย ' . PASSWORD_MIN_LENGTH . ' ตัวอักษร';
        } elseif ($new_password !== $confirm_password) {
            $error_message = 'รหัสผ่านใหม่ไม่ตรงกัน';
        } elseif (!password_verify($current_password, $user['password_hash'])) {
            $error_message = 'รหัสผ่านปัจจุบันไม่ถูกต้อง';
        } else {
            try {
                $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$new_password_hash, $_SESSION['user_id']]);
                
                // Log activity
                logActivity('password_change', 'Password changed');
                
                $success_message = 'เปลี่ยนรหัสผ่านสำเร็จ';
            } catch (Exception $e) {
                error_log("Password change error: " . $e->getMessage());
                $error_message = 'เกิดข้อผิดพลาดในการเปลี่ยนรหัสผ่าน';
            }
        }
    }
}

include '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h1 class="h3 mb-0">
            <i class="fas fa-user me-2"></i>โปรไฟล์
        </h1>
        <p class="text-muted">จัดการข้อมูลส่วนตัวและรหัสผ่าน</p>
    </div>
</div>

<div class="row">
    <!-- Profile Information -->
    <div class="col-lg-8 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-user-edit me-2"></i>ข้อมูลส่วนตัว
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label">ชื่อ</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" 
                                   value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label">นามสกุล</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" 
                                   value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">ชื่อผู้ใช้</label>
                        <input type="text" class="form-control" id="username" 
                               value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                        <div class="form-text">ไม่สามารถเปลี่ยนชื่อผู้ใช้ได้</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">อีเมล</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">บทบาท</label>
                        <input type="text" class="form-control" 
                               value="<?php 
                               $role_texts = ['admin' => 'ผู้ดูแลระบบ', 'manager' => 'ผู้จัดการ', 'dev' => 'นักพัฒนา'];
                               echo $role_texts[$user['role']]; 
                               ?>" disabled>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">สถานะอีเมล</label>
                        <div>
                            <?php if ($user['email_verified']): ?>
                                <span class="badge bg-success">
                                    <i class="fas fa-check me-1"></i>ยืนยันแล้ว
                                </span>
                            <?php else: ?>
                                <span class="badge bg-warning">
                                    <i class="fas fa-exclamation-triangle me-1"></i>ยังไม่ยืนยัน
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">สมาชิกตั้งแต่</label>
                        <input type="text" class="form-control" 
                               value="<?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?>" disabled>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">เข้าสู่ระบบล่าสุด</label>
                        <input type="text" class="form-control" 
                               value="<?php echo $user['last_login'] ? date('d/m/Y H:i', strtotime($user['last_login'])) : 'ไม่เคย'; ?>" disabled>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>บันทึกการเปลี่ยนแปลง
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Change Password -->
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-lock me-2"></i>เปลี่ยนรหัสผ่าน
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="change_password">
                    
                    <div class="mb-3">
                        <label for="current_password" class="form-label">รหัสผ่านปัจจุบัน</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_password" class="form-label">รหัสผ่านใหม่</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                        <div class="form-text">รหัสผ่านต้องมีอย่างน้อย <?php echo PASSWORD_MIN_LENGTH; ?> ตัวอักษร</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">ยืนยันรหัสผ่านใหม่</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <button type="submit" class="btn btn-warning w-100">
                        <i class="fas fa-key me-2"></i>เปลี่ยนรหัสผ่าน
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Account Actions -->
        <div class="card mt-3">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-cog me-2"></i>การดำเนินการ
                </h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="<?php echo BASE_URL; ?>auth/forgot-password.php" class="btn btn-outline-primary">
                        <i class="fas fa-key me-2"></i>ลืมรหัสผ่าน
                    </a>
                    
                    <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                        <i class="fas fa-trash me-2"></i>ลบบัญชี
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Account Modal -->
<div class="modal fade" id="deleteAccountModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">ยืนยันการลบบัญชี</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>คำเตือน:</strong> การลบบัญชีจะไม่สามารถกู้คืนได้ และข้อมูลทั้งหมดจะถูกลบอย่างถาวร
                </div>
                <p>คุณแน่ใจหรือไม่ที่จะลบบัญชีของคุณ?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" class="btn btn-danger" onclick="deleteAccount()">ลบบัญชี</button>
            </div>
        </div>
    </div>
</div>

<script>
function deleteAccount() {
    if (confirm('คุณแน่ใจหรือไม่ที่จะลบบัญชีของคุณ? การดำเนินการนี้ไม่สามารถยกเลิกได้!')) {
        // In a real application, you would send a request to delete the account
        alert('ฟีเจอร์นี้ยังไม่พร้อมใช้งาน');
    }
}
</script>

<?php include '../includes/footer.php'; ?>
