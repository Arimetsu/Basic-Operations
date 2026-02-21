<?php require_once ROOT_PATH . '/app/views/layouts/header.php'; 

function formatMobileNumber($number) {
    $digits = preg_replace('/\D/', '', $number);
    
    if (strlen($digits) >= 10) {
        $clean_number = substr($digits, -10);
        return '+63 ' . substr($clean_number, 0, 3) . ' ' . substr($clean_number, 3, 3) . ' ' . substr($clean_number, 6, 4);
    }

    return htmlspecialchars($number ?? 'N/A');
}

$mobile_display = formatMobileNumber($data['profile']->mobile_number);
$mobile_raw = $data['profile']->mobile_number ?? '';
// Clean mobile number for input (remove +63 if present)
if (!empty($mobile_raw) && $mobile_raw !== 'N/A') {
    $mobile_raw = preg_replace('/[^0-9]/', '', $mobile_raw);
    if (strpos($mobile_raw, '63') === 0 && strlen($mobile_raw) > 10) {
        $mobile_raw = '0' . substr($mobile_raw, 2);
    }
}
?>

<style>
:root {
    --primary-color: #6c757d;
    --primary-green: #003631;
    --light-gray: #f8f9fa;
    --border-gray: #e9ecef;
    --text-dark: #212529;
    --text-muted: #6c757d;
    --success-green: #28a745;
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

/* Main Content Area */
.profile-main {
    flex: 1;
    padding: 2rem 3rem;
    overflow-y: auto;
    animation: fadeIn 0.4s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.profile-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.profile-header h2 {
    font-size: 1.75rem;
    font-weight: 600;
    color: var(--text-dark);
    margin: 0;
}

.profile-subtitle {
    color: var(--text-muted);
    font-size: 0.95rem;
    margin-bottom: 2rem;
}

.btn-back {
    display: inline-flex;
    align-items: center;
    padding: 0.5rem 1rem;
    background: white;
    border: 1px solid var(--border-gray);
    border-radius: 6px;
    color: var(--text-dark);
    text-decoration: none;
    font-size: 0.9rem;
    transition: all 0.3s ease;
}

.btn-back:hover {
    background: var(--light-gray);
    border-color: var(--text-muted);
    transform: translateX(-2px);
}

.btn-back i {
    margin-right: 0.5rem;
}

/* Info Cards */
.info-card {
    background: white;
    border: 1px solid var(--border-gray);
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    transition: all 0.3s ease;
}

.info-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.info-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.25rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--border-gray);
}

.info-card-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--text-dark);
    margin: 0;
}

.btn-edit {
    display: inline-flex;
    align-items: center;
    padding: 0.4rem 1rem;
    background: transparent;
    border: 1px solid var(--primary-color);
    border-radius: 6px;
    color: var(--primary-color);
    font-size: 0.875rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-edit:hover {
    background: var(--primary-color);
    color: white;
    transform: translateY(-1px);
}

.btn-edit i {
    margin-right: 0.4rem;
}

.btn-add-more {
    display: inline-flex;
    align-items: center;
    color: var(--primary-color);
    font-size: 0.875rem;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-add-more:hover {
    color: #5a6268;
    text-decoration: underline;
}

.btn-add-more i {
    margin-right: 0.4rem;
}

/* Contact Items */
.contact-item {
    display: flex;
    align-items: center;
    padding: 1rem;
    background: var(--light-gray);
    border-radius: 6px;
    margin-bottom: 0.75rem;
    transition: all 0.3s ease;
}

.contact-item:hover {
    background: #e9ecef;
}

.contact-icon {
    width: 40px;
    height: 40px;
    background: var(--primary-color);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
    flex-shrink: 0;
}

.contact-details {
    flex: 1;
}

.contact-value {
    font-size: 0.95rem;
    color: var(--text-dark);
    font-weight: 500;
    margin-bottom: 0.25rem;
}

.contact-label {
    font-size: 0.8rem;
    color: var(--text-muted);
}

.contact-badge {
    background: var(--success-green);
    color: white;
    padding: 0.25rem 0.6rem;
    border-radius: 12px;
    font-size: 0.75rem;
    margin-left: 0.5rem;
}

.contact-badge.verified {
    background: #17a2b8;
}

/* Side-by-side cards for address sections */
.cards-row {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

/* Info Grid */
.info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
}

.info-item {
    display: flex;
    flex-direction: column;
}

.info-label {
    font-size: 0.8rem;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.5rem;
}

.info-value {
    font-size: 1rem;
    color: var(--text-dark);
    font-weight: 500;
}

/* Alerts */
.alert {
    padding: 1rem 1.25rem;
    margin-bottom: 1.5rem;
    border-radius: 8px;
    animation: slideDown 0.4s ease;
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.alert-success {
    background-color: #d1e7dd;
    border: 1px solid #badbcc;
    color: #0f5132;
}

.alert-danger {
    background-color: #f8d7da;
    border: 1px solid #f5c2c7;
    color: #842029;
}

/* Modal Customization */
.modal-content {
    border: none;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.15);
}

.modal-header {
    border-bottom: 1px solid var(--border-gray);
    padding: 1.5rem;
}

.modal-title {
    font-weight: 600;
    color: var(--text-dark);
}

.modal-body {
    padding: 1.5rem;
}

.modal-footer {
    border-top: 1px solid var(--border-gray);
    padding: 1rem 1.5rem;
}

.form-label {
    font-weight: 500;
    color: var(--text-dark);
    margin-bottom: 0.5rem;
}

.form-control:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.2rem rgba(108, 117, 125, 0.25);
}

/* Responsive */
@media (max-width: 992px) {
    .profile-container {
        flex-direction: column;
    }
    
    .profile-sidebar {
        width: 100%;
        border-right: none;
        border-bottom: 1px solid var(--border-gray);
    }
    
    .info-grid {
        grid-template-columns: 1fr;
    }
    
    .cards-row {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .profile-main {
        padding: 1.5rem;
    }
    
    .profile-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
}
</style>


<div class="profile-container">
    <!-- Sidebar Navigation -->
    <aside class="profile-sidebar">
        <div class="user-info">
            <h5><?= htmlspecialchars($data['full_name']) ?? 'User'; ?></h5>
            <p><?= htmlspecialchars($data['profile']->email_address ?? ''); ?></p>
        </div>
        
        <ul class="profile-nav">
            <li class="profile-nav-item">
                <a href="#profile" class="profile-nav-link active" data-section="profile">
                    <i class="bi bi-person"></i>
                    Profile
                </a>
            </li>
            <li class="profile-nav-item">
                <a href="<?= URLROOT ?>/customer/change_password" class="profile-nav-link">
                    <i class="bi bi-shield-lock"></i>
                    Security
                </a>
            </li>
            <li class="profile-nav-item">
                <a href="<?= URLROOT ?>/customer/dashboard" class="profile-nav-link">
                    <i class="bi bi-house-door"></i>
                    Back to Dashboard
                </a>
            </li>
        </ul>
    </aside>

    <!-- Main Content -->
    <main class="profile-main">
        <div class="profile-header">
            <div>
                <h2>My Profile</h2>
                <p class="profile-subtitle">Manage your personal information and contact details</p>
            </div>
            <a href="<?= URLROOT ?>/customer/account" class="btn-back">
                <i class="bi bi-arrow-left"></i>
                Back to Accounts
            </a>
        </div>

        <!-- Success/Error Messages -->
        <?php if (!empty($data['success_message'])): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle me-2"></i>
                <?= htmlspecialchars($data['success_message']) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($data['error_message'])): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <?= htmlspecialchars($data['error_message']) ?>
            </div>
        <?php endif; ?>

        <!-- Contact Information Card -->
        <div class="info-card">
            <div class="info-card-header">
                <h3 class="info-card-title">Contact Information</h3>
                <a href="#" class="btn-add-more" data-bs-toggle="modal" data-bs-target="#addContactModal">
                    <i class="bi bi-plus-circle"></i>
                    Add More
                </a>
            </div>

            <?php 
            // Display all emails
            $emails = $data['emails'] ?? [];
            if (!empty($emails)) {
                foreach ($emails as $email) {
                    ?>
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="bi bi-envelope-fill"></i>
                        </div>
                        <div class="contact-details">
                            <div class="contact-value">
                                <?= htmlspecialchars($email->email ?? 'N/A') ?>
                                <?php if ($email->is_primary == 1): ?>
                                    <span class="contact-badge">Primary</span>
                                <?php endif; ?>
                            </div>
                            <div class="contact-label">Email Address</div>
                        </div>
                        <button class="btn btn-sm btn-link text-muted" 
                                data-bs-toggle="modal" 
                                data-bs-target="#editEmailModal<?= $email->email_id ?>"
                                title="Edit">
                            <i class="bi bi-pencil"></i>
                        </button>
                    </div>
                    
                    <!-- Edit Email Modal -->
                    <div class="modal fade" id="editEmailModal<?= $email->email_id ?>" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Edit Email</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <form method="POST" action="<?= URLROOT ?>/customer/updateContact">
                                    <div class="modal-body">
                                        <input type="hidden" name="contact_id" value="<?= $email->email_id ?>">
                                        <input type="hidden" name="contact_type" value="email">
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Email Address</label>
                                            <input type="email" class="form-control" name="contact_value" 
                                                   value="<?= htmlspecialchars($email->email) ?>" required>
                                        </div>
                                        
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="set_primary" 
                                                   id="setPrimaryEmail<?= $email->email_id ?>" 
                                                   <?= $email->is_primary == 1 ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="setPrimaryEmail<?= $email->email_id ?>">
                                                Set as primary
                                            </label>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-success">Save Changes</button>
                                        <button type="submit" name="delete_contact" value="1" class="btn btn-danger" 
                                                onclick="return confirm('Are you sure you want to delete this email?')">Delete</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php
                }
            } else {
                // Fallback to single email from profile
                ?>
                <div class="contact-item">
                    <div class="contact-icon">
                        <i class="bi bi-envelope-fill"></i>
                    </div>
                    <div class="contact-details">
                        <div class="contact-value">
                            <?= htmlspecialchars($data['profile']->email_address ?? 'N/A') ?>
                            <span class="contact-badge">Primary</span>
                        </div>
                        <div class="contact-label">Email Address</div>
                    </div>
                </div>
                <?php
            }
            ?>

            <?php 
            // Display all phones
            $phones = $data['phones'] ?? [];
            if (!empty($phones)) {
                foreach ($phones as $phone) {
                    $phone_display = formatMobileNumber($phone->phone_number);
                    ?>
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="bi bi-telephone-fill"></i>
                        </div>
                        <div class="contact-details">
                            <div class="contact-value">
                                <?= $phone_display ?>
                                <?php if ($phone->is_primary == 1): ?>
                                    <span class="contact-badge">Primary</span>
                                <?php endif; ?>
                            </div>
                            <div class="contact-label">Phone Number</div>
                        </div>
                        <button class="btn btn-sm btn-link text-muted" 
                                data-bs-toggle="modal" 
                                data-bs-target="#editPhoneModal<?= $phone->phone_id ?>"
                                title="Edit">
                            <i class="bi bi-pencil"></i>
                        </button>
                    </div>
                    
                    <!-- Edit Phone Modal -->
                    <div class="modal fade" id="editPhoneModal<?= $phone->phone_id ?>" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Edit Phone Number</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <form method="POST" action="<?= URLROOT ?>/customer/updateContact">
                                    <div class="modal-body">
                                        <input type="hidden" name="contact_id" value="<?= $phone->phone_id ?>">
                                        <input type="hidden" name="contact_type" value="phone">
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Phone Number</label>
                                            <input type="text" class="form-control" name="contact_value" 
                                                   value="<?= htmlspecialchars($phone->phone_number) ?>" 
                                                   maxlength="11" required>
                                        </div>
                                        
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="set_primary" 
                                                   id="setPrimaryPhone<?= $phone->phone_id ?>" 
                                                   <?= $phone->is_primary == 1 ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="setPrimaryPhone<?= $phone->phone_id ?>">
                                                Set as primary
                                            </label>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-success">Save Changes</button>
                                        <button type="submit" name="delete_contact" value="1" class="btn btn-danger" 
                                                onclick="return confirm('Are you sure you want to delete this phone number?')">Delete</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php
                }
            } else {
                // Fallback to single phone from profile
                ?>
                <div class="contact-item">
                    <div class="contact-icon">
                        <i class="bi bi-telephone-fill"></i>
                    </div>
                    <div class="contact-details">
                        <div class="contact-value">
                            <?= $mobile_display ?>
                            <span class="contact-badge">Primary</span>
                        </div>
                        <div class="contact-label">Phone Number</div>
                    </div>
                </div>
                <?php
            }
            ?>
        </div>

        <!-- Personal Information Card -->
        <div class="info-card">
            <div class="info-card-header">
                <h3 class="info-card-title">Personal Information</h3>
                <button class="btn-edit" onclick="editSection('personal')">
                    <i class="bi bi-pencil"></i>
                    Edit
                </button>
            </div>

            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Full Name</div>
                    <div class="info-value"><?= htmlspecialchars($data['full_name']) ?? 'N/A'; ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label">Date of Birth</div>
                    <div class="info-value">
                        <?php
                        if (isset($data['profile']->date_of_birth) && $data['profile']->date_of_birth) {
                            $dob = new DateTime($data['profile']->date_of_birth);
                            echo htmlspecialchars($dob->format('F j, Y'));
                        } else {
                            echo 'N/A';
                        }
                        ?>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-label">Gender</div>
                    <div class="info-value"><?= htmlspecialchars($data['profile']->gender ?? 'N/A'); ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label">Civil Status</div>
                    <div class="info-value"><?= htmlspecialchars($data['profile']->civil_status ?? 'N/A'); ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label">Nationality</div>
                    <div class="info-value"><?= htmlspecialchars($data['profile']->citizenship ?? 'N/A'); ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label">Employment Status</div>
                    <div class="info-value"><?= htmlspecialchars($data['employment_status'] ?? 'N/A'); ?></div>
                </div>
            </div>
        </div>

        <!-- Home Address and Billing Address Side-by-Side -->
        <div class="cards-row">
            <!-- Home Address Card -->
            <div class="info-card">
                <div class="info-card-header">
                    <h3 class="info-card-title">Home Address</h3>
                    <button class="btn-edit" onclick="editSection('address')">
                        <i class="bi bi-pencil"></i>
                        Edit
                    </button>
                </div>

                <div class="info-item" style="margin-bottom: 1rem;">
                    <div class="info-label">Street Address</div>
                    <div class="info-value"><?= htmlspecialchars($data['profile']->address_line ?? 'N/A'); ?></div>
                </div>

                <div class="info-item" style="margin-bottom: 1rem;">
                    <div class="info-label">Barangay</div>
                    <div class="info-value"><?= htmlspecialchars($data['profile']->barangay_name ?? 'N/A'); ?></div>
                </div>

                <div class="info-item" style="margin-bottom: 1rem;">
                    <div class="info-label">City/Municipality</div>
                    <div class="info-value"><?= htmlspecialchars($data['profile']->city_name ?? 'N/A'); ?></div>
                </div>

                <div class="info-item" style="margin-bottom: 1rem;">
                    <div class="info-label">Province</div>
                    <div class="info-value"><?= htmlspecialchars($data['profile']->province_name ?? 'N/A'); ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label">Country</div>
                    <div class="info-value">Philippines</div>
                </div>
            </div>

            <!-- Financial Information Card -->
            <div class="info-card">
                <div class="info-card-header">
                    <h3 class="info-card-title">Financial Information</h3>
                    <button class="btn-edit" onclick="editSection('financial')">
                        <i class="bi bi-pencil"></i>
                        Edit
                    </button>
                </div>

                <div class="info-item" style="margin-bottom: 1rem;">
                    <div class="info-label">Source of Funds</div>
                    <div class="info-value"><?= htmlspecialchars($data['profile']->occupation ?? 'N/A'); ?></div>
                </div>

                <div class="info-item" style="margin-bottom: 1rem;">
                    <div class="info-label">Name of Employer</div>
                    <div class="info-value"><?= htmlspecialchars($data['profile']->name_of_employer ?? 'N/A'); ?></div>
                </div>

                <div class="info-item" style="margin-bottom: 1rem;">
                    <div class="info-label">Employment Status</div>
                    <div class="info-value"><?= htmlspecialchars($data['employment_status'] ?? 'N/A'); ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label">Income Range</div>
                    <div class="info-value"><?= htmlspecialchars($data['profile']->income_range ?? 'N/A'); ?></div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Add Contact Modal -->
<div class="modal fade" id="addContactModal" tabindex="-1" aria-labelledby="addContactModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addContactModalLabel">Add Contact Information</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addContactForm" method="POST" action="<?= URLROOT ?>/customer/profile">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Contact Type</label>
                        <select class="form-select" id="contactType" name="contact_type" required>
                            <option value="">Select Type</option>
                            <option value="email">Email Address</option>
                            <option value="phone">Phone Number</option>
                        </select>
                    </div>

                    <div class="mb-3" id="emailField" style="display: none;">
                        <label class="form-label">Email Address</label>
                        <input type="email" class="form-control" name="new_email" placeholder="example@email.com">
                    </div>

                    <div class="mb-3" id="phoneField" style="display: none;">
                        <label class="form-label">Phone Number</label>
                        <input type="text" class="form-control" name="new_phone" placeholder="09123456789" maxlength="11">
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="set_primary" id="setPrimary">
                        <label class="form-check-label" for="setPrimary">
                            Set as primary
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-plus-circle me-1"></i>
                        Add Contact
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Personal Info Modal -->
<div class="modal fade" id="editPersonalModal" tabindex="-1" aria-labelledby="editPersonalModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editPersonalModalLabel">Edit Personal Information</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?= URLROOT ?>/customer/profile">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Gender</label>
                            <select name="gender" class="form-select">
                                <option value="">Select Gender</option>
                                <option value="Male" <?= (isset($data['profile']->gender) && $data['profile']->gender == 'Male') ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?= (isset($data['profile']->gender) && $data['profile']->gender == 'Female') ? 'selected' : ''; ?>>Female</option>
                                <option value="Other" <?= (isset($data['profile']->gender) && $data['profile']->gender == 'Other') ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Civil Status</label>
                            <select name="civil_status" class="form-select">
                                <option value="">Select Status</option>
                                <option value="Single" <?= (isset($data['profile']->civil_status) && $data['profile']->civil_status == 'Single') ? 'selected' : ''; ?>>Single</option>
                                <option value="Married" <?= (isset($data['profile']->civil_status) && $data['profile']->civil_status == 'Married') ? 'selected' : ''; ?>>Married</option>
                                <option value="Widowed" <?= (isset($data['profile']->civil_status) && $data['profile']->civil_status == 'Widowed') ? 'selected' : ''; ?>>Widowed</option>
                                <option value="Divorced" <?= (isset($data['profile']->civil_status) && $data['profile']->civil_status == 'Divorced') ? 'selected' : ''; ?>>Divorced</option>
                            </select>
                        </div>

                        <div class="col-12 mb-3">
                            <label class="form-label">Nationality</label>
                            <input type="text" name="citizenship" class="form-control" value="<?= htmlspecialchars($data['profile']->citizenship ?? ''); ?>" placeholder="e.g., Filipino">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle me-1"></i>
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Address Modal -->
<div class="modal fade" id="editAddressModal" tabindex="-1" aria-labelledby="editAddressModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editAddressModalLabel">Edit Home Address</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?= URLROOT ?>/customer/profile">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12 mb-3">
                            <label class="form-label">Street Address</label>
                            <input type="text" name="address_line" class="form-control" value="<?= htmlspecialchars($data['profile']->address_line ?? ''); ?>" placeholder="House No., Street, Subdivision">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Province</label>
                            <select name="province_id" class="form-select" id="provinceSelectModal">
                                <option value="">Select Province</option>
                                <?php foreach (($data['provinces'] ?? []) as $prov): ?>
                                    <option value="<?= $prov->province_id; ?>" <?= (isset($data['profile']->province_id) && $data['profile']->province_id == $prov->province_id) ? 'selected' : ''; ?>><?= htmlspecialchars($prov->province_name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">City/Municipality</label>
                            <select name="city_id" class="form-select" id="citySelectModal">
                                <option value="">Select City</option>
                                <?php foreach (($data['cities'] ?? []) as $city): ?>
                                    <option value="<?= $city->city_id; ?>" data-province="<?= $city->province_id; ?>" <?= (isset($data['profile']->city_id) && $data['profile']->city_id == $city->city_id) ? 'selected' : ''; ?>><?= htmlspecialchars($city->city_name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Barangay</label>
                            <select name="barangay_id" class="form-select" id="barangaySelectModal">
                                <option value="">Select Barangay</option>
                                <?php
                                if (isset($data['barangays']) && is_array($data['barangays'])) {
                                    foreach ($data['barangays'] as $barangay) {
                                        $selected = (isset($data['profile']->barangay_id) && $data['profile']->barangay_id == $barangay->barangay_id) ? 'selected' : '';
                                        echo '<option value="' . $barangay->barangay_id . '" data-city="' . $barangay->city_id . '" ' . $selected . '>' . htmlspecialchars($barangay->barangay_name) . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle me-1"></i>
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Financial Info Modal -->
<div class="modal fade" id="editFinancialModal" tabindex="-1" aria-labelledby="editFinancialModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editFinancialModalLabel">Edit Financial Information</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?= URLROOT ?>/customer/profile">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Source of Funds (Occupation)</label>
                        <input type="text" name="occupation" class="form-control" value="<?= htmlspecialchars($data['profile']->occupation ?? ''); ?>" placeholder="e.g., Employment, Business">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Name of Employer</label>
                        <input type="text" name="name_of_employer" class="form-control" value="<?= htmlspecialchars($data['profile']->name_of_employer ?? ''); ?>" placeholder="Company name">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Income Range</label>
                        <select name="income_range" class="form-select">
                            <option value="">Select Income Range</option>
                            <option value="Below 100,000" <?= (isset($data['profile']->income_range) && $data['profile']->income_range == 'Below 100,000') ? 'selected' : ''; ?>>Below ₱100,000</option>
                            <option value="100,000 - 250,000" <?= (isset($data['profile']->income_range) && $data['profile']->income_range == '100,000 - 250,000') ? 'selected' : ''; ?>>₱100,000 - ₱250,000</option>
                            <option value="250,000 - 500,000" <?= (isset($data['profile']->income_range) && $data['profile']->income_range == '250,000 - 500,000') ? 'selected' : ''; ?>>₱250,000 - ₱500,000</option>
                            <option value="500,000 - 1,000,000" <?= (isset($data['profile']->income_range) && $data['profile']->income_range == '500,000 - 1,000,000') ? 'selected' : ''; ?>>₱500,000 - ₱1,000,000</option>
                            <option value="Above 1,000,000" <?= (isset($data['profile']->income_range) && $data['profile']->income_range == 'Above 1,000,000') ? 'selected' : ''; ?>>Above ₱1,000,000</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle me-1"></i>
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Section edit handlers
function editSection(section) {
    const modals = {
        'personal': 'editPersonalModal',
        'address': 'editAddressModal',
        'financial': 'editFinancialModal'
    };
    
    if (modals[section]) {
        const modal = new bootstrap.Modal(document.getElementById(modals[section]));
        modal.show();
    }
}

// Contact type toggle
document.getElementById('contactType')?.addEventListener('change', function() {
    const emailField = document.getElementById('emailField');
    const phoneField = document.getElementById('phoneField');
    
    if (this.value === 'email') {
        emailField.style.display = 'block';
        phoneField.style.display = 'none';
        document.querySelector('[name="new_email"]').required = true;
        document.querySelector('[name="new_phone"]').required = false;
    } else if (this.value === 'phone') {
        phoneField.style.display = 'block';
        emailField.style.display = 'none';
        document.querySelector('[name="new_phone"]').required = true;
        document.querySelector('[name="new_email"]').required = false;
    } else {
        emailField.style.display = 'none';
        phoneField.style.display = 'none';
    }
});

// Phone number formatting
document.querySelector('[name="new_phone"]')?.addEventListener('input', function(e) {
    this.value = this.value.replace(/\D/g, '').substring(0, 11);
});

// Cascading dropdowns for address modal
const provinceSelectModal = document.getElementById('provinceSelectModal');
const citySelectModal = document.getElementById('citySelectModal');
const barangaySelectModal = document.getElementById('barangaySelectModal');

if (provinceSelectModal && citySelectModal && barangaySelectModal) {
    // Store all barangays data
    const allBarangaysData = {};
    Array.from(barangaySelectModal.options).forEach(option => {
        if (option.value !== '') {
            const cityId = option.getAttribute('data-city');
            if (!allBarangaysData[cityId]) {
                allBarangaysData[cityId] = [];
            }
            allBarangaysData[cityId].push({
                id: option.value,
                name: option.textContent
            });
        }
    });

    provinceSelectModal.addEventListener('change', function() {
        const selectedProvince = this.value;
        
        // Show/hide cities based on selected province
        Array.from(citySelectModal.options).forEach(option => {
            if (option.value === '') {
                option.style.display = 'block';
            } else {
                const optionProvince = option.getAttribute('data-province');
                option.style.display = optionProvince === selectedProvince ? 'block' : 'none';
            }
        });
        
        // Reset city and barangay
        citySelectModal.value = '';
        barangaySelectModal.innerHTML = '<option value="">Select Barangay</option>';
    });

    citySelectModal.addEventListener('change', function() {
        const selectedCity = this.value;
        
        if (selectedCity === '') {
            barangaySelectModal.innerHTML = '<option value="">Select Barangay</option>';
            return;
        }
        
        // Clear current options
        barangaySelectModal.innerHTML = '<option value="">Select Barangay</option>';
        
        // Add barangays for selected city
        if (allBarangaysData[selectedCity]) {
            allBarangaysData[selectedCity].forEach(brgy => {
                const option = document.createElement('option');
                option.value = brgy.id;
                option.setAttribute('data-city', selectedCity);
                option.textContent = brgy.name;
                barangaySelectModal.appendChild(option);
            });
        }
    });
}

// Smooth scroll animations
document.querySelectorAll('.profile-nav-link').forEach(link => {
    link.addEventListener('click', function(e) {
        if (this.getAttribute('href').startsWith('#')) {
            e.preventDefault();
            document.querySelectorAll('.profile-nav-link').forEach(l => l.classList.remove('active'));
            this.classList.add('active');
        }
    });
});

// Auto-hide alerts after 5 seconds
setTimeout(() => {
    document.querySelectorAll('.alert').forEach(alert => {
        alert.style.transition = 'opacity 0.5s ease';
        alert.style.opacity = '0';
        setTimeout(() => alert.remove(), 500);
    });
}, 5000);
</script>
<script src="https://kit.fontawesome.com/0dcd9efbbc.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>