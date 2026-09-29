<?php
include 'configures_system.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: user_login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD']==='POST'){
    $currency = sanitize($_POST['default_currency']);
    $theme = sanitize($_POST['default_theme']);
    $force_currency = isset($_POST['force_currency']) ? 1 : 0;
    $force_theme = isset($_POST['force_theme']) ? 1 : 0;

    $conn->query("UPDATE system_settings 
                  SET default_currency='$currency', default_theme='$theme', 
                      force_currency=$force_currency, force_theme=$force_theme 
                  WHERE id=1");

    header("Location: admin_system_settings.php?success=1");
    exit;
}

$res = $conn->query("SELECT * FROM system_settings LIMIT 1");
$settings = $res->fetch_assoc();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin System Settings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <h1>System Settings (Admin)</h1>
    <?php if(isset($_GET['success'])){ ?><div class="alert alert-success">Settings updated!</div><?php } ?>
    <form method="POST" class="card p-4 shadow-sm">
        <div class="mb-3">
            <label class="form-label">Default Currency</label>
            <input type="text" name="default_currency" class="form-control" value="<?php echo htmlspecialchars($settings['default_currency']); ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Default Theme</label>
            <select name="default_theme" class="form-select">
                <option value="light" <?php if($settings['default_theme']=='light') echo 'selected'; ?>>Light</option>
                <option value="dark" <?php if($settings['default_theme']=='dark') echo 'selected'; ?>>Dark</option>
            </select>
        </div>
        <div class="form-check mb-2">
            <input type="checkbox" name="force_currency" class="form-check-input" <?php if($settings['force_currency']) echo 'checked'; ?>>
            <label class="form-check-label">Force all users to use default currency</label>
        </div>
        <div class="form-check mb-2">
            <input type="checkbox" name="force_theme" class="form-check-input" <?php if($settings['force_theme']) echo 'checked'; ?>>
            <label class="form-check-label">Force all users to use default theme</label>
        </div>
        <button type="submit" class="btn btn-primary">Save Settings</button>
    </form>
</div>
</body>
</html>
