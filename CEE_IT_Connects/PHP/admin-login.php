<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CEE IT Connects - Admin Login</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../CSS/student-login.css">
</head>
<body>

    <div id="loading-screen">
        <img src="../Sources/CEE IT Connects Logo.png" alt="Logo" class="loading-logo">
    </div>

    <div class="container-fluid login-container">
        <div class="row h-100">
            <div class="col-md-5 p-0 left-panel"></div>

            <div class="col-md-7 right-panel">
                <img src="../Sources/CEE IT Connects Logo.png" alt="CEE IT Logo" class="logo-img">
                <h2 class="login-title">CEE IT CONNECTS</h2>
                <h3 class="login-subtitle">Login</h3>

                <div class="role-toggle">
                    <a href="student-login.php" class="btn-role inactive">Student</a>
                    <a href="#" class="btn-role active">Admin</a>
                </div>

                <form class="form-wrapper" method="POST" action="login.php">
                    <div class="mb-3">
                        <input type="email" class="form-control" name="email" placeholder="Student Email" required>
                    </div>

                    <div class="mb-3 password-group">
                        <input type="password" class="form-control" name="password" id="password" placeholder="Password" required>
                        <i class="fa fa-eye-slash toggle-password" id="togglePasswordIcon"></i>
                    </div>

                    <input type="hidden" name="role" value="admin"> <!-- Hidden field to identify role -->

                    <a href="#" class="forgot-link">Forgot password?</a>
                    <button type="submit" class="btn-login">Login</button>
                </form>
                <div class="register-text mt-3" style="text-align: center; margin-top: 20px; 
                font-size: 0.9rem; color: #333;">Click <a href="student-register.php" style="color: #e05834;">here</a> to register</div>
            </div>
        </div>
    </div>
    

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../JS/login.js"></script>
</body>
</html>