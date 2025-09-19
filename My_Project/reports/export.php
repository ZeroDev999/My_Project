<?php
require_once '../config/config.php';
requireLogin();

// Check permission
if (!hasPermission('manager')) {
    $_SESSION['error_message'] = 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้';
    header('Location: ' . BASE_URL . 'dashboard/index.php');
    exit();
}

// Get filter parameters
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$project_id = $_GET['project'] ?? '';
$user_id = $_GET['user'] ?? '';

try {
    // Build query conditions
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
    
    // Get tasks data
    $stmt = $db->prepare("
        SELECT 
            t.id,
            t.title,
            t.description,
            t.status,
            t.priority,
            t.estimated_hours,
            t.actual_hours,
            t.deadline,
            t.created_at,
            t.completed_at,
            p.name as project_name,
            u1.first_name as assigned_first_name,
            u1.last_name as assigned_last_name,
            u2.first_name as created_first_name,
            u2.last_name as created_last_name
        FROM tasks t
        LEFT JOIN projects p ON t.project_id = p.id
        LEFT JOIN users u1 ON t.assigned_to = u1.id
        LEFT JOIN users u2 ON t.created_by = u2.id
        WHERE $where_clause
        ORDER BY t.created_at DESC
    ");
    $stmt->execute($params);
    $tasks = $stmt->fetchAll();
    
    // Set headers for CSV download
    $filename = 'tasks_report_' . date('Y-m-d_H-i-s') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    // Create file handle
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Write CSV header
    fputcsv($output, [
        'รหัสงาน',
        'ชื่องาน',
        'คำอธิบาย',
        'สถานะ',
        'ความสำคัญ',
        'ชั่วโมงที่คาดการณ์',
        'ชั่วโมงที่ใช้จริง',
        'กำหนดส่ง',
        'วันที่สร้าง',
        'วันที่เสร็จสิ้น',
        'โปรเจค',
        'ผู้รับผิดชอบ',
        'ผู้สร้างงาน'
    ]);
    
    // Write data rows
    foreach ($tasks as $task) {
        $status_texts = [
            'todo' => 'รอดำเนินการ',
            'in_progress' => 'กำลังดำเนินการ',
            'done' => 'เสร็จสิ้น',
            'cancelled' => 'ยกเลิก'
        ];
        
        $priority_texts = [
            'low' => 'ต่ำ',
            'medium' => 'ปานกลาง',
            'high' => 'สูง'
        ];
        
        fputcsv($output, [
            $task['id'],
            $task['title'],
            $task['description'],
            $status_texts[$task['status']] ?? $task['status'],
            $priority_texts[$task['priority']] ?? $task['priority'],
            $task['estimated_hours'] ? number_format($task['estimated_hours'], 2) : '',
            $task['actual_hours'] ? number_format($task['actual_hours'], 2) : '',
            $task['deadline'] ? date('d/m/Y', strtotime($task['deadline'])) : '',
            date('d/m/Y H:i', strtotime($task['created_at'])),
            $task['completed_at'] ? date('d/m/Y H:i', strtotime($task['completed_at'])) : '',
            $task['project_name'],
            $task['assigned_first_name'] . ' ' . $task['assigned_last_name'],
            $task['created_first_name'] . ' ' . $task['created_last_name']
        ]);
    }
    
    fclose($output);
    exit();
    
} catch (Exception $e) {
    error_log("CSV export error: " . $e->getMessage());
    $_SESSION['error_message'] = 'เกิดข้อผิดพลาดในการส่งออกข้อมูล';
    header('Location: ' . BASE_URL . 'reports/index.php');
    exit();
}
?>
