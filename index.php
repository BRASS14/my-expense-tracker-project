<?php
session_start();

// Database connection
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "expense_tracker";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// User login check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user preferences
$stmt = $conn->prepare("SELECT currency, theme FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($currency, $theme);
$stmt->fetch();
$stmt->close();

// Handle expense submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $description = $_POST['description'];
    $amount = $_POST['amount'];
    $date = $_POST['date'];
    $category = $_POST['category'];

    $stmt = $conn->prepare("INSERT INTO expenses (user_id, description, amount, date, category) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("isdss", $user_id, $description, $amount, $date, $category);
    $stmt->execute();
    $stmt->close();
}

// Fetch expenses
$result = $conn->query("SELECT description, amount, date, category FROM expenses WHERE user_id = $user_id ORDER BY date DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Expense Tracker</title>
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom Theme -->
    <link href="css/style.css" rel="stylesheet">
    <?php if ($theme == "dark") { ?>
        <link href="css/dark-mode.css" rel="stylesheet" id="dark-theme">
    <?php } else { ?>
        <link href="css/light-mode.css" rel="stylesheet" id="light-theme">
    <?php } ?>
</head>

<body class="bg-light">
    <div class="container mt-5">
        <h1 class="text-center mb-4">Expense Tracker</h1>

        <!-- Navigation -->
        <nav class="mb-4 d-flex justify-content-center gap-2">
            <a href="dashboard.php" class="btn btn-primary">Dashboard</a>
            <a href="profile.php" class="btn btn-secondary">Profile</a>
            <a href="preferences.php" class="btn btn-info">Preferences</a>
            <a href="logout.php" class="btn btn-danger">Logout</a>
        </nav>

        <!-- Expense Form -->
        <div class="card p-4 mb-4">
            <h3>Add Expense</h3>
            <form method="post" class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="description" class="form-control" placeholder="Description" required>
                </div>
                <div class="col-md-2">
                    <input type="number" step="0.01" name="amount" class="form-control" placeholder="Amount" required>
                </div>
                <div class="col-md-3">
                    <input type="date" name="date" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <select name="category" class="form-select" required>
                        <option value="Food">Food</option>
                        <option value="Transport">Transport</option>
                        <option value="Bills">Bills</option>
                        <option value="Entertainment">Entertainment</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-success">Add Expense</button>
                </div>
            </form>
        </div>

        <!-- Expense Table -->
        <div class="card p-4">
            <h3>Expense History</h3>
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Description</th>
                        <th>Amount</th>
                        <th>Date</th>
                        <th>Category</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result->fetch_assoc()) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['description']); ?></td>
                        <td><?php echo $currency . number_format($row['amount'], 2); ?></td>
                        <td><?php echo $row['date']; ?></td>
                        <td><?php echo $row['category']; ?></td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
