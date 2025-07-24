<?php
session_start();

// Security Check: Ensure a master admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'master_admin') {
    header('Location: ../Login/login.php');
    exit();
}

// Security Check: Ensure the invitation details exist in the session.
// If not, redirect back to the dashboard.
if (!isset($_SESSION['new_invitation_details'])) {
    header('Location: dashboard.php');
    exit();
}

// Get the details from the session
$details = $_SESSION['new_invitation_details'];
$name = htmlspecialchars($details['name']);
$email = htmlspecialchars($details['email']);
$temp_password = htmlspecialchars($details['temp_password']);
$token = $details['token'];

// Construct the full registration link
$registration_link = "https://{$_SERVER['HTTP_HOST']}/Employee/verify_details.php?token={$token}";

// Prepare the mailto link parts
$mailto_subject = "Your Invitation to Join StudyCore";
$mailto_body = "Hello {$name},\n\nYou have been invited to join StudyCore as an employee.\n\n" .
               "Please follow these two steps to complete your registration:\n\n" .
               "1. Go to the unique registration link below. It will expire in 6 days once the invitation is marked as sent.\n" .
               "   Registration Link: {$registration_link}\n\n" .
               "2. On the verification page, you will be asked to enter a temporary password. Your temporary password is:\n" .
               "   Temporary Password: {$temp_password}\n\n" .
               "Thank you,\nStudyCore Administration";

$mailto_link = "mailto:{$email}?subject=" . rawurlencode($mailto_subject) . "&body=" . rawurlencode($mailto_body);


// **Crucially, unset the session variable after we've used it** so this page can't be refreshed.
unset($_SESSION['new_invitation_details']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitation Created - StudyCore</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f0f2f5; color: #333; margin: 0; padding: 2rem; }
        .container { max-width: 800px; margin: 2rem auto; background-color: #fff; padding: 2.5rem; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        h1 { color: #1e40af; border-bottom: 2px solid #eee; padding-bottom: 1rem; }
        .alert-success { background-color: #d4edda; color: #155724; padding: 1rem; border-radius: 5px; margin-bottom: 1.5rem; }
        .details-box { background-color: #f8f9fa; border: 1px solid #ddd; padding: 1.5rem; border-radius: 5px; margin-bottom: 1.5rem; }
        .details-box p { margin: 0 0 1rem 0; font-size: 1.1rem; }
        .details-box code { background-color: #e9ecef; padding: 0.2rem 0.4rem; border-radius: 3px; font-family: monospace; }
        .btn-mailto { display: inline-block; padding: 0.85rem 2rem; background-color: #28a745; color: #fff; text-decoration: none; border-radius: 5px; font-weight: 600; }
        .link-back a { text-decoration: none; color: #555; font-weight: 500; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Invitation Created Successfully!</h1>
        <div class="alert-success">
            <strong>Next Step:</strong> You must now send these details to the employee.
        </div>

        <div class="details-box">
            <p><strong>Employee:</strong> <?php echo $name; ?></p>
            <p><strong>Email:</strong> <?php echo $email; ?></p>
            <p><strong>Registration Link:</strong> <code><?php echo $registration_link; ?></code></p>
            <p><strong>Temporary Password:</strong> <code><?php echo $temp_password; ?></code></p>
        </div>
        
        <p>Click the button below to open your default email client with a pre-filled message containing all the necessary details.</p>
        <a href="<?php echo $mailto_link; ?>" class="btn-mailto">Open Email Client</a>

        <hr style="margin: 2rem 0; border: none; border-top: 1px solid #eee;">

        <div class="link-back">
             <p>After you have sent the email, go back to the dashboard to start the 6-day timer for this invitation.</p>
            <a href="dashboard.php">← Back to Dashboard</a>
        </div>
    </div>
</body>
</html>