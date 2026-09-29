<?php
session_start();
$conn = new mysqli("localhost","root","","expense_tracker");

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $stmt = $conn->prepare("SELECT id, reset_expires, role FROM users WHERE reset_token=?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->bind_result($id, $expires, $role);
    if ($stmt->fetch() && strtotime($expires) > time()) {
        $valid = true;
    } else {
        $error = "Invalid or expired token.";
    }
    $stmt->close();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($valid)) {
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    if ($password !== $confirm) {
        $error = "Passwords do not match.";
    } elseif (!preg_match("/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@$!%*?&]).{8,}$/", $password)) {
        $error = "Password must be at least 8 characters long and include uppercase, lowercase, number, and special character.";
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password=?, reset_token=NULL, reset_expires=NULL WHERE id=?");
        $stmt->bind_param("si", $hashed, $id);
        if ($stmt->execute()) {
            // Role-aware success message
            if ($role === 'admin') {
                $message = "Password reset successful. <a href='admin_login.php'>Login as Admin</a>";
            } else {
                $message = "Password reset successful. <a href='user_login.php'>Login as User</a>";
            }
        } else {
            $error = "Failed to reset password.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <h2>Reset Password</h2>
    <?php if(isset($error)) echo "<p class='text-danger'>$error</p>"; ?>
    <?php if(isset($message)) echo "<p class='text-success'>$message</p>"; ?>
    <?php if(isset($valid)) { ?>
    <form method="post" class="card p-4">
        <!-- Password with toggle + strength meter -->
        <div class="input-group mb-3">
            <input type="password" name="password" id="password" class="form-control" placeholder="New Password" required>
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

        <button type="submit" class="btn btn-success">Reset Password</button>
    </form>
    <?php } ?>
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
