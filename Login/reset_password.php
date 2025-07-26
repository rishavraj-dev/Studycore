<?php
require_once '../config/db_connect.php';
$token = $_GET['token'] ?? '';
if (empty($token)) { die("Invalid reset link."); }

// Validate the token
$hashed_token = hash('sha256', $token);
$stmt = $conn->prepare("SELECT email FROM password_resets WHERE token = ? AND created_at > NOW() - INTERVAL 1 HOUR");
$stmt->bind_param("s", $hashed_token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    die("This password reset link is invalid or has expired. Please request a new one.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - StudyCore</title>
    <link rel="stylesheet" href="../CSS/auth_style.css">
</head>
<body>
    <nav class="navbar"><div class="container navbar-content"><a href="../index.html" class="navbar-brand">StudyCore</a></div></nav>
    <main class="main-content">
        <div class="auth-container">
            <h1>Create a New Password</h1>
            <?php if (isset($_GET['error'])): ?>
                <div class="message error-message"><?php echo htmlspecialchars($_GET['error']); ?></div>
            <?php endif; ?>
            <form action="handle_reset_password.php" method="POST">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                <div class="form-group">
                    <label for="password">New Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <button type="submit" class="btn">Reset Password</button>
            </form>
        </div>
    </main>
    <footer class="main-footer"><!-- Footer --></footer>
</body>
</html>