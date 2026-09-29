<?php
session_start();
$conn = new mysqli("localhost","root","","expense_tracker");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION['user_id'])) {
    header("Location: user_login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// ✅ Seed categories if table is empty
$check = $conn->query("SELECT COUNT(*) AS cnt FROM categories");
if ($check && $check->fetch_assoc()['cnt'] == 0) {
    $conn->query("INSERT INTO `categories` (name, type) VALUES
    ('Salary', 'income'),
    ('Business', 'income'),
    ('Investments', 'income'),
    ('Other Income', 'income'),
    ('Food', 'expense'),
    ('Transportation', 'expense'),
    ('Rent', 'expense'),
    ('Utilities', 'expense'),
    ('Entertainment', 'expense'),
    ('Healthcare', 'expense'),
    ('Education', 'expense'),
    ('Other Expense', 'expense')");
}

// Pagination setup
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Filters
$type_filter     = $_GET['type'] ?? '';
$category_filter = $_GET['category'] ?? '';
$start_date      = $_GET['start_date'] ?? '';
$end_date        = $_GET['end_date'] ?? '';

// Build filter query
$where  = "WHERE t.user_id=?";
$params = [$user_id];
$types  = "i";

if ($type_filter) {
    $where .= " AND t.type=?";
    $params[] = $type_filter;
    $types   .= "s";
}
if ($category_filter) {
    $where .= " AND c.id=?";
    $params[] = $category_filter;
    $types   .= "i";
}
if ($start_date) {
    $where .= " AND t.transaction_date >= ?";
    $params[] = $start_date;
    $types   .= "s";
}
if ($end_date) {
    $where .= " AND t.transaction_date <= ?";
    $params[] = $end_date;
    $types   .= "s";
}

// Count total transactions
$stmt = $conn->prepare("SELECT COUNT(*) AS total 
                        FROM `transactions` t 
                        JOIN categories c ON t.category_id = c.id 
                        $where");
$stmt->bind_param($types, ...$params);
$stmt->execute();
$total_transactions = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$total_pages = ceil($total_transactions / $limit);

// Fetch transactions
$stmt = $conn->prepare("SELECT t.id, t.transaction_date, t.type, c.name AS category, 
                               t.description, t.amount
                        FROM `transactions` t
                        JOIN categories c ON t.category_id = c.id
                        $where
                        ORDER BY t.transaction_date DESC 
                        LIMIT ? OFFSET ?");
$params_with_limit = array_merge($params, [$limit, $offset]);
$types_with_limit  = $types . "ii";
$stmt->bind_param($types_with_limit, ...$params_with_limit);
$stmt->execute();
$transactions = $stmt->get_result();
$stmt->close();

// Fetch categories for filter dropdown
$categories = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>All Transactions</title>
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
        <li class="nav-item"><a class="nav-link active" href="transactions.php">View Transactions</a></li>
        <li class="nav-item"><a class="nav-link" href="add_transaction.php">Add Transaction</a></li>
        <li class="nav-item"><a class="nav-link" href="reports.php">Reports</a></li>
        <li class="nav-item"><a class="nav-link" href="budget.php">Budget</a></li>
        <li class="nav-item"><a class="nav-link text-danger" href="logout.php">Logout</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container mt-5">
    <h1 class="mb-4">All Transactions</h1>

    <!-- Filters -->
    <div class="card p-4 mb-4 shadow-sm">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <select name="type" class="form-select">
                    <option value="">All Types</option>
                    <option value="income" <?php if($type_filter=='income') echo 'selected'; ?>>Income</option>
                    <option value="expense" <?php if($type_filter=='expense') echo 'selected'; ?>>Expense</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="category" class="form-select">
                    <option value="">All Categories</option>
                    <?php while($c=$categories->fetch_assoc()){ ?>
                        <option value="<?php echo $c['id']; ?>" <?php if($category_filter==$c['id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($c['name']); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
            <div class="col-md-3">
                <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
            </div>
            <div class="col-md-3">
                <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
            </div>
            <div class="col-md-12">
                <button type="submit" class="btn btn-primary">Apply Filters</button>
                <a href="transactions.php" class="btn btn-secondary">Reset</a>
                <button type="button" class="btn btn-success" onclick="downloadCSV()">Export CSV</button>
            </div>
        </form>
    </div>

    <!-- Transactions Table -->
    <div class="card p-4 shadow-sm">
        <table class="table table-striped align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Category</th>
                    <th>Description</th>
                    <th>Amount</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row=$transactions->fetch_assoc()){ ?>
                <tr>
                    <td><?php echo date("M d, Y", strtotime($row['transaction_date'])); ?></td>
                    <td>
                        <span class="badge bg-<?php echo ($row['type']=='income')?'success':'danger'; ?>">
                            <?php echo ucfirst($row['type']); ?>
                        </span>
                    </td>
                    <td><?php echo htmlspecialchars($row['category']); ?></td>
                    <td><?php echo htmlspecialchars($row['description']); ?></td>
                    <td><?php echo number_format((float)$row['amount'],2); ?></td>
                    <td>
                        <a href="remove_transaction.php?edit_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                        <a href="remove_transaction.php?delete_id=<?php echo $row['id']; ?>" 
                           class="btn btn-sm btn-danger" onclick="return confirm('Delete this transaction?');">Delete</a>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <nav>
          <ul class="pagination justify-content-center">
            <?php if($page > 1){ ?>
              <li class="page-item">
                <a class="page-link" href="?page=<?php echo $page-1; ?>&type=<?php echo $type_filter; ?>&category=<?php echo $category_filter; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>">Previous</a>
              </li>
            <?php } ?>
            
            <?php for($i=1;$i<=$total_pages;$i++){ ?>
              <li class="page-item <?php echo ($i==$page)?'active':''; ?>">
                <a class="page-link" href="?page=<?php echo $i; ?>&type=<?php echo $type_filter; ?>&category=<?php echo $category_filter; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>"><?php echo $i; ?></a>
              </li>
            <?php } ?>
                        <?php if($page < $total_pages){ ?>
              <li class="page-item">
                <a class="page-link" href="?page=<?php echo $page+1; ?>&type=<?php echo $type_filter; ?>&category=<?php echo $category_filter; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>">Next</a>
              </li>
            <?php } ?>
          </ul>
        </nav>
    </div>
</div>

<script>
// Export filtered transactions to CSV
function downloadCSV(){
    let rows = document.querySelectorAll("table tr");
    let csv = [];
    
    rows.forEach(row => {
        let cols = row.querySelectorAll("td, th");
        let rowData = [];
        cols.forEach(col => {
            let text = col.innerText.replace(/"/g, '""'); // escape quotes
            rowData.push('"' + text + '"');
        });
        csv.push(rowData.join(","));
    });

    let csvString = csv.join("\n");
    let blob = new Blob([csvString], { type: 'text/csv' });
    let url = window.URL.createObjectURL(blob);
    let a = document.createElement("a");
    a.setAttribute("href", url);
    a.setAttribute("download", "transactions.csv");
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
}
</script>
</body>
</html>

            