<?php
/**
 * User Login Page
 * 
 * This page handles user authentication for students, staff, and guests.
 * Uses the new modular structure with centralized functions.
 */

// Include application initialization
require_once '../../includes/init.php';

// Check if user is already logged in
if (isLoggedIn()) {
    redirect(BASE_URL . '/user-dashboard.php');
}

$error = '';
$success = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $userType = $_POST['userType'] ?? '';
    
    // Validate inputs
    if (empty($email) || empty($password) || empty($userType)) {
        $error = 'Please fill in all fields.';
    } else {
        // Attempt login
        $result = userLogin($email, $password, $userType);
        
        if ($result['success']) {
            // Redirect to dashboard or specified URL
            $redirectUrl = $_GET['redirect'] ?? BASE_URL . '/user-dashboard.php';
            redirect($redirectUrl);
        } else {
            $error = $result['message'];
        }
    }
}

// Get flash message if any
$flash = getFlashMessage();
if ($flash) {
    if ($flash['type'] === 'success') {
        $success = $flash['message'];
    } else {
        $error = $flash['message'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= APP_NAME ?></title>
    
    <!-- Include common assets -->
    <?php includeCommonAssets(); ?>
    
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
        }
        
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            padding: 40px;
            width: 100%;
            max-width: 400px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header img {
            width: 60px;
            height: 60px;
            margin-bottom: 15px;
        }
        
        .login-header h1 {
            color: #333;
            font-size: 24px;
            margin: 0;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn-login {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
        }
        
        .alert {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .alert-danger {
            background-color: #fee;
            color: #c53030;
            border: 1px solid #fed7d7;
        }
        
        .alert-success {
            background-color: #f0fff4;
            color: #2f855a;
            border: 1px solid #c6f6d5;
        }
        
        .login-footer {
            text-align: center;
            margin-top: 20px;
        }
        
        .login-footer a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
        
        .login-footer a:hover {
            text-decoration: underline;
        }
        
        .user-type-selector {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .user-type-option {
            flex: 1;
            padding: 10px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .user-type-option:hover {
            border-color: #667eea;
        }
        
        .user-type-option.selected {
            background-color: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .user-type-option input {
            display: none;
        }
        
        @media (max-width: 480px) {
            .login-card {
                padding: 30px 20px;
            }
            
            .user-type-selector {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <img src="<?= asset('images/AULogo.png') ?>" alt="AU Logo">
                <h1><?= APP_NAME ?></h1>
                <p style="color: #666; margin: 5px 0 0 0;">Sign in to your account</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <?php csrfField(); ?>
                
                <div class="form-group">
                    <label for="userType">I am a:</label>
                    <div class="user-type-selector">
                        <label class="user-type-option" onclick="selectUserType('student')">
                            <input type="radio" name="userType" value="student" id="student">
                            Student
                        </label>
                        <label class="user-type-option" onclick="selectUserType('staff')">
                            <input type="radio" name="userType" value="staff" id="staff">
                            Staff
                        </label>
                        <label class="user-type-option" onclick="selectUserType('guest')">
                            <input type="radio" name="userType" value="guest" id="guest">
                            Guest
                        </label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           class="form-control" 
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                           required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           class="form-control" 
                           required>
                </div>
                
                <button type="submit" class="btn-login">Sign In</button>
            </form>
            
            <div class="login-footer">
                <p>
                    <a href="<?= BASE_URL ?>/forgot_password.php">Forgot your password?</a>
                </p>
                <p>
                    Don't have an account? 
                    <a href="<?= BASE_URL ?>/registration-form.html">Register here</a>
                </p>
                <p>
                    <a href="<?= BASE_URL ?>/admin-login.php">Admin Login</a>
                </p>
            </div>
        </div>
    </div>
    
    <script>
        function selectUserType(type) {
            // Remove selected class from all options
            document.querySelectorAll('.user-type-option').forEach(option => {
                option.classList.remove('selected');
            });
            
            // Add selected class to clicked option
            event.target.closest('.user-type-option').classList.add('selected');
            
            // Check the radio button
            document.getElementById(type).checked = true;
        }
        
        // Auto-select user type if provided in URL
        const urlParams = new URLSearchParams(window.location.search);
        const userType = urlParams.get('userType');
        if (userType && ['student', 'staff', 'guest'].includes(userType)) {
            selectUserType(userType);
        }
    </script>
</body>
</html> 