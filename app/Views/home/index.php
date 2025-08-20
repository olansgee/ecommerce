<h2 class="page-title">Dashboard</h2>

<div class="row">
    <div class="col-md-3">
        <div class="card bg-success-light stat-card">
            <i class="fas fa-check-circle"></i>
            <div class="number"><?= $stats['today_completed'] ?></div>
            <div class="label">Completed Sales</div>
            <div class="small">₦<?= number_format($stats['today_completed_amount'], 2) ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning-light stat-card">
            <i class="fas fa-clock"></i>
            <div class="number"><?= $stats['today_pending'] ?></div>
            <div class="label">Pending Sales</div>
            <div class="small">₦<?= number_format($stats['today_pending_amount'], 2) ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-primary-light stat-card">
            <i class="fas fa-boxes"></i>
            <div class="number"><?= $stats['total_products'] ?></div>
            <div class="label">Total Products</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-danger-light stat-card">
            <i class="fas fa-exclamation-triangle"></i>
            <div class="number"><?= $stats['pending_payments'] ?></div>
            <div class="label">Pending Payments</div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Recent Sales</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Date</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Payment Status</th>
                                <th>Delivery Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($recent_sales as $sale): ?>
                            <tr>
                                <td>#<?= str_pad($sale['id'], 6, '0', STR_PAD_LEFT) ?></td>
                                <td><?= date('M j, Y h:i A', strtotime($sale['sale_date'])) ?></td>
                                <td><?= $sale['customer_name'] ?></td>
                                <td>₦<?= number_format($sale['total_amount'], 2) ?></td>
                                <td>
                                    <span class="badge bg-<?= $sale['payment_status'] == 'completed' ? 'success' : 'warning' ?>">
                                        <?= ucfirst($sale['payment_status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $sale['delivery_status'] == 'delivered' ? 'success' : 'warning' ?>">
                                        <?= ucfirst($sale['delivery_status']) ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
