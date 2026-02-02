<?php
require_once 'includes/auth.php';

$auth = new Auth();
$message = '';
$error = '';

if($_POST) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role'];
    
    if($auth->register($username, $email, $password, $role)) {
        $message = 'Registration successful! You can now login.';
    } else {
        $error = 'Registration failed. Username or email already exists.';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Register - School Management System</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <h2>School Management System</h2>
            <h3>Register</h3>
            
            <?php if($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>Username:</label>
                    <input type="text" name="username" required>
                </div>
                
                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label>Password:</label>
                    <input type="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label>Role:</label>
                    <select name="role" required>
                        <option value="">Select Role</option>
                        <option value="admin">Admin</option>
                        <option value="teacher">Teacher</option>
                        <option value="student">Student</option>
                        <option value="parent">Parent</option>
                    </select>
                </div>
                
                <button type="submit" class="btn">Register</button>
            </form>
            
            <div class="auth-links">
                <a href="index.php">Already have an account? Login</a>
            </div>
        </div>
    </div>
</body>
</html>