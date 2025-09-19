<?php
require_once '../config/config.php';
requireLogin();

// Check admin permission
if (!hasPermission('admin')) {
    $_SESSION['error_message'] = 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้';
    header('Location: ' . BASE_URL . 'dashboard/index.php');
    exit();
}

$page_title = 'ตั้งค่าระบบ - Task Tracking System';

$error_message = '';
$success_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_general_settings') {
        $app_name = sanitizeInput($_POST['app_name'] ?? '');
        $app_description = sanitizeInput($_POST['app_description'] ?? '');
        $timezone = sanitizeInput($_POST['timezone'] ?? '');
        $language = sanitizeInput($_POST['language'] ?? '');
        $items_per_page = (int)($_POST['items_per_page'] ?? 10);
        $session_timeout = (int)($_POST['session_timeout'] ?? 3600);
        
        if (empty($app_name) || $items_per_page < 1 || $session_timeout < 300) {
            $error_message = 'กรุณากรอกข้อมูลให้ถูกต้อง';
        } else {
            try {
                // Update general settings in database or config file
                // For now, we'll store in a simple settings table
                $settings = [
                    'app_name' => $app_name,
                    'app_description' => $app_description,
                    'timezone' => $timezone,
                    'language' => $language,
                    'items_per_page' => $items_per_page,
                    'session_timeout' => $session_timeout
                ];
                
                // Store in session for demo (in production, store in database)
                $_SESSION['app_settings'] = $settings;
                
                // Log activity
                logActivity('settings_update', 'Updated general settings');
                
                $success_message = 'บันทึกการตั้งค่าทั่วไปสำเร็จ';
            } catch (Exception $e) {
                error_log("Settings update error: " . $e->getMessage());
                $error_message = 'เกิดข้อผิดพลาดในการบันทึกการตั้งค่า';
            }
        }
    } elseif ($action === 'update_email_settings') {
        $smtp_host = sanitizeInput($_POST['smtp_host'] ?? '');
        $smtp_port = (int)($_POST['smtp_port'] ?? 587);
        $smtp_username = sanitizeInput($_POST['smtp_username'] ?? '');
        $smtp_password = $_POST['smtp_password'] ?? '';
        $from_email = sanitizeInput($_POST['from_email'] ?? '');
        $from_name = sanitizeInput($_POST['from_name'] ?? '');
        
        if (empty($smtp_host) || empty($from_email) || empty($from_name)) {
            $error_message = 'กรุณากรอกข้อมูลอีเมลให้ครบถ้วน';
        } elseif (!filter_var($from_email, FILTER_VALIDATE_EMAIL)) {
            $error_message = 'รูปแบบอีเมลไม่ถูกต้อง';
        } else {
            try {
                // Update email settings
                $email_settings = [
                    'smtp_host' => $smtp_host,
                    'smtp_port' => $smtp_port,
                    'smtp_username' => $smtp_username,
                    'smtp_password' => $smtp_password,
                    'from_email' => $from_email,
                    'from_name' => $from_name
                ];
                
                // Store in session for demo (in production, store in database)
                $_SESSION['email_settings'] = $email_settings;
                
                // Log activity
                logActivity('settings_update', 'Updated email settings');
                
                $success_message = 'บันทึกการตั้งค่าอีเมลสำเร็จ';
            } catch (Exception $e) {
                error_log("Email settings update error: " . $e->getMessage());
                $error_message = 'เกิดข้อผิดพลาดในการบันทึกการตั้งค่าอีเมล';
            }
        }
    } elseif ($action === 'update_security_settings') {
        $password_min_length = (int)($_POST['password_min_length'] ?? 8);
        $max_login_attempts = (int)($_POST['max_login_attempts'] ?? 5);
        $lockout_duration = (int)($_POST['lockout_duration'] ?? 15);
        $require_2fa = isset($_POST['require_2fa']) ? 1 : 0;
        $enable_audit_log = isset($_POST['enable_audit_log']) ? 1 : 0;
        
        if ($password_min_length < 6 || $max_login_attempts < 1 || $lockout_duration < 1) {
            $error_message = 'กรุณากรอกข้อมูลความปลอดภัยให้ถูกต้อง';
        } else {
            try {
                // Update security settings
                $security_settings = [
                    'password_min_length' => $password_min_length,
                    'max_login_attempts' => $max_login_attempts,
                    'lockout_duration' => $lockout_duration,
                    'require_2fa' => $require_2fa,
                    'enable_audit_log' => $enable_audit_log
                ];
                
                // Store in session for demo (in production, store in database)
                $_SESSION['security_settings'] = $security_settings;
                
                // Log activity
                logActivity('settings_update', 'Updated security settings');
                
                $success_message = 'บันทึกการตั้งค่าความปลอดภัยสำเร็จ';
            } catch (Exception $e) {
                error_log("Security settings update error: " . $e->getMessage());
                $error_message = 'เกิดข้อผิดพลาดในการบันทึกการตั้งค่าความปลอดภัย';
            }
        }
    } elseif ($action === 'update_file_settings') {
        $max_file_size = (int)($_POST['max_file_size'] ?? 5);
        $allowed_extensions = $_POST['allowed_extensions'] ?? [];
        $upload_path = sanitizeInput($_POST['upload_path'] ?? 'uploads/');
        
        if ($max_file_size < 1 || empty($allowed_extensions) || empty($upload_path)) {
            $error_message = 'กรุณากรอกข้อมูลการอัปโหลดไฟล์ให้ถูกต้อง';
        } else {
            try {
                // Update file settings
                $file_settings = [
                    'max_file_size' => $max_file_size * 1024 * 1024, // Convert to bytes
                    'allowed_extensions' => $allowed_extensions,
                    'upload_path' => $upload_path
                ];
                
                // Store in session for demo (in production, store in database)
                $_SESSION['file_settings'] = $file_settings;
                
                // Log activity
                logActivity('settings_update', 'Updated file settings');
                
                $success_message = 'บันทึกการตั้งค่าไฟล์สำเร็จ';
            } catch (Exception $e) {
                error_log("File settings update error: " . $e->getMessage());
                $error_message = 'เกิดข้อผิดพลาดในการบันทึกการตั้งค่าไฟล์';
            }
        }
    }
}

// Get current settings (default values)
$app_settings = $_SESSION['app_settings'] ?? [
    'app_name' => 'Task Tracking System',
    'app_description' => 'ระบบติดตามงานและเวลา',
    'timezone' => 'Asia/Bangkok',
    'language' => 'th',
    'items_per_page' => 10,
    'session_timeout' => 3600
];

$email_settings = $_SESSION['email_settings'] ?? [
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_username' => 'your-email@gmail.com',
    'smtp_password' => '',
    'from_email' => 'noreply@yourcompany.com',
    'from_name' => 'Task Tracking System'
];

$security_settings = $_SESSION['security_settings'] ?? [
    'password_min_length' => 8,
    'max_login_attempts' => 5,
    'lockout_duration' => 15,
    'require_2fa' => 0,
    'enable_audit_log' => 1
];

$file_settings = $_SESSION['file_settings'] ?? [
    'max_file_size' => 5 * 1024 * 1024,
    'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt'],
    'upload_path' => 'uploads/'
];

include '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h1 class="h3 mb-0">
            <i class="fas fa-cog me-2"></i>ตั้งค่าระบบ
        </h1>
        <p class="text-muted">จัดการการตั้งค่าระบบและความปลอดภัย</p>
    </div>
</div>

<!-- Messages -->
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

<!-- Settings Tabs -->
<div class="card">
    <div class="card-header">
        <ul class="nav nav-tabs card-header-tabs" id="settingsTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab">
                    <i class="fas fa-cog me-2"></i>การตั้งค่าทั่วไป
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="email-tab" data-bs-toggle="tab" data-bs-target="#email" type="button" role="tab">
                    <i class="fas fa-envelope me-2"></i>การตั้งค่าอีเมล
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab">
                    <i class="fas fa-shield-alt me-2"></i>ความปลอดภัย
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="files-tab" data-bs-toggle="tab" data-bs-target="#files" type="button" role="tab">
                    <i class="fas fa-file-upload me-2"></i>การอัปโหลดไฟล์
                </button>
            </li>
        </ul>
    </div>
    <div class="card-body">
        <div class="tab-content" id="settingsTabContent">
            <!-- General Settings -->
            <div class="tab-pane fade show active" id="general" role="tabpanel">
                <form method="POST">
                    <input type="hidden" name="action" value="update_general_settings">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="app_name" class="form-label">ชื่อระบบ</label>
                            <input type="text" class="form-control" id="app_name" name="app_name" 
                                   value="<?php echo htmlspecialchars($app_settings['app_name']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="timezone" class="form-label">เขตเวลา</label>
                            <select class="form-select" id="timezone" name="timezone">
                                <option value="Asia/Bangkok" <?php echo $app_settings['timezone'] === 'Asia/Bangkok' ? 'selected' : ''; ?>>Asia/Bangkok</option>
                                <option value="UTC" <?php echo $app_settings['timezone'] === 'UTC' ? 'selected' : ''; ?>>UTC</option>
                                <option value="America/New_York" <?php echo $app_settings['timezone'] === 'America/New_York' ? 'selected' : ''; ?>>America/New_York</option>
                                <option value="Europe/London" <?php echo $app_settings['timezone'] === 'Europe/London' ? 'selected' : ''; ?>>Europe/London</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="app_description" class="form-label">คำอธิบายระบบ</label>
                        <textarea class="form-control" id="app_description" name="app_description" rows="3"><?php echo htmlspecialchars($app_settings['app_description']); ?></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="language" class="form-label">ภาษา</label>
                            <select class="form-select" id="language" name="language">
                                <option value="th" <?php echo $app_settings['language'] === 'th' ? 'selected' : ''; ?>>ไทย</option>
                                <option value="en" <?php echo $app_settings['language'] === 'en' ? 'selected' : ''; ?>>English</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="items_per_page" class="form-label">จำนวนรายการต่อหน้า</label>
                            <input type="number" class="form-control" id="items_per_page" name="items_per_page" 
                                   value="<?php echo $app_settings['items_per_page']; ?>" min="5" max="100" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="session_timeout" class="form-label">หมดอายุเซสชัน (วินาที)</label>
                            <input type="number" class="form-control" id="session_timeout" name="session_timeout" 
                                   value="<?php echo $app_settings['session_timeout']; ?>" min="300" max="86400" required>
                        </div>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>บันทึกการตั้งค่าทั่วไป
                        </button>
                    </div>
                </form>
            </div>

            <!-- Email Settings -->
            <div class="tab-pane fade" id="email" role="tabpanel">
                <form method="POST">
                    <input type="hidden" name="action" value="update_email_settings">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="smtp_host" class="form-label">SMTP Host</label>
                            <input type="text" class="form-control" id="smtp_host" name="smtp_host" 
                                   value="<?php echo htmlspecialchars($email_settings['smtp_host']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="smtp_port" class="form-label">SMTP Port</label>
                            <input type="number" class="form-control" id="smtp_port" name="smtp_port" 
                                   value="<?php echo $email_settings['smtp_port']; ?>" min="1" max="65535" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="smtp_username" class="form-label">SMTP Username</label>
                            <input type="text" class="form-control" id="smtp_username" name="smtp_username" 
                                   value="<?php echo htmlspecialchars($email_settings['smtp_username']); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="smtp_password" class="form-label">SMTP Password</label>
                            <input type="password" class="form-control" id="smtp_password" name="smtp_password" 
                                   placeholder="กรอกรหัสผ่านใหม่หากต้องการเปลี่ยน">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="from_email" class="form-label">อีเมลผู้ส่ง</label>
                            <input type="email" class="form-control" id="from_email" name="from_email" 
                                   value="<?php echo htmlspecialchars($email_settings['from_email']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="from_name" class="form-label">ชื่อผู้ส่ง</label>
                            <input type="text" class="form-control" id="from_name" name="from_name" 
                                   value="<?php echo htmlspecialchars($email_settings['from_name']); ?>" required>
                        </div>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>บันทึกการตั้งค่าอีเมล
                        </button>
                    </div>
                </form>
            </div>

            <!-- Security Settings -->
            <div class="tab-pane fade" id="security" role="tabpanel">
                <form method="POST">
                    <input type="hidden" name="action" value="update_security_settings">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="password_min_length" class="form-label">ความยาวรหัสผ่านขั้นต่ำ</label>
                            <input type="number" class="form-control" id="password_min_length" name="password_min_length" 
                                   value="<?php echo $security_settings['password_min_length']; ?>" min="6" max="50" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="max_login_attempts" class="form-label">จำนวนครั้งลองเข้าสู่ระบบสูงสุด</label>
                            <input type="number" class="form-control" id="max_login_attempts" name="max_login_attempts" 
                                   value="<?php echo $security_settings['max_login_attempts']; ?>" min="1" max="10" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="lockout_duration" class="form-label">ระยะเวลาล็อค (นาที)</label>
                            <input type="number" class="form-control" id="lockout_duration" name="lockout_duration" 
                                   value="<?php echo $security_settings['lockout_duration']; ?>" min="1" max="60" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="require_2fa" name="require_2fa" 
                                       <?php echo $security_settings['require_2fa'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="require_2fa">
                                    ต้องการการยืนยันตัวตน 2 ขั้นตอน
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="enable_audit_log" name="enable_audit_log" 
                                       <?php echo $security_settings['enable_audit_log'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="enable_audit_log">
                                    เปิดใช้งาน Audit Log
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>บันทึกการตั้งค่าความปลอดภัย
                        </button>
                    </div>
                </form>
            </div>

            <!-- File Settings -->
            <div class="tab-pane fade" id="files" role="tabpanel">
                <form method="POST">
                    <input type="hidden" name="action" value="update_file_settings">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="max_file_size" class="form-label">ขนาดไฟล์สูงสุด (MB)</label>
                            <input type="number" class="form-control" id="max_file_size" name="max_file_size" 
                                   value="<?php echo $file_settings['max_file_size'] / (1024 * 1024); ?>" min="1" max="100" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="upload_path" class="form-label">โฟลเดอร์อัปโหลด</label>
                            <input type="text" class="form-control" id="upload_path" name="upload_path" 
                                   value="<?php echo htmlspecialchars($file_settings['upload_path']); ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ประเภทไฟล์ที่อนุญาต</label>
                        <div class="row">
                            <?php
                            $all_extensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt', 'xls', 'xlsx', 'ppt', 'pptx', 'zip', 'rar'];
                            foreach ($all_extensions as $ext):
                            ?>
                            <div class="col-md-3 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="ext_<?php echo $ext; ?>" 
                                           name="allowed_extensions[]" value="<?php echo $ext; ?>"
                                           <?php echo in_array($ext, $file_settings['allowed_extensions']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="ext_<?php echo $ext; ?>">
                                        .<?php echo $ext; ?>
                                    </label>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>บันทึกการตั้งค่าไฟล์
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- System Information -->
<div class="card mt-4">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fas fa-info-circle me-2"></i>ข้อมูลระบบ
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <table class="table table-borderless">
                    <tr>
                        <td><strong>เวอร์ชัน PHP:</strong></td>
                        <td><?php echo PHP_VERSION; ?></td>
                    </tr>
                    <tr>
                        <td><strong>เวอร์ชัน MySQL:</strong></td>
                        <td><?php echo $db->query('SELECT VERSION()')->fetchColumn(); ?></td>
                    </tr>
                    <tr>
                        <td><strong>ระบบปฏิบัติการ:</strong></td>
                        <td><?php echo PHP_OS; ?></td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-borderless">
                    <tr>
                        <td><strong>หน่วยความจำที่ใช้:</strong></td>
                        <td><?php echo round(memory_get_usage() / 1024 / 1024, 2); ?> MB</td>
                    </tr>
                    <tr>
                        <td><strong>หน่วยความจำสูงสุด:</strong></td>
                        <td><?php echo ini_get('memory_limit'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>เวลาปัจจุบัน:</strong></td>
                        <td><?php echo date('d/m/Y H:i:s'); ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
