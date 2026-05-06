<?php
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

if (isset($_POST['submit'])) {
    $drawingFile = ""; // Logic for file upload could go here

    if (!empty($_POST['canvas_image'])) {
        $img = $_POST['canvas_image'];
        $img = str_replace('data:image/png;base64,', '', $img);
        $img = str_replace(' ', '+', $img);

        $data = base64_decode($img);

        $fileName = "shirt_canvas_" . time() . "_" . $item_id . ".png";
        $filePath = '../uploads/drawings/' . $fileName;

        file_put_contents($filePath, $data);

        $drawingFile = $fileName;
    }

    // Handle File Upload
    if (isset($_FILES['drawing']) && $_FILES['drawing']['error'] == 0) {
        $uploadDir = '../uploads/drawings/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $fileExt = pathinfo($_FILES['drawing']['name'], PATHINFO_EXTENSION);
        $fileName = "trousers_" . time() . "_" . $item_id . "." . $fileExt;
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['drawing']['tmp_name'], $targetPath)) {
            $drawingFile = $fileName;
        }
    }

    $stmt = $conn->prepare("INSERT INTO workslip_trousers
                (item_id, manufacturer, salesman_name, cutter_name, tailor_name, gender, special_instructions, previous_invoice_number, fly_hs, side_pocket_hs, side_seams_hs, pocket_pull, pleat_num, waist_fit, waist_loose, hip_fit, hip_loose, top_hip_fit, top_hip_loose, length, thigh, knee, bottom, crotch, position_on_waist, corpulent, seating_type, turn_up, turn_up_length, inside_pocket_num, inside_pocket_width, inside_pocket_length, loop_num, loop_width, loop_length, right_pocket, left_pocket, lining_type, bottom_initial, cleaning_type, drawing)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param(
        "issssssssssssdddddddddddsdssdsddsddssssss",
        $item_id,
        $_POST['manufacturer'],
        $_POST['salesman_name'],
        $_POST['cutter_name'],
        $_POST['tailor_name'],
        $_POST['gender'],
        $_POST['special_instructions'],
        $_POST['previous_invoice_number'],
        $_POST['fly_hs'],
        $_POST['side_pocket_hs'],
        $_POST['side_seams_hs'],
        $_POST['pocket_pull'],
        $_POST['pleat_num'],
        $_POST['waist_fit'],
        $_POST['waist_loose'],
        $_POST['hip_fit'],
        $_POST['hip_loose'],
        $_POST['top_hip_fit'],
        $_POST['top_hip_loose'],
        $_POST['length'],
        $_POST['thigh'],
        $_POST['knee'],
        $_POST['bottom'],
        $_POST['crotch'],
        $_POST['position_on_waist'],
        $_POST['corpulent'],
        $_POST['seating_type'],
        $_POST['turn_up'],
        $_POST['turn_up_length'],
        $_POST['inside_pocket_num'],
        $_POST['inside_pocket_width'],
        $_POST['inside_pocket_length'],
        $_POST['loop_num'],
        $_POST['loop_width'],
        $_POST['loop_length'],
        $_POST['right_pocket'],
        $_POST['left_pocket'],
        $_POST['lining_type'],
        $_POST['bottom_initial'],
        $_POST['cleaning_type'],
        $drawingFile
    );

    if ($stmt->execute()) {
        header("Location: ../customers/view.php?id=" . $details['customer_id'] . "&msg=workslip_saved");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trousers Workslip - WSSB CMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f0f2f5;
        }

        .form-section {
            background: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            border: 1px solid #dee2e6;
        }

        .section-title {
            font-size: 1rem;
            color: #0d6efd;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }

        .section-title i {
            margin-right: 10px;
        }

        label {
            font-size: 0.8rem;
            font-weight: 700;
            color: #555;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .form-control,
        .form-select {
            border-radius: 6px;
            border: 1px solid #ced4da;
            padding: 10px;
        }

        .form-control:focus {
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.1);
            border-color: #0d6efd;
        }

        .header-box {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            border-left: 5px solid #0d6efd;
        }

        #drawingCanvas {
            touch-action: none;
            /* 🔥 disables scroll/zoom gestures */
        }

        #canvasWrapper {
            touch-action: none;
        }
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
                    <h2 class="fw-bold mb-0">Trousers Measurement</h2>
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
                <!--<div class="section-title"><i class="fa-solid fa-user-tie"></i> Personnel & Reference</div>-->
                <div class="row g-3">
                    <div class="col-md-4">
                        <label>placeholder</label>
                        <select name="manufacturer" class="form-select" disabled="">
                            <option value="Demak Factory">Demak Factory</option>
                            <option value="Fabrica">Fabrica</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label>Manufacturer</label>
                        <select name="manufacturer" class="form-select">
                            <option value="Demak Factory">Demak Factory</option>
                            <option value="Fabrica">Fabrica</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <h3 class="align-items-center text-center justify-content-between m-4">MUST</h3>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label>Fabric Name</label>
                        <input name="fabric_name" class="form-control" value="<?php echo htmlspecialchars($details['fabric_name']) ?>" disabled>
                    </div>
                    <div class="col-md-4">
                        <label>Salesman</label>
                        <select name="salesman_name" class="form-select">
                            <option value="Razak">Razak</option>
                            <option value="Hamidah">Hamidah</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label>Delivery Date</label>
                        <input name="delivery_date" class="form-control" value="<?php echo htmlspecialchars($details['delivery_date']) ?>" disabled>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-2">
                        <label>Cutter</label>
                        <input name="cutter_name" class="form-control" placeholder="Enter name">
                    </div>
                    <div class="col-md-2">
                        <label>Tailor</label>
                        <input name="tailor_name" class="form-control" placeholder="Enter name">
                    </div>
                    <div class="col-md-4">
                        <label>Quantity</label>
                        <input name="quantity" class="form-control" value="<?php echo htmlspecialchars($details['quantity']) ?>" disabled>
                    </div>
                    <div class="col-md-4">
                        <label>Fitting Date</label>
                        <input name="quantity" class="form-control" value="<?php echo htmlspecialchars($details['fitting_date']) ?>" disabled>
                    </div>
                    <div class="col-md-2">
                        <label>Gender</label>
                        <select name="gender" class="form-select">
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label>Special Instructions</label>
                        <textarea name="special_instructions" class="form-control" rows="4" placeholder="Any special requests..."></textarea>
                    </div>
                    <div class="col-md-2">
                        <label>Fabric Dir.</label>
                        <select name="fabric_direction" class="form-select" rows="4">
                            <option value="No Direction">No Direction</option>
                            <option value="Vertical">Vertical</option>
                            <option value="Horizontal">Horizontal</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="form-section shadow-sm">
                <div class="section-title"><i class="fa-solid fa-ruler-combined"></i> Core Measurements (Inches)</div>
                <div class="row g-3">
                    <div class="col-md-2 col-6">
                        <label>Fly Stitch</label>
                        <select name="fly_hs" class="form-control" required>
                            <option value="Yes">Yes</option>
                            <option value="No" selected>No</option>
                        </select>
                    </div>
                    <div class="col-md-2 col-6">
                        <label>Side Pocket Hidden Stitch</label>
                        <select name="side_pocket_hs" class="form-control" required>
                            <option value="Yes">Yes</option>
                            <option value="No" selected>No</option>
                        </select>
                    </div>
                    <div class="col-md-2 col-6">
                        <label>Side Seams Hidden Stitch</label>
                        <select name="side_seams_hs" class="form-control" required>
                            <option value="Yes">Yes</option>
                            <option value="No" selected>No</option>
                        </select>
                    </div>
                    <div class="col-md-2 col-6">
                        <label>Pocket Pull Stitch</label>
                        <select name="pocket_pull" class="form-control" required>
                            <option value="Yes">Yes</option>
                            <option value="No" selected>No</option>
                        </select>
                    </div>
                    <div class="col-md-2 col-6">
                        <label>Pleat Number</label>
                        <select name="pleat_num" class="form-control" required>
                            <option value="0" selected>0</option>
                            <option value="1">1</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                        </select>
                    </div>

                    <div class="col-md-2 col-6">
                        <label>Waist (Fit)</label>
                        <input type="number" step="0.01" name="waist_fit" class="form-control">
                    </div>
                    <div class="col-md-2 col-6">
                        <label>Waist (Loose)</label>
                        <input type="number" step="0.01" name="waist_loose" class="form-control">
                    </div>
                    <div class="col-md-2 col-6">
                        <label>Hip (Fit)</label>
                        <input type="number" step="0.01" name="hip_fit" class="form-control">
                    </div>
                    <div class="col-md-2 col-6">
                        <label>Hip (Loose)</label>
                        <input type="number" step="0.01" name="hip_loose" class="form-control">
                    </div>
                    <div class="col-md-2 col-6">
                        <label>Top Hip (Fit)</label>
                        <input type="number" step="0.01" name="top_hip_fit" class="form-control">
                    </div>
                    <div class="col-md-2 col-6">
                        <label>Top Hip (Loose)</label>
                        <input type="number" step="0.01" name="top_hip_loose" class="form-control">
                    </div>

                    <div class="col-md-2 col-6">
                        <label>Length</label>
                        <input type="number" step="0.01" name="length" class="form-control">
                    </div>
                    <div class="col-md-2 col-6">
                        <label>Thigh</label>
                        <input type="number" step="0.01" name="thigh" class="form-control">
                    </div>
                    <div class="col-md-2 col-6">
                        <label>Knee</label>
                        <input type="number" step="0.01" name="knee" class="form-control">
                    </div>
                    <div class="col-md-2 col-6">
                        <label>Bottom</label>
                        <input type="number" step="0.01" name="bottom" class="form-control">
                    </div>
                    <div class="col-md-2 col-6">
                        <label>Crotch</label>
                        <input type="number" step="0.01" name="crotch" class="form-control">
                    </div>
                    <div class="col-md-2 col-6">
                        <label>Corpulent</label>
                        <input type="number" step="0.01" name="corpulent" class="form-control">
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-md-6">
                    <div class="form-section shadow-sm h-100">
                        <div class="section-title"><i class="fa-solid fa-shirt"></i>Pocket Details</div>
                        <div class="row g-3">
                            <div class="col-4"><label>Inside Pocket Num</label>
                                <input type="number" step="0.01" name="inside_pocket_num" class="form-control">
                            </div>
                            <div class="col-4"><label>Inside Pocket Width</label>
                                <input type="number" step="0.01" name="inside_pocket_width" class="form-control">
                            </div>
                            <div class="col-4"><label>Inside Pocket Length</label>
                                <input type="number" step="0.01" name="inside_pocket_length" class="form-control">
                            </div>

                            <div class="col-6"><label>Right Pocket</label>
                                <select name="right_pocket" class="form-control">
                                    <option value="Yes" selected>Yes</option>
                                    <option value="No">No</option>
                                </select>
                            </div>
                            <div class="col-6"><label>Left Pocket</label>
                                <select name="left_pocket" class="form-control">
                                    <option value="Yes" selected>Yes</option>
                                    <option value="No">No</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-section shadow-sm h-100">
                        <div class="section-title"><i class="fa-solid fa-person"></i>Body Posture</div>
                        <div class="row g-3">
                            <div class="col-6"><label>Position on Waist</label>
                                <select name="position_on_waist" class="form-control">
                                    <option value="Not Stated" disabled selected>Not Stated</option>
                                    <option value="Front High">Front High</option>
                                    <option value="Front Cut Low">Front Cut Low</option>
                                </select>
                            </div>
                            <div class="col-6"><label>Seating Type</label>
                                <select name="position_on_waist" class="form-control">
                                    <option value="Not Stated" disabled selected>Not Stated</option>
                                    <option value="Front High">Front High</option>
                                    <option value="Front Cut Low">Front Cut Low</option>
                                </select>
                            </div>
                            <div class="col-6"><label>Turn Up</label>
                                <select name="turn_up" class="form-select">
                                    <option value="Yes">Yes</option>
                                    <option value="No">No</option>
                                </select>
                            </div>
                            <div class="col-4"><label>Turn Up Length</label>
                                <input type="number" step="0.01" name="turn_up_length" class="form-control">
                            </div>
                            <div class="col-4"><label>Loop Number</label>
                                <input type="number" step="0.01" name="loop_num" class="form-control">
                            </div>
                            <div class="col-4"><label>Loop Width</label>
                                <input type="number" step="0.01" name="loop_width" class="form-control">
                            </div>
                            <div class="col-4"><label>Loop Length</label>
                                <input type="number" step="0.01" name="loop_length" class="form-control">
                            </div>
                            <div class="col-6"><label>Lining Type</label>
                                <select name="lining_type" class="form-control">
                                    <option value="Not Stated" disabled selected>Not Stated</option>
                                    <option value="Half Lined Front Only">Half Lined Front Only</option>
                                    <option value="Front Back 1/2 Lining">Front Back 1/2 Lining</option>
                                    <option value="Front Full Length Lined">Front Full Length Lined</option>
                                    <option value="Trousers Full Lined">Trousers Full Lined</option>
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
                            <div class="col-6"><label>Bottom Init.</label><input name="bottom_initial" class="form-control"></div>
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