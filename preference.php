<?php
session_start();
$conn = new mysqli("localhost","root","","expense_tracker");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

$user_id = $_SESSION['user_id'] ?? 1;

// Initialize defaults
$message = "";
$message_type = ""; // success, info, danger
$theme = "light";
$currency = "₦";
$monthly_budget = 0;

// Fetch preferences
$stmt = $conn->prepare("SELECT theme, currency, monthly_budget FROM users WHERE id=?");
$stmt->bind_param("i",$user_id);
$stmt->execute();
$stmt->bind_result($theme,$currency,$monthly_budget);
$stmt->fetch();
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['reset'])) {
        // Reset to defaults
        $theme = "light";
        $currency = "₦";
        $monthly_budget = 0;

        $stmt = $conn->prepare("UPDATE users SET theme=?, currency=?, monthly_budget=? WHERE id=?");
        $stmt->bind_param("ssii",$theme,$currency,$monthly_budget,$user_id);
        $stmt->execute();
        $stmt->close();

        $message = "Preferences reset to defaults.";
        $message_type = "info";
    } else {
        // Save custom preferences
        $theme = $_POST['theme'] ?? "light";
        $currency = $_POST['currency'] ?? "₦";
        $monthly_budget = floatval($_POST['monthly_budget'] ?? 0);

        $stmt = $conn->prepare("UPDATE users SET theme=?, currency=?, monthly_budget=? WHERE id=?");
        $stmt->bind_param("ssii",$theme,$currency,$monthly_budget,$user_id);
        $stmt->execute();
        $stmt->close();

        $message = "Preferences updated successfully.";
        $message_type = "success";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Preferences</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body class="<?php echo $theme === 'dark' ? 'bg-dark text-light' : 'bg-light'; ?>">

<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="user.php">Expense Tracker</a>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="user.php">User Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="reports.php">Reports</a></li>
        <li class="nav-item"><a class="nav-link active" href="preference.php">Preferences</a></li>
        <li class="nav-item"><a class="nav-link" href="notification.php">Alerts</a></li>
        <li class="nav-item"><a class="nav-link text-danger" href="logout.php">Logout</a></li>
      </ul>
    </div>
  </div>
</nav>

<!-- Main Content -->
<div class="container mt-5">
    <h1 class="mb-4 fw-bold">User Preferences</h1>

    <form method="post" class="card shadow-sm p-4">
        <!-- Theme Selection -->
        <div class="mb-3">
            <label class="form-label fw-semibold">Theme</label>
            <select name="theme" id="themeSelect" class="form-select">
                <option value="light" <?php if($theme=="light") echo "selected"; ?>>Light</option>
                <option value="dark" <?php if($theme=="dark") echo "selected"; ?>>Dark</option>
                <option value="auto" <?php if($theme=="auto") echo "selected"; ?>>Auto (System Default)</option>
            </select>
        </div>

        <!-- Currency Selection -->
        <div class="mb-3">
            <label class="form-label fw-semibold">Currency Symbol</label>
            <select name="currency" id="currencySelect" class="form-select">
                <option value="₦" <?php if($currency=="₦") echo "selected"; ?>>₦ (Naira)</option>
                <option value="$" <?php if($currency=="$") echo "selected"; ?>>$ (Dollar)</option>
                <option value="€" <?php if($currency=="€") echo "selected"; ?>>€ (Euro)</option>
                <option value="£" <?php if($currency=="£") echo "selected"; ?>>£ (Pound)</option>
            </select>
        </div>

        <!-- Monthly Budget -->
        <div class="mb-3">
            <label class="form-label fw-semibold">Monthly Budget</label>
            <input type="number" step="0.01" min="0" name="monthly_budget" id="budgetInput" class="form-control" 
                   value="<?php echo htmlspecialchars($monthly_budget); ?>">
            <small class="text-muted">Set your monthly spending limit. You’ll receive alerts when expenses exceed this amount.</small>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary flex-fill">Save Preferences</button>
            <button type="button" class="btn btn-outline-secondary flex-fill" data-bs-toggle="modal" data-bs-target="#resetModal">
                Reset to Defaults
            </button>
        </div>
    </form>

    <!-- Live Preview -->
    <div class="card shadow-sm p-4 mt-4">
        <h3>Live Preview</h3>
        <p id="previewText">Your budget is set to <?php echo $currency.number_format($monthly_budget,2); ?>.</p>
    </div>
</div>

<!-- Reset Confirmation Modal -->
<div class="modal fade" id="resetModal" tabindex="-1" aria-labelledby="resetModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post">
        <div class="modal-header">
          <h5 class="modal-title" id="resetModalLabel">Confirm Reset</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p>Are you sure you want to reset your preferences to the system defaults?</p>
          <ul>
            <li>Theme: Light</li>
            <li>Currency: ₦ (Naira)</li>
            <li>Monthly Budget: 0</li>
          </ul>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="reset" value="1" class="btn btn-danger">Yes, Reset</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Toast Notification -->
<?php if($message): ?>
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
  <div id="liveToast" class="toast align-items-center text-bg-<?php echo $message_type; ?> border-0 show" role="alert">
    <div class="d-flex">
      <div class="toast-body">
        <?php echo $message; ?>
      </div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Live theme preview
document.getElementById('themeSelect').addEventListener('change', function() {
    if (this.value === 'dark') {
        document.body.className = 'bg-dark text-light';
    } else {
        document.body.className = 'bg-light';
    }
});

// Live currency + budget preview
function updatePreview() {
    const currency = document.getElementById('currencySelect').value;
    const budget = document.getElementById('budgetInput').value || 0;
    document.getElementById('previewText').textContent = 
        `Your budget is set to ${currency}${parseFloat(budget).toFixed(2)}.`;
}
document.getElementById('currencySelect').addEventListener('change', updatePreview);
document.getElementById('budgetInput').addEventListener('input', updatePreview);
</script>
</body>
</html>
