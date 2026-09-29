// Load system settings
$sys_res = $conn->query("SELECT * FROM system_settings LIMIT 1");
$system_settings = $sys_res && $sys_res->num_rows > 0 ? $sys_res->fetch_assoc() : [
    'default_currency' => 'USD',
    'default_theme' => 'light',
    'force_currency' => 0,
    'force_theme' => 0
];

// Load user settings if logged in
$user_settings = [
    'currency' => $system_settings['default_currency'],
    'monthly_budget' => 0,
    'theme' => $system_settings['default_theme'],
    'notifications_enabled' => 1
];

if (isset($_SESSION['user_id'])) {
    $uid = (int)$_SESSION['user_id'];
    $res = $conn->query("SELECT currency, monthly_budget, theme, notifications_enabled 
                         FROM user_settings WHERE user_id=$uid");
    if ($res && $res->num_rows > 0) {
        $user_settings = $res->fetch_assoc();
    }
}

// Apply admin overrides
if ($system_settings['force_currency']) {
    $user_settings['currency'] = $system_settings['default_currency'];
}
if ($system_settings['force_theme']) {
    $user_settings['theme'] = $system_settings['default_theme'];
}
