<?php
session_start();
require_once 'db.php'; 

// Ensure only logged-in voters can access this page
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Voter') {
    header('Location: index.php');
    exit();
}

$voter_id = $_SESSION['user_id'];
$error_message = '';
$votes = [];

try {
    // Fetch all votes cast by the logged-in voter
    $query = "
        SELECT c.position, c.first_name, c.last_name
        FROM vote v
        INNER JOIN candidate c ON v.candidate_id = c.candidate_id
        WHERE v.voter_id = ?
        ORDER BY c.position
    ";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'i', $voter_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $votes = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
} catch (Exception $e) {
    $error_message = 'Error retrieving your ballot: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Ballot</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="dashboard-container">

<!-- Navbar -->
<nav class="navbar">
    <div class="navbar-content" style="display: flex; justify-content: space-between; align-items: center;">
        <div class="navbar-left" style="display: flex; align-items: center; gap: 8px;">
            <a href="vote.php" class="navbar-brand" style="color: white; text-decoration: none; font-weight: bold;">
                <i class="fas fa-vote-yea"></i> Student Voting Election System
            </a>
        </div>

        <div class="navbar-user" style="display: flex; align-items: center; gap: 10px;">
            <span style="color: white;">
                <i class="fas fa-user"></i> 
                <?php echo htmlspecialchars($_SESSION['email']); ?>
            </span>
            <a href="logout.php" class="btn btn-sm btn-logout">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
</nav>

<!-- Main Content -->
<div class="main-content" style="padding: 30px;">
    <div class="page-header">
        <h1><i class="fas fa-list-check"></i> Your Votes</h1>
        <p>Here are the candidates you voted for in each position.</p>
    </div>

    <?php if ($error_message): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <?php if (empty($votes)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> You haven’t voted yet.
        </div>
    <?php else: ?>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Position</th>
                        <th>Candidate</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($votes as $vote): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($vote['position']); ?></td>
                            <td><?php echo htmlspecialchars($vote['first_name'] . ' ' . $vote['last_name']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
