<?php
require_once '../config/config.php';
requireLogin();

// Check permission
if (!hasPermission('manager')) {
    $_SESSION['error_message'] = 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้';
    header('Location: ' . BASE_URL . 'dashboard/');
    exit();
}

$page_title = 'จัดการโปรเจค - Task Tracking System';

$error_message = '';
$success_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create_project') {
        $name = sanitizeInput($_POST['name'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        $start_date = $_POST['start_date'] ?? '';
        $end_date = $_POST['end_date'] ?? '';
        
        if (empty($name)) {
            $error_message = 'กรุณากรอกชื่อโปรเจค';
        } else {
            try {
                $stmt = $db->prepare("INSERT INTO projects (name, description, start_date, end_date, created_by) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$name, $description, $start_date ?: null, $end_date ?: null, $_SESSION['user_id']]);
                
                // Log activity
                logActivity('project_create', 'Created project: ' . $name);
                
                $success_message = 'สร้างโปรเจคสำเร็จ';
            } catch (Exception $e) {
                error_log("Project creation error: " . $e->getMessage());
                $error_message = 'เกิดข้อผิดพลาดในการสร้างโปรเจค';
            }
        }
    } elseif ($action === 'update_project') {
        $project_id = (int)($_POST['project_id'] ?? 0);
        $name = sanitizeInput($_POST['name'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        $status = sanitizeInput($_POST['status'] ?? 'active');
        $start_date = $_POST['start_date'] ?? '';
        $end_date = $_POST['end_date'] ?? '';
        
        if (empty($name) || $project_id <= 0) {
            $error_message = 'ข้อมูลไม่ถูกต้อง';
        } else {
            try {
                $stmt = $db->prepare("UPDATE projects SET name = ?, description = ?, status = ?, start_date = ?, end_date = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$name, $description, $status, $start_date ?: null, $end_date ?: null, $project_id]);
                
                // Log activity
                logActivity('project_update', 'Updated project: ' . $name);
                
                $success_message = 'อัปเดตโปรเจคสำเร็จ';
            } catch (Exception $e) {
                error_log("Project update error: " . $e->getMessage());
                $error_message = 'เกิดข้อผิดพลาดในการอัปเดตโปรเจค';
            }
        }
    } elseif ($action === 'delete_project') {
        $project_id = (int)($_POST['project_id'] ?? 0);
        
        if ($project_id <= 0) {
            $error_message = 'ข้อมูลไม่ถูกต้อง';
        } else {
            try {
                // Check if project has tasks
                $stmt = $db->prepare("SELECT COUNT(*) FROM tasks WHERE project_id = ?");
                $stmt->execute([$project_id]);
                $task_count = $stmt->fetchColumn();
                
                if ($task_count > 0) {
                    $error_message = 'ไม่สามารถลบโปรเจคที่มีงานอยู่ได้ กรุณาลบงานทั้งหมดก่อน';
                } else {
                    // Get project name for logging
                    $stmt = $db->prepare("SELECT name FROM projects WHERE id = ?");
                    $stmt->execute([$project_id]);
                    $project_name = $stmt->fetchColumn();
                    
                    // Delete project
                    $stmt = $db->prepare("DELETE FROM projects WHERE id = ?");
                    $stmt->execute([$project_id]);
                    
                    // Log activity
                    logActivity('project_delete', 'Deleted project: ' . $project_name);
                    
                    $success_message = 'ลบโปรเจคสำเร็จ';
                }
            } catch (Exception $e) {
                error_log("Project deletion error: " . $e->getMessage());
                $error_message = 'เกิดข้อผิดพลาดในการลบโปรเจค';
            }
        }
    }
}

// Get projects with statistics
try {
    $stmt = $db->prepare("
        SELECT p.*, 
               u.first_name, u.last_name,
               COUNT(t.id) as task_count,
               SUM(CASE WHEN t.status = 'done' THEN 1 ELSE 0 END) as completed_tasks
        FROM projects p
        LEFT JOIN users u ON p.created_by = u.id
        LEFT JOIN tasks t ON p.id = t.project_id
        GROUP BY p.id
        ORDER BY p.created_at DESC
    ");
    $stmt->execute();
    $projects = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Projects fetch error: " . $e->getMessage());
    $projects = [];
}

include '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-0">
                    <i class="fas fa-project-diagram me-2"></i>จัดการโปรเจค
                </h1>
                <p class="text-muted">สร้างและจัดการโปรเจคต่างๆ</p>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createProjectModal">
                <i class="fas fa-plus me-2"></i>สร้างโปรเจคใหม่
            </button>
        </div>
    </div>
</div>

<!-- Projects Grid -->
<div class="row">
    <?php if (empty($projects)): ?>
        <div class="col-12">
            <div class="card text-center py-5">
                <div class="card-body">
                    <i class="fas fa-project-diagram fa-4x text-muted mb-4"></i>
                    <h5 class="card-title">ยังไม่มีโปรเจค</h5>
                    <p class="card-text text-muted">เริ่มต้นด้วยการสร้างโปรเจคแรกของคุณ</p>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createProjectModal">
                        <i class="fas fa-plus me-2"></i>สร้างโปรเจคใหม่
                    </button>
                </div>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($projects as $project): ?>
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0"><?php echo htmlspecialchars($project['name']); ?></h5>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" onclick="editProject(<?php echo htmlspecialchars(json_encode($project)); ?>)">
                                <i class="fas fa-edit me-2"></i>แก้ไข
                            </a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>projects/view.php?id=<?php echo $project['id']; ?>">
                                <i class="fas fa-eye me-2"></i>ดูรายละเอียด
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="#" onclick="deleteProject(<?php echo $project['id']; ?>, '<?php echo htmlspecialchars($project['name']); ?>')">
                                <i class="fas fa-trash me-2"></i>ลบ
                            </a></li>
                        </ul>
                    </div>
                </div>
                <div class="card-body">
                    <?php if ($project['description']): ?>
                    <p class="card-text text-muted"><?php echo htmlspecialchars(substr($project['description'], 0, 100)) . (strlen($project['description']) > 100 ? '...' : ''); ?></p>
                    <?php endif; ?>
                    
                    <div class="row text-center mb-3">
                        <div class="col-6">
                            <div class="border-end">
                                <h6 class="text-primary mb-0"><?php echo number_format($project['task_count']); ?></h6>
                                <small class="text-muted">งานทั้งหมด</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <h6 class="text-success mb-0"><?php echo number_format($project['completed_tasks']); ?></h6>
                            <small class="text-muted">เสร็จสิ้น</small>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <?php
                        $status_classes = [
                            'active' => 'bg-success',
                            'completed' => 'bg-primary',
                            'on_hold' => 'bg-warning',
                            'cancelled' => 'bg-danger'
                        ];
                        $status_texts = [
                            'active' => 'กำลังดำเนินการ',
                            'completed' => 'เสร็จสิ้น',
                            'on_hold' => 'พักชั่วคราว',
                            'cancelled' => 'ยกเลิก'
                        ];
                        ?>
                        <span class="badge <?php echo $status_classes[$project['status']]; ?>">
                            <?php echo $status_texts[$project['status']]; ?>
                        </span>
                    </div>
                    
                    <div class="mb-2">
                        <small class="text-muted">
                            <i class="fas fa-user me-1"></i>
                            สร้างโดย: <?php echo htmlspecialchars($project['first_name'] . ' ' . $project['last_name']); ?>
                        </small>
                    </div>
                    
                    <?php if ($project['start_date'] || $project['end_date']): ?>
                    <div class="mb-2">
                        <small class="text-muted">
                            <i class="fas fa-calendar me-1"></i>
                            <?php if ($project['start_date']): ?>
                                เริ่ม: <?php echo date('d/m/Y', strtotime($project['start_date'])); ?>
                            <?php endif; ?>
                            <?php if ($project['start_date'] && $project['end_date']): ?> - <?php endif; ?>
                            <?php if ($project['end_date']): ?>
                                สิ้นสุด: <?php echo date('d/m/Y', strtotime($project['end_date'])); ?>
                            <?php endif; ?>
                        </small>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer">
                    <small class="text-muted">
                        <i class="fas fa-clock me-1"></i>
                        สร้างเมื่อ: <?php echo date('d/m/Y H:i', strtotime($project['created_at'])); ?>
                    </small>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Create Project Modal -->
<div class="modal fade" id="createProjectModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">สร้างโปรเจคใหม่</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_project">
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">ชื่อโปรเจค <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">คำอธิบาย</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="start_date" class="form-label">วันที่เริ่มต้น</label>
                            <input type="date" class="form-control" id="start_date" name="start_date">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="end_date" class="form-label">วันที่สิ้นสุด</label>
                            <input type="date" class="form-control" id="end_date" name="end_date">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">สร้างโปรเจค</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Project Modal -->
<div class="modal fade" id="editProjectModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">แก้ไขโปรเจค</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_project">
                    <input type="hidden" name="project_id" id="edit_project_id">
                    
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">ชื่อโปรเจค <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">คำอธิบาย</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_status" class="form-label">สถานะ</label>
                        <select class="form-control" id="edit_status" name="status">
                            <option value="active">กำลังดำเนินการ</option>
                            <option value="completed">เสร็จสิ้น</option>
                            <option value="on_hold">พักชั่วคราว</option>
                            <option value="cancelled">ยกเลิก</option>
                        </select>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_start_date" class="form-label">วันที่เริ่มต้น</label>
                            <input type="date" class="form-control" id="edit_start_date" name="start_date">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_end_date" class="form-label">วันที่สิ้นสุด</label>
                            <input type="date" class="form-control" id="edit_end_date" name="end_date">
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

<!-- Delete Project Modal -->
<div class="modal fade" id="deleteProjectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">ยืนยันการลบโปรเจค</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>คำเตือน:</strong> การลบโปรเจคจะไม่สามารถกู้คืนได้
                </div>
                <p>คุณแน่ใจหรือไม่ที่จะลบโปรเจค <strong id="delete_project_name"></strong>?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <form method="POST" action="" style="display: inline;">
                    <input type="hidden" name="action" value="delete_project">
                    <input type="hidden" name="project_id" id="delete_project_id">
                    <button type="submit" class="btn btn-danger">ลบโปรเจค</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function editProject(project) {
    document.getElementById('edit_project_id').value = project.id;
    document.getElementById('edit_name').value = project.name;
    document.getElementById('edit_description').value = project.description || '';
    document.getElementById('edit_status').value = project.status;
    document.getElementById('edit_start_date').value = project.start_date || '';
    document.getElementById('edit_end_date').value = project.end_date || '';
    
    new bootstrap.Modal(document.getElementById('editProjectModal')).show();
}

function deleteProject(projectId, projectName) {
    document.getElementById('delete_project_id').value = projectId;
    document.getElementById('delete_project_name').textContent = projectName;
    
    new bootstrap.Modal(document.getElementById('deleteProjectModal')).show();
}
</script>

<?php include '../includes/footer.php'; ?>
