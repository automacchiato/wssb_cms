<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('../auth/check.php');
include('../config/db.php');

$item_id = isset($_GET['item_id']) ? mysqli_real_escape_string($conn, $_GET['item_id']) : 0;

// Fetch Item and Customer details
$query = "SELECT ii.*, i.invoice_number, i.customer_id, c.customer_name 
          FROM invoice_items ii
          JOIN invoices i ON ii.invoice_id = i.invoice_id
          JOIN customers c ON i.customer_id = c.customer_id
          WHERE ii.invoice_item_id = '$item_id'";

$result = mysqli_query($conn, $query);
$details = mysqli_fetch_assoc($result);

if (!$details) {
    header("Location: ../customers/index.php");
    exit();
}

if(isset($_POST['submit'])) {
    $drawingFile = ""; 
    
    // Handle File Upload
    if (isset($_FILES['drawing']) && $_FILES['drawing']['error'] == 0) {
        $uploadDir = '../uploads/drawings/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        
        $fileExt = pathinfo($_FILES['drawing']['name'], PATHINFO_EXTENSION);
        $fileName = "shirt_" . time() . "_" . $item_id . "." . $fileExt;
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['drawing']['tmp_name'], $targetPath)) {
            $drawingFile = $fileName;
        }
    }

    $stmt = $conn->prepare("INSERT INTO workslip_jacket
                (item_id, manufacturer, salesman_name, cutter_name, tailor_name, gender, special_instructions, previous_invoice_number, back_length, front_length, chest_fit, chest_loose, waist_fit, waist_loose, hip_fit, hip_loose, shoulder, sleeve_length, cuff_length, cross_back, cross_front, vest_length, armhole, back_neck_to_waist, back_neck_to_front_waist, sleeve_button, top_initial, bottom_initial, cleaning_type, drawing)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param(
                "isssssssdddddddddddddddddissss",
                $item_id, //i
                $_POST['manufacturer'], //s
                $_POST['salesman_name'], //s
                $_POST['cutter_name'], //s
                $_POST['tailor_name'], //s
                $_POST['gender'], //s
                $_POST['special_instructions'],
                $_POST['previous_invoice_number'], //s
                $_POST['back_length'], //d
                $_POST['front_length'], ///d
                $_POST['chest_fit'], //d
                $_POST['chest_loose'], //d
                $_POST['waist_fit'], //d
                $_POST['waist_loose'], //d
                $_POST['hip_fit'], //d
                $_POST['hip_loose'], //d
                $_POST['shoulder'], //d
                $_POST['sleeve_length'], //d
                $_POST['cuff_length'], //d
                $_POST['cross_back'], //d
                $_POST['cross_front'], //d
                $_POST['vest_length'], //d
                $_POST['armhole'], //d
                $_POST['back_neck_to_waist'], //d
                $_POST['back_neck_to_front_waist'], //d
                $_POST['sleeve_button'], //i
                $_POST['top_initial'], //s
                $_POST['bottom_initial'], //s
                $_POST['cleaning_type'], //s
                $drawingFile //s
            );
    if ($stmt->execute()) {
        header("Location: ../customers/view.php?id=" . $details['customer_id'] . "&msg=workslip_saved");
        exit();
    } else {
        die("Execute failed: " . $stmt->error);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jacket Workslip - WSSB CMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f0f2f5; }
        .form-section { background: white; padding: 25px; border-radius: 12px; margin-bottom: 25px; border: 1px solid #dee2e6; }
        .section-title { font-size: 1rem; color: #0d6efd; font-weight: 700; text-transform: uppercase; margin-bottom: 20px; display: flex; align-items: center; }
        .section-title i { margin-right: 10px; }
        label { font-size: 0.8rem; font-weight: 700; color: #555; text-transform: uppercase; margin-bottom: 5px; }
        .form-control, .form-select { border-radius: 6px; border: 1px solid #ced4da; padding: 10px; }
        .form-control:focus { box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.1); border-color: #0d6efd; }
        .header-box { background: #fff; padding: 20px; border-radius: 12px; margin-bottom: 25px; border-left: 5px solid #0d6efd; }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" href="../dashboard.php text-primary">WSSB CMS</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="../dashboard.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="../customers/index.php">Customers</a></li>
                    <li class="nav-item"><a class="nav-link" href="../invoices/index.php">Invoices</a></li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link text-danger" href="../auth/logout.php"><i class="fa-solid fa-sign-out"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mb-5">
        <form method="POST" enctype="multipart/form-data">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold mb-0">Jacket Measurement</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="../customers/view.php?id=<?php echo $details['customer_id']; ?>"><?php echo htmlspecialchars($details['customer_name']); ?></a></li>
                            <li class="breadcrumb-item active">New Workslip</li>
                        </ol>
                    </nav>
                </div>
                <a href="../customers/view.php?id=<?php echo $details['customer_id']; ?>" class="btn btn-outline-secondary">
                    <i class="fa-solid fa-xmark"></i> Cancel
                </a>
            </div>

            <div class="header-box shadow-sm d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small">INVOICE ITEM</span>
                    <h5 class="mb-0 fw-bold text-dark">#<?php echo htmlspecialchars($details['invoice_number']); ?> — <?php echo htmlspecialchars($details['item_type']); ?></h5>
                </div>
                <div class="text-end">
                    <span class="text-muted small">DATE</span>
                    <h5 class="mb-0 fw-bold"><?php echo date('d M Y'); ?></h5>
                </div>
            </div>

            <div class="form-section shadow-sm">
                <div class="section-title"><i class="fa-solid fa-user-tie"></i> Personnel & Reference</div>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label>Manufacturer</label>
                        <select name="manufacturer" class="form-select">
                            <option value="Demak Factory">Demak Factory</option>
                            <option value="Fabrica">Fabrica</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>Salesman</label>
                        <select name="salesman_name" class="form-select">
                            <option value="Razak">Razak</option>
                            <option value="Hamidah">Hamidah</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>Cutter</label>
                        <input name="cutter_name" class="form-control" placeholder="Enter name">
                    </div>
                    <div class="col-md-3">
                        <label>Tailor</label>
                        <input name="tailor_name" class="form-control" placeholder="Enter name">
                    </div>
                    <div class="col-md-4">
                        <label>Gender</label>
                        <select name="gender" class="form-select">
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label>Prev. Invoice #</label>
                        <input name="previous_invoice_number" class="form-control" placeholder="e.g. 8892">
                    </div>
                </div>
            </div>

            <div class="form-section shadow-sm">
                <div class="section-title"><i class="fa-solid fa-ruler-combined"></i> Core Measurements (Inches)</div>
                <div class="row g-3">
                    <div class="col-md-2 col-6"><label>Back L.</label><input type="number" step="0.01" name="back_length" class="form-control"></div>
                    <div class="col-md-2 col-6"><label>Front L.</label><input type="number" step="0.01" name="front_length" class="form-control"></div>
                    <div class="col-md-2 col-6"><label>Chest (Fit)</label><input type="number" step="0.01" name="chest_fit" class="form-control"></div>
                    <div class="col-md-2 col-6"><label>Chest (Loose)</label><input type="number" step="0.01" name="chest_loose" class="form-control"></div>
                    <div class="col-md-2 col-6"><label>Waist (Fit)</label><input type="number" step="0.01" name="waist_fit" class="form-control"></div>
                    <div class="col-md-2 col-6"><label>Waist (Loose)</label><input type="number" step="0.01" name="waist_loose" class="form-control"></div>
                    
                    <div class="col-md-2 col-6"><label>Hip (Fit)</label><input type="number" step="0.01" name="hip_fit" class="form-control"></div>
                    <div class="col-md-2 col-6"><label>Hip (Loose)</label><input type="number" step="0.01" name="hip_loose" class="form-control"></div>
                    <div class="col-md-2 col-6"><label>Shoulder</label><input type="number" step="0.01" name="shoulder" class="form-control"></div>
                    <div class="col-md-2 col-6"><label>Sleeve L.</label><input type="number" step="0.01" name="sleeve_length" class="form-control"></div>
                    <div class="col-md-2 col-6"><label>Cuff L.</label><input type="number" step="0.01" name="cuff_length" class="form-control"></div>
                    <div class="col-md-2 col-6"><label>Armhole</label><input type="number" step="0.01" name="armhole" class="form-control"></div>

                </div>
            </div>

            <div class="row g-4">
                <div class="col-md-6">
                    <div class="form-section shadow-sm h-100">
                        <div class="section-title"><i class="fa-solid fa-shirt"></i> Overall Body</div>
                        <div class="row g-3">
                            <div class="col-4"><label>Vest Length</label>
                                <input type="number" step="0.01" name="vest_length" class="form-control">
                            </div>
                            <div class="col-4"><label>Cross Back</label>
                                <input type="number" step="0.01" name="cross_back" class="form-control">
                            </div>
                            <div class="col-4"><label>Cross Front</label>
                                <input type="number" step="0.01" name="cross_front" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="form-section shadow-sm h-100">
                        <div class="section-title"><i class="fa-solid fa-person"></i>Sleeve</div>
                        <div class="row g-3">
                            <div class="col-4"><label>Back Neck to Waist</label>
                                <input type="number" step="0.01" name="back_neck_to_waist" class="form-control">
                            </div>
                            <div class="col-4"><label>Back Neck to Front Waist</label>
                                <input type="number" step="0.01" name="back_neck_to_front_waist" class="form-control">
                            </div>
                            <div class="col-6"><label>Sleeve Button</label>
                                <select name="sleeve_button" class="form-select">
                                    <option value="Yes">Yes</option>
                                    <option value="No">No</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-section shadow-sm">
    <div class="section-title"><i class="fa-solid fa-camera"></i> Sketch or Photo Reference</div>
    <div class="row align-items-center">
        <div class="col-md-6">
            <p class="text-muted small">Capture a sketch, fabric sample, or existing garment reference.</p>
            <div class="d-grid gap-2 d-md-block">
                <input type="file" name="drawing" id="drawingInput" class="form-control" accept="image/*" capture="environment" style="display: none;" onchange="previewImage(this)">
                
                <button type="button" class="btn btn-outline-primary btn-lg" onclick="document.getElementById('drawingInput').click()">
                    <i class="fa-solid fa-camera"></i> Take Photo / Upload
                </button>
            </div>
        </div>
        <div class="col-md-6 text-center">
            <div id="imagePreviewContainer" class="mt-3 mt-md-0" style="display: none;">
                <img id="preview" src="#" alt="Preview" style="max-height: 200px; border-radius: 8px; border: 2px dashed #ccc;">
                <p class="small text-success mt-1"><i class="fa-solid fa-check-circle"></i> Image attached</p>
            </div>
        </div>
    </div>
</div>

            <div class="form-section shadow-sm mt-4">
                <div class="section-title"><i class="fa-solid fa-comment-dots"></i> Instructions & Finishing</div>
                <div class="row g-3">
                    <div class="col-md-8">
                        <label>Special Instructions</label>
                        <textarea name="special_instructions" class="form-control" rows="4" placeholder="Any special requests..."></textarea>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label>Cleaning Type</label>
                            <select name="cleaning_type" class="form-select text-danger fw-bold">
                                <option value="No Restriction">No Restriction</option>
                                <option value="Dry Clean Only">Dry Clean Only</option>
                                <option value="Hand Wash Only">Hand Wash Only</option>
                            </select>
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <label>Top Initial</label>
                                <input name="top_initial" class="form-control">
                            </div>
                            <div class="col-6">
                                <label>Bottom Init.</label>
                                <input name="bottom_initial" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="sticky-bottom bg-light p-3 border-top d-flex justify-content-end gap-2 shadow">
                <button type="submit" name="submit" class="btn btn-primary btn-lg px-5">
                    <i class="fa-solid fa-floppy-disk"></i> Save Workslip
                </button>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
function previewImage(input) {
    const preview = document.getElementById('preview');
    const container = document.getElementById('imagePreviewContainer');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.src = e.target.result;
            container.style.display = 'block';
        }
        
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
</body>
</html>