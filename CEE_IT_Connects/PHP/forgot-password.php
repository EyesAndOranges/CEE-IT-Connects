<?php
require 'db.php';
session_start();

//send code
if(isset($_POST['send_code'])){
    $email = $_POST['email'];
    $method = $_POST['method'];

    $stmt = $pdo->prepare("SELECT * FROM students WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if($student){
        $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiry = date("Y-m-d H:i:s", strtotime("+10 minutes"));

        $stmt = $pdo->prepare("UPDATE students SET reset_code = :code, reset_expiry = :expiry WHERE id = :id");
        $stmt->execute([
            'code' => $code,
            'expiry' => $expiry,
            'id' => $student['id']
        ]);

        $_SESSION['reset_student_id'] = $student['id'];

        if($method == 'email'){
            $message = "A verification code has been sent to your email: $email";
        } else {
            $message = "A verification code has been sent to your phone number: ".$student['contact_number'];
        }

        header("Location: forgot-password.php?step=code&msg=".urlencode($message));
        exit;
    } else {
        $error = "No student found with that email.";
    }
}

//verify
if(isset($_POST['verify_code'])){
    $entered_code = $_POST['code'];

    $stmt = $pdo->prepare("SELECT * FROM students WHERE id = :id AND reset_code = :code AND reset_expiry >= NOW()");
    $stmt->execute([
        'id' => $_SESSION['reset_student_id'],
        'code' => $entered_code
    ]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if($student){
        $_SESSION['code_verified'] = true;
        header("Location: forgot-password.php?step=reset");
        exit;
    } else {
        $error = "Invalid or expired verification code.";
    }
}

// Reset pass
if(isset($_POST['reset_password'])){
    if(!isset($_SESSION['code_verified']) || !$_SESSION['code_verified']){
        header("Location: forgot-password.php");
        exit;
    }

    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if($new_password !== $confirm_password){
        $error = "Passwords do not match.";
    } else {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE students SET password_hash = :password, reset_code = NULL, reset_expiry = NULL WHERE id = :id");
        $stmt->execute([
            'password' => $hashed,
            'id' => $_SESSION['reset_student_id']
        ]);

        session_unset();
        session_destroy();

        header("Location: student-login.php?reset=success");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password - CEE IT Connects</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../CSS/student-login.css">
<style>
/* Center the card inside the right panel */
.card {
    background: #fff;
    padding: 30px;
    border-radius: 12px;
    max-width: 400px;
    margin: auto;
    box-shadow: 0 8px 20px rgba(0,0,0,0.3);
    text-align: center;
}

.btn-login { width: 100%; }
.alert { font-size: 0.9rem; }
</style>
</head>
<body>

<div class="container-fluid login-container">
    <div class="row h-100">

        <!-- LEFT IMAGE PANEL -->
        <div class="col-md-5 p-0 left-panel"></div>

        <!-- RIGHT FORM PANEL -->
        <div class="col-md-7 right-panel">
            <img src="../Sources/CEE IT Connects Logo.png" alt="CEE IT Logo" class="logo-img">

            <div class="card">
                <?php if(isset($_GET['step']) && $_GET['step'] == 'code'): ?>
                    <h3>Enter Verification Code</h3>
                    <p><?php echo htmlspecialchars($_GET['msg']); ?></p>
                    <?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
                    <form method="POST">
                        <div class="mb-3">
                            <input type="text" name="code" class="form-control" placeholder="Enter Code" required>
                        </div>
                        <button type="submit" name="verify_code" class="btn-login">Verify Code</button>
                    </form>

                <?php elseif(isset($_GET['step']) && $_GET['step'] == 'reset'): ?>
                    <h3>Reset Password</h3>
                    <?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
                    <form method="POST">
                        <div class="mb-3">
                            <input type="password" name="new_password" class="form-control" placeholder="New Password" required>
                        </div>
                        <div class="mb-3">
                            <input type="password" name="confirm_password" class="form-control" placeholder="Confirm Password" required>
                        </div>
                        <button type="submit" name="reset_password" class="btn-login">Reset Password</button>
                    </form>

                <?php else: ?>
                    <h3>Forgot Password</h3>
                    <p>Choose how you want to receive your verification code</p>
                    <?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
                    <form method="POST">
                        <div class="mb-3">
                            <input type="email" name="email" id="emailInput" class="form-control" placeholder="Enter your registered email" required>
                        </div>
                        <div class="mb-3 text-start">
                            <label class="form-label">Send code via:</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input method-radio" type="radio" name="method" value="email" checked>
                                <label class="form-check-label">Email</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input method-radio" type="radio" name="method" value="phone">
                                <label class="form-check-label">Phone</label>
                            </div>
                        </div>
                        <button type="submit" name="send_code" class="btn-login">Send Verification Code</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Change placeholder dynamically
const emailInput = document.getElementById('emailInput');
document.querySelectorAll('.method-radio').forEach(radio => {
    radio.addEventListener('change', () => {
        if(radio.value === 'email'){
            emailInput.type = 'email';
            emailInput.placeholder = 'Enter your registered email';
        } else {
            emailInput.type = 'text';
            emailInput.placeholder = 'Enter your registered phone number';
        }
    });
});
</script>

</body>
</html>
