<?php require_once ROOT_PATH . '/app/views/layouts/header.php'; ?>

<style>
    body {
        background: linear-gradient(135deg, #003631 0%, #006b5e 100%);
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }
    
    .login-container {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px 20px;
    }
    
    .login-card {
        background: white;
        border-radius: 20px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        padding: 40px;
        width: 100%;
        max-width: 440px;
        animation: fadeInUp 0.6s ease-out;
    }
    
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .logo-container {
        text-align: center;
        margin-bottom: 30px;
    }
    
    .logo-container img {
        width: 80px;
        height: 80px;
        margin-bottom: 15px;
        filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.1));
    }
    
    .company-name {
        color: #003631;
        font-size: 28px;
        font-weight: 700;
        margin-bottom: 5px;
        letter-spacing: 1px;
    }
    
    .welcome-text {
        color: #666;
        font-size: 14px;
        margin-bottom: 0;
    }
    
    .login-card h3 {
        color: #003631;
        font-weight: 600;
        margin-bottom: 25px;
        text-align: center;
    }
    
    .form-label {
        color: #333;
        font-weight: 500;
        font-size: 14px;
        margin-bottom: 8px;
    }
    
    .form-control {
        border: 2px solid #e0e0e0;
        border-radius: 10px;
        padding: 12px 16px;
        font-size: 14px;
        transition: all 0.3s ease;
    }
    
    .form-control:focus {
        border-color: #003631;
        box-shadow: 0 0 0 0.2rem rgba(0, 54, 49, 0.15);
    }
    
    .form-control.is-invalid {
        border-color: #dc3545;
    }
    
    .form-check-input:checked {
        background-color: #003631;
        border-color: #003631;
    }
    
    .btn-login {
        background: linear-gradient(135deg, #003631 0%, #006b5e 100%);
        border: none;
        border-radius: 10px;
        padding: 12px;
        font-weight: 600;
        font-size: 16px;
        margin-top: 10px;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    
    .btn-login:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0, 54, 49, 0.3);
        background: linear-gradient(135deg, #004d45 0%, #007a6b 100%);
    }
    
    .divider {
        text-align: center;
        margin: 25px 0 20px;
        position: relative;
        height: 20px;
    }
    
    .divider::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 0;
        right: 0;
        height: 1px;
        background: #e0e0e0;
        z-index: 0;
    }
    
    .divider span {
        background: white;
        padding: 0 15px;
        color: #999;
        font-size: 13px;
        position: relative;
        z-index: 1;
    }
    
    .links-section {
        text-align: center;
        position: relative;
        z-index: 2;
    }
    
    .links-section a {
        color: #003631;
        text-decoration: none;
        font-weight: 500;
        transition: color 0.2s ease;
    }
    
    .links-section a:hover {
        color: #006b5e;
        text-decoration: underline;
    }
    
    .links-section p {
        margin-bottom: 10px;
        color: #666;
        font-size: 14px;
    }
</style>

<div class="login-container">
    <div class="login-card">
        <div class="logo-container">
            <img src="<?= URLROOT; ?>/img/logo.png" alt="Evergreen Logo">
            <h2 class="company-name">EVERGREEN</h2>
            <p class="welcome-text">Welcome back! Please login to your account</p>
        </div>

        <?php if (!empty($login_error)): ?>
            <div class="alert alert-danger text-center" role="alert">
                <i class="bi bi-exclamation-circle-fill me-2"></i>
                <?= htmlspecialchars($login_error) ?>
            </div>
        <?php endif; ?>

        <form action="<?= URLROOT; ?>/auth/login" method="POST">
            <div class="mb-3">
                <label for="identifier" class="form-label">
                    <i class="bi bi-person-circle me-1"></i>Email or Account Number
                </label>
                <input 
                    type="text" 
                    name="identifier" 
                    id="identifier" 
                    class="form-control <?= (!empty($identifier_error)) ? 'is-invalid' : ''; ?>" 
                    placeholder="Enter email or account number" 
                    value="<?= htmlspecialchars($identifier ?? ''); ?>"
                    required
                >
                <div class="invalid-feedback">
                    <?= htmlspecialchars($identifier_error ?? ''); ?>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="password" class="form-label">
                    <i class="bi bi-lock-fill me-1"></i>Password
                </label>
                <input 
                    type="password" 
                    name="password" 
                    id="password" 
                    class="form-control <?= (!empty($password_error)) ? 'is-invalid' : ''; ?>" 
                    placeholder="Enter password" 
                    required
                >
                <div class="invalid-feedback">
                    <?= htmlspecialchars($password_error ?? ''); ?>
                </div>
            </div>
            
            <div class="mb-3 form-check">
                <input 
                    type="checkbox" 
                    class="form-check-input" 
                    id="remember_me" 
                    name="remember_me"
                    <?= (!empty($remember_me)) ? 'checked' : ''; ?>
                >
                <label class="form-check-label" for="remember_me">
                    Remember me
                </label>
            </div>
            
            <button type="submit" class="btn btn-login text-white w-100">
                <i class="bi bi-box-arrow-in-right me-2"></i>Login
            </button>
        </form>
        
        <div class="divider">
            <span>OR</span>
        </div>
        
        <div class="links-section">
            <p class="mb-2">Don't have an account? <a href="<?= URLROOT; ?>/auth/signup">Sign Up</a></p>
            <p class="mb-0"><a href="<?= URLROOT; ?>/auth/activate"><i class="bi bi-key-fill me-1"></i>Activate Account</a></p>
        </div>
    </div>
</div>

<?php require_once ROOT_PATH . '/app/views/layouts/footer.php'; ?>