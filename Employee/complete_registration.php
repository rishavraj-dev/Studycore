<?php
session_start();
require_once '../config/db_connect.php';

// Security checks remain the same
if (!isset($_SESSION['registration_step_2_complete']) || !$_SESSION['registration_step_2_complete']) {
    die("Access Denied. Please complete the previous steps first.");
}
$token = $_SESSION['registration_token'] ?? '';
if (empty($token)) {
    die("Your registration session has expired. Please start the process again.");
}

// Fetch security questions for the dropdown
$security_questions = $conn->query("SELECT id, question_text FROM security_questions ORDER BY id")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Your Registration - StudyCore</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- The CSS from the previous version can be reused here -->
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f0f2f5; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 2rem 0; }
        .container { background-color: #fff; padding: 2.5rem 3rem; border-radius: 8px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); width: 100%; max-width: 500px; }
        h1 { color: #1e40af; margin-bottom: 2rem; text-align: center; }
        .form-group { margin-bottom: 1.25rem; }
        label { display: block; font-weight: 600; margin-bottom: 0.5rem; }
        input, select { width: 100%; padding: 0.75rem; border: 1px solid #ccc; border-radius: 5px; font-size: 1rem; box-sizing: border-box; }
        .btn { display: block; width: 100%; padding: 0.85rem; background-color: #28a745; color: #fff; border: none; border-radius: 5px; font-size: 1rem; font-weight: 600; cursor: pointer; }
        .form-error { color: #dc3545; font-size: 0.875rem; margin-top: 0.25rem; min-height: 1rem; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Step 3: Finalize Your Account</h1>
        <?php if (isset($_GET['error'])): ?>
            <p style="color: #721c24; background-color: #f8d7da; padding: 1rem; border-radius: 5px;"><?php echo htmlspecialchars($_GET['error']); ?></p>
        <?php endif; ?>
        <form id="finalRegisterForm" action="handle_complete_registration.php" method="POST" onsubmit="return validateForm()">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            <div class="form-group">
                <label for="password">Create New Permanent Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
                <div id="passwordError" class="form-error"></div>
            </div>

            <!-- DOB Field REMOVED -->

            <div class="form-group">
                <label for="security_question_id">Security Question</label>
                <select id="security_question_id" name="security_question_id" required>
                    <option value="" disabled selected>-- Please Select a Question --</option>
                    <?php foreach ($security_questions as $question): ?>
                        <option value="<?php echo $question['id']; ?>"><?php echo htmlspecialchars($question['question_text']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="security_answer">Your Answer</label>
                <input type="text" id="security_answer" name="security_answer" required>
            </div>
            <button type="submit" class="btn">Complete Registration</button>
        </form>
    </div>
    <script>
        // JavaScript validation for age is no longer needed here
        const password = document.getElementById('password');
        const confirm_password = document.getElementById('confirm_password');
        const passwordError = document.getElementById('passwordError');

        function validatePassword() {
            if (password.value.length > 0 && password.value !== confirm_password.value) {
                passwordError.textContent = "Passwords do not match!";
                return false;
            }
            passwordError.textContent = "";
            return true;
        }
        password.addEventListener('keyup', validatePassword);
        confirm_password.addEventListener('keyup', validatePassword);
        function validateForm() { return validatePassword(); }
    </script>
</body>
</html>