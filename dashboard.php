<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

$auth = new Auth();

if(!$auth->isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$role = $auth->getRole();
$db = new Database();

// Get user stats based on role
function getStats($db, $role) {
    $stats = [];
    
    try {
        switch($role) {
            case 'admin':
                $db->query("SELECT COUNT(*) as count FROM students");
                $result = $db->single();
                $stats['students'] = $result ? $result['count'] : 0;
                
                $db->query("SELECT COUNT(*) as count FROM teachers");
                $result = $db->single();
                $stats['teachers'] = $result ? $result['count'] : 0;
                
                $db->query("SELECT COUNT(*) as count FROM classes");
                $result = $db->single();
                $stats['classes'] = $result ? $result['count'] : 0;
                
                $db->query("SELECT COUNT(*) as count FROM subjects");
                $result = $db->single();
                $stats['subjects'] = $result ? $result['count'] : 0;
                break;
                
            case 'teacher':
                $db->query("SELECT COUNT(*) as count FROM students");
                $result = $db->single();
                $stats['total_students'] = $result ? $result['count'] : 0;
                
                $db->query("SELECT COUNT(*) as count FROM classes");
                $result = $db->single();
                $stats['classes'] = $result ? $result['count'] : 0;
                
                $db->query("SELECT COUNT(*) as count FROM subjects");
                $result = $db->single();
                $stats['subjects'] = $result ? $result['count'] : 0;
                break;
                
            case 'student':
                $db->query("SELECT COUNT(*) as count FROM subjects");
                $result = $db->single();
                $stats['subjects'] = $result ? $result['count'] : 0;
                
                $db->query("SELECT COUNT(*) as count FROM study_materials");
                $result = $db->single();
                $stats['materials'] = $result ? $result['count'] : 0;
                break;
                
            case 'parent':
                $db->query("SELECT COUNT(*) as count FROM announcements WHERE target_audience IN ('all', 'parents')");
                $result = $db->single();
                $stats['announcements'] = $result ? $result['count'] : 0;
                break;
        }
    } catch(Exception $e) {
        // Return zeros if database error
        $stats = array_fill_keys(['students', 'teachers', 'classes', 'subjects', 'total_students', 'materials', 'announcements'], 0);
    }
    
    return $stats;
}

$stats = getStats($db, $role);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - School Management System</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>SMS</h2>
                <p>School Management</p>
            </div>
            
            <div class="sidebar-menu">
                <?php if($role == 'admin'): ?>
                    <a href="#" class="menu-item">Dashboard</a>
                    <a href="admin/students.php" class="menu-item">Manage Students</a>
                    <a href="admin/teachers.php" class="menu-item">Manage Teachers</a>
                    <a href="admin/classes.php" class="menu-item">Manage Classes</a>
                    <a href="admin/subjects.php" class="menu-item">Manage Subjects</a>
                    <a href="admin/fees.php" class="menu-item">Fee Management</a>
                    <a href="admin/reports.php" class="menu-item">Reports</a>
                    <a href="admin/settings.php" class="menu-item">Settings</a>
                <?php elseif($role == 'teacher'): ?>
                    <a href="dashboard.php" class="menu-item">Dashboard</a>
                    <a href="teacher/classes.php" class="menu-item">My Classes</a>
                    <a href="teacher/attendance.php" class="menu-item">Attendance</a>
                    <a href="teacher/exams.php" class="menu-item">Exams</a>
                    <a href="teacher/materials.php" class="menu-item">Study Materials</a>
                    <a href="teacher/messages.php" class="menu-item">Messages</a>
                <?php elseif($role == 'student'): ?>
                    <a href="dashboard.php" class="menu-item">Dashboard</a>
                    <a href="student/subjects.php" class="menu-item">My Subjects</a>
                    <a href="student/exams.php" class="menu-item">Exams & Assignments</a>
                    <a href="student/materials.php" class="menu-item">Study Materials</a>
                    <a href="student/results.php" class="menu-item">Exam Results</a>
                    <a href="student/attendance.php" class="menu-item">Attendance</a>
                    <a href="student/messages.php" class="menu-item">Messages</a>
                <?php elseif($role == 'parent'): ?>
                    <a href="#" class="menu-item">Dashboard</a>
                    <a href="#" class="menu-item">Child Progress</a>
                    <a href="#" class="menu-item">Attendance Report</a>
                    <a href="#" class="menu-item">Fee Status</a>
                    <a href="#" class="menu-item">Messages</a>
                    <a href="#" class="menu-item">Announcements</a>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="header">
                <h1><?php echo ucfirst($role); ?> Dashboard</h1>
                
                <div class="user-profile">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                    </div>
                    <div class="user-info">
                        <div class="user-name"><?php echo $_SESSION['username']; ?></div>
                        <div class="user-role"><?php echo $role; ?></div>
                    </div>
                    <a href="logout.php" style="margin-left: 15px; color: #00BFA6; text-decoration: none;">Logout</a>
                </div>
            </div>
            
            <!-- Container -->
            <div class="container">
                <!-- Stats Section -->
                <div class="stats-grid">
                    <?php if($role == 'admin'): ?>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $stats['students']; ?></div>
                            <div class="stat-label">Total Students</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $stats['teachers']; ?></div>
                            <div class="stat-label">Total Teachers</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $stats['classes']; ?></div>
                            <div class="stat-label">Total Classes</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $stats['subjects']; ?></div>
                            <div class="stat-label">Total Subjects</div>
                        </div>
                    <?php elseif($role == 'teacher'): ?>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $stats['total_students']; ?></div>
                            <div class="stat-label">Total Students</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $stats['classes']; ?></div>
                            <div class="stat-label">Classes</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $stats['subjects']; ?></div>
                            <div class="stat-label">Subjects</div>
                        </div>
                    <?php elseif($role == 'student'): ?>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $stats['subjects']; ?></div>
                            <div class="stat-label">Subjects</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $stats['materials']; ?></div>
                            <div class="stat-label">Study Materials</div>
                        </div>
                    <?php elseif($role == 'parent'): ?>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $stats['announcements']; ?></div>
                            <div class="stat-label">Announcements</div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Recent Activity Section -->
                <div class="recent-activity">
                    <h3>Recent Activity</h3>
                    <div class="activity-list">
                        <?php if($role == 'admin'): ?>
                            <?php
                            // Get recent students
                            $db->query("SELECT u.username, s.roll_number, s.admission_date FROM students s JOIN users u ON s.user_id = u.id ORDER BY s.admission_date DESC LIMIT 5");
                            $recentStudents = $db->resultset();
                            
                            foreach($recentStudents as $student) {
                                echo '<div class="activity-item">'
                                    . '<span class="activity-icon">👤</span>'
                                    . '<span class="activity-text">New student: ' . $student['username'] . ' (' . $student['roll_number'] . ')</span>'
                                    . '<span class="activity-date">' . date('M d', strtotime($student['admission_date'])) . '</span>'
                                    . '</div>';
                            }
                            ?>
                        <?php elseif($role == 'teacher'): ?>
                            <div class="activity-item">
                                <span class="activity-icon">📚</span>
                                <span class="activity-text">Classes assigned for current term</span>
                                <span class="activity-date">Today</span>
                            </div>
                            <div class="activity-item">
                                <span class="activity-icon">✅</span>
                                <span class="activity-text">Attendance marked for Class 10-A</span>
                                <span class="activity-date">Yesterday</span>
                            </div>
                        <?php elseif($role == 'student'): ?>
                            <div class="activity-item">
                                <span class="activity-icon">📖</span>
                                <span class="activity-text">New study material uploaded for Mathematics</span>
                                <span class="activity-date">2 days ago</span>
                            </div>
                            <div class="activity-item">
                                <span class="activity-icon">📝</span>
                                <span class="activity-text">Exam results published for Science</span>
                                <span class="activity-date">1 week ago</span>
                            </div>
                        <?php elseif($role == 'parent'): ?>
                            <div class="activity-item">
                                <span class="activity-icon">📢</span>
                                <span class="activity-text">New announcement: Parent-Teacher Meeting</span>
                                <span class="activity-date">3 days ago</span>
                            </div>
                            <div class="activity-item">
                                <span class="activity-icon">💰</span>
                                <span class="activity-text">Fee payment reminder sent</span>
                                <span class="activity-date">1 week ago</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="footer">
                <p>&copy; 2024 School Management System. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>