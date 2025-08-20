<h2 class="page-title">Sales History</h2>

<div class="card mb-4">
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
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($sales as $sale): ?>
                    <tr>
                        <td>#<?= str_pad($sale['id'], 6, '0', STR_PAD_LEFT) ?></td>
                        <td><?= date('M j, Y h:i A', strtotime($sale['sale_date'])) ?></td>
                        <td><?= $sale['customer_name'] ?></td>
                        <td>₦<?= number_format($sale['total_amount'], 2) ?></td>
                        <td><span class="badge bg-<?= $sale['payment_status'] == 'completed' ? 'success' : 'warning' ?>"><?= ucfirst($sale['payment_status']) ?></span></td>
                        <td><span class="badge bg-<?= $sale['delivery_status'] == 'delivered' ? 'success' : 'warning' ?>"><?= ucfirst($sale['delivery_status']) ?></span></td>
                        <td>
                            <?php if($sale['payment_status'] === 'incomplete'): ?>
                            <form action="<?= url('sale/confirmpayment') ?>" method="post" class="d-inline">
                                <input type="hidden" name="sale_id" value="<?= $sale['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-success">Confirm Payment</button>
                            </form>
                            <?php endif; ?>
                            <?php if($_SESSION['role'] == 'admin'): ?>
                            <form action="<?= url('sale/updatedelivery') ?>" method="post" class="d-inline">
                                <input type="hidden" name="sale_id" value="<?= $sale['id'] ?>">
                                <select name="delivery_status" class="form-select form-select-sm d-inline w-auto">
                                    <option value="pending" <?= $sale['delivery_status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="delivered" <?= $sale['delivery_status'] == 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                </select>
                                <button type="submit" class="btn btn-sm btn-primary">Update</button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
