<?php
require_once '../includes/auth.php';
requireAdmin();
updateOverdueStatus($conn);

$pageTitle = 'Dashboard';
$activePage = 'dashboard';

// Stats
$totalBooks      = $conn->query("SELECT SUM(total_copies) AS t FROM books")->fetch_assoc()['t'] ?? 0;
$availBooks      = $conn->query("SELECT SUM(available_copies) AS t FROM books")->fetch_assoc()['t'] ?? 0;
$borrowedBooks   = $conn->query("SELECT SUM(borrowed_copies) AS t FROM books")->fetch_assoc()['t'] ?? 0;
$totalMembers    = $conn->query("SELECT COUNT(*) AS t FROM members")->fetch_assoc()['t'];
$activeMembers   = $conn->query("SELECT COUNT(*) AS t FROM members WHERE status='active'")->fetch_assoc()['t'];
$overdueBooks    = $conn->query("SELECT COUNT(*) AS t FROM borrow_transactions WHERE status='overdue'")->fetch_assoc()['t'];
$finesCollected  = $conn->query("SELECT SUM(paid_amount) AS t FROM fines WHERE status='paid'")->fetch_assoc()['t'] ?? 0;
$todayIssued     = $conn->query("SELECT COUNT(*) AS t FROM borrow_transactions WHERE DATE(created_at)=CURDATE()")->fetch_assoc()['t'];
$todayReturned   = $conn->query("SELECT COUNT(*) AS t FROM borrow_transactions WHERE DATE(return_date)=CURDATE()")->fetch_assoc()['t'];
$unpaidFines     = $conn->query("SELECT SUM(balance) AS t FROM fines WHERE status='unpaid'")->fetch_assoc()['t'] ?? 0;

// Recent transactions
$recentTx = $conn->query("SELECT bt.*, m.full_name AS member_name, b.title AS book_title
    FROM borrow_transactions bt
    JOIN members m ON m.id = bt.member_id
    JOIN books b ON b.id = bt.book_id
    ORDER BY bt.created_at DESC LIMIT 8");

// Recent announcements
$announcements = $conn->query("SELECT * FROM announcements WHERE is_active=1 ORDER BY created_at DESC LIMIT 3");

include '../includes/layout.php';
?>

<div class="stats-grid">
  <div class="stat-card">
    <div class="icon">📚</div>
    <div class="num"><?= number_format($totalBooks) ?></div>
    <div class="label">Total Books</div>
  </div>
  <div class="stat-card green">
    <div class="icon">✅</div>
    <div class="num"><?= number_format($availBooks) ?></div>
    <div class="label">Available</div>
  </div>
  <div class="stat-card orange">
    <div class="icon">📤</div>
    <div class="num"><?= number_format($borrowedBooks) ?></div>
    <div class="label">Borrowed</div>
  </div>
  <div class="stat-card">
    <div class="icon">👥</div>
    <div class="num"><?= number_format($totalMembers) ?></div>
    <div class="label">Total Members</div>
  </div>
  <div class="stat-card green">
    <div class="icon">🟢</div>
    <div class="num"><?= number_format($activeMembers) ?></div>
    <div class="label">Active Members</div>
  </div>
  <div class="stat-card red">
    <div class="icon">⏰</div>
    <div class="num"><?= number_format($overdueBooks) ?></div>
    <div class="label">Overdue</div>
  </div>
  <div class="stat-card teal">
    <div class="icon">💰</div>
    <div class="num">Rs.<?= number_format($finesCollected) ?></div>
    <div class="label">Fines Collected</div>
  </div>
  <div class="stat-card orange">
    <div class="icon">⚠️</div>
    <div class="num">Rs.<?= number_format($unpaidFines) ?></div>
    <div class="label">Unpaid Fines</div>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 320px;gap:20px;flex-wrap:wrap;">
  <!-- Recent Transactions -->
  <div class="card">
    <div class="card-header">
      <h3>📋 Recent Transactions</h3>
      <a href="transactions.php" class="btn btn-sm btn-outline-primary">View All</a>
    </div>
    <div class="card-body" style="padding:0;">
      <div class="table-wrap">
        <table>
          <thead>
            <tr><th>Member</th><th>Book</th><th>Borrow Date</th><th>Due Date</th><th>Status</th></tr>
          </thead>
          <tbody>
            <?php if ($recentTx->num_rows === 0): ?>
              <tr><td colspan="5" class="empty-state">No transactions yet</td></tr>
            <?php else: while ($tx = $recentTx->fetch_assoc()): ?>
              <tr>
                <td><?= htmlspecialchars($tx['member_name']) ?></td>
                <td><?= htmlspecialchars(substr($tx['book_title'],0,30)) ?>...</td>
                <td><?= $tx['borrow_date'] ?></td>
                <td><?= $tx['due_date'] ?></td>
                <td>
                  <?php $s = $tx['status'];
                  $cls = $s==='returned'?'success':($s==='overdue'?'danger':($s==='renewed'?'info':'warning')); ?>
                  <span class="badge badge-<?= $cls ?>"><?= ucfirst($s) ?></span>
                </td>
              </tr>
            <?php endwhile; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Side Panel -->
  <div>
    <div class="card">
      <div class="card-header"><h3>📊 Today's Activity</h3></div>
      <div class="card-body">
        <div style="display:flex;justify-content:space-between;margin-bottom:12px;">
          <span>📤 Issued Today</span><strong><?= $todayIssued ?></strong>
        </div>
        <div style="display:flex;justify-content:space-between;">
          <span>📥 Returned Today</span><strong><?= $todayReturned ?></strong>
        </div>
      </div>
    </div>
    <div class="card">
      <div class="card-header">
        <h3>📢 Announcements</h3>
        <a href="announcements.php" class="btn btn-sm btn-outline-primary">Manage</a>
      </div>
      <div class="card-body">
        <?php if ($announcements->num_rows === 0): ?>
          <p style="color:#aaa;font-size:13px;">No announcements</p>
        <?php else: while ($a = $announcements->fetch_assoc()): ?>
          <div style="margin-bottom:10px;padding-bottom:10px;border-bottom:1px solid #eee;">
            <strong style="font-size:13px;"><?= htmlspecialchars($a['title']) ?></strong>
            <p style="font-size:12px;color:#888;margin-top:2px;"><?= htmlspecialchars(substr($a['content'],0,80)) ?>...</p>
          </div>
        <?php endwhile; endif; ?>
      </div>
    </div>
    <div class="flex-row">
      <a href="issue.php" class="btn btn-success" style="flex:1;justify-content:center;">📤 Issue Book</a>
      <a href="return.php" class="btn btn-info" style="flex:1;justify-content:center;">📥 Return</a>
    </div>
  </div>
</div>

<?php include '../includes/layout_end.php'; ?>
