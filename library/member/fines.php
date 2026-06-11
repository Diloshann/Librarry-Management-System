<?php
require_once '../includes/auth.php';
requireMember();
updateOverdueStatus($conn);

$pageTitle = 'My Fines';
$activePage = 'fines';
$mid = $_SESSION['member_id'];

$fines = $conn->query("SELECT f.*, b.title AS book_title, bt.borrow_date, bt.due_date, bt.return_date
    FROM fines f
    JOIN borrow_transactions bt ON bt.id=f.transaction_id
    JOIN books b ON b.id=bt.book_id
    WHERE f.member_id=$mid
    ORDER BY f.created_at DESC");

$totalUnpaid = $conn->query("SELECT SUM(balance) AS t FROM fines WHERE member_id=$mid AND status='unpaid'")->fetch_assoc()['t'] ?? 0;
$totalPaid   = $conn->query("SELECT SUM(paid_amount) AS t FROM fines WHERE member_id=$mid AND status='paid'")->fetch_assoc()['t'] ?? 0;

include 'layout.php';
?>

<div class="stats-grid" style="grid-template-columns:repeat(3,1fr);max-width:600px;">
  <div class="stat-card red"><div class="icon">⚠️</div><div class="num">Rs.<?= number_format($totalUnpaid) ?></div><div class="label">Unpaid</div></div>
  <div class="stat-card green"><div class="icon">✅</div><div class="num">Rs.<?= number_format($totalPaid) ?></div><div class="label">Paid</div></div>
  <div class="stat-card orange"><div class="icon">💰</div><div class="num">Rs.<?= getSetting($conn,'fine_per_day') ?>/day</div><div class="label">Rate</div></div>
</div>

<?php if ($totalUnpaid > 0): ?>
  <div class="alert alert-danger">
    ⚠️ You have <strong>Rs.<?= number_format($totalUnpaid,2) ?></strong> in unpaid fines.
    Please visit the library to pay.
  </div>
<?php endif; ?>

<div class="card">
  <div class="card-header"><h3>💰 My Fines</h3></div>
  <div class="card-body" style="padding:0;">
    <div class="table-wrap">
      <table>
        <thead><tr><th>Book</th><th>Due Date</th><th>Return Date</th><th>Overdue Days</th><th>Total Fine</th><th>Paid</th><th>Balance</th><th>Status</th></tr></thead>
        <tbody>
          <?php if ($fines->num_rows===0): ?>
            <tr><td colspan="8"><div class="empty-state"><div class="icon">✅</div>No fines!</div></td></tr>
          <?php else: while ($f=$fines->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($f['book_title']) ?></td>
              <td><?= $f['due_date'] ?></td>
              <td><?= $f['return_date'] ?: '—' ?></td>
              <td style="color:var(--danger);"><?= $f['overdue_days'] ?></td>
              <td>Rs.<?= number_format($f['total_fine'],2) ?></td>
              <td style="color:var(--success);">Rs.<?= number_format($f['paid_amount'],2) ?></td>
              <td style="font-weight:700;color:<?= $f['balance']>0?'var(--danger)':'var(--success)' ?>">
                Rs.<?= number_format($f['balance'],2) ?>
              </td>
              <td>
                <?php $cls=$f['status']==='paid'?'success':($f['status']==='waived'?'info':'danger'); ?>
                <span class="badge badge-<?= $cls ?>"><?= ucfirst($f['status']) ?></span>
              </td>
            </tr>
          <?php endwhile; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php include 'layout_end.php'; ?>
