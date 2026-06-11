<?php
// member/layout.php
$memberName = $_SESSION['member_name'] ?? 'Student';

// Unread notifications count
$unreadCount = $conn->query("SELECT COUNT(*) AS c FROM notifications WHERE member_id={$_SESSION['member_id']} AND is_read=0")->fetch_assoc()['c'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle ?? 'ATI Library') ?> - ATI Library</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="layout">
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
      <h2>📚 ATI Library</h2>
      <p>Student Portal</p>
    </div>
    <nav class="sidebar-menu">
      <a href="dashboard.php" class="<?= ($activePage??'')==='dashboard'?'active':'' ?>">🏠 Dashboard</a>
      <a href="search.php"    class="<?= ($activePage??'')==='search'   ?'active':'' ?>">🔍 Search Books</a>
      <a href="borrowed.php"  class="<?= ($activePage??'')==='borrowed' ?'active':'' ?>">📤 My Borrowed Books</a>
      <a href="history.php"   class="<?= ($activePage??'')==='history'  ?'active':'' ?>">📋 Borrow History</a>
      <a href="fines.php"     class="<?= ($activePage??'')==='fines'    ?'active':'' ?>">💰 My Fines</a>
      <a href="notifications.php" class="<?= ($activePage??'')==='notifications'?'active':'' ?>">
        🔔 Notifications <?= $unreadCount > 0 ? "<span class='badge' style='background:var(--danger);color:#fff;padding:2px 6px;border-radius:10px;font-size:11px;'>$unreadCount</span>" : '' ?>
      </a>
      <a href="profile.php"   class="<?= ($activePage??'')==='profile'  ?'active':'' ?>">👤 My Profile</a>
    </nav>
    <div class="sidebar-footer">
      <a href="../logout.php">🚪 Logout</a>
    </div>
  </aside>

  <div class="main-content">
    <div class="topbar">
      <div style="display:flex;align-items:center;gap:12px;">
        <button onclick="toggleSidebar()" style="background:none;border:none;cursor:pointer;font-size:20px;">☰</button>
        <h3><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></h3>
      </div>
      <div class="topbar-right">
        <span class="topbar-user">Welcome, <strong><?= htmlspecialchars($memberName) ?></strong></span>
        <a href="../logout.php" class="btn btn-sm btn-secondary">Logout</a>
      </div>
    </div>
    <div class="page-body">
<?php
$flash = getFlash();
if ($flash): ?>
  <div class="alert alert-<?= $flash['type'] ?> alert-auto"><?= htmlspecialchars($flash['msg']) ?></div>
<?php endif; ?>
