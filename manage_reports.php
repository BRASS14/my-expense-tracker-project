<?php
session_start();
$conn = new mysqli("localhost","root","","expense_tracker");

// Only allow admins
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: user_login.php");
    exit();
}

// Build filter query
$where = [];
if (!empty($_GET['username'])) {
    $username = $conn->real_escape_string($_GET['username']);
    $where[] = "u.username LIKE '%$username%'";
}
if (!empty($_GET['type'])) {
    $type = $conn->real_escape_string($_GET['type']);
    $where[] = "t.type = '$type'";
}
if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
    $start = $conn->real_escape_string($_GET['start_date']);
    $end = $conn->real_escape_string($_GET['end_date']);
    $where[] = "t.transaction_date BETWEEN '$start' AND '$end'";
}

$where_sql = count($where) ? "WHERE " . implode(" AND ", $where) : "";

// Fetch transactions
$res = $conn->query("SELECT t.id, u.username, u.email, t.amount, t.type, t.description, t.transaction_date
                     FROM transactions t
                     JOIN users u ON t.user_id = u.id
                     $where_sql
                     ORDER BY t.transaction_date DESC");

// Summary totals
$total_income = 0;
$total_expense = 0;
$res_summary = $conn->query("SELECT type, SUM(amount) as total
                             FROM transactions t
                             JOIN users u ON t.user_id = u.id
                             $where_sql
                             GROUP BY type");
while($sum = $res_summary->fetch_assoc()){
    if($sum['type'] == 'Income'){
        $total_income = $sum['total'];
    } else {
        $total_expense = $sum['total'];
    }
}
$net_balance = $total_income - $total_expense;

// Per-user aggregated totals
$user_summary = $conn->query("SELECT u.username, u.email,
                                     SUM(CASE WHEN t.type='Income' THEN t.amount ELSE 0 END) as income_total,
                                     SUM(CASE WHEN t.type='Expense' THEN t.amount ELSE 0 END) as expense_total,
                                     (SUM(CASE WHEN t.type='Income' THEN t.amount ELSE 0 END) -
                                      SUM(CASE WHEN t.type='Expense' THEN t.amount ELSE 0 END)) as net_balance
                              FROM transactions t
                              JOIN users u ON t.user_id = u.id
                              $where_sql
                              GROUP BY u.id, u.username, u.email
                              ORDER BY u.username ASC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="admin.php">Admin Dashboard</a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="manage_transactions.php">Manage Transactions</a></li>
        <li class="nav-item"><a class="nav-link" href="manage_users.php">Manage Users</a></li>
        <li class="nav-item"><a class="nav-link active" href="manage_reports.php">Reports</a></li>
        <li class="nav-item"><a class="nav-link text-danger" href="logout.php">Logout</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container mt-5">
    <h1 class="mb-4">User Reports</h1>

    <!-- Filter Form -->
    <div class="card p-4 mb-4 shadow-sm">
        <h3>Filter Reports</h3>
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <input type="text" name="username" class="form-control" placeholder="Search by Username" value="<?php echo htmlspecialchars($_GET['username'] ?? ''); ?>">
            </div>
            <div class="col-md-3">
                <select name="type" class="form-control">
                    <option value="">All Types</option>
                    <option value="Income" <?php if(($_GET['type'] ?? '')=='Income') echo 'selected'; ?>>Income</option>
                    <option value="Expense" <?php if(($_GET['type'] ?? '')=='Expense') echo 'selected'; ?>>Expense</option>
                </select>
            </div>
            <div class="col-md-3">
                <input type="date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($_GET['start_date'] ?? ''); ?>">
            </div>
            <div class="col-md-3">
                <input type="date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($_GET['end_date'] ?? ''); ?>">
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">Apply Filters</button>
                <a href="manage_reports.php" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>

    <!-- Reports Table -->
    <div class="card p-4 shadow-sm">
        <h3>Transactions Report</h3>
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Email</th>
                    <th>Amount</th>
                    <th>Type</th>
                    <th>Description</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $res->fetch_assoc()){ ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td>₦<?php echo number_format($row['amount'],2); ?></td>
                    <td><?php echo $row['type']; ?></td>
                    <td><?php echo htmlspecialchars($row['description']); ?></td>
                    <td><?php echo $row['transaction_date']; ?></td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <!-- Per-User Summary -->
    <div class="card p-4 mt-4 shadow-sm">
        <h3>Per-User Summary</h3>
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>User</th>
                    <th>Email</th>
                    <th>Total Income</th>
                    <th>Total Expenses</th>
                    <th>Net Balance</th>
                </tr>
            </thead>
            <tbody>
                <?php while($usr = $user_summary->fetch_assoc()){ ?>
                <tr>
                    <td><?php echo htmlspecialchars($usr['username']); ?></td>
                    <td><?php echo htmlspecialchars($usr['email']); ?></td>
                    <td>₦<?php echo number_format($usr['income_total'],2); ?></td>
                    <td>₦<?php echo number_format($usr['expense_total'],2); ?></td>
                    <td>₦<?php echo number_format($usr['net_balance'],2); ?></td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <!-- Overall Summary -->
    <div class="card p-4 mt-4 shadow-sm">
        <h3>Overall Summary</h3>
        <p><strong>Total Income:</strong> ₦<?php echo number_format($total_income,2); ?></p>
        <p><strong>Total Expenses:</strong> ₦<?php echo number_format($total_expense,2); ?></p>
        <p><strong>Net Balance:</strong> ₦<?php echo number_format($net_balance,2); ?></p>
    </div>
</div>

</body>
</html>
