<?php
require_once '../includes/auth.php';
requireMember();

$pageTitle = 'Borrow History';
$activePage = 'history';
$mid = $_SESSION['member_id'];

$history = $conn->query("SELECT bt.*, b.title, b.isbn, a.name AS author_name,
    f.total_fine, f.balance, f.status AS fine_status
    FROM borrow_transactions bt
    JOIN books b ON b.id=bt.book_id
    LEFT JOIN authors a ON a.id=b.author_id
    LEFT JOIN fines f ON f.transaction_id=bt.id
    WHERE bt.member_id=$mid
    ORDER BY bt.created_at DESC");

include 'layout.php';
?>
<div class="card">
  <div class="card-header"><h3>📋 My Borrow History (<?= $history->num_rows ?>)</h3></div>
  <div class="card-body" style="padding:0;">
    <div class="table-wrap">
      <table>
        <thead><tr><th>#</th><th>Book</th><th>Author</th><th>Borrowed</th><th>Due</th><th>Returned</th><th>Status</th><th>Fine</th></tr></thead>
        <tbody>
          <?php if ($history->num_rows===0): ?>
            <tr><td colspan="8"><div class="empty-state">No history yet</div></td></tr>
          <?php else: $i=1; while ($h=$history->fetch_assoc()): ?>
            <tr>
              <td><?= $i++ ?></td>
              <td><strong><?= htmlspecialchars($h['title']) ?></strong></td>
              <td><?= htmlspecialchars($h['author_name']??'—') ?></td>
              <td><?= $h['borrow_date'] ?></td>
              <td><?= $h['due_date'] ?></td>
              <td><?= $h['return_date'] ?: '—' ?></td>
              <td>
                <?php $s=$h['status']; $cls=$s==='returned'?'success':($s==='overdue'?'danger':'warning'); ?>
                <span class="badge badge-<?= $cls ?>"><?= ucfirst($s) ?></span>
              </td>
              <td>
                <?php if ($h['total_fine']): ?>
                  <span style="color:<?= $h['fine_status']==='paid'?'var(--success)':'var(--danger)' ?>;font-weight:600;">
                    Rs.<?= number_format($h['total_fine'],2) ?>
                    <br><small>(<?= $h['fine_status'] ?>)</small>
                  </span>
                <?php else: echo '—'; endif; ?>
              </td>
            </tr>
          <?php endwhile; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php include 'layout_end.php'; ?>
