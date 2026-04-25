<?php
include('../auth/check.php');
include('../config/db.php');

$item_id = isset($_GET['item_id']) ? mysqli_real_escape_string($conn, $_GET['item_id']) : 0;

// ITEM + INVOICE + CUSTOMER
$data_query = mysqli_query($conn, "
    SELECT invoice_items.*, invoices.invoice_number, customers.customer_name
    FROM invoice_items
    JOIN invoices ON invoice_items.invoice_id = invoices.invoice_id
    JOIN customers ON invoices.customer_id = customers.customer_id
    WHERE invoice_items.invoice_item_id = $item_id
");
$data = mysqli_fetch_assoc($data_query);

if (!$data) {
    header("Location: ../customers/index.php");
    exit();
}

// DETECT WORKSLIP TYPE & FETCH DATA
$type = "Unknown";
$workslip = null;

$tables = ['workslip_baju_melayu' => 'Baju Melayu', 'workslip_shirts' => 'Shirt', 'workslip_trousers' => 'Trouser', 'workslip_jacket' => 'Jacket'];

foreach ($tables as $table => $label) {
    $q = mysqli_query($conn, "SELECT * FROM $table WHERE item_id = $item_id");
    if (mysqli_num_rows($q) > 0) {
        $type = $label;
        $workslip = mysqli_fetch_assoc($q);
        break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workslip - <?php echo htmlspecialchars($data['customer_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .workslip-card { background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 30px; }
        .spec-label { color: #6c757d; font-size: 0.8rem; text-transform: uppercase; font-weight: bold; }
        .spec-value { font-size: 1.1rem; font-weight: 600; color: #333; }
        .measurement-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; }
        .measurement-item { border-bottom: 1px dashed #dee2e6; padding-bottom: 5px; }
        
        @media print {
            .navbar, .no-print { display: none !important; }
            body { background-color: white; }
            .workslip-card { box-shadow: none; border: 1px solid #eee; padding: 0; }
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4 no-print">
        <div class="container">
            <a class="navbar-brand" href="../dashboard.php">WSSB CMS</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="../dashboard.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="../customers/index.php">Customers</a></li>
                    <li class="nav-item"><a class="nav-link" href="../invoices/index.php">Invoices</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4 no-print">
            <button onclick="history.back()" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left"></i> Back</button>
            <button onclick="window.print()" class="btn btn-primary"><i class="fa-solid fa-print"></i> Print Workslip</button>
        </div>

        <div class="workslip-card">
            <div class="row mb-4 border-bottom pb-3">
                <div class="col-md-6">
                    <h2 class="text-primary mb-1"><?php echo htmlspecialchars($data['customer_name']); ?></h2>
                    <p class="text-muted">Job Type: <span class="badge bg-info text-dark"><?php echo $type; ?></span></p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0 text-muted small text-uppercase fw-bold">Invoice Number</p>
                    <h4 class="mb-0">#<?php echo htmlspecialchars($data['invoice_number']); ?></h4>
                    <p class="text-muted small mt-1">Item ID: <?php echo $item_id; ?></p>
                </div>
            </div>

            <?php if(!empty($data['fabric_code'])): ?>
            <div class="row mb-4">
                <div class="col-12">
                    <div class="p-3 bg-light rounded border">
                        <h6 class="small text-uppercase fw-bold text-muted mb-2">Assigned Fabric Details</h6>
                        <div class="row">
                            <div class="col-md-3"><small class="d-block text-muted">Code</small> <strong><?php echo $data['fabric_code']; ?></strong></div>
                            <div class="col-md-3"><small class="d-block text-muted">Name</small> <strong><?php echo $data['fabric_name']; ?></strong></div>
                            <div class="col-md-3"><small class="d-block text-muted">Color</small> <strong><?php echo $data['fabric_color']; ?></strong></div>
                            <div class="col-md-3"><small class="d-block text-muted">Usage</small> <strong><?php echo $data['fabric_usage']; ?></strong></div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <h5 class="mb-4 border-start border-4 border-primary ps-3">Measurements & Specifications</h5>
            
            <div class="measurement-grid mb-5">
                <?php 
                if ($workslip) {
                    foreach($workslip as $key => $value) { 
                        // Skip system fields
                        if(in_array($key, ['workslip_id','item_id','customer_id','drawing','created_at'])) continue;

                        if($value != "" && $value !== 0 && $value !== "0.00") {
                            // Format the Label (e.g., chest_fit -> Chest Fit)
                            $label = ucwords(str_replace("_"," ",$key));
                            ?>
                            <div class="measurement-item">
                                <div class="spec-label"><?php echo $label; ?></div>
                                <div class="spec-value"><?php echo htmlspecialchars($value); ?></div>
                            </div>
                            <?php 
                        } 
                    } 
                } else {
                    echo "<p class='text-danger'>No measurement data found for this item.</p>";
                }
                ?>
            </div>
            
            <div class="mt-5">
    <h5 class="mb-4 border-start border-4 border-primary ps-3">Reference Drawing / Photo</h5>
    <div class="p-3 border rounded bg-light text-center">
        <?php if (!empty($workslip['drawing']) && file_exists('../uploads/drawings/' . $workslip['drawing'])): ?>
            <div class="drawing-container">
                <img src="../uploads/drawings/<?php echo htmlspecialchars($workslip['drawing']); ?>" 
                     alt="Workslip Reference" 
                     class="img-fluid rounded shadow-sm" 
                     style="max-height: 500px; border: 1px solid #ddd;">
                <div class="mt-2 no-print">
                    <a href="../uploads/drawings/<?php echo htmlspecialchars($workslip['drawing']); ?>" 
                       target="_blank" 
                       class="btn btn-sm btn-outline-secondary">
                        <i class="fa-solid fa-magnifying-glass-plus"></i> View Full Size
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="py-5 text-muted">
                <i class="fa-solid fa-image-slash fa-3x mb-3 opacity-25"></i>
                <p class="mb-0 fw-bold">No photo is attached</p>
                <small>No visual reference was uploaded for this item.</small>
            </div>
        <?php endif; ?>
    </div>
</div>

            <?php if(!empty($workslip['special_instructions'])): ?>
            <div class="mt-4 p-4 border rounded bg-warning bg-opacity-10">
                <h6 class="fw-bold"><i class="fa-solid fa-triangle-exclamation text-warning me-2"></i>Special Instructions</h6>
                <p class="mb-0"><?php echo nl2br(htmlspecialchars($workslip['special_instructions'])); ?></p>
            </div>
            <?php endif; ?>
            
            

            <div class="mt-5 pt-4 border-top text-center no-print">
                <p class="text-muted small italic">This document is for internal production use.</p>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>