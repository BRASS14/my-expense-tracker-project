<?php
session_start();
$conn = new mysqli("localhost","root","","expense_tracker");

if (!isset($_SESSION['user_id'])) {
    header("Location: user_login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle delete
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $conn->query("DELETE FROM transactions WHERE id=$delete_id AND user_id=$user_id");
    header("Location: transactions.php?success=transaction_deleted");
    exit();
}

// Handle edit form submission
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['update_transaction'])) {
    $id = intval($_POST['id']);
    $type = $conn->real_escape_string($_POST['type']);
    $category_id = intval($_POST['category_id']);
    $amount = floatval($_POST['amount']);
    $description = $conn->real_escape_string($_POST['description']);
    $date = $_POST['transaction_date'];
    $recurring = isset($_POST['recurring']) ? 1 : 0;
    $frequency = $recurring ? $conn->real_escape_string($_POST['frequency']) : NULL;

    $conn->query("UPDATE transactions 
                  SET type='$type', category_id=$category_id, amount=$amount, description='$description',
                      transaction_date='$date', recurring=$recurring, frequency='$frequency'
                  WHERE id=$id AND user_id=$user_id");
    header("Location: transactions.php?success=transaction_updated");
    exit();
}

// Fetch transaction for editing
$transaction = null;
if (isset($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']);
    $result = $conn->query("SELECT * FROM transactions WHERE id=$edit_id AND user_id=$user_id");
    $transaction = $result->fetch_assoc();
}

// Fetch categories
$income_categories = $conn->query("SELECT * FROM categories WHERE type='income' ORDER BY name ASC");
$expense_categories = $conn->query("SELECT * FROM categories WHERE type='expense' ORDER BY name ASC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit/Delete Transaction</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script>
    function updateCategories(){
        let type = document.getElementById("type").value;
        let incomeCats = document.getElementById("incomeCats");
        let expenseCats = document.getElementById("expenseCats");
        if(type==="income"){
            incomeCats.style.display="block";
            expenseCats.style.display="none";
        } else if(type==="expense"){
            incomeCats.style.display="none";
            expenseCats.style.display="block";
        } else {
            incomeCats.style.display="none";
            expenseCats.style.display="none";
        }
    }
    function toggleRecurring(){
        let checkbox = document.getElementById("recurring");
        let freqDiv = document.getElementById("frequencyDiv");
        freqDiv.style.display = checkbox.checked ? "block" : "none";
    }
    </script>
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
        <li class="nav-item"><a class="nav-link active" href="remove_transaction.php">Edit/Delete</a></li>
        <li class="nav-item"><a class="nav-link" href="reports.php">Reports</a></li>
        <li class="nav-item"><a class="nav-link" href="budget.php">Budget</a></li>
        <li class="nav-item"><a class="nav-link text-danger" href="logout.php">Logout</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container mt-5">
    <h1 class="mb-4">Edit/Delete Transaction</h1>

    <?php if($transaction){ ?>
    <div class="card p-4 shadow-sm">
        <form method="POST" class="row g-3">
            <input type="hidden" name="id" value="<?php echo $transaction['id']; ?>">
            <div class="col-md-6">
                <label class="form-label">Type</label>
                <select name="type" id="type" class="form-select" onchange="updateCategories()" required>
                    <option value="income" <?php if($transaction['type']=='income') echo 'selected'; ?>>Income</option>
                    <option value="expense" <?php if($transaction['type']=='expense') echo 'selected'; ?>>Expense</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Amount</label>
                <input type="number" step="0.01" name="amount" class="form-control" 
                       value="<?php echo $transaction['amount']; ?>" required>
            </div>
            <div class="col-md-6" id="incomeCats" style="display:<?php echo ($transaction['type']=='income')?'block':'none'; ?>;">
                <label class="form-label">Income Category</label>
                <select name="category_id" class="form-select">
                    <?php while($row=$income_categories->fetch_assoc()){ ?>
                        <option value="<?php echo $row['id']; ?>" 
                            <?php if($transaction['category_id']==$row['id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($row['name']); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
            <div class="col-md-6" id="expenseCats" style="display:<?php echo ($transaction['type']=='expense')?'block':'none'; ?>;">
                <label class="form-label">Expense Category</label>
                <select name="category_id" class="form-select">
                    <?php while($row=$expense_categories->fetch_assoc()){ ?>
                        <option value="<?php echo $row['id']; ?>" 
                            <?php if($transaction['category_id']==$row['id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($row['name']); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
            <div class="col-md-12">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control"><?php echo htmlspecialchars($transaction['description']); ?></textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label">Date</label>
                <input type="date" name="transaction_date" class="form-control" 
                       value="<?php echo $transaction['transaction_date']; ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Recurring</label><br>
                <input type="checkbox" id="recurring" name="recurring" onclick="toggleRecurring()" 
                       <?php if($transaction['recurring']) echo 'checked'; ?>> Yes
            </div>
            <div class="col-md-6" id="frequencyDiv" style="display:<?php echo ($transaction['recurring'])?'block':'none'; ?>;">
                <label class="form-label">Frequency</label>
                <select name="frequency" class="form-select">
                    <option value="daily" <?php if($transaction['frequency']=='daily') echo 'selected'; ?>>Daily</option>
                    <option value="weekly" <?php if($transaction['frequency']=='weekly') echo 'selected'; ?>>Weekly</option>
                    <option value="monthly" <?php if($transaction['frequency']=='monthly') echo 'selected'; ?>>Monthly</option>
                    <option value="yearly" <?php if($transaction['frequency']=='yearly') echo 'selected'; ?>>Yearly</option>
                </select>
            </div>
            <div class="col-md-12">
                <button type="submit" name="update_transaction" class="btn btn-primary">Update Transaction</button>
                <a href="remove_transaction.php?delete_id=<?php echo $transaction['id']; ?>" 
                   class="btn btn-danger" onclick="return confirm('Delete this transaction?');">Delete Transaction</a>
                <a href="transactions.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
    <?php } else { ?>
        <div class="alert alert-info">No transaction selected for editing. Go back to <a href="transactions.php">Transactions</a>.</div>
    <?php } ?>
</div>

</body>
</html>
