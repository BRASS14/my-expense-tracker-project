<?php
session_start();
$conn = new mysqli("localhost","root","","expense_tracker");

if (!isset($_SESSION['user_id'])) {
    header("Location: user_login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user info
$stmt = $conn->prepare("SELECT username, currency FROM users WHERE id=?");
$stmt->bind_param("i",$user_id);
$stmt->execute();
$stmt->bind_result($username,$currency);
$stmt->fetch();
$stmt->close();

// Expenses by category
$category_expenses = [];
$res = $conn->query("SELECT c.name AS category, SUM(t.amount) AS total 
                     FROM transactions t 
                     JOIN categories c ON t.category_id=c.id 
                     WHERE t.user_id=$user_id AND t.type='expense' 
                     GROUP BY c.id");
while($row=$res->fetch_assoc()){
    $category_expenses[$row['category']] = $row['total'];
}

// Monthly income vs expenses (last 6 months)
$monthly_data = [];
$res = $conn->query("SELECT DATE_FORMAT(transaction_date,'%Y-%m') AS month,
                            SUM(CASE WHEN type='income' THEN amount ELSE 0 END) AS income,
                            SUM(CASE WHEN type='expense' THEN amount ELSE 0 END) AS expense
                     FROM transactions
                     WHERE user_id=$user_id
                     GROUP BY month
                     ORDER BY month DESC
                     LIMIT 6");
while($row=$res->fetch_assoc()){
    $monthly_data[$row['month']] = [
        'income'=>$row['income'],
        'expense'=>$row['expense']
    ];
}
$months = array_reverse(array_keys($monthly_data));
$income_values = array_reverse(array_column($monthly_data,'income'));
$expense_values = array_reverse(array_column($monthly_data,'expense'));
?>
<!DOCTYPE html>
<html>
<head>
    <title>Reports</title>
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
        <li class="nav-item"><a class="nav-link" href="transactions.php">Transactions</a></li>
        <li class="nav-item"><a class="nav-link active" href="report.php">Reports</a></li>
        <li class="nav-item"><a class="nav-link" href="preference.php">Preferences</a></li>
        <li class="nav-item"><a class="nav-link text-danger" href="logout.php">Logout</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container mt-5">
    <h1 class="mb-4">Reports for <?php echo htmlspecialchars($username); ?></h1>

    <!-- Expenses by Category -->
    <div class="card p-4 mb-4 shadow-sm">
        <h3>Expenses by Category</h3>
        <canvas id="categoryChart"></canvas>
    </div>

    <!-- Monthly Income vs Expenses -->
    <div class="card p-4 shadow-sm">
        <h3>Monthly Income vs Expenses (Last 6 Months)</h3>
        <canvas id="monthlyChart"></canvas>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Pie chart for expenses by category
const categoryCtx = document.getElementById('categoryChart');
new Chart(categoryCtx, {
    type: 'pie',
    data: {
        labels: <?php echo json_encode(array_keys($category_expenses)); ?>,
        datasets: [{
            data: <?php echo json_encode(array_values($category_expenses)); ?>,
            backgroundColor: [
                '#e74c3c','#3498db','#2ecc71','#9b59b6','#f1c40f','#1abc9c','#34495e'
            ]
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom' }
        }
    }
});

// Bar chart for monthly income vs expenses
const monthlyCtx = document.getElementById('monthlyChart');
new Chart(monthlyCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($months); ?>,
        datasets: [
            {
                label: 'Income',
                data: <?php echo json_encode($income_values); ?>,
                backgroundColor: '#2ecc71'
            },
            {
                label: 'Expenses',
                data: <?php echo json_encode($expense_values); ?>,
                backgroundColor: '#e74c3c'
            }
        ]
    },
    options: {
        responsive: true,
        scales: {
            y: { beginAtZero: true }
        }
    }
});
</script>
</body>
</html>
