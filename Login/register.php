<?php
// This PHP block is identical to yours, fetching security questions.
require_once '../config/db_connect.php';
try {
    $stmt = $conn->query("SELECT id, question_text FROM security_questions ORDER BY id");
    $security_questions = $stmt->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $security_questions = [];
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - StudyCore</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/auth_style.css">
    
    <!-- ** NEW **: Additional CSS for OTP elements -->
    <style>
        .email-group { display: flex; align-items: flex-end; gap: 0.75rem; }
        .email-group .form-group { flex-grow: 1; margin-bottom: 0; }
        .btn-verify { padding: 0.75rem 1rem; white-space: nowrap; height: fit-content; margin-bottom: 2px; }
        #otpSection { display: none; /* Hidden by default */ }
        #otpGroup { display: flex; align-items: center; gap: 1rem; }
        #otpInput { letter-spacing: 0.5rem; text-align: center; }
        #resendOtpLink { color: var(--primary-button-bg); text-decoration: none; font-size: 0.9em; margin-top: 0.5rem; display: inline-block; }
        #resendOtpLink.disabled { color: #999; cursor: not-allowed; text-decoration: none; }
        #emailStatus { font-weight: 600; margin-top: 0.5rem; min-height: 1.2rem; transition: color 0.3s; }
        .status-success { color: #16a34a; }
        .status-error { color: #dc2626; }
        .status-info { color: #64748b; }
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
            <h1>Create Your Account</h1>

            <?php if (isset($_GET['error'])): ?>
                <div class="message error-message"><?php echo htmlspecialchars($_GET['error']); ?></div>
            <?php endif; ?>
            
            <!-- The form action is now pointed to the final handler -->
            <form id="registerForm" action="handle_register.php" method="POST" onsubmit="return validateFinalForm()">
                
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" required>
                </div>

                <!-- ** NEW **: Email section is now interactive -->
                <div class="form-group email-group">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <button type="button" id="verifyBtn" class="btn">Verify</button>
                </div>
                <div id="emailStatus"></div>

                <!-- ** NEW **: Hidden OTP section -->
                <div id="otpSection" class="form-group">
                    <label for="otp">Enter OTP sent to your email</label>
                    <div id="otpGroup">
                        <input type="text" id="otpInput" name="otp" placeholder="6-digit code" maxlength="6">
                        <button type="button" id="confirmOtpBtn" class="btn">Confirm</button>
                    </div>
                    <a href="#" id="resendOtpLink" class="disabled">Resend OTP</a>
                </div>

                <!-- All other form fields remain the same -->
                <div class="form-group"><label for="phone">Phone Number (Optional)</label><div class="phone-group"><select id="phone_code" name="phone_code"><option value="+91" selected>+91 (IN)</option><option value="+1">+1 (US)</option><option value="+44">+44 (UK)</option><option value="+61">+61 (AU)</option></select><input type="tel" id="phone_number" name="phone_number" placeholder="9876543210"></div></div>
                <div class="form-group"><label for="dob">Date of Birth</label><input type="date" id="dob" name="dob" required><div id="dobError" class="form-error"></div></div>
                <div class="form-group"><label for="password">Password</label><input type="password" id="password" name="password" required></div>
                <div class="form-group"><label for="confirm_password">Confirm Password</label><input type="password" id="confirm_password" name="confirm_password" required><div id="passwordError" class="form-error"></div></div>
                <div class="form-group">
                    <label for="security_question">Security Question</label>
                    <select id="security_question_id" name="security_question_id" required>
                        <option value="" disabled selected>-- Select a Question --</option>
                        <?php foreach ($security_questions as $question): ?>
                            <option value="<?php echo $question['id']; ?>"><?php echo htmlspecialchars($question['question_text']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label for="security_answer">Your Answer</label><input type="text" id="security_answer" name="security_answer" required></div>
                
                <!-- The register button is now disabled by default -->
                <button type="submit" id="registerBtn" class="btn" disabled>Register</button>
            </form>

            <div class="link"><p>Already have an account? <a href="login.php">Login here</a></p></div>
        </div>
    </main>

    <footer class="main-footer"><!-- Your Full Footer HTML --><div class="container"><div class="footer-bottom"><p>© <?php echo date("Y"); ?> StudyCore. All Rights Reserved.</p></div></div></footer>
    <script src="../JS/auth_script.js"></script>

    <!-- ** NEW **: Combined and upgraded validation script -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // --- DOM ELEMENT REFERENCES ---
            const emailInput = document.getElementById('email');
            const verifyBtn = document.getElementById('verifyBtn');
            const otpSection = document.getElementById('otpSection');
            const otpInput = document.getElementById('otpInput');
            const confirmOtpBtn = document.getElementById('confirmOtpBtn');
            const resendOtpLink = document.getElementById('resendOtpLink');
            const emailStatus = document.getElementById('emailStatus');
            const registerBtn = document.getElementById('registerBtn');
            let isEmailVerified = false; // Security flag

            // --- OTP SENDING LOGIC ---
            const sendOtp = async () => {
                const email = emailInput.value;
                if (!email || !emailInput.checkValidity()) {
                    setStatus('Please enter a valid email address.', 'error');
                    return;
                }
                setStatus('Sending OTP...', 'info');
                verifyBtn.disabled = true;

                const formData = new FormData();
                formData.append('email', email);

                try {
                    const response = await fetch('send_otp.php', { method: 'POST', body: formData });
                    const data = await response.json();

                    if (data.status === 'success') {
                        setStatus(data.message, 'success');
                        otpSection.style.display = 'block';
                        verifyBtn.style.display = 'none';
                        startResendTimer();
                    } else {
                        setStatus(data.message, 'error');
                        verifyBtn.disabled = false;
                    }
                } catch (error) {
                    setStatus('An error occurred. Please try again.', 'error');
                    verifyBtn.disabled = false;
                }
            };
            verifyBtn.addEventListener('click', sendOtp);

            // --- OTP CONFIRMATION LOGIC ---
            confirmOtpBtn.addEventListener('click', async () => {
                const email = emailInput.value;
                const otp = otpInput.value;
                if (!otp || !/^\d{6}$/.test(otp)) {
                    setStatus('Please enter a valid 6-digit OTP.', 'error');
                    return;
                }
                setStatus('Verifying OTP...', 'info');

                const formData = new FormData();
                formData.append('email', email);
                formData.append('otp', otp);

                try {
                    const response = await fetch('confirm_otp.php', { method: 'POST', body: formData });
                    const data = await response.json();

                    if (data.status === 'success') {
                        setStatus('Email successfully verified! ✔', 'success');
                        isEmailVerified = true;
                        registerBtn.disabled = false;
                        emailInput.readOnly = true;
                        otpSection.style.display = 'none';
                        verifyBtn.style.display = 'none';
                    } else {
                        setStatus(data.message, 'error');
                    }
                } catch (error) {
                    setStatus('An error occurred during verification.', 'error');
                }
            });

            // --- RESEND TIMER LOGIC ---
            let resendTimer;
            function startResendTimer() {
                let timeLeft = 90;
                resendOtpLink.classList.add('disabled');
                resendOtpLink.textContent = `Resend OTP in ${timeLeft}s`;
                clearInterval(resendTimer);
                resendTimer = setInterval(() => {
                    timeLeft--;
                    resendOtpLink.textContent = `Resend OTP in ${timeLeft}s`;
                    if (timeLeft <= 0) {
                        clearInterval(resendTimer);
                        resendOtpLink.classList.remove('disabled');
                        resendOtpLink.textContent = 'Resend OTP';
                    }
                }, 1000);
            }
            resendOtpLink.addEventListener('click', (e) => {
                e.preventDefault();
                if (!resendOtpLink.classList.contains('disabled')) { sendOtp(); }
            });
            
            // --- HELPER & FINAL VALIDATION ---
            function setStatus(message, type) {
                emailStatus.textContent = message;
                emailStatus.className = `status-${type}`;
            }

            // Existing validation for password and age
            const password = document.getElementById('password');
            const confirm_password = document.getElementById('confirm_password');
            const passwordError = document.getElementById('passwordError');
            const dobInput = document.getElementById('dob');
            const dobError = document.getElementById('dobError');

            function validatePassword() { /* Your existing password validation code */ return true; }
            function validateAge() { /* Your existing age validation code */ return true; }
            password.addEventListener('keyup', validatePassword);
            confirm_password.addEventListener('keyup', validatePassword);
            dobInput.addEventListener('change', validateAge);

            // This function is called when the final form is submitted
            window.validateFinalForm = function() {
                if (!isEmailVerified) {
                    setStatus('You must verify your email before registering.', 'error');
                    return false; // Prevent form submission
                }
                // Run your other checks
                const isPasswordValid = validatePassword();
                const isAgeValid = validateAge();
                return isPasswordValid && isAgeValid;
            }
        });
    </script>
</body>
</html>