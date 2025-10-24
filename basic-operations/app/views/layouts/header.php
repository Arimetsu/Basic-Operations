<!-- app/views/layouts/header.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $data['title'] ?? 'Evergreen'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    




    <nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #003631;">
    <div class="container-fluid px-4">

        <!--------------------------- LOGO --------------------------------------------------------------------------------------------->
        <a class="navbar-brand d-flex align-items-center" href=<?= URLROOT .'/customer/account'; ?>>
        <img src= <?= URLROOT . "/img/logo.png";?> alt="Evergreen Logo" width="40" height="40" class="me-2">
        <span class="fw-bold">EVERGREEN</span>
        </a>

        <!--------------------------- TOGGLER FOR PHONES ------------------------------------------------------------------------------->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
        </button>

        <!--------------------------- BUTTONS FOR OTHER PAGES -------------------------------------------------------------------------->
        <div class="collapse navbar-collapse" id="navbarNav">
            <?php if((isset($_SESSION['customer_id']))): ?>
        <ul class="navbar-nav mx-auto">
            <li class="nav-item me-2">
                <a class="nav-link <?php echo ($current_page == 'dashboard') ? 'active text-warning' : ''; ?>" href=<?=URLROOT . "/customer/dashboard"?>>Home</a>
            </li>
            <li class="nav-item mx-2">
                <a class="nav-link <?php echo ($current_page == 'Account') ? 'active text-warning' : ''; ?>" href=<?= URLROOT .'/customer/account'; ?>>Accounts</a>
            </li>
            <li class="nav-item mx-2">
                <a class="nav-link <?php echo ($current_page == 'fund_transfer.php') ? 'active text-warning' : ''; ?>" href="fund_transfer.php">Fund Transfer</a>
            </li>
            <li class="nav-item mx-2">
                <a class="nav-link <?php echo ($current_page == 'transaction_history.php') ? 'active text-warning' : ''; ?>" href="transaction_history.php">Transaction History</a>
            </li>
            <li class="nav-item mx-2">
                <a class="nav-link <?php echo ($current_page == 'referral.php') ? 'active text-warning' : ''; ?>" href="referral.php">Referral</a>
            </li>
        </ul>

        <!------------------------- USERNAME AND PROFILE ----------------------------------------------------------------------------->
        <div class="d-flex align-items-center">
            <span class="text-white me-2"><?= $_SESSION['customer_first_name']?></span>
            <div class="bg-light text-dark rounded-circle d-flex justify-content-center align-items-center" style="width:35px; height:35px;">
            <i class="bi bi-person-fill"></i>
            </div>
        </div>
        </div>
    <?php endif;?>
    </div>
    </nav>