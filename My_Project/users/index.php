<?php
require_once '../config/config.php';
requireLogin();

// Check admin permission
if (!hasPermission('admin')) {
    $_SESSION['error_message'] = 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้';
    header('Location: ' . BASE_URL . 'dashboard/index.php');
    exit();
}

$page_title = 'จัดการผู้ใช้ - Task Tracking System';

$error_message = '';
$success_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create_user') {
        $username = sanitizeInput($_POST['username'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $first_name = sanitizeInput($_POST['first_name'] ?? '');
        $last_name = sanitizeInput($_POST['last_name'] ?? '');
        $role = sanitizeInput($_POST['role'] ?? 'dev');
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if (empty($username) || empty($email) || empty($password) || empty($first_name) || empty($last_name)) {
            $error_message = 'กรุณากรอกข้อมูลให้ครบถ้วน';
        } elseif (strlen($password) < PASSWORD_MIN_LENGTH) {
            $error_message = 'รหัสผ่านต้องมีอย่างน้อย ' . PASSWORD_MIN_LENGTH . ' ตัวอักษร';
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
                    
                    // Insert user
                    $stmt = $db->prepare("INSERT INTO users (username, email, password_hash, first_name, last_name, role, is_active, email_verified) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
                    $stmt->execute([$username, $email, $password_hash, $first_name, $last_name, $role, $is_active]);
                    
                    // Log activity
                    logActivity('user_create', 'Created user: ' . $username);
                    
                    $success_message = 'สร้างผู้ใช้สำเร็จ';
                }
            } catch (Exception $e) {
                error_log("User creation error: " . $e->getMessage());
                $error_message = 'เกิดข้อผิดพลาดในการสร้างผู้ใช้';
            }
        }
    } elseif ($action === 'update_user') {
        $user_id = (int)($_POST['user_id'] ?? 0);
        $username = sanitizeInput($_POST['username'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $first_name = sanitizeInput($_POST['first_name'] ?? '');
        $last_name = sanitizeInput($_POST['last_name'] ?? '');
        $role = sanitizeInput($_POST['role'] ?? 'dev');
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if (empty($username) || empty($email) || empty($first_name) || empty($last_name)) {
            $error_message = 'กรุณากรอกข้อมูลให้ครบถ้วน';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = 'รูปแบบอีเมลไม่ถูกต้อง';
        } else {
            try {
                // Check if username or email already exists (excluding current user)
                $stmt = $db->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
                $stmt->execute([$username, $email, $user_id]);
                if ($stmt->fetch()) {
                    $error_message = 'ชื่อผู้ใช้หรืออีเมลนี้มีอยู่แล้ว';
                } else {
                    // Update user
                    $stmt = $db->prepare("UPDATE users SET username = ?, email = ?, first_name = ?, last_name = ?, role = ?, is_active = ? WHERE id = ?");
                    $stmt->execute([$username, $email, $first_name, $last_name, $role, $is_active, $user_id]);
                    
                    // Log activity
                    logActivity('user_update', 'Updated user: ' . $username);
                    
                    $success_message = 'อัปเดตผู้ใช้สำเร็จ';
                }
            } catch (Exception $e) {
                error_log("User update error: " . $e->getMessage());
                $error_message = 'เกิดข้อผิดพลาดในการอัปเดตผู้ใช้';
            }
        }
    } elseif ($action === 'delete_user') {
        $user_id = (int)($_POST['user_id'] ?? 0);
        
        if ($user_id === $_SESSION['user_id']) {
            $error_message = 'ไม่สามารถลบบัญชีของตัวเองได้';
        } else {
            try {
                // Get user info for logging
                $stmt = $db->prepare("SELECT username FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();
                
                if ($user) {
                    // Delete user
                    $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    
                    // Log activity
                    logActivity('user_delete', 'Deleted user: ' . $user['username']);
                    
                    $success_message = 'ลบผู้ใช้สำเร็จ';
                } else {
                    $error_message = 'ไม่พบผู้ใช้ที่ต้องการลบ';
                }
            } catch (Exception $e) {
                error_log("User deletion error: " . $e->getMessage());
                $error_message = 'เกิดข้อผิดพลาดในการลบผู้ใช้';
            }
        }
    } elseif ($action === 'reset_password') {
        $user_id = (int)($_POST['user_id'] ?? 0);
        $new_password = $_POST['new_password'] ?? '';
        
        if (strlen($new_password) < PASSWORD_MIN_LENGTH) {
            $error_message = 'รหัสผ่านต้องมีอย่างน้อย ' . PASSWORD_MIN_LENGTH . ' ตัวอักษร';
        } else {
            try {
                // Hash new password
                $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                
                // Update password
                $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                $stmt->execute([$password_hash, $user_id]);
                
                // Log activity
                logActivity('user_password_reset', 'Reset password for user ID: ' . $user_id);
                
                $success_message = 'รีเซ็ตรหัสผ่านสำเร็จ';
            } catch (Exception $e) {
                error_log("Password reset error: " . $e->getMessage());
                $error_message = 'เกิดข้อผิดพลาดในการรีเซ็ตรหัสผ่าน';
            }
        }
    }
}

// Get users list
try {
    $search = $_GET['search'] ?? '';
    $role_filter = $_GET['role'] ?? '';
    $status_filter = $_GET['status'] ?? '';
    
    $where_conditions = [];
    $params = [];
    
    if (!empty($search)) {
        $where_conditions[] = "(username LIKE ? OR email LIKE ? OR first_name LIKE ? OR last_name LIKE ?)";
        $search_param = "%$search%";
        $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
    }
    
    if (!empty($role_filter)) {
        $where_conditions[] = "role = ?";
        $params[] = $role_filter;
    }
    
    if ($status_filter === 'active') {
        $where_conditions[] = "is_active = 1";
    } elseif ($status_filter === 'inactive') {
        $where_conditions[] = "is_active = 0";
    }
    
    $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
    
    $stmt = $db->prepare("
        SELECT id, username, email, first_name, last_name, role, is_active, email_verified, 
               last_login, created_at
        FROM users 
        $where_clause
        ORDER BY created_at DESC
    ");
    $stmt->execute($params);
    $users = $stmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Users list error: " . $e->getMessage());
    $users = [];
}

include '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h1 class="h3 mb-0">
            <i class="fas fa-users me-2"></i>จัดการผู้ใช้
        </h1>
        <p class="text-muted">จัดการข้อมูลผู้ใช้ในระบบ</p>
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

<!-- Filters and Search -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label for="search" class="form-label">ค้นหา</label>
                <input type="text" class="form-control" id="search" name="search" 
                       value="<?php echo htmlspecialchars($search); ?>" placeholder="ชื่อผู้ใช้, อีเมล, ชื่อ-นามสกุล">
            </div>
            <div class="col-md-3">
                <label for="role" class="form-label">บทบาท</label>
                <select class="form-select" id="role" name="role">
                    <option value="">ทั้งหมด</option>
                    <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                    <option value="manager" <?php echo $role_filter === 'manager' ? 'selected' : ''; ?>>Manager</option>
                    <option value="dev" <?php echo $role_filter === 'dev' ? 'selected' : ''; ?>>Developer</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="status" class="form-label">สถานะ</label>
                <select class="form-select" id="status" name="status">
                    <option value="">ทั้งหมด</option>
                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>ใช้งาน</option>
                    <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>ไม่ใช้งาน</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-1"></i>ค้นหา
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Users Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
            <i class="fas fa-list me-2"></i>รายการผู้ใช้ (<?php echo count($users); ?> คน)
        </h5>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">
            <i class="fas fa-plus me-1"></i>เพิ่มผู้ใช้
        </button>
    </div>
    <div class="card-body">
        <?php if (empty($users)): ?>
            <div class="text-center py-4">
                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                <p class="text-muted">ไม่พบผู้ใช้</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ผู้ใช้</th>
                            <th>อีเมล</th>
                            <th>บทบาท</th>
                            <th>สถานะ</th>
                            <th>เข้าสู่ระบบล่าสุด</th>
                            <th>วันที่สร้าง</th>
                            <th>การดำเนินการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="bg-primary bg-gradient rounded-circle d-flex align-items-center justify-content-center me-3" 
                                         style="width: 40px; height: 40px;">
                                        <i class="fas fa-user text-white"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
                                        <small class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <?php
                                $role_classes = [
                                    'admin' => 'bg-danger',
                                    'manager' => 'bg-warning',
                                    'dev' => 'bg-info'
                                ];
                                $role_texts = [
                                    'admin' => 'Admin',
                                    'manager' => 'Manager',
                                    'dev' => 'Developer'
                                ];
                                ?>
                                <span class="badge <?php echo $role_classes[$user['role']]; ?>">
                                    <?php echo $role_texts[$user['role']]; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($user['is_active']): ?>
                                    <span class="badge bg-success">ใช้งาน</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">ไม่ใช้งาน</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($user['last_login']): ?>
                                    <?php echo date('d/m/Y H:i', strtotime($user['last_login'])); ?>
                                <?php else: ?>
                                    <span class="text-muted">ไม่เคย</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                            onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-warning" 
                                            onclick="resetPassword(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')">
                                        <i class="fas fa-key"></i>
                                    </button>
                                    <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                            onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Create User Modal -->
<div class="modal fade" id="createUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user-plus me-2"></i>เพิ่มผู้ใช้ใหม่
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_user">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="create_first_name" class="form-label">ชื่อ</label>
                            <input type="text" class="form-control" id="create_first_name" name="first_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="create_last_name" class="form-label">นามสกุล</label>
                            <input type="text" class="form-control" id="create_last_name" name="last_name" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="create_username" class="form-label">ชื่อผู้ใช้</label>
                        <input type="text" class="form-control" id="create_username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="create_email" class="form-label">อีเมล</label>
                        <input type="email" class="form-control" id="create_email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="create_password" class="form-label">รหัสผ่าน</label>
                        <input type="password" class="form-control" id="create_password" name="password" required>
                        <div class="form-text">รหัสผ่านต้องมีอย่างน้อย <?php echo PASSWORD_MIN_LENGTH; ?> ตัวอักษร</div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="create_role" class="form-label">บทบาท</label>
                            <select class="form-select" id="create_role" name="role" required>
                                <option value="dev">Developer</option>
                                <option value="manager">Manager</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" id="create_is_active" name="is_active" checked>
                                <label class="form-check-label" for="create_is_active">
                                    เปิดใช้งาน
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">สร้างผู้ใช้</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user-edit me-2"></i>แก้ไขผู้ใช้
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_user">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_first_name" class="form-label">ชื่อ</label>
                            <input type="text" class="form-control" id="edit_first_name" name="first_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_last_name" class="form-label">นามสกุล</label>
                            <input type="text" class="form-control" id="edit_last_name" name="last_name" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_username" class="form-label">ชื่อผู้ใช้</label>
                        <input type="text" class="form-control" id="edit_username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_email" class="form-label">อีเมล</label>
                        <input type="email" class="form-control" id="edit_email" name="email" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_role" class="form-label">บทบาท</label>
                            <select class="form-select" id="edit_role" name="role" required>
                                <option value="dev">Developer</option>
                                <option value="manager">Manager</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active">
                                <label class="form-check-label" for="edit_is_active">
                                    เปิดใช้งาน
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">บันทึกการเปลี่ยนแปลง</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reset Password Modal -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-key me-2"></i>รีเซ็ตรหัสผ่าน
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="reset_password">
                    <input type="hidden" name="user_id" id="reset_user_id">
                    <div class="mb-3">
                        <label class="form-label">ผู้ใช้</label>
                        <input type="text" class="form-control" id="reset_username" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">รหัสผ่านใหม่</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                        <div class="form-text">รหัสผ่านต้องมีอย่างน้อย <?php echo PASSWORD_MIN_LENGTH; ?> ตัวอักษร</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-warning">รีเซ็ตรหัสผ่าน</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete User Modal -->
<div class="modal fade" id="deleteUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-2"></i>ยืนยันการลบ
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="delete_user">
                    <input type="hidden" name="user_id" id="delete_user_id">
                    <p>คุณแน่ใจหรือไม่ที่จะลบผู้ใช้ <strong id="delete_username"></strong>?</p>
                    <p class="text-danger">การดำเนินการนี้ไม่สามารถย้อนกลับได้</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-danger">ลบผู้ใช้</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editUser(user) {
    document.getElementById('edit_user_id').value = user.id;
    document.getElementById('edit_first_name').value = user.first_name;
    document.getElementById('edit_last_name').value = user.last_name;
    document.getElementById('edit_username').value = user.username;
    document.getElementById('edit_email').value = user.email;
    document.getElementById('edit_role').value = user.role;
    document.getElementById('edit_is_active').checked = user.is_active == 1;
    
    new bootstrap.Modal(document.getElementById('editUserModal')).show();
}

function resetPassword(userId, username) {
    document.getElementById('reset_user_id').value = userId;
    document.getElementById('reset_username').value = username;
    document.getElementById('new_password').value = '';
    
    new bootstrap.Modal(document.getElementById('resetPasswordModal')).show();
}

function deleteUser(userId, username) {
    document.getElementById('delete_user_id').value = userId;
    document.getElementById('delete_username').textContent = username;
    
    new bootstrap.Modal(document.getElementById('deleteUserModal')).show();
}
</script>

<?php include '../includes/footer.php'; ?>
