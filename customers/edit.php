<?php
include('../auth/check.php');
include('../config/db.php');

$id = $_GET['id'];

// Use Prepared Statements to prevent SQL Injection
$stmt = $conn->prepare("SELECT * FROM customers WHERE customer_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if (isset($_POST['update'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];

    // Update using Prepared Statement
    $update_stmt = $conn->prepare("UPDATE customers SET customer_name=?, customer_email=?, customer_phone=?, customer_address=? WHERE customer_id=?");
    $update_stmt->bind_param("ssssi", $name, $email, $phone, $address, $id);
    
    if ($update_stmt->execute()) {
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
    <title>Edit Customer - WSSB CMS</title>
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
                        <h3 class="mb-0">Edit Customer</h3>
                        <a href="index.php" class="btn btn-sm btn-secondary">Back to List</a>
                    </div>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Full Name</label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($data['customer_name']); ?>" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Email Address</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($data['customer_email']); ?>" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Phone Number</label>
                            <input type="text" name="phone" value="<?php echo htmlspecialchars($data['customer_phone']); ?>" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Address</label>
                            <textarea name="address" class="form-control" rows="3"><?php echo htmlspecialchars($data['customer_address']); ?></textarea>
                        </div>

                        <div class="d-grid gap-2">
                            <button name="update" class="btn btn-primary">Update Customer Details</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>