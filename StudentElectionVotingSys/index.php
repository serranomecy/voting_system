<?php
session_start();
require_once 'db.php'; 

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_type'] === 'Admin') {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: voter/vote.php');
    }
    exit();
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!empty($email) && !empty($password)) {
        // Prepare the SQL query
        $stmt = $conn->prepare("
            SELECT u.user_id, u.email, u.password, ut.type_name 
            FROM users u 
            JOIN user_type ut ON u.user_type_id = ut.user_type_id 
            WHERE u.email = ?
        ");
        
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            if ($user && password_verify($password, $user['password'])) {
                // Store user info in session
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['user_type'] = $user['type_name'];

                // Redirect based on user type
                if ($user['type_name'] === 'Admin') {
                    header('Location: admin/dashboard.php');
                } else {
                    header('Location: voter/vote.php');
                }
                exit();
            } else {
                $error_message = 'Invalid email or password.';
            }

            $stmt->close();
        } else {
            $error_message = 'Database query failed.';
        }
    } else {
        $error_message = 'Please fill in all fields.';
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Election Voting System - Login</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="login-body">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <i class="fas fa-vote-yea"></i>
                <h1>Student Election Voting System</h1>
                <p>Please log in to continue</p>
            </div>
            
            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="login-form">
                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i>
                        Email Address
                    </label>
                    <input type="email" id="email" name="email" required 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i>
                        Password
                    </label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-full">
                    <i class="fas fa-sign-in-alt"></i>
                    Login
                </button>
            </form>
            
            
        </div>
    </div>
    
    <script src="assets/js/main.js"></script>
</body>
</html>





