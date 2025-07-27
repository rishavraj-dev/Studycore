<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - StudyCore</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/auth_style.css">
    <style>
        /* This style hides the OTP section when the page first loads */
        #otpSection { display: none; }
        /* Style for the disabled resend link timer */
        #resendOtpLink.disabled {
            color: #999;
            cursor: not-allowed;
            text-decoration: none;
            opacity: 0.7;
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="container navbar-content">
            <a href="../index.html" class="navbar-brand">StudyCore</a>
            <button id="darkModeToggle" class="dark-mode-toggle" aria-label="Toggle dark mode">☀️</button>
        </div>
    </nav>

    <main class="main-content">
        <div class="auth-container">

            <!-- ===============================================
                 PASSWORD SECTION (Visible by default)
                 =============================================== -->
            <div id="passwordSection">
                <h1>Welcome Back!</h1>
                <div id="loginMessageContainer">
                    <!-- =================== THIS IS THE UPDATED BLOCK =================== -->
                    <?php
                    // This block now correctly handles redirects from the password reset form.
                    if (isset($_GET['status'])) {
                        $message = '';
                        $message_class = '';

                        // Handle the success case from handle_forgot_password.php
                        if ($_GET['status'] === 'reset_sent') {
                            $message = 'If an account with that email exists, a password reset link has been sent.';
                            $message_class = 'success-message';
                        
                        // Handle the failure case from handle_forgot_password.php
                        } elseif ($_GET['status'] === 'reset_failed' && isset($_GET['msg'])) {
                            $message = htmlspecialchars($_GET['msg']);
                            $message_class = 'error-message';
                        }

                        if ($message) {
                            echo "<div class=\"message {$message_class}\">{$message}</div>";
                        }
                    }
                    ?>
                    <!-- ================================================================= -->
                </div>

                <form id="loginForm">
                    <div class="form-group">
                        <label for="username">Username or Email</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <div style="text-align: right; margin-top: -0.75rem; margin-bottom: 1.25rem;">
                        <a href="#" id="forgotPasswordTrigger" style="font-size: 0.9rem; color: var(--primary-button-bg); text-decoration: none;">Forgot Password?</a>
                    </div>
                    <button type="submit" id="loginBtn" class="btn">Login</button>
                </form>
                <div class="link"><p>Don't have an account? <a href="register.php">Register here</a></p></div>
            </div>

            <!-- ===============================================
                 OTP SECTION (Hidden by default)
                 =============================================== -->
            <div id="otpSection">
                <h1>Two-Factor Authentication</h1>
                <p style="text-align: center; color: var(--text-light); margin-top: -1rem; margin-bottom: 2rem;">Please enter the code sent to your email to complete your login.</p>
                <div id="otpMessageContainer"></div>

                <form id="otpForm">
                    <!-- This hidden input stores the username for the second step -->
                    <input type="hidden" id="otpUsername" name="username">
                    <div class="form-group">
                        <label for="otp">6-Digit Code</label>
                        <input type="text" id="otp" name="otp" required maxlength="6" style="text-align: center; letter-spacing: 0.5rem;" autocomplete="one-time-code">
                    </div>
                    <button type="submit" id="otpBtn" class="btn">Verify & Login</button>
                </form>
                <div class="link">
                    <p><a href="#" id="resendOtpLink">Resend Code</a></p>
                </div>
            </div>

        </div>
    </main>

    <footer class="main-footer">
        <div class="container">
            <div class="footer-grid">
                <div><h4>StudyCore</h4><p>Your partner in achieving academic excellence.</p></div>
                <div><h4>Legal</h4><ul><li><a href="#">Terms of Service</a></li><li><a href="#">Privacy Policy</a></li></ul></div>
            </div>
            <div class="footer-bottom"><p>© <?php echo date("Y"); ?> StudyCore. All Rights Reserved.</p></div>
        </div>
    </footer>

    <div id="forgotPasswordModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Reset Your Password</h2>
                <button id="modalCloseBtn" class="modal-close-btn">×</button>
            </div>
            <p style="color: var(--text-light); margin-top: 0; margin-bottom: 1.5rem;">Enter your email address and we will send you a link to reset your password.</p>
            <form action="handle_forgot_password.php" method="POST">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <button type="submit" class="btn">Send Reset Link</button>
            </form>
        </div>
    </div>

    <!-- This script handles dark mode AND the modal popup logic -->
    <script src="../JS/auth_script.js"></script>

    <!-- This new script block handles the entire 2FA flow -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const loginForm = document.getElementById('loginForm');
            const otpForm = document.getElementById('otpForm');
            const passwordSection = document.getElementById('passwordSection');
            const otpSection = document.getElementById('otpSection');
            const loginMessageContainer = document.getElementById('loginMessageContainer');
            const otpMessageContainer = document.getElementById('otpMessageContainer');
            const otpUsernameInput = document.getElementById('otpUsername');
            const loginBtn = document.getElementById('loginBtn');
            const otpBtn = document.getElementById('otpBtn');
            const resendOtpLink = document.getElementById('resendOtpLink');

            const showMessage = (container, message, isSuccess) => {
                container.innerHTML = `<div class="message ${isSuccess ? 'success-message' : 'error-message'}">${message}</div>`;
            };

            // --- Step 1: Handle the initial password submission ---
            loginForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                loginBtn.disabled = true;
                loginBtn.textContent = 'Authenticating...';
                
                const formData = new FormData(loginForm);
                try {
                    const response = await fetch('handle_login.php', { method: 'POST', body: formData });
                    const data = await response.json();

                    if (data.status === 'success') {
                        window.location.href = data.redirect_url;
                    } else if (data.status === 'otp_required') {
                        showMessage(otpMessageContainer, data.message, true);
                        otpUsernameInput.value = formData.get('username');
                        passwordSection.style.display = 'none';
                        otpSection.style.display = 'block';
                        startResendTimer();
                    } else {
                        showMessage(loginMessageContainer, data.message, false);
                    }
                } catch (error) {
                    showMessage(loginMessageContainer, 'A network error occurred. Please check your connection or contact support.', false);
                } finally {
                    loginBtn.disabled = false;
                    loginBtn.textContent = 'Login';
                }
            });

            // --- Step 2: Handle the OTP submission ---
            otpForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                otpBtn.disabled = true;
                otpBtn.textContent = 'Verifying...';

                const formData = new FormData(otpForm);
                try {
                    const response = await fetch('handle_verify_login_otp.php', { method: 'POST', body: formData });
                    const data = await response.json();

                    if (data.status === 'success') {
                        window.location.href = data.redirect_url;
                    } else {
                        showMessage(otpMessageContainer, data.message, false);
                    }
                } catch (error) {
                    showMessage(otpMessageContainer, 'A network error occurred. Please try again.', false);
                } finally {
                    otpBtn.disabled = false;
                    otpBtn.textContent = 'Verify & Login';
                }
            });

            // --- Step 3: Handle the "Resend OTP" link ---
            let resendTimer;
            function startResendTimer() {
                let timeLeft = 90;
                resendOtpLink.classList.add('disabled');
                clearInterval(resendTimer);
                resendTimer = setInterval(() => {
                    resendOtpLink.textContent = `Resend code in ${timeLeft}s`;
                    timeLeft--;
                    if (timeLeft < 0) {
                        clearInterval(resendTimer);
                        resendOtpLink.classList.remove('disabled');
                        resendOtpLink.textContent = 'Resend Code';
                    }
                }, 1000);
            }

            resendOtpLink.addEventListener('click', (e) => {
                e.preventDefault();
                if (resendOtpLink.classList.contains('disabled')) return;
                
                // This is a clever way to re-trigger the OTP send by re-submitting the original login form
                loginForm.dispatchEvent(new Event('submit', { cancelable: true }));
            });
        });
    </script>
</body>
</html>