<?php
session_start();
$conn = new mysqli("localhost","root","","expense_tracker");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (!preg_match("/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@$!%*?&]).{8,}$/", $password)) {
        $error = "Password must be strong.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $role = "admin";
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $username, $email, $hashed_password, $role);

        if ($stmt->execute()) {
            $_SESSION['user_id'] = $stmt->insert_id;
            $_SESSION['role'] = $role;
            header("Location: admin.php");
            exit();
        } else {
            $error = "Registration failed.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>User Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <h2>Admin Registration</h2>
    <?php if(isset($error)) echo "<p class='text-danger'>$error</p>"; ?>
    <form method="post" class="card p-4">
        <input type="text" name="username" class="form-control mb-3" placeholder="Username" required>
        <input type="email" name="email" class="form-control mb-3" placeholder="Email" required>

        <!-- Password with toggle + strength meter -->
        <div class="input-group mb-3">
            <input type="password" name="password" id="password" class="form-control" placeholder="Password" required>
            <button type="button" class="btn btn-outline-secondary" id="togglePassword">👁️</button>
        </div>
        <small id="strength" class="form-text"></small>
        <div class="progress mb-3" style="height: 8px;">
            <div id="strengthBar" class="progress-bar" role="progressbar" style="width: 0%;"></div>
        </div>

        <!-- Confirm password -->
        <div class="input-group mb-3">
            <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Confirm Password" required>
            <button type="button" class="btn btn-outline-secondary" id="toggleConfirm">👁️</button>
        </div>

        <button type="submit" class="btn btn-success">Register</button>
    </form>
    <p class="mt-3 text-center">
    <a href="admin_login.php" class="btn btn-outline-primary">← admin Login</a>
</p>

</div>

<script>
const passwordInput = document.getElementById('password');
const confirmInput = document.getElementById('confirm_password');
const strengthText = document.getElementById('strength');
const strengthBar = document.getElementById('strengthBar');

// Strength meter
function updateStrength(password) {
    let strength = 0;
    if (password.length >= 8) strength++;
    if (/[A-Z]/.test(password)) strength++;
    if (/[a-z]/.test(password)) strength++;
    if (/\d/.test(password)) strength++;
    if (/[@$!%*?&]/.test(password)) strength++;

    let label = "Weak", color = "bg-danger";
    let width = (strength / 5) * 100;

    if (strength >= 4) { label = "Strong"; color = "bg-success"; }
    else if (strength >= 3) { label = "Medium"; color = "bg-warning"; }

    strengthText.textContent = "Password Strength: " + label;
    strengthBar.style.width = width + "%";
    strengthBar.className = "progress-bar " + color;
}

passwordInput.addEventListener('input', () => updateStrength(passwordInput.value));
confirmInput.addEventListener('input', () => {
    confirmInput.setCustomValidity(confirmInput.value !== passwordInput.value ? "Passwords do not match" : "");
});

// Toggle visibility
function toggleVisibility(inputId, buttonId) {
    const input = document.getElementById(inputId);
    const button = document.getElementById(buttonId);
    button.addEventListener('click', () => {
        if (input.type === "password") {
            input.type = "text"; button.textContent = "🙈";
        } else {
            input.type = "password"; button.textContent = "👁️";
        }
    });
}
toggleVisibility("password", "togglePassword");
toggleVisibility("confirm_password", "toggleConfirm");
</script>
</body>
</html>
