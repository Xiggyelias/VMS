<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/middleware/security.php';
SecurityMiddleware::initialize();

// Generate CSRF token for forms
$csrfToken = SecurityMiddleware::generateCSRFToken();

// Prepare alert messages (rendered later inside the page)
$alert_type = null;
$alert_message = null;

if (isset($_GET['error'])) {
    $error = $_GET['error'];
    if ($error === 'empty_fields') {
        $alert_type = 'danger';
        $alert_message = 'Please fill in all fields.';
    } elseif ($error === 'invalid_password') {
        $alert_type = 'danger';
        $alert_message = 'Invalid password.';
    } elseif ($error === 'not_found') {
        $alert_type = 'warning';
        $alert_message = 'Account not found.';
    }
}

if (isset($_GET['reset']) && $_GET['reset'] === 'success') {
    $alert_type = 'success';
    $alert_message = 'Your password has been reset successfully. Please login with your new password.';
}

// Handle login result
if (isset($login_successful) && $login_successful) {
    // Save logged-in user ID to session
    $_SESSION['user_id'] = $user['applicant_id'];
    header("Location: user-dashboard.php");
    exit();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken) ?>">
    <title>Login - Vehicle Registration System</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #fff 0%, #f3f6ff 35%, #fdeeee 100%);
        }

        .login-page {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 1fr 1fr;
        }

        .login-tabs {
            display: flex;
            margin-bottom: 2rem;
            border-bottom: 2px solid #eee;
        }

        .login-tab {
            padding: 1rem 2rem;
            cursor: pointer;
            color: #666;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
            transition: all 0.3s ease;
        }

        .login-tab.active {
            color: var(--primary-red);
            border-bottom-color: var(--primary-red);
        }

        .login-form-container {
            display: none;
        }

        .login-form-container.active {
            display: block;
        }

        .login-left {
            background-color: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .login-right {
            background-color: var(--primary-red);
            color: var(--white);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            position: relative;
            overflow: hidden;
        }

        .login-right::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(0,0,0,0.2) 0%, rgba(0,0,0,0) 100%);
        }

        .welcome-text {
            position: relative;
            z-index: 1;
            text-align: center;
        }

        .welcome-text h2 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .welcome-text p {
            font-size: 1.1rem;
            opacity: 0.9;
            max-width: 400px;
            line-height: 1.6;
        }

        .login-form {
            width: 100%;
            max-width: 440px;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            padding: 2rem 2rem 1.5rem;
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-header .logo {
            width: 84px;
            height: 84px;
            background: #ffffff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            box-shadow: 0 8px 22px rgba(0,0,0,0.08);
            border: 1px solid #eef0f4;
        }

        .input-group {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .input-group input, .input-group select {
            width: 100%;
            padding: 1rem 3rem 1rem 3rem;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.2s ease;
            background: #fff;
        }

        .input-group input:focus, .input-group select:focus {
            border-color: var(--primary-red);
            box-shadow: 0 0 0 3px rgba(208, 0, 0, 0.12);
            outline: none;
        }

        .input-group i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9aa0a6;
            font-size: 1rem;
        }

        .toggle-password {
            position: absolute;
            right: 0.9rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9aa0a6;
            cursor: pointer;
        }

        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .forgot-password {
            color: var(--primary-red);
            text-decoration: none;
        }

        .forgot-password:hover {
            text-decoration: underline;
        }

        .login-button {
            width: 100%;
            padding: 1rem;
            background-color: var(--primary-red);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform .1s ease, background-color 0.2s ease;
            box-shadow: 0 8px 16px rgba(208,0,0,0.15);
        }

        .login-button:hover {
            background-color: #b00000;
            transform: translateY(-1px);
        }

        .register-link {
            text-align: center;
            margin-top: 1.5rem;
            color: #666;
        }

        .register-link a {
            color: var(--primary-red);
            text-decoration: none;
            font-weight: 600;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

        .alert {
            padding: 0.875rem 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
        }

        @media (max-width: 768px) {
            .login-page {
                grid-template-columns: 1fr;
            }

            .login-right {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="login-page">
        <div class="login-left">
            <div class="login-form">
                <div class="login-header">
                    <div class="logo">
                        <img src="assets/images/AULogo.png" alt="AU Logo" style="height: 56px; width: auto;">
                    </div>
                    <h1 style="color: var(--primary-red); margin: 0;">Welcome Back</h1>
                    <p style="color: #666; margin-top: 0.5rem;">Please log in to your account</p>
                </div>

                <?php if ($alert_type && $alert_message): ?>
                    <div class="alert alert-<?= htmlspecialchars($alert_type) ?>">
                        <?= htmlspecialchars($alert_message) ?>
                    </div>
                <?php endif; ?>

                <div class="login-tabs">
                    <div class="login-tab active" onclick="switchTab('user')">User Login</div>
                    <div class="login-tab" onclick="switchTab('admin')">Admin Login</div>
                </div>

                <div id="userLoginForm" class="login-form-container active">
                    <form id="loginForm" action="login1.php" method="POST">
                        <!-- CSRF Token -->
                        <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        
                        <div class="input-group">
                            <i class="fa fa-user-graduate" aria-hidden="true"></i>
                            <select name="userType" id="userType" required>
                                <option value="student">Student</option>
                                <option value="staff">Staff</option>
                                <option value="guest">Guest</option>
                            </select>
                        </div>

                        <div class="input-group">
                            <i class="fa fa-id-card"></i>
                            <input type="text" placeholder="Registration Number or Email" id="regNo" name="regNo" required>
                        </div>

                        <div class="input-group">
                            <i class="fa fa-lock"></i>
                            <input type="password" id="userPassword" name="password" placeholder="Password" required autocomplete="current-password">
                            <span class="toggle-password" onclick="togglePassword('userPassword', this)" aria-label="Show password"><i class="fa fa-eye"></i></span>
                        </div>

                        <div class="remember-forgot">
                            <div class="remember-me">
                                <input type="checkbox" id="remember">
                                <label for="remember">Remember me</label>
                            </div>
                            <a href="forgot_password.php" class="forgot-password">Forgot Password?</a>
                        </div>

                        <button type="submit" name="login" class="login-button">Login</button>

                        <div class="register-link">
                            Don't have an account? <a href="registration-form.php">Register now</a>
                        </div>
                    </form>
                </div>

                <div id="adminLoginForm" class="login-form-container">
                    <form id="adminLogin" action="admin-login.php" method="POST">
                        <!-- CSRF Token -->
                        <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        
                        <div class="input-group">
                            <i class="fa fa-user-gear"></i>
                            <input type="text" placeholder="Admin Username" id="Username" name="username" required>
                        </div>

                        <div class="input-group">
                            <i class="fa fa-lock"></i>
                            <input type="password" id="adminPassword" name="password" placeholder="Admin Password" required autocomplete="current-password">
                            <span class="toggle-password" onclick="togglePassword('adminPassword', this)" aria-label="Show password"><i class="fa fa-eye"></i></span>
                        </div>

                        <button type="submit" name="admin_login" class="login-button">Admin Login</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="login-right">
            <div class="welcome-text">
                <h2>Vehicle Registration System</h2>
                <p>Manage your vehicle registrations efficiently and securely. Keep track of all your vehicles in one place.</p>
            </div>
        </div>
    </div>

    <script>
        function switchTab(tab) {
            // Update tab styles
            document.querySelectorAll('.login-tab').forEach(t => t.classList.remove('active'));
            document.querySelector(`.login-tab[onclick="switchTab('${tab}')"]`).classList.add('active');
            
            // Show/hide forms
            document.querySelectorAll('.login-form-container').forEach(f => f.classList.remove('active'));
            document.getElementById(`${tab}LoginForm`).classList.add('active');
        }

        // Update input placeholder based on user type
        document.getElementById('userType').addEventListener('change', function() {
            const regNoInput = document.getElementById('regNo');
            if (this.value === 'guest') {
                regNoInput.placeholder = 'Email Address';
                regNoInput.type = 'email';
            } else {
                regNoInput.placeholder = 'Registration Number';
                regNoInput.type = 'text';
            }
        });

        document.getElementById("loginForm").addEventListener("submit", function (e) {
            const regNo = document.getElementById("regNo").value.trim();
            const userType = document.getElementById("userType").value;
            
            if (!regNo) {
                e.preventDefault();
                alert("Please enter your " + (userType === 'guest' ? 'email address' : 'registration number') + ".");
            }
        });

        function togglePassword(inputId, el) {
            const input = document.getElementById(inputId);
            const icon = el.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
                el.setAttribute('aria-label', 'Hide password');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
                el.setAttribute('aria-label', 'Show password');
            }
        }
    </script>
</body>
</html> 