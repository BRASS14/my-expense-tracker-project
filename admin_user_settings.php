<?php
include 'configures_system.php';

// Only allow admins
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: user_login.php");
    exit();
}

// Handle update
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['update_settings'])){
    $uid = (int)$_POST['user_id'];
    $currency = sanitize($_POST['currency']);
    $budget = floatval($_POST['monthly_budget']);
    $theme = sanitize($_POST['theme']);
    $notifications = isset($_POST['notifications_enabled']) ? 1 : 0;

    $conn->query("INSERT INTO user_settings (user_id,currency,monthly_budget,theme,notifications_enabled)
                  VALUES ($uid,'$currency',$budget,'$theme',$notifications)
                  ON DUPLICATE KEY UPDATE currency='$currency', monthly_budget=$budget, theme='$theme', notifications_enabled=$notifications");

    header("Location: admin_user_settings.php?success=1");
    exit;
}

// Fetch all users with settings
$res = $conn->query("SELECT u.id, u.username, u.email, 
                            s.currency, s.monthly_budget, s.theme, s.notifications_enabled
                     FROM users u
                     LEFT JOIN user_settings s ON u.id = s.user_id
                     ORDER BY u.id ASC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Configure User Settings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <h1 class="mb-4">Configure User Settings (Admin)</h1>

    <?php if(isset($_GET['success'])){ ?>
        <div class="alert alert-success">Settings updated successfully!</div>
    <?php } ?>

    <!-- Table of all users and their settings -->
    <div class="card p-4 shadow-sm">
        <h3>All User Settings</h3>
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Currency</th>
                    <th>Monthly Budget</th>
                    <th>Theme</th>
                    <th>Notifications</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $res->fetch_assoc()){ ?>
                <tr>
                    <form method="POST">
                        <td><?php echo $row['id']; ?><input type="hidden" name="user_id" value="<?php echo $row['id']; ?>"></td>
                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td><input type="text" name="currency" class="form-control form-control-sm" value="<?php echo htmlspecialchars($row['currency']); ?>"></td>
                        <td><input type="number" step="0.01" name="monthly_budget" class="form-control form-control-sm" value="<?php echo htmlspecialchars($row['monthly_budget']); ?>"></td>
                        <td>
                            <select name="theme" class="form-select form-select-sm">
                                <option value="light" <?php if($row['theme']=="light") echo "selected"; ?>>Light</option>
                                <option value="dark" <?php if($row['theme']=="dark") echo "selected"; ?>>Dark</option>
                            </select>
                        </td>
                        <td>
                            <input type="checkbox" name="notifications_enabled" <?php if($row['notifications_enabled']) echo "checked"; ?>>
                        </td>
                        <td>
                            <button type="submit" name="update_settings" class="btn btn-sm btn-primary">Save</button>
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
