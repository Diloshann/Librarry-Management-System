<?php
require_once '../includes/auth.php';
requireLibrarian();
updateOverdueStatus($conn);

$pageTitle = 'Dashboard';
$activePage = 'dashboard';

// Stats for librarian
$issuedToday   = $conn->query("SELECT COUNT(*) AS t FROM borrow_transactions WHERE DATE(created_at)=CURDATE()")->fetch_assoc()['t'];
$returnedToday = $conn->query("SELECT COUNT(*) AS t FROM borrow_transactions WHERE DATE(return_date)=CURDATE()")->fetch_assoc()['t'];
$pendingReturn = $conn->query("SELECT COUNT(*) AS t FROM borrow_transactions WHERE status IN ('borrowed','overdue')")->fetch_assoc()['t'];
$overdueCount  = $conn->query("SELECT COUNT(*) AS t FROM borrow_transactions WHERE status='overdue'")->fetch_assoc()['t'];
$fineToday     = $conn->query("SELECT SUM(paid_amount) AS t FROM fines WHERE DATE(paid_at)=CURDATE()")->fetch_assoc()['t'] ?? 0;
$totalBooks    = $conn->query("SELECT COUNT(*) AS t FROM books WHERE status='active'")->fetch_assoc()['t'];
$totalMembers  = $conn->query("SELECT COUNT(*) AS t FROM members WHERE status='active'")->fetch_assoc()['t'];

// Today's transactions
$todayTx = $conn->query("SELECT bt.*, m.full_name AS member_name, b.title AS book_title
    FROM borrow_transactions bt
    JOIN members m ON m.id=bt.member_id
    JOIN books b ON b.id=bt.book_id
    WHERE DATE(bt.created_at)=CURDATE() OR DATE(bt.return_date)=CURDATE()
    ORDER BY bt.created_at DESC LIMIT 10");

include '../includes/layout.php';
?>

<div class="stats-grid">
  <div class="stat-card orange"><div class="icon">📤</div><div class="num"><?= $issuedToday ?></div><div class="label">Issued Today</div></div>
  <div class="stat-card green"><div class="icon">📥</div><div class="num"><?= $returnedToday ?></div><div class="label">Returned Today</div></div>
  <div class="stat-card"><div class="icon">🔄</div><div class="num"><?= $pendingReturn ?></div><div class="label">Pending Returns</div></div>
  <div class="stat-card red"><div class="icon">⏰</div><div class="num"><?= $overdueCount ?></div><div class="label">Overdue</div></div>
  <div class="stat-card teal"><div class="icon">💰</div><div class="num">Rs.<?= number_format($fineToday) ?></div><div class="label">Fines Today</div></div>
  <div class="stat-card"><div class="icon">📚</div><div class="num"><?= $totalBooks ?></div><div class="label">Total Books</div></div>
</div>

<div style="display:flex;gap:12px;margin-bottom:20px;flex-wrap:wrap;">
  <a href="issue.php" class="btn btn-success" style="flex:1;min-width:140px;justify-content:center;padding:14px;">📤 Issue Book</a>
  <a href="return.php" class="btn btn-info" style="flex:1;min-width:140px;justify-content:center;padding:14px;">📥 Return Book</a>
  <a href="members.php" class="btn btn-primary" style="flex:1;min-width:140px;justify-content:center;padding:14px;">👥 Members</a>
  <a href="books.php" class="btn btn-warning" style="flex:1;min-width:140px;justify-content:center;padding:14px;">📚 Books</a>
</div>

<div class="card">
  <div class="card-header">
    <h3>📋 Today's Transactions</h3>
    <a href="transactions.php" class="btn btn-sm btn-outline-primary">View All</a>
  </div>
  <div class="card-body" style="padding:0;">
    <div class="table-wrap">
      <table>
        <thead><tr><th>Member</th><th>Book</th><th>Date</th><th>Due</th><th>Status</th></tr></thead>
        <tbody>
          <?php if ($todayTx->num_rows===0): ?>
            <tr><td colspan="5"><div class="empty-state">No transactions today</div></td></tr>
          <?php else: while ($t=$todayTx->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($t['member_name']) ?></td>
              <td><?= htmlspecialchars(substr($t['book_title'],0,35)) ?>...</td>
              <td><?= $t['borrow_date'] ?></td>
              <td><?= $t['due_date'] ?></td>
              <td>
                <?php $s=$t['status']; $cls=$s==='returned'?'success':($s==='overdue'?'danger':'warning'); ?>
                <span class="badge badge-<?= $cls ?>"><?= ucfirst($s) ?></span>
              </td>
            </tr>
          <?php endwhile; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include '../includes/layout_end.php'; ?>
