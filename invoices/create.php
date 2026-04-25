<?php
include('../auth/check.php');
include('../config/db.php');

if (isset($_POST['submit'])) {
    $invoice_number = $_POST['invoice_number'];
    $customer_id = $_POST['customer_id'];
    $total = $_POST['total'];
    
    // Get Order, Fitting, and Delivery dates
    $order_date = !empty($_POST['order_date']) ? $_POST['order_date'] : date('Y-m-d');
    $fitting_date = !empty($_POST['fitting_date']) ? $_POST['fitting_date'] : NULL;
    $delivery_date = !empty($_POST['delivery_date']) ? $_POST['delivery_date'] : NULL;

    // Securely insert Invoice with custom Order Date
    $stmt = $conn->prepare("INSERT INTO invoices (invoice_number, customer_id, total_amount, fitting_date, delivery_date, order_date) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sidsss", $invoice_number, $customer_id, $total, $fitting_date, $delivery_date, $order_date);
    
    if ($stmt->execute()) {
        $invoice_id = $conn->insert_id;

        // Securely insert Invoice Items 
        $item_stmt = $conn->prepare("INSERT INTO invoice_items (invoice_id, item_type, quantity, amount, fabric_code, fabric_name, fabric_color, fabric_usage) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        foreach ($_POST['item_type'] as $key => $value) {
            $qty = $_POST['quantity'][$key];
            $amt = $_POST['amount'][$key];
            $f_code = $_POST['fabric_code'][$key];
            $f_name = $_POST['fabric_name'][$key];
            $f_color = $_POST['fabric_color'][$key];
            $f_usage = $_POST['fabric_usage'][$key];

            $item_stmt->bind_param("isidssss", $invoice_id, $value, $qty, $amt, $f_code, $f_name, $f_color, $f_usage);
            $item_stmt->execute();
        }

        header("Location: index.php");
        exit();
    }
}

$customers = mysqli_query($conn, "SELECT * FROM customers ORDER BY customer_name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Invoice - WSSB CMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .invoice-card { background: white; border-radius: 12px; padding: 30px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .item-row { background: #fff; border: 1px solid #dee2e6; border-radius: 8px; padding: 15px; margin-bottom: 15px; position: relative; }
        .item-row:hover { border-color: #0d6efd; }
        .section-header { border-left: 4px solid #0d6efd; padding-left: 10px; margin-bottom: 20px; font-weight: bold; color: #333; }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="../dashboard.php">WSSB CMS</a>
        </div>
    </nav>

    <main class="container mb-5">
        <div class="row justify-content-center">
            <div class="col-lg-12">
                <div class="invoice-card">
                    <h3 class="mb-4 text-primary fw-bold">Create New Invoice</h3>
                    
                    <form method="POST" id="invoiceForm">
                        <div class="row mb-4">
                            <div class="col-md-2">
                                <label class="form-label fw-bold small">Invoice #</label>
                                <input name="invoice_number" class="form-control" value="MOOO0" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold small">Customer</label>
                                <select name="customer_id" class="form-select" required>
                                    <option value="">-- Choose Customer --</option>
                                    <?php while($c = mysqli_fetch_assoc($customers)) { ?>
                                        <option value="<?php echo $c['customer_id']; ?>"><?php echo htmlspecialchars($c['customer_name']); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-bold small">Order Date</label>
                                <input type="date" name="order_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-bold small text-info"><i class="fa-solid fa-scissors"></i> Fitting Date</label>
                                <input type="date" name="fitting_date" class="form-control">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-bold small text-success"><i class="fa-solid fa-truck"></i> Delivery Date</label>
                                <input type="date" name="delivery_date" class="form-control">
                            </div>
                        </div>

                        <div class="section-header">Line Items & Fabric Details</div>
                        <div id="items-container">
                            <div class="item-row shadow-sm">
                                <div class="row g-2 mb-2">
                                    <div class="col-md-4">
                                        <label class="small fw-bold">Item Type</label>
                                        <select name="item_type[]" class="form-select" required>
                                            <option value="Baju Melayu">Baju Melayu</option>
                                            <option value="Shirt">Shirt</option>
                                            <option value="Trousers">Trousers</option>
                                            <option value="Jacket">Jacket</option>
                                            <option value="Vest">Vest</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="small fw-bold">Qty</label>
                                        <input type="number" name="quantity[]" class="form-control qty" value="1" min="1" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="small fw-bold">Unit Price (RM)</label>
                                        <input type="number" step="0.01" name="amount[]" class="form-control price" placeholder="0.00" required>
                                    </div>
                                    <div class="col-md-3 d-flex align-items-end">
                                        <button type="button" class="btn btn-outline-danger w-100" onclick="removeItem(this)">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="row g-2">
                                    <div class="col-md-3"><input name="fabric_code[]" class="form-control form-control-sm" placeholder="Fabric Code"></div>
                                    <div class="col-md-3"><input name="fabric_name[]" class="form-control form-control-sm" placeholder="Fabric Name"></div>
                                    <div class="col-md-3"><input name="fabric_color[]" class="form-control form-control-sm" placeholder="Fabric Color"></div>
                                    <div class="col-md-3"><input name="fabric_usage[]" class="form-control form-control-sm" placeholder="Usage (e.g. 4.5m)"></div>
                                </div>
                            </div>
                        </div>

                        <button type="button" onclick="addItem()" class="btn btn-outline-primary mb-4 btn-sm">
                            <i class="fa-solid fa-plus"></i> Add Item
                        </button>

                        <div class="row justify-content-end">
                            <div class="col-md-4">
                                <div class="card border-primary mb-3">
                                    <div class="card-body py-2">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="fw-bold">Grand Total:</span>
                                            <span class="h4 mb-0 fw-bold text-primary">RM <input name="total" id="grandTotal" class="border-0 bg-transparent fw-bold text-primary text-end" style="width:120px" readonly value="0.00"></span>
                                        </div>
                                    </div>
                                </div>
                                <button name="submit" class="btn btn-success btn-lg w-100 shadow">
                                    <i class="fa-solid fa-check-circle"></i> Complete Invoice
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <script>
    function addItem() {
        const container = document.getElementById('items-container');
        const div = document.createElement('div');
        div.className = 'item-row shadow-sm';
        div.innerHTML = `
            <div class="row g-2 mb-2">
                <div class="col-md-4">
                    <select name="item_type[]" class="form-select" required>
                        <option value="Baju Melayu">Baju Melayu</option>
                        <option value="Shirt">Shirt</option>
                        <option value="Trousers">Trousers</option>
                        <option value="Jacket">Jacket</option>
                        <option value="Vest">Vest</option>
                    </select>
                </div>
                <div class="col-md-2"><input type="number" name="quantity[]" class="form-control qty" value="1" min="1" required></div>
                <div class="col-md-3"><input type="number" step="0.01" name="amount[]" class="form-control price" placeholder="0.00" required></div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="button" class="btn btn-outline-danger w-100" onclick="removeItem(this)"><i class="fa-solid fa-trash"></i></button>
                </div>
            </div>
            <div class="row g-2">
                <div class="col-md-3"><input name="fabric_code[]" class="form-control form-control-sm" placeholder="Fabric Code"></div>
                <div class="col-md-3"><input name="fabric_name[]" class="form-control form-control-sm" placeholder="Fabric Name"></div>
                <div class="col-md-3"><input name="fabric_color[]" class="form-control form-control-sm" placeholder="Fabric Color"></div>
                <div class="col-md-3"><input name="fabric_usage[]" class="form-control form-control-sm" placeholder="Usage (e.g. 4.5m)"></div>
            </div>`;
        container.appendChild(div);
        attachCalcEvents();
    }

    function removeItem(btn) {
        const rows = document.querySelectorAll('.item-row');
        if(rows.length > 1) {
            btn.closest('.item-row').remove();
            calculateTotal();
        } else {
            alert("At least one item is required.");
        }
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

    function attachCalcEvents() {
        document.querySelectorAll('.qty, .price').forEach(input => {
            input.removeEventListener('input', calculateTotal);
            input.addEventListener('input', calculateTotal);
        });
    }

    attachCalcEvents();
    </script>
</body>
</html>