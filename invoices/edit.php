<?php
include('../auth/check.php');
include('../config/db.php');

$invoice_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// FETCH INVOICE
$stmt = $conn->prepare("SELECT * FROM invoices WHERE invoice_id = ?");
$stmt->bind_param("i", $invoice_id);
$stmt->execute();
$invoice = $stmt->get_result()->fetch_assoc();

if (!$invoice) {
    header("Location: index.php");
    exit();
}

// FETCH ITEMS
$items_res = mysqli_query($conn, "SELECT * FROM invoice_items WHERE invoice_id = $invoice_id");

// FETCH CUSTOMERS
$customers = mysqli_query($conn, "SELECT * FROM customers ORDER BY customer_name ASC");

// UPDATE LOGIC
if(isset($_POST['update'])) {
    $customer_id = $_POST['customer_id'];
    $total = $_POST['total'];
    $fitting_date = !empty($_POST['fitting_date']) ? $_POST['fitting_date'] : NULL;
    $delivery_date = !empty($_POST['delivery_date']) ? $_POST['delivery_date'] : NULL;

    // 1. UPDATE INVOICE
    $upd_invoice = $conn->prepare("UPDATE invoices SET customer_id = ?, total_amount = ?, fitting_date = ?, delivery_date = ? WHERE invoice_id = ?");
    $upd_invoice->bind_param("idssi", $customer_id, $total, $fitting_date, $delivery_date, $invoice_id);
    $upd_invoice->execute();

    // 2. DELETE OLD ITEMS
    mysqli_query($conn, "DELETE FROM invoice_items WHERE invoice_id = $invoice_id");

    // 3. INSERT NEW/UPDATED ITEMS
    $item_stmt = $conn->prepare("INSERT INTO invoice_items (invoice_id, item_type, quantity, amount, fabric_code, fabric_name, fabric_color, fabric_usage) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    foreach($_POST['item_type'] as $key => $value) {
        if(!empty($value)) {
            $qty = $_POST['quantity'][$key];
            $amt = $_POST['amount'][$key];
            $f_code = $_POST['fabric_code'][$key];
            $f_name = $_POST['fabric_name'][$key];
            $f_color = $_POST['fabric_color'][$key];
            $f_usage = $_POST['fabric_usage'][$key];

            $item_stmt->bind_param("isidssss", $invoice_id, $value, $qty, $amt, $f_code, $f_name, $f_color, $f_usage);
            $item_stmt->execute();
        }
    }

    header("Location: view.php?id=$invoice_id&msg=updated");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Invoice #<?php echo $invoice['invoice_number']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .edit-card { background: white; border-radius: 12px; padding: 30px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .item-row { border: 1px solid #dee2e6; border-radius: 8px; padding: 15px; margin-bottom: 15px; background: #fff; }
    </style>
</head>
<body>

<nav class="navbar navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="../dashboard.php">WSSB CMS | Edit Invoice</a>
    </div>
</nav>

<div class="container mb-5">
    <div class="edit-card">
        <form method="POST" id="editForm">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3>Invoice: <span class="text-primary"><?php echo $invoice['invoice_number']; ?></span></h3>
                <a href="view.php?id=<?php echo $invoice_id; ?>" class="btn btn-outline-secondary">Back to View</a>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Customer</label>
                    <select name="customer_id" class="form-select">
                        <?php while($c = mysqli_fetch_assoc($customers)) { ?>
                            <option value="<?php echo $c['customer_id']; ?>" <?php echo ($c['customer_id'] == $invoice['customer_id']) ? "selected" : ""; ?>>
                                <?php echo htmlspecialchars($c['customer_name']); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold text-info">Fitting Date</label>
                    <input type="date" name="fitting_date" class="form-control" value="<?php echo $invoice['fitting_date']; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold text-success">Delivery Date</label>
                    <input type="date" name="delivery_date" class="form-control" value="<?php echo $invoice['delivery_date']; ?>">
                </div>
            </div>

            <hr>
            <h5 class="mb-3">Items & Fabric Details</h5>
            <div id="items-container">
                <?php while($item = mysqli_fetch_assoc($items_res)) { ?>
                <div class="item-row">
                    <div class="row g-2 mb-2">
                        <div class="col-md-4">
                            <label class="small fw-bold">Item Type</label>
                            <input name="item_type[]" value="<?php echo $item['item_type']; ?>" class="form-control" required>
                        </div>
                        <div class="col-md-2">
                            <label class="small fw-bold">Qty</label>
                            <input type="number" name="quantity[]" value="<?php echo $item['quantity']; ?>" class="form-control qty" required oninput="calculateTotal()">
                        </div>
                        <div class="col-md-3">
                            <label class="small fw-bold">Price (RM)</label>
                            <input type="number" step="0.01" name="amount[]" value="<?php echo $item['amount']; ?>" class="form-control price" required oninput="calculateTotal()">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="button" class="btn btn-outline-danger w-100" onclick="this.closest('.item-row').remove(); calculateTotal();">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-3"><input name="fabric_code[]" value="<?php echo $item['fabric_code']; ?>" class="form-control form-control-sm" placeholder="Fabric Code"></div>
                        <div class="col-md-3"><input name="fabric_name[]" value="<?php echo $item['fabric_name']; ?>" class="form-control form-control-sm" placeholder="Fabric Name"></div>
                        <div class="col-md-3"><input name="fabric_color[]" value="<?php echo $item['fabric_color']; ?>" class="form-control form-control-sm" placeholder="Color"></div>
                        <div class="col-md-3"><input name="fabric_usage[]" value="<?php echo $item['fabric_usage']; ?>" class="form-control form-control-sm" placeholder="Usage"></div>
                    </div>
                </div>
                <?php } ?>
            </div>

            <button type="button" onclick="addItem()" class="btn btn-outline-primary mb-4">+ Add Item</button>

            <div class="row justify-content-end">
                <div class="col-md-4">
                    <label class="fw-bold">Grand Total (RM)</label>
                    <input name="total" id="grandTotal" value="<?php echo $invoice['total_amount']; ?>" class="form-control form-control-lg text-end fw-bold mb-3" readonly>
                    <button name="update" class="btn btn-primary btn-lg w-100 shadow">Update Invoice</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function addItem() {
    const container = document.getElementById('items-container');
    const div = document.createElement('div');
    div.className = 'item-row';
    div.innerHTML = `
        <div class="row g-2 mb-2">
            <div class="col-md-4"><input name="item_type[]" class="form-control" placeholder="Item Type" required></div>
            <div class="col-md-2"><input type="number" name="quantity[]" class="form-control qty" value="1" required oninput="calculateTotal()"></div>
            <div class="col-md-3"><input type="number" step="0.01" name="amount[]" class="form-control price" placeholder="0.00" required oninput="calculateTotal()"></div>
            <div class="col-md-3 d-flex align-items-end"><button type="button" class="btn btn-outline-danger w-100" onclick="this.closest('.item-row').remove(); calculateTotal();"><i class="fa-solid fa-trash"></i></button></div>
        </div>
        <div class="row g-2">
            <div class="col-md-3"><input name="fabric_code[]" class="form-control form-control-sm" placeholder="Fabric Code"></div>
            <div class="col-md-3"><input name="fabric_name[]" class="form-control form-control-sm" placeholder="Fabric Name"></div>
            <div class="col-md-3"><input name="fabric_color[]" class="form-control form-control-sm" placeholder="Color"></div>
            <div class="col-md-3"><input name="fabric_usage[]" class="form-control form-control-sm" placeholder="Usage"></div>
        </div>`;
    container.appendChild(div);
}

function calculateTotal() {
    let total = 0;
    document.querySelectorAll('.item-row').forEach(row => {
        const qty = row.querySelector('.qty').value || 0;
        const price = row.querySelector('.price').value || 0;
        total += (parseFloat(qty) * parseFloat(price));
    });
    document.getElementById('grandTotal').value = total.toFixed(2);
}
</script>

</body>
</html>