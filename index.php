<?php
require_once 'includes/auth.php';

$auth = new Auth();
$error = '';

if($_POST) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    if($auth->login($email, $password)) {
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Invalid email or password';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login - School Management System</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <h2>School Management System</h2>
            <h3>Login</h3>
            
            <?php if($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label>Password:</label>
                    <input type="password" name="password" required>
                </div>
                
                <button type="submit" class="btn">Login</button>
            </form>
            
            <div class="auth-links">
                <a href="register.php">Don't have an account? Register</a>
            </div>
        </div>
    </div>
</body>
</html>