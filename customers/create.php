<?php
include('../auth/check.php');
include('../config/db.php');

if (isset($_POST['submit'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];

    // Securely insert data using Prepared Statements
    $stmt = $conn->prepare("INSERT INTO customers (customer_name, customer_email, customer_phone, customer_address) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $phone, $address);

    if ($stmt->execute()) {
        header("Location: index.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Customer - WSSB CMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .form-card { background: white; border-radius: 10px; padding: 30px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
    </style>
</head>
<body>

    <?php $navbarBasePath = '../'; $navbarActive = 'customers'; include('../includes/navbar.php'); ?>

    <main class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="form-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3 class="mb-0">Add New Customer</h3>
                        <a href="index.php" class="btn btn-sm btn-secondary">Cancel</a>
                    </div>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Full Name</label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. John Doe" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Email Address</label>
                            <input type="email" name="email" class="form-control" placeholder="john@example.com">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Phone Number</label>
                            <input type="text" name="phone" class="form-control" placeholder="012-3456789">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Address</label>
                            <textarea name="address" class="form-control" rows="3" placeholder="Street name, City, Postcode"></textarea>
                        </div>

                        <div class="d-grid gap-2">
                            <button name="submit" class="btn btn-success">Save Customer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>