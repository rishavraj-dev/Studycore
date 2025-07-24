<?php
session_start();
require_once '../config/db_connect.php';

// Security check for Admin and Master Admin roles
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['admin', 'master_admin'])) {
    header('Location: ../Login/login.php?error=' . urlencode('Access Denied.'));
    exit();
}

// Data Fetching for the page
$invitations_query = "SELECT id, full_name, email, dob, role_assigned, invitation_sent_at, token_expires_at, registration_token FROM employees WHERE is_registered = 0 ORDER BY id DESC";
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
    <style>
        :root {
            --primary-500: #3b82f6; --primary-600: #2563eb;
            --success-500: #22c55e; --success-600: #16a34a;
            --warning-500: #f59e0b; --warning-600: #d97706;
            --danger-500: #ef4444;  --danger-600: #dc2626;
            --slate-100: #f1f5f9; --slate-200: #e2e8f0; --slate-400: #94a3b8;
            --slate-600: #475569; --slate-800: #1e293b; --white: #ffffff;
        }
        * { box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background-color: var(--slate-100); color: var(--slate-800); margin: 0; }
        .container { max-width: 1400px; margin: 0 auto; padding: 1.5rem; }
        .navbar { background-color: var(--white); padding: 1rem 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 100; }
        .header { margin-bottom: 1.5rem; }
        .content-card { background-color: var(--white); border-radius: 0.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .tab-nav { display: flex; border-bottom: 1px solid var(--slate-200); padding: 0 1.5rem; }
        .tab-link { padding: 0.75rem 0; margin: 0 1rem; border: none; background: none; cursor: pointer; font-size: 1rem; font-weight: 600; color: var(--slate-600); border-bottom: 3px solid transparent; }
        .tab-link.active { color: var(--primary-600); border-bottom-color: var(--primary-600); }
        .tab-link.cta { margin-left: auto; }
        .tab-content { display: none; padding: 1.5rem; }
        .tab-content.active { display: block; animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .table-toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
        .search-bar { padding: 0.5rem 0.75rem; border: 1px solid var(--slate-200); border-radius: 0.375rem; }
        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 0.75rem 1rem; border-bottom: 1px solid var(--slate-200); text-align: left; white-space: nowrap; }
        th { font-size: 0.75rem; text-transform: uppercase; color: var(--slate-400); }
        .form-group { margin-bottom: 1.25rem; }
        label { display: block; font-weight: 500; margin-bottom: 0.25rem; }
        input[type="text"], input[type="email"], input[type="date"], select { width: 100%; padding: 0.5rem 0.75rem; border: 1px solid var(--slate-200); border-radius: 0.375rem; }
        .btn { padding: 0.5rem 1rem; border-radius: 0.375rem; border: none; font-weight: 600; cursor: pointer; transition: background-color 0.2s; }
        .btn-primary { background-color: var(--primary-500); color: var(--white); }
        .btn-success { background-color: var(--success-500); color: var(--white); }
        .btn-warning { background-color: var(--warning-500); color: var(--white); }
        .btn-logout { background-color: var(--danger-500); color: var(--white); }
        .status { padding: 0.25rem 0.75rem; border-radius: 999px; font-weight: 500; font-size: 0.8rem; }
        .status-sent { background-color: #dcfce7; color: #16a34a; }
        .status-pending { background-color: #fef3c7; color: #d97706; }
        .confirmation-form { display: none; align-items: center; margin-left: 0.5rem; }
        .modal-overlay { position: fixed; inset: 0; background-color: rgba(0,0,0,0.6); z-index: 1000; display: none; justify-content: center; align-items: center; }
        .modal-overlay.active { display: flex; }
        .modal-content { background-color: var(--white); padding: 2rem; border-radius: 0.5rem; max-width: 600px; width: 90%; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--slate-200); padding-bottom: 1rem; margin-bottom: 1rem; }
        .modal-body textarea { width: 100%; min-height: 200px; resize: vertical; }
        .modal-footer { text-align: right; margin-top: 1.5rem; border-top: 1px solid var(--slate-200); padding-top: 1rem; }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="dashboard.php" style="text-decoration: none; font-size: 1.5rem; font-weight: 700; color: var(--primary-600);">StudyCore Admin</a>
        <a href="../Login/logout.php" class="btn btn-logout">Logout</a>
    </nav>

    <div class="container">
        <div class="header"><h1>Employee Management</h1><p><a href="dashboard.php" style="color: var(--slate-600);">← Back to Dashboard</a></p></div>
        
        <?php if (isset($_GET['message'])): ?><div style="background-color: #dcfce7; color: #166534; padding: 1rem; border-radius: 0.375rem; margin-bottom: 1.5rem;"><?php echo htmlspecialchars($_GET['message']); ?></div><?php endif; ?>
        <?php if (isset($_GET['error'])): ?><div style="background-color: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 0.375rem; margin-bottom: 1.5rem;"><?php echo htmlspecialchars($_GET['error']); ?></div><?php endif; ?>

        <div class="content-card">
            <div class="tab-nav">
                <button class="tab-link active" onclick="openTab(event, 'invitations')">Invitations</button>
                <button class="tab-link" onclick="openTab(event, 'staff')">Registered Staff</button>
                <div class="tab-link cta"><button class="btn btn-success" onclick="openTab(event, 'add')">Add New Employee</button></div>
            </div>

            <!-- Invitations Tab -->
            <div id="invitations" class="tab-content active">
                <div class="table-toolbar"><h2>Pending & Sent Invitations</h2><input type="search" class="search-bar" id="invitationsSearch" placeholder="Search invitations..."></div>
                <div class="table-responsive"><table>
                    <thead><tr><th>Employee Details</th><th>Role</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody id="invitationsTable">
                        <?php if ($invitations_result && $invitations_result->num_rows > 0): while ($row = $invitations_result->fetch_assoc()): ?>
                            <tr
                                data-id="<?php echo $row['id']; ?>"
                                data-name="<?php echo htmlspecialchars($row['full_name']); ?>"
                                data-email="<?php echo htmlspecialchars($row['email']); ?>"
                                data-dob="<?php echo htmlspecialchars($row['dob']); ?>"
                                data-token="<?php echo $row['registration_token']; ?>"
                            >
                                <td><strong><?php echo htmlspecialchars($row['full_name']); ?></strong><br><small><?php echo htmlspecialchars($row['email']); ?></small></td>
                                <td><?php echo htmlspecialchars(ucfirst($row['role_assigned'])); ?></td>
                                <td><?php echo ($row['invitation_sent_at']) ? '<span class="status status-sent">Sent</span>' : '<span class="status status-pending">Pending</span>'; ?></td>
                                <td style="display: flex; align-items: center; gap: 0.5rem;">
                                    <button class="btn btn-primary" onclick="openInvitationModal(this)">Prepare</button>
                                    <form action="handle_start_timer.php" method="POST" class="confirmation-form" id="form-<?php echo $row['id']; ?>"><input type="hidden" name="employee_id" value="<?php echo $row['id']; ?>"><button type="submit" name="confirmation" value="1" class="btn btn-success">Confirm Sent</button></form>
                                    <?php if ($row['invitation_sent_at']): ?>
                                        <form action="handle_resend_invitation.php" method="POST"><input type="hidden" name="employee_id" value="<?php echo $row['id']; ?>"><button type="submit" class="btn btn-warning">Reset</button></form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; else: ?>
                            <tr><td colspan="4" style="text-align: center; padding: 2rem;">No pending invitations.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table></div>
            </div>

            <!-- Staff Tab -->
            <div id="staff" class="tab-content">
                <div class="table-toolbar"><h2>Registered Staff Members</h2><input type="search" class="search-bar" id="staffSearch" placeholder="Search staff..."></div>
                <div class="table-responsive"><table>
                    <thead><tr><th>EMP ID</th><th>Name</th><th>Role</th></tr></thead>
                    <tbody id="staffTable">
                        <?php if ($staff_result && $staff_result->num_rows > 0): while ($row = $staff_result->fetch_assoc()): ?>
                            <tr><td><strong><?php echo htmlspecialchars($row['emp_id'] ?? 'N/A'); ?></strong></td><td><?php echo htmlspecialchars($row['name']); ?></td><td><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $row['user_type']))); ?></td></tr>
                        <?php endwhile; else: ?>
                            <tr><td colspan="3" style="text-align: center; padding: 2rem;">No registered staff found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table></div>
            </div>

            <!-- Add Employee Tab -->
            <div id="add" class="tab-content">
                <div style="max-width: 600px;"><h2>Create a New Invitation</h2><form action="handle_add_employee.php" method="POST"><div class="form-group"><label for="full_name">Employee's Full Name</label><input type="text" id="full_name" name="full_name" required></div><div class="form-group"><label for="email">Employee's Email Address</label><input type="email" id="email" name="email" required></div><div class="form-group"><label for="dob">Date of Birth</label><input type="date" id="dob" name="dob" required></div><div class="form-group"><label for="role">Assign Role</label><select id="role" name="role" required><option value="" disabled selected>-- Select a Role --</option><option value="admin">Admin</option><option value="educator">Educator</option><option value="support">Support</option></select></div><button type="submit" class="btn btn-primary">Create Invitation</button></form></div>
            </div>
        </div>
    </div>

    <!-- Modal HTML -->
    <div id="invitationModal" class="modal-overlay" onclick="closeInvitationModal()">
        <div class="modal-content" onclick="event.stopPropagation()">
            <div class="modal-header"><h2>Prepare Invitation</h2><button onclick="closeInvitationModal()" style="background:none; border:none; font-size:1.5rem; cursor:pointer;">×</button></div>
            <div class="modal-body">
                <p>Review the details, then click below to open your email client.</p>
                <div class="form-group"><label>To:</label><input type="text" id="modal-to" readonly></div>
                <div class="form-group"><label>Subject:</label><input type="text" id="modal-subject"></div>
                <div class="form-group"><label>Body:</label><textarea id="modal-body"></textarea></div>
            </div>
            <div class="modal-footer"><button class="btn btn-success" onclick="sendEmailFromModal()">Open Mail Client</button></div>
        </div>
    </div>

    <script>
        const modal = document.getElementById('invitationModal');
        let activeRowId = null; // To track which row's confirmation to show

        function openTab(event, tabId) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.tab-link').forEach(el => el.classList.remove('active'));
            document.getElementById(tabId).classList.add('active');
            event.currentTarget.classList.add('active');
        }

        function setupSearch(inputId, tableId) {
            const searchInput = document.getElementById(inputId);
            const table = document.getElementById(tableId);
            if (!searchInput || !table) return;
            const rows = table.getElementsByTagName('tr');
            searchInput.addEventListener('keyup', function() {
                const filter = this.value.toLowerCase();
                for (let row of rows) {
                    row.style.display = (row.textContent || row.innerText).toLowerCase().includes(filter) ? '' : 'none';
                }
            });
        }
        setupSearch('invitationsSearch', 'invitationsTable');
        setupSearch('staffSearch', 'staffTable');

        function openInvitationModal(button) {
            const row = button.closest('tr');
            activeRowId = row.dataset.id; // Store the ID of the row we're working with
            const name = row.dataset.name;
            const email = row.dataset.email;
            const dob = row.dataset.dob;
            const token = row.dataset.token;
            
            if (!dob) { alert('Error: DOB is missing. Please reset this invitation.'); return; }
            
            const nameParts = name.split(' ');
            const firstName = nameParts[0].charAt(0).toUpperCase() + nameParts[0].slice(1).toLowerCase();
            const date = new Date(dob);
            const dobFormatted = ('0' + date.getDate()).slice(-2) + ('0' + (date.getMonth() + 1)).slice(-2) + String(date.getFullYear()).slice(-2);
            const tempPasswordInstruction = `${firstName}@${dobFormatted}`;
            const registrationLink = `https://${window.location.host}/Employee/verify_details.php?token=${token}`;
            
            const subject = "Your Invitation to Join StudyCore";
            const body = `Hello ${name},\n\nYou have been invited to join the StudyCore platform.\n\n` +
                         `1. Go to this unique registration link:\n${registrationLink}\n\n` +
                         `2. To verify your account, you will need a temporary password. It is your First Name (capitalized) followed by '@' and your date of birth in DDMMYY format.\n` +
                         `   Example: ${tempPasswordInstruction}\n\n` +
                         `Thank you,\nStudyCore Administration`;
            
            document.getElementById('modal-to').value = email;
            document.getElementById('modal-subject').value = subject;
            document.getElementById('modal-body').value = body;
            modal.classList.add('active');
        }

        function closeInvitationModal() {
            modal.classList.remove('active');
        }

        function sendEmailFromModal() {
            const to = document.getElementById('modal-to').value;
            const subject = encodeURIComponent(document.getElementById('modal-subject').value);
            const body = encodeURIComponent(document.getElementById('modal-body').value);
            window.open(`mailto:${to}?subject=${subject}&body=${body}`, '_blank');
            closeInvitationModal();
            
            // After closing modal, show the confirmation button for the active row
            if (activeRowId) {
                const form = document.getElementById(`form-${activeRowId}`);
                if (form) form.style.display = 'inline-flex';
            }
        }
    </script>
</body>
</html>