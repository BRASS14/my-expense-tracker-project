<?php
session_start();
$conn = new mysqli("localhost","root","","expense_tracker");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: user_login.php");
    exit();
}

$admin_id = $_SESSION['user_id'];

// Handle email update
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['update_email'])){
    $new_email = $conn->real_escape_string($_POST['email']);
    $conn->query("UPDATE users SET email='$new_email' WHERE id=$admin_id");
    header("Location: admin_profile.php");
    exit;
}

// Handle password change
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['change_password'])){
    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    $res = $conn->query("SELECT password FROM users WHERE id=$admin_id");
    $row = $res->fetch_assoc();
    if(md5($old_password)===$row['password'] && $new_password===$confirm_password){
        $conn->query("UPDATE users SET password='".md5($new_password)."' WHERE id=$admin_id");
    }
    header("Location: admin_profile.php");
    exit;
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['update_profile'])){
    $username = $conn->real_escape_string($_POST['username']);
    $conn->query("UPDATE users SET username='$username' WHERE id=$admin_id");
    header("Location: admin_profile.php");
    exit;
}

// Handle profile picture upload
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['upload_picture'])){
    if(isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error']==0){
        $target_dir = "uploads/";
        if(!is_dir($target_dir)) mkdir($target_dir);
        $filename = $admin_id."_".basename($_FILES['profile_picture']['name']);
        $target_file = $target_dir.$filename;
        move_uploaded_file($_FILES['profile_picture']['tmp_name'],$target_file);
        $conn->query("UPDATE users SET profile_picture='$filename' WHERE id=$admin_id");
    }
    header("Location: admin_profile.php");
    exit;
}

// Fetch admin info
$res = $conn->query("SELECT username,email,profile_picture FROM users WHERE id=$admin_id");
$admin = $res->fetch_assoc();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="admin.php">Admin Dashboard</a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link active" href="admin_profile.php">Profile</a></li>
        <li class="nav-item"><a class="nav-link" href="manage_users.php">Manage Users</a></li>
        <li class="nav-item"><a class="nav-link" href="manage_reports.php">Reports</a></li>
        <li class="nav-item"><a class="nav-link text-danger" href="logout.php">Logout</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container mt-5">
    <h1 class="mb-4">Admin Profile</h1>

    <!-- Profile Picture -->
    <div class="card p-4 mb-4 shadow-sm text-center">
        <h3>Profile Picture</h3>
        <?php if(!empty($admin['profile_picture'])){ ?>
            <img src="uploads/<?php echo htmlspecialchars($admin['profile_picture']); ?>" 
                 class="rounded-circle mb-3" width="150" height="150" alt="Profile Picture">
        <?php } else { ?>
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
        <p><strong>Username:</strong> <?php echo htmlspecialchars($admin['username']); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($admin['email']); ?></p>

        <form method="POST" class="mt-3">
            <input type="hidden" name="update_profile" value="1">
            <div class="mb-2"><input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($admin['username']); ?>" required></div>
            <button type="submit" class="btn btn-success">Update Username</button>
        </form>
    </div>

    <!-- Update Email -->
    <div class="card p-4 mb-4 shadow-sm">
        <h3>Update Email</h3>
        <form method="POST">
            <input type="hidden" name="update_email" value="1">
            <div class="mb-2"><input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($admin['email']); ?>" required></div>
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
</div>

</body>
</html>
