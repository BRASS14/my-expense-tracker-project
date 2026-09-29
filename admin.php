<?php
session_start();
$conn = new mysqli("localhost","root","","expense_tracker");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

// Fetch all users
$users = $conn->query("SELECT id, username, email, monthly_budget, currency, role 
                       FROM users ORDER BY id ASC");

// Summary stats
$total_users = $users->num_rows;
$total_expenses = $conn->query("SELECT SUM(amount) AS total FROM expenses")->fetch_assoc()['total'] ?? 0;

// Default currency (fallback if not set)
$currency = "₦";
if ($users->num_rows > 0) {
    $firstUser = $users->fetch_assoc();
    $currency = $firstUser['currency'] ?: $currency;
    $users->data_seek(0); // reset pointer for later loop
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body class="bg-light">

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="admin.php">Admin Dashboard</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" 
            data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" 
            aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="manage_users.php">Manage Users</a></li>
         <li class="nav-item"><a class="nav-link" href="manage_preference.php">Manage Preferences</a></li>
        <li class="nav-item"><a class="nav-link" href="manage_transactions.php">Transactions</a></li>
        <li class="nav-item"><a class="nav-link" href="manage_reports.php">Reports</a></li>
        <li class="nav-item"><a class="nav-link" href="admin_profile.php">My Profile</a></li>
        <li class="nav-item"><a class="nav-link text-danger" href="logout.php">Logout</a></li>
      </ul>
    </div>
  </div>
</nav>

<!-- Main Content -->
<div class="container mt-5">
    <h1 class="mb-4 fw-bold">Admin Dashboard</h1>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm text-center p-3">
                <h5 class="text-muted">Total Users</h5>
                <p class="fs-3 text-primary fw-bold"><?php echo $total_users; ?></p>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm text-center p-3">
                <h5 class="text-muted">Total Expenses</h5>
                <p class="fs-3 text-success fw-bold"><?php echo $currency.number_format($total_expenses,2); ?></p>
            </div>
        </div>
    </div>

    <!-- User Management -->
    <div class="card shadow-sm p-4 mb-4">
        <h3 class="mb-3">Users Overview</h3>
        <table class="table table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>ID</th><th>Username</th><th>Email</th><th>Budget</th><th>Currency</th><th>Role</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row=$users->fetch_assoc()){ ?>
                <tr onclick="loadUserDetail(<?php echo $row['id']; ?>)" style="cursor:pointer;">
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td><?php echo $currency.number_format($row['monthly_budget'],2); ?></td>
                    <td><?php echo $row['currency']; ?></td>
                    <td>
                        <span class="badge bg-<?php echo ($row['role']=='admin')?'danger':'secondary'; ?>">
                            <?php echo ucfirst($row['role']); ?>
                        </span>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<!-- User Detail Modal -->
<div class="modal fade" id="userModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">User Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="userDetail"></div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function loadUserDetail(id){
  fetch('user_detail.php?id='+id)
    .then(res => res.text())
    .then(html => {
      document.getElementById('userDetail').innerHTML = html;
      new bootstrap.Modal(document.getElementById('userModal')).show();
    })
    .catch(() => {
      document.getElementById('userDetail').innerHTML = "<p class='text-danger'>Error loading user details.</p>";
      new bootstrap.Modal(document.getElementById('userModal')).show();
    });
}
</script>
</body>
</html>
