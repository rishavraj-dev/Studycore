<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - StudyCore</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Links to the shared CSS which includes styles for the modal -->
    <link rel="stylesheet" href="../CSS/auth_style.css">
</head>
<body>

    <!-- The full, working navigation bar -->
    <nav class="navbar">
        <div class="container navbar-content">
            <a href="../index.html" class="navbar-brand">StudyCore</a>
            <button id="darkModeToggle" class="dark-mode-toggle" aria-label="Toggle dark mode">☀️</button>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <div class="auth-container">
            <h1>Welcome Back!</h1>
            
            <!-- This container handles all possible messages from different handlers -->
            <div id="messageContainer">
                <!-- Messages for Password Reset flow -->
                <?php if (isset($_GET['status']) && $_GET['status'] === 'reset_sent'): ?>
                    <div class="message success-message">If an account with that email exists, a password reset link has been sent. Please check your spam folder.</div>
                <?php endif; ?>
                <?php if (isset($_GET['status']) && $_GET['status'] === 'reset_failed'): ?>
                    <div class="message error-message"><?php echo htmlspecialchars($_GET['msg'] ?? 'An error occurred during password reset.'); ?></div>
                <?php endif; ?>
                
                <!-- Messages for standard Login & Registration flows -->
                <?php if (isset($_GET['success'])): ?><div class="message success-message"><?php echo htmlspecialchars($_GET['success']); ?></div><?php endif; ?>
                <?php if (isset($_GET['error'])): ?><div class="message error-message"><?php echo htmlspecialchars($_GET['error']); ?></div><?php endif; ?>
            </div>

            <!-- The standard login form that submits directly to handle_login.php -->
            <form action="handle_login.php" method="POST">
                <div class="form-group">
                    <label for="username">Username or Email</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <!-- The "Forgot Password?" link that triggers the modal -->
                <div style="text-align: right; margin-top: -0.75rem; margin-bottom: 1.25rem;">
                    <a href="#" id="forgotPasswordTrigger" style="font-size: 0.9rem; color: var(--primary-button-bg); text-decoration: none;">Forgot Password?</a>
                </div>
                <button type="submit" class="btn">Login</button>
            </form>

            <div class="link"><p>Don't have an account? <a href="register.php">Register here</a></p></div>
        </div>
    </main>

    <!-- The full, working footer -->
    <footer class="main-footer">
        <div class="container">
            <div class="footer-grid">
                <div><h4>StudyCore</h4><p>Your partner in achieving academic excellence.</p></div>
                <div><h4>Legal</h4><ul><li><a href="#">Terms of Service</a></li><li><a href="#">Privacy Policy</a></li></ul></div>
            </div>
            <div class="footer-bottom"><p>© <?php echo date("Y"); ?> StudyCore. All Rights Reserved.</p></div>
        </div>
    </footer>

    <!-- The Hidden Modal for "Forgot Password" -->
    <div id="forgotPasswordModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Reset Your Password</h2>
                <button id="modalCloseBtn" class="modal-close-btn">×</button>
            </div>
            <p style="color: var(--text-light); margin-top: 0; margin-bottom: 1.5rem;">Enter your email, and we'll send a link to reset your password.</p>
            <form action="handle_forgot_password.php" method="POST">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <button type="submit" class="btn">Send Reset Link</button>
            </form>
        </div>
    </div>

    <!-- The JS file that controls both dark mode and the modal -->
    <script src="../JS/auth_script.js"></script>
</body>
</html>