<?php
$navbarBasePath = $navbarBasePath ?? '';
$navbarActive = $navbarActive ?? '';
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="<?php echo $navbarBasePath; ?>dashboard.php">WSSB CMS</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link<?php echo $navbarActive === 'home' ? ' active' : ''; ?>"<?php echo $navbarActive === 'home' ? ' aria-current="page"' : ''; ?> href="<?php echo $navbarBasePath; ?>dashboard.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?php echo $navbarActive === 'customers' ? ' active' : ''; ?>"<?php echo $navbarActive === 'customers' ? ' aria-current="page"' : ''; ?> href="<?php echo $navbarBasePath; ?>customers/index.php">Customers</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?php echo $navbarActive === 'invoices' ? ' active' : ''; ?>"<?php echo $navbarActive === 'invoices' ? ' aria-current="page"' : ''; ?> href="<?php echo $navbarBasePath; ?>invoices/index.php">Invoices</a>
                </li>
            </ul>
            <div class="navbar-nav align-items-lg-center">
                <span class="navbar-text me-lg-3">
                    Hello, <strong><?php echo htmlspecialchars($_SESSION['user']); ?></strong>
                </span>
                <a href="<?php echo $navbarBasePath; ?>auth/logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
            </div>
        </div>
    </div>
</nav>
