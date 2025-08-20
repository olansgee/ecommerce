<h2 class="page-title">Point of Sale</h2>

<div class="row">
    <!-- Products List -->
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Available Products</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach($products as $p): ?>
                    <div class="col-md-3 mb-3">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <img src="<?= url('uploads/' . $p['image']) ?>" alt="<?= $p['name'] ?>" class="product-image">
                                <h6 class="card-title"><?= $p['name'] ?></h6>
                                <p class="card-text text-success fw-bold">₦<?= number_format($p['price'], 2) ?></p>
                                <p class="small text-muted">Stock: <?= $p['stock'] ?></p>
                                <form action="<?= url('sale/addtocart') ?>" method="post">
                                    <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                    <input type="number" name="quantity" value="1" min="1" max="<?= $p['stock'] ?>" class="form-control mb-2">
                                    <button type="submit" class="btn btn-sm btn-primary w-100" <?= $p['stock'] == 0 ? 'disabled' : '' ?>>
                                        <i class="fas fa-cart-plus me-1"></i> Add to Cart
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Shopping Cart -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Shopping Cart</h5>
            </div>
            <div class="card-body">
                <?php if(!empty($cart_items)): ?>
                    <?php
                    $total = 0;
                    foreach($cart_items as $item):
                        $subtotal = $item['price'] * $item['quantity'];
                        $total += $subtotal;
                    ?>
                    <div class="cart-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-bold"><?= $item['name'] ?></div>
                                <div>₦<?= number_format($item['price'], 2) ?> x <?= $item['quantity'] ?></div>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold">₦<?= number_format($subtotal, 2) ?></div>
                                <form action="<?= url('sale/removefromcart') ?>" method="post" class="d-inline">
                                    <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <div class="cart-item fw-bold">
                        <div class="d-flex justify-content-between">
                            <div>Total:</div>
                            <div>₦<?= number_format($total, 2) ?></div>
                        </div>
                    </div>
                    <button class="btn btn-primary w-100 mt-3" data-bs-toggle="modal" data-bs-target="#checkoutModal">
                        Checkout
                    </button>
                <?php else: ?>
                    <p class="text-center text-muted">Your cart is empty</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Checkout Modal -->
<div class="modal fade" id="checkoutModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Complete Purchase</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= url('sale/checkout') ?>" method="post">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Customer Name</label>
                        <input type="text" class="form-control" name="customer_name" value="<?= $_SESSION['username'] ?? '' ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Customer Email</label>
                        <input type="email" class="form-control" name="customer_email" value="<?= $_SESSION['email'] ?? '' ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Complete Order</button>
                </div>
            </form>
        </div>
    </div>
</div>
