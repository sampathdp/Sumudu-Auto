<?php
require_once __DIR__ . '/../../classes/Includes.php';

// This page is shown to logged-in users who don't have permissions
// If not logged in, they should go to login page instead
if (!isLoggedIn()) {
    redirect(BASE_URL . 'views/auth/login.php');
}
?>
<!DOCTYPE html>
<html lang="en" class="h-100">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Access Denied</title>
    <?php include '../../includes/main-css.php'; ?>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Public Sans', sans-serif;
        }

        .access-denied-container {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 2rem;
        }

        .access-denied-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 100%;
            padding: 3rem;
            text-align: center;
        }

        .error-icon {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #ff6b6b, #ee5a6f);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            animation: pulse 2s infinite;
        }

        .error-icon i {
            font-size: 60px;
            color: white;
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }

        .error-title {
            font-size: 2rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 1rem;
        }

        .error-code {
            font-size: 5rem;
            font-weight: 900;
            color: #e74c3c;
            line-height: 1;
            margin-bottom: 1rem;
        }

        .error-message {
            color: #6c757d;
            font-size: 1.1rem;
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 2rem;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            margin: 0.5rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .btn-secondary {
            background: #f1f3f5;
            color: #495057;
        }

        .btn-secondary:hover {
            background: #e9ecef;
            color: #495057;
        }

        .info-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 2rem;
            text-align: left;
        }

        .info-box strong {
            display: block;
            margin-bottom: 0.5rem;
            color: #856404;
        }

        .info-box p {
            margin: 0;
            color: #856404;
            font-size: 0.9rem;
        }
    </style>
</head>

<body>
    <div class="access-denied-container">
        <div class="access-denied-card">
            <div class="error-icon">
                <i class="fas fa-lock"></i>
            </div>

            <div class="error-code">403</div>
            <h1 class="error-title">Access Denied</h1>
            <p class="error-message">
                Sorry, you don't have permission to access this page. 
                If you believe this is an error, please contact your system administrator.
            </p>

            <div>
                <a href="<?php echo BASE_URL; ?>views/Dashboard/" class="btn-action btn-primary">
                    <i class="fas fa-home"></i> Go to Dashboard
                </a>
                <a href="javascript:history.back()" class="btn-action btn-secondary">
                    <i class="fas fa-arrow-left"></i> Go Back
                </a>
            </div>

            <div class="info-box">
                <strong><i class="fas fa-info-circle"></i> Need Access?</strong>
                <p>Contact your administrator to request permissions for this page. They can grant you access through the User Permissions management section.</p>
            </div>
        </div>
    </div>

    <?php include '../../includes/main-js.php'; ?>
</body>

</html>
