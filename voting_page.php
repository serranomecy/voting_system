<?php
include 'db.php'; // connect to database

// When the user submits the vote form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $voter_id = $_POST['voter_id'];
    $candidate_id = $_POST['candidate_id'];
    $vote_date = date('Y-m-d H:i:s');

    // Insert into the vote table
    $sql = "INSERT INTO vote (voter_id, candidate_id, vote_date) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$voter_id, $candidate_id, $vote_date]);

    echo "<script>alert('Vote submitted successfully!');</script>";
}

// Get all candidates
$candidates = $conn->query("SELECT * FROM candidates")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Voting Page</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="votingsystem.html">Student Election Voting System</a>
    <span class="navbar-text text-white">Voting Page</span>
  </div>
</nav>

<div class="container mt-5">
  <h3 class="text-center mb-4">🗳 Vote for Your Candidate</h3>

  <form method="POST" class="card p-4 shadow-sm bg-white">
    <div class="mb-3">
      <label for="voter_id" class="form-label">Your Voter ID</label>
      <input type="number" name="voter_id" id="voter_id" class="form-control" placeholder="Enter your voter ID" required>
    </div>

    <div class="mb-3">
      <label for="candidate_id" class="form-label">Select Candidate</label>
      <select name="candidate_id" id="candidate_id" class="form-select" required>
        <option value="">-- Choose a Candidate --</option>
        <?php foreach ($candidates as $c): ?>
          <option value="<?= $c['candidate_id'] ?>">
            <?= htmlspecialchars($c['first_name'] . " " . $c['last_name'] . " - " . $c['position']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <button type="submit" class="btn btn-primary w-100">Submit Vote</button>
  </form>
</div>

</body>
</html>