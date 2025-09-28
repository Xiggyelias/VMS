<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/middleware/security.php';
SecurityMiddleware::initialize();

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate CSRF token for forms and AJAX
$csrfToken = SecurityMiddleware::generateCSRFToken();

// Require admin access
requireAdmin();

// Function to connect to the database
function getDBConnection() {
    $conn = new mysqli("localhost", "root", "", "vehicleregistrationsystem");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    return $conn;
}

// Initialize messages
$success_message = '';
$error_message = '';

// Handle form submission (Create report with optional fields and file upload)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = getDBConnection();
    $createdAt = date('Y-m-d H:i:s');
    $adminId = $_SESSION['admin_id'] ?? null;

    if (!$adminId) {
        $error_message = "Please log in again to continue.";
    } else {
        // Discover existing columns for safe/dynamic INSERT
        $existing = [];
        if ($cols = $conn->query("SHOW COLUMNS FROM admin_reports")) {
            while ($row = $cols->fetch_assoc()) { $existing[strtolower($row['Field'])] = true; }
            $cols->close();
        }

        // Inputs
        $title = trim((string)($_POST['title'] ?? ''));
        $description = trim((string)($_POST['content'] ?? ''));
        $category = trim((string)($_POST['type'] ?? ''));
        $regNumber = trim((string)($_POST['regNumber'] ?? ''));
        $status = trim((string)($_POST['status'] ?? 'open'));
        $officer = trim((string)($_POST['officer'] ?? ''));
        $reportDate = $_POST['report_date'] ?? date('Y-m-d');

        if ($title === '' || $description === '' || $category === '') {
            $error_message = 'Please fill in all required fields.';
        } else {
            // File upload (images/PDF)
            $filePath = null;
            if (!empty($_FILES['evidence']['name'])) {
                $allowed = ['image/jpeg','image/png','image/gif','application/pdf'];
                $tmp = $_FILES['evidence']['tmp_name'] ?? '';
                $mime = $tmp && file_exists($tmp) ? @mime_content_type($tmp) : '';
                if (!$tmp || !in_array($mime, $allowed, true)) {
                    $error_message = 'Invalid file type. Allowed: JPG, PNG, GIF, PDF.';
                } else {
                    $ext = strtolower(pathinfo($_FILES['evidence']['name'], PATHINFO_EXTENSION));
                    $safeName = 'report_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                    $uploadDir = __DIR__ . '/uploads/reports';
                    if (!is_dir($uploadDir)) { @mkdir($uploadDir, 0755, true); }
                    $dest = $uploadDir . '/' . $safeName;
                    if (move_uploaded_file($tmp, $dest)) {
                        $filePath = 'uploads/reports/' . $safeName;
                    } else {
                        $error_message = 'Failed to upload file.';
                    }
                }
            }

            if (empty($error_message)) {
                $columns = [];
                $placeholders = [];
                $values = [];
                $types = '';

                if (isset($existing['title'])) { $columns[] = 'title'; $placeholders[] = '?'; $values[] = $title; $types .= 's'; }
                if (isset($existing['description'])) { $columns[] = 'description'; $placeholders[] = '?'; $values[] = $description; $types .= 's'; }
                if (isset($existing['category'])) { $columns[] = 'category'; $placeholders[] = '?'; $values[] = $category; $types .= 's'; }
                if (isset($existing['reg_number']) && $regNumber !== '') { $columns[] = 'reg_number'; $placeholders[] = '?'; $values[] = $regNumber; $types .= 's'; }
                if (isset($existing['status'])) { $columns[] = 'status'; $placeholders[] = '?'; $values[] = $status; $types .= 's'; }
                if (isset($existing['officer'])) { $columns[] = 'officer'; $placeholders[] = '?'; $values[] = $officer; $types .= 's'; }
                if (isset($existing['report_date'])) { $columns[] = 'report_date'; $placeholders[] = '?'; $values[] = $reportDate; $types .= 's'; }
                if (isset($existing['file_path']) && $filePath) { $columns[] = 'file_path'; $placeholders[] = '?'; $values[] = $filePath; $types .= 's'; }
                if (isset($existing['admin_id'])) { $columns[] = 'admin_id'; $placeholders[] = '?'; $values[] = $adminId; $types .= 'i'; }
                if (isset($existing['created_at'])) { $columns[] = 'created_at'; $placeholders[] = '?'; $values[] = $createdAt; $types .= 's'; }

                if (count($columns) > 0) {
                    $sql = 'INSERT INTO admin_reports (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')';
                    $stmt = $conn->prepare($sql);
                    if ($stmt) {
                        $stmt->bind_param($types, ...$values);
    if ($stmt->execute()) {
                            header('Location: admin_reports.php?success=1');
                exit();
    } else {
                            $error_message = 'Error creating report: ' . $stmt->error;
    }
    $stmt->close();
                    } else {
                        $error_message = 'Prepare failed: ' . $conn->error;
                    }
                } else {
                    $error_message = 'No compatible columns found in admin_reports table.';
                }
            }
        }
    }
    $conn->close();
}

// Show success message only once, then clear it from the URL
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $success_message = "Report created successfully!";
    echo '<script>if (window.history.replaceState) { window.history.replaceState(null, null, window.location.pathname); }</script>';
}

// Fetch all reports
$conn = getDBConnection();
$stmt = $conn->prepare("SELECT * FROM admin_reports ORDER BY created_at DESC");
$stmt->execute();
$reports = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reports - Vehicle Registration System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken) ?>">
    <?php includeCommonAssets(); ?>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- DataTables + Buttons (for search, pagination, export) -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
    <style>
        :root {
            --primary-red: #d00000;
            --primary-red-dark: #b00000;
            --white: #ffffff;
            --black: #000000;
            --gray-100: #f8f9fa;
            --gray-200: #e9ecef;
            --gray-300: #dee2e6;
            --gray-600: #6c757d;
            --gray-800: #343a40;
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 6px rgba(0,0,0,0.1);
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            margin: 0;
            background-color: var(--gray-100);
            color: var(--gray-800);
            line-height: 1.5;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .header { background: linear-gradient(90deg, rgba(208,0,0,1) 0%, rgba(176,0,0,1) 100%); color: var(--white); }

        .header h1 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .header-logo { width: 80px; }
        .header-logo img { width: 100%; height: auto; }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .admin-nav {
            background: var(--white);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-sm);
        }

        .admin-nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .admin-nav a {
            text-decoration: none;
            color: var(--gray-800);
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: all 0.2s ease;
        }

        /* admin nav hover/active handled by shared CSS */

        .report-form, .reports-list {
            background-color: var(--white);
            padding: 2rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-sm);
        }

        .filters {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 0.75rem;
            margin-bottom: 1rem;
        }
        .filters .form-input { padding: 0.5rem; }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--gray-800);
        }

        .form-input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--gray-300);
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.2s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary-red);
            box-shadow: 0 0 0 3px rgba(208, 0, 0, 0.1);
        }

        textarea.form-input {
            min-height: 150px;
            resize: vertical;
        }

        .btn {
            padding: 0.6rem 1.2rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .btn-primary {
            background-color: var(--primary-red);
            color: var(--white);
        }

        .btn-primary:hover { background-color: var(--primary-red-600); transform: translateY(-1px); }

        .btn-danger {
            background-color: #dc3545;
            color: var(--white);
        }

        .btn-danger:hover {
            background-color: #c82333;
            transform: translateY(-1px);
        }

        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 5px;
            font-size: 0.9375rem;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .report-card {
            border: 1px solid var(--gray-200);
            padding: 1.5rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            background-color: var(--white);
            transition: all 0.2s ease;
        }

        .report-card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-1px);
        }

        .report-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--gray-800);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .report-meta {
            font-size: 0.875rem;
            color: var(--gray-600);
            margin-bottom: 1rem;
        }

        .report-content {
            color: var(--gray-800);
            margin-bottom: 1rem;
            line-height: 1.6;
        }

        .report-type-badge {
            padding: 0.2rem 0.6rem;
            border-radius: 10px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .type-incident {
            background-color: #ffe6e6;
            color: var(--primary-red);
        }

        .type-maintenance {
            background-color: #e6ffe6;
            color: #007200;
        }

        .type-general {
            background-color: #e6f3ff;
            color: #004080;
        }

        .report-actions {
            margin-top: 1rem;
            display: flex;
            gap: 0.5rem;
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            header {
                padding: 1rem;
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .admin-nav ul {
                flex-direction: column;
            }

            .admin-nav a {
                display: block;
                text-align: center;
                padding: 0.75rem;
            }

            .report-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="header-left">
                    <div class="header-logo" style="width: 80px;">
                        <a href="admin-dashboard.php">
                            <img src="assets/images/AULogo.png" alt="AULogo">
                        </a>
                    </div>
                    <h1>Admin - Reports</h1>
                </div>
                <div class="header-right">
                    <button onclick="logout()" class="btn btn-logout">Logout</button>
                </div>
            </div>
        </div>
    </header>

    <div class="container">
        <nav class="admin-nav">
            <ul>
                <li><a href="admin-dashboard.php">Dashboard</a></li>
                <li><a href="owner-list.php">Manage Owners</a></li>
                <li><a href="vehicle-list.php">Manage Vehicles</a></li>
                <li><a href="manage-disk-numbers.php">Manage Disk Numbers</a></li>
                <li><a href="admin_reports.php" class="active">Reports</a></li>
                <li><a href="user-dashboard.php">User View</a></li>
            </ul>
        </nav>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
        <?php endif; ?>
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>

        <div class="report-form">
            <h2>Create New Report</h2>
            <form method="POST" action="" id="reportForm" enctype="multipart/form-data">
                <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" name="title" id="title" class="form-input" required>
                </div>
                <div class="form-group">
                    <label for="type">Type</label>
                    <select name="type" id="type" class="form-input" required>
                        <option value="">Select Type</option>
                        <option value="incident">Incident</option>
                        <option value="maintenance">Maintenance</option>
                        <option value="general">General</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="regNumber">Vehicle Registration Number</label>
                    <input type="text" name="regNumber" id="regNumber" class="form-input" placeholder="e.g., ABC123" list="reg_suggestions">
                    <datalist id="reg_suggestions">
                        <?php
                        try {
                            $conn = getDBConnection();
                            $rs = $conn->query("SELECT regNumber FROM vehicles ORDER BY regNumber ASC LIMIT 200");
                            if ($rs) { while ($row = $rs->fetch_assoc()) { echo '<option value="'.htmlspecialchars($row['regNumber']).'"></option>'; } $rs->close(); }
                            $conn->close();
                        } catch (Exception $e) {}
                        ?>
                    </datalist>
                </div>
                <div class="form-group">
                    <label for="report_date">Report Date</label>
                    <input type="date" name="report_date" id="report_date" class="form-input" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-group">
                    <label for="status">Status</label>
                    <select name="status" id="status" class="form-input">
                        <option value="open">Open</option>
                        <option value="in_progress">In Progress</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="officer">Officer In Charge</label>
                    <input type="text" name="officer" id="officer" class="form-input" placeholder="Full name of officer">
                </div>
                <div class="form-group">
                    <label for="content">Content</label>
                    <textarea name="content" id="content" class="form-input" required></textarea>
                </div>
                <div class="form-group">
                    <label for="evidence">Attach Evidence (Images/PDF)</label>
                    <input type="file" name="evidence" id="evidence" class="form-input" accept="image/*,application/pdf">
                </div>
                <button type="submit" class="btn btn-primary" id="submitBtn">Submit Report</button>
            </form>
        </div>

        <div class="reports-list">
            <h2>Reports</h2>
            <div class="filters">
                <input type="text" id="fltReg" class="form-input" placeholder="Filter by Reg Number">
                <select id="fltType" class="form-input">
                    <option value="">All Types</option>
                    <option value="incident">Incident</option>
                    <option value="maintenance">Maintenance</option>
                    <option value="general">General</option>
                </select>
                <select id="fltStatus" class="form-input">
                    <option value="">All Status</option>
                    <option value="open">Open</option>
                    <option value="in_progress">In Progress</option>
                    <option value="closed">Closed</option>
                </select>
                <input type="date" id="fltFrom" class="form-input" placeholder="From">
                <input type="date" id="fltTo" class="form-input" placeholder="To">
            </div>
            <div class="table-container">
                <table id="reportsTable" class="table" style="width:100%">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Reg Number</th>
                            <th>Status</th>
                            <th>Officer</th>
                            <th>Report Date</th>
                            <th>Created</th>
                            <th>Evidence</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($reports)): ?>
                            <?php foreach ($reports as $r): ?>
                                <tr>
                                    <td><?= htmlspecialchars($r['title'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($r['category'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($r['reg_number'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($r['status'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($r['officer'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($r['report_date'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($r['created_at'] ?? '') ?></td>
                                    <td>
                                        <?php if (!empty($r['file_path'])): ?>
                                            <a href="<?= htmlspecialchars($r['file_path']) ?>" target="_blank" class="btn btn-secondary" style="padding:.35rem .75rem;">View</a>
            <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    </td>
                                    <td class="action-buttons">
                                        <button class="btn btn-primary btn-icon" onclick="window.location.href='edit_report.php?id=<?= (int)($r['id'] ?? 0) ?>'"><i class="fas fa-pen"></i> Edit</button>
                                        <button class="btn btn-danger btn-icon" onclick="deleteReport(<?= (int)($r['id'] ?? 0) ?>)"><i class="fas fa-trash"></i> Delete</button>
                                    </td>
                                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
    <script>
        function logout() { window.location.href = 'logout.php'; }
        // Prevent form resubmission
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }

        // Disable submit button after form submission
        document.getElementById('reportForm').addEventListener('submit', function() {
            document.getElementById('submitBtn').disabled = true;
            document.getElementById('submitBtn').textContent = 'Submitting...';
        });

        function deleteReport(id) {
            if (confirm("Are you sure you want to delete this report?")) {
                // Disable the delete button to prevent multiple clicks
                event.target.disabled = true;
                event.target.textContent = 'Deleting...';
                
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                const body = `report_id=${encodeURIComponent(id)}&_token=${encodeURIComponent(csrfToken)}`;

                fetch('delete_report.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-CSRF-Token': csrfToken
                    },
                    body
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert("Failed to delete: " + (data.message || "Unknown error"));
                        // Re-enable the button if deletion failed
                        event.target.disabled = false;
                        event.target.textContent = 'Delete';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert("An error occurred while deleting the report.");
                    // Re-enable the button if there was an error
                    event.target.disabled = false;
                    event.target.textContent = 'Delete';
                });
            }
        }

        // Initialize DataTable with export buttons and column filters
        document.addEventListener('DOMContentLoaded', function() {
            const table = new DataTable('#reportsTable', {
                dom: 'Bfrtip',
                buttons: [
                    { extend: 'csvHtml5', title: 'Reports' },
                    { extend: 'excelHtml5', title: 'Reports' },
                    { extend: 'pdfHtml5', title: 'Reports', orientation: 'landscape', pageSize: 'A4' },
                    { extend: 'print', title: 'Reports' }
                ],
                order: [[6, 'desc']],
                pageLength: 10
            });

            // Filters
            const fltReg = document.getElementById('fltReg');
            const fltType = document.getElementById('fltType');
            const fltStatus = document.getElementById('fltStatus');
            const fltFrom = document.getElementById('fltFrom');
            const fltTo = document.getElementById('fltTo');

            function applyFilters() {
                table.column(2).search(fltReg.value || '');
                table.column(1).search(fltType.value || '');
                table.column(3).search(fltStatus.value || '');
                // Date range filter on report date (column 5)
                const from = fltFrom.value ? new Date(fltFrom.value) : null;
                const to = fltTo.value ? new Date(fltTo.value) : null;
                table.column(5).search(''); // reset
                table.draw();
                // Custom filtering for date range
            }

            [fltReg, fltType, fltStatus, fltFrom, fltTo].forEach(el => {
                el && el.addEventListener('change', applyFilters);
                el && el.addEventListener('keyup', applyFilters);
            });

            // Custom date range filtering
            DataTable.ext.search.push(function(settings, data) {
                if (settings.nTable !== document.getElementById('reportsTable')) return true;
                const from = fltFrom.value ? new Date(fltFrom.value) : null;
                const to = fltTo.value ? new Date(fltTo.value) : null;
                const dateStr = data[5] || '';
                const rowDate = dateStr ? new Date(dateStr) : null;
                if (!from && !to) return true;
                if (rowDate === null || isNaN(rowDate)) return false;
                if (from && rowDate < from) return false;
                if (to && rowDate > to) return false;
                return true;
            });
        });
    </script>
</body>
</html>
