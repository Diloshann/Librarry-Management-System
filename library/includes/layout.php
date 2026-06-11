<?php
// includes/layout.php
// Usage: include this file with $pageTitle and $activePage set
$role = $_SESSION['role'] ?? '';
$userName = $_SESSION['user_name'] ?? 'User';

// Build menu based on role
$adminMenu = [
    ['icon'=>'🏠','label'=>'Dashboard','url'=>'dashboard.php','page'=>'dashboard'],
    ['icon'=>'📚','label'=>'Books','url'=>'books.php','page'=>'books'],
    ['icon'=>'✍️','label'=>'Authors','url'=>'authors.php','page'=>'authors'],
    ['icon'=>'🏷️','label'=>'Categories','url'=>'categories.php','page'=>'categories'],
    ['icon'=>'👥','label'=>'Members','url'=>'members.php','page'=>'members'],
    ['icon'=>'👤','label'=>'Librarians','url'=>'librarians.php','page'=>'librarians'],
    ['section'=>'Transactions'],
    ['icon'=>'📤','label'=>'Issue Book','url'=>'issue.php','page'=>'issue'],
    ['icon'=>'📥','label'=>'Return Book','url'=>'return.php','page'=>'return'],
    ['icon'=>'📋','label'=>'All Transactions','url'=>'transactions.php','page'=>'transactions'],
    ['icon'=>'⚠️','label'=>'Overdue Books','url'=>'overdue.php','page'=>'overdue'],
    ['section'=>'Finance'],
    ['icon'=>'💰','label'=>'Fines','url'=>'fines.php','page'=>'fines'],
    ['section'=>'System'],
    ['icon'=>'📢','label'=>'Announcements','url'=>'announcements.php','page'=>'announcements'],
    ['icon'=>'🔍','label'=>'Audit Log','url'=>'audit.php','page'=>'audit'],
    ['icon'=>'⚙️','label'=>'Settings','url'=>'settings.php','page'=>'settings'],
];

$librarianMenu = [
    ['icon'=>'🏠','label'=>'Dashboard','url'=>'dashboard.php','page'=>'dashboard'],
    ['icon'=>'📚','label'=>'Books','url'=>'books.php','page'=>'books'],
    ['icon'=>'👥','label'=>'Members','url'=>'members.php','page'=>'members'],
    ['section'=>'Transactions'],
    ['icon'=>'📤','label'=>'Issue Book','url'=>'issue.php','page'=>'issue'],
    ['icon'=>'📥','label'=>'Return Book','url'=>'return.php','page'=>'return'],
    ['icon'=>'📋','label'=>'Transactions','url'=>'transactions.php','page'=>'transactions'],
    ['icon'=>'⚠️','label'=>'Overdue Books','url'=>'overdue.php','page'=>'overdue'],
    ['icon'=>'💰','label'=>'Fines','url'=>'fines.php','page'=>'fines'],
];

$menu = ($role === 'admin') ? $adminMenu : $librarianMenu;
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
  <!-- SIDEBAR -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
      <h2>📚 ATI Library</h2>
      <p><?= ucfirst($role) ?> Panel</p>
    </div>
    <nav class="sidebar-menu">
      <?php foreach ($menu as $item): ?>
        <?php if (isset($item['section'])): ?>
          <div class="menu-section"><?= $item['section'] ?></div>
        <?php else: ?>
          <a href="<?= $item['url'] ?>" class="<?= ($activePage??'') === $item['page'] ? 'active' : '' ?>">
            <span><?= $item['icon'] ?></span> <?= $item['label'] ?>
          </a>
        <?php endif; ?>
      <?php endforeach; ?>
    </nav>
    <div class="sidebar-footer">
      <a href="../logout.php">🚪 Logout</a>
    </div>
  </aside>

  <!-- MAIN -->
  <div class="main-content">
    <div class="topbar">
      <div style="display:flex;align-items:center;gap:12px;">
        <button onclick="toggleSidebar()" style="background:none;border:none;cursor:pointer;font-size:20px;">☰</button>
        <h3><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></h3>
      </div>
      <div class="topbar-right">
        <span class="topbar-user">Welcome, <strong><?= htmlspecialchars($userName) ?></strong></span>
        <a href="../logout.php" class="btn btn-sm btn-secondary">Logout</a>
      </div>
    </div>
    <div class="page-body">
<?php
// Show flash
$flash = getFlash();
if ($flash): ?>
  <div class="alert alert-<?= $flash['type'] ?> alert-auto">
    <?= htmlspecialchars($flash['msg']) ?>
  </div>
<?php endif; ?>
