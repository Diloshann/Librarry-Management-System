<?php
require_once 'includes/config.php';
// If already logged in, redirect
if (isset($_SESSION['user_id'])) redirect($_SESSION['role'] . '/dashboard.php');
if (isset($_SESSION['member_id'])) redirect('member/dashboard.php');

$error = '';
$msg = clean($_GET['msg'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = clean($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? '';

    if ($role === 'member') {
        // Member login
        $stmt = $conn->prepare("SELECT * FROM members WHERE username = ? AND status = 'active'");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['member_id'] = $user['id'];
            $_SESSION['member_name'] = $user['full_name'];
            $_SESSION['role'] = 'member';
            logAction($conn, 'member', $user['id'], 'LOGIN', 'Member logged in');
            redirect('member/dashboard.php');
        } else {
            $error = 'Invalid username or password.';
        }
    } else {
        // Admin / Librarian login
        $stmt = $conn->prepare("SELECT u.*, r.name AS role_name FROM users u
            JOIN roles r ON r.id = u.role_id
            WHERE u.username = ? AND u.status = 'active'");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role_name'];
            logAction($conn, $user['role_name'], $user['id'], 'LOGIN', 'Logged in');
            redirect($user['role_name'] . '/dashboard.php');
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ATI Library Management System - Login</title>
<link rel="stylesheet" href="assets/css/style.css">
<style>
.role-tabs { display: flex; gap: 0; margin-bottom: 24px; border-radius: 8px; overflow: hidden; border: 1px solid #ddd; }
.role-tab { flex: 1; padding: 10px; text-align: center; cursor: pointer; font-size: 13px; font-weight: 600; background: #f5f5f5; color: #666; border: none; transition: all 0.2s; }
.role-tab.active { background: var(--primary); color: #fff; }
</style>
</head>
<body>
<div class="login-page">
  <div class="login-box">
    <div class="login-logo">
      <div style="font-size:44px; margin-bottom:8px;">📚</div>
      <h1>ATI Library</h1>
      <p>Advanced Technological Institute</p>
    </div>

    <?php if ($msg): ?>
      <div class="alert alert-warning"><?= $msg ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <div class="role-tabs">
      <button class="role-tab active" onclick="setRole('admin', this)">🔑 Admin</button>
      <button class="role-tab" onclick="setRole('librarian', this)">📖 Librarian</button>
      <button class="role-tab" onclick="setRole('member', this)">🎓 Member</button>
    </div>

    <form method="POST">
      <input type="hidden" name="role" id="roleInput" value="admin">
      <div class="form-group">
        <label>Username</label>
        <input type="text" name="username" class="form-control" placeholder="Enter username" required autofocus>
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" class="form-control" placeholder="Enter password" required>
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:11px">
        Login &rarr;
      </button>
    </form>

    <p style="text-align:center;margin-top:14px;font-size:12px;color:#aaa;">
      Not a member? <a href="member/register.php" style="color:var(--primary);">Register here</a>
    </p>
    <p style="text-align:center;margin-top:6px;font-size:11px;color:#ccc;">
      Default admin: <strong>admin</strong> / <strong>admin123</strong>
    </p>
  </div>
</div>
<script>
function setRole(role, el) {
    document.getElementById('roleInput').value = role;
    document.querySelectorAll('.role-tab').forEach(t => t.classList.remove('active'));
    el.classList.add('active');
}
</script>
</body>
</html>
