<?php
// Database connection
$conn = new mysqli("localhost","root","","expense_tracker");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

$user_id = 1; // Example: replace with loop for all users
$user = $conn->query("SELECT id, username, email FROM users WHERE id=$user_id")->fetch_assoc();

// Fetch user preferences
$pref = $conn->query("SELECT currency, monthly_budget FROM users WHERE id=$user_id")->fetch_assoc();
$currency = $pref['currency'];
$budget = $pref['monthly_budget'];

// Calculate total
$total = $conn->query("SELECT SUM(amount) AS spent FROM expenses 
                       WHERE user_id=$user_id 
                       AND MONTH(date)=MONTH(CURDATE()) 
                       AND YEAR(date)=YEAR(CURDATE())")->fetch_assoc()['spent'];

$budget_note = ($total > $budget && $budget > 0) 
    ? "⚠️ You exceeded your monthly budget of ".$currency.number_format($budget,2).". Total spending: ".$currency.number_format($total,2)."." 
    : "✅ You are within your monthly budget of ".$currency.number_format($budget,2).".";

// Email body preview
$body = "Dear ".$user['username'].",\n\nAttached is your monthly expense report.\n".$budget_note."\n\nRegards,\nExpense Tracker";

// Handle manual send
if (isset($_POST['send_now'])) {
    $to = $user['email'];
    $subject = "Monthly Expense Report";
    $headers = "From: no-reply@expensetracker.com";
    mail($to, $subject, $body, $headers);
    $sent_message = "✅ Report sent immediately to ".$user['email'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Scheduled Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body class="bg-light">

<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="dashboard.php">Expense Tracker</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" 
            data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" 
            aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="reports.php">Reports</a></li>
        <li class="nav-item"><a class="nav-link" href="preference.php">Preferences</a></li>
        <li class="nav-item"><a class="nav-link active" href="scheduled_reports.php">Scheduled Reports</a></li>
        <li class="nav-item"><a class="nav-link" href="notifications.php">Alerts</a></li>
        <li class="nav-item"><a class="nav-link text-danger" href="logout.php">Logout</a></li>
      </ul>
    </div>
  </div>
</nav>

<!-- Main Content -->
<div class="container mt-5">
    <div class="card shadow-sm p-4">
        <h2 class="mb-3">Monthly Report for <?php echo htmlspecialchars($user['username']); ?></h2>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
        <p><strong>Total Spending:</strong> <?php echo $currency.number_format($total,2); ?></p>
        <p><strong>Budget Note:</strong> <?php echo $budget_note; ?></p>
        <hr>
        <h4>Email Preview</h4>
        <pre class="bg-light border rounded p-3"><?php echo $body; ?></pre>
        <hr>
        <!-- Download buttons -->
        <div class="d-flex gap-2">
            <a href="reports.php?format=csv" class="btn btn-success">Download CSV Report</a>
            <a href="reports.php?format=pdf" class="btn btn-primary">Download PDF Report</a>
        </div>
        <!-- Send Now button -->
        <form method="post" class="mt-3">
            <button type="submit" name="send_now" class="btn btn-danger w-100">Send Report Now</button>
        </form>
        <?php if (!empty($sent_message)) { ?>
            <div class="alert alert-success mt-3"><?php echo $sent_message; ?></div>
        <?php } ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
