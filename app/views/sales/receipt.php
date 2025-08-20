<h2 class="page-title">Receipt</h2>

<?php if($sale): ?>
<div class="receipt">
    <div class="receipt-header">
        <h3><?= ORG_NAME ?></h3>
        <p><?= ORG_ADDRESS ?></p>
        <p>Phone: <?= ORG_PHONE ?> | Email: <?= ORG_EMAIL ?></p>
        <h4 class="mt-3">SALES INVOICE</h4>
    </div>

    <div class="row mb-4">
        <div class="col-md-6">
            <p><strong>Invoice #:</strong> <?= str_pad($sale['id'], 6, '0', STR_PAD_LEFT) ?></p>
            <p><strong>Date:</strong> <?= $sale['sale_date'] ?></p>
        </div>
        <div class="col-md-6">
            <p><strong>Customer:</strong> <?= $sale['customer_name'] ?></p>
            <p><strong>Email:</strong> <?= $sale['customer_email'] ?></p>
        </div>
    </div>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Product</th>
                <th class="text-center">Price</th>
                <th class="text-center">Qty</th>
                <th class="text-end">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($sale['items'] as $item): ?>
            <tr>
                <td><?= $item['product_name'] ?></td>
                <td class="text-center">₦<?= number_format($item['price'], 2) ?></td>
                <td class="text-center"><?= $item['quantity'] ?></td>
                <td class="text-end">₦<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
            <tr>
                <td colspan="3" class="text-end fw-bold">TOTAL:</td>
                <td class="text-end fw-bold">₦<?= number_format($sale['total_amount'], 2) ?></td>
            </tr>
        </tbody>
    </table>

    <div class="receipt-footer">
        <p>Thank you for your purchase!</p>
    </div>

    <div class="d-flex justify-content-center mt-4">
        <button class="btn btn-success" onclick="window.print()">
            <i class="fas fa-print me-1"></i> Print Invoice
        </button>
    </div>
</div>
<?php else: ?>
<div class="alert alert-info">
    No receipt to display.
</div>
<?php endif; ?>
