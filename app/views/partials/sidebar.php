<div class="pt-3">
    <div class="text-center mb-4">
        <a class="navbar-brand" href="/">
            <img src='/assets/img/newlogo3.png' alt="Logo" class="mb-3" width="80">
            <h4 class="text-white"><?= ORG_NAME ?></h4>
            <hr class="bg-light mx-3">
        </a>
    </div>

    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link" href="/">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
        </li>
        <?php if(isset($_SESSION['user_id'])): ?>
        <li class="nav-item">
            <a class="nav-link" href="/sale">
                <i class="fas fa-cash-register"></i> Point of Sale
            </a>
        </li>
        <?php endif; ?>
        <?php if(isset($_SESSION['role']) && ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'cashier')): ?>
        <li class="nav-item">
            <a class="nav-link" href="/product">
                <i class="fas fa-box"></i> Products
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="/sale/history">
                <i class="fas fa-chart-line"></i> Sales
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="/admin/reports">
                <i class="fas fa-file-alt"></i> Reports
            </a>
        </li>
        <?php endif; ?>
        <li class="nav-item">
            <a class="nav-link" href="/sale/receipt">
                <i class="fas fa-receipt"></i> Receipts
            </a>
        </li>
        <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'customer'): ?>
        <li class="nav-item">
            <a class="nav-link" href="/sale/transactions">
                <i class="fas fa-history"></i> My Transactions
            </a>
        </li>
        <?php endif; ?>
        <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
        <li class="nav-item">
            <a class="nav-link" href="/admin/settings">
                <i class="fas fa-cog"></i> Settings
            </a>
        </li>
        <?php endif; ?>
    </ul>
</div>
