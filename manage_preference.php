<?php
session_start();
$conn = new mysqli("localhost","root","","expense_tracker");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

// Only allow admins
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: user_login.php");
    exit();
}

$message = "";

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_pref'])) {
    $uid = (int)$_POST['user_id'];
    $theme = $_POST['theme'] ?? "light";
    $currency = $_POST['currency'] ?? "₦";
    $monthly_budget = floatval($_POST['monthly_budget'] ?? 0);

    $stmt = $conn->prepare("UPDATE users SET theme=?, currency=?, monthly_budget=? WHERE id=?");
    $stmt->bind_param("ssii",$theme,$currency,$monthly_budget,$uid);
    $stmt->execute();
    $stmt->close();

    $message = "✅ Preferences updated successfully for user ID $uid.";
}

// Fetch all users with preferences
$users = $conn->query("SELECT id, username, email, theme, currency, monthly_budget FROM users ORDER BY id ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage User Preferences</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="admin.php">Admin Dashboard</a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="manage_users.php">Manage Users</a></li>
        <li class="nav-item"><a class="nav-link" href="manage_transactions.php">Transactions</a></li>
        <li class="nav-item"><a class="nav-link" href="manage_reports.php">Reports</a></li>
        <li class="nav-item"><a class="nav-link active" href="manage_preference.php">Preferences</a></li>
        <li class="nav-item"><a class="nav-link text-danger" href="logout.php">Logout</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container mt-5">
    <h1 class="mb-4 fw-bold">Manage User Preferences</h1>

    <?php if($message): ?>
      <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>

    <div class="card shadow-sm p-4">
        <table class="table table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>ID</th><th>Username</th><th>Email</th><th>Theme</th><th>Currency</th><th>Monthly Budget</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row=$users->fetch_assoc()){ ?>
                <tr>
                    <form method="post">
                        <td><?php echo $row['id']; ?>
                            <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                        </td>
                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td>
                            <select name="theme" class="form-select form-select-sm">
                                <option value="light" <?php if($row['theme']=="light") echo "selected"; ?>>Light</option>
                                <option value="dark" <?php if($row['theme']=="dark") echo "selected"; ?>>Dark</option>
                                <option value="auto" <?php if($row['theme']=="auto") echo "selected"; ?>>Auto</option>
                            </select>
                        </td>
                        <td>
                            <select name="currency" class="form-select form-select-sm">
                                <option value="₦" <?php if($row['currency']=="₦") echo "selected"; ?>>₦</option>
                                <option value="$" <?php if($row['currency']=="$") echo "selected"; ?>>$</option>
                                <option value="€" <?php if($row['currency']=="€") echo "selected"; ?>>€</option>
                                <option value="£" <?php if($row['currency']=="£") echo "selected"; ?>>£</option>
                            </select>
                        </td>
                        <td><input type="number" step="0.01" min="0" name="monthly_budget" class="form-control form-control-sm" value="<?php echo $row['monthly_budget']; ?>"></td>
                        <td>
                            <button type="submit" name="update_pref" class="btn btn-sm btn-primary">Update</button>
                        </td>
                    </form>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
