<?php
session_start();
$conn = new mysqli("localhost","root","","expense_tracker");

if (!isset($_SESSION['user_id'])) {
    header("Location: user_login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user info + preferences
$stmt = $conn->prepare("SELECT username, email, monthly_budget, currency, theme FROM users WHERE id=?");
$stmt->bind_param("i",$user_id);
$stmt->execute();
$stmt->bind_result($username,$email,$monthly_budget,$currency,$theme);
$stmt->fetch();
$stmt->close();

// Current month spending (expenses only)
$stmt = $conn->prepare("SELECT SUM(amount) AS spent FROM transactions 
                        WHERE user_id=? AND type='expense'
                        AND MONTH(transaction_date)=MONTH(CURDATE()) 
                        AND YEAR(transaction_date)=YEAR(CURDATE())");
$stmt->bind_param("i",$user_id);
$stmt->execute();
$total = $stmt->get_result()->fetch_assoc()['spent'] ?? 0;
$stmt->close();

// Forecast (average of last 6 months expenses)
$stmt = $conn->prepare("SELECT SUM(amount) AS total 
                        FROM transactions 
                        WHERE user_id=? AND type='expense'
                        GROUP BY DATE_FORMAT(transaction_date,'%Y-%m') 
                        ORDER BY transaction_date DESC LIMIT 6");
$stmt->bind_param("i",$user_id);
$stmt->execute();
$res = $stmt->get_result();
$expense_totals = [];
while($row=$res->fetch_assoc()){ $expense_totals[]=$row['total']; }
$stmt->close();
$forecast = (count($expense_totals)>0)? array_sum($expense_totals)/count($expense_totals):0;

// Income totals (last 6 months)
$stmt = $conn->prepare("SELECT SUM(amount) AS total 
                        FROM transactions 
                        WHERE user_id=? AND type='income'
                        GROUP BY DATE_FORMAT(transaction_date,'%Y-%m') 
                        ORDER BY transaction_date DESC LIMIT 6");
$stmt->bind_param("i",$user_id);
$stmt->execute();
$res = $stmt->get_result();
$income_totals = [];
while($row=$res->fetch_assoc()){ $income_totals[]=$row['total']; }
$stmt->close();

// Net savings (income - expense per month)
$net_savings = [];
for($i=0;$i<count($income_totals);$i++){
    $income = $income_totals[$i] ?? 0;
    $expense = $expense_totals[$i] ?? 0;
    $net_savings[] = $income - $expense;
}

// Remaining budget
$remaining = ($monthly_budget>0)? $monthly_budget-$total:0;

// Recent transactions (last 5 expenses)
$stmt = $conn->prepare("SELECT c.name AS category, t.amount, t.transaction_date 
                        FROM `transactions` t 
                        JOIN categories c ON t.category_id = c.id
                        WHERE t.user_id = ? AND t.type = 'expense' 
                        ORDER BY t.transaction_date DESC 
                        LIMIT 5");

$stmt->bind_param("i",$user_id);
$stmt->execute();
$recent = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>User Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body class="<?php echo ($theme=='dark')?'dark-mode':'bg-light'; ?>">

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="user.php">Expense Tracker</a>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link active" href="user.php">Dashboard</a></li>
         <li class="nav-item"><a class="nav-link" href="preference.php">Preferences</a></li>
        <li class="nav-item"><a class="nav-link" href="transactions.php">Transactions</a></li>
        <li class="nav-item"><a class="nav-link" href="categories.php">Categories</a></li>
        <li class="nav-item"><a class="nav-link" href="reports.php">Reports</a></li>
        <li class="nav-item"><a class="nav-link" href="budget.php">Budget</a></li>
        <li class="nav-item"><a class="nav-link" href="user_profile.php">Profile</a></li>
        <li class="nav-item"><a class="nav-link text-danger" href="logout.php">Logout</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container mt-5">
    <h1 class="mb-4">Welcome, <?php echo htmlspecialchars($username); ?></h1>

    <!-- Profile Info -->
    <div class="card p-4 mb-4">
        <h3>Profile</h3>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($email); ?></p>
        <p><strong>Currency:</strong> <?php echo $currency; ?></p>
        <p><strong>Theme:</strong> <?php echo ucfirst($theme); ?></p>
        <a href="reset_password.php" class="btn btn-warning">Change Password</a>
        <a href="logout.php" class="btn btn-danger">Logout</a>
    </div>

    <!-- Dashboard Overview -->
    <div class="row g-4">
        <div class="col-md-4">
            <div class="card text-center p-3">
                <h4>This Month’s Spending</h4>
                <p class="fs-3 text-danger"><?php echo $currency.number_format((float)$total,2); ?></p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center p-3">
                <h4>Remaining Budget</h4>
                <p class="fs-3 text-<?php echo ($remaining<0)?'danger':'success'; ?>">
                    <?php echo $currency.number_format((float)$remaining,2); ?>
                </p>
                <div class="progress mt-2">
                    <div class="progress-bar <?php echo ($remaining<0)?'bg-danger':'bg-success'; ?>" 
                         role="progressbar" 
                         style="width: <?php echo ($monthly_budget>0)? min(100, ($total/$monthly_budget)*100):0; ?>%">
                         <?php echo ($monthly_budget>0)? round(($total/$monthly_budget)*100,1):0; ?>%
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center p-3">
                <h4>Forecasted Spending</h4>
                <p class="fs-3 text-warning"><?php echo $currency.number_format((float)$forecast,2); ?></p>
            </div>
        </div>
    </div>

    <!-- Quick Add Expense -->
    <div class="card p-4 mt-4">
        <h3>Quick Add Expense</h3>
        <form method="post" action="add_transaction.php" class="row g-3">
            <input type="hidden" name="type" value="expense">
            <div class="col-md-4">
                <input type="number" step="0.01" name="amount" class="form-control" placeholder="Amount" required>
            </div>
            <div class="col-md-4">
                <input type="date" name="transaction_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-success w-100">Add Expense</button>
            </div>
        </form>
    </div>

    <!-- Recent Expenses -->
    <div class="card p-4 mt-4">
        <h3>Recent Expenses</h3>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Amount</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row=$recent->fetch_assoc()){ ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['category']); ?></td>
                    <td><?php echo $currency.number_format((float)$row['amount'],2); ?></td>
                    <td><?php echo date("M d, Y", strtotime($row['transaction_date'])); ?></td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <!-- Chart.js Spending Trend -->
    <div class="card p-4 mt-4">
        <h3>Income vs Expenses vs Net Savings (Last 6 Months)</h3>
        <canvas id="spendingChart"></canvas>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('spendingChart');

new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode(
            array_reverse(
                array_map(fn($i)=>date("M",strtotime("-$i month")), range(0,count($expense_totals)-1))
            )
        ); ?>,
        datasets: [
            {
                label: 'Monthly Expenses',
                data: <?php echo json_encode(array_reverse($expense_totals)); ?>,
                borderColor: '#dc3545',
                backgroundColor: 'rgba(220,53,69,0.2)',
                tension: 0.3,
                fill: true,
                pointBackgroundColor: '#dc3545',
                pointRadius: 4
            },
            {
                label: 'Monthly Income',
                data: <?php echo json_encode(array_reverse($income_totals)); ?>,
                borderColor: '#28a745',
                backgroundColor: 'rgba(40,167,69,0.2)',
                tension: 0.3,
                fill: true,
                pointBackgroundColor: '#28a745',
                pointRadius: 4
            },
            {
                label: 'Net Savings',
                data: <?php echo json_encode(array_reverse($net_savings)); ?>,
                borderColor: '#007bff',
                backgroundColor: 'rgba(0,123,255,0.2)',
                tension: 0.3,
                fill: true,
                pointBackgroundColor: '#007bff',
                pointRadius: 4
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'top' },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return '<?php echo $currency; ?>' + parseFloat(context.raw).toFixed(2);
                    }
                }
            }
        },
        scales: {
            x: { title: { display: true, text: 'Month' } },
            y: { title: { display: true, text: 'Amount (<?php echo $currency; ?>)' }, beginAtZero: true }
        }
    }
});

// === Stacked Bar Chart ===
const barCtx = document.createElement('canvas');
barCtx.id = 'barChart';
document.querySelector('.container').appendChild(barCtx);

new Chart(barCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(
            array_reverse(
                array_map(fn($i)=>date("M",strtotime("-$i month")), range(0,count($expense_totals)-1))
            )
        ); ?>,
        datasets: [
            {
                label: 'Income',
                data: <?php echo json_encode(array_reverse($income_totals)); ?>,
                backgroundColor: '#28a745'
            },
            {
                label: 'Expenses',
                data: <?php echo json_encode(array_reverse($expense_totals)); ?>,
                backgroundColor: '#dc3545'
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'top' },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return '<?php echo $currency; ?>' + parseFloat(context.raw).toFixed(2);
                    }
                }
            }
        },
        scales: {
            x: { stacked: true, title: { display: true, text: 'Month' } },
            y: { stacked: true, title: { display: true, text: 'Amount (<?php echo $currency; ?>)' }, beginAtZero: true }
        }
    }
});
</script>
</body>
</html>