<?php
include 'db.php';

// Fetch joined data from both tables
$stmt = $conn->query("
    SELECT 
        users.user_id, 
        users.email, 
        users.password,
        users.user_type_id,
        user_info.first_name, 
        user_info.middle_name, 
        user_info.surname
    FROM users
    JOIN user_info ON users.user_id = user_info.user_id
");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage User Information</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
        }
        nav {
            background-color: #343a40;
            color: white;
            padding: 15px;
            text-align: center;
            font-size: 20px;
            font-weight: bold;
        }
        .container {
            padding: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }
        th {
            background-color: #495057;
            color: white;
        }
        .btn {
            padding: 5px 10px;
            border-radius: 4px;
            text-decoration: none;
            color: white;
        }
        .btn-edit {
            background-color: #28a745;
        }
        .btn-delete {
            background-color: #dc3545;
        }
        .btn-add {
            background-color: #007bff;
            padding: 8px 12px;
            margin-bottom: 15px;
            display: inline-block;
        }
    </style>
</head>
<body>
    <nav>Manage User Information</nav>

    <div class="container">
        <a href="add_user.php" class="btn btn-add">+ Add User</a>
        <table>
            <tr>
                <th>User ID</th>
                <th>Email</th>
                <th>Password</th>
                <th>First Name</th>
                <th>Middle Name</th>
                <th>Surname</th>
                <th>User Type</th>
                <th>Action</th>
            </tr>

            <?php foreach ($users as $row): ?>
            <tr>
                <td><?= $row['user_id'] ?></td>
                <td><?= $row['email'] ?></td>
                <td><?= $row['password'] ?></td>
                <td><?= $row['first_name'] ?></td>
                <td><?= $row['middle_name'] ?></td>
                <td><?= $row['surname'] ?></td>
                <td><?= $row['user_type_id'] ?></td>
                <td>
                    <a href="edit_user.php?id=<?= $row['user_id'] ?>" class="btn btn-edit">Edit</a>
                    <a href="delete_user.php?id=<?= $row['user_id'] ?>" class="btn btn-delete" onclick="return confirm('Delete this user?')">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>

        </table>
    </div>
</body>
</html>