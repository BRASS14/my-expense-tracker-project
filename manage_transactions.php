<?php
session_start();
$conn = new mysqli("localhost","root","","expense_tracker");

// Only allow admins
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: user_login.php");
    exit();
}

// Handle transaction update
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['edit_transaction'])){
    $tid = (int)$_POST['transaction_id'];
    $amount = floatval($_POST['amount']);
    $description = $conn->real_escape_string($_POST['description']);
    $transaction_date = $conn->real_escape_string($_POST['date']);
    $type = $conn->real_escape_string($_POST['transaction_type']);

    $conn->query("UPDATE transactions 
                  SET amount=$amount, description='$description', 
                      transaction_date='$transaction_date', type='$type' 
                  WHERE id=$tid");
    header("Location: manage_transactions.php?edited=1");
    exit;
}

// Handle transaction delete
if (isset($_GET['delete'])) {
    $tid = (int)$_GET['delete'];
    $conn->query("DELETE FROM transactions WHERE id=$tid");
    header("Location: manage_transactions.php?deleted=1");
    exit;
}

// Build filter query
$where = [];
if (!empty($_GET['username'])) {
    $username = $conn->real_escape_string($_GET['username']);
    $where[] = "u.username LIKE '%$username%'";
}
if (!empty($_GET['transaction_type'])) {
    $type = $conn->real_escape_string($_GET['transaction_type']);
    $where[] = "t.type = '$type'";
}
if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
    $start = $conn->real_escape_string($_GET['start_date']);
    $end = $conn->real_escape_string($_GET['end_date']);
    $where[] = "t.transaction_date BETWEEN '$start' AND '$end'";
}

$where_sql = count($where) ? "WHERE " . implode(" AND ", $where) : "";

// Fetch transactions
$res = $conn->query("SELECT t.id, t.user_id, u.username, u.email, 
                            t.amount, t.type, t.description, t.transaction_date 
                     FROM transactions t 
                     JOIN users u ON t.user_id = u.id 
                     $where_sql
                     ORDER BY t.transaction_date DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Transactions</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="admin.php">Admin Dashboard</a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link active" href="manage_transactions.php">Manage Transactions</a></li>
        <li class="nav-item"><a class="nav-link" href="manage_users.php">Manage Users</a></li>
        <li class="nav-item"><a class="nav-link" href="manage_reports.php">Users Reports</a></li>
        <li class="nav-item"><a class="nav-link text-danger" href="logout.php">Logout</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container mt-5">
    <h1 class="mb-4">Manage Transactions</h1>

    <?php if(isset($_GET['edited'])){ ?><div class="alert alert-info">Transaction updated!</div><?php } ?>
    <?php if(isset($_GET['deleted'])){ ?><div class="alert alert-danger">Transaction deleted!</div><?php } ?>

    <!-- Filter Form -->
    <div class="card p-4 mb-4 shadow-sm">
        <h3>Filter Transactions</h3>
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <input type="text" name="username" class="form-control" placeholder="Search by Username" value="<?php echo htmlspecialchars($_GET['username'] ?? ''); ?>">
            </div>
            <div class="col-md-3">
                <select name="transaction_type" class="form-control">
                    <option value="">All Types</option>
                    <option value="Income" <?php if(($_GET['transaction_type'] ?? '')=='Income') echo 'selected'; ?>>Income</option>
                    <option value="Expense" <?php if(($_GET['transaction_type'] ?? '')=='Expense') echo 'selected'; ?>>Expense</option>
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
                <a href="manage_transactions.php" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>

    <!-- Transactions Table -->
    <div class="card p-4 shadow-sm">
        <h3>All Transactions</h3>
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
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $res->fetch_assoc()){ ?>
                <tr>
                    <form method="POST">
                        <td><?php echo $row['id']; ?>
                            <input type="hidden" name="transaction_id" value="<?php echo $row['id']; ?>">
                        </td>
                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td><input type="number" step="0.01" name="amount" class="form-control form-control-sm" value="<?php echo $row['amount']; ?>"></td>
                        <td>
                            <select name="transaction_type" class="form-control form-control-sm">
                                <option value="Income" <?php if($row['type']=='Income') echo 'selected'; ?>>Income</option>
                                <option value="Expense" <?php if($row['type']=='Expense') echo 'selected'; ?>>Expense</option>
                            </select>
                        </td>
                        <td><input type="text" name="description" class="form-control form-control-sm" value="<?php echo htmlspecialchars($row['description']); ?>"></td>
                        <td><input type="date" name="date" class="form-control form-control-sm" value="<?php echo $row['transaction_date']; ?>"></td>
                        <td>
                            <button type="submit" name="edit_transaction" class="btn btn-sm btn-info">Save</button>
                            <a href="manage_transactions.php?delete=<?php echo $row['id']; ?>" 
                               class="btn btn-sm btn-danger"
                               onclick="return confirm('Delete this transaction?');">Delete</a>
                        </td>
                    </form>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <!-- Summary Section -->
    <?php
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
    ?>
    <div class="card p-4 mt-4 shadow-sm">
        <h3>Summary</h3>
        <p><strong>Total Income:</strong> ₦<?php echo number_format($total_income,2); ?></p>
        <p><strong>Total Expenses:</strong> ₦<?php echo number_format($total_expense,2); ?></p>
        <p><strong>Net Balance:</strong> ₦<?php echo number_format($net_balance,2); ?></p>
    </div>
</div>

</body>
</html>
