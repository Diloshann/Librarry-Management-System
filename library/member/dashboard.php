<?php
require_once '../includes/auth.php';
requireMember();
updateOverdueStatus($conn);

$pageTitle = 'My Dashboard';
$activePage = 'dashboard';

$mid = $_SESSION['member_id'];

// Stats
$activeBorrows = $conn->query("SELECT COUNT(*) AS c FROM borrow_transactions WHERE member_id=$mid AND status IN ('borrowed','overdue')")->fetch_assoc()['c'];
$totalBorrowed = $conn->query("SELECT COUNT(*) AS c FROM borrow_transactions WHERE member_id=$mid")->fetch_assoc()['c'];
$unpaidFines   = $conn->query("SELECT SUM(balance) AS t FROM fines WHERE member_id=$mid AND status='unpaid'")->fetch_assoc()['t'] ?? 0;
$unreadNotifs  = $conn->query("SELECT COUNT(*) AS c FROM notifications WHERE member_id=$mid AND is_read=0")->fetch_assoc()['c'];

// Currently borrowed
$currentBooks = $conn->query("SELECT bt.*, b.title, b.isbn, b.shelf_location,
    DATEDIFF(bt.due_date, CURDATE()) AS days_left
    FROM borrow_transactions bt
    JOIN books b ON b.id=bt.book_id
    WHERE bt.member_id=$mid AND bt.status IN ('borrowed','overdue')
    ORDER BY bt.due_date ASC");

// Announcements
$anns = $conn->query("SELECT * FROM announcements WHERE is_active=1 ORDER BY created_at DESC LIMIT 3");

include 'layout.php';
?>

<div class="stats-grid" style="grid-template-columns:repeat(4,1fr);">
  <div class="stat-card orange"><div class="icon">📚</div><div class="num"><?= $activeBorrows ?></div><div class="label">Currently Borrowed</div></div>
  <div class="stat-card"><div class="icon">📋</div><div class="num"><?= $totalBorrowed ?></div><div class="label">Total Borrowed</div></div>
  <div class="stat-card red"><div class="icon">💰</div><div class="num">Rs.<?= number_format($unpaidFines) ?></div><div class="label">Unpaid Fines</div></div>
  <div class="stat-card teal"><div class="icon">🔔</div><div class="num"><?= $unreadNotifs ?></div><div class="label">Notifications</div></div>
</div>

<div style="display:grid;grid-template-columns:1fr 300px;gap:20px;">
  <!-- Currently Borrowed -->
  <div class="card">
    <div class="card-header">
      <h3>📤 Currently Borrowed Books</h3>
      <a href="borrowed.php" class="btn btn-sm btn-outline-primary">View All</a>
    </div>
    <div class="card-body" style="padding:0;">
      <table>
        <thead><tr><th>Book Title</th><th>ISBN</th><th>Due Date</th><th>Days Left</th><th>Status</th></tr></thead>
        <tbody>
          <?php if ($currentBooks->num_rows===0): ?>
            <tr><td colspan="5"><div class="empty-state"><div class="icon">📚</div>No active borrows</div></td></tr>
          <?php else: while ($b=$currentBooks->fetch_assoc()): ?>
            <tr>
              <td><strong><?= htmlspecialchars($b['title']) ?></strong></td>
              <td><?= htmlspecialchars($b['isbn']) ?></td>
              <td style="<?= $b['days_left']<0?'color:var(--danger);font-weight:700;':($b['days_left']<=3?'color:var(--warning);font-weight:600;':'') ?>">
                <?= $b['due_date'] ?>
              </td>
              <td>
                <?php if ($b['days_left'] < 0): ?>
                  <span class="badge badge-danger"><?= abs($b['days_left']) ?> days overdue</span>
                <?php elseif ($b['days_left'] <= 3): ?>
                  <span class="badge badge-warning"><?= $b['days_left'] ?> days</span>
                <?php else: ?>
                  <span class="badge badge-success"><?= $b['days_left'] ?> days</span>
                <?php endif; ?>
              </td>
              <td>
                <?php $s=$b['status']; $cls=$s==='overdue'?'danger':'warning'; ?>
                <span class="badge badge-<?= $cls ?>"><?= ucfirst($s) ?></span>
              </td>
            </tr>
          <?php endwhile; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Side -->
  <div>
    <div class="card">
      <div class="card-header"><h3>⚡ Quick Actions</h3></div>
      <div class="card-body">
        <a href="search.php" class="btn btn-primary" style="width:100%;justify-content:center;margin-bottom:8px;">🔍 Search Books</a>
        <a href="fines.php" class="btn btn-<?= $unpaidFines>0?'danger':'secondary' ?>" style="width:100%;justify-content:center;margin-bottom:8px;">
          💰 My Fines <?= $unpaidFines>0?"(Rs.".number_format($unpaidFines).")":'' ?>
        </a>
        <a href="notifications.php" class="btn btn-info" style="width:100%;justify-content:center;">
          🔔 Notifications <?= $unreadNotifs>0?"($unreadNotifs)":'' ?>
        </a>
      </div>
    </div>

    <?php if ($anns->num_rows > 0): ?>
    <div class="card">
      <div class="card-header"><h3>📢 Announcements</h3></div>
      <div class="card-body">
        <?php while ($a=$anns->fetch_assoc()): ?>
          <div style="margin-bottom:12px;padding-bottom:12px;border-bottom:1px solid #eee;">
            <strong style="font-size:13px;"><?= htmlspecialchars($a['title']) ?></strong>
            <p style="font-size:12px;color:#666;margin-top:4px;"><?= htmlspecialchars(substr($a['content'],0,100)) ?>...</p>
            <small style="color:#aaa;"><?= date('d M Y',strtotime($a['created_at'])) ?></small>
          </div>
        <?php endwhile; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php include 'layout_end.php'; ?>
