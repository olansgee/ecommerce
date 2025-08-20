<h2 class="page-title">System Settings</h2>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Create Cashier Account</h5>
            </div>
            <div class="card-body">
                <form action="<?= url('admin/createcashier') ?>" method="post">
                    <div class="mb-3">
                        <label for="cashier_username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="cashier_username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="cashier_email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="cashier_email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="cashier_password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="cashier_password" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="cashier_confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="cashier_confirm_password" name="confirm_password" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" name="create_cashier" class="btn btn-primary">
                            <i class="fas fa-user-plus me-1"></i> Create Cashier
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">System Information</h5>
            </div>
            <div class="card-body">
                <p><strong>Organization:</strong> <?= ORG_NAME ?></p>
                <p><strong>Version:</strong> 1.0.0 (MVC)</p>
            </div>
        </div>
    </div>
</div>
