<?php
// connect to database
include 'db.php';

// Fetch candidates
$stmt = $conn->query("SELECT * FROM candidates");
$candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Candidates</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>

<nav class="navbar navbar-dark bg-dark mb-4">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">Manage Candidates</a>
        <a href="votingsystem.html" class="btn btn-light">Back to Home</a>
    </div>
</nav>

<div class="container">
    <h2 class="mb-3">Candidates List</h2>
    <a href="add_candidate.php" class="btn btn-primary mb-3">Add Candidate</a>

    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Position</th>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Party</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($candidates as $row): ?>
                <tr>
                    <td><?= $row['candidate_id'] ?></td>
                    <td><?= htmlspecialchars($row['position']) ?></td>
                    <td><?= htmlspecialchars($row['first_name']) ?></td>
                    <td><?= htmlspecialchars($row['last_name']) ?></td>
                    <td><?= htmlspecialchars($row['party']) ?></td>
                    <td>
                        <a href="edit_candidate.php?id=<?= $row['candidate_id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                        <a href="delete_candidate.php?id=<?= $row['candidate_id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this candidate?')">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

</body>
</html>