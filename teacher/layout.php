<?php
require_once __DIR__ . '/../includes/auth.php';

$auth = new Auth();
if(!$auth->isLoggedIn() || $auth->getRole() !== 'teacher') {
    header('Location: ../index.php');
    exit;
}

function renderTeacherLayout($title, $content) {
    require_once __DIR__ . '/../config/database.php';
    $db = new Database();
    $db->query("SELECT photo FROM teachers WHERE user_id = :id");
    $db->bind(':id', $_SESSION['user_id']);
    $user = $db->single();
    $photoSrc = ($user && $user['photo'] && file_exists(__DIR__ . '/../' . $user['photo'])) ? '/sms/' . $user['photo'] : 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40"><rect width="40" height="40" fill="#00BFA6"/><text x="20" y="28" font-family="Arial" font-size="20" font-weight="bold" text-anchor="middle" fill="white">' . strtoupper(substr($_SESSION['username'], 0, 1)) . '</text></svg>');
    ob_start();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title><?php echo $title; ?> - Teacher Panel</title>
        <link rel="stylesheet" href="/sms/assets/css/style.css?v=<?php echo time(); ?>">
        <script src="/sms/assets/js/pagination.js"></script>
    </head>
    <body>
        <div class="dashboard-layout">
            <div class="sidebar">
                <div class="sidebar-header">
                    <h2>Teacher Panel</h2>
                    <p>Academic Management</p>
                </div>
                
                <div class="sidebar-menu">
                    <a href="/sms/dashboard.php" class="menu-item">Dashboard</a>
                    <a href="/sms/teacher/classes.php" class="menu-item">My Classes</a>
                    <a href="/sms/teacher/attendance.php" class="menu-item">Attendance</a>
                    <a href="/sms/teacher/exams.php" class="menu-item">Exams</a>
                    <a href="/sms/teacher/materials.php" class="menu-item">Study Materials</a>
                    <a href="/sms/teacher/messages.php" class="menu-item">Messages</a>
                </div>
            </div>
            
            <div class="main-content">
                <div class="header">
                    <h1><?php echo $title; ?></h1>
                    <div class="user-profile">
                        <div class="user-avatar">
                            <img src="<?php echo $photoSrc; ?>" alt="Profile" style="width:40px;height:40px;border-radius:50%;object-fit:cover;">
                        </div>
                        <div class="user-info">
                            <div class="user-name"><?php echo $_SESSION['username']; ?></div>
                            <div class="user-role">teacher</div>
                        </div>
                        <a href="/sms/profile.php" style="margin-left: 15px; color: #00BFA6; text-decoration: none;">Profile</a>
                        <a href="/sms/logout.php" style="margin-left: 15px; color: #00BFA6; text-decoration: none;">Logout</a>
                    </div>
                </div>
                
                <div class="container">
                    <?php echo $content; ?>
                </div>
                
                <div class="footer">
                    <p>&copy; <span id="year"></span> School Management System. All rights reserved.</p>
                </div>
            </div>
        </div>
        <script>
        document.getElementById('year').textContent = new Date().getFullYear();
        </script>
    </body>
    </html>
    <?php
    return ob_get_clean();
}
?>