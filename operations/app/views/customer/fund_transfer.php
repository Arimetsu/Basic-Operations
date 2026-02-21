<?php require_once ROOT_PATH . '/app/views/layouts/header.php'; ?>

<style>
.transfer-card {
    transition: all 0.3s ease;
    cursor: pointer;
    border: 2px solid transparent;
}
.transfer-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 16px rgba(0,0,0,0.1) !important;
}
.transfer-card.selected {
    border-color: #198754;
    background-color: #f0f9f4;
}
.step-indicator {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    background: #e9ecef;
    color: #6c757d;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}
.step-indicator.active {
    background: #198754;
    color: white;
}
.step-indicator.completed {
    background: #146c43;
    color: white;
}
</style>

<!-- Error Messages -->
<?php if (!empty($data['from_account_error']) || !empty($data['recipient_number_error']) || !empty($data['recipient_name_error']) || !empty($data['amount_error']) || !empty($data['message_error']) || !empty($data['other_error']) || !empty($data['bank_name_error']) || !empty($data['bank_accountnumber_error']) || !empty($data['bank_accountname_error']) || !empty($data['bank_code_error'])): ?>
    <div class="position-fixed top-0 start-50 translate-middle-x p-3" style="z-index: 9999; margin-top: 80px;">
        <?php foreach(['from_account_error', 'recipient_number_error', 'recipient_name_error', 'amount_error', 'message_error', 'other_error', 'bank_name_error', 'bank_account_number_error', 'bank_account_name_error', 'bank_code_error'] as $error_key): ?>
            <?php if (!empty($data[$error_key])): ?>
                <div class="alert alert-danger alert-dismissible fade show shadow-sm">
                    <?= $data[$error_key]; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="container-fluid py-4" style="background-color: #f8f9fa; min-height: 100vh;">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10 col-xl-8">
            <!-- Header -->
            <div class="text-center mb-4">
                <h3 class="fw-bold" style="color: #003631;">
                    <i class="bi bi-arrow-left-right me-2"></i>Fund Transfer
                </h3>
                <p class="text-muted">Send money quickly and securely</p>
            </div>

            <!-- Step 1: Select Transfer Type -->
            <div id="step1" class="mb-4">
                <div class="text-center mb-3">
                    <div class="d-inline-flex align-items-center gap-2">
                        <div class="step-indicator active">1</div>
                        <span class="text-muted">Select Transfer Type</span>
                    </div>
                </div>
                
                <div class="row g-3">
                    <!-- Own Account Card -->
                    <div class="col-md-4">
                        <div class="card transfer-card h-100 shadow-sm" onclick="selectTransferType('own_account')">
                            <div class="card-body text-center p-4">
                                <div class="mb-3">
                                    <div class="rounded-circle mx-auto d-flex align-items-center justify-content-center" 
                                         style="width: 70px; height: 70px; background: linear-gradient(135deg, #198754 0%, #146c43 100%);">
                                        <i class="bi bi-arrow-repeat text-white" style="font-size: 2rem;"></i>
                                    </div>
                                </div>
                                <h5 class="fw-bold mb-2" style="color: #003631;">My Account</h5>
                                <p class="text-muted small mb-3">Transfer between your own accounts</p>
                                <div class="badge bg-success">No Fee</div>
                            </div>
                        </div>
                    </div>

                    <!-- Another Evergreen Account Card -->
                    <div class="col-md-4">
                        <div class="card transfer-card h-100 shadow-sm" onclick="selectTransferType('another_account')">
                            <div class="card-body text-center p-4">
                                <div class="mb-3">
                                    <div class="rounded-circle mx-auto d-flex align-items-center justify-content-center" 
                                         style="width: 70px; height: 70px; background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);">
                                        <i class="bi bi-people text-white" style="font-size: 2rem;"></i>
                                    </div>
                                </div>
                                <h5 class="fw-bold mb-2" style="color: #003631;">Evergreen Account</h5>
                                <p class="text-muted small mb-3">Send to another Evergreen  customer</p>
                                <div class="badge bg-primary">₱15.00 Fee</div>
                            </div>
                        </div>
                    </div>

                    <!-- Other Bank Card -->
                    <div class="col-md-4">
                        <div class="card transfer-card h-100 shadow-sm" onclick="selectTransferType('other_bank')">
                            <div class="card-body text-center p-4">
                                <div class="mb-3">
                                    <div class="rounded-circle mx-auto d-flex align-items-center justify-content-center" 
                                         style="width: 70px; height: 70px; background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);">
                                        <i class="bi bi-bank text-white" style="font-size: 2rem;"></i>
                                    </div>
                                </div>
                                <h5 class="fw-bold mb-2" style="color: #003631;">Other Bank</h5>
                                <p class="text-muted small mb-3">Transfer to external bank accounts</p>
                                <div class="badge bg-warning text-dark">₱25.00 Fee</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 2: Transfer Details Form -->
            <div id="step2" style="display: none;">
                <div class="text-center mb-3">
                    <div class="d-inline-flex align-items-center gap-2">
                        <div class="step-indicator completed">1</div>
                        <span class="text-muted">→</span>
                        <div class="step-indicator active">2</div>
                        <span class="text-muted">Enter Details</span>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 pt-3 pb-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="fw-bold mb-0" id="formTitle">Transfer Details</h5>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="backToStep1()">
                                <i class="bi bi-arrow-left me-1"></i>Change Type
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-3">                        <form action="<?= URLROOT ."/customer/fund_transfer"?>" method="POST" id="transferForm">
                            <input type="hidden" name="transfer_type" id="transfer_type_input" value="">

                            <?php if (!empty($data['low_balance_confirm_required'])): ?>
                                <div class="alert alert-warning">
                                    <strong>Warning:</strong> This transfer will bring your account balance below the required maintaining balance of
                                    <strong>PHP <?= number_format($data['maintaining_required'] ?? 500.00, 2); ?></strong>.
                                    Please confirm to proceed.
                                </div>
                                <input type="hidden" name="confirm_low_balance" value="1">
                            <?php endif; ?>
                            
                            <!-- From Account - Always shown -->
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label fw-semibold mb-0" style="color: #003631;">
                                        <i class="bi bi-wallet2 me-1"></i>From Account
                                    </label>
                                    <small class="text-muted">
                                        Balance: <span id="account_balance" class="fw-bold text-success">₱0.00</span>
                                    </small>
                                </div>
                                <select name="from_account" id="from_account" class="form-select shadow-sm" required style="border-color: #dee2e6;">
                                    <option value="">Select Source Account</option>
                                    <?php foreach($data['accounts'] as $account): ?>
                                        <?php if (in_array($account->account_type_id, [1, 2, 3, 4])): ?>
                                        <option value="<?= $account->account_number?>" 
                                                data-balance="<?= number_format($account->ending_balance, 2, '.', '') ?>" 
                                                data-type="<?= $account->account_type_id ?>" 
                                                data-type-name="<?= $account->type_name ?>">
                                            <?= $account->account_number ?> (<?= $account->type_name ?>) - ₱<?= number_format($account->ending_balance, 2) ?>
                                        </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- OWN ACCOUNT SECTION -->
                            <div id="own_account_section" style="display: none;">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold" style="color: #003631;">
                                        <i class="bi bi-arrow-down-circle me-1"></i>To My Account
                                    </label>
                                    <select name="to_account" id="to_account" class="form-select shadow-sm" style="border-color: #dee2e6;">
                                        <option value="">Select Destination Account</option>
                                        <?php foreach($data['accounts'] as $account): ?>
                                            <?php if (in_array($account->account_type_id, [1, 2, 3])): ?>
                                            <option value="<?= $account->account_number?>" 
                                                    data-type="<?= $account->account_type_id ?>" 
                                                    data-type-name="<?= $account->type_name ?>">
                                                <?= $account->account_number ?> (<?= $account->type_name ?>)
                                            </option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- ANOTHER EVERGREEN ACCOUNT SECTION -->
                            <div id="another_account_section" style="display: none;">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold" style="color: #003631;">
                                            <i class="bi bi-credit-card me-1"></i>Recipient Account Number
                                        </label>
                                        <input type="text" name="recipient_number" id="recipient_number" 
                                               class="form-control shadow-sm" 
                                               placeholder="ex. SA-2026-0001"
                                               style="border-color: #dee2e6;">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold" style="color: #003631;">
                                            <i class="bi bi-person me-1"></i>Recipient Name
                                        </label>
                                        <input type="text" name="recipient_name" id="recipient_name" 
                                               class="form-control shadow-sm" 
                                               placeholder="ex. Juan Dela Cruz"
                                               style="border-color: #dee2e6;">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold" style="color: #003631;">
                                        <i class="bi bi-chat-left-text me-1"></i>Message (Optional)
                                    </label>
                                    <textarea class="form-control shadow-sm" name="message" id="message" rows="2" 
                                              placeholder="Add a message to the recipient (max 100 characters)"
                                              style="border-color: #dee2e6;"></textarea>
                                </div>
                            </div>

                            <!-- OTHER BANK SECTION -->
                            <div id="other_bank_section" style="display: none;">
                                <div class="alert alert-info mb-3">
                                    <i class="bi bi-info-circle me-2"></i>
                                    <small>Transfers to other banks may take 1-3 business days to process.</small>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold" style="color: #003631;">
                                            <i class="bi bi-bank me-1"></i>Bank Name
                                        </label>
                                        <input type="text" name="bank_name" id="bank_name" 
                                               class="form-control shadow-sm" 
                                               placeholder="ex. BDO, BPI, Metrobank"
                                               style="border-color: #dee2e6;">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold" style="color: #003631;">
                                            <i class="bi bi-upc me-1"></i>Bank Code (Optional)
                                        </label>
                                        <input type="text" name="bank_code" id="bank_code" 
                                               class="form-control shadow-sm" 
                                               placeholder="ex. BOPIPHMM"
                                               style="border-color: #dee2e6;">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold" style="color: #003631;">
                                            <i class="bi bi-credit-card me-1"></i>Account Number
                                        </label>
                                        <input type="text" name="bank_account_number" id="bank_account_number" 
                                               class="form-control shadow-sm" 
                                               placeholder="Enter recipient's account number"
                                               style="border-color: #dee2e6;">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold" style="color: #003631;">
                                            <i class="bi bi-person me-1"></i>Account Holder Name
                                        </label>
                                        <input type="text" name="bank_account_name" id="bank_account_name" 
                                               class="form-control shadow-sm" 
                                               placeholder="Enter recipient's full name"
                                               style="border-color: #dee2e6;">
                                    </div>
                                </div>
                            </div>

                            <!-- Amount - Always shown -->
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label fw-semibold mb-0" style="color: #003631;">
                                        <i class="bi bi-cash-coin me-1"></i>Transfer Amount
                                    </label>
                                    <div>
                                        <small id="insufficient_balance" class="text-danger d-none">
                                            <i class="bi bi-exclamation-circle"></i> Insufficient balance
                                        </small>
                                        <small id="remaining_text" class="text-muted d-none">
                                            Remaining: <span id="remaining_balance" class="fw-bold text-success">₱0.00</span>
                                        </small>
                                    </div>
                                </div>
                                <div class="input-group shadow-sm">
                                    <span class="input-group-text bg-white" style="border-color: #dee2e6;">₱</span>
                                    <input type="number" id="transfer_amount" name="amount" 
                                           class="form-control" 
                                           placeholder="0.00" style="border-color: #dee2e6;" 
                                           step="0.01" min="1" required>
                                </div>
                                <small class="text-muted">Minimum transfer: ₱1.00</small>
                            </div>

                            <!-- Transfer Summary -->
                            <div class="card bg-light mb-3">
                                <div class="card-body p-3">
                                    <h6 class="fw-bold mb-2" style="color: #003631;">Transfer Summary</h6>
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-muted small">Amount:</span>
                                        <span class="fw-semibold" id="summary_amount">₱0.00</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-muted small">Transfer Fee:</span>
                                        <span class="fw-semibold" id="summary_fee">₱0.00</span>
                                    </div>
                                    <hr class="my-2">
                                    <div class="d-flex justify-content-between">
                                        <span class="fw-bold">Total:</span>
                                        <span class="fw-bold text-success" id="summary_total">₱0.00</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <button type="submit" class="btn btn-success w-100 fw-semibold shadow py-2">
                                <i class="bi bi-check-circle me-2"></i>Continue Transfer
                            </button>

                            <div class="text-center mt-3">
                                <small class="text-muted">
                                    By clicking continue, you agree to our <a href="#" class="text-decoration-none" style="color: #003631;">Terms &amp; Conditions</a>
                                </small>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Low Balance Warning Modal -->
<div class="modal fade" id="lowBalanceModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning text-dark border-0">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Low Balance Warning
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p class="mb-3">After this transfer, your account balance will be:</p>
                <div class="alert alert-warning mb-3">
                    <h4 class="mb-0 fw-bold" id="modalRemainingBalance">₱0.00</h4>
                </div>
                <p class="mb-3">This is below the maintaining balance requirement. Consequences may include:</p>
                <ul class="small text-muted">
                    <li>Monthly service fees may be charged</li>
                    <li>Service interruptions or transaction restrictions</li>
                    <li>Possible overdraft or additional charges</li>
                </ul>
                <p class="mb-0"><strong style="color: #003631;">Do you want to proceed?</strong></p>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" id="confirmLowBalanceBtn">Yes, Continue</button>
            </div>
        </div>
    </div>
</div>

<?php require_once ROOT_PATH . '/app/views/layouts/footer.php'; ?>

<script>
let currentTransferType = '';
const FEES = {
    'own_account': 0,
    'another_account': 15.00,
    'other_bank': 25.00
};
const MIN_REQUIRED_BALANCE = 500.00;

function selectTransferType(type) {
    currentTransferType = type;
    document.getElementById('transfer_type_input').value = type;
    
    // Update form title
    const titles = {
        'own_account': 'Transfer to My Account',
        'another_account': 'Transfer to Evergreen Account',
        'other_bank': 'Transfer to Other Bank'
    };
    document.getElementById('formTitle').textContent = titles[type];
    
    // Show step 2, hide step 1
    document.getElementById('step1').style.display = 'none';
    document.getElementById('step2').style.display = 'block';
    
    // Show/hide appropriate sections
    document.getElementById('own_account_section').style.display = type === 'own_account' ? 'block' : 'none';
    document.getElementById('another_account_section').style.display = type === 'another_account' ? 'block' : 'none';
    document.getElementById('other_bank_section').style.display = type === 'other_bank' ? 'block' : 'none';
    
    // Set required attributes
    if (type === 'own_account') {
        document.getElementById('to_account').required = true;
        document.getElementById('recipient_number').required = false;
        document.getElementById('recipient_name').required = false;
        document.getElementById('bank_name').required = false;
        document.getElementById('bank_account_number').required = false;
        document.getElementById('bank_account_name').required = false;
    } else if (type === 'another_account') {
        document.getElementById('to_account').required = false;
        document.getElementById('recipient_number').required = true;
        document.getElementById('recipient_name').required = true;
        document.getElementById('bank_name').required = false;
        document.getElementById('bank_account_number').required = false;
        document.getElementById('bank_account_name').required = false;
    } else if (type === 'other_bank') {
        document.getElementById('to_account').required = false;
        document.getElementById('recipient_number').required = false;
        document.getElementById('recipient_name').required = false;
        document.getElementById('bank_name').required = true;
        document.getElementById('bank_account_number').required = true;
        document.getElementById('bank_account_name').required = true;
    }
    
    updateSummary();
}

function backToStep1() {
    document.getElementById('step1').style.display = 'block';
    document.getElementById('step2').style.display = 'none';
    currentTransferType = '';
}

function updateBalance() {
    const selectElement = document.getElementById('from_account');
    const selectedOption = selectElement.options[selectElement.selectedIndex];
    const balance = parseFloat(selectedOption.getAttribute('data-balance')) || 0;
    
    document.getElementById('account_balance').textContent = '₱' + balance.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    
    // Update to_account if own_account type
    if (currentTransferType === 'own_account') {
        updateToAccountOptions();
    }
    
    updateRemainingBalance();
}

function updateToAccountOptions() {
    const fromAccount = document.getElementById('from_account');
    const toAccount = document.getElementById('to_account');
    const selectedFromAccount = fromAccount.value;
    
    Array.from(toAccount.options).forEach((option) => {
        if (option.value === (selectedFromAccount)) {
            option.style.display = 'none';
            option.disabled = true;
        } else {
            option.style.display = 'block';
            option.disabled = false;
        }
    });
    
    if (toAccount.value === selectedFromAccount) {
        toAccount.value = '';
    }
}

function getCurrentFee() {
    return FEES[currentTransferType] || 0;
}

function updateRemainingBalance() {
    const selectElement = document.getElementById('from_account');
    const selectedOption = selectElement.options[selectElement.selectedIndex];
    const balance = parseFloat(selectedOption.getAttribute('data-balance')) || 0;
    
    const amount = parseFloat(document.getElementById('transfer_amount').value) || 0;
    const fee = getCurrentFee();
    const total = amount + fee;
    const remaining = balance - total;
    
    const insufficientAlert = document.getElementById('insufficient_balance');
    const remainingText = document.getElementById('remaining_text');
    const remainingBalanceSpan = document.getElementById('remaining_balance');
    
    if (amount > 0) {
        if (balance < total) {
            insufficientAlert.classList.remove('d-none');
            remainingText.classList.add('d-none');
        } else {
            insufficientAlert.classList.add('d-none');
            remainingText.classList.remove('d-none');
            remainingBalanceSpan.textContent = '₱' + remaining.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }
    } else {
        insufficientAlert.classList.add('d-none');
        remainingText.classList.add('d-none');
    }
    
    updateSummary();
}

function updateSummary() {
    const amount = parseFloat(document.getElementById('transfer_amount').value) || 0;
    const fee = getCurrentFee();
    const total = amount + fee;
    
    document.getElementById('summary_amount').textContent = '₱' + amount.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('summary_fee').textContent = '₱' + fee.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('summary_total').textContent = '₱' + total.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

document.addEventListener('DOMContentLoaded', function() {
    const fromAccountSelect = document.getElementById('from_account');
    if (fromAccountSelect) {
        fromAccountSelect.addEventListener('change', updateBalance);
    }
    
    const amountInput = document.getElementById('transfer_amount');
    if (amountInput) {
        amountInput.addEventListener('input', updateRemainingBalance);
    }
    
    // Form submission validation
    const form = document.getElementById('transferForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            const selectElement = document.getElementById('from_account');
            const selectedOption = selectElement.options[selectElement.selectedIndex];
            const balance = parseFloat(selectedOption.getAttribute('data-balance')) || 0;
            const amount = parseFloat(document.getElementById('transfer_amount').value) || 0;
            const fee = getCurrentFee();
            const total = amount + fee;
            const remaining = balance - total;
            
            if (remaining < 0) {
                e.preventDefault();
                alert('Insufficient funds. Please enter a smaller amount or choose another account.');
                return false;
            }
            
            if (remaining < MIN_REQUIRED_BALANCE) {
                e.preventDefault();
                document.getElementById('modalRemainingBalance').textContent = '₱' + remaining.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                const modal = new bootstrap.Modal(document.getElementById('lowBalanceModal'));
                modal.show();
                
                document.getElementById('confirmLowBalanceBtn').onclick = function() {
                    let hidden = form.querySelector('input[name="confirm_low_balance"]');
                    if (!hidden) {
                        hidden = document.createElement('input');
                        hidden.type = 'hidden';
                        hidden.name = 'confirm_low_balance';
                        hidden.value = '1';
                        form.appendChild(hidden);
                    }
                    modal.hide();
                    form.submit();
                };
                return false;
            }
        });
    }
    
    // Auto-dismiss alerts
    setTimeout(() => {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        });
    }, 5000);
});
</script>
