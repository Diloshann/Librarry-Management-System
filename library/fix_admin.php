<?php
// ============================================================
// fix_admin.php — Run this ONCE to fix the admin password
// Place in: C:\xampp\htdocs\library\fix_admin.php
// Then open: http://localhost/library/fix_admin.php
// DELETE this file after running it!
// ============================================================

require_once 'includes/config.php';

$newPassword = 'admin123';
$hash = password_hash($newPassword, PASSWORD_DEFAULT);

// Check if admin user exists
$result = $conn->query("SELECT id, username FROM users WHERE username = 'admin'");

if ($result->num_rows === 0) {
    // Admin doesn't exist — create it
    $stmt = $conn->prepare("INSERT INTO users (role_id, username, password, full_name, email, status) VALUES (1, 'admin', ?, 'Administrator', 'admin@ati.lk', 'active')");
    $stmt->bind_param("s", $hash);
    $stmt->execute();
    $stmt->close();
    $action = "Admin account CREATED";
} else {
    // Admin exists — update password
    $stmt = $conn->prepare("UPDATE users SET password = ?, status = 'active' WHERE username = 'admin'");
    $stmt->bind_param("s", $hash);
    $stmt->execute();
    $stmt->close();
    $action = "Admin password UPDATED";
}

// Verify it works
$verify = $conn->query("SELECT password FROM users WHERE username = 'admin'")->fetch_assoc();
$works  = password_verify($newPassword, $verify['password']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Fix - ATI Library</title>
  <style>
    body { font-family: Arial, sans-serif; background: #f0f2f5; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
    .box { background: #fff; border-radius: 12px; padding: 36px; max-width: 460px; width: 90%; box-shadow: 0 8px 30px rgba(0,0,0,0.12); text-align: center; }
    .icon { font-size: 56px; margin-bottom: 12px; }
    h2 { color: #1a3c5e; margin-bottom: 8px; }
    .result { padding: 16px; border-radius: 8px; margin: 20px 0; font-size: 15px; font-weight: 600; }
    .ok  { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
    .err { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
    table { width: 100%; border-collapse: collapse; margin: 16px 0; text-align: left; }
    td { padding: 9px 12px; font-size: 14px; border-bottom: 1px solid #eee; }
    td:first-child { color: #888; width: 40%; }
    td strong { color: #1a3c5e; }
    .btn { display: inline-block; padding: 12px 28px; background: #1a3c5e; color: #fff; border-radius: 7px; text-decoration: none; font-weight: 700; font-size: 15px; margin-top: 8px; }
    .btn:hover { background: #2a5f94; }
    .warn { margin-top: 16px; font-size: 12px; color: #dc3545; background: #fff3cd; padding: 10px; border-radius: 6px; }
  </style>
</head>
<body>
<div class="box">
  <div class="icon"><?= $works ? '✅' : '❌' ?></div>
  <h2>ATI Library — Admin Fix</h2>

  <?php if ($works): ?>
    <div class="result ok">✔ <?= $action ?> successfully!</div>
    <table>
      <tr><td>Username</td><td><strong>admin</strong></td></tr>
      <tr><td>Password</td><td><strong>admin123</strong></td></tr>
      <tr><td>Hash verify</td><td><strong style="color:#28a745;">PASSED ✓</strong></td></tr>
    </table>
    <a href="index.php" class="btn">→ Go to Login</a>
    <div class="warn">⚠️ Important: Delete <strong>fix_admin.php</strong> from your htdocs/library/ folder after logging in!</div>
  <?php else: ?>
    <div class="result err">✘ Something went wrong. Hash verification failed.</div>
    <p style="color:#555;font-size:13px;">Check that your database connection settings in <code>includes/config.php</code> are correct and that the <strong>ati_library</strong> database exists.</p>
  <?php endif; ?>
</div>
</body>
</html>
