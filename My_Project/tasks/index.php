<?php
require_once '../config/config.php';
requireLogin();

$page_title = 'งานของฉัน - Task Tracking System';

$error_message = '';
$success_message = '';

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$project_filter = $_GET['project'] ?? '';
$priority_filter = $_GET['priority'] ?? '';
$search = $_GET['search'] ?? '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create_task') {
        $title = sanitizeInput($_POST['title'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        $project_id = (int)($_POST['project_id'] ?? 0);
        $priority = sanitizeInput($_POST['priority'] ?? 'medium');
        $estimated_hours = (float)($_POST['estimated_hours'] ?? 0);
        $deadline = $_POST['deadline'] ?? '';
        
        if (empty($title) || $project_id <= 0) {
            $error_message = 'กรุณากรอกข้อมูลให้ครบถ้วน';
        } else {
            try {
                $stmt = $db->prepare("INSERT INTO tasks (title, description, project_id, assigned_to, created_by, priority, estimated_hours, deadline) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$title, $description, $project_id, $_SESSION['user_id'], $_SESSION['user_id'], $priority, $estimated_hours ?: null, $deadline ?: null]);
                
                // Log activity
                logActivity('task_create', 'Created task: ' . $title);
                
                $success_message = 'สร้างงานสำเร็จ';
            } catch (Exception $e) {
                error_log("Task creation error: " . $e->getMessage());
                $error_message = 'เกิดข้อผิดพลาดในการสร้างงาน';
            }
        }
    } elseif ($action === 'update_task') {
        $task_id = (int)($_POST['task_id'] ?? 0);
        $title = sanitizeInput($_POST['title'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        $status = sanitizeInput($_POST['status'] ?? 'todo');
        $priority = sanitizeInput($_POST['priority'] ?? 'medium');
        $estimated_hours = (float)($_POST['estimated_hours'] ?? 0);
        $actual_hours = (float)($_POST['actual_hours'] ?? 0);
        $deadline = $_POST['deadline'] ?? '';
        
        if (empty($title) || $task_id <= 0) {
            $error_message = 'ข้อมูลไม่ถูกต้อง';
        } else {
            try {
                $completed_at = null;
                if ($status === 'done' && $actual_hours > 0) {
                    $completed_at = date('Y-m-d H:i:s');
                }
                
                $stmt = $db->prepare("UPDATE tasks SET title = ?, description = ?, status = ?, priority = ?, estimated_hours = ?, actual_hours = ?, deadline = ?, completed_at = ?, updated_at = NOW() WHERE id = ? AND assigned_to = ?");
                $stmt->execute([$title, $description, $status, $priority, $estimated_hours ?: null, $actual_hours ?: null, $deadline ?: null, $completed_at, $task_id, $_SESSION['user_id']]);
                
                // Log activity
                logActivity('task_update', 'Updated task: ' . $title);
                
                $success_message = 'อัปเดตงานสำเร็จ';
            } catch (Exception $e) {
                error_log("Task update error: " . $e->getMessage());
                $error_message = 'เกิดข้อผิดพลาดในการอัปเดตงาน';
            }
        }
    } elseif ($action === 'delete_task') {
        $task_id = (int)($_POST['task_id'] ?? 0);
        
        if ($task_id <= 0) {
            $error_message = 'ข้อมูลไม่ถูกต้อง';
        } else {
            try {
                // Get task title for logging
                $stmt = $db->prepare("SELECT title FROM tasks WHERE id = ? AND assigned_to = ?");
                $stmt->execute([$task_id, $_SESSION['user_id']]);
                $task_title = $stmt->fetchColumn();
                
                if ($task_title) {
                    // Delete task
                    $stmt = $db->prepare("DELETE FROM tasks WHERE id = ? AND assigned_to = ?");
                    $stmt->execute([$task_id, $_SESSION['user_id']]);
                    
                    // Log activity
                    logActivity('task_delete', 'Deleted task: ' . $task_title);
                    
                    $success_message = 'ลบงานสำเร็จ';
                } else {
                    $error_message = 'ไม่พบงานที่ต้องการลบ';
                }
            } catch (Exception $e) {
                error_log("Task deletion error: " . $e->getMessage());
                $error_message = 'เกิดข้อผิดพลาดในการลบงาน';
            }
        }
    } elseif ($action === 'start_timer') {
        $task_id = (int)($_POST['task_id'] ?? 0);
        
        if ($task_id <= 0) {
            $error_message = 'ข้อมูลไม่ถูกต้อง';
        } else {
            try {
                // Check if there's already an active timer for this task
                $stmt = $db->prepare("SELECT id FROM time_logs WHERE task_id = ? AND user_id = ? AND end_time IS NULL");
                $stmt->execute([$task_id, $_SESSION['user_id']]);
                if ($stmt->fetch()) {
                    $error_message = 'มีตัวจับเวลาที่กำลังทำงานอยู่แล้ว';
                } else {
                    // Start timer
                    $stmt = $db->prepare("INSERT INTO time_logs (task_id, user_id, start_time) VALUES (?, ?, NOW())");
                    $stmt->execute([$task_id, $_SESSION['user_id']]);
                    
                    // Update task status to in_progress
                    $stmt = $db->prepare("UPDATE tasks SET status = 'in_progress', updated_at = NOW() WHERE id = ? AND assigned_to = ?");
                    $stmt->execute([$task_id, $_SESSION['user_id']]);
                    
                    // Log activity
                    logActivity('timer_start', 'Started timer for task ID: ' . $task_id);
                    
                    $success_message = 'เริ่มจับเวลาแล้ว';
                }
            } catch (Exception $e) {
                error_log("Timer start error: " . $e->getMessage());
                $error_message = 'เกิดข้อผิดพลาดในการเริ่มจับเวลา';
            }
        }
    } elseif ($action === 'stop_timer') {
        $time_log_id = (int)($_POST['time_log_id'] ?? 0);
        
        if ($time_log_id <= 0) {
            $error_message = 'ข้อมูลไม่ถูกต้อง';
        } else {
            try {
                // Get time log details
                $stmt = $db->prepare("SELECT tl.*, t.id as task_id FROM time_logs tl JOIN tasks t ON tl.task_id = t.id WHERE tl.id = ? AND tl.user_id = ? AND tl.end_time IS NULL");
                $stmt->execute([$time_log_id, $_SESSION['user_id']]);
                $time_log = $stmt->fetch();
                
                if ($time_log) {
                    // Calculate duration
                    $start_time = new DateTime($time_log['start_time']);
                    $end_time = new DateTime();
                    $duration = $end_time->diff($start_time);
                    $duration_minutes = ($duration->days * 24 * 60) + ($duration->h * 60) + $duration->i;
                    
                    // Update time log
                    $stmt = $db->prepare("UPDATE time_logs SET end_time = NOW(), duration_minutes = ? WHERE id = ?");
                    $stmt->execute([$duration_minutes, $time_log_id]);
                    
                    // Update task actual hours
                    $stmt = $db->prepare("UPDATE tasks SET actual_hours = actual_hours + ? WHERE id = ?");
                    $stmt->execute([$duration_minutes / 60, $time_log['task_id']]);
                    
                    // Log activity
                    logActivity('timer_stop', 'Stopped timer for task ID: ' . $time_log['task_id'] . ' (Duration: ' . $duration_minutes . ' minutes)');
                    
                    $success_message = 'หยุดจับเวลาแล้ว (ระยะเวลา: ' . formatDuration($duration_minutes) . ')';
                } else {
                    $error_message = 'ไม่พบตัวจับเวลาที่กำลังทำงาน';
                }
            } catch (Exception $e) {
                error_log("Timer stop error: " . $e->getMessage());
                $error_message = 'เกิดข้อผิดพลาดในการหยุดจับเวลา';
            }
        }
    }
}

// Get user's tasks with filters
try {
    $where_conditions = ["t.assigned_to = ?"];
    $params = [$_SESSION['user_id']];
    
    if ($status_filter) {
        $where_conditions[] = "t.status = ?";
        $params[] = $status_filter;
    }
    
    if ($project_filter) {
        $where_conditions[] = "t.project_id = ?";
        $params[] = $project_filter;
    }
    
    if ($priority_filter) {
        $where_conditions[] = "t.priority = ?";
        $params[] = $priority_filter;
    }
    
    if ($search) {
        $where_conditions[] = "(t.title LIKE ? OR t.description LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    $stmt = $db->prepare("
        SELECT t.*, p.name as project_name,
               tl.id as active_timer_id, tl.start_time as timer_start_time
        FROM tasks t
        LEFT JOIN projects p ON t.project_id = p.id
        LEFT JOIN time_logs tl ON t.id = tl.task_id AND tl.user_id = ? AND tl.end_time IS NULL
        WHERE $where_clause
        ORDER BY t.created_at DESC
    ");
    $params = array_merge([$_SESSION['user_id']], $params);
    $stmt->execute($params);
    $tasks = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Tasks fetch error: " . $e->getMessage());
    $tasks = [];
}

// Get projects for filter and form
try {
    $stmt = $db->prepare("SELECT id, name FROM projects WHERE status = 'active' ORDER BY name");
    $stmt->execute();
    $projects = $stmt->fetchAll();
} catch (Exception $e) {
    $projects = [];
}

// Get active timers
try {
    $stmt = $db->prepare("
        SELECT tl.*, t.title as task_title, p.name as project_name
        FROM time_logs tl
        JOIN tasks t ON tl.task_id = t.id
        LEFT JOIN projects p ON t.project_id = p.id
        WHERE tl.user_id = ? AND tl.end_time IS NULL
        ORDER BY tl.start_time DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $active_timers = $stmt->fetchAll();
} catch (Exception $e) {
    $active_timers = [];
}

function formatDuration($minutes) {
    if ($minutes < 60) {
        return $minutes . ' นาที';
    } else {
        $hours = floor($minutes / 60);
        $remaining_minutes = $minutes % 60;
        return $hours . ' ชั่วโมง ' . $remaining_minutes . ' นาที';
    }
}

include '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-0">
                    <i class="fas fa-tasks me-2"></i>งานของฉัน
                </h1>
                <p class="text-muted">จัดการงานและติดตามเวลา</p>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createTaskModal">
                <i class="fas fa-plus me-2"></i>สร้างงานใหม่
            </button>
        </div>
    </div>
</div>

<!-- Active Timers -->
<?php if (!empty($active_timers)): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-warning">
            <div class="card-header bg-warning text-dark">
                <h5 class="card-title mb-0">
                    <i class="fas fa-clock me-2"></i>ตัวจับเวลาที่กำลังทำงาน
                </h5>
            </div>
            <div class="card-body">
                <?php foreach ($active_timers as $timer): ?>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <strong><?php echo htmlspecialchars($timer['task_title']); ?></strong>
                        <br>
                        <small class="text-muted">
                            โปรเจค: <?php echo htmlspecialchars($timer['project_name']); ?> | 
                            เริ่มเมื่อ: <?php echo date('H:i', strtotime($timer['start_time'])); ?>
                        </small>
                    </div>
                    <div>
                        <span class="badge bg-warning text-dark" id="timer-<?php echo $timer['id']; ?>">
                            <i class="fas fa-play me-1"></i>
                            <span id="duration-<?php echo $timer['id']; ?>">กำลังคำนวณ...</span>
                        </span>
                        <form method="POST" action="" style="display: inline;" class="ms-2">
                            <input type="hidden" name="action" value="stop_timer">
                            <input type="hidden" name="time_log_id" value="<?php echo $timer['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-danger">
                                <i class="fas fa-stop me-1"></i>หยุด
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Filters -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-3">
                        <label for="status" class="form-label">สถานะ</label>
                        <select class="form-control" id="status" name="status">
                            <option value="">ทั้งหมด</option>
                            <option value="todo" <?php echo $status_filter === 'todo' ? 'selected' : ''; ?>>รอดำเนินการ</option>
                            <option value="in_progress" <?php echo $status_filter === 'in_progress' ? 'selected' : ''; ?>>กำลังดำเนินการ</option>
                            <option value="done" <?php echo $status_filter === 'done' ? 'selected' : ''; ?>>เสร็จสิ้น</option>
                            <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>ยกเลิก</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="project" class="form-label">โปรเจค</label>
                        <select class="form-control" id="project" name="project">
                            <option value="">ทั้งหมด</option>
                            <?php foreach ($projects as $project): ?>
                            <option value="<?php echo $project['id']; ?>" <?php echo $project_filter == $project['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($project['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="priority" class="form-label">ความสำคัญ</label>
                        <select class="form-control" id="priority" name="priority">
                            <option value="">ทั้งหมด</option>
                            <option value="low" <?php echo $priority_filter === 'low' ? 'selected' : ''; ?>>ต่ำ</option>
                            <option value="medium" <?php echo $priority_filter === 'medium' ? 'selected' : ''; ?>>ปานกลาง</option>
                            <option value="high" <?php echo $priority_filter === 'high' ? 'selected' : ''; ?>>สูง</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="search" class="form-label">ค้นหา</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="ค้นหางาน...">
                            <button class="btn btn-outline-secondary" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Tasks List -->
<div class="row">
    <?php if (empty($tasks)): ?>
        <div class="col-12">
            <div class="card text-center py-5">
                <div class="card-body">
                    <i class="fas fa-tasks fa-4x text-muted mb-4"></i>
                    <h5 class="card-title">ไม่พบงาน</h5>
                    <p class="card-text text-muted">เริ่มต้นด้วยการสร้างงานแรกของคุณ</p>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createTaskModal">
                        <i class="fas fa-plus me-2"></i>สร้างงานใหม่
                    </button>
                </div>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($tasks as $task): ?>
        <div class="col-lg-6 col-xl-4 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0"><?php echo htmlspecialchars($task['title']); ?></h6>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" onclick="editTask(<?php echo htmlspecialchars(json_encode($task)); ?>)">
                                <i class="fas fa-edit me-2"></i>แก้ไข
                            </a></li>
                            <li><a class="dropdown-item" href="#" onclick="deleteTask(<?php echo $task['id']; ?>, '<?php echo htmlspecialchars($task['title']); ?>')">
                                <i class="fas fa-trash me-2"></i>ลบ
                            </a></li>
                        </ul>
                    </div>
                </div>
                <div class="card-body">
                    <?php if ($task['description']): ?>
                    <p class="card-text text-muted"><?php echo htmlspecialchars(substr($task['description'], 0, 100)) . (strlen($task['description']) > 100 ? '...' : ''); ?></p>
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <span class="badge bg-info"><?php echo htmlspecialchars($task['project_name']); ?></span>
                    </div>
                    
                    <div class="row text-center mb-3">
                        <div class="col-6">
                            <div class="border-end">
                                <h6 class="text-primary mb-0"><?php echo $task['estimated_hours'] ? number_format($task['estimated_hours'], 1) : '-'; ?></h6>
                                <small class="text-muted">ชั่วโมงที่คาดการณ์</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <h6 class="text-success mb-0"><?php echo $task['actual_hours'] ? number_format($task['actual_hours'], 1) : '0'; ?></h6>
                            <small class="text-muted">ชั่วโมงที่ใช้จริง</small>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <?php
                        $status_classes = [
                            'todo' => 'bg-warning',
                            'in_progress' => 'bg-info',
                            'done' => 'bg-success',
                            'cancelled' => 'bg-danger'
                        ];
                        $status_texts = [
                            'todo' => 'รอดำเนินการ',
                            'in_progress' => 'กำลังดำเนินการ',
                            'done' => 'เสร็จสิ้น',
                            'cancelled' => 'ยกเลิก'
                        ];
                        ?>
                        <span class="badge <?php echo $status_classes[$task['status']]; ?>">
                            <?php echo $status_texts[$task['status']]; ?>
                        </span>
                        
                        <?php
                        $priority_classes = [
                            'low' => 'bg-secondary',
                            'medium' => 'bg-warning',
                            'high' => 'bg-danger'
                        ];
                        $priority_texts = [
                            'low' => 'ต่ำ',
                            'medium' => 'ปานกลาง',
                            'high' => 'สูง'
                        ];
                        ?>
                        <span class="badge <?php echo $priority_classes[$task['priority']]; ?> ms-1">
                            <?php echo $priority_texts[$task['priority']]; ?>
                        </span>
                    </div>
                    
                    <?php if ($task['deadline']): ?>
                    <div class="mb-3">
                        <small class="text-muted">
                            <i class="fas fa-calendar me-1"></i>
                            กำหนดส่ง: <?php echo date('d/m/Y', strtotime($task['deadline'])); ?>
                        </small>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer">
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">
                            <i class="fas fa-clock me-1"></i>
                            สร้างเมื่อ: <?php echo date('d/m/Y', strtotime($task['created_at'])); ?>
                        </small>
                        
                        <?php if ($task['status'] !== 'done' && $task['status'] !== 'cancelled'): ?>
                            <?php if ($task['active_timer_id']): ?>
                                <form method="POST" action="" style="display: inline;">
                                    <input type="hidden" name="action" value="stop_timer">
                                    <input type="hidden" name="time_log_id" value="<?php echo $task['active_timer_id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <i class="fas fa-stop me-1"></i>หยุด
                                    </button>
                                </form>
                            <?php else: ?>
                                <form method="POST" action="" style="display: inline;">
                                    <input type="hidden" name="action" value="start_timer">
                                    <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-success">
                                        <i class="fas fa-play me-1"></i>เริ่ม
                                    </button>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Create Task Modal -->
<div class="modal fade" id="createTaskModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">สร้างงานใหม่</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_task">
                    
                    <div class="mb-3">
                        <label for="title" class="form-label">ชื่องาน <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">คำอธิบาย</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="project_id" class="form-label">โปรเจค <span class="text-danger">*</span></label>
                            <select class="form-control" id="project_id" name="project_id" required>
                                <option value="">เลือกโปรเจค</option>
                                <?php foreach ($projects as $project): ?>
                                <option value="<?php echo $project['id']; ?>"><?php echo htmlspecialchars($project['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="priority" class="form-label">ความสำคัญ</label>
                            <select class="form-control" id="priority" name="priority">
                                <option value="low">ต่ำ</option>
                                <option value="medium" selected>ปานกลาง</option>
                                <option value="high">สูง</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="estimated_hours" class="form-label">ชั่วโมงที่คาดการณ์</label>
                            <input type="number" class="form-control" id="estimated_hours" name="estimated_hours" step="0.1" min="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="deadline" class="form-label">กำหนดส่ง</label>
                            <input type="date" class="form-control" id="deadline" name="deadline">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">สร้างงาน</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Task Modal -->
<div class="modal fade" id="editTaskModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">แก้ไขงาน</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_task">
                    <input type="hidden" name="task_id" id="edit_task_id">
                    
                    <div class="mb-3">
                        <label for="edit_title" class="form-label">ชื่องาน <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_title" name="title" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">คำอธิบาย</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_status" class="form-label">สถานะ</label>
                            <select class="form-control" id="edit_status" name="status">
                                <option value="todo">รอดำเนินการ</option>
                                <option value="in_progress">กำลังดำเนินการ</option>
                                <option value="done">เสร็จสิ้น</option>
                                <option value="cancelled">ยกเลิก</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_priority" class="form-label">ความสำคัญ</label>
                            <select class="form-control" id="edit_priority" name="priority">
                                <option value="low">ต่ำ</option>
                                <option value="medium">ปานกลาง</option>
                                <option value="high">สูง</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="edit_estimated_hours" class="form-label">ชั่วโมงที่คาดการณ์</label>
                            <input type="number" class="form-control" id="edit_estimated_hours" name="estimated_hours" step="0.1" min="0">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="edit_actual_hours" class="form-label">ชั่วโมงที่ใช้จริง</label>
                            <input type="number" class="form-control" id="edit_actual_hours" name="actual_hours" step="0.1" min="0">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="edit_deadline" class="form-label">กำหนดส่ง</label>
                            <input type="date" class="form-control" id="edit_deadline" name="deadline">
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

<!-- Delete Task Modal -->
<div class="modal fade" id="deleteTaskModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">ยืนยันการลบงาน</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>คำเตือน:</strong> การลบงานจะไม่สามารถกู้คืนได้
                </div>
                <p>คุณแน่ใจหรือไม่ที่จะลบงาน <strong id="delete_task_name"></strong>?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <form method="POST" action="" style="display: inline;">
                    <input type="hidden" name="action" value="delete_task">
                    <input type="hidden" name="task_id" id="delete_task_id">
                    <button type="submit" class="btn btn-danger">ลบงาน</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function editTask(task) {
    document.getElementById('edit_task_id').value = task.id;
    document.getElementById('edit_title').value = task.title;
    document.getElementById('edit_description').value = task.description || '';
    document.getElementById('edit_status').value = task.status;
    document.getElementById('edit_priority').value = task.priority;
    document.getElementById('edit_estimated_hours').value = task.estimated_hours || '';
    document.getElementById('edit_actual_hours').value = task.actual_hours || '';
    document.getElementById('edit_deadline').value = task.deadline || '';
    
    new bootstrap.Modal(document.getElementById('editTaskModal')).show();
}

function deleteTask(taskId, taskName) {
    document.getElementById('delete_task_id').value = taskId;
    document.getElementById('delete_task_name').textContent = taskName;
    
    new bootstrap.Modal(document.getElementById('deleteTaskModal')).show();
}

// Update timer display
function updateTimers() {
    <?php foreach ($active_timers as $timer): ?>
    const startTime<?php echo $timer['id']; ?> = new Date('<?php echo $timer['start_time']; ?>');
    const now = new Date();
    const diff = now - startTime<?php echo $timer['id']; ?>;
    const minutes = Math.floor(diff / 60000);
    const hours = Math.floor(minutes / 60);
    const remainingMinutes = minutes % 60;
    
    const durationText = hours > 0 ? 
        `${hours} ชั่วโมง ${remainingMinutes} นาที` : 
        `${minutes} นาที`;
    
    document.getElementById('duration-<?php echo $timer['id']; ?>').textContent = durationText;
    <?php endforeach; ?>
}

// Update timers every minute
setInterval(updateTimers, 60000);
updateTimers(); // Initial update
</script>

<?php include '../includes/footer.php'; ?>
