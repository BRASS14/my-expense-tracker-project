<?php
session_start();
$conn = new mysqli("localhost","root","","expense_tracker");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $token = bin2hex(random_bytes(32));
    $expires = date("Y-m-d H:i:s", strtotime("+1 hour"));

    $stmt = $conn->prepare("UPDATE users SET reset_token=?, reset_expires=? WHERE email=?");
    $stmt->bind_param("sss", $token, $expires, $email);
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $resetLink = "http://localhost/expense_tracker/reset_password.php?token=$token";
        $message = "Password reset link: <a href='$resetLink'>$resetLink</a>";
    } else {
        $error = "Email not found.";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
        }
        .card {
            max-width: 500px;
            margin: auto;
            margin-top: 80px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
<div class="container">
    <div class="card p-4">
        <h2 class="mb-3 text-center">Forgot Password</h2>
        <?php if(isset($message)) echo "<div class='alert alert-success'>$message</div>"; ?>
        <?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
        <form method="post">
            <div class="mb-3">
                <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Send Reset Link</button>
        </form>
       <p class="mt-3 text-center">
    <a href="admin_login.php" class="btn btn-outline-primary">← Admin Login</a>
</p>

   </div>
</div>
</body>
</html>
