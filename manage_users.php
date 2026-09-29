<?php
session_start();
$conn = new mysqli("localhost","root","","expense_tracker");

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: user_login.php");
    exit();
}

// Handle add user
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_user'])){
    $username = $conn->real_escape_string($_POST['username']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $conn->real_escape_string($_POST['role']);

    $conn->query("INSERT INTO users (username,email,password,role) VALUES ('$username','$email','$password','$role')");
    $new_id = $conn->insert_id;
    $conn->query("INSERT INTO user_biodata (user_id, fullname, phone, occupation) VALUES ($new_id,'','','')");
    header("Location: manage_users.php?added=1");
    exit;
}

// Handle edit user (basic info)
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['edit_user'])){
    $user_id = (int)$_POST['user_id'];
    $username = $conn->real_escape_string($_POST['username']);
    $email = $conn->real_escape_string($_POST['email']);
    $role = $conn->real_escape_string($_POST['role']);

    $conn->query("UPDATE users SET username='$username', email='$email', role='$role' WHERE id=$user_id");
    header("Location: manage_users.php?edited=1");
    exit;
}

// Handle delete user
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    $conn->query("DELETE FROM users WHERE id=$delete_id");
    $conn->query("DELETE FROM user_biodata WHERE user_id=$delete_id");
    header("Location: manage_users.php?deleted=1");
    exit;
}

// Fetch all users
$res = $conn->query("SELECT u.id, u.username, u.email, u.role, b.fullname, b.phone, b.occupation 
                     FROM users u 
                     LEFT JOIN user_biodata b ON u.id = b.user_id 
                     ORDER BY u.id DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Users</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="admin.php">Admin Dashboard</a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav ms-auto">
       <li class="nav-item"><a class="nav-link active" href="manage_users.php">Manage Users</a></li>
        <li class="nav-item"><a class="nav-link" href="manage_reports.php">Users Reports</a></li>
        <li class="nav-item"><a class="nav-link text-danger" href="logout.php">Logout</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container mt-5">
    <h1 class="mb-4">Manage Users</h1>

    <?php if(isset($_GET['added'])){ ?><div class="alert alert-success">New user added!</div><?php } ?>
    <?php if(isset($_GET['edited'])){ ?><div class="alert alert-info">User updated!</div><?php } ?>
    <?php if(isset($_GET['deleted'])){ ?><div class="alert alert-danger">User deleted!</div><?php } ?>

    <!-- Add User Form -->
    <div class="card p-4 mb-4 shadow-sm">
        <h3>Add New User</h3>
        <form method="POST">
            <input type="hidden" name="add_user" value="1">
            <div class="mb-2"><input type="text" name="username" class="form-control" placeholder="Username" required></div>
            <div class="mb-2"><input type="email" name="email" class="form-control" placeholder="Email" required></div>
            <div class="mb-2"><input type="password" name="password" class="form-control" placeholder="Password" required></div>
            <div class="mb-2">
                <select name="role" class="form-select">
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Add User</button>
        </form>
    </div>

    <!-- User Table -->
    <div class="card p-4 shadow-sm">
        <h3>All Users</h3>
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Full Name</th>
                    <th>Phone</th>
                    <th>Occupation</th>
                    <th>Role</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $res->fetch_assoc()){ ?>
                <tr>
                    <form method="POST">
                        <td><?php echo $row['id']; ?><input type="hidden" name="user_id" value="<?php echo $row['id']; ?>"></td>
                        <td><input type="text" name="username" class="form-control form-control-sm" value="<?php echo htmlspecialchars($row['username']); ?>"></td>
                        <td><input type="email" name="email" class="form-control form-control-sm" value="<?php echo htmlspecialchars($row['email']); ?>"></td>
                        <td><?php echo htmlspecialchars($row['fullname']); ?></td>
                        <td><?php echo htmlspecialchars($row['phone']); ?></td>
                        <td><?php echo htmlspecialchars($row['occupation']); ?></td>
                        <td>
                            <select name="role" class="form-select form-select-sm">
                                <option value="user" <?php if($row['role']=="user") echo "selected"; ?>>User</option>
                                <option value="admin" <?php if($row['role']=="admin") echo "selected"; ?>>Admin</option>
                            </select>
                        </td>
                        <td>
                            <button type="submit" name="edit_user" class="btn btn-sm btn-info">Save</button>
                            <a href="manage_users.php?delete=<?php echo $row['id']; ?>" 
                               class="btn btn-sm btn-danger"
                               onclick="return confirm('Delete this user?');">Delete</a>
                        </td>
                    </form>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
