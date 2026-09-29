<?php
session_start();
$conn = new mysqli("localhost","root","","expense_tracker");

if (!isset($_SESSION['user_id'])) {
    header("Location: user_login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle email update
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['update_email'])){
    $new_email = $conn->real_escape_string($_POST['email']);
    $conn->query("UPDATE users SET email='$new_email' WHERE id=$user_id");
    header("Location: user_profile.php");
    exit;
}

// Handle password change
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['change_password'])){
    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    $res = $conn->query("SELECT password FROM users WHERE id=$user_id");
    $row = $res->fetch_assoc();
    if(md5($old_password)===$row['password'] && $new_password===$confirm_password){
        $conn->query("UPDATE users SET password='".md5($new_password)."' WHERE id=$user_id");
    }
    header("Location: user_profile.php");
    exit;
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['update_profile'])){
    $username = $conn->real_escape_string($_POST['username']);
    $currency = $conn->real_escape_string($_POST['currency']);
    $budget = floatval($_POST['monthly_budget']);
    $conn->query("UPDATE users SET username='$username', currency='$currency', monthly_budget=$budget WHERE id=$user_id");
    header("Location: user_profile.php");
    exit;
}

// Handle profile picture upload
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['upload_picture'])){
    if(isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error']==0){
        $target_dir = "uploads/";
        if(!is_dir($target_dir)) mkdir($target_dir);
        $filename = $user_id."_".basename($_FILES['profile_picture']['name']);
        $target_file = $target_dir.$filename;
        move_uploaded_file($_FILES['profile_picture']['tmp_name'],$target_file);
        $conn->query("UPDATE users SET profile_picture='$filename' WHERE id=$user_id");
    }
    header("Location: user_profile.php");
    exit;
}

// Handle account deletion
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['delete_account'])){
    $conn->query("DELETE FROM users WHERE id=$user_id");
    session_destroy();
    header("Location: user_login.php");
    exit;
}

// Handle account deactivation
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['deactivate_account'])){
    $conn->query("UPDATE users SET active=0 WHERE id=$user_id");
    session_destroy();
    header("Location: user_login.php?deactivated=1");
    exit;
}

// Fetch user info
$res = $conn->query("SELECT username,email,monthly_budget,currency,profile_picture FROM users WHERE id=$user_id");
$user = $res->fetch_assoc();
?>
<!DOCTYPE html>
<html>
<head>
    <title>User Profile</title>
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
        <li class="nav-item"><a class="nav-link active" href="user_profile.php">Profile</a></li>
        <li class="nav-item"><a class="nav-link" href="transactions.php">Transactions</a></li>
         <li class="nav-item"><a class="nav-link active" href="user_biodata.php">My Bio-Data</a></li>
        <li class="nav-item"><a class="nav-link" href="report.php">Scheduled Reports</a></li>
        <li class="nav-item"><a class="nav-link" href="budget.php">Budget</a></li>
        <li class="nav-item"><a class="nav-link text-danger" href="logout.php">Logout</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container mt-5">
    <h1 class="mb-4">User Profile</h1>

    <!-- Profile Picture -->
    <div class="card p-4 mb-4 shadow-sm text-center">
        <h3>Profile Picture</h3>
        <?php if(!empty($user['profile_picture'])){ ?>
            <img src="uploads/<?php echo htmlspecialchars($user['profile_picture']); ?>" 
                 class="rounded-circle mb-3" width="150" height="150" alt="Profile Picture">
        <?php } else { ?>
            <!-- Default avatar if no picture -->
            <img src="assets/default-avatar.png" 
                 class="rounded-circle mb-3" width="150" height="150" alt="Default Avatar">
            <p class="text-muted">No profile picture uploaded.</p>
        <?php } ?>
        <form method="POST" enctype="multipart/form-data">
            <input type="file" name="profile_picture" class="form-control mb-2" required>
            <button type="submit" name="upload_picture" class="btn btn-primary">Upload Picture</button>
        </form>
    </div>

    <!-- Profile Details -->
    <div class="card p-4 mb-4 shadow-sm">
        <h3>Profile Details</h3>
        <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
        <p><strong>Currency:</strong> <?php echo htmlspecialchars($user['currency']); ?></p>
        <p><strong>Monthly Budget:</strong> <?php echo number_format((float)$user['monthly_budget'],2); ?></p>

        <form method="POST" class="mt-3">
            <input type="hidden" name="update_profile" value="1">
            <div class="mb-2"><input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" required></div>
            <div class="mb-2"><input type="text" name="currency" class="form-control" value="<?php echo htmlspecialchars($user['currency']); ?>" required></div>
            <div class="mb-2"><input type="number" step="0.01" name="monthly_budget" class="form-control" value="<?php echo $user['monthly_budget']; ?>" required></div>
            <button type="submit" class="btn btn-success">Update Profile</button>
        </form>
    </div>

    <!-- Update Email -->
    <div class="card p-4 mb-4 shadow-sm">
        <h3>Update Email</h3>
        <form method="POST">
            <input type="hidden" name="update_email" value="1">
            <div class="mb-2"><input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required></div>
            <button type="submit" class="btn btn-warning">Update Email</button>
        </form>
    </div>

    <!-- Change Password -->
    <div class="card p-4 mb-4 shadow-sm">
        <h3>Change Password</h3>
        <form method="POST">
            <input type="hidden" name="change_password" value="1">
            <div class="mb-2"><input type="password" name="old_password" class="form-control" placeholder="Old Password" required></div>
            <div class="mb-2"><input type="password" name="new_password" class="form-control" placeholder="New Password" required></div>
            <div class="mb-2"><input type="password" name="confirm_password" class="form-control" placeholder="Confirm New Password" required></div>
            <button type="submit" class="btn btn-danger">Change Password</button>
        </form>
    </div>

    <!-- Deactivate Account -->
    <div class="card p-4 mb-4 shadow-sm">
        <h3 class="text-warning">Deactivate Account</h3>
        <p>⚠️ This will temporarily disable your account. You can reactivate later by logging in again.</p>
              <form method="POST" onsubmit="return confirm('Are you sure you want to deactivate your account?');">
            <input type="hidden" name="deactivate_account" value="1">
            <button type="submit" class="btn btn-outline-warning">Deactivate My Account</button>
        </form>
    </div>

    <!-- Delete Account -->
    <div class="card p-4 shadow-sm">
        <h3 class="text-danger">Delete Account</h3>
        <p>⚠️ This action will permanently delete your account and all associated data. It cannot be undone.</p>
        <form method="POST" onsubmit="return confirm('Are you sure you want to delete your account? This action cannot be undone.');">
            <input type="hidden" name="delete_account" value="1">
            <button type="submit" class="btn btn-outline-danger">Delete My Account</button>
        </form>
    </div>
</div>

</body>
</html>
