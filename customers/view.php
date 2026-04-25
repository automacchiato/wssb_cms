<?php
include('../auth/check.php');
include('../config/db.php');

$customer_id = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : 0;

// Fetch Customer Info
$customer_query = mysqli_query($conn, "SELECT * FROM customers WHERE customer_id = $customer_id");
$customer = mysqli_fetch_assoc($customer_query);

if (!$customer) {
    header("Location: index.php");
    exit();
}

// Fetch Invoices
$invoices = mysqli_query($conn, "SELECT * FROM invoices WHERE customer_id = $customer_id ORDER BY invoice_id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($customer['customer_name']); ?> - Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .profile-header { background: white; border-radius: 12px; padding: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .invoice-card { border-left: 5px solid #0d6efd; transition: transform 0.2s; }
        .invoice-card:hover { transform: translateX(5px); }
        .workslip-btn-group .btn { font-size: 0.75rem; }
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

    <main class="container mb-5">
        <div class="profile-header mb-4">
            <div class="row align-items-center">
                <div class="col-md-1 text-center d-none d-md-block">
                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; font-size: 24px;">
                        <i class="fa-solid fa-user"></i>
                    </div>
                </div>
                <div class="col-md-7">
                    <h2 class="mb-1"><?php echo htmlspecialchars($customer['customer_name']); ?></h2>
                    <p class="text-muted mb-0">
                        <i class="fa-solid fa-envelope me-2"></i><?php echo htmlspecialchars($customer['customer_email']); ?> | 
                        <i class="fa-solid fa-phone me-2"></i><?php echo htmlspecialchars($customer['customer_phone']); ?>
                    </p>
                    <p class="text-muted small mt-1"><i class="fa-solid fa-location-dot me-2"></i><?php echo htmlspecialchars($customer['customer_address']); ?></p>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="edit.php?id=<?php echo $customer_id; ?>" class="btn btn-outline-primary btn-sm">Edit Profile</a>
                </div>
            </div>
        </div>

        <h4 class="mb-3"><i class="fa-solid fa-file-invoice me-2"></i> Billing History</h4>

        <?php if (mysqli_num_rows($invoices) > 0): ?>
            <?php while($inv = mysqli_fetch_assoc($invoices)) { ?>
                <div class="card mb-4 shadow-sm invoice-card">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                        <span>
                            <strong class="text-primary">#<?php echo htmlspecialchars($inv['invoice_number']); ?></strong> 
                            <span class="text-muted ms-2 small"><?php echo date("d M Y", strtotime($inv['order_date'])); ?></span>
                        </span>
                        <span class="fw-bold">Total: RM <?php echo number_format($inv['total_amount'], 2); ?></span>
                    </div>
                    <div class="card-body">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr class="small text-uppercase">
                                    <th style="width: 40%;">Item</th>
                                    <th class="text-center">Qty</th>
                                    <th>Price</th>
                                    <th class="text-end">Workslip Status</th>
                                </tr>
                            </thead>
                            <tbody>
    <?php
    $items = mysqli_query($conn, "SELECT * FROM invoice_items WHERE invoice_id=".$inv['invoice_id']);
    while($item = mysqli_fetch_assoc($items)) {
        
        $item_id = $item['invoice_item_id'];
        $item_type = strtolower(trim($item['item_type'])); // Normalize for comparison

        // 1. Optimized Workslip Checking
        $bm = mysqli_num_rows(mysqli_query($conn, "SELECT 1 FROM workslip_baju_melayu WHERE item_id=$item_id"));
        $shirt = mysqli_num_rows(mysqli_query($conn, "SELECT 1 FROM workslip_shirts WHERE item_id=$item_id"));
        $trouser = mysqli_num_rows(mysqli_query($conn, "SELECT 1 FROM workslip_trousers WHERE item_id=$item_id"));
        $jacket = mysqli_num_rows(mysqli_query($conn, "SELECT 1 FROM workslip_jacket WHERE item_id=$item_id"));
        
        $hasWorkslip = ($bm || $shirt || $trouser || $jacket);

        // 2. Map item types to your PHP files
        $workslip_map = [
            'baju melayu' => 'baju_melayu.php',
            'shirt'       => 'shirts.php',
            'trousers'    => 'trousers.php',
            'pants'       => 'trousers.php', // Alias
            'jacket'      => 'jacket.php',
            'coat'        => 'jacket.php'  // Alias
        ];

        // Determine if we have a direct match
        $auto_file = isset($workslip_map[$item_type]) ? $workslip_map[$item_type] : null;
    ?>
    <tr>
        <td>
            <strong><?php echo htmlspecialchars($item['item_type']); ?></strong>
        </td>
        <td class="text-center"><?php echo $item['quantity']; ?></td>
        <td>RM <?php echo number_format($item['amount'], 2); ?></td>
        <td class="text-end">
            <?php if($hasWorkslip): ?>
                <span class="badge rounded-pill bg-success"><i class="fa-solid fa-circle-check me-1"></i> Completed</span>
                <a href="../workslip/view.php?item_id=<?php echo $item_id; ?>" class="btn btn-link btn-sm p-0 ms-2 text-decoration-none">View</a>
            <?php else: ?>
                <div class="btn-group workslip-btn-group">
                    <button type="button" class="btn btn-warning btn-sm disabled" style="opacity: 1;">Pending</button>
                    
                    <?php if($auto_file): ?>
                        <a href="../workslip/<?php echo $auto_file; ?>?item_id=<?php echo $item_id; ?>" class="btn btn-primary btn-sm">
                            Create <?php echo ucwords($item['item_type']); ?>
                        </a>
                    <?php else: ?>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">Select Type</button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item small" href="../workslip/baju_melayu.php?item_id=<?php echo $item_id; ?>">Baju Melayu</a></li>
                                <li><a class="dropdown-item small" href="../workslip/shirts.php?item_id=<?php echo $item_id; ?>">Shirt</a></li>
                                <li><a class="dropdown-item small" href="../workslip/trousers.php?item_id=<?php echo $item_id; ?>">Trousers</a></li>
                                <li><a class="dropdown-item small" href="../workslip/jacket.php?item_id=<?php echo $item_id; ?>">Jacket</a></li>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </td>
    </tr>
    <?php } ?>
</tbody>
                        </table>
                    </div>
                </div>
            <?php } ?>
        <?php else: ?>
            <div class="alert alert-info">This customer has no invoices yet.</div>
        <?php endif; ?>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>