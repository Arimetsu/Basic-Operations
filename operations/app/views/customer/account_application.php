<?php require_once ROOT_PATH . '/app/views/layouts/header.php'; ?>

<style>
    .application-container {
        max-width: 800px;
        margin: 50px auto;
        padding: 0 20px;
    }
    
    .step-indicator {
        display: flex;
        justify-content: center;
        gap: 80px;
        margin-bottom: 50px;
        padding: 0 20px;
    }
    
    .step {
        text-align: center;
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    
    .step-circle {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: #e9ecef;
        color: #6c757d;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 1.25rem;
        margin-bottom: 12px;
        position: relative;
        transition: all 0.3s ease;
        border: 3px solid #e9ecef;
    }
    
    .step.active .step-circle {
        background: #198754;
        color: white;
        border-color: #198754;
        box-shadow: 0 4px 12px rgba(25, 135, 84, 0.3);
    }
    
    .step.completed .step-circle {
        background: #198754;
        color: white;
        border-color: #198754;
    }
    
    .step-label {
        font-size: 0.9rem;
        color: #6c757d;
        font-weight: 500;
        margin-top: 4px;
    }
    
    .step.active .step-label {
        color: #198754;
        font-weight: 600;
    }
    
    .step-content {
        display: none;
        animation: fadeIn 0.4s ease;
    }
    
    .step-content.active {
        display: block;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .form-section {
        background: white;
        padding: 40px;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    }
    
    .section-title {
        font-size: 1.75rem;
        font-weight: 600;
        margin-bottom: 12px;
        color: #212529;
    }
    
    .section-subtitle {
        color: #6c757d;
        margin-bottom: 30px;
        font-size: 1rem;
    }
    
    .account-types-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 20px;
        margin-bottom: 20px;
    }
    
    .account-type-card {
        border: 2px solid #e9ecef;
        border-radius: 12px;
        padding: 24px;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
        background: white;
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    
    .account-type-card:hover {
        border-color: #198754;
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(0,0,0,0.1);
    }
    
    .account-type-card.selected {
        border-color: #198754;
        background: #d1f2e6;
        box-shadow: 0 4px 16px rgba(25, 135, 84, 0.25);
    }
    
    .account-type-card input[type="radio"] {
        position: absolute;
        opacity: 0;
    }
    
    .account-type-header {
        display: flex;
        align-items: center;
        margin-bottom: 12px;
    }
    
    .account-type-icon {
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, #198754, #146c43);
        color: white;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 16px;
        font-size: 1.75rem;
        box-shadow: 0 4px 8px rgba(25, 135, 84, 0.2);
    }
    
    .account-type-card.selected .account-type-icon {
        background: linear-gradient(135deg, #146c43, #0d5132);
        box-shadow: 0 6px 12px rgba(25, 135, 84, 0.3);
    }
    
    .account-type-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: #212529;
        margin: 0 0 8px 0;
    }
    
    .account-type-description {
        color: #6c757d;
        font-size: 0.85rem;
        margin-bottom: 16px;
        line-height: 1.4;
        flex-grow: 1;
    }
    
    .account-type-features {
        display: flex;
        flex-direction: column;
        gap: 8px;
        margin-top: auto;
    }
    
    .feature-badge {
        background: #f8f9fa;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 0.8rem;
        color: #495057;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    
    .feature-badge i {
        color: #198754;
    }
    
    .account-type-card.selected .feature-badge {
        background: rgba(25, 135, 84, 0.15);
        color: #146c43;
    }
    
    .service-option {
        background: #f8f9fa;
        border: 2px solid #dee2e6;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 16px;
        transition: all 0.3s ease;
    }
    
    .service-option:hover {
        border-color: #198754;
        background: white;
    }
    
    .service-option.selected {
        border-color: #198754;
        background: #d1f2e6;
    }
    
    .review-section {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 20px;
        border-left: 4px solid #198754;
    }
    
    .review-section h6 {
        color: #198754;
        margin-bottom: 12px;
        font-weight: 600;
        font-size: 1.1rem;
    }
    
    .review-item {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        border-bottom: 1px solid #dee2e6;
    }
    
    .review-item:last-child {
        border-bottom: none;
    }
    
    .review-label {
        color: #6c757d;
        font-weight: 500;
    }
    
    .review-value {
        color: #212529;
        font-weight: 600;
    }
    
    .btn-navigation {
        margin-top: 30px;
        display: flex;
        justify-content: space-between;
        gap: 16px;
    }
    
    .btn-navigation button {
        min-width: 140px;
        font-weight: 500;
        padding: 12px 28px;
        border-radius: 8px;
        transition: all 0.3s ease;
        font-size: 1rem;
    }
    
    .btn-navigation .btn-success {
        background-color: #198754;
        border-color: #198754;
    }
    
    .btn-navigation .btn-success:hover {
        background-color: #146c43;
        border-color: #13653f;
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(25, 135, 84, 0.3);
    }
    
    .btn-navigation .btn-outline-secondary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 10px rgba(108, 117, 125, 0.2);
    }
    
    .form-check-input:checked {
        background-color: #198754;
        border-color: #198754;
    }
    
    .deposit-input-group {
        max-width: 400px;
    }
</style>

<div class="application-container">
    <div class="text-center mb-4">
        <h2 class="fw-bold" style="color: #212529;">Apply for New Account</h2>
        <p class="text-muted">Complete the steps below to submit your application</p>
    </div>
    
    <!-- Step Indicator -->
    <div class="step-indicator">
        <div class="step active" data-step="1">
            <span class="step-circle">1</span>
            <div class="step-label">Account Type</div>
        </div>
        <div class="step" data-step="2">
            <span class="step-circle">2</span>
            <div class="step-label">Services</div>
        </div>
        <div class="step" data-step="3">
            <span class="step-circle">3</span>
            <div class="step-label">Review</div>
        </div>
    </div>

    <?php if (!empty($data['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <?= htmlspecialchars($data['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($data['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle me-2"></i>
            <?= htmlspecialchars($data['success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <form id="applicationForm" action="<?= URLROOT; ?>/customer/submitAccountApplication" method="POST">
        
        <!-- Step 1: Choose Account Type -->
        <div class="step-content active" data-step="1">
            <div class="form-section">
                <h4 class="section-title">Choose Your Account Type</h4>
                <p class="section-subtitle">Select the account type that best fits your needs</p>
                
                <?php if (!empty($data['account_types'])): ?>
                <div class="account-types-grid">
                    <?php foreach ($data['account_types'] as $type): ?>
                        <label class="account-type-card" data-type-id="<?= $type->account_type_id ?>">
                            <input type="radio" name="account_type_id" value="<?= $type->account_type_id ?>" required>
                            <div class="account-type-header">
                                <div class="account-type-icon">
                                    <?php if (stripos($type->type_name, 'savings') !== false): ?>
                                        <i class="bi bi-piggy-bank"></i>
                                    <?php elseif (stripos($type->type_name, 'current') !== false || stripos($type->type_name, 'checking') !== false): ?>
                                        <i class="bi bi-credit-card"></i>
                                    <?php elseif (stripos($type->type_name, 'time') !== false || stripos($type->type_name, 'deposit') !== false): ?>
                                        <i class="bi bi-clock-history"></i>
                                    <?php else: ?>
                                        <i class="bi bi-wallet2"></i>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <h5 class="account-type-title"><?= htmlspecialchars($type->type_name) ?></h5>
                                </div>
                            </div>
                            <p class="account-type-description"><?= htmlspecialchars($type->description ?? 'Banking account') ?></p>
                            <div class="account-type-features">
                                <span class="feature-badge">
                                    <i class="bi bi-percent me-1"></i>
                                    <?= number_format($type->base_interest_rate * 100, 2) ?>% Interest
                                </span>
                                <span class="feature-badge">
                                    <i class="bi bi-cash me-1"></i>
                                    Min: ₱<?= number_format($type->minimum_balance, 2) ?>
                                </span>
                                <?php if ($type->allows_passbook): ?>
                                    <span class="feature-badge">
                                        <i class="bi bi-book me-1"></i>
                                        Passbook Available
                                    </span>
                                <?php endif; ?>
                                <?php if ($type->allows_atm_card): ?>
                                    <span class="feature-badge">
                                        <i class="bi bi-credit-card-2-front me-1"></i>
                                        ATM Card Available
                                    </span>
                                <?php endif; ?>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-circle me-2"></i>
                        No account types available at the moment. Please contact support.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Step 2: Select Services -->
        <div class="step-content" data-step="2">
            <div class="form-section">
                <h4 class="section-title">Additional Services</h4>
                <p class="section-subtitle">Choose the services you'd like to add to your account</p>
                
                <div class="service-option" id="passbookOption">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="wants_passbook" id="wants_passbook" value="1">
                        <label class="form-check-label w-100" for="wants_passbook">
                            <div class="d-flex align-items-start">
                                <i class="bi bi-book fs-3 me-3 text-success"></i>
                                <div>
                                    <h6 class="mb-1 fw-bold">Passbook</h6>
                                    <p class="mb-0 text-muted small">Get a physical passbook to track your transactions and balance</p>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>
                
                <div class="service-option" id="atmOption">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="wants_atm_card" id="wants_atm_card" value="1">
                        <label class="form-check-label w-100" for="wants_atm_card">
                            <div class="d-flex align-items-start">
                                <i class="bi bi-credit-card-2-front fs-3 me-3 text-success"></i>
                                <div>
                                    <h6 class="mb-1 fw-bold">ATM/Debit Card</h6>
                                    <p class="mb-0 text-muted small">Access your funds anytime with an ATM/debit card</p>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 3: Review & Submit -->
        <div class="step-content" data-step="3">
            <div class="form-section">
                <h4 class="section-title">Review Your Application</h4>
                <p class="section-subtitle">Please review your information before submitting</p>
                
                <div class="alert alert-info mb-4">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Important:</strong> After your application is approved, please visit any of our branches to make your initial deposit and activate your account.
                </div>
                
                <div class="review-section">
                    <h6><i class="bi bi-bank me-2"></i>Account Details</h6>
                    <div class="review-item">
                        <span class="review-label">Account Type:</span>
                        <span class="review-value" id="review-account-type">-</span>
                    </div>
                </div>
                
                <div class="review-section">
                    <h6><i class="bi bi-gear me-2"></i>Selected Services</h6>
                    <div class="review-item">
                        <span class="review-label">Passbook:</span>
                        <span class="review-value" id="review-passbook">No</span>
                    </div>
                    <div class="review-item">
                        <span class="review-label">ATM/Debit Card:</span>
                        <span class="review-value" id="review-atm">No</span>
                    </div>
                </div>
                
                <div class="review-section">
                    <h6><i class="bi bi-person me-2"></i>Applicant Information</h6>
                    <div class="review-item">
                        <span class="review-label">Name:</span>
                        <span class="review-value"><?= htmlspecialchars($data['full_name'] ?? 'N/A') ?></span>
                    </div>
                    <div class="review-item">
                        <span class="review-label">Email:</span>
                        <span class="review-value"><?= htmlspecialchars($data['email'] ?? 'N/A') ?></span>
                    </div>
                    <div class="review-item">
                        <span class="review-label">Phone:</span>
                        <span class="review-value"><?= htmlspecialchars($data['phone'] ?? 'N/A') ?></span>
                    </div>
                </div>
                
                <div class="mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="accept_terms" name="accept_terms" required>
                        <label class="form-check-label" for="accept_terms">
                            I accept the <a href="#" class="text-success" data-bs-toggle="modal" data-bs-target="#termsModal">Terms and Conditions</a> and <a href="#" class="text-success" data-bs-toggle="modal" data-bs-target="#privacyModal">Privacy Policy</a> *
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Buttons -->
        <div class="btn-navigation">
            <button type="button" class="btn btn-outline-secondary" id="prevBtn" onclick="changeStep(-1)" style="display: none;">
                <i class="bi bi-arrow-left me-2"></i>Previous
            </button>
            <div style="flex: 1;"></div>
            <button type="button" class="btn btn-success" id="nextBtn" onclick="changeStep(1)">
                Next<i class="bi bi-arrow-right ms-2"></i>
            </button>
            <button type="submit" class="btn btn-success" id="submitBtn" style="display: none;">
                <i class="bi bi-check-circle me-2"></i>Submit Application
            </button>
        </div>
    </form>
</div>

<!-- Terms and Conditions Modal -->
<div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #198754; color: white;">
                <h5 class="modal-title" id="termsModalLabel"><i class="bi bi-file-text me-2"></i>Terms and Conditions</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6 class="fw-bold mb-3">1. Account Opening</h6>
                <p>By submitting this application, you agree to open an account with our institution subject to approval and compliance with all applicable laws and regulations.</p>
                
                <h6 class="fw-bold mb-3 mt-4">2. Account Activation</h6>
                <p>Upon approval of your application, you must visit a physical branch to make the initial deposit and complete the account activation process. Your account will not be active until the initial deposit meeting the minimum balance requirement is made.</p>
                
                <h6 class="fw-bold mb-3 mt-4">3. Account Maintenance</h6>
                <p>You agree to maintain the minimum balance requirement for your selected account type. Failure to maintain the minimum balance may result in service fees or account restrictions.</p>
                
                <h6 class="fw-bold mb-3 mt-4">4. Services and Fees</h6>
                <p>Additional services such as passbook and ATM/debit cards may be subject to issuance fees and replacement fees as per our current fee schedule. Interest rates and fees are subject to change with prior notice.</p>
                
                <h6 class="fw-bold mb-3 mt-4">5. Account Security</h6>
                <p>You are responsible for maintaining the confidentiality of your account information, PIN, passwords, and any security credentials. Notify us immediately of any unauthorized transactions or security breaches.</p>
                
                <h6 class="fw-bold mb-3 mt-4">6. Compliance</h6>
                <p>You agree to provide accurate and complete information and to update your information as needed. You also agree to comply with all anti-money laundering and know-your-customer requirements.</p>
                
                <h6 class="fw-bold mb-3 mt-4">7. Account Closure</h6>
                <p>Either party may close the account with appropriate notice. Upon closure, any remaining balance will be returned to you after deduction of applicable fees and charges.</p>
                
                <h6 class="fw-bold mb-3 mt-4">8. Dispute Resolution</h6>
                <p>Any disputes arising from this account shall be resolved through arbitration in accordance with applicable laws and regulations.</p>
                
                <div class="alert alert-info mt-4">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Last Updated:</strong> February 21, 2026
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" data-bs-dismiss="modal">I Understand</button>
            </div>
        </div>
    </div>
</div>

<!-- Privacy Policy Modal -->
<div class="modal fade" id="privacyModal" tabindex="-1" aria-labelledby="privacyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #198754; color: white;">
                <h5 class="modal-title" id="privacyModalLabel"><i class="bi bi-shield-check me-2"></i>Privacy Policy</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6 class="fw-bold mb-3">1. Information We Collect</h6>
                <p>We collect personal information including but not limited to: name, address, contact details, date of birth, government-issued ID information, employment details, and financial information necessary for account opening and maintenance.</p>
                
                <h6 class="fw-bold mb-3 mt-4">2. How We Use Your Information</h6>
                <p>Your information is used to:</p>
                <ul>
                    <li>Process your account application</li>
                    <li>Verify your identity and comply with regulations</li>
                    <li>Maintain and service your account</li>
                    <li>Communicate with you regarding your account</li>
                    <li>Detect and prevent fraud</li>
                    <li>Comply with legal and regulatory requirements</li>
                </ul>
                
                <h6 class="fw-bold mb-3 mt-4">3. Information Sharing</h6>
                <p>We do not sell your personal information. We may share your information with:</p>
                <ul>
                    <li>Regulatory authorities as required by law</li>
                    <li>Service providers who assist in our operations</li>
                    <li>Credit bureaus for credit reporting purposes</li>
                    <li>Law enforcement when legally required</li>
                </ul>
                
                <h6 class="fw-bold mb-3 mt-4">4. Data Security</h6>
                <p>We implement industry-standard security measures to protect your personal information from unauthorized access, disclosure, alteration, or destruction. This includes encryption, secure servers, and access controls.</p>
                
                <h6 class="fw-bold mb-3 mt-4">5. Your Rights</h6>
                <p>You have the right to:</p>
                <ul>
                    <li>Access your personal information</li>
                    <li>Request correction of inaccurate information</li>
                    <li>Request deletion of your information (subject to legal requirements)</li>
                    <li>Opt-out of marketing communications</li>
                    <li>File a complaint with relevant authorities</li>
                </ul>
                
                <h6 class="fw-bold mb-3 mt-4">6. Data Retention</h6>
                <p>We retain your personal information for as long as necessary to fulfill the purposes outlined in this policy and as required by applicable laws and regulations.</p>
                
                <h6 class="fw-bold mb-3 mt-4">7. Cookies and Tracking</h6>
                <p>Our online banking platform may use cookies and similar technologies to enhance user experience and maintain security. You can control cookie settings through your browser.</p>
                
                <h6 class="fw-bold mb-3 mt-4">8. Contact Us</h6>
                <p>If you have questions about this Privacy Policy or wish to exercise your rights, please contact our Data Protection Officer through our customer service channels.</p>
                
                <div class="alert alert-info mt-4">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Last Updated:</strong> February 21, 2026
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" data-bs-dismiss="modal">I Understand</button>
            </div>
        </div>
    </div>
</div>

<script>
let currentStep = 1;
const totalSteps = 3;
let selectedAccountType = null;
let accountTypesData = <?= json_encode($data['account_types'] ?? []) ?>;

// Account type selection
document.querySelectorAll('.account-type-card').forEach(card => {
    card.addEventListener('click', function() {
        document.querySelectorAll('.account-type-card').forEach(c => c.classList.remove('selected'));
        this.classList.add('selected');
        this.querySelector('input[type="radio"]').checked = true;
        
        const typeId = this.dataset.typeId;
        selectedAccountType = accountTypesData.find(t => t.account_type_id == typeId);
        
        // Update service availability
        if (selectedAccountType) {
            if (!selectedAccountType.allows_passbook) {
                document.getElementById('passbookOption').style.opacity = '0.5';
                document.getElementById('wants_passbook').disabled = true;
                document.getElementById('wants_passbook').checked = false;
            } else {
                document.getElementById('passbookOption').style.opacity = '1';
                document.getElementById('wants_passbook').disabled = false;
            }
            
            if (!selectedAccountType.allows_atm_card) {
                document.getElementById('atmOption').style.opacity = '0.5';
                document.getElementById('wants_atm_card').disabled = true;
                document.getElementById('wants_atm_card').checked = false;
            } else {
                document.getElementById('atmOption').style.opacity = '1';
                document.getElementById('wants_atm_card').disabled = false;
            }
        }
    });
});

// Service option styling
document.querySelectorAll('.service-option input').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        if (this.checked) {
            this.closest('.service-option').classList.add('selected');
        } else {
            this.closest('.service-option').classList.remove('selected');
        }
    });
});

function changeStep(direction) {
    // Validate current step
    if (direction === 1 && !validateStep(currentStep)) {
        return;
    }
    
    // Hide current step
    document.querySelector(`.step-content[data-step="${currentStep}"]`).classList.remove('active');
    document.querySelector(`.step[data-step="${currentStep}"]`).classList.remove('active');
    if (direction === 1) {
        document.querySelector(`.step[data-step="${currentStep}"]`).classList.add('completed');
    }
    
    // Update step number
    currentStep += direction;
    
    // Show new step
    document.querySelector(`.step-content[data-step="${currentStep}"]`).classList.add('active');
    document.querySelector(`.step[data-step="${currentStep}"]`).classList.add('active');
    
    // Update buttons
    document.getElementById('prevBtn').style.display = currentStep === 1 ? 'none' : 'inline-block';
    document.getElementById('nextBtn').style.display = currentStep === totalSteps ? 'none' : 'inline-block';
    document.getElementById('submitBtn').style.display = currentStep === totalSteps ? 'inline-block' : 'none';
    
    // Update review section if on step 3 (review step)
    if (currentStep === 3) {
        updateReview();
    }
    
    // Scroll to top
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function validateStep(step) {
    if (step === 1) {
        const selected = document.querySelector('input[name="account_type_id"]:checked');
        if (!selected) {
            alert('Please select an account type');
            return false;
        }
    }
    
    return true;
}

function updateReview() {
    // Account type
    if (selectedAccountType) {
        document.getElementById('review-account-type').textContent = selectedAccountType.type_name;
    }
    
    // Services
    document.getElementById('review-passbook').textContent = document.getElementById('wants_passbook').checked ? 'Yes' : 'No';
    document.getElementById('review-atm').textContent = document.getElementById('wants_atm_card').checked ? 'Yes' : 'No';
}

// Form submission
document.getElementById('applicationForm').addEventListener('submit', function(e) {
    if (currentStep !== totalSteps) {
        e.preventDefault();
        return;
    }
    
    if (!document.getElementById('accept_terms').checked) {
        e.preventDefault();
        alert('Please accept the Terms and Conditions');
        return;
    }
});

// Auto-dismiss alerts
setTimeout(() => {
    document.querySelectorAll('.alert').forEach(alert => {
        alert.style.transition = 'opacity 0.5s ease';
        alert.style.opacity = '0';
        setTimeout(() => alert.remove(), 500);
    });
}, 5000);
</script>

<?php require_once ROOT_PATH . '/app/views/layouts/footer.php'; ?>
