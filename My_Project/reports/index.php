<?php
require_once '../config/config.php';
requireLogin();

// Check permission
if (!hasPermission('manager')) {
    $_SESSION['error_message'] = 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้';
    header('Location: ' . BASE_URL . 'dashboard/');
    exit();
}

$page_title = 'รายงาน - Task Tracking System';

// Get filter parameters
$date_from = $_GET['date_from'] ?? date('Y-m-01'); // First day of current month
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$project_id = $_GET['project'] ?? '';
$user_id = $_GET['user'] ?? '';

// Get projects for filter
try {
    $stmt = $db->prepare("SELECT id, name FROM projects ORDER BY name");
    $stmt->execute();
    $projects = $stmt->fetchAll();
} catch (Exception $e) {
    $projects = [];
}

// Get users for filter
try {
    $stmt = $db->prepare("SELECT id, first_name, last_name FROM users WHERE is_active = 1 ORDER BY first_name, last_name");
    $stmt->execute();
    $users = $stmt->fetchAll();
} catch (Exception $e) {
    $users = [];
}

// Get report data
try {
    // Task statistics
    $where_conditions = ["t.created_at BETWEEN ? AND ?"];
    $params = [$date_from . ' 00:00:00', $date_to . ' 23:59:59'];
    
    if ($project_id) {
        $where_conditions[] = "t.project_id = ?";
        $params[] = $project_id;
    }
    
    if ($user_id) {
        $where_conditions[] = "t.assigned_to = ?";
        $params[] = $user_id;
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    // Overall statistics
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_tasks,
            SUM(CASE WHEN status = 'todo' THEN 1 ELSE 0 END) as todo_tasks,
            SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_tasks,
            SUM(CASE WHEN status = 'done' THEN 1 ELSE 0 END) as done_tasks,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_tasks,
            SUM(estimated_hours) as total_estimated_hours,
            SUM(actual_hours) as total_actual_hours
        FROM tasks t
        WHERE $where_clause
    ");
    $stmt->execute($params);
    $stats = $stmt->fetch();
    
    // Tasks by status chart data
    $status_data = [
        'labels' => ['รอดำเนินการ', 'กำลังดำเนินการ', 'เสร็จสิ้น', 'ยกเลิก'],
        'data' => [
            $stats['todo_tasks'],
            $stats['in_progress_tasks'],
            $stats['done_tasks'],
            $stats['cancelled_tasks']
        ],
        'colors' => ['#ffc107', '#17a2b8', '#28a745', '#dc3545']
    ];
    
    // Tasks by priority chart data
    $priority_stmt = $db->prepare("
        SELECT 
            priority,
            COUNT(*) as count
        FROM tasks t
        WHERE $where_clause
        GROUP BY priority
        ORDER BY priority
    ");
    $priority_stmt->execute($params);
    $priority_data = $priority_stmt->fetchAll();
    
    $priority_chart = [
        'labels' => [],
        'data' => [],
        'colors' => []
    ];
    
    $priority_map = [
        'low' => ['label' => 'ต่ำ', 'color' => '#6c757d'],
        'medium' => ['label' => 'ปานกลาง', 'color' => '#ffc107'],
        'high' => ['label' => 'สูง', 'color' => '#dc3545']
    ];
    
    foreach ($priority_data as $row) {
        $priority_chart['labels'][] = $priority_map[$row['priority']]['label'];
        $priority_chart['data'][] = $row['count'];
        $priority_chart['colors'][] = $priority_map[$row['priority']]['color'];
    }
    
    // Time tracking data
    $time_stmt = $db->prepare("
        SELECT 
            DATE(tl.created_at) as date,
            SUM(tl.duration_minutes) as total_minutes,
            COUNT(DISTINCT tl.task_id) as task_count
        FROM time_logs tl
        JOIN tasks t ON tl.task_id = t.id
        WHERE tl.created_at BETWEEN ? AND ?
        " . ($project_id ? "AND t.project_id = ?" : "") . "
        " . ($user_id ? "AND tl.user_id = ?" : "") . "
        GROUP BY DATE(tl.created_at)
        ORDER BY date
    ");
    
    $time_params = [$date_from . ' 00:00:00', $date_to . ' 23:59:59'];
    if ($project_id) $time_params[] = $project_id;
    if ($user_id) $time_params[] = $user_id;
    
    $time_stmt->execute($time_params);
    $time_data = $time_stmt->fetchAll();
    
    // Top performers
    $performer_stmt = $db->prepare("
        SELECT 
            u.first_name,
            u.last_name,
            COUNT(t.id) as task_count,
            SUM(t.actual_hours) as total_hours,
            SUM(CASE WHEN t.status = 'done' THEN 1 ELSE 0 END) as completed_tasks
        FROM users u
        LEFT JOIN tasks t ON u.id = t.assigned_to AND t.created_at BETWEEN ? AND ?
        " . ($project_id ? "AND t.project_id = ?" : "") . "
        WHERE u.is_active = 1
        GROUP BY u.id, u.first_name, u.last_name
        HAVING task_count > 0
        ORDER BY completed_tasks DESC, total_hours DESC
        LIMIT 10
    ");
    
    $performer_params = [$date_from . ' 00:00:00', $date_to . ' 23:59:59'];
    if ($project_id) $performer_params[] = $project_id;
    
    $performer_stmt->execute($performer_params);
    $top_performers = $performer_stmt->fetchAll();
    
    // Project performance
    $project_stmt = $db->prepare("
        SELECT 
            p.name as project_name,
            COUNT(t.id) as task_count,
            SUM(CASE WHEN t.status = 'done' THEN 1 ELSE 0 END) as completed_tasks,
            SUM(t.actual_hours) as total_hours,
            ROUND((SUM(CASE WHEN t.status = 'done' THEN 1 ELSE 0 END) / COUNT(t.id)) * 100, 2) as completion_rate
        FROM projects p
        LEFT JOIN tasks t ON p.id = t.project_id AND t.created_at BETWEEN ? AND ?
        " . ($user_id ? "AND t.assigned_to = ?" : "") . "
        WHERE p.status = 'active'
        GROUP BY p.id, p.name
        HAVING task_count > 0
        ORDER BY completion_rate DESC, completed_tasks DESC
    ");
    
    $project_params = [$date_from . ' 00:00:00', $date_to . ' 23:59:59'];
    if ($user_id) $project_params[] = $user_id;
    
    $project_stmt->execute($project_params);
    $project_performance = $project_stmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Reports error: " . $e->getMessage());
    $stats = ['total_tasks' => 0, 'todo_tasks' => 0, 'in_progress_tasks' => 0, 'done_tasks' => 0, 'cancelled_tasks' => 0, 'total_estimated_hours' => 0, 'total_actual_hours' => 0];
    $status_data = ['labels' => [], 'data' => [], 'colors' => []];
    $priority_chart = ['labels' => [], 'data' => [], 'colors' => []];
    $time_data = [];
    $top_performers = [];
    $project_performance = [];
}

include '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h1 class="h3 mb-0">
            <i class="fas fa-chart-bar me-2"></i>รายงาน
        </h1>
        <p class="text-muted">วิเคราะห์ข้อมูลและประสิทธิภาพการทำงาน</p>
    </div>
</div>

<!-- Filters -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-3">
                        <label for="date_from" class="form-label">วันที่เริ่มต้น</label>
                        <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $date_from; ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="date_to" class="form-label">วันที่สิ้นสุด</label>
                        <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $date_to; ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="project" class="form-label">โปรเจค</label>
                        <select class="form-control" id="project" name="project">
                            <option value="">ทั้งหมด</option>
                            <?php foreach ($projects as $project): ?>
                            <option value="<?php echo $project['id']; ?>" <?php echo $project_id == $project['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($project['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="user" class="form-label">ผู้ใช้</label>
                        <select class="form-control" id="user" name="user">
                            <option value="">ทั้งหมด</option>
                            <?php foreach ($users as $user): ?>
                            <option value="<?php echo $user['id']; ?>" <?php echo $user_id == $user['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter me-2"></i>กรองข้อมูล
                        </button>
                        <a href="<?php echo BASE_URL; ?>reports/export.php?<?php echo http_build_query($_GET); ?>" class="btn btn-success">
                            <i class="fas fa-download me-2"></i>ส่งออก CSV
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-center mb-3">
                    <div class="bg-primary bg-gradient rounded-circle p-3">
                        <i class="fas fa-tasks text-white fa-2x"></i>
                    </div>
                </div>
                <h3 class="card-title text-primary"><?php echo number_format($stats['total_tasks']); ?></h3>
                <p class="card-text text-muted">งานทั้งหมด</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-center mb-3">
                    <div class="bg-success bg-gradient rounded-circle p-3">
                        <i class="fas fa-check text-white fa-2x"></i>
                    </div>
                </div>
                <h3 class="card-title text-success"><?php echo number_format($stats['done_tasks']); ?></h3>
                <p class="card-text text-muted">งานที่เสร็จสิ้น</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-center mb-3">
                    <div class="bg-info bg-gradient rounded-circle p-3">
                        <i class="fas fa-clock text-white fa-2x"></i>
                    </div>
                </div>
                <h3 class="card-title text-info"><?php echo number_format($stats['total_estimated_hours'], 1); ?></h3>
                <p class="card-text text-muted">ชั่วโมงที่คาดการณ์</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-center mb-3">
                    <div class="bg-warning bg-gradient rounded-circle p-3">
                        <i class="fas fa-stopwatch text-white fa-2x"></i>
                    </div>
                </div>
                <h3 class="card-title text-warning"><?php echo number_format($stats['total_actual_hours'], 1); ?></h3>
                <p class="card-text text-muted">ชั่วโมงที่ใช้จริง</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Tasks by Status Chart -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-pie me-2"></i>งานตามสถานะ
                </h5>
            </div>
            <div class="card-body">
                <canvas id="statusChart" width="400" height="200"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Tasks by Priority Chart -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-bar me-2"></i>งานตามความสำคัญ
                </h5>
            </div>
            <div class="card-body">
                <canvas id="priorityChart" width="400" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Time Tracking Chart -->
    <div class="col-lg-8 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-line me-2"></i>การติดตามเวลาตามวันที่
                </h5>
            </div>
            <div class="card-body">
                <canvas id="timeChart" width="400" height="200"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Top Performers -->
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-trophy me-2"></i>ผู้ทำงานดีเด่น
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($top_performers)): ?>
                    <p class="text-muted text-center">ไม่มีข้อมูล</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($top_performers as $index => $performer): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div>
                                <div class="fw-bold">
                                    <?php if ($index < 3): ?>
                                        <i class="fas fa-medal text-warning me-1"></i>
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($performer['first_name'] . ' ' . $performer['last_name']); ?>
                                </div>
                                <small class="text-muted">
                                    <?php echo number_format($performer['completed_tasks']); ?> งานเสร็จ | 
                                    <?php echo number_format($performer['total_hours'], 1); ?> ชั่วโมง
                                </small>
                            </div>
                            <span class="badge bg-primary"><?php echo number_format($performer['task_count']); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Project Performance -->
<div class="row">
    <div class="col-12 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-project-diagram me-2"></i>ประสิทธิภาพโปรเจค
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($project_performance)): ?>
                    <p class="text-muted text-center">ไม่มีข้อมูล</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>โปรเจค</th>
                                    <th>งานทั้งหมด</th>
                                    <th>งานเสร็จ</th>
                                    <th>อัตราเสร็จสิ้น</th>
                                    <th>ชั่วโมงทั้งหมด</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($project_performance as $project): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($project['project_name']); ?></strong>
                                    </td>
                                    <td><?php echo number_format($project['task_count']); ?></td>
                                    <td><?php echo number_format($project['completed_tasks']); ?></td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar" role="progressbar" style="width: <?php echo $project['completion_rate']; ?>%">
                                                <?php echo $project['completion_rate']; ?>%
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo number_format($project['total_hours'], 1); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Status Chart
const statusCtx = document.getElementById('statusChart').getContext('2d');
new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode($status_data['labels']); ?>,
        datasets: [{
            data: <?php echo json_encode($status_data['data']); ?>,
            backgroundColor: <?php echo json_encode($status_data['colors']); ?>,
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

// Priority Chart
const priorityCtx = document.getElementById('priorityChart').getContext('2d');
new Chart(priorityCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($priority_chart['labels']); ?>,
        datasets: [{
            data: <?php echo json_encode($priority_chart['data']); ?>,
            backgroundColor: <?php echo json_encode($priority_chart['colors']); ?>,
            borderColor: <?php echo json_encode($priority_chart['colors']); ?>,
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Time Chart
const timeCtx = document.getElementById('timeChart').getContext('2d');
const timeData = <?php echo json_encode($time_data); ?>;

const timeLabels = timeData.map(item => {
    const date = new Date(item.date);
    return date.toLocaleDateString('th-TH', { month: 'short', day: 'numeric' });
});

const timeValues = timeData.map(item => Math.round(item.total_minutes / 60 * 10) / 10);

new Chart(timeCtx, {
    type: 'line',
    data: {
        labels: timeLabels,
        datasets: [{
            label: 'ชั่วโมงทำงาน',
            data: timeValues,
            borderColor: '#667eea',
            backgroundColor: 'rgba(102, 126, 234, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return value + ' ชม.';
                    }
                }
            }
        }
    }
});
</script>

<?php include '../includes/footer.php'; ?>
