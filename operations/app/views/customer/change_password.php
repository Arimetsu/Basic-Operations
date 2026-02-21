<?php require_once ROOT_PATH . '/app/views/layouts/header.php'; ?>

<style>
:root {
    --primary-color: #6c757d;
    --primary-green: #003631;
    --light-gray: #f8f9fa;
    --border-gray: #e9ecef;
    --text-dark: #212529;
    --text-muted: #6c757d;
}

body {
    background-color: var(--light-gray);
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
}

.profile-container {
    display: flex;
    gap: 0;
    min-height: calc(100vh - 100px);
    max-width: 1400px;
    margin: 0 auto;
    background: white;
    box-shadow: 0 0 20px rgba(0,0,0,0.05);
}

/* Sidebar Navigation */
.profile-sidebar {
    width: 280px;
    background: white;
    border-right: 1px solid var(--border-gray);
    padding: 2rem 0;
    flex-shrink: 0;
}

.profile-sidebar .user-info {
    padding: 0 1.5rem 1.5rem;
    border-bottom: 1px solid var(--border-gray);
    margin-bottom: 1rem;
}

.profile-sidebar .user-info h5 {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text-dark);
    margin-bottom: 0.25rem;
}

.profile-sidebar .user-info p {
    color: var(--text-muted);
    font-size: 0.875rem;
    margin: 0;
}

.profile-nav {
    list-style: none;
    padding: 0;
    margin: 0;
}

.profile-nav-item {
    padding: 0;
}

.profile-nav-link {
    display: flex;
    align-items: center;
    padding: 0.875rem 1.5rem;
    color: var(--text-dark);
    text-decoration: none;
    transition: all 0.3s ease;
    border-left: 3px solid transparent;
    font-size: 0.95rem;
}

.profile-nav-link i {
    width: 20px;
    margin-right: 0.75rem;
    font-size: 1.1rem;
}

.profile-nav-link:hover {
    background-color: rgba(108, 117, 125, 0.05);
    color: var(--primary-color);
}

.profile-nav-link.active {
    background-color: rgba(108, 117, 125, 0.1);
    color: var(--primary-color);
    border-left-color: var(--primary-color);
    font-weight: 500;
}

.profile-main {
    flex: 1;
    padding: 2rem 3rem;
    overflow-y: auto;
}

@media (max-width: 992px) {
    .profile-container {
        flex-direction: column;
    }
    
    .profile-sidebar {
        width: 100%;
        border-right: none;
        border-bottom: 1px solid var(--border-gray);
    }
}
</style>

<div class="profile-container">
    <!-- Sidebar Navigation -->
    <aside class="profile-sidebar">
        <div class="user-info">
            <h5><?= htmlspecialchars($_SESSION['full_name'] ?? 'User') ?></h5>
            <p><?= htmlspecialchars($_SESSION['email'] ?? '') ?></p>
        </div>
        
        <ul class="profile-nav">
            <li class="profile-nav-item">
                <a href="<?= URLROOT ?>/customer/profile" class="profile-nav-link">
                    <i class="bi bi-person"></i>
                    Profile
                </a>
            </li>
            <li class="profile-nav-item">
                <a href="<?= URLROOT ?>/customer/change_password" class="profile-nav-link active">
                    <i class="bi bi-shield-lock"></i>
                    Security
                </a>
            </li>
            <li class="profile-nav-item">
                <a href="<?= URLROOT ?>/customer/dashboard" class="profile-nav-link">
                    <i class="bi bi-house-door"></i>
                    Dashboard
                </a>
            </li>
        </ul>
    </aside>

    <!-- Main Content -->
    <main class="profile-main">
        <div class="mb-4">
            <h4 class="fw-semibold text-dark">Change Password</h4>
            <p class="text-muted" style="max-width: 600px;">
                If you'd like to change your password, enter your current and new passwords here. Otherwise, leave them all blank. You can use letters, numbers, spaces, and special characters (like *!@#$&, etc.).
            </p>
        </div>

            <?php if(!empty($data['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show fw-semibold" role="alert">
                    <?= htmlspecialchars($data['success_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php elseif(!empty($data['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible fade show fw-semibold" role="alert">
                    <?= htmlspecialchars($data['error_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form action="<?= URLROOT . "/customer/change_password" ?>" method="POST" id="changePasswordForm">
                <div class="row">
                    <div class="col-lg-6">
                        
                        <!-- Current Password -->
                        <div class="mb-3">
                            <label for="old_password" class="form-label fw-semibold text-dark">Current Password</label>
                            <div class="input-group">
                                <input type="password" 
                                        name="old_password" 
                                        id="old_password" 
                                        class="form-control <?= (!empty($data['old_password_err'])) ? 'is-invalid' : ''; ?>" 
                                        value="<?= htmlspecialchars($data['old_password'] ?? ''); ?>"
                                        placeholder="*************">
                                <span class="input-group-text bg-white" 
                                        style="cursor: pointer;" 
                                        onclick="togglePassword('old_password', this)">
                                    <i class="fas fa-eye-slash"></i>
                                </span>
                                <div class="invalid-feedback">
                                    <?= $data['old_password_err'] ?? ''; ?>
                                </div>
                            </div>
                        </div>

                        <!-- New Password -->
                        <div class="mb-3">
                            <label for="new_password" class="form-label fw-semibold text-dark">New Password</label>
                            <div class="input-group">
                                <input type="password" 
                                        name="new_password" 
                                        id="new_password" 
                                        class="form-control <?= (!empty($data['new_password_err'])) ? 'is-invalid' : ''; ?>" 
                                        value="<?= htmlspecialchars($data['new_password'] ?? ''); ?>"
                                        placeholder="*************">
                                <span class="input-group-text bg-white" 
                                        style="cursor: pointer;" 
                                        onclick="togglePassword('new_password', this)">
                                    <i class="fas fa-eye-slash"></i>
                                </span>
                                <div class="invalid-feedback">
                                    <?= $data['new_password_err'] ?? ''; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Confirm New Password -->
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label fw-semibold text-dark">Confirm New Password</label>
                            <div class="input-group">
                                <input type="password" 
                                        name="confirm_password" 
                                        id="confirm_password" 
                                        class="form-control <?= (!empty($data['confirm_password_err'])) ? 'is-invalid' : ''; ?>" 
                                        value="<?= htmlspecialchars($data['confirm_password'] ?? ''); ?>"
                                        placeholder="*************">
                                <span class="input-group-text bg-white" 
                                        style="cursor: pointer;" 
                                        onclick="togglePassword('confirm_password', this)">
                                    <i class="fas fa-eye-slash"></i>
                                </span>
                                <div class="invalid-feedback">
                                    <?= $data['confirm_password_err'] ?? ''; ?>
                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="col-lg-6">
                        <div class="mt-4 mt-lg-0 pt-lg-4">
                            <h6 class="fw-semibold text-dark">Password Requirements</h6>
                            <ul class="list-unstyled">
                                <li id="length" class="mb-2 text-danger"><i class="fas fa-times me-2"></i> At least 10 characters long</li>
                                <li id="uppercase" class="mb-2 text-danger"><i class="fas fa-times me-2"></i> Contains at least one uppercase character</li>
                                <li id="lowercase" class="mb-2 text-danger"><i class="fas fa-times me-2"></i> Contains at least one lowercase character</li>
                                <li id="number" class="mb-2 text-danger"><i class="fas fa-times me-2"></i> Contains at least one number</li>
                                <li id="match" class="mb-2 text-danger"><i class="fas fa-times me-2"></i> Type the password again to confirm</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4">
                    <button type="submit" class="btn btn-success">Save Settings</button>
                </div>
            </form>
    </main>
</div>

<script>
    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password');

    const requirements = {
        length: document.getElementById('length'),
        uppercase: document.getElementById('uppercase'),
        lowercase: document.getElementById('lowercase'),
        number: document.getElementById('number'),
        match: document.getElementById('match')
    };

    // Helper to update requirement list item
    function updateRequirement(reqElement, condition, successText, failureText) {
        if (condition) {
            reqElement.classList.replace('text-danger', 'text-success');
            reqElement.innerHTML = `<i class="fas fa-check me-2"></i> ${successText}`;
        } else {
            reqElement.classList.replace('text-success', 'text-danger');
            reqElement.innerHTML = `<i class="fas fa-times me-2"></i> ${failureText}`;
        }
    }

    function checkPasswordRequirements() {
        const pwd = newPassword.value;
        const confirmPwd = confirmPassword.value;

        // Length
        updateRequirement(requirements.length, pwd.length >= 10, 'At least 10 characters long', 'At least 10 characters long');

        // Uppercase
        updateRequirement(requirements.uppercase, /[A-Z]/.test(pwd), 'Contains at least one uppercase character', 'Contains at least one uppercase character');

        // Lowercase
        updateRequirement(requirements.lowercase, /[a-z]/.test(pwd), 'Contains at least one lowercase character', 'Contains at least one lowercase character');

        // Number
        updateRequirement(requirements.number, /\d/.test(pwd), 'Contains at least one number', 'Contains at least one number');

        // Match with confirm password - IMPROVED LOGIC
        if (pwd !== "" && pwd === confirmPwd) {
            updateRequirement(requirements.match, true, 'Passwords match', 'Type the password again to confirm');
        } else if (pwd !== "" && confirmPwd !== "" && pwd !== confirmPwd) {
             updateRequirement(requirements.match, false, 'Passwords match', 'Passwords do not match');
        } else {
            updateRequirement(requirements.match, false, 'Passwords match', 'Type the password again to confirm');
        }
    }

    // Initial check on load in case of PHP validation errors
    document.addEventListener('DOMContentLoaded', checkPasswordRequirements);

    newPassword.addEventListener('input', checkPasswordRequirements);
    confirmPassword.addEventListener('input', checkPasswordRequirements);

    // Toggle visibility function
    function togglePassword(id, icon) {
        const input = document.getElementById(id);
        const iTag = icon.querySelector('i');
        if (input.type === "password") {
            input.type = "text";
            iTag.classList.replace('fa-eye-slash', 'fa-eye');
        } else {
            input.type = "password";
            iTag.classList.replace('fa-eye', 'fa-eye-slash');
        }
    }
</script>

<script src="https://kit.fontawesome.com/0dcd9efbbc.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>