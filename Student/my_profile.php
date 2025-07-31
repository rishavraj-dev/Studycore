<?php
session_start();
require_once '../config/db_connect.php';

// =================================================================
//  1. SECURITY GUARD & FULL DATA FETCH
// =================================================================

// Check if a user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../Login/login.php?error=' . urlencode('You must be logged in to view your profile.'));
    exit();
}

// Full session token validation for enhanced security
try {
    $stmt_session = $conn->prepare("SELECT session_id FROM active_sessions WHERE user_id = ? AND session_token = ?");
    $stmt_session->bind_param("is", $_SESSION['user_id'], $_SESSION['session_token']);
    $stmt_session->execute();
    if ($stmt_session->get_result()->num_rows === 0) {
        session_unset(); session_destroy();
        header('Location: ../Login/login.php?error=' . urlencode('Your session is invalid. Please log in again.'));
        exit();
    }
    $stmt_session->close();
} catch (Exception $e) { die("A session validation error occurred. Please try again later."); }

// Fetch all necessary data for the currently logged-in user
$user_id = $_SESSION['user_id'];
$user_data = []; // Initialize to prevent errors

try {
    $stmt_user = $conn->prepare("SELECT name, username, email, phone_country_code, phone_number, dob, user_type, created_at, is_email_verified, is_2fa_enabled FROM users WHERE id = ?");
    $stmt_user->bind_param("i", $user_id);
    $stmt_user->execute();
    $result = $stmt_user->get_result();
    if ($result->num_rows === 1) {
        $user_data = $result->fetch_assoc();
    } else {
        session_unset(); session_destroy();
        header('Location: ../Login/login.php?error=' . urlencode('User account not found.'));
        exit();
    }
    $stmt_user->close();
} catch (Exception $e) { die("A database error occurred while fetching your profile data."); }

// --- Prepare all variables for safe display in HTML ---
$full_name = htmlspecialchars($user_data['name'] ?? 'N/A');
$first_initial = !empty($full_name) ? strtoupper(substr($full_name, 0, 1)) : '?';
$username = htmlspecialchars($user_data['username'] ?? 'N/A');
$email = htmlspecialchars($user_data['email'] ?? 'N/A');
$phone_code_current = htmlspecialchars($user_data['phone_country_code'] ?? '+91');
$phone_number_current = htmlspecialchars($user_data['phone_number'] ?? '');
$phone_display = ($user_data['phone_country_code'] && $user_data['phone_number']) ? htmlspecialchars($user_data['phone_country_code'] . ' ' . $user_data['phone_number']) : 'Not Provided';
$dob_display = ($user_data['dob']) ? date('F j, Y', strtotime($user_data['dob'])) : 'Not Provided';
$register_date_display = date('F j, Y', strtotime($user_data['created_at']));
$email_verified_status = ($user_data['is_email_verified'] == 1) ? '<span class="status-verified">Verified</span>' : '<span class="status-unverified">Not Verified</span>';
$two_fa_status = ($user_data['is_2fa_enabled'] == 1) ? '<span class="status-verified">Enabled</span>' : '<span class="status-unverified">Disabled</span>';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - StudyCore</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/profile.css">
</head>
<body>
    <div class="dashboard-container">
        <header class="dashboard-header">
            <a href="home.php" class="header-logo">StudyCore</a>
            <div style="display: flex; align-items: center; gap: 1rem;">
                <button id="darkModeToggle" class="dark-mode-toggle" aria-label="Toggle dark mode">☀️</button>
                <div class="profile-section">
                    <button id="profileButton" class="profile-button">
                        <div class="profile-avatar"><?php echo $first_initial; ?></div>
                        <span class="profile-name"><?php echo $full_name; ?></span>
                    </button>
                    <div id="profileDropdown" class="profile-dropdown">
                        <a href="home.php">Home</a>
                        <a href="my_profile.php">My Profile</a>
                        <a href="manage_plan.php">Manage Plan</a>
                        <div class="dropdown-divider"></div>
                        <a href="../Login/logout.php">Logout</a>
                    </div>
                </div>
            </div>
        </header>

        <main class="main-content">
            <?php if (isset($_GET['msg'])): ?><p class="message success"><?php echo htmlspecialchars($_GET['msg']); ?></p><?php endif; ?>
            <?php if (isset($_GET['err'])): ?><p class="message error"><?php echo htmlspecialchars($_GET['err']); ?></p><?php endif; ?>

            <div class="profile-card">
                <div class="tab-nav">
                    <button class="tab-link active" onclick="openTab(event, 'viewProfile')">Profile Overview</button>
                    <button class="tab-link" onclick="openTab(event, 'editProfile')">Edit Information</button>
                    <button class="tab-link" onclick="openTab(event, 'security')">Security</button>
                </div>

                <!-- Tab 1: View Profile (Read-Only) -->
                <div id="viewProfile" class="tab-content active">
                    <h2>Your Details</h2>
                    <div class="profile-details-grid">
                        <div class="detail-item"><span class="label">Full Name</span><div class="value"><?php echo $full_name; ?></div></div>
                        <div class="detail-item"><span class="label">Username</span><div class="value"><?php echo $username; ?></div></div>
                        <div class="detail-item"><span class="label">Email Address</span><div class="value"><?php echo $email; ?></div></div>
                        <div class="detail-item"><span class="label">Email Status</span><div class="value"><?php echo $email_verified_status; ?></div></div>
                        <div class="detail-item"><span class="label">Phone Number</span><div class="value"><?php echo $phone_display; ?></div></div>
                        <div class="detail-item"><span class="label">Date of Birth</span><div class="value"><?php echo $dob_display; ?></div></div>
                        <div class="detail-item"><span class="label">2FA Security</span><div class="value"><?php echo $two_fa_status; ?></div></div>
                        <div class="detail-item"><span class="label">Member Since</span><div class="value"><?php echo $register_date_display; ?></div></div>
                    </div>
                </div>

                <!-- Tab 2: Edit Information -->
                <div id="editProfile" class="tab-content">
                    <h2>Edit Your Information</h2>
                    <form action="handle_update_profile.php" method="POST">
                        <div class="form-group"><label>Username (cannot be changed)</label><input type="text" value="<?php echo $username; ?>" readonly></div>
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" value="<?php echo $email; ?>" readonly>
                            <small style="margin-top: 0.25rem; display: block;">
                                <a href="request_email_change.php" style="color: var(--primary-color);">Visit this page to request an email change.</a>
                            </small>
                        </div>
                        <div class="form-group"><label for="name">Full Name</label><input type="text" id="name" name="name" value="<?php echo $full_name; ?>" required></div>
                        <div class="form-group"><label for="phone">Phone Number</label><div style="display: flex; gap: 0.5rem;"><select name="phone_code" style="width: 120px;"><option value="+91" <?php if($phone_code_current == '+91') echo 'selected'; ?>>+91 (IN)</option><option value="+1" <?php if($phone_code_current == '+1') echo 'selected'; ?>>+1 (US)</option></select><input type="tel" name="phone_number" value="<?php echo $phone_number_current; ?>"></div></div>
                        <button type="submit" class="btn-save">Save Changes</button>
                    </form>
                </div>

                <!-- Tab 3: Security -->
                <div id="security" class="tab-content">
                    <h2>Security Settings</h2>
                    <div class="form-group" style="border-bottom: 1px solid var(--border-color); padding-bottom: 1.5rem; margin-bottom: 1.5rem;">
                        <h3 style="margin-top: 0;">Change Password</h3>
                        <form action="handle_change_password.php" method="POST">
                            <div class="form-group"><label for="current_password">Current Password</label><input type="password" id="current_password" name="current_password" required></div>
                            <div class="form-group"><label for="new_password">New Password (min. 8 characters)</label><input type="password" id="new_password" name="new_password" required minlength="8"></div>
                            <div class="form-group"><label for="confirm_new_password">Confirm New Password</label><input type="password" id="confirm_new_password" name="confirm_new_password" required></div>
                            <button type="submit" class="btn-save">Update Password</button>
                        </form>
                    </div>
                    <div class="form-group">
                        <h3>Two-Factor Authentication (2FA)</h3>
                        <?php if ($user_data['is_2fa_enabled'] == 1): ?>
                            <p>Status: <strong style="color: var(--success-color);">Enabled</strong>. An OTP will be sent to your email upon login.</p>
                            <form action="handle_change_2fa.php" method="POST">
                                <input type="hidden" name="action" value="disable">
                                <button type="submit" class="btn-save" style="background-color: var(--danger-color);">Disable 2FA</button>
                            </form>
                        <?php else: ?>
                            <p>Status: <strong style="color: var(--text-light);">Disabled</strong>. Enable 2FA for enhanced account security.</p>
                            <form action="handle_change_2fa.php" method="POST">
                                <input type="hidden" name="action" value="enable">
                                <button type="submit" class="btn-save">Enable 2FA</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script src="../JS/student_dashboard.js"></script>
    <script>
        // --- Tab Switching Logic ---
        function openTab(event, tabName) {
            document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.tab-link').forEach(link => link.classList.remove('active'));
            document.getElementById(tabName).classList.add('active');
            event.currentTarget.classList.add('active');
        }
        
        // --- Dark Mode Logic ---
        document.addEventListener('DOMContentLoaded', () => {
            const toggleButton = document.getElementById('darkModeToggle');
            if (toggleButton) {
                const applyTheme = (theme) => {
                    document.body.classList.toggle('dark-mode', theme === 'dark');
                    toggleButton.textContent = theme === 'dark' ? '🌙' : '☀️';
                };
                const savedTheme = localStorage.getItem('theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
                applyTheme(savedTheme);
                toggleButton.addEventListener('click', () => {
                    const newTheme = document.body.classList.contains('dark-mode') ? 'light' : 'dark';
                    localStorage.setItem('theme', newTheme);
                    applyTheme(newTheme);
                });
            }
        });
    </script>
</body>
</html>