<?php
require_once '../config/config.php';
requireLogin();

$page_title = 'Work & Time Tracking System';

// Get dashboard statistics
try {
    $user_id = $_SESSION['user_id'];
    $user_role = $_SESSION['user_role'];
    
    // Get task statistics
    if ($user_role === 'dev') {
        // Developer sees only their tasks
        $task_stats = $db->prepare("
            SELECT 
                COUNT(*) as total_tasks,
                SUM(CASE WHEN status = 'todo' THEN 1 ELSE 0 END) as todo_tasks,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_tasks,
                SUM(CASE WHEN status = 'done' THEN 1 ELSE 0 END) as done_tasks,
                SUM(actual_hours) as total_hours
            FROM tasks 
            WHERE assigned_to = ?
        ");
        $task_stats->execute([$user_id]);
    } else {
        // Manager and admin see all tasks
        $task_stats = $db->prepare("
            SELECT 
                COUNT(*) as total_tasks,
                SUM(CASE WHEN status = 'todo' THEN 1 ELSE 0 END) as todo_tasks,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_tasks,
                SUM(CASE WHEN status = 'done' THEN 1 ELSE 0 END) as done_tasks,
                SUM(actual_hours) as total_hours
            FROM tasks
        ");
        $task_stats->execute();
    }
    $stats = $task_stats->fetch();
    
    // Get recent tasks
    if ($user_role === 'dev') {
        $recent_tasks = $db->prepare("
            SELECT t.*, p.name as project_name, u.first_name, u.last_name
            FROM tasks t
            LEFT JOIN projects p ON t.project_id = p.id
            LEFT JOIN users u ON t.created_by = u.id
            WHERE t.assigned_to = ?
            ORDER BY t.created_at DESC
            LIMIT 5
        ");
        $recent_tasks->execute([$user_id]);
    } else {
        $recent_tasks = $db->prepare("
            SELECT t.*, p.name as project_name, 
                   u1.first_name as assigned_first_name, u1.last_name as assigned_last_name,
                   u2.first_name as created_first_name, u2.last_name as created_last_name
            FROM tasks t
            LEFT JOIN projects p ON t.project_id = p.id
            LEFT JOIN users u1 ON t.assigned_to = u1.id
            LEFT JOIN users u2 ON t.created_by = u2.id
            ORDER BY t.created_at DESC
            LIMIT 5
        ");
        $recent_tasks->execute();
    }
    $recent_tasks_data = $recent_tasks->fetchAll();
    
    // Get project statistics (for managers and admins)
    $project_stats = null;
    if ($user_role !== 'dev') {
        $project_stats = $db->prepare("
            SELECT 
                COUNT(*) as total_projects,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_projects,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_projects
            FROM projects
        ");
        $project_stats->execute();
        $project_stats = $project_stats->fetch();
    }
    
    // Get time tracking data for charts
    $time_data = $db->prepare("
        SELECT 
            DATE(created_at) as date,
            SUM(duration_minutes) as total_minutes
        FROM time_logs 
        WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date
    ");
    $time_data->execute([$user_id]);
    $time_chart_data = $time_data->fetchAll();
    
} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $stats = ['total_tasks' => 0, 'todo_tasks' => 0, 'in_progress_tasks' => 0, 'done_tasks' => 0, 'total_hours' => 0];
    $recent_tasks_data = [];
    $project_stats = null;
    $time_chart_data = [];
}

include '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h1 class="h3 mb-0">
            <i class="fas fa-tachometer-alt me-2"></i>แดชบอร์ด
        </h1>
        <p class="text-muted">ภาพรวมของระบบติดตามงานและเวลา</p>
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
                    <div class="bg-warning bg-gradient rounded-circle p-3">
                        <i class="fas fa-clock text-white fa-2x"></i>
                    </div>
                </div>
                <h3 class="card-title text-warning"><?php echo number_format($stats['todo_tasks']); ?></h3>
                <p class="card-text text-muted">งานที่รอดำเนินการ</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-center mb-3">
                    <div class="bg-info bg-gradient rounded-circle p-3">
                        <i class="fas fa-play text-white fa-2x"></i>
                    </div>
                </div>
                <h3 class="card-title text-info"><?php echo number_format($stats['in_progress_tasks']); ?></h3>
                <p class="card-text text-muted">งานที่กำลังดำเนินการ</p>
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
</div>

<!-- Additional Stats for Managers/Admins -->
<?php if ($project_stats): ?>
<div class="row mb-4">
    <div class="col-md-4 mb-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-center mb-3">
                    <div class="bg-secondary bg-gradient rounded-circle p-3">
                        <i class="fas fa-project-diagram text-white fa-2x"></i>
                    </div>
                </div>
                <h3 class="card-title text-secondary"><?php echo number_format($project_stats['total_projects']); ?></h3>
                <p class="card-text text-muted">โปรเจคทั้งหมด</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-center mb-3">
                    <div class="bg-success bg-gradient rounded-circle p-3">
                        <i class="fas fa-play-circle text-white fa-2x"></i>
                    </div>
                </div>
                <h3 class="card-title text-success"><?php echo number_format($project_stats['active_projects']); ?></h3>
                <p class="card-text text-muted">โปรเจคที่กำลังดำเนินการ</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-center mb-3">
                    <div class="bg-primary bg-gradient rounded-circle p-3">
                        <i class="fas fa-check-circle text-white fa-2x"></i>
                    </div>
                </div>
                <h3 class="card-title text-primary"><?php echo number_format($project_stats['completed_projects']); ?></h3>
                <p class="card-text text-muted">โปรเจคที่เสร็จสิ้น</p>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row">
    <!-- Recent Tasks -->
    <div class="col-lg-8 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-list me-2"></i>งานล่าสุด
                </h5>
                <a href="<?php echo BASE_URL; ?>tasks/" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-eye me-1"></i>ดูทั้งหมด
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($recent_tasks_data)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <p class="text-muted">ยังไม่มีงาน</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>งาน</th>
                                    <th>โปรเจค</th>
                                    <?php if ($user_role !== 'dev'): ?>
                                    <th>ผู้รับผิดชอบ</th>
                                    <?php endif; ?>
                                    <th>สถานะ</th>
                                    <th>ความสำคัญ</th>
                                    <th>วันที่สร้าง</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_tasks_data as $task): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold"><?php echo htmlspecialchars($task['title']); ?></div>
                                        <?php if ($task['description']): ?>
                                        <small class="text-muted"><?php echo htmlspecialchars(substr($task['description'], 0, 50)) . '...'; ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?php echo htmlspecialchars($task['project_name']); ?></span>
                                    </td>
                                    <?php if ($user_role !== 'dev'): ?>
                                    <td><?php echo htmlspecialchars($task['assigned_first_name'] . ' ' . $task['assigned_last_name']); ?></td>
                                    <?php endif; ?>
                                    <td>
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
                                    </td>
                                    <td>
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
                                        <span class="badge <?php echo $priority_classes[$task['priority']]; ?>">
                                            <?php echo $priority_texts[$task['priority']]; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($task['created_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Time Tracking Chart -->
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-line me-2"></i>การติดตามเวลา (7 วันล่าสุด)
                </h5>
            </div>
            <div class="card-body">
                <canvas id="timeChart" width="400" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
// Time tracking chart
const timeCtx = document.getElementById('timeChart').getContext('2d');
const timeData = <?php echo json_encode($time_chart_data); ?>;

// Prepare chart data
const labels = [];
const data = [];

// Fill in the last 7 days
for (let i = 6; i >= 0; i--) {
    const date = new Date();
    date.setDate(date.getDate() - i);
    const dateStr = date.toISOString().split('T')[0];
    labels.push(date.toLocaleDateString('th-TH', { month: 'short', day: 'numeric' }));
    
    // Find data for this date
    const dayData = timeData.find(d => d.date === dateStr);
    data.push(dayData ? Math.round(dayData.total_minutes / 60 * 10) / 10 : 0);
}

new Chart(timeCtx, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'ชั่วโมงทำงาน',
            data: data,
            borderColor: '#667eea',
            backgroundColor: 'rgba(102, 126, 234, 0.1)',
            tension: 0.4,
            fill: true
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
