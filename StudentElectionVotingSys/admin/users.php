<?php
session_start();
require_once '../db.php'; // Make sure this uses mysqli connection ($conn)

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Admin') {
    header('Location: ../index.php');
    exit();
}

$action = $_GET['action'] ?? 'list';
$user_id = $_GET['id'] ?? null;
$message = '';
$error_message = '';

// Handle form submissions
if ($_POST) {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $user_type_id = $_POST['user_type_id'] ?? '';
    $first_name = trim($_POST['first_name'] ?? '');
    $middle_name = trim($_POST['middle_name'] ?? '');
    $surname = trim($_POST['surname'] ?? '');
    
    if (empty($email) || empty($password) || empty($user_type_id) || empty($first_name) || empty($surname)) {
        $error_message = 'Please fill in all required fields';
    } else {
        if ($action === 'add') {
            // Check if email exists
            $stmt = mysqli_prepare($conn, "SELECT user_id FROM users WHERE email = ?");
            mysqli_stmt_bind_param($stmt, 's', $email);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            if (mysqli_stmt_num_rows($stmt) > 0) {
                $error_message = 'Email already exists';
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Insert into users
                $stmt = mysqli_prepare($conn, "INSERT INTO users (email, password, user_type_id) VALUES (?, ?, ?)");
                mysqli_stmt_bind_param($stmt, 'ssi', $email, $hashed_password, $user_type_id);
                mysqli_stmt_execute($stmt);
                $new_user_id = mysqli_insert_id($conn);

                // Insert into user_info
                $stmt = mysqli_prepare($conn, "INSERT INTO user_info (user_id, first_name, middle_name, surname) VALUES (?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt, 'isss', $new_user_id, $first_name, $middle_name, $surname);
                mysqli_stmt_execute($stmt);

                $message = 'User added successfully!';
                $action = 'list';
            }
            mysqli_stmt_close($stmt);

        } elseif ($action === 'edit' && $user_id) {
            // Check if email already exists (excluding current)
            $stmt = mysqli_prepare($conn, "SELECT user_id FROM users WHERE email = ? AND user_id != ?");
            mysqli_stmt_bind_param($stmt, 'si', $email, $user_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            if (mysqli_stmt_num_rows($stmt) > 0) {
                $error_message = 'Email already exists';
            } else {
                if (!empty($password)) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = mysqli_prepare($conn, "UPDATE users SET email = ?, password = ?, user_type_id = ? WHERE user_id = ?");
                    mysqli_stmt_bind_param($stmt, 'ssii', $email, $hashed_password, $user_type_id, $user_id);
                } else {
                    $stmt = mysqli_prepare($conn, "UPDATE users SET email = ?, user_type_id = ? WHERE user_id = ?");
                    mysqli_stmt_bind_param($stmt, 'sii', $email, $user_type_id, $user_id);
                }
                mysqli_stmt_execute($stmt);

                // Update user_info
                $stmt = mysqli_prepare($conn, "UPDATE user_info SET first_name = ?, middle_name = ?, surname = ? WHERE user_id = ?");
                mysqli_stmt_bind_param($stmt, 'sssi', $first_name, $middle_name, $surname, $user_id);
                mysqli_stmt_execute($stmt);

                $message = 'User updated successfully!';
                $action = 'list';
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Handle delete
if ($action === 'delete' && $user_id) {
    if ($user_id == $_SESSION['user_id']) {
        $error_message = 'You cannot delete your own account';
    } else {
        // Delete from vote table first (child table)
        $stmt = mysqli_prepare($conn, "DELETE FROM vote WHERE voter_id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        // Delete from user_info (child table)
        $stmt = mysqli_prepare($conn, "DELETE FROM user_info WHERE user_id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        // Then delete from users (parent table)
        $stmt = mysqli_prepare($conn, "DELETE FROM users WHERE user_id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $user_id);
        mysqli_stmt_execute($stmt);
        $message = 'User deleted successfully!';
        $action = 'list';
        mysqli_stmt_close($stmt);
    }
}

// Get user info for editing
$user = null;
if ($action === 'edit' && $user_id) {
    $stmt = mysqli_prepare($conn, "
        SELECT u.*, ui.first_name, ui.middle_name, ui.surname 
        FROM users u 
        LEFT JOIN user_info ui ON u.user_id = ui.user_id 
        WHERE u.user_id = ?
    ");
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    if (!$user) {
        $error_message = 'User not found';
        $action = 'list';
    }
    mysqli_stmt_close($stmt);
}

// Get all users for list
$users = [];
if ($action === 'list') {
    $query = "
        SELECT u.*, ui.first_name, ui.middle_name, ui.surname, ut.type_name,
               CASE WHEN v.vote_id IS NOT NULL THEN 1 ELSE 0 END as has_voted
        FROM users u 
        LEFT JOIN user_info ui ON u.user_id = ui.user_id 
        LEFT JOIN user_type ut ON u.user_type_id = ut.user_type_id
        LEFT JOIN vote v ON u.user_id = v.voter_id
        ORDER BY u.user_id DESC
    ";
    $result = mysqli_query($conn, $query);
    if ($result) {
        $users = mysqli_fetch_all($result, MYSQLI_ASSOC);
    }
}

// Get user types
$user_types = [];
$result = mysqli_query($conn, "SELECT * FROM user_type ORDER BY user_type_id");
if ($result) {
    $user_types = mysqli_fetch_all($result, MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User List - Student Election Voting System</title>
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
                <li><a href="users.php" class="active">Users</a></li>
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

    <!-- Main Content -->
    <div class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-user-plus"></i> User List</h1>
            <p>Manage system users and their accounts.</p>
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

        <?php if ($action === 'list'): ?>
            <!-- User List -->
            <div class="d-flex justify-between align-center mb-2">
                <h2>All Users</h2>
                <a href="users.php?action=add" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add New User
                </a>
            </div>

            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Type</th>
                            <th>Vote Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="5" class="text-center">No users found. <a href="users.php?action=add">Add the first user</a></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td>
                                        <?php 
                                        $name = trim(($u['first_name'] ?? '') . ' ' . ($u['middle_name'] ?? '') . ' ' . ($u['surname'] ?? ''));
                                        echo htmlspecialchars($name ?: 'No name provided');
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                                    <td>
                                        <span class="btn btn-sm <?php echo $u['type_name'] === 'Admin' ? 'btn-warning' : 'btn-info'; ?>">
                                            <i class="fas fa-<?php echo $u['type_name'] === 'Admin' ? 'user-shield' : 'user'; ?>"></i>
                                            <?php echo htmlspecialchars($u['type_name']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($u['type_name'] === 'Voter'): ?>
                                            <?php if ($u['has_voted']): ?>
                                                <span class="btn btn-sm btn-success">
                                                    <i class="fas fa-check"></i> Voted
                                                </span>
                                            <?php else: ?>
                                                <span class="btn btn-sm btn-vote_status">
                                                    <i class="fas fa-clock"></i> Not Voted
                                                </span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="users.php?action=edit&id=<?php echo $u['user_id']; ?>" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($u['user_id'] != $_SESSION['user_id']): ?>
                                            <a href="users.php?action=delete&id=<?php echo $u['user_id']; ?>" 
                                               onclick="return confirm('Are you sure you want to delete this user?')" 
                                               class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($action === 'add' || $action === 'edit'): ?>
            <!-- Add/Edit User Form -->
            <div class="form-container">
                <h2>
                    <i class="fas fa-<?php echo $action === 'add' ? 'plus' : 'edit'; ?>"></i>
                    <?php echo $action === 'add' ? 'Add New User' : 'Edit User'; ?>
                </h2>
                
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">
                                <i class="fas fa-envelope"></i>
                                Email Address *
                            </label>
                            <input type="email" id="email" name="email" required 
                                   value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="user_type_id">
                                <i class="fas fa-user-tag"></i>
                                User Type *
                            </label>
                            <select id="user_type_id" name="user_type_id" required>
                                <option value="">Select User Type</option>
                                <?php foreach ($user_types as $type): ?>
                                    <option value="<?php echo $type['user_type_id']; ?>" 
                                            <?php echo ($user['user_type_id'] ?? '') == $type['user_type_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($type['type_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">
                            <i class="fas fa-lock"></i>
                            Password <?php echo $action === 'edit' ? '(leave blank to keep current)' : '*'; ?>
                        </label>
                        <input type="password" id="password" name="password" 
                               <?php echo $action === 'add' ? 'required' : ''; ?>>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name">
                                <i class="fas fa-user"></i>
                                First Name *
                            </label>
                            <input type="text" id="first_name" name="first_name" required 
                                   value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="middle_name">
                                <i class="fas fa-user"></i>
                                Middle Name
                            </label>
                            <input type="text" id="middle_name" name="middle_name" 
                                   value="<?php echo htmlspecialchars($user['middle_name'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="surname">
                                <i class="fas fa-user"></i>
                                Surname *
                            </label>
                            <input type="text" id="surname" name="surname" required 
                                   value="<?php echo htmlspecialchars($user['surname'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            <?php echo $action === 'add' ? 'Add User' : 'Update User'; ?>
                        </button>
                        <a href="users.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <script src="../assets/js/main.js"></script>
</body>
</html>

