<?php require_once ROOT_PATH . '/app/views/layouts/header.php'; ?>

<style>
    .signup-container {
        max-width: 700px;
        margin: 50px auto;
    }
    .step-indicator {
        display: flex;
        justify-content: space-between;
        margin-bottom: 30px;
        position: relative;
    }
    .step-indicator::before {
        content: '';
        position: absolute;
        top: 15px;
        left: 0;
        right: 0;
        height: 2px;
        background: #e0e0e0;
        z-index: -1;
    }
    .step {
        text-align: center;
        flex: 1;
        position: relative;
    }
    .step-circle {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background: #e0e0e0;
        color: #666;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        margin-bottom: 5px;
        position: relative;
        z-index: 1;
    }
    .step.active .step-circle {
        background: #198754;
        color: white;
        box-shadow: 0 2px 8px rgba(25, 135, 84, 0.3);
    }
    .step.completed .step-circle {
        background: #198754;
        color: white;
    }
    .step-label {
        font-size: 11px;
        color: #666;
        display: block;
    }
    .step-content {
        display: none;
    }
    .step-content.active {
        display: block;
    }
    .form-section {
        background: white;
        padding: 30px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .section-title {
        font-size: 24px;
        font-weight: 600;
        margin-bottom: 10px;
        color: #333;
    }
    .section-subtitle {
        color: #666;
        margin-bottom: 25px;
        font-size: 14px;
    }
    .btn-navigation {
        margin-top: 20px;
    }
    .account-type-card {
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        padding: 20px;
        cursor: pointer;
        transition: all 0.3s;
        margin-bottom: 15px;
    }
    .account-type-card:hover {
        border-color: #198754;
        background: #f8f9fa;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .account-type-card.selected {
        border-color: #198754;
        background: #d1f2e6;
    }
    .account-type-card input[type="radio"] {
        margin-right: 10px;
    }
    .upload-box {
        border: 2px dashed #198754;
        border-radius: 8px;
        padding: 40px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s;
    }
    .upload-box:hover {
        background: #f8f9fa;
        border-color: #157347;
        transform: translateY(-2px);
    }
    .upload-box.has-file {
        border-color: #198754;
        background: #d1f2e6;
        border-style: solid;
    }
    .review-section {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 15px;
        border-left: 4px solid #198754;
    }
    .review-section h6 {
        color: #198754;
        margin-bottom: 10px;
        font-weight: 600;
    }
    .password-strength {
        height: 5px;
        border-radius: 3px;
        background: #e0e0e0;
        margin-top: 8px;
        margin-bottom: 10px;
        overflow: hidden;
    }
    .password-strength-bar {
        height: 100%;
        width: 0%;
        transition: all 0.3s;
        border-radius: 3px;
    }
    .strength-weak { background: #dc3545; width: 33%; }
    .strength-medium { background: #ffc107; width: 66%; }
    .strength-strong { background: #198754; width: 100%; }
    .password-requirements {
        font-size: 13px;
        margin-top: 10px;
    }
    .password-requirements li {
        margin-bottom: 5px;
    }
    .password-requirements li.valid {
        color: #198754;
    }
    .password-requirements li.invalid {
        color: #dc3545;
    }
    .btn-navigation button {
        min-width: 120px;
        font-weight: 500;
        padding: 10px 25px;
        border-radius: 5px;
        transition: all 0.3s ease;
    }
    .btn-navigation .btn-success {
        background-color: #198754;
        border-color: #198754;
    }
    .btn-navigation .btn-success:hover {
        background-color: #157347;
        border-color: #146c43;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(25, 135, 84, 0.3);
    }
    .btn-navigation .btn-outline-secondary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(108, 117, 125, 0.2);
    }
</style>

<div class="signup-container">
    <h3 class="text-center mb-4">Create Your Account</h3>
    
    <!-- Step Indicator -->
    <div class="step-indicator">
        <div class="step active" data-step="1">
            <span class="step-circle">1</span>
            <span class="step-label">Personal</span>
        </div>
        <div class="step" data-step="2">
            <span class="step-circle">2</span>
            <span class="step-label">Profile</span>
        </div>
        <div class="step" data-step="3">
            <span class="step-circle">3</span>
            <span class="step-label">Employment</span>
        </div>
        <div class="step" data-step="4">
            <span class="step-circle">4</span>
            <span class="step-label">Address</span>
        </div>
        <div class="step" data-step="5">
            <span class="step-circle">5</span>
            <span class="step-label">ID</span>
        </div>
        <div class="step" data-step="6">
            <span class="step-circle">6</span>
            <span class="step-label">Documents</span>
        </div>
        <div class="step" data-step="7">
            <span class="step-circle">7</span>
            <span class="step-label">Account</span>
        </div>
        <div class="step" data-step="8">
            <span class="step-circle">8</span>
            <span class="step-label">Review</span>
        </div>
    </div>

    <?php if (!empty($data['error'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($data['error']) ?></div>
    <?php endif; ?>

    <form id="signupForm" action="<?= URLROOT; ?>/auth/signup" method="POST" enctype="multipart/form-data">
        
        <!-- Step 1: Personal Details -->
        <div class="step-content active" data-step="1">
            <div class="form-section">
                <h4 class="section-title">Personal Details</h4>
                <p class="section-subtitle">Let's start with your basic information</p>
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">First Name *</label>
                        <input type="text" name="first_name" class="form-control" required 
                               value="<?= htmlspecialchars($data['first_name'] ?? '') ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Middle Name</label>
                        <input type="text" name="middle_name" class="form-control" 
                               value="<?= htmlspecialchars($data['middle_name'] ?? '') ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Last Name *</label>
                        <input type="text" name="last_name" class="form-control" required 
                               value="<?= htmlspecialchars($data['last_name'] ?? '') ?>">
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Email Address *</label>
                        <input type="email" name="email" id="email" class="form-control" required 
                               value="<?= htmlspecialchars($data['email'] ?? '') ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Phone Number *</label>
                        <div class="input-group">
                            <span class="input-group-text">+63</span>
                            <input type="tel" name="phone_number" id="phone_number" class="form-control" required 
                                   placeholder="XXX XXX XXXX" pattern="[0-9]{10}" maxlength="10"
                                   value="<?= htmlspecialchars($data['phone_number'] ?? '') ?>">
                        </div>
                        <small class="text-muted">Enter 10 digits (e.g., 9123456789)</small>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Create Password *</label>
                        <div class="input-group">
                            <input type="password" name="password" id="password" class="form-control" required 
                                   placeholder="Minimum 10 characters">
                            <span class="input-group-text bg-white" style="cursor: pointer;" onclick="togglePassword('password', this)">
                                <i class="fas fa-eye-slash"></i>
                            </span>
                        </div>
                        <div class="password-strength">
                            <div class="password-strength-bar" id="passwordStrengthBar"></div>
                        </div>
                        <small id="passwordStrengthText" class="text-muted">Password strength: None</small>
                        <ul class="password-requirements list-unstyled mt-2">
                            <li id="req-length" class="invalid"><i class="fas fa-times me-1"></i> At least 10 characters</li>
                            <li id="req-uppercase" class="invalid"><i class="fas fa-times me-1"></i> One uppercase letter</li>
                            <li id="req-lowercase" class="invalid"><i class="fas fa-times me-1"></i> One lowercase letter</li>
                            <li id="req-number" class="invalid"><i class="fas fa-times me-1"></i> One number</li>
                            <li id="req-special" class="invalid"><i class="fas fa-times me-1"></i> One special character (!@#$%^&*)</li>
                        </ul>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Confirm Password *</label>
                        <div class="input-group">
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control" required 
                                   placeholder="Re-enter password">
                            <span class="input-group-text bg-white" style="cursor: pointer;" onclick="togglePassword('confirm_password', this)">
                                <i class="fas fa-eye-slash"></i>
                            </span>
                        </div>
                        <ul class="password-requirements list-unstyled mt-2">
                            <li id="req-match" class="invalid"><i class="fas fa-times me-1"></i> Passwords match</li>
                        </ul>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Date of Birth *</label>
                        <input type="date" name="date_of_birth" class="form-control" required 
                               value="<?= htmlspecialchars($data['date_of_birth'] ?? '') ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 2: Personal Details Continued -->
        <div class="step-content" data-step="2">
            <div class="form-section">
                <h4 class="section-title">Additional Information</h4>
                <p class="section-subtitle">Tell us more about yourself</p>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Gender *</label>
                        <select name="gender_id" class="form-control" required>
                            <option value="">Select Gender</option>
                            <?php if (!empty($data['genders'])): ?>
                                <?php foreach ($data['genders'] as $gender): ?>
                                    <option value="<?= $gender->gender_id ?>" 
                                            <?= (($data['gender_id'] ?? '') == $gender->gender_id) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($gender->gender_name) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Marital Status *</label>
                        <select name="marital_status" class="form-control" required>
                            <option value="">Select Status</option>
                            <option value="Single" <?= (($data['marital_status'] ?? '') == 'Single') ? 'selected' : '' ?>>Single</option>
                            <option value="Married" <?= (($data['marital_status'] ?? '') == 'Married') ? 'selected' : '' ?>>Married</option>
                            <option value="Divorced" <?= (($data['marital_status'] ?? '') == 'Divorced') ? 'selected' : '' ?>>Divorced</option>
                            <option value="Widowed" <?= (($data['marital_status'] ?? '') == 'Widowed') ? 'selected' : '' ?>>Widowed</option>
                        </select>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Nationality *</label>
                    <input type="text" name="nationality" class="form-control" required 
                           value="<?= htmlspecialchars($data['nationality'] ?? 'Filipino') ?>">
                </div>
            </div>
        </div>

        <!-- Step 3: Employment Information -->
        <div class="step-content" data-step="3">
            <div class="form-section">
                <h4 class="section-title">Employment Information</h4>
                <p class="section-subtitle">Share your employment details with us</p>
                
                <div class="mb-3">
                    <label class="form-label">Employment Status *</label>
                    <select name="employment_status" class="form-control" required>
                        <option value="">Select Status</option>
                        <option value="Employed" <?= (($data['employment_status'] ?? '') == 'Employed') ? 'selected' : '' ?>>Employed</option>
                        <option value="Self-Employed" <?= (($data['employment_status'] ?? '') == 'Self-Employed') ? 'selected' : '' ?>>Self-Employed</option>
                        <option value="Unemployed" <?= (($data['employment_status'] ?? '') == 'Unemployed') ? 'selected' : '' ?>>Unemployed</option>
                        <option value="Student" <?= (($data['employment_status'] ?? '') == 'Student') ? 'selected' : '' ?>>Student</option>
                        <option value="Retired" <?= (($data['employment_status'] ?? '') == 'Retired') ? 'selected' : '' ?>>Retired</option>
                    </select>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Occupation</label>
                        <input type="text" name="occupation" class="form-control" 
                               value="<?= htmlspecialchars($data['occupation'] ?? '') ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Company Name</label>
                        <input type="text" name="company_name" class="form-control" 
                               value="<?= htmlspecialchars($data['company_name'] ?? '') ?>">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Annual Income Range *</label>
                    <select name="income_range" class="form-control" required>
                        <option value="">Select Range</option>
                        <option value="Below 250,000" <?= (($data['income_range'] ?? '') == 'Below 250,000') ? 'selected' : '' ?>>Below ₱250,000</option>
                        <option value="250,000 - 500,000" <?= (($data['income_range'] ?? '') == '250,000 - 500,000') ? 'selected' : '' ?>>₱250,000 - ₱500,000</option>
                        <option value="500,000 - 1,000,000" <?= (($data['income_range'] ?? '') == '500,000 - 1,000,000') ? 'selected' : '' ?>>₱500,000 - ₱1,000,000</option>
                        <option value="Above 1,000,000" <?= (($data['income_range'] ?? '') == 'Above 1,000,000') ? 'selected' : '' ?>>Above ₱1,000,000</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Step 4: Address Details -->
        <div class="step-content" data-step="4">
            <div class="form-section">
                <h4 class="section-title">Where do you live?</h4>
                <p class="section-subtitle">Help us know your current address</p>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Province *</label>
                        <select name="province_id" id="province_id" class="form-control" required>
                            <option value="">Select Province</option>
                            <?php if (!empty($data['provinces'])): ?>
                                <?php foreach ($data['provinces'] as $province): ?>
                                    <option value="<?= $province->province_id ?>" 
                                            <?= (($data['province_id'] ?? '') == $province->province_id) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($province->province_name) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">City / Municipality *</label>
                        <select name="city_id" id="city_id" class="form-control" required>
                            <option value="">Select City</option>
                        </select>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Barangay *</label>
                        <select name="barangay_id" id="barangay_id" class="form-control" required>
                            <option value="">Select Barangay</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Postal Code</label>
                        <input type="text" name="postal_code" class="form-control" 
                               value="<?= htmlspecialchars($data['postal_code'] ?? '') ?>">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Street Address *</label>
                    <input type="text" name="address_line" class="form-control" required 
                           placeholder="House/Unit/Bldg No., Street Name" 
                           value="<?= htmlspecialchars($data['address_line'] ?? '') ?>">
                </div>
            </div>
        </div>

        <!-- Step 5: ID Verification -->
        <div class="step-content" data-step="5">
            <div class="form-section">
                <h4 class="section-title">Verify your Identity</h4>
                <p class="section-subtitle">Please provide a valid government-issued ID for security purposes</p>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">ID Type *</label>
                        <select name="id_type_id" class="form-control" required>
                            <option value="">Select ID Type</option>
                            <option value="1" <?= (($data['id_type_id'] ?? '') == '1') ? 'selected' : '' ?>>National ID</option>
                            <option value="2" <?= (($data['id_type_id'] ?? '') == '2') ? 'selected' : '' ?>>Passport</option>
                            <option value="3" <?= (($data['id_type_id'] ?? '') == '3') ? 'selected' : '' ?>>Driver's License</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">ID Number *</label>
                        <input type="text" name="id_number" class="form-control" required 
                               value="<?= htmlspecialchars($data['id_number'] ?? '') ?>">
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Issue Date</label>
                        <input type="date" name="id_issue_date" class="form-control" 
                               value="<?= htmlspecialchars($data['id_issue_date'] ?? '') ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Expiration Date</label>
                        <input type="date" name="id_expiration_date" class="form-control" 
                               value="<?= htmlspecialchars($data['id_expiration_date'] ?? '') ?>">
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Upload ID (Front) *</label>
                        <div class="upload-box" id="id_front_box" onclick="document.getElementById('id_front').click()">
                            <i class="bi bi-cloud-upload" style="font-size: 2rem; color: #198754;"></i>
                            <p class="mb-0 mt-2">Click to upload</p>
                            <small class="text-muted">Max 5MB</small>
                        </div>
                        <input type="file" name="id_front" id="id_front" class="d-none" accept="image/*">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Upload ID (Back)</label>
                        <div class="upload-box" id="id_back_box" onclick="document.getElementById('id_back').click()">
                            <i class="bi bi-cloud-upload" style="font-size: 2rem; color: #198754;"></i>
                            <p class="mb-0 mt-2">Click to upload</p>
                            <small class="text-muted">Max 5MB</small>
                        </div>
                        <input type="file" name="id_back" id="id_back" class="d-none" accept="image/*">
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 6: Documents & Bio -->
        <div class="step-content" data-step="6">
            <div class="form-section">
                <h4 class="section-title">Let's secure your account</h4>
                <p class="section-subtitle">Upload your profile photo and digital signature for identification</p>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Profile Picture *</label>
                        <div class="upload-box" id="profile_pic_box" onclick="document.getElementById('profile_picture').click()">
                            <i class="bi bi-person-circle" style="font-size: 2rem; color: #198754;"></i>
                            <p class="mb-0 mt-2">Upload Photo</p>
                            <small class="text-muted">Max 5MB</small>
                        </div>
                        <input type="file" name="profile_picture" id="profile_picture" class="d-none" accept="image/*">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Digital Signature *</label>
                        <div class="upload-box" id="signature_box" onclick="document.getElementById('signature').click()">
                            <i class="bi bi-pen" style="font-size: 2rem; color: #198754;"></i>
                            <p class="mb-0 mt-2">Upload Signature</p>
                            <small class="text-muted">Max 5MB</small>
                        </div>
                        <input type="file" name="signature" id="signature" class="d-none" accept="image/*">
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 7: Account Selection -->
        <div class="step-content" data-step="7">
            <div class="form-section">
                <h4 class="section-title">Choose your account type</h4>
                <p class="section-subtitle">Select the account that best suits your needs</p>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="account-type-card" onclick="selectAccountType(1, this)">
                            <input type="radio" name="account_type_id" value="1" id="account_savings" required>
                            <label for="account_savings" style="cursor: pointer; margin: 0;">
                                <h5 class="mb-1">Savings Account</h5>
                                <p class="mb-2 text-muted small">₱2,000 minimum balance</p>
                                <p class="mb-0 small">Perfect for saving money with interest</p>
                            </label>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="account-type-card" onclick="selectAccountType(2, this)">
                            <input type="radio" name="account_type_id" value="2" id="account_junior" required>
                            <label for="account_junior" style="cursor: pointer; margin: 0;">
                                <h5 class="mb-1">Junior Savings</h5>
                                <p class="mb-2 text-muted small">₱0 minimum balance</p>
                                <p class="mb-0 small">Savings for minors (requires parent/guardian)</p>
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3 mt-4">
                    <h6>Account Configuration</h6>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="wants_passbook" id="wants_passbook" value="1">
                        <label class="form-check-label" for="wants_passbook">
                            I want a Passbook
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="wants_atm_card" id="wants_atm_card" value="1">
                        <label class="form-check-label" for="wants_atm_card">
                            I want an ATM/Debit Card
                        </label>
                    </div>
                </div>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Note:</strong> Initial deposit will be made when you visit the nearest branch for account activation. Online registration is free.
                </div>
            </div>
        </div>

        <!-- Step 8: Review & Submit -->
        <div class="step-content" data-step="8">
            <div class="form-section">
                <h4 class="section-title">Review your Application</h4>
                <p class="section-subtitle">Please review your information before submitting</p>
                
                <div class="review-section">
                    <h6><i class="bi bi-person"></i> Personal Details</h6>
                    <p class="mb-1"><strong>Name:</strong> <span id="review_name"></span></p>
                    <p class="mb-1"><strong>Email:</strong> <span id="review_email"></span></p>
                    <p class="mb-1"><strong>Phone:</strong> <span id="review_phone"></span></p>
                    <p class="mb-0"><strong>Date of Birth:</strong> <span id="review_dob"></span></p>
                </div>
                
                <div class="review-section">
                    <h6><i class="bi bi-briefcase"></i> Employment</h6>
                    <p class="mb-1"><strong>Status:</strong> <span id="review_employment"></span></p>
                    <p class="mb-0"><strong>Income Range:</strong> <span id="review_income"></span></p>
                </div>
                
                <div class="review-section">
                    <h6><i class="bi bi-geo-alt"></i> Address</h6>
                    <p class="mb-0"><span id="review_address"></span></p>
                </div>
                
                <div class="review-section">
                    <h6><i class="bi bi-bank"></i> Account Type</h6>
                    <p class="mb-0"><strong>Type:</strong> <span id="review_account_type"></span></p>
                </div>
                
                <div class="mt-4">
                    <h6>Terms & Conditions</h6>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="terms_accepted" id="terms_accepted" required>
                        <label class="form-check-label" for="terms_accepted">
                            I agree to the <a href="#" class="text-success fw-semibold" data-bs-toggle="modal" data-bs-target="#termsModal" onclick="event.preventDefault()">General Banking Terms and Conditions</a>
                        </label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="privacy_accepted" id="privacy_accepted" required>
                        <label class="form-check-label" for="privacy_accepted">
                            I agree to the <a href="#" class="text-success fw-semibold" data-bs-toggle="modal" data-bs-target="#privacyModal" onclick="event.preventDefault()">Data Privacy Consent</a>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Buttons -->
        <div class="btn-navigation d-flex justify-content-between">
            <button type="button" class="btn btn-outline-secondary" id="prevBtn" onclick="changeStep(-1)" style="display: none;">
                <i class="bi bi-arrow-left"></i> Previous
            </button>
            <button type="button" class="btn btn-success" id="nextBtn" onclick="changeStep(1)">
                Next <i class="bi bi-arrow-right"></i>
            </button>
            <button type="submit" class="btn btn-success" id="submitBtn" style="display: none;">
                <i class="bi bi-check-circle"></i> Submit Application
            </button>
        </div>
    </form>
    
    <div class="text-center mt-3">
        <p class="mb-0">Already have an account? <a href="<?= URLROOT; ?>/auth/login" class="text-success fw-semibold">Login</a></p>
    </div>
</div>

<!-- Terms and Conditions Modal -->
<div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="termsModalLabel">General Banking Terms and Conditions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6>1. Account Opening and Maintenance</h6>
                <p>By opening an account with us, you agree to provide accurate and complete information. You must maintain the minimum balance requirements for your account type to avoid fees.</p>
                
                <h6>2. Deposits and Withdrawals</h6>
                <p>All deposits are subject to verification and clearing. Withdrawals may be subject to daily limits as per account type and regulatory requirements.</p>
                
                <h6>3. Interest Rates</h6>
                <p>Interest rates are subject to change without prior notice. Current rates will be posted on our website and at all branches.</p>
                
                <h6>4. Fees and Charges</h6>
                <p>Various fees may apply including but not limited to: dormancy fees, below minimum balance fees, and transaction fees. A complete schedule of fees is available upon request.</p>
                
                <h6>5. Security</h6>
                <p>You are responsible for maintaining the confidentiality of your account credentials. Report any unauthorized transactions immediately.</p>
                
                <h6>6. Account Closure</h6>
                <p>Either party may close the account with proper notice. Any remaining balance will be refunded according to our standard procedures.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Privacy Policy Modal -->
<div class="modal fade" id="privacyModal" tabindex="-1" aria-labelledby="privacyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="privacyModalLabel">Data Privacy Consent</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6>Data Collection and Usage</h6>
                <p>We collect and process your personal information in accordance with the Data Privacy Act. Your information will be used for:</p>
                <ul>
                    <li>Account management and maintenance</li>
                    <li>Transaction processing</li>
                    <li>Compliance with legal and regulatory requirements</li>
                    <li>Communication regarding your account</li>
                    <li>Fraud prevention and security</li>
                </ul>
                
                <h6>Information Sharing</h6>
                <p>We do not sell your personal information. We may share your data with:</p>
                <ul>
                    <li>Regulatory authorities as required by law</li>
                    <li>Service providers who assist in our operations</li>
                    <li>Credit bureaus for credit-related products</li>
                </ul>
                
                <h6>Your Rights</h6>
                <p>You have the right to:</p>
                <ul>
                    <li>Access your personal information</li>
                    <li>Correct inaccurate data</li>
                    <li>Request deletion of your data (subject to legal requirements)</li>
                    <li>Object to certain processing activities</li>
                </ul>
                
                <h6>Data Security</h6>
                <p>We implement appropriate technical and organizational measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
let currentStep = 1;
const totalSteps = 8;

// Load cities and barangays data
const citiesData = <?= json_encode($data['cities'] ?? []) ?>;
const barangaysData = <?= json_encode($data['barangays'] ?? []) ?>;

// Password validation
const passwordInput = document.getElementById('password');
const confirmPasswordInput = document.getElementById('confirm_password');

function checkPasswordRequirements() {
    const pwd = passwordInput.value;
    const confirmPwd = confirmPasswordInput.value;
    
    // Check requirements
    const requirements = {
        length: pwd.length >= 10,
        uppercase: /[A-Z]/.test(pwd),
        lowercase: /[a-z]/.test(pwd),
        number: /\d/.test(pwd),
        special: /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(pwd)
    };
    
    // Update requirement indicators
    updateRequirement('req-length', requirements.length, 'At least 10 characters');
    updateRequirement('req-uppercase', requirements.uppercase, 'One uppercase letter');
    updateRequirement('req-lowercase', requirements.lowercase, 'One lowercase letter');
    updateRequirement('req-number', requirements.number, 'One number');
    updateRequirement('req-special', requirements.special, 'One special character (!@#$%^&*)');
    
    // Check password match
    if (pwd && confirmPwd) {
        updateRequirement('req-match', pwd === confirmPwd, 'Passwords match');
    }
    
    // Calculate password strength
    let strength = 0;
    if (requirements.length) strength++;
    if (requirements.uppercase) strength++;
    if (requirements.lowercase) strength++;
    if (requirements.number) strength++;
    if (requirements.special) strength++;
    
    // Update strength bar
    const strengthBar = document.getElementById('passwordStrengthBar');
    const strengthText = document.getElementById('passwordStrengthText');
    strengthBar.className = 'password-strength-bar';
    
    if (strength === 0) {
        strengthText.textContent = 'Password strength: None';
        strengthText.className = 'text-muted';
    } else if (strength <= 2) {
        strengthBar.classList.add('strength-weak');
        strengthText.textContent = 'Password strength: Weak';
        strengthText.className = 'text-danger';
    } else if (strength <= 4) {
        strengthBar.classList.add('strength-medium');
        strengthText.textContent = 'Password strength: Medium';
        strengthText.className = 'text-warning';
    } else {
        strengthBar.classList.add('strength-strong');
        strengthText.textContent = 'Password strength: Strong';
        strengthText.className = 'text-success';
    }
}

function updateRequirement(id, condition, text) {
    const element = document.getElementById(id);
    if (condition) {
        element.className = 'valid';
        element.innerHTML = `<i class="fas fa-check me-1"></i> ${text}`;
    } else {
        element.className = 'invalid';
        element.innerHTML = `<i class="fas fa-times me-1"></i> ${text}`;
    }
}

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

passwordInput.addEventListener('input', checkPasswordRequirements);
confirmPasswordInput.addEventListener('input', checkPasswordRequirements);

// Phone number formatting - only allow numbers
document.getElementById('phone_number').addEventListener('input', function(e) {
    this.value = this.value.replace(/\D/g, '').substring(0, 10);
});

// Province change handler
document.getElementById('province_id').addEventListener('change', function() {
    const provinceId = this.value;
    const citySelect = document.getElementById('city_id');
    const barangaySelect = document.getElementById('barangay_id');
    
    citySelect.innerHTML = '<option value="">Select City</option>';
    barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
    
    if (provinceId) {
        const cities = citiesData.filter(city => city.province_id == provinceId);
        cities.forEach(city => {
            const option = document.createElement('option');
            option.value = city.city_id;
            option.textContent = city.city_name;
            citySelect.appendChild(option);
        });
    }
});

// City change handler
document.getElementById('city_id').addEventListener('change', function() {
    const cityId = this.value;
    const barangaySelect = document.getElementById('barangay_id');
    
    barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
    
    if (cityId) {
        const barangays = barangaysData.filter(brgy => brgy.city_id == cityId);
        barangays.forEach(barangay => {
            const option = document.createElement('option');
            option.value = barangay.barangay_id;
            option.textContent = barangay.barangay_name;
            barangaySelect.appendChild(option);
        });
    }
});

// File upload handlers
['id_front', 'id_back', 'profile_picture', 'signature'].forEach(id => {
    document.getElementById(id).addEventListener('change', function(e) {
        const box = document.getElementById(id + '_box');
        if (this.files.length > 0) {
            box.classList.add('has-file');
            const fileName = this.files[0].name;
            box.querySelector('p').textContent = fileName;
        }
    });
});

function selectAccountType(type, element) {
    document.querySelectorAll('.account-type-card').forEach(card => {
        card.classList.remove('selected');
    });
    element.classList.add('selected');
    document.getElementById('account_' + (type === 1 ? 'savings' : 'junior')).checked = true;
}

function changeStep(direction) {
    const currentStepContent = document.querySelector(`.step-content[data-step="${currentStep}"]`);
    
    // Validate current step before proceeding
    if (direction > 0) {
        const inputs = currentStepContent.querySelectorAll('input[required], select[required]');
        let valid = true;
        
        inputs.forEach(input => {
            if (!input.value && input.type !== 'radio' && input.type !== 'checkbox') {
                input.classList.add('is-invalid');
                valid = false;
            } else if (input.type === 'radio') {
                const radioGroup = currentStepContent.querySelectorAll(`input[name="${input.name}"]`);
                const checked = Array.from(radioGroup).some(radio => radio.checked);
                if (!checked && input.hasAttribute('required')) {
                    valid = false;
                }
            } else {
                input.classList.remove('is-invalid');
            }
        });
        
        if (!valid) {
            alert('Please fill all required fields before proceeding.');
            return;
        }
    }
    
    // Hide current step
    currentStepContent.classList.remove('active');
    document.querySelector(`.step[data-step="${currentStep}"]`).classList.remove('active');
    if (direction > 0) {
        document.querySelector(`.step[data-step="${currentStep}"]`).classList.add('completed');
    }
    
    // Update step number
    currentStep += direction;
    
    // Show new step
    document.querySelector(`.step-content[data-step="${currentStep}"]`).classList.add('active');
    document.querySelector(`.step[data-step="${currentStep}"]`).classList.add('active');
    
    // Update buttons
    document.getElementById('prevBtn').style.display = currentStep === 1 ? 'none' : 'block';
    document.getElementById('nextBtn').style.display = currentStep === totalSteps ? 'none' : 'block';
    document.getElementById('submitBtn').style.display = currentStep === totalSteps ? 'block' : 'none';
    
    // If on review step, populate review
    if (currentStep === 8) {
        populateReview();
    }
    
    // Scroll to top
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function populateReview() {
    const form = document.getElementById('signupForm');
    const formData = new FormData(form);
    
    document.getElementById('review_name').textContent = 
        `${formData.get('first_name')} ${formData.get('middle_name')} ${formData.get('last_name')}`;
    document.getElementById('review_email').textContent = formData.get('email');
    document.getElementById('review_phone').textContent = '+63 ' + formData.get('phone_number');
    document.getElementById('review_dob').textContent = formData.get('date_of_birth');
    document.getElementById('review_employment').textContent = formData.get('employment_status');
    document.getElementById('review_income').textContent = formData.get('income_range');
    
    const province = document.getElementById('province_id').selectedOptions[0]?.text || '';
    const city = document.getElementById('city_id').selectedOptions[0]?.text || '';
    const barangay = document.getElementById('barangay_id').selectedOptions[0]?.text || '';
    const street = formData.get('address_line');
    document.getElementById('review_address').textContent = `${street}, ${barangay}, ${city}, ${province}`;
    
    const accountType = document.querySelector('input[name="account_type_id"]:checked')?.nextElementSibling?.querySelector('h5')?.textContent || '';
    document.getElementById('review_account_type').textContent = accountType;
}

// Form validation on submit
document.getElementById('signupForm').addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    if (password !== confirmPassword) {
        e.preventDefault();
        alert('Passwords do not match!');
        return false;
    }
    
    if (password.length < 10) {
        e.preventDefault();
        alert('Password must be at least 10 characters long!');
        return false;
    }
    
    if (!/[A-Z]/.test(password)) {
        e.preventDefault();
        alert('Password must contain at least one uppercase letter!');
        return false;
    }
    
    if (!/[a-z]/.test(password)) {
        e.preventDefault();
        alert('Password must contain at least one lowercase letter!');
        return false;
    }
    
    if (!/\d/.test(password)) {
        e.preventDefault();
        alert('Password must contain at least one number!');
        return false;
    }
    
    if (!/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)) {
        e.preventDefault();
        alert('Password must contain at least one special character (!@#$%^&* etc.)!');
        return false;
    }
});
</script>

<?php require_once ROOT_PATH . '/app/views/layouts/footer.php'; ?>
