<?php
require_once '../includes/auth.php';
requireLibrarian();
updateOverdueStatus($conn);
$pageTitle = 'Overdue Books'; $activePage = 'overdue';

$overdue = $conn->query("SELECT bt.*, m.full_name AS member_name, m.student_id, m.email, m.phone,
    b.title AS book_title, b.isbn,
    DATEDIFF(CURDATE(), bt.due_date) AS days_overdue,
    DATEDIFF(CURDATE(), bt.due_date) * (SELECT setting_value FROM settings WHERE setting_key='fine_per_day') AS estimated_fine
    FROM borrow_transactions bt
    JOIN members m ON m.id=bt.member_id
    JOIN books b ON b.id=bt.book_id
    WHERE bt.status='overdue'
    ORDER BY days_overdue DESC");

include '../includes/layout.php';
?>
<div class="card">
  <div class="card-header">
    <h3>⏰ Overdue Books (<?= $overdue->num_rows ?>)</h3>
  </div>
  <div class="card-body" style="padding:0;">
    <div class="table-wrap">
      <table>
        <thead><tr><th>#</th><th>Member</th><th>Book</th><th>Borrow Date</th><th>Due Date</th><th>Days Overdue</th><th>Est. Fine</th><th>Actions</th></tr></thead>
        <tbody>
          <?php if ($overdue->num_rows===0): ?>
            <tr><td colspan="8"><div class="empty-state"><div class="icon">✅</div>No overdue books!</div></td></tr>
          <?php else: $i=1; while ($r=$overdue->fetch_assoc()): ?>
            <tr>
              <td><?= $i++ ?></td>
              <td>
                <strong><?= htmlspecialchars($r['member_name']) ?></strong><br>
                <small><?= htmlspecialchars($r['student_id']) ?></small><br>
                <small style="color:#888;"><?= htmlspecialchars($r['phone']) ?></small>
              </td>
              <td><?= htmlspecialchars($r['book_title']) ?><br><small><?= $r['isbn'] ?></small></td>
              <td><?= $r['borrow_date'] ?></td>
              <td style="color:var(--danger);font-weight:600;"><?= $r['due_date'] ?></td>
              <td><span class="badge badge-danger"><?= $r['days_overdue'] ?> days</span></td>
              <td style="color:var(--danger);font-weight:700;">Rs.<?= number_format($r['estimated_fine'],2) ?></td>
              <td><a href="return.php" class="btn btn-sm btn-info">📥 Return</a></td>
            </tr>
          <?php endwhile; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php include '../includes/layout_end.php'; ?>
