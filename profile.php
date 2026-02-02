<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

$auth = new Auth();
if(!$auth->isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$db = new Database();
$message = '';
$error = '';
$userId = $_SESSION['user_id'];
$userRole = $_SESSION['role'];

// Handle form submission
if($_POST) {
    if(isset($_POST['action'])) {
        switch($_POST['action']) {
            case 'update_profile':
                // Handle image upload
                $photoPath = null;
                if(isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
                    $uploadDir = 'uploads/profiles/';
                    if(!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    
                    $fileExtension = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
                    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                    
                    if(in_array($fileExtension, $allowedExtensions)) {
                        $fileName = $userId . '_' . time() . '.' . $fileExtension;
                        $photoPath = $uploadDir . $fileName;
                        
                        if(move_uploaded_file($_FILES['photo']['tmp_name'], $photoPath)) {
                            // Update photo in role-specific table
                            switch($userRole) {
                                case 'teacher':
                                    $db->query("UPDATE teachers SET photo = :photo WHERE user_id = :user_id");
                                    break;
                                case 'student':
                                    $db->query("UPDATE students SET photo = :photo WHERE user_id = :user_id");
                                    break;
                            }
                            if($userRole !== 'admin') {
                                $db->bind(':photo', $photoPath);
                                $db->bind(':user_id', $userId);
                                $db->execute();
                            }
                        }
                    }
                }
                
                // Update user table
                $db->query("UPDATE users SET username = :username, email = :email WHERE id = :id");
                $db->bind(':username', $_POST['username']);
                $db->bind(':email', $_POST['email']);
                $db->bind(':id', $userId);
                
                if($db->execute()) {
                    // Update role-specific table
                    switch($userRole) {
                        case 'teacher':
                            $db->query("UPDATE teachers SET qualification = :qualification, specialization = :specialization, phone = :phone, address = :address WHERE user_id = :user_id");
                            $db->bind(':qualification', $_POST['qualification']);
                            $db->bind(':specialization', $_POST['specialization']);
                            $db->bind(':phone', $_POST['phone']);
                            $db->bind(':address', $_POST['address']);
                            $db->bind(':user_id', $userId);
                            break;
                            
                        case 'student':
                            $db->query("UPDATE students SET phone = :phone, address = :address WHERE user_id = :user_id");
                            $db->bind(':phone', $_POST['phone']);
                            $db->bind(':address', $_POST['address']);
                            $db->bind(':user_id', $userId);
                            break;
                    }
                    
                    if($db->execute()) {
                        $_SESSION['username'] = $_POST['username'];
                        $message = 'Profile updated successfully!';
                    } else {
                        $error = 'Failed to update profile details.';
                    }
                } else {
                    $error = 'Failed to update profile.';
                }
                break;
                
            case 'change_password':
                if(password_verify($_POST['current_password'], $currentUser['password'])) {
                    if($_POST['new_password'] === $_POST['confirm_password']) {
                        $db->query("UPDATE users SET password = :password WHERE id = :id");
                        $db->bind(':password', password_hash($_POST['new_password'], PASSWORD_DEFAULT));
                        $db->bind(':id', $userId);
                        
                        if($db->execute()) {
                            $message = 'Password changed successfully!';
                        } else {
                            $error = 'Failed to change password.';
                        }
                    } else {
                        $error = 'New passwords do not match.';
                    }
                } else {
                    $error = 'Current password is incorrect.';
                }
                break;
        }
    }
}

// Get user data based on role
switch($userRole) {
    case 'admin':
        $db->query("SELECT * FROM users WHERE id = :id");
        $db->bind(':id', $userId);
        $currentUser = $db->single();
        break;
        
    case 'teacher':
        $db->query("SELECT u.*, t.* FROM users u JOIN teachers t ON u.id = t.user_id WHERE u.id = :id");
        $db->bind(':id', $userId);
        $currentUser = $db->single();
        break;
        
    case 'student':
        $db->query("SELECT u.*, s.*, c.name as class_name, sec.name as section_name FROM users u JOIN students s ON u.id = s.user_id LEFT JOIN classes c ON s.class_id = c.id LEFT JOIN sections sec ON s.section_id = sec.id WHERE u.id = :id");
        $db->bind(':id', $userId);
        $currentUser = $db->single();
        break;
}

// Determine layout based on role
switch($userRole) {
    case 'admin':
        require_once 'admin/layout.php';
        $layoutFunction = 'renderAdminLayout';
        break;
    case 'teacher':
        require_once 'teacher/layout.php';
        $layoutFunction = 'renderTeacherLayout';
        break;
    case 'student':
        require_once 'student/layout.php';
        $layoutFunction = 'renderStudentLayout';
        break;
}

$photoSrc = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 80 80"><rect width="80" height="80" fill="#00BFA6"/><text x="40" y="50" font-family="Arial" font-size="32" font-weight="bold" text-anchor="middle" fill="white">' . strtoupper(substr($currentUser['username'], 0, 1)) . '</text></svg>');
if(isset($currentUser['photo']) && $currentUser['photo'] && file_exists($currentUser['photo'])) {
    $photoSrc = $currentUser['photo'];
}

$content = '
<div class="page-header">
    <h2>My Profile</h2>
</div>

' . ($message ? '<div class="alert alert-success">' . $message . '</div>' : '') . '
' . ($error ? '<div class="alert alert-error">' . $error . '</div>' : '') . '

<div class="profile-container">
    <div class="profile-main">
        <div class="profile-card">
            <div class="profile-header">
                <div class="profile-avatar-container">
                    <img src="' . $photoSrc . '" alt="Profile Photo" class="profile-avatar-img">
                </div>
                <div class="profile-info">
                    <h3>' . $currentUser['username'] . '</h3>
                    <div class="user-role">' . ucfirst($userRole) . '</div>
                    <div class="email">' . $currentUser['email'] . '</div>
                </div>
            </div>
            
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update_profile">
                <div class="form-group">
                    <label>Profile Photo:</label>
                    <input type="file" name="photo" accept="image/*" class="form-control">
                </div>
                <div class="form-group">
                    <label>Full Name:</label>
                    <input type="text" name="username" required class="form-control" value="' . $currentUser['username'] . '">
                </div>
                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" name="email" required class="form-control" value="' . $currentUser['email'] . '">
                </div>';

// Role-specific fields
if($userRole === 'teacher') {
    $content .= '
                <div class="form-group">
                    <label>Employee ID:</label>
                    <input type="text" class="form-control" value="' . ($currentUser['employee_id'] ?? '') . '" readonly>
                </div>
                <div class="form-group">
                    <label>Qualification:</label>
                    <input type="text" name="qualification" class="form-control" value="' . ($currentUser['qualification'] ?? '') . '">
                </div>
                <div class="form-group">
                    <label>Specialization:</label>
                    <input type="text" name="specialization" class="form-control" value="' . ($currentUser['specialization'] ?? '') . '">
                </div>';
}

if($userRole === 'student') {
    $content .= '
                <div class="form-group">
                    <label>Roll Number:</label>
                    <input type="text" class="form-control" value="' . ($currentUser['roll_number'] ?? '') . '" readonly>
                </div>
                <div class="form-group">
                    <label>Class:</label>
                    <input type="text" class="form-control" value="' . ($currentUser['class_name'] ?? '') . ' - ' . ($currentUser['section_name'] ?? '') . '" readonly>
                </div>';
}

if($userRole !== 'admin') {
    $content .= '
                <div class="form-group">
                    <label>Phone:</label>
                    <input type="text" name="phone" class="form-control" value="' . ($currentUser['phone'] ?? '') . '">
                </div>
                <div class="form-group">
                    <label>Address:</label>
                    <textarea name="address" class="form-control">' . ($currentUser['address'] ?? '') . '</textarea>
                </div>';
}

$content .= '
                <button type="submit" class="btn">Update Profile</button>
            </form>
        </div>
    </div>
    
    <div class="profile-sidebar">
        <div class="profile-card">
            <h3>Change Password</h3>
            <form method="POST">
                <input type="hidden" name="action" value="change_password">
                <div class="form-group">
                    <label>Current Password:</label>
                    <input type="password" name="current_password" required class="form-control">
                </div>
                <div class="form-group">
                    <label>New Password:</label>
                    <input type="password" name="new_password" required class="form-control">
                </div>
                <div class="form-group">
                    <label>Confirm New Password:</label>
                    <input type="password" name="confirm_password" required class="form-control">
                </div>
                <button type="submit" class="btn btn-danger">Change Password</button>
            </form>
        </div>
    </div>
</div>';

echo $layoutFunction('My Profile', $content);
?>