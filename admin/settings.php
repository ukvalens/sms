<?php
require_once 'layout.php';
require_once '../config/database.php';

$db = new Database();
$message = '';
$error = '';

// Handle password reset
if($_POST && isset($_POST['action'])) {
    if($_POST['action'] === 'reset_password') {
        $userId = $_POST['user_id'];
        $newPassword = $_POST['new_password'];
        
        $db->query("UPDATE users SET password = :password WHERE id = :id");
        $db->bind(':password', password_hash($newPassword, PASSWORD_DEFAULT));
        $db->bind(':id', $userId);
        
        if($db->execute()) {
            $message = 'Password reset successfully!';
        } else {
            $error = 'Failed to reset password.';
        }
    } elseif($_POST['action'] === 'backup') {
        $backupDir = '../backups/';
        if(!is_dir($backupDir)) mkdir($backupDir, 0777, true);
        
        $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        $filepath = realpath($backupDir) . '\\' . $filename;
        
        // Use PHP to create backup instead of mysqldump
        try {
            $pdo = new PDO('mysql:host=localhost;dbname=school_management', 'root', '');
            $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
            
            $sql = "-- Database Backup\n-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
            
            foreach($tables as $table) {
                $sql .= "DROP TABLE IF EXISTS `$table`;\n";
                $create = $pdo->query("SHOW CREATE TABLE `$table`")->fetch();
                $sql .= $create[1] . ";\n\n";
                
                $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
                foreach($rows as $row) {
                    $values = array_map(function($v) use ($pdo) { return $pdo->quote($v); }, array_values($row));
                    $sql .= "INSERT INTO `$table` VALUES (" . implode(',', $values) . ");\n";
                }
                $sql .= "\n";
            }
            
            file_put_contents($filepath, $sql);
            header('Location: settings.php?msg=Backup created successfully: ' . $filename);
        } catch(Exception $e) {
            header('Location: settings.php?msg=Backup failed: ' . $e->getMessage());
        }
        exit;
    }
}

if(isset($_GET['msg'])) {
    if(strpos($_GET['msg'], 'successfully') !== false) {
        $message = $_GET['msg'];
    } else {
        $error = $_GET['msg'];
    }
}

// Get all users for password reset
$db->query("SELECT id, username, email, role FROM users ORDER BY role, username");
$users = $db->resultset();

$content = '
<div class="page-header">
    <h2>System Settings</h2>
</div>

' . ($message ? '<div class="alert alert-success">' . $message . '</div>' : '') . '
' . ($error ? '<div class="alert alert-error">' . $error . '</div>' : '') . '

<!-- Password Reset Modal -->
<div id="resetModal" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h3>Reset User Password</h3>
        <form method="POST">
            <input type="hidden" name="action" value="reset_password">
            <input type="hidden" name="user_id" id="resetUserId">
            <div class="form-group">
                <label>User:</label>
                <input type="text" id="resetUserName" readonly class="form-control">
            </div>
            <div class="form-group">
                <label>New Password:</label>
                <input type="password" name="new_password" required class="form-control" placeholder="Enter new password">
            </div>
            <button type="submit" class="btn">Reset Password</button>
        </form>
    </div>
</div>

<div class="settings-grid">
    <div class="settings-card">
        <h3>School Information</h3>
        <form>
            <div class="form-group">
                <label>School Name:</label>
                <input type="text" value="ABC School" class="form-control">
            </div>
            <div class="form-group">
                <label>Address:</label>
                <textarea class="form-control">123 School Street, City</textarea>
            </div>
            <div class="form-group">
                <label>Phone:</label>
                <input type="text" value="+1234567890" class="form-control">
            </div>
            <button type="submit" class="btn">Update</button>
        </form>
    </div>
    
    <div class="settings-card">
        <h3>Academic Settings</h3>
        <form>
            <div class="form-group">
                <label>Current Session:</label>
                <select class="form-control">
                    <option>2024-2025</option>
                    <option>2025-2026</option>
                </select>
            </div>
            <div class="form-group">
                <label>Attendance Marking Time:</label>
                <input type="time" value="09:00" class="form-control">
            </div>
            <div class="form-group">
                <label>Pass Percentage:</label>
                <input type="number" value="40" class="form-control">
            </div>
            <button type="submit" class="btn">Update</button>
        </form>
    </div>
    
    <div class="settings-card">
        <h3>System Preferences</h3>
        <form>
            <div class="form-group">
                <label>
                    <input type="checkbox" checked> Email Notifications
                </label>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" checked> SMS Notifications
                </label>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox"> Auto Backup
                </label>
            </div>
            <button type="submit" class="btn">Update</button>
        </form>
    </div>
    
    <div class="settings-card">
        <h3>User Management</h3>
        <div class="action-buttons">
            <button class="btn" onclick="showUserList()">Reset User Passwords</button>
            <a href="../admin/reports.php" class="btn">Export Data (Reports)</a>
            <form method="POST" style="display:inline;" onsubmit="return confirm(\'Create database backup now?\');">
                <input type="hidden" name="action" value="backup">
                <button type="submit" class="btn btn-danger">Create Backup</button>
            </form>
        </div>
    </div>
</div>

<!-- User List Modal -->
<div id="userListModal" class="modal" style="display:none;">
    <div class="modal-content" style="max-width: 600px;">
        <span class="close" onclick="closeUserList()">&times;</span>
        <h3>Select User to Reset Password</h3>
        <div class="data-table">
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>';

foreach($users as $user) {
    $content .= '
                    <tr>
                        <td>' . $user['username'] . '</td>
                        <td>' . $user['email'] . '</td>
                        <td>' . ucfirst($user['role']) . '</td>
                        <td>
                            <button class="btn-small btn-edit" onclick="resetUserPassword(' . $user['id'] . ', \'' . $user['username'] . '\', \'' . $user['email'] . '\')">
                                Reset Password
                            </button>
                        </td>
                    </tr>';
}

$content .= '
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function showUserList() {
    document.getElementById("userListModal").style.display = "block";
}

function closeUserList() {
    document.getElementById("userListModal").style.display = "none";
}

function resetUserPassword(userId, username, email) {
    document.getElementById("resetUserId").value = userId;
    document.getElementById("resetUserName").value = username + " (" + email + ")";
    document.getElementById("userListModal").style.display = "none";
    document.getElementById("resetModal").style.display = "block";
}

function closeModal() {
    document.getElementById("resetModal").style.display = "none";
}
</script>';

echo renderAdminLayout('Settings', $content);
?>