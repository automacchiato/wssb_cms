<?php
session_start();
include('../config/db.php');

$error = "";
$success = "";

if (isset($_POST['register'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($username === '' || $password === '' || $confirmPassword === '') {
        $error = 'Please fill in all fields.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        $checkStmt = mysqli_prepare($conn, 'SELECT 1 FROM users WHERE username = ? LIMIT 1');
        mysqli_stmt_bind_param($checkStmt, 's', $username);
        mysqli_stmt_execute($checkStmt);
        $checkResult = mysqli_stmt_get_result($checkStmt);

        if (mysqli_num_rows($checkResult) > 0) {
            $error = 'This username is already taken.';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $insertStmt = mysqli_prepare($conn, 'INSERT INTO users (username, password) VALUES (?, ?)');
            mysqli_stmt_bind_param($insertStmt, 'ss', $username, $hashedPassword);

            if (mysqli_stmt_execute($insertStmt)) {
                $success = 'Account created successfully. Redirecting to login...';
                header('Refresh: 1; url=login.php');
                exit();
            } else {
                $error = 'Unable to create account. Please try again.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Portal | Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .register-card {
            width: 100%;
            max-width: 500px;
            padding: 2rem;
            border: none;
            border-radius: 1rem;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        }
        .form-control {
            padding: 0.8rem;
            font-size: 1.1rem;
        }
        .btn-register {
            padding: 0.8rem;
            font-weight: 600;
            font-size: 1.1rem;
            border-radius: 0.5rem;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-12 d-flex justify-content-center">
            <div class="card register-card">
                <div class="card-body">
                    <div class="text-center mb-4">
                        <h2 class="fw-bold text-primary">Create Account</h2>
                        <p class="text-muted">Register a new staff account</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger py-2 text-center" role="alert">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success py-2 text-center" role="alert">
                            <?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label small text-uppercase fw-bold text-muted">Username</label>
                            <input type="text" name="username" class="form-control" placeholder="e.g. admin" required autocomplete="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small text-uppercase fw-bold text-muted">Password</label>
                            <input type="password" name="password" class="form-control" placeholder="••••••••" required autocomplete="new-password">
                        </div>

                        <div class="mb-4">
                            <label class="form-label small text-uppercase fw-bold text-muted">Confirm Password</label>
                            <input type="password" name="confirm_password" class="form-control" placeholder="••••••••" required autocomplete="new-password">
                        </div>

                        <button name="register" type="submit" class="btn btn-primary btn-register w-100">
                            Register
                        </button>
                    </form>
                </div>
                <div class="card-footer bg-transparent border-0 text-center pb-3">
                    <small class="text-muted">
                        Already have an account? <a href="login.php">Sign in</a>
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
