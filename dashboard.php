<?php
include('auth/check.php');
include('config/db.php');

$statsQuery = mysqli_query($conn, "
    SELECT
        (SELECT COUNT(*) FROM users) AS total_users,
        (SELECT COUNT(*) FROM invoices) AS total_invoices,
        (SELECT COALESCE(SUM(total_amount), 0) FROM invoices) AS total_revenue
");
$stats = mysqli_fetch_assoc($statsQuery);

$totalUsers = (int) $stats['total_users'];
$totalInvoices = (int) $stats['total_invoices'];
$totalRevenue = number_format((float) $stats['total_revenue'], 2);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WSSB CMS - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --dashboard-navy: #172554;
            --dashboard-blue: #2563eb;
        }

        body {
            min-height: 100vh;
            background: #f8fafc;
        }

        .navbar-brand {
            font-weight: 700;
            letter-spacing: 1px;
        }

        .dashboard-hero {
            padding: clamp(1.5rem, 4vw, 3rem);
            border: 0;
            border-radius: 1rem;
            color: #fff;
            background: linear-gradient(135deg, var(--dashboard-navy), var(--dashboard-blue));
            box-shadow: 0 0.75rem 2rem rgba(23, 37, 84, 0.18);
        }

        .dashboard-hero h1 {
            font-size: clamp(1.65rem, 4vw, 2.5rem);
        }

        .stat-card {
            height: 100%;
            border: 0;
            border-radius: 1rem;
            box-shadow: 0 0.25rem 0.9rem rgba(15, 23, 42, 0.08);
        }

        .stat-icon {
            width: 3.25rem;
            height: 3.25rem;
            flex: 0 0 3.25rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.85rem;
            font-size: 1.35rem;
        }

        .stat-value {
            font-size: clamp(1.5rem, 3.2vw, 2.25rem);
            font-weight: 700;
            line-height: 1.15;
            color: #0f172a;
            overflow-wrap: anywhere;
        }

        .quick-link {
            min-height: 3rem;
        }

        @media (min-width: 768px) and (max-width: 991.98px) {
            .dashboard-container,
            .navbar .container {
                max-width: 100%;
                padding-right: 1.5rem;
                padding-left: 1.5rem;
            }
        }

        @media (max-width: 575.98px) {
            .navbar-text {
                display: block;
                margin: 0.75rem 0 !important;
            }
        }
    </style>
</head>

<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">WSSB CMS</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="dashboard.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="customers/index.php">Customers</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="invoices/index.php">Invoices</a>
                    </li>
                </ul>
                <div class="navbar-nav align-items-lg-center">
                    <span class="navbar-text me-lg-3">
                        Hello, <strong><?php echo htmlspecialchars($_SESSION['user']); ?></strong>
                    </span>
                    <a href="auth/logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="container dashboard-container py-3 py-md-4">
        <section class="dashboard-hero mb-4" aria-labelledby="dashboard-title">
            <p class="text-uppercase small fw-semibold opacity-75 mb-2">Dashboard overview</p>
            <h1 id="dashboard-title" class="mb-2">Welcome back, <?php echo htmlspecialchars($_SESSION['user']); ?>!</h1>
            <p class="mb-4 opacity-75">Keep track of your tailor management system at a glance.</p>
            <div class="d-grid gap-2 d-sm-flex">
                <a href="customers/index.php" class="btn btn-light quick-link px-4">
                    <i class="fa-solid fa-users me-2"></i>Manage Customers
                </a>
                <a href="invoices/index.php" class="btn btn-outline-light quick-link px-4">
                    <i class="fa-solid fa-file-invoice-dollar me-2"></i>View Invoices
                </a>
            </div>
        </section>

        <section aria-label="Business totals">
            <div class="row g-3 g-lg-4">
                <div class="col-12 col-sm-6 col-lg-4">
                    <article class="card stat-card">
                        <div class="card-body p-3 p-md-4 d-flex align-items-center gap-3">
                            <span class="stat-icon bg-primary-subtle text-primary"><i class="fa-solid fa-users"></i></span>
                            <div>
                                <p class="text-muted small text-uppercase fw-semibold mb-1">Total Users</p>
                                <p class="stat-value mb-0"><?php echo number_format($totalUsers); ?></p>
                            </div>
                        </div>
                    </article>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                    <article class="card stat-card">
                        <div class="card-body p-3 p-md-4 d-flex align-items-center gap-3">
                            <span class="stat-icon bg-warning-subtle text-warning-emphasis"><i class="fa-solid fa-file-invoice"></i></span>
                            <div>
                                <p class="text-muted small text-uppercase fw-semibold mb-1">Total Invoices</p>
                                <p class="stat-value mb-0"><?php echo number_format($totalInvoices); ?></p>
                            </div>
                        </div>
                    </article>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                    <article class="card stat-card">
                        <div class="card-body p-3 p-md-4 d-flex align-items-center gap-3">
                            <span class="stat-icon bg-success-subtle text-success"><i class="fa-solid fa-money-bill-wave"></i></span>
                            <div>
                                <p class="text-muted small text-uppercase fw-semibold mb-1">Total Revenue</p>
                                <p class="stat-value mb-0">RM <?php echo $totalRevenue; ?></p>
                            </div>
                        </div>
                    </article>
                </div>
            </div>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
