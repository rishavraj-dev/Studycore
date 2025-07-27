<?php
session_start();
require_once '../config/db_connect.php';

// --- Security Guard ---
// Standard security check for all protected student pages.
if (!isset($_SESSION['user_id'])) {
    header('Location: ../Login/login.php?error=' . urlencode('You must be logged in.'));
    exit();
}
// You should also include the session token validation for full security.


// --- Fetch All User Data for Display ---
$user_id = $_SESSION['user_id'];
$user_data = []; // Initialize an empty array to hold our data

try {
    // We select all the columns you requested, excluding the password and security details.
    $stmt_user = $conn->prepare(
        "SELECT name, username, email, phone_country_code, phone_number, dob, user_type, created_at, is_email_verified 
         FROM users WHERE id = ?"
    );
    $stmt_user->bind_param("i", $user_id);
    $stmt_user->execute();
    $result = $stmt_user->get_result();
    if ($result->num_rows === 1) {
        $user_data = $result->fetch_assoc();
    } else {
        // Handle case where user is not found, though this is unlikely if they are logged in.
        session_unset(); session_destroy();
        header('Location: ../Login/login.php?error=' . urlencode('User not found.'));
        exit();
    }
    $stmt_user->close();
} catch (Exception $e) {
    // In a real app, you would log this error.
    die("A database error occurred. Please try again later.");
}

// --- Prepare Data for Display ---
// This makes it easier to use in the HTML and handles cases where data might be NULL.
$full_name = htmlspecialchars($user_data['name'] ?? 'N/A');
$first_initial = strtoupper(substr($full_name, 0, 1));
$username = htmlspecialchars($user_data['username'] ?? 'N/A');
$email = htmlspecialchars($user_data['email'] ?? 'N/A');
$phone_number_display = ($user_data['phone_country_code'] && $user_data['phone_number'])
    ? htmlspecialchars($user_data['phone_country_code'] . ' ' . $user_data['phone_number'])
    : 'Not Provided';
$dob_display = ($user_data['dob'])
    ? date('F j, Y', strtotime($user_data['dob']))
    : 'Not Provided';
$user_type_display = htmlspecialchars(ucfirst(str_replace('_', ' ', $user_data['user_type'])));
$created_at_display = date('F j, Y \a\t g:i A', strtotime($user_data['created_at']));
$email_verified_status = ($user_data['is_email_verified'] == 1)
    ? '<span class="status-verified">Verified</span>'
    : '<span class="status-unverified">Not Verified</span>';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - StudyCore</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Reusing the same student dashboard stylesheet for a consistent look -->
    <link rel="stylesheet" href="../CSS/student_style.css">
    <style>
        /* Additional styles for the profile details view */
        .profile-card {
            background-color: var(--bg-card);
            padding: 2rem;
            border-radius: 8px;
            box-shadow: var(--shadow);
        }
        .profile-header {
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 1rem;
            margin-bottom: 1.5rem;
        }
        .profile-header h1 {
            margin: 0;
            font-size: 2rem;
        }
        .profile-details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }
        .detail-item {
            background-color: #f8fafc;
            padding: 1rem;
            border-radius: 6px;
        }
        .detail-item .label {
            font-size: 0.9em;
            color: var(--text-light);
            display: block;
            margin-bottom: 0.25rem;
        }
        .detail-item .value {
            font-size: 1.1em;
            font-weight: 500;
            color: var(--text-dark);
        }
        .status-verified { color: #16a34a; font-weight: bold; }
        .status-unverified { color: #dc2626; font-weight: bold; }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- The Header with Profile Dropdown (Reused from home.php) -->
        <header class="dashboard-header">
            <a href="home.php" class="header-logo">StudyCore</a>
            <div class="profile-section">
                <button id="profileButton" class="profile-button">
                    <div class="profile-avatar"><?php echo $first_initial; ?></div>
                    <span class="profile-name"><?php echo $full_name; ?></span>
                </button>
                <div id="profileDropdown" class="profile-dropdown">
                    <a href="dashboard.php">Dashboard</a>
                    <a href="manage_plan.php">Manage Plan</a>
                    <a href="profile_settings.php">Profile Settings</a>
                    <div class="dropdown-divider"></div>
                    <a href="../Login/logout.php">Logout</a>
                </div>
            </div>
        </header>

        <main class="main-content">
            <div class="profile-card">
                <div class="profile-header">
                    <h1>My Profile Dashboard</h1>
                </div>
                
                <div class="profile-details-grid">
                    <div class="detail-item">
                        <span class="label">Full Name</span>
                        <span class="value"><?php echo $full_name; ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="label">Username</span>
                        <span class="value"><?php echo $username; ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="label">Email Address</span>
                        <span class="value"><?php echo $email; ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="label">Email Status</span>
                        <span class="value"><?php echo $email_verified_status; ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="label">Phone Number</span>
                        <span class="value"><?php echo $phone_number_display; ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="label">Date of Birth</span>
                        <span class="value"><?php echo $dob_display; ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="label">Account Type</span>
                        <span class="value"><?php echo $user_type_display; ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="label">Date Registered</span>
                        <span class="value"><?php echo $created_at_display; ?></span>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- The same JavaScript for the dropdown menu -->
    <script src="../JS/student_dashboard.js"></script>
</body>
</html>