<?php
include('../auth/check.php');
include('../config/db.php');

// Initialize variables for filtering
$where_clauses = [];
$search_inv = isset($_GET['search_inv']) ? mysqli_real_escape_string($conn, $_GET['search_inv']) : '';
$search_cust = isset($_GET['search_cust']) ? mysqli_real_escape_string($conn, $_GET['search_cust']) : '';
$search_date = isset($_GET['search_date']) ? mysqli_real_escape_string($conn, $_GET['search_date']) : '';

// Build the dynamic WHERE query
if (!empty($search_inv)) {
    $where_clauses[] = "invoices.invoice_number LIKE '%$search_inv%'";
}
if (!empty($search_cust)) {
    $where_clauses[] = "customers.customer_name LIKE '%$search_cust%'";
}
if (!empty($search_date)) {
    $where_clauses[] = "invoices.order_date = '$search_date'";
}

$where_sql = "";
if (count($where_clauses) > 0) {
    $where_sql = "WHERE " . implode(' AND ', $where_clauses);
}

// Fetch invoices with customer names using a JOIN and the filter
$query_str = "
    SELECT invoices.*, customers.customer_name 
    FROM invoices
    JOIN customers ON invoices.customer_id = customers.customer_id
    $where_sql
    ORDER BY invoices.invoice_id DESC
";

$query = mysqli_query($conn, $query_str);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoices - WSSB CMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .table-container { background: white; border-radius: 8px; padding: 20px; box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075); }
        .invoice-no { font-family: 'Courier New', Courier, monospace; font-weight: bold; color: #0d6efd; }
        .filter-section { background: #fff; border-radius: 8px; padding: 15px; margin-bottom: 20px; border: 1px solid #dee2e6; }
    </style>
</head>

<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="../dashboard.php">WSSB CMS</a>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="../dashboard.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="../customers/index.php">Customers</a></li>
                    <li class="nav-item"><a class="nav-link active" href="index.php">Invoices</a></li>
                </ul>
                <div class="navbar-nav">
                    <a href="../auth/logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fa-solid fa-file-invoice-dollar me-2"></i>Invoices</h2>
            <a href="create.php" class="btn btn-success">
                <i class="fa-solid fa-plus me-1"></i> Create New Invoice
            </a>
        </div>

        <div class="filter-section shadow-sm">
            <form method="GET" action="index.php" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Invoice Number</label>
                    <input type="text" name="search_inv" class="form-control form-control-sm" placeholder="e.g. INV-2024" value="<?php echo htmlspecialchars($search_inv); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Customer Name</label>
                    <input type="text" name="search_cust" class="form-control form-control-sm" placeholder="Search customer..." value="<?php echo htmlspecialchars($search_cust); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Order Date</label>
                    <input type="date" name="search_date" class="form-control form-control-sm" value="<?php echo htmlspecialchars($search_date); ?>">
                </div>
                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                        <i class="fa-solid fa-magnifying-glass me-1"></i> Filter
                    </button>
                    <a href="index.php" class="btn btn-outline-secondary btn-sm flex-grow-1">Reset</a>
                </div>
            </form>
        </div>

        <div class="table-container shadow-sm">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th scope="col">Invoice #</th>
                        <th scope="col">Customer</th>
                        <th scope="col">Date</th>
                        <th scope="col" class="text-end">Total Amount</th>
                        <th scope="col" class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($query) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($query)) { ?>
                            <tr>
                                <td>
                                    <span class="badge bg-light text-dark border invoice-no">
                                        <?php echo htmlspecialchars($row['invoice_number']); ?>
                                    </span>
                                </td>
                                <td class="fw-bold"><?php echo htmlspecialchars($row['customer_name']); ?></td>
                                <td><?php echo date("d M Y", strtotime($row['order_date'])); ?></td>
                                <td class="text-end fw-bold">RM <?php echo number_format($row['total_amount'], 2); ?></td>
                                <td class="text-center">
                                    <a href="view.php?id=<?php echo $row['invoice_id']; ?>" class="btn btn-primary btn-sm">
                                        <i class="fa-solid fa-eye me-1"></i> View
                                    </a>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No matching invoices found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>