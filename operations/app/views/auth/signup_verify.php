<?php require_once ROOT_PATH . '/app/views/layouts/header.php'; ?>

<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Redirect if no signup session exists
if (!isset($_SESSION['signup_otp']) || !isset($_SESSION['pending_signup_data'])) {
    header('Location: ' . URLROOT . '/auth/signup');
    exit();
}

$error = '';
$resend_success = false;
$otp_expired = false;

// Check if OTP has expired (5 minutes = 300 seconds)
if (isset($_SESSION['otp_signup_time'])) {
    $elapsed_time = time() - $_SESSION['otp_signup_time'];
    if ($elapsed_time > 300) {
        $otp_expired = true;
        $error = 'Your verification code has expired. Please request a new one.';
    }
}

// Handle OTP verification
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['verify_otp'])) {
    $entered_otp = trim($_POST['otp']);
    
    if (empty($entered_otp)) {
        $error = 'Please enter the verification code.';
    } elseif ($otp_expired) {
        $error = 'Your verification code has expired. Please request a new one.';
    } elseif ($entered_otp !== $_SESSION['signup_otp']) {
        $error = 'Invalid verification code. Please try again.';
    } else {
        // OTP verified successfully, mark as verified
        $_SESSION['signup_otp_verified'] = true;
        
        // Redirect to complete registration
        header('Location: ' . URLROOT . '/auth/complete_signup');
        exit();
    }
}

// Handle resend OTP
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['resend_otp'])) {
    // Generate new OTP
    $new_otp = sprintf("%06d", mt_rand(0, 999999));
    $_SESSION['signup_otp'] = $new_otp;
    $_SESSION['otp_signup_time'] = time();
    
    // Send new OTP
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'evrgrn.64@gmail.com';
        $mail->Password   = 'dourhhbymvjejuct';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        $mail->setFrom('evrgrn.64@gmail.com', 'Evergreen Banking');
        $mail->addAddress($_SESSION['pending_signup_data']['email'], $_SESSION['pending_signup_data']['first_name']);
        
        $mail->isHTML(true);
        $mail->Subject = 'Email Verification - New Code';
        $mail->Body    = '
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
            <div style="background: linear-gradient(135deg, #003631 0%, #1a6b62 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
                <h1 style="color: white; margin: 0;">New Verification Code</h1>
            </div>
            <div style="background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px;">
                <p style="font-size: 16px; color: #333;">Hello <strong>' . htmlspecialchars($_SESSION['pending_signup_data']['first_name']) . '</strong>,</p>
                <p style="font-size: 14px; color: #666;">You requested a new verification code. Here it is:</p>
                <div style="background: white; padding: 20px; text-align: center; margin: 20px 0; border-radius: 8px; border: 2px dashed #003631;">
                    <h2 style="color: #003631; font-size: 32px; letter-spacing: 8px; margin: 0;">' . $new_otp . '</h2>
                </div>
                <p style="font-size: 13px; color: #666;">This code will expire in <strong>5 minutes</strong>.</p>
            </div>
        </div>
        ';
        
        $mail->send();
        $resend_success = true;
        $error = '';
        $otp_expired = false;
        
    } catch (Exception $e) {
        $error = 'Failed to resend code. Please try again.';
    }
}

// Calculate remaining time
$remaining_time = 300;
if (isset($_SESSION['otp_signup_time'])) {
    $elapsed = time() - $_SESSION['otp_signup_time'];
    $remaining_time = max(0, 300 - $elapsed);
}
?>

<style>
    body {
        background: linear-gradient(135deg, #f5f7fa 0%, #e9ecef 100%);
        min-height: 100vh;
        padding: 40px 20px;
    }
    .verify-container {
        max-width: 500px;
        margin: 0 auto;
        background: white;
        border-radius: 16px;
        box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
        overflow: hidden;
    }
    .verify-header {
        background: linear-gradient(135deg, #003631 0%, #0a5a4f 50%, #146c43 100%);
        padding: 40px 30px;
        text-align: center;
        color: white;
    }
    .verify-header h1 {
        margin: 0 0 10px;
        font-size: 28px;
        font-weight: 700;
    }
    .verify-header p {
        margin: 0;
        opacity: 0.9;
        font-size: 15px;
    }
    .verify-body {
        padding: 40px 30px;
    }
    .info-box {
        background: #e8f5f3;
        border-left: 4px solid #003631;
        padding: 15px 20px;
        margin-bottom: 25px;
        border-radius: 4px;
    }
    .info-box p {
        margin: 0;
        color: #003631;
        font-size: 14px;
    }
    .otp-input-group {
        display: flex;
        gap: 10px;
        justify-content: center;
        margin: 30px 0;
    }
    .otp-input {
        width: 50px;
        height: 60px;
        text-align: center;
        font-size: 24px;
        font-weight: 700;
        border: 2px solid #dee2e6;
        border-radius: 8px;
        transition: all 0.2s;
    }
    .otp-input:focus {
        border-color: #003631;
        box-shadow: 0 0 0 3px rgba(0, 54, 49, 0.1);
        outline: none;
    }
    .btn-verify {
        background: #003631;
        color: white;
        border: none;
        padding: 14px;
        font-size: 16px;
        font-weight: 600;
        border-radius: 8px;
        width: 100%;
        transition: all 0.2s;
        cursor: pointer;
    }
    .btn-verify:hover {
        background: #004d45;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 54, 49, 0.2);
    }
    .btn-verify:disabled {
        background: #6c757d;
        cursor: not-allowed;
        opacity: 0.6;
    }
    .timer {
        text-align: center;
        margin: 20px 0;
        font-size: 14px;
        color: #666;
    }
    .timer .time {
        font-weight: 700;
        color: #003631;
        font-size: 18px;
    }
    .resend-section {
        text-align: center;
        padding-top: 20px;
        border-top: 1px solid #e9ecef;
        margin-top: 30px;
    }
    .btn-resend {
        background: transparent;
        color: #003631;
        border: 2px solid #003631;
        padding: 10px 24px;
        font-weight: 600;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s;
    }
    .btn-resend:hover {
        background: #003631;
        color: white;
    }
    .btn-resend:disabled {
        border-color: #dee2e6;
        color: #6c757d;
        cursor: not-allowed;
    }
    .success-message {
        background: #d1e7dd;
        color: #0f5132;
        padding: 12px 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 14px;
    }
</style>

<div class="verify-container">
    <div class="verify-header">
        <i class="bi bi-envelope-check" style="font-size: 48px; margin-bottom: 15px; display: block;"></i>
        <h1>Verify Your Email</h1>
        <p>We sent a 6-digit code to <strong><?= htmlspecialchars($_SESSION['pending_signup_data']['email']); ?></strong></p>
    </div>
    
    <div class="verify-body">
        <?php if ($resend_success): ?>
            <div class="success-message">
                <i class="bi bi-check-circle-fill"></i>
                <span>New verification code sent successfully!</span>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?= $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="info-box">
            <p><i class="bi bi-info-circle me-2"></i>Enter the 6-digit code we sent to your email to complete your registration.</p>
        </div>
        
        <form method="POST" id="otpForm">
            <div class="otp-input-group">
                <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" required data-index="0">
                <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" required data-index="1">
                <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" required data-index="2">
                <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" required data-index="3">
                <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" required data-index="4">
                <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" required data-index="5">
            </div>
            
            <input type="hidden" name="otp" id="otpValue">
            
            <button type="submit" name="verify_otp" class="btn-verify" id="verifyBtn">
                <i class="bi bi-check-circle me-2"></i>Verify & Complete Registration
            </button>
        </form>
        
        <?php if (!$otp_expired): ?>
            <div class="timer">
                Code expires in <span class="time" id="timer"><?= gmdate("i:s", $remaining_time); ?></span>
            </div>
        <?php endif; ?>
        
        <div class="resend-section">
            <p style="font-size: 14px; color: #666; margin-bottom: 12px;">Didn't receive the code?</p>
            <form method="POST" style="display: inline;">
                <button type="submit" name="resend_otp" class="btn-resend" id="resendBtn" <?= !$otp_expired ? 'disabled' : ''; ?>>
                    <i class="bi bi-arrow-clockwise me-2"></i>Resend Code
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    // OTP input handling
    const otpInputs = document.querySelectorAll('.otp-input');
    const otpValue = document.getElementById('otpValue');
    const verifyBtn = document.getElementById('verifyBtn');
    
    otpInputs.forEach((input, index) => {
        input.addEventListener('input', (e) => {
            const value = e.target.value;
            
            // Only allow numbers
            if (!/^\d$/.test(value)) {
                e.target.value = '';
                return;
            }
            
            // Move to next input
            if (value && index < otpInputs.length - 1) {
                otpInputs[index + 1].focus();
            }
            
            // Update hidden field and button
            updateOTP();
        });
        
        input.addEventListener('keydown', (e) => {
            // Move to previous input on backspace
            if (e.key === 'Backspace' && !e.target.value && index > 0) {
                otpInputs[index - 1].focus();
            }
        });
        
        input.addEventListener('paste', (e) => {
            e.preventDefault();
            const pastedData = e.clipboardData.getData('text').slice(0, 6);
            
            if (/^\d{6}$/.test(pastedData)) {
                pastedData.split('').forEach((char, i) => {
                    if (otpInputs[i]) {
                        otpInputs[i].value = char;
                    }
                });
                updateOTP();
                otpInputs[5].focus();
            }
        });
    });
    
    function updateOTP() {
        const otp = Array.from(otpInputs).map(input => input.value).join('');
        otpValue.value = otp;
        verifyBtn.disabled = otp.length !== 6;
    }
    
    // Timer countdown
    let remainingTime = <?= $remaining_time; ?>;
    const timerElement = document.getElementById('timer');
    const resendBtn = document.getElementById('resendBtn');
    
    if (!<?= $otp_expired ? 'true' : 'false'; ?> && remainingTime > 0) {
        const countdown = setInterval(() => {
            remainingTime--;
            
            if (remainingTime <= 0) {
                clearInterval(countdown);
                timerElement.textContent = 'Expired';
                timerElement.style.color = '#dc3545';
                resendBtn.disabled = false;
            } else {
                const minutes = Math.floor(remainingTime / 60);
                const seconds = remainingTime % 60;
                timerElement.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            }
        }, 1000);
    }
    
    // Form submission
    document.getElementById('otpForm').addEventListener('submit', (e) => {
        if (otpValue.value.length !== 6) {
            e.preventDefault();
            alert('Please enter the complete 6-digit code');
        }
    });
    
    // Auto-focus first input
    otpInputs[0].focus();
</script>

<?php require_once ROOT_PATH . '/app/views/layouts/footer.php'; ?>
