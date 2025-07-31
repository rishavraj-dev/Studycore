<?php
session_start();
require_once '../config/db_connect.php';

// =================================================================
//  1. SECURITY GUARD & FULL DATA FETCH
// =================================================================

if (!isset($_SESSION['user_id'])) { header('Location: ../Login/login.php'); exit(); }
$user_id = $_SESSION['user_id'];

// Fetch User's Name for the Header
try {
    $stmt_name = $conn->prepare("SELECT name FROM users WHERE id = ?");
    $stmt_name->bind_param("i", $user_id);
    $stmt_name->execute();
    $user_result = $stmt_name->get_result();
    if ($user_result->num_rows === 1) {
        $user = $user_result->fetch_assoc();
    } else { $user = ['name' => 'User']; }
    $stmt_name->close();
} catch (Exception $e) { die("Database error fetching user data."); }
$full_name = htmlspecialchars($user['name']);
$first_initial = !empty($full_name) ? strtoupper(substr($full_name, 0, 1)) : '?';


// Fetch User's CURRENT ACTIVE Subscription
$current_subscription = null;
try {
    $stmt_current = $conn->prepare("SELECT p.plan_name, us.end_date FROM user_subscriptions us JOIN plans p ON us.plan_id = p.plan_id WHERE us.user_id = ? AND us.status = 'active' AND us.end_date > NOW() ORDER BY us.end_date DESC LIMIT 1");
    $stmt_current->bind_param("i", $user_id);
    $stmt_current->execute();
    $result_current = $stmt_current->get_result();
    if ($result_current->num_rows === 1) {
        $current_subscription = $result_current->fetch_assoc();
    }
    $stmt_current->close();
} catch (Exception $e) { die("Database error fetching current plan."); }


// Fetch ALL AVAILABLE Plans for Purchase
$available_plans = [];
$current_plan_name = $current_subscription['plan_name'] ?? null;
try {
    $stmt_all = $conn->prepare("SELECT plan_id, plan_name, description, price, currency, duration_days FROM plans WHERE is_active = 1 ORDER BY price ASC");
    $stmt_all->execute();
    $available_plans = $stmt_all->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_all->close();
} catch (Exception $e) { die("Database error fetching available plans."); }

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Plan - StudyCore</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/profile.css">
    <style>
        .plans-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-top: 2rem; }
        .plan-card { border: 1px solid var(--border-color); border-radius: 8px; padding: 1.5rem; display: flex; flex-direction: column; background-color: var(--bg-card); box-shadow: var(--shadow-md); transition: transform 0.2s, box-shadow 0.2s; }
        .plan-card:hover { transform: translateY(-5px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -4px rgba(0,0,0,0.1); }
        .plan-card h3 { margin-top: 0; color: var(--primary-color); font-size: 1.25rem; }
        .plan-card .price { font-size: 2.5rem; font-weight: 700; margin: 0.5rem 0; }
        .plan-card .price small { font-size: 1rem; font-weight: 400; color: var(--text-light); }
        .plan-card .description { color: var(--text-light); flex-grow: 1; margin-bottom: 1.5rem; }
        .btn-purchase { width: 100%; text-align: center; font-weight: 600; }
        .btn-purchase.current { background-color: var(--text-light); color: var(--bg-card); cursor: not-allowed; }
        .notice-box { background-color: #fff3cd; color: #664d03; border: 1px solid #ffeeba; padding: 1rem 1.5rem; border-radius: 8px; margin-bottom: 2rem; text-align: center; }
        .notice-box code { background-color: #d1ecf1; padding: 0.2rem 0.5rem; border-radius: 4px; font-weight: bold; }
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6); z-index: 1000; display: none; justify-content: center; align-items: center; }
        .modal-overlay.active { display: flex; }
        .modal-content { background-color: var(--bg-card); padding: 2.5rem; border-radius: 8px; width: 90%; max-width: 450px; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 1rem; margin-bottom: 1.5rem; }
        .modal-header h2 { margin: 0; font-size: 1.5rem; }
        .modal-close-btn { background: none; border: none; font-size: 2.5rem; cursor: pointer; }
        .modal-footer { text-align: right; margin-top: 1.5rem; }
    </style>
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
                <h2>Your Current Plan</h2>
                <?php if ($current_subscription): ?>
                    <div class="detail-item" style="background-color: #dcfce7;"><span class="label">Plan Name</span><div class="value" style="color: #166534; font-size: 1.5rem;"><?php echo htmlspecialchars($current_subscription['plan_name']); ?></div></div>
                    <div class="profile-details-grid" style="margin-top: 1rem;"><div class="detail-item"><span class="label">Status</span><div class="value status-verified">Active</div></div><div class="detail-item"><span class="label">Valid Until</span><div class="value"><?php echo date('F j, Y', strtotime($current_subscription['end_date'])); ?></div></div></div>
                <?php else: ?>
                    <p>You do not have an active subscription. Choose a plan below to get started!</p>
                <?php endif; ?>
            </div>

            <div class="profile-card" style="margin-top: 2rem;">
                <h2>Upgrade or Change Your Plan</h2>
                <div class="notice-box"><strong>Notice:</strong> All plans are FREE for development. Use code <code>DEVFREE100</code> to activate any plan.</div>
                <div class="plans-grid">
                    <?php foreach ($available_plans as $plan): ?>
                        <div class="plan-card">
                            <h3><?php echo htmlspecialchars($plan['plan_name']); ?></h3>
                            <div class="price"><?php echo htmlspecialchars($plan['currency']); ?> <?php echo htmlspecialchars(number_format($plan['price'], 2)); ?> <small>/ <?php echo htmlspecialchars($plan['duration_days']); ?> days</small></div>
                            <p class="description"><?php echo htmlspecialchars($plan['description']); ?></p>
                            <?php if ($plan['plan_name'] === $current_plan_name): ?>
                                <button class="btn btn-purchase current" disabled>Current Plan</button>
                            <?php else: ?>
                                <button class="btn btn-purchase btn-success" style="background-color: var(--success-color);" onclick="openPurchaseModal('<?php echo $plan['plan_id']; ?>', '<?php echo htmlspecialchars(addslashes($plan['plan_name'])); ?>')">Activate Plan</button>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>

    <div id="purchaseModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalPlanName">Activate Plan</h2>
                <button id="modalCloseBtn" class="modal-close-btn">×</button>
            </div>
            <form action="handle_activate_plan.php" method="POST">
                <p>You are about to activate the <strong id="modalPlanNameToConfirm"></strong> plan.</p>
                <input type="hidden" id="modalPlanId" name="plan_id" value="">
                <div class="form-group">
                    <label for="coupon_code">Enter Activation Code</label>
                    <input type="text" id="coupon_code" name="coupon_code" required>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success" style="background-color: var(--success-color);">Confirm Activation</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="../JS/student_dashboard.js"></script>
    <script>
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

        const purchaseModal = document.getElementById('purchaseModal');
        const modalPlanName = document.getElementById('modalPlanName');
        const modalPlanNameToConfirm = document.getElementById('modalPlanNameToConfirm');
        const modalPlanIdInput = document.getElementById('modalPlanId');
        const modalCloseBtn = document.getElementById('modalCloseBtn');

        function openPurchaseModal(planId, planName) {
            modalPlanIdInput.value = planId;
            modalPlanName.textContent = `Activate: ${planName}`;
            modalPlanNameToConfirm.textContent = planName;
            purchaseModal.classList.add('active');
        }
        function closePurchaseModal() {
            purchaseModal.classList.remove('active');
        }
        modalCloseBtn.addEventListener('click', closePurchaseModal);
        purchaseModal.addEventListener('click', (event) => { if (event.target === purchaseModal) closePurchaseModal(); });
    </script>
</body>
</html>