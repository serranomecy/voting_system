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
$hasVoted = false;

// Check if user has already voted
$sql = "SELECT COUNT(*) as vote_count FROM vote WHERE voter_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$hasVoted = $row['vote_count'] > 0;
$stmt->close();

// Handle vote submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$hasVoted) {
    $candidate_id = $_POST['candidate_id'] ?? null;
    
    if (!$candidate_id) {
        $error_message = 'Please select a candidate to vote for';
    } else {
        // Check if candidate exists
        $sql = "SELECT candidate_id FROM candidate WHERE candidate_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $candidate_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $error_message = 'Invalid candidate selected';
        } else {
            // Record the vote
            $sql = "INSERT INTO vote (voter_id, candidate_id) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $_SESSION['user_id'], $candidate_id);
            if ($stmt->execute()) {
                $message = 'Your vote has been successfully recorded! Thank you for participating in the election.';
                $hasVoted = true;
            } else {
                $error_message = 'Database error: Unable to record your vote.';
            }
        }
        $stmt->close();
    }
}

// Get all candidates (if not yet voted)
$candidates = [];
if (!$hasVoted) {
    $sql = "SELECT * FROM candidate ORDER BY position, first_name, last_name";
    $result = $conn->query($sql);
    if ($result) {
        $candidates = $result->fetch_all(MYSQLI_ASSOC);
    } else {
        $error_message = 'Database error while loading candidates.';
    }
}

// Get user info
$user_info = null;
$sql = "SELECT ui.* FROM user_info ui WHERE ui.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user_info = $result->fetch_assoc();
$stmt->close();
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
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="navbar-content">
            <a href="vote.php" class="navbar-brand">
                <i class="fas fa-vote-yea"></i>
                Student Election System
            </a>
            
            <div class="navbar-user">
                <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['email']); ?></span>
                <a href="../logout.php" class="btn btn-sm btn-logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-vote-yea"></i> Cast Your Vote</h1>
            <p>Select your preferred candidate for each position.</p>
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

        <?php if ($hasVoted): ?>
            <!-- Already Voted -->
            <div class="form-container text-center">
                <div style="font-size: 4rem; color: #28a745; margin-bottom: 20px;">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h2>Thank You for Voting!</h2>
                <p>You have already cast your vote in this election.</p>
                <p>Your participation is important for our democratic process.</p>
                
                <div style="margin-top: 30px;">
                    <a href="../logout.php" class="btn btn-primary">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
            
        <?php else: ?>
            <!-- Voting Interface -->
            <?php if (empty($candidates)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    No candidates are available for voting at this time. Please contact the administrator.
                </div>
            <?php else: ?>
                <form method="POST" id="votingForm">
                    <input type="hidden" name="candidate_id" id="selectedCandidate" required>
                    
                    <div class="voting-container">
                        <?php 
                        $current_position = '';
                        foreach ($candidates as $candidate): 
                            if ($current_position !== $candidate['position']):
                                if ($current_position !== '') {
                                    echo '</div>'; // close previous
                                }
                                $current_position = $candidate['position'];
                        ?>
                            <div class="position-group" style="margin-bottom: 30px;">
                                <h2 style="color: black; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #e9ecef;">
                                    <i class="fas fa-briefcase"></i> <?php echo htmlspecialchars($candidate['position']); ?>
                                </h2>
                        <?php endif; ?>
                        
                        <div class="candidate-card" data-candidate-id="<?php echo $candidate['candidate_id']; ?>">
                            <div class="d-flex justify-between align-center">
                                <div>
                                    <h3><?php echo htmlspecialchars($candidate['first_name'] . ' ' . $candidate['last_name']); ?></h3>
                                    <p class="position"><?php echo htmlspecialchars($candidate['position']); ?></p>
                                    
                                    <?php if (!empty($candidate['platform_pdf'])): ?>
                                        <a href="../<?php echo htmlspecialchars($candidate['platform_pdf']); ?>" 
                                           target="_blank" class="btn btn-sm btn-secondary">
                                            <i class="fa-solid fa-magnifying-glass"></i> View Platform
                                        </a>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="vote-indicator" style="font-size: 2rem; color: #e9ecef;">
                                    <i class="fas fa-circle"></i>
                                </div>
                            </div>
                        </div>
                        
                        <?php endforeach; ?>
                        <?php if ($current_position !== '') echo '</div>'; ?>
                    </div>
                    
                    <div class="text-center" style="margin-top: 30px;">
                        <button type="submit" class="btn btn-primary btn-lg" id="submitVote" disabled>
                            <i class="fas fa-vote-yea"></i> Submit Vote
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script src="assets/js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const candidateCards = document.querySelectorAll('.candidate-card');
            const submitButton = document.getElementById('submitVote');
            const selectedCandidateInput = document.getElementById('selectedCandidate');
            
            candidateCards.forEach(card => {
                card.addEventListener('click', function() {
                    const position = this.querySelector('.position').textContent;
                    const allCardsInPosition = Array.from(candidateCards).filter(c => 
                        c.querySelector('.position').textContent === position
                    );
                    
                    allCardsInPosition.forEach(c => {
                        c.classList.remove('selected');
                        c.querySelector('.vote-indicator i').className = 'fas fa-circle';
                        c.querySelector('.vote-indicator').style.color = '#e9ecef';
                    });
                    
                    this.classList.add('selected');
                    this.querySelector('.vote-indicator i').className = 'fas fa-check-circle';
                    this.querySelector('.vote-indicator').style.color = '#28a745';
                    
                    selectedCandidateInput.value = this.dataset.candidateId;
                    submitButton.disabled = false;
                });
            });
            
            document.getElementById('votingForm').addEventListener('submit', function(e) {
                if (!confirm('Are you sure you want to submit your vote? This action cannot be undone.')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>
