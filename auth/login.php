<?php
session_start();
include('../config/db.php');

$error = "";

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Secure Prepared Statement
    $stmt = mysqli_prepare($conn, "SELECT username, password FROM users WHERE username = ?");
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = $user['username'];
        header("Location: ../dashboard.php");
        exit();
    } else {
        $error = "Invalid credentials. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Portal | Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            width: 100%;
            max-width: 500px; /* Perfect width for iPad/Tablets */
            padding: 2rem;
            border: none;
            border-radius: 1rem;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        }
        .form-control {
            padding: 0.8rem; /* Larger touch targets for fingers */
            font-size: 1.1rem;
        }
        .btn-login {
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
            
            <div class="card login-card">
                <div class="card-body">
                    <div class="text-center mb-4">
                        <h2 class="fw-bold text-primary">WSSB CMS</h2>
                        <p class="text-muted">Enter your credentials to access the dashboard</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger py-2 text-center" role="alert">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label small text-uppercase fw-bold text-muted">Username</label>
                            <input type="text" name="username" class="form-control" placeholder="e.g. admin" required autocomplete="username">
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label small text-uppercase fw-bold text-muted">Password</label>
                            <input type="password" name="password" class="form-control" placeholder="••••••••" required autocomplete="current-password">
                        </div>

                        <button name="login" type="submit" class="btn btn-primary btn-login w-100">
                            Sign In
                        </button>
                    </form>
                </div>
                <div class="card-footer bg-transparent border-0 text-center pb-4">
                    <small class="text-muted">&copy; <?php echo date("Y"); ?> WSSB</small>
                </div>
            </div>

        </div>
    </div>
</div>

</body>
</html>