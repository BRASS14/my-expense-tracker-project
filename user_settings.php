<?php
include 'configures_system.php';
check_login();

$user_id = (int)$_SESSION['user_id'];

// Handle update
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['update_settings'])){
    $currency = sanitize($_POST['currency']);
    $budget = floatval($_POST['monthly_budget']);
    $theme = sanitize($_POST['theme']);
    $notifications = isset($_POST['notifications_enabled']) ? 1 : 0;

    $conn->query("INSERT INTO user_settings (user_id,currency,monthly_budget,theme,notifications_enabled)
                  VALUES ($user_id,'$currency',$budget,'$theme',$notifications)
                  ON DUPLICATE KEY UPDATE currency='$currency', monthly_budget=$budget, theme='$theme', notifications_enabled=$notifications");

    header("Location: user_settings.php?success=1");
    exit;
}

// Fetch current settings
$res = $conn->query("SELECT currency, monthly_budget, theme, notifications_enabled 
                     FROM user_settings WHERE user_id=$user_id");
$settings = $res && $res->num_rows > 0 ? $res->fetch_assoc() : [
    'currency' => 'USD',
    'monthly_budget' => 0,
    'theme' => 'light',
    'notifications_enabled' => 1
];
?>
<!DOCTYPE html>
<html>
<head>
    <title>User Settings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="user_dashboard.php">Expense Tracker</a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="user_dashboard.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link active" href="user_settings.php">Settings</a></li>
        <li class="nav-item"><a class="nav-link" href="transactions.php">Transactions</a></li>
        <li class="nav-item"><a class="nav-link" href="user_biodata.php">My Bio-Data</a></li>
        <li class="nav-item"><a class="nav-link" href="report.php">Reports</a></li>
        <li class="nav-item"><a class="nav-link" href="budget.php">Budget</a></li>
        <li class="nav-item"><a class="nav-link text-danger" href="logout.php">Logout</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container mt-5">
    <h1 class="mb-4">User Settings</h1>

    <?php if(isset($_GET['success'])){ ?>
        <div class="alert alert-success">Settings updated successfully!</div>
    <?php } ?>

    <div class="card p-4 shadow-sm">
        <form method="POST">
            <input type="hidden" name="update_settings" value="1">
            <div class="mb-3">
                <label class="form-label">Currency</label>
                <input type="text" name="currency" class="form-control" value="<?php echo htmlspecialchars($settings['currency']); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Monthly Budget</label>
                <input type="number" step="0.01" name="monthly_budget" class="form-control" value="<?php echo $settings['monthly_budget']; ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Theme</label>
                <select name="theme" class="form-select">
                    <option value="light" <?php if($settings['theme']=='light') echo 'selected'; ?>>Light</option>
                    <option value="dark" <?php if($settings['theme']=='dark') echo 'selected'; ?>>Dark</option>
                </select>
            </div>
            <div class="mb-3 form-check">
                <input type="checkbox" name="notifications_enabled" class="form-check-input" <?php if($settings['notifications_enabled']) echo 'checked'; ?>>
                <label class="form-check-label">Enable Notifications</label>
            </div>
            <button type="submit" class="btn btn-primary">Save Settings</button>
        </form>
    </div>
</div>

</body>
</html>
