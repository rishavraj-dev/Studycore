<?php
require_once '../config/db_connect.php';

// Get token from the URL
$token = $_GET['token'] ?? '';

if (empty($token)) {
    die("Error: Registration token is missing. Please use the link provided in your invitation.");
}

// Security Check: Validate the token against the database
try {
    $stmt = $conn->prepare("SELECT full_name, email FROM employees WHERE registration_token = ? AND is_registered = 0 AND (token_expires_at IS NULL OR token_expires_at > NOW()) LIMIT 1");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $employee = $result->fetch_assoc();
        $name = htmlspecialchars($employee['full_name']);
        $email = htmlspecialchars($employee['email']);
    } else {
        // Token is invalid, already used, or expired
        die("Error: This registration link is invalid, expired, or has already been used. Please contact your administrator.");
    }
    $stmt->close();
} catch (Exception $e) {
    die("A database error occurred. Please try again later.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Details - StudyCore</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f0f2f5; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .container { background-color: #fff; padding: 2.5rem 3rem; border-radius: 8px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); width: 100%; max-width: 550px; text-align: center; }
        h1 { color: #1e40af; margin-bottom: 1rem; }
        p { color: #555; line-height: 1.6; }
        .details { background-color: #f8f9fa; border: 1px solid #ddd; padding: 1.5rem; border-radius: 5px; margin: 2rem 0; text-align: left; }
        .details strong { display: inline-block; width: 80px; }
        .form-group { margin-top: 1.5rem; text-align: left; }
        .btn { display: block; width: 100%; padding: 0.85rem; background-color: #28a745; color: #fff; border: none; border-radius: 5px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: background-color 0.3s; text-decoration: none; }
        .btn:disabled { background-color: #ccc; cursor: not-allowed; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Step 1: Confirm Your Identity</h1>
        <p>Please confirm that the following details are correct before proceeding.</p>

        <div class="details">
            <p><strong>Name:</strong> <?php echo $name; ?></p>
            <p><strong>Email:</strong> <?php echo $email; ?></p>
        </div>

        <form action="verify_password.php" method="POST">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            <div class="form-group">
                <label style="display: flex; align-items: center; cursor: pointer;">
                    <input type="checkbox" id="confirmCheckbox" onchange="document.getElementById('proceedBtn').disabled = !this.checked;" style="width: auto; height: 1.2rem; margin-right: 0.5rem;">
                    I confirm that these details are correct.
                </label>
            </div>
            <button type="submit" id="proceedBtn" class="btn" disabled>Proceed to Next Step</button>
        </form>
    </div>
</body>
</html>