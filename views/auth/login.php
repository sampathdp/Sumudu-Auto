<?php
require_once __DIR__ . '/../../classes/Includes.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(BASE_URL . 'views/Dashboard/');
}

// Load company profile logic
$companyProfile = new CompanyProfile();
$companySettings = null;
$targetCompanyId = null;

// Check for company parameter
if (isset($_GET['company'])) {
    $companyCode = $_GET['company'];
    $company = new Company();
    if ($company->loadByCode($companyCode)) {
        $targetCompanyId = $company->id;
        if ($companyProfile->loadByCompanyId($targetCompanyId)) {
            // Profile loaded successfully
        } else {
            // Fallback if profile doesn't exist but company does
            $companyProfile->name = $company->name;
            $companyProfile->theme = 'default';
        }
    }
} else {
    // Default behavior - load active (e.g., main company or first active)
    $companyProfile->loadActive();
}

$companyLogo = $companyProfile->image_name ? BASE_URL . 'uploads/company/' . $companyProfile->image_name : null;
$companyName = $companyProfile->name ?: (APP_NAME ?? 'GaragePulse');

// Theme gradient for header
$themeGradients = [
    'default' => 'linear-gradient(135deg, #1a1a2e 0%, #16213e 100%)',
    'purple' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
    'blue' => 'linear-gradient(135deg, #2193b0 0%, #6dd5ed 100%)',
    'green' => 'linear-gradient(135deg, #11998e 0%, #38ef7d 100%)',
    'red' => 'linear-gradient(135deg, #cb2d3e 0%, #ef473a 100%)',
    'orange' => 'linear-gradient(135deg, #f46b45 0%, #eea849 100%)',
    'dark' => 'linear-gradient(135deg, #232526 0%, #414345 100%)',
    'teal' => 'linear-gradient(135deg, #0f2027 0%, #2c5364 100%)',
];
$headerGradient = $themeGradients[$companyProfile->theme] ?? $themeGradients['default'];
?>
<!DOCTYPE html>
<html lang="en" class="h-100">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | <?php echo htmlspecialchars($companyName); ?></title>
    <link rel="shortcut icon" href="<?php echo asset('images/favicon.ico'); ?>">

    <?php include '../../includes/main-css.php'; ?>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8ec 100%);
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 1rem;
            overflow-x: hidden;
            overflow-y: auto;
        }



        .login-container {
            width: 100%;
            max-width: 400px;
            display: flex;
            flex-direction: column;
        }

        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-header {
            background: #000000;
            padding: clamp(1.5rem, 5vw, 2.5rem) clamp(1rem, 4vw, 2rem);
            text-align: center;
        }

        .logo-wrapper {
            position: relative;
            z-index: 1;
            margin-bottom: 1rem;
        }

        .logo-img {
            max-width: min(80%, 200px);
            max-height: clamp(60px, 15vh, 100px);
            height: auto;
            margin: 0 auto 1rem;
            display: block;
            object-fit: contain;
        }

        .logo-placeholder {
            width: 100px;
            height: 100px;
            margin: 0 auto 1.5rem;
            background: rgba(255,255,255,0.2);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logo-placeholder i {
            font-size: 3rem;
            color: white;
        }

        .login-header h4 {
            color: white;
            font-size: clamp(1.1rem, 4vw, 1.5rem);
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .login-header p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 0.95rem;
            position: relative;
            z-index: 1;
        }

        .login-body {
            padding: clamp(1.25rem, 4vw, 2rem);
        }

        .form-label {
            font-weight: 600;
            color: #4a5568;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            display: block;
        }

        .input-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .input-group-text {
            background: #f7fafc;
            border: 2px solid #e2e8f0;
            border-right: none;
            border-radius: 10px 0 0 10px;
            padding: 0.75rem 1rem;
            color: #4361ee;
        }

        .form-control {
            padding: 0.75rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 0 10px 10px 0;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .input-group .form-control {
            border-left: none;
        }

        .form-control:focus {
            border-color: #4361ee;
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
            outline: none;
        }

        .input-group:focus-within .input-group-text {
            border-color: #4361ee;
            background: rgba(67, 97, 238, 0.05);
        }

        .toggle-password {
            position: absolute;
            right: 0;
            top: 0;
            bottom: 0;
            border: 2px solid #e2e8f0;
            border-left: none;
            border-radius: 0 10px 10px 0;
            background: white;
            padding: 0 1rem;
            color: #718096;
            transition: all 0.3s ease;
            z-index: 10;
        }

        .toggle-password:hover {
            color: #4361ee;
            background: #f7fafc;
        }

        .form-check {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .form-check-input {
            width: 1.2rem;
            height: 1.2rem;
            margin-right: 0.5rem;
            cursor: pointer;
            border: 2px solid #cbd5e0;
        }

        .form-check-input:checked {
            background-color: #4361ee;
            border-color: #4361ee;
        }

        .form-check-label {
            color: #4a5568;
            font-size: 0.9rem;
            cursor: pointer;
            user-select: none;
        }

        .btn-login {
            background: #4361ee;
            border: none;
            padding: 0.9rem 2rem;
            font-weight: 600;
            font-size: 1rem;
            border-radius: 10px;
            color: white;
            width: 100%;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.3);
        }

        .btn-login:hover {
            background: #3651d4;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(67, 97, 238, 0.4);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .btn-login:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .spinner-border {
            width: 1rem;
            height: 1rem;
            border-width: 2px;
        }

        .footer-text {
            text-align: center;
            margin-top: 2rem;
        }

        .footer-text p {
            color: #666;
            font-size: 0.85rem;
            margin-bottom: 0.25rem;
        }

        .footer-text .powered-by {
            color: #999;
            font-size: 0.75rem;
        }

        .footer-text .powered-by a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        .footer-text .powered-by a:hover {
            text-decoration: underline;
        }

        .alert {
            border-radius: 10px;
            padding: 0.75rem 1rem;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }

        /* Responsive Design */
        @media (max-width: 576px) {
            .login-header {
                padding: 2rem 1.5rem;
            }

            .login-body {
                padding: 2rem 1.5rem;
            }

            .login-header h4 {
                font-size: 1.5rem;
            }

            .logo-img {
                max-width: 150px;
            }
        }

        /* Loading Animation */
        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .spinner-border {
            animation: spin 0.75s linear infinite;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="logo-wrapper">
                    <img src="<?php echo BASE_URL; ?>assets/img/Logo1.png" alt="Garage Pulse" class="logo-img">
                    <h4>Garage Pulse</h4>
                    <p>Sign in to your account</p>
                </div>
            </div>

            <div class="login-body">
                <form id="login" method="post" autocomplete="off">
                    <div class="mb-3">
                        <label for="company_code" class="form-label">Company Code</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-building"></i>
                            </span>
                            <input type="text" class="form-control" id="company_code" 
                                name="company_code" placeholder="Enter company code" required 
                                value="<?php echo isset($_GET['company']) ? htmlspecialchars($_GET['company']) : ''; ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-user"></i>
                            </span>
                            <input type="text" class="form-control" id="username"
                                name="username" placeholder="Enter your username" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-lock"></i>
                            </span>
                            <input type="password" class="form-control" id="password"
                                name="password" placeholder="Enter your password" required>
                            <button class="toggle-password" type="button" tabindex="-1">
                                <i class="far fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="remember-me">
                        <label class="form-check-label" for="remember-me">
                            Remember me for 30 days
                        </label>
                    </div>

                    <button type="submit" class="btn btn-login">
                        <span class="spinner-border spinner-border-sm d-none me-2"></span>
                        <span class="btn-text">Sign In</span>
                    </button>
                </form>
            </div>
        </div>

        <div class="footer-text">
            <p>&copy; <?php echo date('Y'); ?> Codeplay Studio. All rights reserved.</p>
            <p class="powered-by">Powered by <a href="#">Codeplay Studio</a></p>
        </div>
    </div>

    <?php include '../../includes/main-js.php'; ?>
    <script src="<?php echo '../../Ajax/js/user.js'; ?>"></script>

    <script>
        // Toggle password visibility
        document.querySelector('.toggle-password').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });

        // Add enter key handler - trigger button click to invoke jQuery submit handler
        document.getElementById('login').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                // Trigger the submit button click to invoke the jQuery handler
                document.querySelector('.btn-login').click();
            }
        });

        // Focus first input on load
        window.addEventListener('load', function() {
            document.getElementById('username').focus();
        });
    </script>
</body>

</html>