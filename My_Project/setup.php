<?php
/**
 * Task Tracking System Setup Script
 * Run this script to set up the database and initial configuration
 */

// Check if already installed
if (file_exists('config/installed.flag')) {
    die('System is already installed. Delete config/installed.flag to reinstall.');
}

// Database configuration
$db_host = getenv('DB_HOST') ?: 'localhost:3306';
$db_name = getenv('DB_NAME') ?: 'ncitproj_ikkyuz';
$db_user = getenv('DB_USER') ?: 'ncitproj_ikkyuz';
$db_pass = getenv('DB_PASS') ?: 'it2_ikkyuz';

// Get database configuration from user
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db_host = $_POST['db_host'] ?? 'localhost:3306';
    $db_name = $_POST['db_name'] ?? 'ncitproj_ikkyuz';
    $db_user = $_POST['db_user'] ?? 'ncitproj_ikkyuz';
    $db_pass = $_POST['db_pass'] ?? 'it2_ikkyuz';
    
    try {
        // Test database connection
        $pdo = new PDO("mysql:host=$db_host;charset=utf8mb4", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create database if it doesn't exist
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$db_name`");
        
        // Read and execute schema
        $schema = file_get_contents('database/schema.sql');
        $statements = explode(';', $schema);
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                $pdo->exec($statement);
            }
        }
        
        // Update database configuration
        $config_content = "<?php
// Database configuration
// For production hosting, these values should be provided by your hosting provider
// You can also set these as environment variables for better security
define('DB_HOST', getenv('DB_HOST') ?: '$db_host');
define('DB_NAME', getenv('DB_NAME') ?: '$db_name');
define('DB_USER', getenv('DB_USER') ?: '$db_user');
define('DB_PASS', getenv('DB_PASS') ?: '$db_pass');

class Database {
    private \$connection;
    
    public function __construct() {
        try {
            \$this->connection = new PDO(
                \"mysql:host=\" . DB_HOST . \";dbname=\" . DB_NAME . \";charset=utf8mb4\",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch(PDOException \$e) {
            error_log(\"Database connection failed: \" . \$e->getMessage());
            die(\"Database connection failed. Please check your configuration.\");
        }
    }
    
    public function getConnection() {
        return \$this->connection;
    }
    
    public function prepare(\$sql) {
        return \$this->connection->prepare(\$sql);
    }
    
    public function lastInsertId() {
        return \$this->connection->lastInsertId();
    }
}
?>";
        
        file_put_contents('config/database.php', $config_content);
        
        // Create installed flag
        file_put_contents('config/installed.flag', date('Y-m-d H:i:s'));
        
        // Create uploads directory
        if (!is_dir('uploads')) {
            mkdir('uploads', 0755, true);
        }
        
        echo '<div class="alert alert-success">Installation completed successfully!</div>';
        echo '<p><a href="auth/login.php" class="btn btn-primary">Go to Login</a></p>';
        echo '<p><strong>Default Login:</strong><br>';
        echo 'Username: admin<br>';
        echo 'Email: admin@example.com<br>';
        echo 'Password: password</p>';
        
    } catch (Exception $e) {
        echo '<div class="alert alert-danger">Installation failed: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Tracking System - Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Sarabun', sans-serif;
        }
        .setup-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-8 col-lg-6">
                <div class="setup-card p-5">
                    <div class="text-center mb-4">
                        <i class="fas fa-tasks fa-3x text-primary mb-3"></i>
                        <h3 class="fw-bold">Task Tracking System Setup</h3>
                        <p class="text-muted">Configure your database connection</p>
                    </div>
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="db_host" class="form-label">Database Host</label>
                            <input type="text" class="form-control" id="db_host" name="db_host" 
                                   value="<?php echo htmlspecialchars($db_host); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="db_name" class="form-label">Database Name</label>
                            <input type="text" class="form-control" id="db_name" name="db_name" 
                                   value="<?php echo htmlspecialchars($db_name); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="db_user" class="form-label">Database Username</label>
                            <input type="text" class="form-control" id="db_user" name="db_user" 
                                   value="<?php echo htmlspecialchars($db_user); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="db_pass" class="form-label">Database Password</label>
                            <input type="password" class="form-control" id="db_pass" name="db_pass" 
                                   value="<?php echo htmlspecialchars($db_pass); ?>">
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-cog me-2"></i>Install System
                            </button>
                        </div>
                    </form>
                    
                    <div class="mt-4">
                        <h6>Requirements:</h6>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-check text-success me-2"></i>PHP 7.4 or higher</li>
                            <li><i class="fas fa-check text-success me-2"></i>MySQL 5.7 or higher</li>
                            <li><i class="fas fa-check text-success me-2"></i>PDO extension</li>
                            <li><i class="fas fa-check text-success me-2"></i>Write permissions</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</body>
</html>
