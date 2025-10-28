<?php
session_start();
require_once '../db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Admin') {
    header('Location: ../index.php');
    exit();
}

$error_message = '';

// Dashboard statistics
$totalCandidates = $totalVoters = $totalVotes = $remainingVoters = 0;
$recentCandidates = [];

try {
    // Total candidates
    $result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM candidate");
    $totalCandidates = mysqli_fetch_assoc($result)['total'] ?? 0;

    // Total registered voters
    $result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE user_type_id = 2");
    $totalVoters = mysqli_fetch_assoc($result)['total'] ?? 0;

    // Total votes cast
    $result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM vote");
    $totalVotes = mysqli_fetch_assoc($result)['total'] ?? 0;

    // Voters who haven't voted yet
    $sqlRemaining = "
        SELECT COUNT(*) AS total
        FROM users u
        WHERE u.user_type_id = 2
        AND u.user_id NOT IN (SELECT voter_id FROM vote)
    ";
    $result = mysqli_query($conn, $sqlRemaining);
    $remainingVoters = mysqli_fetch_assoc($result)['total'] ?? 0;

    // Recent candidates
    $sqlRecent = "
        SELECT c.*, COUNT(v.vote_id) AS vote_count
        FROM candidate c
        LEFT JOIN vote v ON c.candidate_id = v.candidate_id
        GROUP BY c.candidate_id
        ORDER BY c.candidate_id DESC
        LIMIT 5
    ";
    $result = mysqli_query($conn, $sqlRecent);
    while ($row = mysqli_fetch_assoc($result)) {
        $recentCandidates[] = $row;
    }

} catch (Exception $e) {
    $error_message = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Student Election Voting System</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="dashboard-container">
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="navbar-content">
            <a href="dashboard.php" class="navbar-brand">
                <i class="fas fa-vote-yea"></i> Student Election Voting System
            </a>
            <ul class="navbar-nav">
                <li><a href="dashboard.php" class="active">Dashboard</a></li>
                <li><a href="candidates.php">Candidates</a></li>
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

    <!-- date and time-->
   <div style="padding: 8px 20px; font-weight: 500; color:gray; text-align: right;">
    <i class="fas fa-calendar-alt"></i>
    <span id="currentDateTime"><?php echo date('M d, Y | g:i A'); ?></span>
</div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-tachometer-alt"></i> Admin Dashboard</h1>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="dashboard-grid">
            <div class="dashboard-card">
                <h3><i class="fas fa-users"></i> Total Candidates</h3>
                <div class="stat-number"><?php echo $totalCandidates; ?></div>
                <div class="stat-label">Registered candidates</div>
            </div>

            <div class="dashboard-card">
                <h3><i class="fas fa-user-friends"></i> Total Voters</h3>
                <div class="stat-number"><?php echo $totalVoters; ?></div>
                <div class="stat-label">Registered voters</div>
            </div>

            <div class="dashboard-card">
                <h3><i class="fas fa-vote-yea"></i> Votes Cast</h3>
                <div class="stat-number"><?php echo $totalVotes; ?></div>
                <div class="stat-label">Total votes submitted</div>
            </div>

            <div class="dashboard-card">
                <h3><i class="fas fa-clock"></i> Remaining Voters</h3>
                <div class="stat-number"><?php echo $remainingVoters; ?></div>
                <div class="stat-label">Voters yet to vote</div>
            </div>
        </div>

        <!-- Quick Actions -->

<!-- Quick Actions -->
<div class="dashboard-grid">
    <div class="dashboard-card">
        <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
        <div style="display: flex; flex-direction: column; align-items: center; gap: 15px;">
            <a href="candidates.php?action=add" class="btn btn-primary" 
               style="width: 200px; text-align: center;">
                <i class="fas fa-user-plus"></i> Add Candidate
            </a>
            <a href="users.php?action=add" class="btn btn-success" 
               style="width: 200px; text-align: center;">
                <i class="fas fa-user-plus"></i> Add User
            </a>
            <a href="results.php" class="btn btn-warning" 
               style="width: 200px; text-align: center;">
                <i class="fas fa-chart-bar"></i> View Results
            </a>
        </div>
    </div>
</div> 

<!-- Recent Candidates (new section BELOW) -->
<div class="table-container" style="margin-top: 20px;">
    <div class="table-header">
        <h2><i class="fas fa-users"></i> Recent Candidates</h2>
        <p>Latest candidates added to the system</p>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Position</th>
                <th>Votes</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($recentCandidates)): ?>
                <tr>
                    <td colspan="4" class="text-center">
                        No candidates found. 
                        <a href="candidates.php?action=add">Add the first candidate</a>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($recentCandidates as $candidate): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($candidate['first_name'] . ' ' . $candidate['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($candidate['position']); ?></td>
                        <td><?php echo (int)$candidate['vote_count']; ?></td>
                        <td>
                            <a href="candidates.php?action=edit&id=<?php echo $candidate['candidate_id']; ?>" class="btn btn-sm btn-warning">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="candidates.php?action=delete&id=<?php echo $candidate['candidate_id']; ?>" 
                               onclick="return confirm('Are you sure you want to delete this candidate?')" 
                               class="btn btn-sm btn-danger">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

    <script src="../assets/js/main.js"></script>
    
</body>
</html>
