<?php
session_start();
require_once '../db.php'; // Make sure this uses $conn (MySQLi connection)

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Admin') {
    header('Location: ../index.php');
    exit();
}

$message = '';
$error_message = '';

// Get election results
try {
    // Get vote counts by candidate
    $query = "
        SELECT c.candidate_id, c.first_name, c.last_name, c.position, c.platform_pdf,
               COUNT(v.vote_id) as vote_count
        FROM candidate c 
        LEFT JOIN vote v ON c.candidate_id = v.candidate_id 
        GROUP BY c.candidate_id 
        ORDER BY c.position, vote_count DESC, c.first_name, c.last_name
    ";
    $result = mysqli_query($conn, $query);
    $results = mysqli_fetch_all($result, MYSQLI_ASSOC);

    // Get total votes
    $query = "SELECT COUNT(*) as total FROM vote";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    $totalVotes = $row['total'];

    // Get total voters
    $query = "SELECT COUNT(*) as total FROM users WHERE user_type_id = 2";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    $totalVoters = $row['total'];

    // Get voting statistics
    $votingRate = $totalVoters > 0 ? ($totalVotes / $totalVoters) * 100 : 0;

    // Group results by position
    $resultsByPosition = [];
    foreach ($results as $result) {
        $position = $result['position'];
        if (!isset($resultsByPosition[$position])) {
            $resultsByPosition[$position] = [];
        }
        $resultsByPosition[$position][] = $result;
    }

    // Get recent votes
    $query = "
        SELECT v.*, c.first_name, c.last_name, c.position, ui.first_name as voter_first_name, ui.surname as voter_surname
        FROM vote v 
        JOIN candidate c ON v.candidate_id = c.candidate_id
        LEFT JOIN user_info ui ON v.voter_id = ui.user_id
        ORDER BY v.vote_date DESC 
        LIMIT 10
    ";
    $result = mysqli_query($conn, $query);
    $recentVotes = mysqli_fetch_all($result, MYSQLI_ASSOC);

} catch (Exception $e) {
    $error_message = 'Database error: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Election Results - Student Election Voting System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
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
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="candidates.php">Candidates</a></li>
                <li><a href="users.php">Users</a></li>
                <li><a href="results.php" class="active">Results</a></li>
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

    <!-- Main Content -->
    <div class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-chart-bar"></i> Election Results</h1>
            <p>View the current election results and voting statistics.</p>
        </div>

        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Election Statistics -->
        <div class="dashboard-grid">
            <div class="dashboard-card">
                <h3><i class="fas fa-vote-yea"></i> Total Votes</h3>
                <div class="stat-number"><?php echo $totalVotes; ?></div>
                <div class="stat-label">Votes cast</div>
            </div>
            
            <div class="dashboard-card">
                <h3><i class="fas fa-user-friends"></i> Total Voters</h3>
                <div class="stat-number"><?php echo $totalVoters; ?></div>
                <div class="stat-label">Registered voters</div>
            </div>
            
            <div class="dashboard-card">
                <h3><i class="fas fa-percentage"></i> Voting Rate</h3>
                <div class="stat-number"><?php echo number_format($votingRate, 1); ?>%</div>
                <div class="stat-label">Participation rate</div>
            </div>
            
            <div class="dashboard-card">
                <h3><i class="fas fa-users"></i> Total Candidates</h3>
                <div class="stat-number"><?php echo count($results); ?></div>
                <div class="stat-label">Candidates running</div>
            </div>
        </div>

        <!-- results -->
        <?php if (empty($resultsByPosition)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                No election results available. Candidates need to be added and votes need to be cast.
            </div>
        <?php else: ?>
            <?php foreach ($resultsByPosition as $position => $positionResults): ?>
                <div class="table-container mb-3">
                    <div class="table-header">
                        <h2><i class="fas fa-briefcase"></i> <?php echo htmlspecialchars($position); ?></h2>
                        <p>Results for <?php echo htmlspecialchars($position); ?> position</p>
                    </div>
                    
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Candidate</th>
                                <th>Votes</th>
                                <th>Percentage</th>
                                <th>Platform</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $positionTotalVotes = array_sum(array_column($positionResults, 'vote_count'));
                            $rank = 1;
                            foreach ($positionResults as $index => $result): 
                                $percentage = $positionTotalVotes > 0 ? ($result['vote_count'] / $positionTotalVotes) * 100 : 0;
                            ?>
                                <tr class="<?php echo $rank === 1 ? 'winner-row' : ''; ?>" 
                                    style="<?php echo $rank === 1 ? 'background: #d4edda; font-weight: bold;' : ''; ?>">
                                    <td>
                                        <?php if ($rank === 1): ?>
                                            <i class="fas fa-trophy" style="color: #ffc107;"></i> 1st
                                        <?php elseif ($rank === 2): ?>
                                            <i class="fas fa-medal" style="color: #6c757d;"></i> 2nd
                                        <?php elseif ($rank === 3): ?>
                                            <i class="fas fa-award" style="color: #cd7f32;"></i> 3rd
                                        <?php else: ?>
                                            <?php echo $rank; ?>th
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($result['first_name'] . ' ' . $result['last_name']); ?></td>
                                    <td><?php echo $result['vote_count']; ?></td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <span><?php echo number_format($percentage, 1); ?>%</span>
                                            <div style="background: #e9ecef; border-radius: 5px; height: 8px; width: 100px;">
                                                <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); height: 100%; border-radius: 5px; width: <?php echo $percentage; ?>%;"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($result['platform_pdf']): ?>
                                            <a href="../<?php echo htmlspecialchars($result['platform_pdf']); ?>" target="_blank" class="btn btn-sm btn-secondary">
                                                <i class="fa-solid fa-magnifying-glass"></i> View 
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">No platform</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php $rank++; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- votes-->
        <?php if (!empty($recentVotes)): ?>
            <div class="table-container">
                <div class="table-header">
                    <h2><i class="fas fa-history"></i> Recent Votes</h2>
                    <p>Latest votes cast in the election</p>
                </div>
                
                <table class="table">
                    <thead>
                        <tr>
                            <th>Voter</th>
                            <th>Candidate</th>
                            <th>Position</th>
                            <th>Vote Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentVotes as $vote): ?>
                            <tr>
                                <td>
                                    <?php 
                                    $voterName = trim(($vote['voter_first_name'] ?? '') . ' ' . ($vote['voter_surname'] ?? ''));
                                    echo htmlspecialchars($voterName ?: 'Unknown Voter');
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($vote['first_name'] . ' ' . $vote['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($vote['position']); ?></td>
                                <td><?php echo date('M j, Y g:i A', strtotime($vote['vote_date'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- Export Options -->
        <div class="form-container text-center">
            <h3><i class="fas fa-download"></i> Export Results</h3>
            <p>Download election results in different formats</p>
            
            <div class="d-flex gap-2 justify-center">
                <a href="generate_report.php" class="btn btn-danger">
                    <i class="fas fa-file-pdf"></i> Download PDF Report
                </a>
                <button onclick="exportResults('csv')" class="btn btn-success">
                    <i class="fas fa-file-csv"></i> Export CSV
                </button>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        function exportResults(format) {
            if (format === 'csv') {
                let csvContent = "Position,Candidate,Votes,Percentage\n";
                
                <?php foreach ($resultsByPosition as $position => $positionResults): ?>
                    <?php 
                    $positionTotalVotes = array_sum(array_column($positionResults, 'vote_count'));
                    foreach ($positionResults as $result): 
                        $percentage = $positionTotalVotes > 0 ? ($result['vote_count'] / $positionTotalVotes) * 100 : 0;
                    ?>
                        csvContent += "<?php echo addslashes($position); ?>,<?php echo addslashes($result['first_name'] . ' ' . $result['last_name']); ?>,<?php echo $result['vote_count']; ?>,<?php echo number_format($percentage, 1); ?>%\n";
                    <?php endforeach; ?>
                <?php endforeach; ?>
                
                const blob = new Blob([csvContent], { type: 'text/csv' });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'election_results.csv';
                a.click();
                window.URL.revokeObjectURL(url);
            }
        }
    </script>
</body>
</html>
