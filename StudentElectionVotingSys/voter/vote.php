<?php
session_start();
require_once '../db.php';

// Check if user is logged in and is voter
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Voter') {
    header('Location: ../index.php');
    exit();
}

$message = '';
$error_message = '';
$voterPositions = [];

// Get positions the voter has already voted for
$stmt = mysqli_prepare($conn, "
    SELECT DISTINCT c.position 
    FROM vote v 
    JOIN candidate c ON v.candidate_id = c.candidate_id 
    WHERE v.voter_id = ?
");
mysqli_stmt_bind_param($stmt, 'i', $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($result)) {
    $voterPositions[] = $row['position'];
}
mysqli_stmt_close($stmt);

// Handle vote submission - allow one vote per position
if ($_POST && isset($_POST['candidate_votes']) && is_array($_POST['candidate_votes'])) {
    $candidateIds = $_POST['candidate_votes'];
    $successCount = 0;
    $errors = [];
    
    foreach ($candidateIds as $candidate_id) {
        if (!is_numeric($candidate_id)) continue;
        
        // Get candidate's position
        $stmt = mysqli_prepare($conn, "SELECT position FROM candidate WHERE candidate_id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $candidate_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $candidate = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        if (!$candidate) continue;
        
        // Check if voter already voted for this position
        if (in_array($candidate['position'], $voterPositions)) {
            $errors[] = "Already voted for " . $candidate['position'];
            continue;
        }
        
        // Record the vote
        $stmt = mysqli_prepare($conn, "INSERT INTO vote (voter_id, candidate_id) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt, 'ii', $_SESSION['user_id'], $candidate_id);
        if (mysqli_stmt_execute($stmt)) {
            $successCount++;
            $voterPositions[] = $candidate['position'];
        }
        mysqli_stmt_close($stmt);
    }
    
    if ($successCount > 0) {
        $message = "Thank you for voting! Successfully recorded vote(s) for $successCount position(s).";
    } else if (!empty($errors)) {
        $error_message = implode(', ', $errors);
    } else {
        $error_message = 'Unable to record votes.';
    }
}

// Get all candidates grouped by position
$candidatesByPosition = [];
$query = "SELECT * FROM candidate ORDER BY position, first_name, last_name";
$result = mysqli_query($conn, $query);
if ($result) {
    while ($candidate = mysqli_fetch_assoc($result)) {
        $position = $candidate['position'];
        if (!isset($candidatesByPosition[$position])) {
            $candidatesByPosition[$position] = [];
        }
        $candidatesByPosition[$position][] = $candidate;
    }
}

$allPositionsVoted = count($voterPositions) >= count($candidatesByPosition);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vote - Student Election Voting System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="dashboard-container">
    <nav class="navbar">
        <div class="navbar-content">
            <a href="vote.php" class="navbar-brand">
                <i class="fas fa-vote-yea"></i> Student Election System
            </a>
            
            <div class="navbar-user">
                <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['email']); ?></span>
                <a href="../logout.php" class="btn btn-sm btn-logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-vote-yea"></i> Cast Your Vote</h1>
            <p>Select one candidate per position.</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($allPositionsVoted): ?>
            <div class="form-container text-center">
                <div style="font-size: 4rem; color: #28a745; margin-bottom: 20px;">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h2>Thank You for Voting!</h2>
                <p>You have voted for all positions.</p>
                
                <div style="margin-top: 30px;">
                    <a href="../viewballot.php" class="btn btn-primary">
                        <i class="fas fa-sign-out-alt"></i> View Ballot
                    </a>
                </div>
            </div>
        <?php else: ?>
            <?php if (empty($candidatesByPosition)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    No candidates are available for voting at this time.
                </div>
            <?php else: ?>
                <form method="POST" id="votingForm">
                    <div class="voting-container">
                        <?php foreach ($candidatesByPosition as $position => $candidates): 
                            $alreadyVoted = in_array($position, $voterPositions);
                        ?>
                            <div class="position-group" style="margin-bottom: 30px; <?php echo $alreadyVoted ? 'opacity: 0.6;' : ''; ?>">
                                <h2 style="color:black; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #e9ecef;">
                                    <i class="fas fa-briefcase"></i> <?php echo htmlspecialchars($position); ?>
                                    <?php if ($alreadyVoted): ?>
                                        <span style="color: #28a745; font-size: 0.8em; margin-left: 10px;">✓ Voted</span>
                                    <?php endif; ?>
                                </h2>
                                
                                <?php foreach ($candidates as $candidate): ?>
                                    <div class="candidate-card <?php echo $alreadyVoted ? 'disabled' : ''; ?>" 
                                         data-candidate-id="<?php echo $candidate['candidate_id']; ?>" 
                                         data-position="<?php echo htmlspecialchars($position); ?>"
                                         <?php if ($alreadyVoted): ?>style="pointer-events: none;"<?php endif; ?>>
                                        <div class="d-flex justify-between align-center">
                                            <div>
                                                <h3><?php echo htmlspecialchars($candidate['first_name'] . ' ' . $candidate['last_name']); ?></h3>
                                                
                                                <?php if (!empty($candidate['platform_pdf'])): ?>
                                                    <a href="../<?php echo htmlspecialchars($candidate['platform_pdf']); ?>" 
                                                       target="_blank" class="btn btn-sm btn-secondary">
                                                        <i class="fas fa-file-pdf"></i> View Platform
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="vote-indicator" style="font-size: 2rem; color: #e9ecef;">
                                                <i class="fas fa-circle"></i>
                                            </div>
                                        </div>
                                        <?php if (!$alreadyVoted): ?>
                                            <input type="checkbox" 
                                                   name="candidate_votes[]" 
                                                   value="<?php echo $candidate['candidate_id']; ?>" 
                                                   style="display: none;"
                                                   class="candidate-checkbox">
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="text-center" style="margin-top: 30px;">
                        <button type="submit" class="btn btn-primary btn-lg" id="submitVote">
                            <i class="fas fa-vote-yea"></i> Submit Vote
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const candidateCards = document.querySelectorAll('.candidate-card:not(.disabled)');
            const submitButton = document.getElementById('submitVote');
            
            candidateCards.forEach(card => {
                card.addEventListener('click', function() {
                    const position = this.dataset.position;
                    const checkboxes = this.querySelectorAll('.candidate-checkbox');
                    
                    // Unselect other candidates in the same position
                    const samePosition = document.querySelectorAll(`[data-position="${position}"]`);
                    samePosition.forEach(c => {
                        if (!c.classList.contains('disabled')) {
                            c.classList.remove('selected');
                            c.querySelector('.vote-indicator i').className = 'fas fa-circle';
                            c.querySelector('.vote-indicator').style.color = '#e9ecef';
                            const cb = c.querySelector('.candidate-checkbox');
                            if (cb) cb.checked = false;
                        }
                    });
                    
                    // Select this candidate
                    this.classList.add('selected');
                    this.querySelector('.vote-indicator i').className = 'fas fa-check-circle';
                    this.querySelector('.vote-indicator').style.color = '#28a745';
                    const checkbox = this.querySelector('.candidate-checkbox');
                    if (checkbox) checkbox.checked = true;
                });
            });

            document.getElementById('votingForm').addEventListener('submit', function(e) {
                const selected = document.querySelectorAll('.candidate-checkbox:checked');
                if (selected.length === 0) {
                    e.preventDefault();
                    alert('Please select at least one candidate');
                    return false;
                }
                
                if (!confirm('Are you sure you want to submit your votes? This action cannot be undone.')) {
                    e.preventDefault();
                    return false;
                }
            });
        });
    </script>
</body>
</html>
