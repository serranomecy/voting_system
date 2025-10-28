<?php
session_start();
require_once '../db.php'; // make sure db.php uses mysqli

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Admin') {
    header('Location: ../index.php');
    exit();
}

$action = $_GET['action'] ?? 'list';
$candidate_id = $_GET['id'] ?? null;
$message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $position = trim($_POST['position'] ?? '');

    if (empty($first_name) || empty($last_name) || empty($position)) {
        $error_message = 'Please fill in all required fields.';
    } else {
        // Handle file upload
        $platform_pdf = null;
        if (isset($_FILES['platform_pdf']) && $_FILES['platform_pdf']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/platforms/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $file_extension = pathinfo($_FILES['platform_pdf']['name'], PATHINFO_EXTENSION);
            $filename = strtolower($first_name . '_' . $last_name . '_' . time() . '.' . $file_extension);
            $upload_path = $upload_dir . $filename;

            if (move_uploaded_file($_FILES['platform_pdf']['tmp_name'], $upload_path)) {
                $platform_pdf = 'uploads/platforms/' . $filename;
            }
        }

        if ($action === 'add') {
            $stmt = mysqli_prepare($conn, "INSERT INTO candidate (first_name, last_name, position, platform_pdf) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "ssss", $first_name, $last_name, $position, $platform_pdf);
            mysqli_stmt_execute($stmt);
            $message = "Candidate added successfully!";
            mysqli_stmt_close($stmt);
            $action = 'list';
        } elseif ($action === 'edit' && $candidate_id) {
            $platform_pdf = $_POST['current_platform_pdf'] ?? $platform_pdf;
            $stmt = mysqli_prepare($conn, "UPDATE candidate SET first_name = ?, last_name = ?, position = ?, platform_pdf = ? WHERE candidate_id = ?");
            mysqli_stmt_bind_param($stmt, "ssssi", $first_name, $last_name, $position, $platform_pdf, $candidate_id);
            mysqli_stmt_execute($stmt);
            $message = "Candidate updated successfully!";
            mysqli_stmt_close($stmt);
            $action = 'list';
        }
    }
}

// Handle delete action
if ($action === 'delete' && $candidate_id) {
    $stmt = mysqli_prepare($conn, "DELETE FROM candidate WHERE candidate_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $candidate_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    $message = "Candidate deleted successfully!";
    $action = 'list';
}

// Get candidate data for editing
$candidate = null;
if ($action === 'edit' && $candidate_id) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM candidate WHERE candidate_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $candidate_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $candidate = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$candidate) {
        $error_message = "Candidate not found.";
        $action = 'list';
    }
}

// Get all candidates for listing
$candidates = [];
if ($action === 'list') {
    $sql = "
        SELECT c.*, COUNT(v.vote_id) AS vote_count 
        FROM candidate c 
        LEFT JOIN vote v ON c.candidate_id = v.candidate_id 
        GROUP BY c.candidate_id 
        ORDER BY c.candidate_id DESC
    ";
    $result = mysqli_query($conn, $sql);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $candidates[] = $row;
        }
        mysqli_free_result($result);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidate Management - Student Election Voting System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="dashboard-container">
    <!-- Navigation -->
    <nav class="navbar">
        <div class="navbar-content">
            <a href="dashboard.php" class="navbar-brand">
                <i class="fas fa-vote-yea"></i> Student Election Voting System
            </a>
            <ul class="navbar-nav">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="candidates.php" class="active">Candidates</a></li>
                <li><a href="users.php">Users</a></li>
                <li><a href="results.php">Results</a></li>
            </ul>
            <div class="navbar-user">
                <span style="margin-right: 15px;"><i class="fas fa-user-shield"></i> <?php echo htmlspecialchars($_SESSION['email']); ?></span>
                <a href="../logout.php" class="btn btn-sm btn-logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>
        <div style="padding: 8px 20px; font-weight: 500; color:gray; text-align: right;">
        <i class="fas fa-calendar-alt"></i>
    <span id="currentDateTime"><?php echo date('M d, Y | g:i A'); ?></span>
    </div>

    <div class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-users"></i> Candidate List</h1>
            <p>Manage election candidates and their information.</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <?php if ($action === 'list'): ?>
            <div class="d-flex justify-between align-center mb-2">
                <h2>All Candidates</h2>
                <a href="candidates.php?action=add" class="btn btn-primary"><i class="fas fa-plus"></i> Add New Candidate</a>
            </div>

            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Position</th>
                            <th>Votes</th>
                            <th>Platform</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($candidates)): ?>
                            <tr><td colspan="5" class="text-center">No candidates found. <a href="candidates.php?action=add">Add one</a></td></tr>
                        <?php else: ?>
                            <?php foreach ($candidates as $c): ?>
                                <tr>
                                    <td><?= htmlspecialchars($c['first_name'] . ' ' . $c['last_name']); ?></td>
                                    <td><?= htmlspecialchars($c['position']); ?></td>
                                    <td><?= (int)$c['vote_count']; ?></td>
                                    <td>
                                        <?php if ($c['platform_pdf']): ?>
                                            <a href="../<?= htmlspecialchars($c['platform_pdf']); ?>" target="_blank" class="btn btn-sm btn-secondary">
                                                <i class="fa-solid fa-magnifying-glass"></i> View
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">No file</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                         <a href="candidates.php?action=edit&id=<?= $c['candidate_id']; ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                                        <a href="candidates.php?action=delete&id=<?= $c['candidate_id']; ?>" onclick="return confirm('Are you sure you want to Delete this candidate?')" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <?php else: ?>
            <!-- Add/Edit Candidate -->
            <div class="form-container">
                <h2><i class="fas fa-<?php echo $action === 'add' ? 'plus' : 'edit'; ?>"></i> <?= $action === 'add' ? 'Add Candidate' : 'Edit Candidate'; ?></h2>
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="form-group">
                            <label>First Name *</label>
                            <input type="text" name="first_name" required value="<?= htmlspecialchars($candidate['first_name'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Last Name *</label>
                            <input type="text" name="last_name" required value="<?= htmlspecialchars($candidate['last_name'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Position *</label>
                        <input type="text" name="position" required value="<?= htmlspecialchars($candidate['position'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label>Platform</label>
                        <input type="file" name="platform_pdf" accept=".pdf">
                        <?php if ($candidate && $candidate['platform_pdf']): ?>
                            <input type="hidden" name="current_platform_pdf" value="<?= htmlspecialchars($candidate['platform_pdf']); ?>">
                            <p><small>Current: <a href="../<?= htmlspecialchars($candidate['platform_pdf']); ?>" target="_blank">Platform</a></small></p>
                        <?php endif; ?>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?= $action === 'add' ? 'Add Candidate' : 'Update Candidate'; ?></button>
                        <a href="candidates.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancel</a>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
    <script src="../assets/js/main.js"></script>
</body>
</html>
