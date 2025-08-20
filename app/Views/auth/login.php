<div class="row justify-content-center mt-5">
    <div class="col-md-6 auth-container">
        <div class="card">
            <div class="card-header p-0">
                <ul class="nav nav-tabs auth-tabs">
                    <li class="nav-item w-50 text-center">
                        <a class="nav-link active" data-bs-toggle="tab" href="#login">Login</a>
                    </li>
                    <li class="nav-item w-50 text-center">
                        <a class="nav-link" data-bs-toggle="tab" href="#signup">Sign Up</a>
                    </li>
                </ul>
            </div>
            <div class="card-body p-4">
                <div class="tab-content">
                    <!-- Login Tab -->
                    <div class="tab-pane active" id="login">
                        <form action="<?= url('auth/login') ?>" method="post">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username or Email</label>
                                <input type="text" class="form-control form-control-lg" id="username" name="username" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control form-control-lg" id="password" name="password" required>
                            </div>
                            <div class="d-grid mb-3">
                                <button type="submit" name="login" class="btn btn-primary btn-lg">Login</button>
                            </div>
                        </form>
                    </div>

                    <!-- Signup Tab -->
                    <div class="tab-pane" id="signup">
                        <form action="<?= url('auth/signup') ?>" method="post">
                            <div class="mb-3">
                                <label for="signup_username" class="form-label">Username</label>
                                <input type="text" class="form-control form-control-lg" id="signup_username" name="username" required>
                            </div>
                            <div class="mb-3">
                                <label for="signup_email" class="form-label">Email</label>
                                <input type="email" class="form-control form-control-lg" id="signup_email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="signup_password" class="form-label">Password</label>
                                <input type="password" class="form-control form-control-lg" id="signup_password" name="password" required>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control form-control-lg" id="confirm_password" name="confirm_password" required>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="gender" class="form-label">Gender</label>
                                    <select class="form-select form-select-lg" id="gender" name="gender" required>
                                        <option value="">Select Gender</option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="age" class="form-label">Age</label>
                                    <input type="number" class="form-control form-control-lg" id="age" name="age" min="1" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="address" class="form-label">Residential Address</label>
                                <textarea class="form-control form-control-lg" id="address" name="address" rows="2" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control form-control-lg" id="phone" name="phone" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" name="signup" class="btn btn-success btn-lg">Create Account</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
