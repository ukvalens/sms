<?php
require_once __DIR__ . '/../includes/auth.php';

$auth = new Auth();
if(!$auth->isLoggedIn() || $auth->getRole() !== 'admin') {
    header('Location: ../index.php');
    exit;
}

function renderAdminLayout($title, $content) {
    ob_start();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title><?php echo $title; ?> - Admin Panel</title>
        <link rel="stylesheet" href="<?php echo (strpos($_SERVER['PHP_SELF'], '/admin/') !== false) ? '../assets/css/style.css' : 'assets/css/style.css'; ?>?v=<?php echo time(); ?>">
    </head>
    <body>
        <div class="dashboard-layout">
            <div class="sidebar">
                <div class="sidebar-header">
                    <h2>SMS Admin</h2>
                    <p>Management Panel</p>
                </div>
                
                <div class="sidebar-menu">
                    <a href="/sms/dashboard.php" class="menu-item">Dashboard</a>
                    <a href="/sms/admin/students.php" class="menu-item">Manage Students</a>
                    <div class="menu-item dropdown">
                        <a href="/sms/admin/teachers.php" onclick="handleTeacherClick(event)">Manage Teachers <span class="dropdown-arrow" onclick="toggleDropdown(event)">▶</span></a>
                        <div class="dropdown-content">
                            <a href="/sms/admin/teachers.php">All Teachers</a>
                            <a href="/sms/admin/teacher_assignments.php">Teacher Assignments</a>
                        </div>
                    </div>
                    <a href="/sms/admin/classes.php" class="menu-item">Manage Classes</a>
                    <a href="/sms/admin/subjects.php" class="menu-item">Manage Subjects</a>
                    <a href="/sms/admin/fees.php" class="menu-item">Fee Management</a>
                    <a href="/sms/admin/reports.php" class="menu-item">Reports</a>
                    <a href="/sms/admin/settings.php" class="menu-item">Settings</a>
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
                            <div class="user-role">admin</div>
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
        <script>
        function handleTeacherClick(event) {
            // Allow normal navigation to teachers.php
            return true;
        }
        
        function toggleDropdown(event) {
            event.preventDefault();
            event.stopPropagation();
            const dropdown = event.target.closest('.menu-item.dropdown');
            
            // Close other dropdowns
            document.querySelectorAll('.menu-item.dropdown.active').forEach(item => {
                if (item !== dropdown) {
                    item.classList.remove('active');
                }
            });
            
            // Toggle current dropdown
            dropdown.classList.toggle('active');
        }
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            if (!event.target.closest('.menu-item.dropdown')) {
                document.querySelectorAll('.menu-item.dropdown.active').forEach(item => {
                    item.classList.remove('active');
                });
            }
        });
        </script>
    </body>
    </html>
    <?php
    return ob_get_clean();
}
?>