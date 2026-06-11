<?php
require_once '../includes/auth.php';
requireLibrarian();
updateOverdueStatus($conn);

$pageTitle = 'Fines';
$activePage = 'fines';

// Pay fine
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'pay') {
    $fine_id    = (int)$_POST['fine_id'];
    $pay_amount = (float)$_POST['pay_amount'];
    $fine = $conn->query("SELECT * FROM fines WHERE id=$fine_id")->fetch_assoc();
    if ($fine) {
        $newPaid    = min($fine['total_fine'], $fine['paid_amount'] + $pay_amount);
        $newBalance = $fine['total_fine'] - $newPaid;
        $status     = $newBalance <= 0 ? 'paid' : 'unpaid';
        $stmt = $conn->prepare("UPDATE fines SET paid_amount=?, balance=?, status=?, collected_by=?, paid_at=NOW() WHERE id=?");
        $stmt->bind_param("ddsii", $newPaid, $newBalance, $status, $_SESSION['user_id'], $fine_id);
        $stmt->execute(); $stmt->close();
        setFlash('success', "Payment of Rs.$pay_amount recorded.");
        logAction($conn,$_SESSION['role'],$_SESSION['user_id'],'COLLECT_FINE',"Fine ID: $fine_id | Amount: $pay_amount");
    }
    redirect('fines.php');
}

// Waive
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'waive') {
    $fine_id = (int)$_POST['fine_id'];
    $conn->query("UPDATE fines SET status='waived', balance=0 WHERE id=$fine_id");
    setFlash('success','Fine waived.');
    redirect('fines.php');
}

$filter = clean($_GET['filter'] ?? 'unpaid');
$whereClause = $filter !== 'all' ? "WHERE f.status='$filter'" : '';

$fines = $conn->query("SELECT f.*, m.full_name AS member_name, m.student_id,
    b.title AS book_title, bt.borrow_date, bt.due_date, bt.return_date
    FROM fines f
    JOIN members m ON m.id = f.member_id
    JOIN borrow_transactions bt ON bt.id = f.transaction_id
    JOIN books b ON b.id = bt.book_id
    $whereClause ORDER BY f.created_at DESC");

$totalUnpaid = $conn->query("SELECT SUM(balance) AS t FROM fines WHERE status='unpaid'")->fetch_assoc()['t'] ?? 0;
$totalPaid   = $conn->query("SELECT SUM(paid_amount) AS t FROM fines WHERE status='paid'")->fetch_assoc()['t'] ?? 0;

include '../includes/layout.php';
?>

<div class="stats-grid" style="grid-template-columns:repeat(3,1fr);">
  <div class="stat-card red"><div class="icon">⚠️</div><div class="num">Rs.<?= number_format($totalUnpaid) ?></div><div class="label">Unpaid Fines</div></div>
  <div class="stat-card green"><div class="icon">✅</div><div class="num">Rs.<?= number_format($totalPaid) ?></div><div class="label">Collected</div></div>
  <div class="stat-card orange"><div class="icon">💰</div><div class="num">Rs.<?= getSetting($conn,'fine_per_day') ?>/day</div><div class="label">Fine Rate</div></div>
</div>

<div class="search-bar">
  <a href="?filter=unpaid" class="btn <?= $filter==='unpaid'?'btn-primary':'btn-outline-primary' ?>">Unpaid</a>
  <a href="?filter=paid"   class="btn <?= $filter==='paid'  ?'btn-primary':'btn-outline-primary' ?>">Paid</a>
  <a href="?filter=waived" class="btn <?= $filter==='waived'?'btn-primary':'btn-outline-primary' ?>">Waived</a>
  <a href="?filter=all"    class="btn <?= $filter==='all'   ?'btn-primary':'btn-outline-primary' ?>">All</a>
</div>

<div class="card">
  <div class="card-header"><h3>💰 Fines (<?= $fines->num_rows ?>)</h3></div>
  <div class="card-body" style="padding:0;">
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>Member</th><th>Book</th><th>Due Date</th><th>Return Date</th><th>Overdue Days</th><th>Total Fine</th><th>Paid</th><th>Balance</th><th>Status</th><th>Actions</th></tr>
        </thead>
        <tbody>
          <?php if ($fines->num_rows === 0): ?>
            <tr><td colspan="10"><div class="empty-state"><div class="icon">✅</div>No <?= $filter ?> fines</div></td></tr>
          <?php else: while ($f = $fines->fetch_assoc()): ?>
            <tr>
              <td>
                <strong><?= htmlspecialchars($f['member_name']) ?></strong><br>
                <small style="color:#888;"><?= htmlspecialchars($f['student_id']) ?></small>
              </td>
              <td><?= htmlspecialchars(substr($f['book_title'],0,30)) ?>...</td>
              <td><?= $f['due_date'] ?></td>
              <td><?= $f['return_date'] ?: '<em style="color:#aaa">Pending</em>' ?></td>
              <td style="color:var(--danger);font-weight:600;"><?= $f['overdue_days'] ?></td>
              <td>Rs.<?= number_format($f['total_fine'],2) ?></td>
              <td style="color:var(--success);">Rs.<?= number_format($f['paid_amount'],2) ?></td>
              <td style="font-weight:700;color:<?= $f['balance']>0?'var(--danger)':'var(--success)' ?>">
                Rs.<?= number_format($f['balance'],2) ?>
              </td>
              <td>
                <?php $cls = $f['status']==='paid'?'success':($f['status']==='waived'?'info':'danger'); ?>
                <span class="badge badge-<?= $cls ?>"><?= ucfirst($f['status']) ?></span>
              </td>
              <td>
                <?php if ($f['status'] === 'unpaid'): ?>
                  <button class="btn btn-sm btn-success" onclick='openPayModal(<?= $f["id"] ?>,<?= $f["balance"] ?>)'>💵 Pay</button>
                  <?php if ($_SESSION['role'] === 'admin'): ?>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Waive this fine?')">
                      <input type="hidden" name="action" value="waive">
                      <input type="hidden" name="fine_id" value="<?= $f['id'] ?>">
                      <button class="btn btn-sm btn-secondary" type="submit">Waive</button>
                    </form>
                  <?php endif; ?>
                <?php endif; ?>
              </td>
            </tr>
          <?php endwhile; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- PAY MODAL -->
<div class="modal-overlay" id="payModal">
  <div class="modal" style="max-width:360px;">
    <div class="modal-header">
      <h3>💵 Collect Fine Payment</h3>
      <span class="modal-close" onclick="closeModal('payModal')">✕</span>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="pay">
      <input type="hidden" name="fine_id" id="pay_fine_id">
      <div class="form-group">
        <label>Balance Due</label>
        <div style="font-size:22px;font-weight:700;color:var(--danger);">Rs. <span id="pay_balance">0</span></div>
      </div>
      <div class="form-group">
        <label>Amount Paying (Rs.) *</label>
        <input type="number" name="pay_amount" id="pay_amount_input" class="form-control" step="0.01" min="1" required>
      </div>
      <div class="flex-row" style="justify-content:flex-end;margin-top:16px;">
        <button type="button" class="btn btn-secondary" onclick="closeModal('payModal')">Cancel</button>
        <button type="submit" class="btn btn-success">Confirm Payment</button>
      </div>
    </form>
  </div>
</div>

<script>
function openPayModal(fineId, balance) {
    document.getElementById('pay_fine_id').value = fineId;
    document.getElementById('pay_balance').textContent = parseFloat(balance).toFixed(2);
    document.getElementById('pay_amount_input').value = parseFloat(balance).toFixed(2);
    document.getElementById('pay_amount_input').max = balance;
    openModal('payModal');
}
</script>

<?php include '../includes/layout_end.php'; ?>
