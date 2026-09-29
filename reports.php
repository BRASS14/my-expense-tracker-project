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
$total_income = $conn->query("SELECT SUM(amount) AS total FROM transactions t $where AND t.type='income'")->fetch_assoc()['total'] ?? 0;
$total_expense = $conn->query("SELECT SUM(amount) AS total FROM transactions t $where AND t.type='expense'")->fetch_assoc()['total'] ?? 0;
$balance = $total_income - $total_expense;

// Breakdown by category
$category_data = $conn->query("SELECT c.name, t.type, SUM(t.amount) AS total
                               FROM transactions t
                               JOIN categories c ON t.category_id=c.id
                               $where
                               GROUP BY c.name, t.type
                               ORDER BY c.name ASC");

// Prepare data for charts
$categories = [];
$expenses = [];
$incomes = [];
while($row=$category_data->fetch_assoc()){
    $categories[] = $row['name']." (".ucfirst($row['type']).")";
    if($row['type']=='expense'){
        $expenses[] = $row['total'];
        $incomes[] = 0;
    } else {
        $incomes[] = $row['total'];
        $expenses[] = 0;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-light">

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="user.php">Dashboard</a>
    <div class="collapse navbar-collapse">
        <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="preference.php">Preferences</a></li>
        <li class="nav-item"><a class="nav-link" href="transactions.php">Transactions</a></li>
        <li class="nav-item"><a class="nav-link active" href="reports.php">Reports</a></li>
        <li class="nav-item"><a class="nav-link" href="budget.php">Budget</a></li>
        <li class="nav-item"><a class="nav-link text-danger" href="logout.php">Logout</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container mt-5">
    <h1 class="mb-4">Reports</h1>

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
                <a href="reports.php" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>

    <!-- Summary -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <h5 class="card-title text-success">Total Income</h5>
                    <p class="fs-4 fw-bold"><?php echo number_format($total_income,2); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <h5 class="card-title text-danger">Total Expense</h5>
                    <p class="fs-4 fw-bold"><?php echo number_format($total_expense,2); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <h5 class="card-title text-primary">Balance</h5>
                    <p class="fs-4 fw-bold"><?php echo number_format($balance,2); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="card p-4 mb-4 shadow-sm">
        <h3>Visual Reports</h3>
        <canvas id="incomeExpenseChart" height="100"></canvas>
        <canvas id="categoryChart" height="100" class="mt-4"></canvas>
    </div>
</div>

<script>
const incomeExpenseCtx = document.getElementById('incomeExpenseChart').getContext('2d');
new Chart(incomeExpenseCtx, {
    type: 'doughnut',
    data: {
        labels: ['Income', 'Expense'],
        datasets: [{
            data: [<?php echo $total_income; ?>, <?php echo $total_expense; ?>],
            backgroundColor: ['#28a745','#dc3545']
        }]
    }
});

const categoryCtx = document.getElementById('categoryChart').getContext('2d');
new Chart(categoryCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($categories); ?>,
        datasets: [
            {
                label: 'Income',
                data: <?php echo json_encode($incomes); ?>,
                backgroundColor: '#28a745'
            },
            {
                label: 'Expense',
                data: <?php echo json_encode($expenses); ?>,
                backgroundColor: '#dc3545'
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'top' }
        }
    }
});
</script>

</body>
</html>
