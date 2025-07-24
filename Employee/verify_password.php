<?php
require_once '../config/db_connect.php';

// This page can be accessed via POST (from verify_details) or GET (if returning with an error)
$token = $_POST['token'] ?? $_GET['token'] ?? '';

if (empty($token)) {
    die("Error: Registration token is missing.");
}

// Re-validate token to ensure it's still valid
try {
    $stmt = $conn->prepare("SELECT id FROM employees WHERE registration_token = ? AND is_registered = 0 AND (token_expires_at > NOW()) LIMIT 1");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows !== 1) {
        die("Error: This registration link is invalid, expired, or has already been used.");
    }
    $stmt->close();
} catch (Exception $e) {
    die("A database error occurred.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Password - StudyCore</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f0f2f5; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .container { background-color: #fff; padding: 2.5rem 3rem; border-radius: 8px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); width: 100%; max-width: 450px; }
        h1 { color: #1e40af; margin-bottom: 1rem; text-align: center; }
        p { color: #555; text-align: center; margin-bottom: 2rem; }
        .form-group { margin-bottom: 1.5rem; }
        label { display: block; font-weight: 600; margin-bottom: 0.5rem; }
        input { width: 100%; padding: 0.75rem; border: 1px solid #ccc; border-radius: 5px; font-size: 1rem; box-sizing: border-box; }
        .btn { display: block; width: 100%; padding: 0.85rem; background-color: #28a745; color: #fff; border: none; border-radius: 5px; font-size: 1rem; font-weight: 600; cursor: pointer; }
        .error-message { color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 1rem; border-radius: 5px; margin-bottom: 1.5rem; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Step 2: Enter Temporary Password</h1>
        <p>Please enter the temporary password that was sent to your email.</p>

        <?php if (isset($_GET['error'])): ?>
            <div class="error-message"><?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>

        <form action="handle_verify_password.php" method="POST">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            <div class="form-group">
                <label for="temp_password">Temporary Password</label>
                <input type="password" id="temp_password" name="temp_password" required>
            </div>
            <button type="submit" class="btn">Verify Password</button>
        </form>
    </div>
</body>
</html>