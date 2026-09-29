<?php
session_start();
$conn = new mysqli("localhost","root","","expense_tracker");

if (!isset($_SESSION['user_id'])) {
    header("Location: user_login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD']==='POST'){
    $type = $conn->real_escape_string($_POST['type']);
    $amount = floatval($_POST['amount']);
    $description = $conn->real_escape_string($_POST['description']);
    $date = $_POST['transaction_date'];
    $recurring = isset($_POST['recurring']) ? 1 : 0;
    $frequency = $recurring ? $conn->real_escape_string($_POST['frequency']) : NULL;

    // ✅ Choose category based on type
    if($type === 'income'){
        $category_id = intval($_POST['income_category_id']);
    } else {
        $category_id = intval($_POST['expense_category_id']);
    }

    if($type && $category_id && $amount > 0 && $date){
        $conn->query("INSERT INTO transactions 
            (user_id,category_id,type,amount,description,transaction_date,recurring,frequency) 
            VALUES ($user_id,$category_id,'$type',$amount,'$description','$date',$recurring,'$frequency')");
        header("Location: transactions.php?success=transaction_added");
        exit;
    }
}

// Fetch categories grouped by type
$income_categories = $conn->query("SELECT * FROM categories WHERE type='income' ORDER BY name ASC");
$expense_categories = $conn->query("SELECT * FROM categories WHERE type='expense' ORDER BY name ASC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Transaction</title>
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
        <li class="nav-item"><a class="nav-link active" href="add_transaction.php">Add Transaction</a></li>
        <li class="nav-item"><a class="nav-link" href="reports.php">Reports</a></li>
        <li class="nav-item"><a class="nav-link" href="budget.php">Budget</a></li>
        <li class="nav-item"><a class="nav-link text-danger" href="logout.php">Logout</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container mt-5">
    <h1 class="mb-4">Add Transaction</h1>

    <div class="card p-4 shadow-sm">
        <form method="POST" class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Type</label>
                <select name="type" id="type" class="form-select" onchange="updateCategories()" required>
                    <option value="">Select Type</option>
                    <option value="income">Income</option>
                    <option value="expense">Expense</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Amount</label>
                <input type="number" step="0.01" name="amount" class="form-control" required>
            </div>

            <!-- Income Categories -->
            <div class="col-md-6" id="incomeCats" style="display:none;">
                <label class="form-label">Income Category</label>
                <select name="income_category_id" class="form-select">
                    <option value="">Select Category</option>
                    <?php while($row=$income_categories->fetch_assoc()){ ?>
                        <option value="<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['name']); ?></option>
                    <?php } ?>
                </select>
            </div>

            <!-- Expense Categories -->
            <div class="col-md-6" id="expenseCats" style="display:none;">
                <label class="form-label">Expense Category</label>
                <select name="expense_category_id" class="form-select">
                    <option value="">Select Category</option>
                    <?php while($row=$expense_categories->fetch_assoc()){ ?>
                        <option value="<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['name']); ?></option>
                    <?php } ?>
                </select>
            </div>

            <div class="col-md-12">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" placeholder="Optional"></textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label">Date</label>
                <input type="date" name="transaction_date" class="form-control" required>
            </div>

            <!-- Recurring Option -->
            <div class="col-md-6">
                <label class="form-label">Recurring</label><br>
                <input type="checkbox" id="recurring" name="recurring" onclick="toggleRecurring()"> Yes
            </div>
            <div class="col-md-6" id="frequencyDiv" style="display:none;">
                <label class="form-label">Frequency</label>
                <select name="frequency" class="form-select">
                    <option value="daily">Daily</option>
                    <option value="weekly">Weekly</option>
                    <option value="monthly">Monthly</option>
                    <option value="yearly">Yearly</option>
                </select>
            </div>

            <div class="col-md-12">
                <button type="submit" class="btn btn-primary">Add Transaction</button>
                <a href="transactions.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

</body>
</html>
