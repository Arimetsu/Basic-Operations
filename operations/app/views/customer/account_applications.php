<?php 
require_once ROOT_PATH . '/app/views/layouts/header.php'; 
?>

<style>
.status-badge {
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.status-pending {
    background-color: #fff3cd;
    color: #856404;
    border: 2px solid #ffc107;
}
.status-under_review {
    background-color: #cfe2ff;
    color: #084298;
    border: 2px solid #0d6efd;
}
.status-approved {
    background-color: #d1e7dd;
    color: #0f5132;
    border: 2px solid #198754;
}
.status-rejected {
    background-color: #f8d7da;
    color: #842029;
    border: 2px solid #dc3545;
}
.status-cancelled {
    background-color: #e2e3e5;
    color: #41464b;
    border: 2px solid #6c757d;
}
.application-card {
    border: none;
    border-radius: 12px;
    transition: all 0.3s ease;
    overflow: hidden;
}
.application-card:hover {
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
    transform: translateY(-4px);
}
.info-label {
    font-weight: 600;
    color: #6c757d;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 4px;
}
.info-value {
    color: #212529;
    font-size: 1rem;
    font-weight: 500;
}
.stats-card {
    border: none;
    border-radius: 12px;
    transition: all 0.3s ease;
}
.stats-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0,0,0,0.1);
}
.icon-circle {
    width: 56px;
    height: 56px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
}
.application-timeline {
    border-left: 3px solid #198754;
    padding-left: 20px;
    margin-left: 10px;
}
.timeline-item {
    position: relative;
    padding-bottom: 20px;
}
.timeline-dot {
    position: absolute;
    left: -30px;
    width: 16px;
    height: 16px;
    background: #198754;
    border-radius: 50%;
    border: 3px solid white;
    box-shadow: 0 0 0 3px #198754;
}
</style>

<div class="container-fluid px-4 py-4" style="background-color: #f5f5f0; min-height: 100vh;">
    
    <?php if (isset($_SESSION['application_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" style="border-radius: 10px;">
            <i class="bi bi-check-circle-fill me-2"></i>
            <strong>Success!</strong> <?= htmlspecialchars($_SESSION['application_success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['application_success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['application_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" style="border-radius: 10px;">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <strong>Error!</strong> <?= htmlspecialchars($_SESSION['application_error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['application_error']); ?>
    <?php endif; ?>
    
    <!--------------------------- PAGE TITLE --------------------------------------------------------------------------------------->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-2" style="color: #003631;">Account Applications</h2>
            <p class="text-muted mb-0">View the status of your account applications</p>
        </div>
        <a href="<?= URLROOT ?>/customer/account_application" class="btn btn-success px-4 py-2">
            <i class="bi bi-plus-circle me-2"></i>New Application
        </a>
    </div>

    <!--------------------------- STATISTICS CARDS --------------------------------------------------------------------------------->
    <div class="row mb-4 g-3">
        <div class="col-md-3">
            <div class="card stats-card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small fw-semibold">Total Applications</p>
                            <h2 class="mb-0 fw-bold" style="color: #212529;"><?= $data['total_applications'] ?></h2>
                        </div>
                        <div class="icon-circle" style="background: linear-gradient(135deg, #198754 0%, #146c43 100%);">
                            <i class="bi bi-file-earmark-text text-white" style="font-size: 1.75rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small fw-semibold">Pending</p>
                            <h2 class="mb-0 fw-bold text-warning"><?= $data['pending_count'] ?></h2>
                        </div>
                        <div class="icon-circle" style="background: linear-gradient(135deg, #ffc107 0%, #ffca2c 100%);">
                            <i class="bi bi-clock-history text-white" style="font-size: 1.75rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small fw-semibold">Approved</p>
                            <h2 class="mb-0 fw-bold text-success"><?= $data['approved_count'] ?></h2>
                        </div>
                        <div class="icon-circle" style="background: linear-gradient(135deg, #198754 0%, #20c997 100%);">
                            <i class="bi bi-check-circle text-white" style="font-size: 1.75rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small fw-semibold">Rejected</p>
                            <h2 class="mb-0 fw-bold text-danger"><?= $data['rejected_count'] ?? 0 ?></h2>
                        </div>
                        <div class="icon-circle" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);">
                            <i class="bi bi-x-circle text-white" style="font-size: 1.75rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!--------------------------- APPLICATIONS LIST -------------------------------------------------------------------------------->
    <?php if (empty($data['applications'])): ?>
        <div class="card border-0 shadow-sm" style="border-radius: 12px;">
            <div class="card-body text-center py-5">
                <div class="mb-4">
                    <i class="bi bi-inbox" style="font-size: 4rem; color: #adb5bd;"></i>
                </div>
                <h4 class="mt-3 fw-bold text-muted">No Applications Found</h4>
                <p class="text-muted mb-4">You haven't submitted any account applications yet.</p>
                <a href="<?= URLROOT ?>/customer/account_application" class="btn btn-success px-4 py-2">
                    <i class="bi bi-plus-circle me-2"></i>Create Your First Application
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($data['applications'] as $app): ?>
                <div class="col-12">
                    <div class="card application-card shadow-sm">
                        <div class="card-body p-4">
                            <!-- Header Section -->
                            <div class="row mb-4">
                                <div class="col-lg-8">
                                    <div class="d-flex align-items-start mb-3">
                                        <div class="me-3">
                                            <div class="icon-circle" style="background: linear-gradient(135deg, #003631 0%, #198754 100%); width: 48px; height: 48px;">
                                                <i class="bi bi-file-earmark-check text-white fs-4"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h5 class="fw-bold mb-1" style="color: #003631;"><?= htmlspecialchars($app['application_number']) ?></h5>
                                            <p class="text-muted mb-0 small">
                                                <i class="bi bi-clock me-1"></i>
                                                Submitted: <?= htmlspecialchars($app['submitted_at']) ?>
                                            </p>
                                        </div>
                                        <span class="status-badge status-<?= strtolower(str_replace(' ', '_', $app['application_status'])) ?>">
                                            <?= htmlspecialchars($app['application_status']) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Main Content -->
                            <div class="row">
                                <!-- Left Column: Account Details -->
                                <div class="col-lg-6 mb-3">
                                    <h6 class="fw-bold mb-3" style="color: #003631;">
                                        <i class="bi bi-bank me-2"></i>Account Information
                                    </h6>
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <span class="info-label d-block">Account Type</span>
                                            <span class="info-value"><?= htmlspecialchars($app['account_type']) ?></span>
                                        </div>
                                        <div class="col-6">
                                            <span class="info-label d-block">Applicant</span>
                                            <span class="info-value"><?= htmlspecialchars($app['full_name']) ?></span>
                                        </div>
                                        <div class="col-6">
                                            <span class="info-label d-block">Email</span>
                                            <span class="info-value small"><?= htmlspecialchars($app['email']) ?></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Right Column: Services & Status -->
                                <div class="col-lg-6 mb-3">
                                    <h6 class="fw-bold mb-3" style="color: #003631;">
                                        <i class="bi bi-gear me-2"></i>Requested Services
                                    </h6>
                                    <div class="mb-3">
                                        <?php if ($app['wants_passbook']): ?>
                                            <span class="badge bg-success me-2 mb-2">
                                                <i class="bi bi-book me-1"></i>Passbook
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($app['wants_atm_card']): ?>
                                            <span class="badge bg-primary me-2 mb-2">
                                                <i class="bi bi-credit-card me-1"></i>ATM Card
                                            </span>
                                        <?php endif; ?>
                                        <?php if (!$app['wants_passbook'] && !$app['wants_atm_card']): ?>
                                            <span class="text-muted small">No additional services requested</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if ($app['reviewed_at']): ?>
                                        <div class="mt-3 p-3" style="background: #f8f9fa; border-radius: 8px;">
                                            <span class="info-label d-block mb-1">
                                                <i class="bi bi-check2-circle me-1"></i>Reviewed
                                            </span>
                                            <span class="info-value small"><?= htmlspecialchars($app['reviewed_at']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Expandable Details -->
                            <div class="mt-3 pt-3 border-top">
                                <a class="text-success text-decoration-none" data-bs-toggle="collapse" href="#details<?= $app['application_id'] ?>" role="button">
                                    <i class="bi bi-chevron-down me-1"></i>
                                    <span class="fw-semibold">View More Details</span>
                                </a>
                                <div class="collapse mt-3" id="details<?= $app['application_id'] ?>">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <span class="info-label d-block">Phone Number</span>
                                            <span class="info-value"><?= htmlspecialchars($app['phone_number']) ?></span>
                                        </div>
                                        <div class="col-md-6">
                                            <span class="info-label d-block">Date of Birth</span>
                                            <span class="info-value"><?= htmlspecialchars($app['date_of_birth']) ?></span>
                                        </div>
                                        <div class="col-12">
                                            <span class="info-label d-block">Address</span>
                                            <span class="info-value"><?= htmlspecialchars($app['full_address']) ?></span>
                                        </div>
                                        <div class="col-md-6">
                                            <span class="info-label d-block">ID Type</span>
                                            <span class="info-value"><?= htmlspecialchars($app['id_type']) ?></span>
                                        </div>
                                        <div class="col-md-6">
                                            <span class="info-label d-block">Employment Status</span>
                                            <span class="info-value"><?= htmlspecialchars($app['employment_status']) ?></span>
                                        </div>
                                        <?php if ($app['employer_name'] !== 'N/A'): ?>
                                            <div class="col-md-6">
                                                <span class="info-label d-block">Employer</span>
                                                <span class="info-value"><?= htmlspecialchars($app['employer_name']) ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <div class="col-md-6">
                                            <span class="info-label d-block">Annual Income</span>
                                            <span class="info-value"><?= htmlspecialchars($app['annual_income']) ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
// Auto-dismiss alerts after 5 seconds
setTimeout(() => {
    document.querySelectorAll('.alert').forEach(alert => {
        alert.style.transition = 'opacity 0.5s ease';
        alert.style.opacity = '0';
        setTimeout(() => alert.remove(), 500);
    });
}, 5000);
</script>

<?php require_once ROOT_PATH . '/app/views/layouts/footer.php'; ?>

