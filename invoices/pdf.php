<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require('../fpdf186/fpdf.php');

$host = "127.0.0.1:3306";
$user = "u647109978_admin";
$pass = "Mocha98@";
$dbname = "u647109978_wssb";

$conn = new mysqli($host, $user, $pass, $dbname);

$invoice_id = intval($_GET['id']);

// Fetch invoice + customer
$invoice_sql = "
    SELECT i.*, c.*
    FROM invoices i
    JOIN customers c ON i.customer_id = c.customer_id
    WHERE i.invoice_id = $invoice_id;
";
$invoice = $conn->query($invoice_sql)->fetch_assoc();

// Fetch items
$item_sql = "
    SELECT it.invoice_item_id, it.item_type, it.quantity, it.fabric_code, it.fabric_name, it.fabric_color, it.fabric_usage, it.amount 
    FROM invoice_items it
    WHERE it.invoice_id = $invoice_id
";
$items = $conn->query($item_sql);

// ---------------- EXIF Orientation Fix ----------------
function fixImageOrientation($imagePath) {
    // Only process JPEG files (EXIF only exists on JPEG)
    $ext = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg'])) {
        return $imagePath;
    }

    if (!function_exists('exif_read_data')) {
        return $imagePath; // EXIF extension not available
    }

    $exif = @exif_read_data($imagePath);
    $orientation = $exif['Orientation'] ?? 1;

    if ($orientation === 1) {
        return $imagePath; // Already correct, no change needed
    }

    $img = imagecreatefromjpeg($imagePath);

    switch ($orientation) {
        case 3: $img = imagerotate($img, 180, 0); break;
        case 6: $img = imagerotate($img, -90, 0); break;  // Most common: phone portrait
        case 8: $img = imagerotate($img, 90, 0);  break;
    }

    // Save to a temp file
    $tmpPath = sys_get_temp_dir() . '/' . uniqid('drawing_', true) . '.jpg';
    imagejpeg($img, $tmpPath, 95);
    imagedestroy($img);

    return $tmpPath;
}

// ---------------- Page 1: Invoice ----------------
class PDF extends FPDF
{
    function Header()
    {
        // Company Logo
        $this->Image('../assets/logo2.png', 10, 10, 30); // x, y, width
    }
}

$pdf = new PDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'I', 8);
$pdf->SetXY(10, 25);
$pdf->Cell(50, 5, "[Dimiliki oleh Malmo Clothier (K) Sdn Bhd - 1364467-K]", 0, 1, "L");
$pdf->SetFont('Arial', '', 8);
// Address (using MultiCell)
$pdf->SetX(10);
$pdf->MultiCell(
    0,
    3.5,
    "LOT C31, ARAS 2, MAJMA' TUANKU ABDUL HALIM,\n" .
        "MU'AZAM SHAH, LORONG P.RAMLEE 5,\n" .
        "93400 KUCHING, SARAWAK.\n" .
        "TEL: 082-239278 | H/P: 017-8203560 / 012-8079091\n" .
        "Email: malmo6870k@gmail.com",
    0,
    'L'
);
$pdf->SetFont('Arial', 'B', 15);

// --- Invoice details (top right) ---
$pdf->SetXY(150, 10);
$pdf->Cell(50, 5, "Invoice No: " . $invoice['invoice_number'], 0, 1, "R");
$pdf->Ln();
$pdf->SetFont('Arial', '', 10);
$pdf->SetX(150);
$pdf->Cell(50, 5, "Order Date: " . $invoice['order_date'], 1, 1, "R");
$pdf->SetX(150);
$pdf->Cell(50, 5, "Fitting Date: " . $invoice['fitting_date'], 1, 1, "R");
$pdf->SetX(150);
$pdf->Cell(50, 5, "Delivery Date: " . $invoice['delivery_date'], 1, 1, "R");

$pdf->Ln(20);

// --- Customer Table ---
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, "Customer Details", 0, 1, 'L');

$pdf->SetFont('Arial', '', 11);
$pdf->Cell(30, 10, "Name", 1);
$pdf->Cell(160, 10, $invoice['customer_name'], 1, 1);

$pdf->Cell(30, 10, "Address", 1);
$pdf->Cell(160, 10, $invoice['customer_address'], 1, 1);

$pdf->Cell(30, 10, "Email", 1);
$pdf->Cell(160, 10, $invoice['customer_email'], 1, 1);

$pdf->Cell(30, 10, "Telephone", 1);
$pdf->Cell(160, 10, $invoice['customer_phone'], 1, 1);

$pdf->Ln(10);

// --- Items Table ---
$pdf->SetFont('Arial', 'B', 11);
$headers = ["Qty", "Item Type", "Fabric Code", "Fabric Name", "Fabric Color", "Usage (m)", "Amount"];
$widths  = [10, 25, 25, 50, 25, 25, 30];

foreach ($headers as $i => $col) {
    $pdf->Cell($widths[$i], 10, $col, 1, 0, 'C');
}
$pdf->Ln();

$pdf->SetFont('Arial', '', 11);
while ($row = $items->fetch_assoc()) {
    $pdf->Cell($widths[0], 10, $row['quantity'], 1, 0, "C");
    $pdf->Cell($widths[1], 10, $row['item_type'], 1, 0, "C");
    $pdf->Cell($widths[2], 10, $row['fabric_code'], 1, 0, "C");
    $pdf->Cell($widths[3], 10, $row['fabric_name'], 1, 0, "C");
    $pdf->Cell($widths[4], 10, $row['fabric_color'], 1, 0, "C");
    $pdf->Cell($widths[5], 10, $row['fabric_usage'], 1, 0, "C");
    $pdf->Cell($widths[6], 10, $row['amount'], 1, 0, "R");
    $pdf->Ln();
}

$emptyRows = 5;
for ($i = 0; $i < $emptyRows; $i++) {
    foreach ($widths as $w) {
        $pdf->Cell($w, 10, "", 1, 0);
    }
    $pdf->Ln();
}

// --- Totals from invoices ---
$pdf->SetX(110);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(60, 10, "Total Amount", 1);
$pdf->Cell(30, 10, $invoice['total_amount'], 1, 1, "R");

$pdf->SetX(110);
$pdf->Cell(60, 10, "Deposit", 1);
$pdf->Cell(30, 10, $invoice['deposit_amount'], 1, 1, "R");

$pdf->SetX(110);
$pdf->Cell(60, 10, "Balance", 1);
$pdf->Cell(30, 10, $invoice['balance_amount'], 1, 1, "R");

$pdf->SetX(110);
$pdf->Cell(60, 10, "Additional Deposit", 1);
$pdf->Cell(30, 10, $invoice['additional_deposit'], 1, 1, "R");

$pdf->SetX(110);
$pdf->Cell(60, 10, "Final Balance", 1);
$pdf->Cell(30, 10, $invoice['additional_balance'], 1, 1, "R");

// ---------------- Page 2: Workslip ----------------
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);
$pdf->SetXY(-50, 10); // 50 is an offset from right edge
$pdf->Cell(40, 10, "Workslip", 0, 0, 'R');
$pdf->Ln(20);

$items->data_seek(0);
while ($row = $items->fetch_assoc()) {
    $pdf->SetFont('Arial', '', 12);

    $item_id = $row['invoice_item_id'];

    // Fetch details from correct workslip table
    switch (strtoupper($row['item_type'])) {
        case 'SHIRT':
            $sql = "SELECT * FROM workslip_shirts WHERE item_id = $item_id";
            $work = $conn->query($sql)->fetch_assoc();

            //Line 1
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(38, 10, "Invoice No.", 1);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(38, 10, $invoice['invoice_number'], 1);
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(38, 10, "Manufacturer", 1);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(38, 10, $work['manufacturer'], 1);
            $pdf->SetFont('Arial', 'B', 14);
            $pdf->Cell(38, 10, "MUST", 1, 1, "C");

            //Line 2
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(31.7, 10, "Salesman", 1);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(31.7, 10, $work['salesman_name'], 1);
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(31.7, 10, "Cutter", 1);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(31.7, 10, $work['cutter_name'], 1);
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(31.7, 10, "Tailor", 1);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(31.6, 10, $work['tailor_name'], 1, 1);

            //Line 3
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(47.5, 10, "Fitting Date", 1);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(47.5, 10, $invoice['fitting_date'], 1);
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(47.5, 10, "Deliver Date", 1);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(47.5, 10, $invoice['delivery_date'], 1, 1);

            //Line 4
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(47.5, 10, "Gender", 1);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(47.5, 10, $work['gender'], 1);
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(47.5, 10, "Fabric Direction", 1);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(47.5, 10, $work['fabric_direction'], 1, 1);

            //Line 5
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(30, 10, "Collar", 1);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(60, 10, $work['collar_length'], 1);
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(30, 10, "Top Initial", 1, 0, "C");
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(70, 10, $work['top_initial'], 1, 1);

            //Line 6
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(30, 10, "Back", 1);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(60, 10, $work['back_length'], 1);
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(30, 10, "Collar Design", 1, 0, "C");
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(70, 10, "Collar Specification", 1, 1, "C");

            //Line 7
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(30, 10, "Front", 1);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(60, 10, $work['front_length'], 1);
            $pdf->Cell(30, 10, $work['collar_design'], 1, 0, "C");
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(17.5, 10, "Width", 1, 0, "C");
            $pdf->Cell(17.5, 10, "Height", 1, 0, "C");
            $pdf->Cell(17.5, 10, "Gap", 1, 0, "C");
            $pdf->Cell(17.5, 10, "Meet", 1, 1, "C");

            //Line 8
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(30, 10, "Chest", 1);
            $pdf->Cell(15, 10, "Fit", 1, 0, "C");
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(15, 10, $work['chest_fit'], 1, 0, "C");
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(15, 10, "Loose", 1, 0, "C");
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(15, 10, $work['chest_loose'], 1, 0, "C");
            $pdf->Cell(30, 10, "", 1, 0, "C");
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(17.5, 10, $work['collar_width'], 1, 0, "C");
            $pdf->Cell(17.5, 10, $work['collar_height'], 1, 0, "C");
            $pdf->Cell(17.5, 10, $work['collar_gap'], 1, 0, "C");
            $pdf->Cell(17.5, 10, $work['collar_meet'], 1, 1, "C");

            //Line 9
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(30, 10, "Waist", 1);
            $pdf->Cell(15, 10, "Fit", 1, 0, "C");
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(15, 10, $work['waist_fit'], 1, 0, "C");
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(15, 10, "Loose", 1, 0, "C");
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(15, 10, $work['waist_loose'], 1, 1, "C");

            //Line 10
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(30, 10, "Hip", 1);
            $pdf->Cell(15, 10, "Fit", 1, 0, "C");
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(15, 10, $work['hip_fit'], 1, 0, "C");
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(15, 10, "Loose", 1, 0, "C");
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(15, 10, $work['hip_loose'], 1, 1, "C");

            //Line 11
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(30, 10, "Shoulder", 1);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(60, 10, $work['shoulder'], 1, 1);

            //Line 12
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(30, 10, "Sleeve", 1);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(60, 10, $work['sleeve_length'], 1, 1);

            //Line 13
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(30, 10, "Arm", 1);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(60, 10, $work['arm_length'], 1, 1);

            //Line 14
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(30, 10, "Elbow", 1);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(60, 10, $work['elbow_length'], 1, 1);

            //Line 15
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(30, 10, "Cuff", 1);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(60, 10, $work['cuff_length'], 1, 1);

            //Line 16
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(30, 10, "Armhole", 1);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(60, 10, $work['armhole_length'], 1, 1);

            //Line 17
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(30, 10, "Erect", 1);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(60, 10, $work['erect'], 1, 1);

            //Line 18
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(30, 10, "Hunch", 1);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(60, 10, $work['hunch'], 1, 1);

            //Line 19
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(30, 10, "Shoulder Type", 1);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(60, 10, $work['shoulder_type'], 1);
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(25, 10, "Placket Type", 1, 0, "C");
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(25, 10, $work['placket_type'], 1, 0, "C");
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(25, 10, "Cuff Type", 1, 0, "C");
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(25, 10, $work['cuff_type'], 1, 1, "C");

            //Line 20
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(30, 10, "Corpulent", 1);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(60, 10, $work['corpulent'], 1);
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(30, 10, "Front Cutting", 1, 0, "C");
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(70, 10, $work['front_cutting'], 1, 1);

            //Line 21
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(80, 10, "Bottom Initial", 1, 0, "C");
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(110, 10, $work['bottom_initial'], 1, 1);
            $pdf->Ln(5);

            //Line 22
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(30, 10, "Fabric Code", 1, 0, "C");
            $pdf->Cell(60, 10, "Fabric Name", 1, 0, "C");
            $pdf->Cell(30, 10, "Fabric Color", 1, 0, "C");
            $pdf->Cell(35, 10, "Fabric Usage (m)", 1, 0, "C");
            $pdf->Cell(35, 10, "Cleaning Type", 1, 1, "C");

            //Line 23
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(30, 10, $row['fabric_code'], 1, 0, "C");
            $pdf->Cell(60, 10, $row['fabric_name'], 1, 0, "C");
            $pdf->Cell(30, 10, $row['fabric_color'], 1, 0, "C");
            $pdf->Cell(35, 10, $row['fabric_usage'], 1, 0, "C");
            $pdf->Cell(35, 10, $work['cleaning_type'], 1, 1, "C");

            // Drawing (EXIF-corrected)
            $drawingPath = fixImageOrientation(__DIR__ . "/../uploads/drawings/" . $work['drawing']);
            $pdf->Image($drawingPath, 110, 125, 80, 80);

            // Extra notes / signatures
            $pdf->Cell(0, 8, "Special Instructions: " . ($work['special_instructions'] ?? ""), 0, 1);
            break;

        case 'TROUSERS':
            $sql = "SELECT * FROM workslip_trousers WHERE item_id = $item_id";
            $work = $conn->query($sql)->fetch_assoc();

            //Line 1
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(38, 8, "Invoice No.", 1);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(38, 8, $invoice['invoice_number'], 1);
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(38, 8, "Manufacturer", 1);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(38, 8, $work['manufacturer'], 1);
            $pdf->SetFont('Arial', 'B', 14);
            $pdf->Cell(38, 8, "MUST", 1, 1, "C");

            //Line 2
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(31.7, 8, "Salesman", 1);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(31.7, 8, $work['salesman_name'], 1);
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(31.7, 8, "Cutter", 1);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(31.7, 8, $work['cutter_name'], 1);
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(31.7, 8, "Tailor", 1);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(31.6, 8, $work['tailor_name'], 1, 1);

            //Line 3
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(47.5, 8, "Fitting Date", 1);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(47.5, 8, $invoice['fitting_date'], 1);
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(47.5, 8, "Deliver Date", 1);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(47.5, 8, $invoice['delivery_date'], 1, 1);

            //Line 4
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(47.5, 8, "Gender", 1);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(47.5, 8, $work['gender'], 1);
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(47.5, 8, "", 1);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(47.5, 8, "", 1, 1);

            //Line 5
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(70, 8, "Hand Stitch", 1, 0, "C");
            $pdf->Cell(25, 8, "Pleats", 1, 0, "C");
            $pdf->Cell(47.5, 8, "Inside Pocket", 1, 0, "C");
            $pdf->Cell(47.5, 8, "Loop", 1, 1, "C");

            //Line 6
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->Cell(17.5, 8, "Fly", 1, 0, "C");
            $pdf->Cell(17.5, 8, "Side Pocket", 1, 0, "C");
            $pdf->Cell(17.5, 8, "Side Seams", 1, 0, "C");
            $pdf->Cell(17.5, 8, "Pocket Pull", 1, 0, "C");
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(25, 8, $work['pleat_num'], 1, 0, "C");
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(15.8, 8, "No.", 1, 0, "C");
            $pdf->Cell(15.8, 8, "Width", 1, 0, "C");
            $pdf->Cell(15.8, 8, "Length", 1, 0, "C");
            $pdf->Cell(15.8, 8, "No.", 1, 0, "C");
            $pdf->Cell(15.8, 8, "Width", 1, 0, "C");
            $pdf->Cell(15.8, 8, "Length", 1, 1, "C");

            //Line 7
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(17.5, 8, $work['fly_hs'], 1, 0, "C");
            $pdf->Cell(17.5, 8, $work['side_pocket_hs'], 1, 0, "C");
            $pdf->Cell(17.5, 8, $work['side_seams_hs'], 1, 0, "C");
            $pdf->Cell(17.5, 8, $work['pocket_pull'], 1, 0, "C");
            $pdf->Cell(25, 8, "", 1, 0, "C");
            $pdf->Cell(15.8, 8, $work['inside_pocket_num'], 1, 0, "C");
            $pdf->Cell(15.8, 8, $work['inside_pocket_width'], 1, 0, "C");
            $pdf->Cell(15.8, 8, $work['inside_pocket_length'], 1, 0, "C");
            $pdf->Cell(15.8, 8, $work['loop_num'], 1, 0, "C");
            $pdf->Cell(15.8, 8, $work['loop_width'], 1, 0, "C");
            $pdf->Cell(15.8, 8, $work['loop_length'], 1, 1, "C");

            //Line 8
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(30, 8, "Waist", 1);
            $pdf->Cell(15, 8, "Fit", 1, 0, "C");
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(15, 8, $work['waist_fit'], 1, 0, "C");
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(15, 8, "Loose", 1, 0, "C");
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(15, 8, $work['waist_loose'], 1, 1, "C");

            //Line 9
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(30, 8, "Hip", 1);
            $pdf->Cell(15, 8, "Fit", 1, 0, "C");
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(15, 8, $work['hip_fit'], 1, 0, "C");
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(15, 8, "Loose", 1, 0, "C");
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(15, 8, $work['hip_loose'], 1, 1, "C");

            //Line 10
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(30, 8, "Top Hip", 1);
            $pdf->Cell(15, 8, "Fit", 1, 0, "C");
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(15, 8, $work['top_hip_fit'], 1, 0, "C");
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(15, 8, "Loose", 1, 0, "C");
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(15, 8, $work['top_hip_loose'], 1, 1, "C");

            //Line 11
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(30, 8, "Length", 1);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(60, 8, $work['length'], 1, 1);

            //Line 12
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(30, 8, "Thigh", 1);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(60, 8, $work['thigh'], 1, 1);

            //Line 13
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(30, 8, "Knee", 1);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(60, 8, $work['knee'], 1, 1);

            //Line 14
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(30, 8, "Bottom", 1);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(60, 8, $work['bottom'], 1, 1);

            //Line 15
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(30, 8, "Crotch", 1);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(60, 8, $work['crotch'], 1, 1);

            //Line 16
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->Cell(30, 8, "Position on Waist", 1);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(60, 8, $work['position_on_waist'], 1, 1);

            //Line 17
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(30, 8, "Corpulent", 1);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(60, 8, $work['corpulent'], 1, 1);

            //Line 18
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(30, 8, "Seating Type", 1);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(60, 8, $work['seating_type'], 1, 1);

            //Line 19
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(30, 8, "Turn Up", 1);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(60, 8, $work['turn_up'], 1, 1);

            //Line 20
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->Cell(30, 8, "Turn Up Length", 1);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(60, 8, $work['turn_up_length'], 1, 1);

            //Line 21
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(30, 8, "Lining Type", 1);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(60, 8, $work['lining_type'], 1);
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(25, 8, "Right Pocket", 1, 0, "C");
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(25, 8, $work['right_pocket'], 1, 0, "C");
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(25, 8, "Left Pocket", 1, 0, "C");
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(25, 8, $work['left_pocket'], 1, 1, "C");

            //Line 22
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(80, 8, "Bottom Initial", 1, 0, "C");
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(110, 8, $work['bottom_initial'], 1, 1, "C");
            $pdf->Ln(5);

            //Line 23
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(30, 8, "Fabric Code", 1, 0, "C");
            $pdf->Cell(60, 8, "Fabric Name", 1, 0, "C");
            $pdf->Cell(30, 8, "Fabric Color", 1, 0, "C");
            $pdf->Cell(35, 8, "Fabric Usage (m)", 1, 0, "C");
            $pdf->Cell(35, 8, "Cleaning Type", 1, 1, "C");

            //Line 24
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(30, 8, $row['fabric_code'], 1, 0, "C");
            $pdf->Cell(60, 8, $row['fabric_name'], 1, 0, "C");
            $pdf->Cell(30, 8, $row['fabric_color'], 1, 0, "C");
            $pdf->Cell(35, 8, $row['fabric_usage'], 1, 0, "C");
            $pdf->Cell(35, 8, $work['cleaning_type'], 1, 1, "C");

            // Drawing (EXIF-corrected)
            $drawingPath = fixImageOrientation(__DIR__ . "/../uploads/drawings/" . $work['drawing']);
            $pdf->Image($drawingPath, 110, 90, 80, 80);

            // Extra notes / signatures
            $pdf->Cell(0, 8, "Special Instructions: " . ($work['special_instructions'] ?? ""), 0, 1);

            $pdf->Ln(200);
            break;

        case 'JACKET':
            $sql = "SELECT * FROM workslip_jacket WHERE item_id = $item_id";
            $work = $conn->query($sql)->fetch_assoc();

            //Line 1
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(38, 10, "Invoice No.", 1);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(38, 10, $invoice['invoice_number'], 1);
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(38, 10, "Manufacturer", 1);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(38, 10, $work['manufacturer'], 1);
            $pdf->SetFont('Arial', 'B', 14);
            $pdf->Cell(38, 10, "MUST", 1, 1, "C");

            //Line 2
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(31.7, 10, "Salesman", 1);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(31.7, 10, $work['salesman_name'], 1);
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(31.7, 10, "Cutter", 1);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(31.7, 10, $work['cutter_name'], 1);
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(31.7, 10, "Tailor", 1);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(31.6, 10, $work['tailor_name'], 1, 1);

            //Line 3
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(31.7, 10, "Fitting Date", 1);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(31.7, 10, $invoice['fitting_date'], 1);
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(31.7, 10, "Deliver Date", 1);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(31.7, 10, $invoice['delivery_date'], 1);
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(31.7, 10, "Gender", 1);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(31.7, 10, $work['gender'], 1, 1);

            //Line 6
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(30, 10, "Back", 1);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(60, 10, $work['back_length'], 1, 1);

            //Line 7
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(30, 10, "Front", 1);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(60, 10, $work['front_length'], 1, 1);

            //Line 8
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(30, 10, "Chest", 1);
            $pdf->Cell(15, 10, "Fit", 1, 0, "C");
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(15, 10, $work['chest_fit'], 1, 0, "C");
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(15, 10, "Loose", 1, 0, "C");
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(15, 10, $work['chest_loose'], 1, 1, "C");

            //Line 9
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(30, 10, "Waist", 1);
            $pdf->Cell(15, 10, "Fit", 1, 0, "C");
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(15, 10, $work['waist_fit'], 1, 0, "C");
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(15, 10, "Loose", 1, 0, "C");
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(15, 10, $work['waist_loose'], 1, 1, "C");

            //Line 10
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(30, 10, "Hip", 1);
            $pdf->Cell(15, 10, "Fit", 1, 0, "C");
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(15, 10, $work['hip_fit'], 1, 0, "C");
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(15, 10, "Loose", 1, 0, "C");
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(15, 10, $work['hip_loose'], 1, 1, "C");

            //Line 11
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(30, 10, "Shoulder", 1);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(60, 10, $work['shoulder'], 1, 1);

            //Line 12
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(30, 10, "Sleeve", 1);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(60, 10, $work['sleeve_length'], 1, 1);

            //Line 13
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(30, 10, "Cuff", 1);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(60, 10, $work['cuff_length'], 1, 1);

            //Line 14
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(30, 10, "Cross Back", 1);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(60, 10, $work['cross_back'], 1, 1);

            //Line 15
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(30, 10, "Cross Front", 1);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(60, 10, $work['cross_front'], 1, 1);

            //Line 16
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(30, 10, "Vest Length", 1);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(60, 10, $work['vest_length'], 1, 1);

            //Line 17
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(30, 10, "Armhole", 1);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(60, 10, $work['armhole'], 1, 1);

            //Line 18
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(30, 10, "B.Neck to Waist", 1);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(60, 10, $work['back_neck_to_waist'], 1, 1);

            //Line 19
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(30, 10, "F.Neck to Waist", 1);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(60, 10, $work['back_neck_to_front_waist'], 1, 0);
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(30, 10, "Sleeve Button", 1, 0, "C");
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(30, 10, $work['sleeve_button'], 1, 1, "C");

            //Line 20
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(30, 10, "Top Initial", 1);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(60, 10, $work['top_initial'], 1);
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(30, 10, "Bottom Initial", 1, 0, "C");
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(70, 10, $work['bottom_initial'], 1, 1);

            //Line 21
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(30, 10, "Fabric Code", 1, 0, "C");
            $pdf->Cell(60, 10, "Fabric Name", 1, 0, "C");
            $pdf->Cell(30, 10, "Fabric Color", 1, 0, "C");
            $pdf->Cell(35, 10, "Fabric Usage (m)", 1, 0, "C");
            $pdf->Cell(35, 10, "Cleaning Type", 1, 1, "C");

            //Line 22
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(30, 10, $row['fabric_code'], 1, 0, "C");
            $pdf->Cell(60, 10, $row['fabric_name'], 1, 0, "C");
            $pdf->Cell(30, 10, $row['fabric_color'], 1, 0, "C");
            $pdf->Cell(35, 10, $row['fabric_usage'], 1, 0, "C");
            $pdf->Cell(35, 10, $work['cleaning_type'], 1, 1, "C");

            // Drawing (EXIF-corrected)
            $drawingPath = fixImageOrientation(__DIR__ . "/../uploads/drawings/" . $work['drawing']);
            $pdf->Image($drawingPath, 100, 75, 100, 100);

            // Extra notes / signatures
            $pdf->Cell(0, 8, "Special Instructions: " . ($work['special_instructions'] ?? ""), 0, 1);
            break;

        default:
            $pdf->MultiCell(0, 8, "- No specific workslip data available.");
            break;
    }

    $pdf->Ln(5);
}

$pdf->Output();