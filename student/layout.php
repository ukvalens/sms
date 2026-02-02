<?php
require_once __DIR__ . '/../includes/auth.php';

$auth = new Auth();
if(!$auth->isLoggedIn() || $auth->getRole() !== 'student') {
    header('Location: ../index.php');
    exit;
}

function renderStudentLayout($title, $content) {
    ob_start();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title><?php echo $title; ?> - Student Panel</title>
        <link rel="stylesheet" href="<?php echo (strpos($_SERVER['PHP_SELF'], '/student/') !== false) ? '../assets/css/style.css' : 'assets/css/style.css'; ?>?v=<?php echo time(); ?>">
    </head>
    <body>
        <div class="dashboard-layout">
            <div class="sidebar">
                <div class="sidebar-header">
                    <h2>Student Panel</h2>
                    <p>Academic Portal</p>
                </div>
                
                <div class="sidebar-menu">
                    <a href="/sms/dashboard.php" class="menu-item">Dashboard</a>
                    <a href="/sms/student/subjects.php" class="menu-item">My Subjects</a>
                    <a href="/sms/student/exams.php" class="menu-item">Exams & Assignments</a>
                    <a href="/sms/student/materials.php" class="menu-item">Study Materials</a>
                    <a href="/sms/student/results.php" class="menu-item">Exam Results</a>
                    <a href="/sms/student/attendance.php" class="menu-item">Attendance</a>
                    <a href="/sms/student/messages.php" class="menu-item">Messages</a>
                </div>
            </div>
            
            <div class="main-content">
                <div class="header">
                    <h1><?php echo $title; ?></h1>
                    <div class="user-profile">
                        <div class="user-avatar">
                            <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                        </div>
                        <div class="user-info">
                            <div class="user-name"><?php echo $_SESSION['username']; ?></div>
                            <div class="user-role">student</div>
                        </div>
                        <a href="/sms/profile.php" style="margin-left: 15px; color: #00BFA6; text-decoration: none;">Profile</a>
                        <a href="/sms/logout.php" style="margin-left: 15px; color: #00BFA6; text-decoration: none;">Logout</a>
                    </div>
                </div>
                
                <div class="container">
                    <?php echo $content; ?>
                </div>
                
                <div class="footer">
                    <p>&copy; 2024 School Management System. All rights reserved.</p>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    return ob_get_clean();
}
?>