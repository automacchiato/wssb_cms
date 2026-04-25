<?php
include('../auth/check.php');
include('../config/db.php');

// Get search term from the URL
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

// Build the query
if ($search != '') {
    $sql = "SELECT * FROM customers WHERE 
            customer_name LIKE '%$search%' OR 
            customer_email LIKE '%$search%' OR 
            customer_phone LIKE '%$search%'";
} else {
    $sql = "SELECT * FROM customers";
}

$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Customers - WSSB CMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .table-container { background: white; border-radius: 8px; padding: 20px; box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075); }
    </style>
</head>

<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="../dashboard.php">WSSB CMS</a>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="../dashboard.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link active" href="index.php">Customers</a></li>
                    <li class="nav-item"><a class="nav-link" href="../invoices/index.php">Invoices</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Customer Directory</h2>
            <a href="create.php" class="btn btn-success">+ Add New Customer</a>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <form action="index.php" method="GET" class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="Search by name, email, or phone..." value="<?php echo htmlspecialchars($search); ?>">
                    <button class="btn btn-primary" type="submit">
                        <i class="fa-solid fa-magnifying-glass"></i> Search
                    </button>
                    <?php if($search != ''): ?>
                        <a href="index.php" class="btn btn-outline-secondary">Clear</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <div class="table-container shadow-sm">
            <table class="table table-hover align-middle" id="customerTable">
                <thead class="table-light">
                    <tr>
                        <th scope="col">Name</th>
                        <th scope="col">Email Address</th>
                        <th scope="col">Phone Number</th>
                        <th scope="col" class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                            <tr>
                                <td class="fw-bold"><?php echo htmlspecialchars($row['customer_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['customer_email']); ?></td>
                                <td><?php echo htmlspecialchars($row['customer_phone']); ?></td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <a href="edit.php?id=<?php echo $row['customer_id']; ?>" class="btn btn-outline-warning btn-sm">Edit</a>
                                        <a href="delete.php?id=<?php echo $row['customer_id']; ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Delete customer?');">Delete</a>
                                        <a href="view.php?id=<?php echo $row['customer_id']; ?>" class="btn btn-info btn-sm">View</a>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">No results found for "<?php echo htmlspecialchars($search); ?>".</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>