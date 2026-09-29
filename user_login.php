<?php
session_start();
$conn = new mysqli("localhost","root","","expense_tracker");

// If user already has a valid cookie, auto-login
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_user'])) {
    $_SESSION['user_id'] = $_COOKIE['remember_user'];
    $_SESSION['role'] = $_COOKIE['remember_role'];
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin.php");
    } else {
        header("Location: user.php");
    }
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);

    $stmt = $conn->prepare("SELECT id, password, role FROM users WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($id, $hashed_password, $role);

    if ($stmt->fetch() && password_verify($password, $hashed_password)) {
        $_SESSION['user_id'] = $id;
        $_SESSION['role'] = $role;

        // If "Remember Me" checked, set cookies for 7 days
        if ($remember) {
            setcookie("remember_user", $id, time() + (7*24*60*60), "/");
            setcookie("remember_role", $role, time() + (7*24*60*60), "/");
        }

        if ($role === 'admin') {
            header("Location: admin.php");
        } else {
            header("Location: user.php");
        }
        exit();
    } else {
        $error = "Invalid email or password.";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <h2 class="mb-4">Login</h2>
    <?php if(isset($error)) echo "<p class='text-danger'>$error</p>"; ?>
    <form method="post" class="card p-4 shadow-sm">
        <div class="mb-3">
            <input type="email" name="email" class="form-control" placeholder="Email" required>
        </div>
        <div class="mb-3 input-group">
            <input type="password" name="password" id="password" class="form-control" placeholder="Password" required>
            <button type="button" class="btn btn-outline-secondary" id="togglePassword">👁️</button>
        </div>
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="remember" id="remember">
            <label class="form-check-label" for="remember">Remember Me</label>
        </div>
        <button type="submit" class="btn btn-primary w-100">Login</button>
    </form>
    <p class="mt-3">
        No account? <a href="register_user.php">Register User</a><br>
        <a href="forgot_password.php">Forgot Password?</a>
    </p>
</div>

<script>
// Toggle password visibility
const passwordInput = document.getElementById('password');
const toggleBtn = document.getElementById('togglePassword');
toggleBtn.addEventListener('click', () => {
    if (passwordInput.type === "password") {
        passwordInput.type = "text";
        toggleBtn.textContent = "🙈";
    } else {
        passwordInput.type = "password";
        toggleBtn.textContent = "👁️";
    }
});
</script>
</body>
</html>
