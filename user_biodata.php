<?php
session_start();
$conn = new mysqli("localhost","root","","expense_tracker");

if (!isset($_SESSION['user_id'])) {
    header("Location: user_login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle biodata update
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['update_biodata'])){
    $fullname = $conn->real_escape_string($_POST['fullname']);
    $dob = $_POST['dob'];
    $gender = $conn->real_escape_string($_POST['gender']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $address = $conn->real_escape_string($_POST['address']);
    $occupation = $conn->real_escape_string($_POST['occupation']);

    $conn->query("UPDATE user_biodata 
                  SET fullname='$fullname', dob='$dob', gender='$gender', phone='$phone', address='$address', occupation='$occupation' 
                  WHERE user_id=$user_id");

    header("Location: user_biodata.php?success=1");
    exit;
}

// Fetch user info with JOIN
$res = $conn->query("SELECT u.username, b.fullname, b.dob, b.gender, b.phone, b.address, b.occupation 
                     FROM users u 
                     LEFT JOIN user_biodata b ON u.id = b.user_id 
                     WHERE u.id=$user_id");
$user = $res->fetch_assoc();
?>
<!DOCTYPE html>
<html>
<head>
    <title>User Biodata</title>
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
        <li class="nav-item"><a class="nav-link active" href="user_biodata.php">Biodata</a></li>
        <li class="nav-item"><a class="nav-link" href="transactions.php">Transactions</a></li>
        <li class="nav-item"><a class="nav-link" href="reports.php">Reports</a></li>
        <li class="nav-item"><a class="nav-link" href="budget.php">Budget</a></li>
        <li class="nav-item"><a class="nav-link text-danger" href="logout.php">Logout</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container mt-5">
    <h1 class="mb-4">User Biodata</h1>

    <?php if(isset($_GET['success'])){ ?>
        <div class="alert alert-success">Biodata updated successfully!</div>
    <?php } ?>

    <div class="card p-4 shadow-sm">
        <h3>My Biodata</h3>
        <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
        <p><strong>Full Name:</strong> <?php echo htmlspecialchars($user['fullname']); ?></p>
        <p><strong>Date of Birth:</strong> <?php echo htmlspecialchars($user['dob']); ?></p>
        <p><strong>Gender:</strong> <?php echo htmlspecialchars($user['gender']); ?></p>
        <p><strong>Phone:</strong> <?php echo htmlspecialchars($user['phone']); ?></p>
        <p><strong>Address:</strong> <?php echo htmlspecialchars($user['address']); ?></p>
        <p><strong>Occupation:</strong> <?php echo htmlspecialchars($user['occupation']); ?></p>

        <form method="POST" class="mt-3">
            <input type="hidden" name="update_biodata" value="1">
            <div class="mb-2"><input type="text" name="fullname" class="form-control" value="<?php echo htmlspecialchars($user['fullname']); ?>" placeholder="Full Name" required></div>
            <div class="mb-2"><input type="date" name="dob" class="form-control" value="<?php echo $user['dob']; ?>" required></div>
            <div class="mb-2">
                <select name="gender" class="form-select" required>
                    <option value="">Select Gender</option>
                    <option value="Male" <?php if($user['gender']=="Male") echo "selected"; ?>>Male</option>
                    <option value="Female" <?php if($user['gender']=="Female") echo "selected"; ?>>Female</option>
                    <option value="Other" <?php if($user['gender']=="Other") echo "selected"; ?>>Other</option>
                </select>
            </div>
            <div class="mb-2"><input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>" placeholder="Phone Number"></div>
            <div class="mb-2"><textarea name="address" class="form-control" placeholder="Address"><?php echo htmlspecialchars($user['address']); ?></textarea></div>
            <div class="mb-2"><input type="text" name="occupation" class="form-control" value="<?php echo htmlspecialchars($user['occupation']); ?>" placeholder="Occupation"></div>
            <button type="submit" class="btn btn-primary">Update Biodata</button>
        </form>
    </div>
</div>

</body>
</html>
