<?php
session_start();

// Database connection
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "expense_tracker";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

// Check login type
if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Determine role
$is_admin = isset($_SESSION['admin_id']);
$user_id  = $is_admin ? $_SESSION['admin_id'] : $_SESSION['user_id'];

// Fetch preferences (only for users)
if (!$is_admin) {
    $stmt = $conn->prepare("SELECT monthly_budget, currency FROM users WHERE id=?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($monthly_budget, $currency);
    $stmt->fetch();
    $stmt->close();

    // Calculate total expenses for current month
    $current_month = date("Y-m");
    $total_query = $conn->prepare("SELECT SUM(amount) FROM expenses WHERE user_id=? AND DATE_FORMAT(date,'%Y-%m')=?");
    $total_query->bind_param("is", $user_id, $current_month);
    $total_query->execute();
    $total_query->bind_result($total_spent);
    $total_query->fetch();
    $total_query->close();

    // Budget alert
    $alert_message = "";
    if ($total_spent > $monthly_budget && $monthly_budget > 0) {
        $alert_message = "⚠️ You have exceeded your monthly budget of {$currency}{$monthly_budget}. 
                          Current spending: {$currency}" . number_format($total_spent,2);
    }
}

// Fetch notifications (admin sees all, user sees own)
if ($is_admin) {
    $notifications = $conn->query("SELECT users.username, message, created_at 
                                   FROM notifications 
                                   JOIN users ON notifications.user_id=users.id 
                                   ORDER BY created_at DESC");
} else {
    $notifications = $conn->query("SELECT message, created_at 
                                   FROM notifications 
                                   WHERE user_id=$user_id 
                                   ORDER BY created_at DESC");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Notifications</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="#">Expense Tracker</a>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <?php if($is_admin): ?>
          <li class="nav-item"><a class="nav-link" href="admin.php">Admin Dashboard</a></li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="reports.php">Reports</a></li>
          <li class="nav-item"><a class="nav-link" href="preference.php">Preferences</a></li>
        <?php endif; ?>
        <li class="nav-item"><a class="nav-link active" href="notification.php">Alerts</a></li>
        <li class="nav-item"><a class="nav-link text-danger" href="logout.php">Logout</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container mt-5">
    <h1 class="mb-4">Notifications</h1>

    <?php if(!$is_admin): ?>
        <?php if(!empty($alert_message)): ?>
            <div class="alert alert-danger"><?php echo $alert_message; ?></div>
        <?php else: ?>
            <div class="alert alert-success">✅ You are within your monthly budget.</div>
        <?php endif; ?>
    <?php endif; ?>

    <h3 class="mt-4"><?php echo $is_admin ? "All User Alerts" : "Your Alerts"; ?></h3>
    <table class="table table-striped">
        <thead>
            <tr>
                <?php if($is_admin): ?><th>User</th><?php endif; ?>
                <th>Message</th><th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row=$notifications->fetch_assoc()){ ?>
            <tr>
                <?php if($is_admin): ?><td><?php echo htmlspecialchars($row['username']); ?></td><?php endif; ?>
                <td><?php echo htmlspecialchars($row['message']); ?></td>
                <td><?php echo $row['created_at']; ?></td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
</div>
</body>
</html>
