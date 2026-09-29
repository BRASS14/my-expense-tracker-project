<?php
session_start();
$conn = new mysqli("localhost","root","","expense_tracker");

if (!isset($_SESSION['user_id'])) {
    header("Location: user_login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle add income/expense
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_transaction'])) {
    $category_id = intval($_POST['category_id']);
    $type = $_POST['type']; // income or expense
    $amount = floatval($_POST['amount']);
    $description = $conn->real_escape_string($_POST['description']);
    $transaction_date = $_POST['transaction_date'];

    $conn->query("INSERT INTO transactions (user_id, category_id, type, amount, description, transaction_date, created_at)
                  VALUES ($user_id, $category_id, '$type', $amount, '$description', '$transaction_date', NOW())");
    header("Location: quick_action.php");
    exit;
}

// Handle add category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $conn->query("INSERT INTO categories (name) VALUES ('$name')");
    header("Location: quick_action.php");
    exit;
}

// Handle edit category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_category'])) {
    $id = intval($_POST['id']);
    $name = $conn->real_escape_string($_POST['name']);
    $conn->query("UPDATE categories SET name='$name' WHERE id=$id");
    header("Location: quick_action.php");
    exit;
}

// Handle delete category
if (isset($_GET['delete_category'])) {
    $id = intval($_GET['delete_category']);
    $conn->query("DELETE FROM categories WHERE id=$id");
    header("Location: quick_action.php");
    exit;
}

// Fetch categories for dropdown
$categories = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Quick Actions</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="user.php">Expense Tracker</a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="user.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="transactions.php">Transactions</a></li>
        <li class="nav-item"><a class="nav-link active" href="quick_action.php">Quick Actions</a></li>
        <li class="nav-item"><a class="nav-link" href="reports.php">Reports</a></li>
        <li class="nav-item"><a class="nav-link" href="preference.php">Preferences</a></li>
        <li class="nav-item"><a class="nav-link text-danger" href="logout.php">Logout</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container mt-5">
    <h1 class="mb-4">Quick Actions</h1>

    <!-- Add Income/Expense -->
    <div class="card p-4 mb-4 shadow-sm">
        <h3 class="mb-3">Add Income / Expense</h3>
        <form method="POST" class="row g-3">
            <input type="hidden" name="add_transaction" value="1">
            <div class="col-md-3">
                <select name="type" class="form-select" required>
                    <option value="income">Income</option>
                    <option value="expense">Expense</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="category_id" class="form-select" required>
                    <option value="">Select Category</option>
                    <?php while($c=$categories->fetch_assoc()){ ?>
                        <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="col-md-2">
                <input type="number" step="0.01" name="amount" class="form-control" placeholder="Amount" required>
            </div>
            <div class="col-md-2">
                <input type="date" name="transaction_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            <div class="col-md-12">
                <textarea name="description" class="form-control" placeholder="Description"></textarea>
            </div>
            <div class="col-md-12">
                <button type="submit" class="btn btn-primary">Add Transaction</button>
            </div>
        </form>
    </div>

    <!-- Manage Categories -->
    <div class="card p-4 shadow-sm">
        <h3 class="mb-3">Manage Categories</h3>
        <form method="POST" class="row g-3 mb-3">
            <input type="hidden" name="add_category" value="1">
            <div class="col-md-8">
                <input type="text" name="name" class="form-control" placeholder="New Category Name" required>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-success w-100">Add Category</button>
            </div>
        </form>
        <table class="table table-striped align-middle">
            <thead class="table-dark">
                <tr><th>ID</th><th>Name</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php
                $all_categories = $conn->query("SELECT id, name FROM categories ORDER BY id ASC");
                while($row=$all_categories->fetch_assoc()){ ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td>
                        <!-- Edit Form -->
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="edit_category" value="1">
                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                            <input type="text" name="name" value="<?php echo htmlspecialchars($row['name']); ?>" class="form-control d-inline w-auto" required>
                            <button type="submit" class="btn btn-warning btn-sm">Edit</button>
                        </form>
                        <!-- Delete Link -->
                        <a href="quick_action.php?delete_category=<?php echo $row['id']; ?>" 
                           class="btn btn-danger btn-sm"
                           onclick="return confirm('Delete this category?');">Delete</a>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
