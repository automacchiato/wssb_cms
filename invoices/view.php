<?php
include('../auth/check.php');
include('../config/db.php');

$id = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : 0;

// Fetch Invoice details with Customer Name and New Date Fields
$invoice_query = mysqli_query($conn, "
    SELECT invoices.*, customers.customer_name, customers.customer_email, customers.customer_address 
    FROM invoices
    JOIN customers ON invoices.customer_id = customers.customer_id
    WHERE invoice_id = $id
");

$invoice = mysqli_fetch_assoc($invoice_query);

if (!$invoice) {
    header("Location: index.php");
    exit();
}

$items = mysqli_query($conn, "SELECT * FROM invoice_items WHERE invoice_id=$id");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice <?php echo $invoice['invoice_number']; ?> - WSSB CMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .invoice-box { background: white; border-radius: 8px; padding: 40px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); position: relative; }
        .date-badge { padding: 10px; border-radius: 6px; background: #f1f3f5; border-left: 4px solid #dee2e6; }
        .date-badge.fitting { border-left-color: #0dcaf0; }
        .date-badge.delivery { border-left-color: #198754; }
        .fabric-info { font-size: 0.85rem; color: #6c757d; margin-top: 4px; }
        
        @media print {
            .navbar, .btn-print, .no-print, .workslip-col { display: none !important; }
            body { background-color: white; }
            .invoice-box { box-shadow: none; padding: 0; }
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4 no-print">
        <div class="container">
            <a class="navbar-brand" href="../dashboard.php">WSSB CMS</a>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="../dashboard.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="../customers/index.php">Customers</a></li>
                    <li class="nav-item"><a class="nav-link active" href="index.php">Invoices</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4 no-print">
            <a href="index.php" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
            <div>
                <a href="edit.php?id=<?php echo $id; ?>" class="btn btn-warning"><i class="fa-solid fa-edit"></i> Edit</a>
                <button onclick="window.print()" class="btn btn-primary"><i class="fa-solid fa-print"></i> Print</button>
                <a href="pdf.php?id=<?php echo $id; ?>" class="btn btn-success" target="_blank">Download PDF</a>
            </div>
        </div>

        <div class="invoice-box">
            <div class="row">
                <div class="col-md-6">
                    <h2 class="text-primary fw-bold">WSSB CMS</h2>
                    <p class="text-muted">Tailor Management System<br>Kuala Lumpur, Malaysia</p>
                </div>
                <div class="col-md-6 text-end">
                    <h1 class="h3 text-uppercase">Invoice</h1>
                    <p class="mb-0"><strong>Number:</strong> #<?php echo htmlspecialchars($invoice['invoice_number']); ?></p>
                    <p><strong>Order Date:</strong> <?php echo date("d M Y", strtotime($invoice['order_date'])); ?></p>
                </div>
            </div>

            <hr class="my-4">

            <div class="row mb-4">
                <div class="col-md-5">
                    <h6 class="text-muted text-uppercase small fw-bold">Billed To:</h6>
                    <h5 class="mb-1 fw-bold"><?php echo htmlspecialchars($invoice['customer_name']); ?></h5>
                    <p class="mb-1 text-muted"><?php echo htmlspecialchars($invoice['customer_email']); ?></p>
                    <p class="text-muted small"><?php echo nl2br(htmlspecialchars($invoice['customer_address'])); ?></p>
                </div>
                
                <div class="col-md-7">
                    <div class="row g-2 text-end justify-content-end">
                        <div class="col-sm-5">
                            <div class="date-badge fitting">
                                <span class="small text-muted d-block uppercase fw-bold">Fitting Date</span>
                                <span class="fw-bold text-dark">
                                    <?php echo $invoice['fitting_date'] ? date("d M Y", strtotime($invoice['fitting_date'])) : 'Not Set'; ?>
                                </span>
                            </div>
                        </div>
                        <div class="col-sm-5">
                            <div class="date-badge delivery">
                                <span class="small text-muted d-block uppercase fw-bold">Delivery Date</span>
                                <span class="fw-bold text-dark">
                                    <?php echo $invoice['delivery_date'] ? date("d M Y", strtotime($invoice['delivery_date'])) : 'Not Set'; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <table class="table table-hover align-middle">
                <thead class="table-light border-top border-bottom">
                    <tr>
                        <th>Item & Fabric Details</th>
                        <th class="text-center">Qty</th>
                        <th class="text-end">Unit Price</th>
                        <th class="text-end">Total</th>
                        <th class="text-center no-print">Workslip</th>
                    </tr>
                </thead>
                <tbody>
                <?php while($i = mysqli_fetch_assoc($items)) { 
                    $subtotal = $i['quantity'] * $i['amount'];
                    $item_id = $i['invoice_item_id'];
                    
                    // Workslip detection
                    $hasWorkslip = false;
                    $ws_checks = ['workslip_baju_melayu', 'workslip_shirts', 'workslip_trousers', 'workslip_jacket'];
                    foreach($ws_checks as $table) {
                        if(mysqli_num_rows(mysqli_query($conn, "SELECT 1 FROM $table WHERE item_id=$item_id"))) {
                            $hasWorkslip = true;
                            break;
                        }
                    }
                ?>
                <tr>
                    <td>
                        <div class="fw-bold"><?php echo htmlspecialchars($i['item_type']); ?></div>
                        <?php if($i['fabric_code'] || $i['fabric_name']): ?>
                            <div class="fabric-info">
                                <i class="fa-solid fa-scroll me-1"></i> 
                                <?php echo htmlspecialchars($i['fabric_code'] . " - " . $i['fabric_name']); ?> 
                                (<?php echo htmlspecialchars($i['fabric_color']); ?>) | 
                                <strong>Usage:</strong> <?php echo htmlspecialchars($i['fabric_usage']); ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td class="text-center"><?php echo $i['quantity']; ?></td>
                    <td class="text-end">RM <?php echo number_format($i['amount'], 2); ?></td>
                    <td class="text-end fw-bold">RM <?php echo number_format($subtotal, 2); ?></td>

                    <td class="text-center no-print">
    <?php if($hasWorkslip): ?>
        <a href="../workslip/view.php?item_id=<?php echo $item_id; ?>" class="btn btn-sm btn-outline-info">
            <i class="fa-solid fa-eye"></i> View
        </a>
    <?php else: 
        // Determine the link based on item type
        $link = "#";
        $type = strtolower($i['item_type']);
        
        if (strpos($type, 'baju melayu') !== false) {
            $link = "baju_melayu.php";
        } elseif (strpos($type, 'shirt') !== false) {
            $link = "shirts.php";
        } elseif (strpos($type, 'trouser') !== false) {
            $link = "trousers.php";
        } elseif (strpos($type, 'jacket') !== false) {
            $link = "jacket.php";
        } elseif (strpos($type, 'vest') !== false) {
            $link = "vest.php";
        }
    ?>
        <a href="../workslip/<?php echo $link; ?>?item_id=<?php echo $item_id; ?>" class="btn btn-sm btn-outline-primary">
            <i class="fa-solid fa-plus"></i> Create
        </a>
    <?php endif; ?>
</td>
                </tr>
                <?php } ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-end fw-bold py-3">Grand Total:</td>
                        <td class="text-end fw-bold py-3 h5 text-primary">RM <?php echo number_format($invoice['total_amount'], 2); ?></td>
                        <td class="no-print"></td>
                    </tr>
                </tfoot>
            </table>

            <div class="mt-5 pt-4 border-top">
                <div class="row">
                    <div class="col-md-6 small text-muted">
                        <p class="mb-0"><strong>Note:</strong> Balance payment is due upon delivery.</p>
                    </div>
                    <div class="col-md-6 text-end">
                        <p class="text-muted small">Thank you for your business!</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>