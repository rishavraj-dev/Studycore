<?php
session_start();
require_once '../config/db_connect.php';

// Access granted to both 'admin' and 'master_admin'
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['admin', 'master_admin'])) {
    header('Location: ../Login/login.php?error=' . urlencode('Access Denied.'));
    exit();
}

// Data fetching logic remains the same
$invitations_query = "SELECT id, full_name, email, role_assigned, invitation_sent_at, token_expires_at FROM employees WHERE is_registered = 0 ORDER BY id DESC";
$invitations_result = $conn->query($invitations_query);
$staff_query = "SELECT emp_id, name, user_type FROM users WHERE user_type != 'student' ORDER BY id DESC";
$staff_result = $conn->query($staff_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Management - StudyCore</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Link to the new, dedicated admin stylesheet -->
    <link rel="stylesheet" href="../CSS/admin_style.css">
</head>
<body>
    <nav class="navbar">
        <a href="dashboard.php" class="navbar-brand">StudyCore Admin</a>
        <div style="display: flex; align-items: center; gap: 1rem;">
            <!-- Dark mode toggle is now included -->
            <button id="darkModeToggle" class="dark-mode-toggle" aria-label="Toggle dark mode">☀️</button>
            <a href="../Login/logout.php" class="btn-logout" style="color: white; text-decoration: none; background-color: var(--danger-color); padding: 0.5rem 1rem; border-radius: 6px;">Logout</a>
        </div>
    </nav>
    <div class="container">
        <div class="header">
            <h1>Employee Management</h1>
            <p><a href="dashboard.php">← Back to Main Dashboard</a></p>
        </div>

        <?php if (isset($_GET['message'])): ?><p class="message success"><?php echo htmlspecialchars($_GET['message']); ?></p><?php endif; ?>
        <?php if (isset($_GET['error'])): ?><p class="message error"><?php echo htmlspecialchars($_GET['error']); ?></p><?php endif; ?>

        <div class="content-card">
            <div class="tab-nav">
                <button class="tab-link active" onclick="openTab(event, 'invitations')">Manage Invitations</button>
                <button class="tab-link" onclick="openTab(event, 'staff')">Registered Staff</button>
                <button class="tab-link cta" onclick="openTab(event, 'add')"><span class="btn btn-success">Add New Employee</span></button>
            </div>

            <!-- Invitations Tab (Simplified UI) -->
            <div id="invitations" class="tab-content active">
                <div class="table-toolbar">
                    <h2>Pending Invitations</h2>
                    <input type="search" class="search-bar" id="invitationsSearch" placeholder="Search invitations...">
                </div>
                <div class="table-responsive">
                    <table>
                        <thead><tr><th>Employee Details</th><th>Assigned Role</th><th>Status</th><th>Actions</th></tr></thead>
                        <tbody id="invitationsTable">
                            <?php if ($invitations_result && $invitations_result->num_rows > 0): while ($row = $invitations_result->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($row['full_name']); ?></strong><br><small><?php echo htmlspecialchars($row['email']); ?></small></td>
                                    <td><?php echo htmlspecialchars(ucfirst($row['role_assigned'])); ?></td>
                                    <td><span class="status pending">Invitation Sent</span><br><small>Expires: <?php echo date('d M Y', strtotime($row['token_expires_at'])); ?></small></td>
                                    <td>
                                        <!-- The "Resend" button is now the primary action -->
                                        <form action="handle_resend_invitation.php" method="POST">
                                            <input type="hidden" name="employee_id" value="<?php echo $row['id']; ?>">
                                            <button type="submit" class="btn btn-warning">Resend</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; else: ?>
                                <tr><td colspan="4" style="text-align: center; padding: 2rem;">No pending invitations found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Registered Staff Tab -->
            <div id="staff" class="tab-content">
                <div class="table-toolbar"><h2>Registered Staff Members</h2><input type="search" class="search-bar" id="staffSearch" placeholder="Search by name or ID..."></div>
                <div class="table-responsive"><table>
                    <thead><tr><th>EMP ID</th><th>Name</th><th>Role</th></tr></thead>
                    <tbody id="staffTable">
                        <?php if ($staff_result && $staff_result->num_rows > 0): while ($row = $staff_result->fetch_assoc()): ?>
                            <tr><td><strong><?php echo htmlspecialchars($row['emp_id'] ?? 'N/A'); ?></strong></td><td><?php echo htmlspecialchars($row['name']); ?></td><td><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $row['user_type']))); ?></td></tr>
                        <?php endwhile; else: ?>
                            <tr><td colspan="3" style="text-align:center; padding: 2rem;">No registered staff found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table></div>
            </div>

            <!-- Add Employee Tab -->
            <div id="add" class="tab-content">
                <div style="max-width: 600px;">
                    <h2>Create & Send New Invitation</h2>
                    <p style="color: var(--text-light);">Fill out the form below. An invitation email with a secure link and password instructions will be sent automatically.</p>
                    <form action="handle_add_employee.php" method="POST">
                        <div class="form-group"><label for="full_name">Employee's Full Name</label><input type="text" id="full_name" name="full_name" required></div>
                        <div class="form-group"><label for="email">Employee's Email Address</label><input type="email" id="email" name="email" required></div>
                        <div class="form-group"><label for="dob">Date of Birth</label><input type="date" id="dob" name="dob" required></div>
                        <div class="form-group"><label for="role">Assign Role</label><select id="role" name="role" required><option value="" disabled selected>-- Select a Role --</option><option value="admin">Admin</option><option value="educator">Educator</option><option value="support">Support</option></select></div>
                        <button type="submit" class="btn btn-primary">Create & Send Invitation</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // --- Dark Mode Logic ---
        document.addEventListener('DOMContentLoaded', () => {
            const toggleButton = document.getElementById('darkModeToggle');
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
        });

        // --- Tab and Search Logic ---
        function openTab(event, tabId) {
            document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.tab-link').forEach(link => link.classList.remove('active'));
            document.getElementById(tabId).classList.add('active');
            event.currentTarget.classList.add('active');
        }
        document.querySelector('.tab-content').classList.add('active');

        function setupSearch(inputId, tableBodyId) {
            const searchInput = document.getElementById(inputId);
            const tableBody = document.getElementById(tableBodyId);
            if (!searchInput || !tableBody) return;
            const rows = tableBody.getElementsByTagName('tr');
            searchInput.addEventListener('keyup', () => {
                const filter = searchInput.value.toLowerCase();
                for (const row of rows) {
                    row.style.display = row.textContent.toLowerCase().includes(filter) ? "" : "none";
                }
            });
        }
        setupSearch('invitationsSearch', 'invitationsTable');
        setupSearch('staffSearch', 'staffTable');
    </script>
</body>
</html>