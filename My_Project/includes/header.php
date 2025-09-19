<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Task Tracking System'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            background-color: #f8f9fa;
        }
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
        }
        .sidebar {
            min-height: calc(100vh - 56px);
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            border-radius: 10px;
            margin: 5px 10px;
            transition: all 0.3s ease;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background: rgba(255,255,255,0.2);
            transform: translateX(5px);
        }
        .sidebar .nav-link i {
            width: 20px;
            margin-right: 10px;
        }
        .main-content {
            padding: 20px;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s ease;
        }
        .card:hover {
            transform: translateY(-2px);
        }
        .btn-primary {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            border-radius: 10px;
            padding: 10px 20px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .badge {
            border-radius: 20px;
            padding: 8px 12px;
            font-weight: 500;
        }
        .table {
            border-radius: 10px;
            overflow: hidden;
        }
        .table thead th {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border: none;
            font-weight: 600;
        }
        .alert {
            border: none;
            border-radius: 10px;
        }
        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .dropdown-menu {
            border: none;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(45deg, #667eea, #764ba2);">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo BASE_URL; ?>dashboard/index.php">
                <i class="fas fa-tasks me-2"></i>Task Tracking System
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle position-relative" href="#" id="notificationsDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-bell"></i>
                            <span class="notification-badge" id="notificationCount">0</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" id="notificationsList">
                            <li><h6 class="dropdown-header">การแจ้งเตือน</h6></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#" id="noNotifications">ไม่มีการแจ้งเตือน</a></li>
                        </ul>
                    </li>
                    
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i>
                            <?php echo $_SESSION['user_name'] ?? 'User'; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>profile/index.php"><i class="fas fa-user me-2"></i>โปรไฟล์</a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>settings/index.php"><i class="fas fa-cog me-2"></i>ตั้งค่า</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i>ออกจากระบบ</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0">
                <div class="sidebar">
                    <div class="p-3">
                        <h6 class="text-white-50 text-uppercase fw-bold mb-3">เมนูหลัก</h6>
                        <ul class="nav nav-pills flex-column">
                            <li class="nav-item">
                                <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>dashboard/index.php">
                                    <i class="fas fa-tachometer-alt"></i>แดชบอร์ด
                                </a>
                            </li>
                            
                            <?php if (hasPermission('manager')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], '/projects/') !== false) ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>projects/index.php">
                                    <i class="fas fa-project-diagram"></i>จัดการโปรเจค
                                </a>
                            </li>
                            <?php endif; ?>
                            
                            <li class="nav-item">
                                <a class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], '/tasks/') !== false) ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>tasks/index.php">
                                    <i class="fas fa-tasks"></i>งานของฉัน
                                </a>
                            </li>
                            
                            <?php if (hasPermission('manager')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], '/reports/') !== false) ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>reports/index.php">
                                    <i class="fas fa-chart-bar"></i>รายงาน
                                </a>
                            </li>
                            <?php endif; ?>
                            
                            <?php if (hasPermission('admin')): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], '/users/') !== false) ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>users/index.php">
                                    <i class="fas fa-users"></i>จัดการผู้ใช้
                                </a>
                            </li>
                            
                            <li class="nav-item">
                                <a class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], '/settings/') !== false) ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>settings/index.php">
                                    <i class="fas fa-cog"></i>ตั้งค่าระบบ
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="main-content">
                    <?php if (isset($_SESSION['success_message'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['error_message'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
