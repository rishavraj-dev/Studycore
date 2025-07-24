<?php
// Include the database connection file
require_once '../config/db_connect.php';

// Fetch security questions from the database to populate the dropdown
try {
    $stmt = $conn->query("SELECT id, question_text FROM security_questions ORDER BY id");
    $security_questions = $stmt->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    // Handle error - for now, we'll just create an empty array
    $security_questions = [];
    // In production, you should log this error: error_log($e->getMessage());
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - StudyCore</title>
    
    <!-- Google Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Shared Auth Stylesheet -->
    <link rel="stylesheet" href="../CSS/auth_style.css">
</head>
<body>

    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="container navbar-content">
            <a href="../index.html" class="navbar-brand">StudyCore</a>
            <button id="darkModeToggle" class="dark-mode-toggle" aria-label="Toggle dark mode">☀️</button>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <div class="auth-container">
            <h1>Create Your Account</h1>

            <!-- Display any messages from the handler (e.g., errors) -->
            <?php if (isset($_GET['error'])): ?>
                <div class="message error-message">
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>
            
            <form id="registerForm" action="handle_register.php" method="POST" onsubmit="return validateForm()">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="phone">Phone Number (Optional)</label>
                    <div class="phone-group">
                        <select id="phone_code" name="phone_code">
                            <option value="+91" selected>+91 (IN)</option>
                            <option value="+1">+1 (US)</option>
                            <option value="+44">+44 (UK)</option>
                            <option value="+61">+61 (AU)</option>
                        </select>
                        <input type="tel" id="phone_number" name="phone_number" placeholder="9876543210">
                    </div>
                </div>
                <div class="form-group">
                    <label for="dob">Date of Birth</label>
                    <input type="date" id="dob" name="dob" required>
                    <div id="dobError" class="form-error"></div>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                    <div id="passwordError" class="form-error"></div>
                </div>
                <div class="form-group">
                    <label for="security_question">Security Question</label>
                    <select id="security_question_id" name="security_question_id" required>
                        <option value="" disabled selected>-- Select a Question --</option>
                        <?php foreach ($security_questions as $question): ?>
                            <option value="<?php echo $question['id']; ?>">
                                <?php echo htmlspecialchars($question['question_text']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="security_answer">Your Answer</label>
                    <input type="text" id="security_answer" name="security_answer" required>
                </div>
                <button type="submit" class="btn">Register</button>
            </form>

            <div class="link">
                <p>Already have an account? <a href="login.php">Login here</a></p>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="main-footer">
        <div class="container">
            <div class="footer-grid">
                <div>
                    <h4>StudyCore</h4>
                    <p>Your partner in achieving academic excellence.</p>
                </div>
                <div>
                    <h4>Legal</h4>
                    <ul>
                        <li><a href="#">Terms of Service</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>© <?php echo date("Y"); ?> StudyCore. All Rights Reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Shared Auth JavaScript for dark mode -->
    <script src="../JS/auth_script.js"></script>

    <!-- Page-specific JavaScript for form validation -->
    <script>
        const password = document.getElementById('password');
        const confirm_password = document.getElementById('confirm_password');
        const passwordError = document.getElementById('passwordError');
        const dobInput = document.getElementById('dob');
        const dobError = document.getElementById('dobError');

        function validatePassword() {
            if (password.value !== confirm_password.value) {
                passwordError.textContent = "Passwords do not match!";
                return false;
            } else {
                passwordError.textContent = "";
                return true;
            }
        }
        
        function validateAge() {
            const dob = new Date(dobInput.value);
            if (isNaN(dob.getTime())) {
                dobError.textContent = "Please enter a valid date.";
                return false;
            }
            const today = new Date();
            let age = today.getFullYear() - dob.getFullYear();
            const monthDifference = today.getMonth() - dob.getMonth();
            if (monthDifference < 0 || (monthDifference === 0 && today.getDate() < dob.getDate())) {
                age--;
            }
            
            if (age < 12) {
                dobError.textContent = "You must be at least 12 years old to register.";
                return false;
            } else {
                dobError.textContent = "";
                return true;
            }
        }

        password.addEventListener('keyup', validatePassword);
        confirm_password.addEventListener('keyup', validatePassword);
        dobInput.addEventListener('change', validateAge);

        function validateForm() {
            const isPasswordValid = validatePassword();
            const isAgeValid = validateAge();
            return isPasswordValid && isAgeValid;
        }
    </script>
</body>
</html>