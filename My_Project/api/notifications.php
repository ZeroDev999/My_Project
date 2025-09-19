<?php
require_once '../config/config.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Get unread notifications
        $stmt = $db->prepare("
            SELECT id, title, message, type, related_task_id, related_project_id, created_at
            FROM notifications 
            WHERE user_id = ? AND is_read = 0
            ORDER BY created_at DESC
            LIMIT 10
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $notifications = $stmt->fetchAll();
        
        // Add links to notifications
        foreach ($notifications as &$notification) {
            if ($notification['related_task_id']) {
                $notification['link'] = BASE_URL . 'tasks/index.php?id=' . $notification['related_task_id'];
            } elseif ($notification['related_project_id']) {
                $notification['link'] = BASE_URL . 'projects/index.php?id=' . $notification['related_project_id'];
            }
        }
        
        echo json_encode([
            'success' => true,
            'notifications' => $notifications
        ]);
    } catch (Exception $e) {
        error_log("Notifications fetch error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในการโหลดการแจ้งเตือน'
        ]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    if ($action === 'mark_all_read') {
        try {
            $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            
            echo json_encode([
                'success' => true,
                'message' => 'ทำเครื่องหมายว่าอ่านแล้วทั้งหมด'
            ]);
        } catch (Exception $e) {
            error_log("Mark notifications read error: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการอัปเดตการแจ้งเตือน'
            ]);
        }
    } elseif ($action === 'mark_read') {
        $notification_id = (int)($input['notification_id'] ?? 0);
        
        if ($notification_id <= 0) {
            echo json_encode([
                'success' => false,
                'message' => 'ข้อมูลไม่ถูกต้อง'
            ]);
        } else {
            try {
                $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
                $stmt->execute([$notification_id, $_SESSION['user_id']]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'ทำเครื่องหมายว่าอ่านแล้ว'
                ]);
            } catch (Exception $e) {
                error_log("Mark notification read error: " . $e->getMessage());
                echo json_encode([
                    'success' => false,
                    'message' => 'เกิดข้อผิดพลาดในการอัปเดตการแจ้งเตือน'
                ]);
            }
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'การดำเนินการไม่ถูกต้อง'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'วิธีการร้องขอไม่ถูกต้อง'
    ]);
}
?>
