<?php require_once ROOT_PATH . '/app/views/layouts/header.php'; ?>

<style>
.receipt-container {
    max-width: 600px;
    margin: 0 auto;
}
.receipt-card {
    border-radius: 12px;
    overflow: hidden;
}
.receipt-header {
    background: linear-gradient(135deg, #003631 0%, #146c43 100%);
    color: white;
    padding: 2rem;
    text-align: center;
}
.receipt-amount {
    background: rgba(255,255,255,0.1);
    border-radius: 8px;
    padding: 1rem;
    margin-top: 1rem;
}
.detail-row {
    display: flex;
    justify-content: space-between;
    padding: 0.75rem 0;
    border-bottom: 1px solid #e9ecef;
}
.detail-row:last-child {
    border-bottom: none;
}
.detail-label {
    color: #6c757d;
    font-size: 0.875rem;
}
.detail-value {
    color: #212529;
    font-weight: 500;
    text-align: right;
}
.transfer-type-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}
.badge-own { background: #d4edda; color: #155724; }
.badge-evergreen { background: #cfe2ff; color: #084298; }
.badge-external { background: #fff3cd; color: #856404; }
</style>

<div class="container py-4">
    <div class="receipt-container">
        <!-- Back Button -->
        <div class="mb-3">
            <a href="<?= URLROOT . "/customer/fund_transfer" ?>" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Back to Transfer
            </a>
        </div>

        <div class="card receipt-card shadow-lg border-0">
            <!-- Header -->
            <div class="receipt-header">
                <div class="mb-3">
                    <i class="bi bi-receipt" style="font-size: 3rem;"></i>
                </div>
                <h4 class="fw-bold mb-2">Review Transfer</h4>
                <span class="transfer-type-badge <?php 
                    if ($data['transfer_type'] === 'own_account') echo 'badge-own';
                    elseif ($data['transfer_type'] === 'other_bank') echo 'badge-external';
                    else echo 'badge-evergreen';
                ?>">
                    <?php 
                        if ($data['transfer_type'] === 'own_account') echo 'OWN ACCOUNT';
                        elseif ($data['transfer_type'] === 'other_bank') echo 'EXTERNAL BANK';
                        else echo 'EVERGREEN ACCOUNT';
                    ?>
                </span>

                <div class="receipt-amount mt-3">
                    <small class="d-block mb-1" style="opacity: 0.9;">Total Amount</small>
                    <h2 class="mb-0 fw-bold">₱ <?= number_format($data['total_payment'] ?? 0.00, 2); ?></h2>
                    <div class="row mt-2 small" style="opacity: 0.85;">
                        <div class="col-6 text-start">Transfer: ₱<?= number_format($data['amount'] ?? 0.00, 2); ?></div>
                        <div class="col-6 text-end">Fee: ₱<?= number_format($data['fee'] ?? 0.00, 2); ?></div>
                    </div>
                </div>
            </div>

            <!-- Details -->
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3 text-uppercase" style="color: #003631; font-size: 0.875rem;">
                    <i class="bi bi-list-ul me-2"></i>Transaction Details
                </h6>

                <!-- From Account -->
                <div class="detail-row">
                    <span class="detail-label">From Account</span>
                    <div class="detail-value">
                        <div><?= htmlspecialchars($data['sender_name'] ?? 'Your Account'); ?></div>
                        <small class="text-muted"><?= htmlspecialchars($data['from_account']); ?></small>
                    </div>
                </div>

                <!-- To Account / Recipient -->
                <?php if ($data['transfer_type'] === 'own_account'): ?>
                    <div class="detail-row">
                        <span class="detail-label">To Account</span>
                        <div class="detail-value">
                            <div>Own Account</div>
                            <small class="text-muted"><?= htmlspecialchars($data['to_account']); ?></small>
                        </div>
                    </div>
                <?php elseif ($data['transfer_type'] === 'other_bank'): ?>
                    <div class="detail-row">
                        <span class="detail-label">Bank Name</span>
                        <div class="detail-value"><?= htmlspecialchars($data['bank_name'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Account Number</span>
                        <div class="detail-value"><?= htmlspecialchars($data['bank_account_number'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Account Name</span>
                        <div class="detail-value"><?= htmlspecialchars($data['bank_account_name'] ?? 'N/A'); ?></div>
                    </div>
                    <?php if (!empty($data['bank_code'])): ?>
                    <div class="detail-row">
                        <span class="detail-label">Bank Code</span>
                        <div class="detail-value"><?= htmlspecialchars($data['bank_code']); ?></div>
                    </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="detail-row">
                        <span class="detail-label">Recipient</span>
                        <div class="detail-value">
                            <div><?= htmlspecialchars($data['recipient_name'] ?? 'N/A'); ?></div>
                            <small class="text-muted"><?= htmlspecialchars($data['recipient_number'] ?? 'N/A'); ?></small>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Message -->
                <?php if (!empty($data['message'])): ?>
                <div class="detail-row">
                    <span class="detail-label">Message</span>
                    <div class="detail-value" style="max-width: 60%;">
                        <em class="text-muted"><?= htmlspecialchars($data['message']); ?></em>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Remaining Balance -->
                <div class="detail-row bg-light rounded mt-3 p-3">
                    <span class="detail-label fw-semibold">Remaining Balance</span>
                    <div class="detail-value">
                        <span class="fs-5 fw-bold text-success">₱ <?= number_format($data['remaining_balance'] ?? 0.00, 2); ?></span>
                    </div>
                </div>

                <!-- Additional Info -->
                <div class="mt-4 p-3 bg-light rounded">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <small class="text-muted">
                            <i class="bi bi-calendar3 me-1"></i><?= date('F d, Y'); ?>
                        </small>
                        <small class="text-muted">
                            <i class="bi bi-clock me-1"></i><?= date('h:i A'); ?>
                        </small>
                    </div>
                    <div class="text-center">
                        <small class="text-muted">Reference: <?= htmlspecialchars($data['temp_transaction_ref'] ?? 'TXN-' . date('YmdHis')); ?></small>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="row g-2 mt-4">
                    <div class="col-6">
                        <a href="<?= URLROOT . "/customer/fund_transfer" ?>" class="btn btn-outline-secondary w-100">
                            <i class="bi bi-x-circle me-1"></i>Cancel
                        </a>
                    </div>
                    <div class="col-6">
                        <form action="<?= URLROOT ."/customer/receipt"?>" method="POST" class="d-inline w-100">
                            <input type="hidden" name="transfer_type" value="<?= htmlspecialchars($data['transfer_type'] ?? 'another_account'); ?>">
                            <input type="hidden" name="from_account" value="<?= htmlspecialchars($data['from_account']); ?>">
                            <input type="hidden" name="to_account" value="<?= htmlspecialchars($data['to_account'] ?? ''); ?>">
                            <input type="hidden" name="recipient_number" value="<?= htmlspecialchars($data['recipient_number'] ?? ''); ?>">
                            <input type="hidden" name="recipient_name" value="<?= htmlspecialchars($data['recipient_name'] ?? ''); ?>">
                            <input type="hidden" name="amount" value="<?= htmlspecialchars($data['amount']); ?>">
                            <input type="hidden" name="message" value="<?= htmlspecialchars($data['message'] ?? ''); ?>">
                            <input type="hidden" name="bank_name" value="<?= htmlspecialchars($data['bank_name'] ?? ''); ?>">
                            <input type="hidden" name="bank_account_number" value="<?= htmlspecialchars($data['bank_account_number'] ?? ''); ?>">
                            <input type="hidden" name="bank_account_name" value="<?= htmlspecialchars($data['bank_account_name'] ?? ''); ?>">
                            <input type="hidden" name="bank_code" value="<?= htmlspecialchars($data['bank_code'] ?? ''); ?>">
                            
                            <button type="submit" class="btn btn-success w-100 fw-semibold">
                                <i class="bi bi-check-circle me-1"></i>Confirm
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Info Alert -->
        <div class="alert alert-info mt-3 border-0 shadow-sm">
            <small>
                <i class="bi bi-info-circle me-2"></i>
                <strong>Note:</strong> Please review all details carefully before confirming. This action cannot be undone.
            </small>
        </div>
    </div>
</div>

<?php require_once ROOT_PATH . '/app/views/layouts/footer.php'; ?>
