<?php
session_start();
$conn = new mysqli("localhost","root","","expense_tracker");

if (!isset($_SESSION['user_id'])) {
    header("Location: user_login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Filters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date   = isset($_GET['end_date']) ? $_GET['end_date'] : '';

$where = "WHERE t.user_id=$user_id";
if($start_date){
    $where .= " AND t.transaction_date >= '$start_date'";
}
if($end_date){
    $where .= " AND t.transaction_date <= '$end_date'";
}

// Totals
$total_income = $conn->query("SELECT SUM(amount) AS total FROM transactions t $where AND t.type='income'")
                     ->fetch_assoc()['total'] ?? 0;
$total_expense = $conn->query("SELECT SUM(amount) AS total FROM transactions t $where AND t.type='expense'")
                      ->fetch_assoc()['total'] ?? 0;
$balance = $total_income - $total_expense;

// Fetch budgets history
$history = $conn->query("
    SELECT ub.month_year, ub.budget,
           COALESCE(SUM(CASE WHEN t.type='expense' THEN t.amount END),0) AS total_expense
    FROM user_budgets ub
    LEFT JOIN transactions t 
      ON ub.user_id=t.user_id 
     AND DATE_FORMAT(t.transaction_date,'%Y-%m')=ub.month_year
    WHERE ub.user_id=$user_id
    GROUP BY ub.month_year, ub.budget
    ORDER BY ub.month_year DESC
    LIMIT 12
");

// Prepare summary stats
$months_count = 0;
$total_budget_sum = 0;
$total_expense_sum = 0;
$exceeded_count = 0;
$history_rows = [];
while($row=$history->fetch_assoc()){
    $months_count++;
    $total_budget_sum += $row['budget'];
    $total_expense_sum += $row['total_expense'];
    if($row['total_expense'] > $row['budget']) $exceeded_count++;
    $history_rows[] = $row;
}
$avg_budget = ($months_count>0)? $total_budget_sum/$months_count:0;
$avg_expense = ($months_count>0)? $total_expense_sum/$months_count:0;
$exceeded_percent = ($months_count>0)? ($exceeded_count/$months_count)*100:0;

// Fetch currency
$stmt = $conn->prepare("SELECT currency FROM users WHERE id=?");
$stmt->bind_param("i",$user_id);
$stmt->execute();
$stmt->bind_result($currency);
$stmt->fetch();
$stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Budget</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="user_dashboard.php">Expense Tracker</a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="user.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="transactions.php">Transactions</a></li>
        <li class="nav-item"><a class="nav-link active" href="budget.php">Budget</a></li>
        <li class="nav-item"><a class="nav-link" href="reports.php">Reports</a></li>
        <li class="nav-item"><a class="nav-link text-danger" href="logout.php">Logout</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container mt-5">
    <h1 class="mb-4">Budget</h1>

    <!-- Filters -->
    <div class="card p-4 mb-4 shadow-sm">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
            </div>
            <div class="col-md-4">
                <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary">Apply Filters</button>
                <a href="budget.php" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>

    <!-- Summary -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <h5 class="card-title text-success">Total Income</h5>
                    <p class="fs-4 fw-bold"><?php echo $currency.number_format($total_income,2); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <h5 class="card-title text-danger">Total Expense</h5>
                    <p class="fs-4 fw-bold"><?php echo $currency.number_format($total_expense,2); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <h5 class="card-title text-primary">Balance</h5>
                    <p class="fs-4 fw-bold"><?php echo $currency.number_format($balance,2); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Budget History -->
    <div class="card p-4 mt-4 shadow-sm">
        <h3>Budget History (Last 12 Months)</h3>
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Month</th>
                    <th>Budget</th>
                    <th>Expenses</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($history_rows as $row){ 
                    $status = ($row['total_expense'] <= $row['budget']) ? 
                              "<span class='text-success fw-bold'>Within Budget</span>" : 
                              "<span class='text-danger fw-bold'>Exceeded</span>";
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['month_year']); ?></td>
                    <td><?php echo $currency.number_format((float)$row['budget'],2); ?></td>
                    <td><?php echo $currency.number_format((float)$row['total_expense'],2); ?></td>
                    <td><?php echo $status; ?></td>
                </tr>
                <?php } ?>
            </tbody>
            <tfoot>
                <tr class="table-secondary fw-bold">
                    <td>Average (<?php echo $months_count; ?> months)</td>
                    <td><?php echo $currency.number_format((float)$avg_budget,2); ?></td>
                    <td><?php echo $currency.number_format((float)$avg_expense,2); ?></td>
                    <td><?php echo round($exceeded_percent,1); ?>% months exceeded</td>
                </tr>
            </tfoot>
        </table>

        <!-- CSV Export Button -->
        <div class="mt-3">
            <a href="budget.php?export=csv" class="btn btn-success">Download CSV</a>
        </div>
    </div>
</div>

</body>
</html>
