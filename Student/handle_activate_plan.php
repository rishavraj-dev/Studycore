<?php
session_start();
require_once '../config/db_connect.php';

// --- 1. Security & Validation ---
if (!isset($_SESSION['user_id'])) {
    header('Location: ../Login/login.php');
    exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: manage_plan.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$plan_id = $_POST['plan_id'] ?? null;
$coupon_code = trim($_POST['coupon_code'] ?? '');

// --- 2. Check the Activation Code ---
if ($coupon_code !== 'DEVFREE100') {
    header('Location: manage_plan.php?err=' . urlencode('Invalid activation code.'));
    exit();
}
if (empty($plan_id)) {
    header('Location: manage_plan.php?err=' . urlencode('Please select a plan to activate.'));
    exit();
}

// --- 3. Database Transaction to Change Plan ---
$conn->begin_transaction();
try {
    // Step A: Cancel any current active subscription for this user
    $stmt_cancel = $conn->prepare("UPDATE user_subscriptions SET status = 'cancelled' WHERE user_id = ? AND status = 'active'");
    $stmt_cancel->bind_param("i", $user_id);
    $stmt_cancel->execute();
    $stmt_cancel->close();

    // Step B: Fetch the duration of the new plan
    $stmt_plan = $conn->prepare("SELECT duration_days FROM plans WHERE plan_id = ?");
    $stmt_plan->bind_param("i", $plan_id);
    $stmt_plan->execute();
    $result_plan = $stmt_plan->get_result();
    if ($result_plan->num_rows !== 1) {
        throw new Exception("Selected plan not found.");
    }
    $plan = $result_plan->fetch_assoc();
    $duration_days = $plan['duration_days'];
    $stmt_plan->close();

    // Step C: Calculate start and end dates for the new subscription
    $start_date = date('Y-m-d H:i:s');
    $end_date = date('Y-m-d H:i:s', strtotime("+{$duration_days} days"));

    // Step D: Insert the new active subscription
    $stmt_insert = $conn->prepare("INSERT INTO user_subscriptions (user_id, plan_id, start_date, end_date, status) VALUES (?, ?, ?, ?, 'active')");
    $stmt_insert->bind_param("iiss", $user_id, $plan_id, $start_date, $end_date);
    $stmt_insert->execute();
    $stmt_insert->close();

    // If all steps were successful, commit the transaction
    $conn->commit();

    header('Location: manage_plan.php?msg=' . urlencode('Plan activated successfully! Your new plan is now active.'));
    exit();

} catch (Exception $e) {
    // If any step failed, roll back all database changes
    $conn->rollback();
    error_log("Plan activation error: " . $e->getMessage());
    header('Location: manage_plan.php?err=' . urlencode('A system error occurred. Could not activate the plan.'));
    exit();
}
?>