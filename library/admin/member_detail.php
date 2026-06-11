<?php
require_once '../includes/auth.php';
requireLibrarian();
$id = (int)($_GET['id'] ?? 0);
$member = $conn->query("SELECT * FROM members WHERE id=$id")->fetch_assoc();
if (!$member) { setFlash('danger','Member not found.'); redirect('members.php'); }

$pageTitle = 'Member: ' . $member['full_name'];
$activePage = 'members';

$history = $conn->query("SELECT bt.*, b.title AS book_title, b.isbn,
    f.total_fine, f.balance, f.status AS fine_status
    FROM borrow_transactions bt
    JOIN books b ON b.id=bt.book_id
    LEFT JOIN fines f ON f.transaction_id=bt.id
    WHERE bt.member_id=$id ORDER BY bt.created_at DESC");

include '../includes/layout.php';
?>
<a href="members.php" class="btn btn-secondary" style="margin-bottom:16px;">← Back</a>

<div style="display:grid;grid-template-columns:280px 1fr;gap:20px;">
  <div class="card">
    <div class="card-body" style="text-align:center;">
      <div style="font-size:64px;margin-bottom:8px;">👤</div>
      <h3><?= htmlspecialchars($member['full_name']) ?></h3>
      <p style="color:#888;font-size:13px;"><?= htmlspecialchars($member['student_id']) ?></p>
      <span class="badge badge-<?= $member['status']==='active'?'success':($member['status']==='suspended'?'warning':'secondary') ?>">
        <?= ucfirst($member['status']) ?>
      </span>
      <hr class="divider">
      <table style="width:100%;text-align:left;font-size:13px;">
        <tr><td style="color:#888;padding:4px 0;">Course</td><td><?= htmlspecialchars($member['course']) ?></td></tr>
        <tr><td style="color:#888;padding:4px 0;">Batch</td><td><?= htmlspecialchars($member['batch']) ?></td></tr>
        <tr><td style="color:#888;padding:4px 0;">NIC</td><td><?= htmlspecialchars($member['nic']) ?></td></tr>
        <tr><td style="color:#888;padding:4px 0;">Email</td><td><?= htmlspecialchars($member['email']) ?></td></tr>
        <tr><td style="color:#888;padding:4px 0;">Phone</td><td><?= htmlspecialchars($member['phone']) ?></td></tr>
        <tr><td style="color:#888;padding:4px 0;">Joined</td><td><?= date('d M Y',strtotime($member['created_at'])) ?></td></tr>
      </table>
    </div>
  </div>
  <div class="card">
    <div class="card-header"><h3>📋 Borrowing History</h3></div>
    <div class="card-body" style="padding:0;">
      <div class="table-wrap">
        <table>
          <thead><tr><th>#</th><th>Book</th><th>Borrowed</th><th>Due</th><th>Returned</th><th>Status</th><th>Fine</th></tr></thead>
          <tbody>
            <?php if ($history->num_rows===0): ?>
              <tr><td colspan="7"><div class="empty-state">No borrow history</div></td></tr>
            <?php else: $i=1; while ($h=$history->fetch_assoc()): ?>
              <tr>
                <td><?= $i++ ?></td>
                <td><?= htmlspecialchars($h['book_title']) ?></td>
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
                      (<?= $h['fine_status'] ?>)
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
</div>
<?php include '../includes/layout_end.php'; ?>
