<?php
require_once '../includes/auth.php';
requireLibrarian();

$pageTitle = 'Return Book';
$activePage = 'return';

$result = null;
$error  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tx_id     = (int)$_POST['tx_id'];
    $condition = clean($_POST['return_condition'] ?? 'Good');
    $notes     = clean($_POST['notes'] ?? '');

    $tx = $conn->query("SELECT bt.*, m.full_name AS member_name, m.id AS mid,
        b.title AS book_title, b.id AS bid
        FROM borrow_transactions bt
        JOIN members m ON m.id = bt.member_id
        JOIN books b ON b.id = bt.book_id
        WHERE bt.id=$tx_id AND bt.status IN ('borrowed','overdue')")->fetch_assoc();

    if (!$tx) {
        $error = 'Transaction not found or already returned.';
    } else {
        $returnDate   = date('Y-m-d');
        $dueDate      = $tx['due_date'];
        $overdueDays  = max(0, (int)((strtotime($returnDate) - strtotime($dueDate)) / 86400));
        $finePerDay   = (float)getSetting($conn, 'fine_per_day');
        $totalFine    = $overdueDays * $finePerDay;

        // Update transaction
        $stmt = $conn->prepare("UPDATE borrow_transactions SET status='returned', return_date=?, book_condition_on_return=?, notes=? WHERE id=?");
        $stmt->bind_param("sssi", $returnDate, $condition, $notes, $tx_id);
        $stmt->execute(); $stmt->close();

        // Update book count
        $conn->query("UPDATE books SET borrowed_copies=borrowed_copies-1, available_copies=available_copies+1 WHERE id={$tx['bid']}");

        // Fine
        $fineId = null;
        if ($totalFine > 0) {
            $stmt2 = $conn->prepare("INSERT INTO fines (transaction_id, member_id, overdue_days, fine_per_day, total_fine, balance) VALUES (?,?,?,?,?,?)");
            $stmt2->bind_param("iiiddd", $tx_id, $tx['mid'], $overdueDays, $finePerDay, $totalFine, $totalFine);
            $stmt2->execute();
            $fineId = $stmt2->insert_id;
            $stmt2->close();
        }

        logAction($conn, $_SESSION['role'], $_SESSION['user_id'], 'RETURN_BOOK',
            "TxID: $tx_id | Book: {$tx['book_title']} | Fine: Rs.$totalFine");

        $result = [
            'tx_id'       => $tx_id,
            'member'      => $tx['member_name'],
            'book'        => $tx['book_title'],
            'borrow_date' => $tx['borrow_date'],
            'due_date'    => $dueDate,
            'return_date' => $returnDate,
            'overdue_days'=> $overdueDays,
            'fine_per_day'=> $finePerDay,
            'total_fine'  => $totalFine,
            'fine_id'     => $fineId,
        ];
    }
}

// Active borrows for dropdown
$activeTx = $conn->query("SELECT bt.id, CONCAT('#',bt.id,' | ',m.full_name,' | ',b.title,' | Due:',bt.due_date) AS label
    FROM borrow_transactions bt
    JOIN members m ON m.id=bt.member_id
    JOIN books b ON b.id=bt.book_id
    WHERE bt.status IN ('borrowed','overdue')
    ORDER BY bt.due_date ASC");

include '../includes/layout.php';
?>

<?php if ($error): ?>
  <div class="alert alert-danger"><?= $error ?></div>
<?php endif; ?>

<?php if ($result): ?>
<div class="card" style="max-width:560px;border-left:4px solid var(--<?= $result['total_fine']>0?'danger':'success' ?>);">
  <div class="card-header">
    <h3><?= $result['total_fine']>0 ? '⚠️ Book Returned — Fine Issued' : '✅ Book Returned Successfully' ?></h3>
    <button class="btn btn-sm btn-secondary" onclick="printDiv('returnReceipt')">🖨️ Print</button>
  </div>
  <div class="card-body" id="returnReceipt">
    <h3 style="text-align:center;margin-bottom:16px;">📚 ATI Library — Return Receipt</h3>
    <table style="width:100%;border-collapse:collapse;">
      <tr><td style="padding:6px;font-weight:600;width:40%;">Transaction ID</td><td>#<?= $result['tx_id'] ?></td></tr>
      <tr style="background:#f9f9f9;"><td style="padding:6px;font-weight:600;">Member</td><td><?= htmlspecialchars($result['member']) ?></td></tr>
      <tr><td style="padding:6px;font-weight:600;">Book</td><td><?= htmlspecialchars($result['book']) ?></td></tr>
      <tr style="background:#f9f9f9;"><td style="padding:6px;font-weight:600;">Borrowed On</td><td><?= $result['borrow_date'] ?></td></tr>
      <tr><td style="padding:6px;font-weight:600;">Due Date</td><td><?= $result['due_date'] ?></td></tr>
      <tr style="background:#f9f9f9;"><td style="padding:6px;font-weight:600;">Return Date</td><td><?= $result['return_date'] ?></td></tr>
      <tr><td style="padding:6px;font-weight:600;">Overdue Days</td><td><?= $result['overdue_days'] ?></td></tr>
      <tr style="background:<?= $result['total_fine']>0?'#f8d7da':'#d4edda' ?>;">
        <td style="padding:6px;font-weight:700;">Total Fine</td>
        <td style="font-weight:700;color:<?= $result['total_fine']>0?'var(--danger)':'var(--success)' ?>;">
          Rs. <?= number_format($result['total_fine'], 2) ?>
        </td>
      </tr>
    </table>
    <?php if ($result['total_fine'] > 0): ?>
      <div class="alert alert-warning" style="margin-top:12px;">
        ⚠️ Fine of <strong>Rs. <?= number_format($result['total_fine'], 2) ?></strong> has been recorded.
        <a href="fines.php" style="color:var(--primary);">Collect payment →</a>
      </div>
    <?php endif; ?>
  </div>
</div>
<a href="return.php" class="btn btn-primary" style="margin-top:10px;">📥 Process Another Return</a>

<?php else: ?>

<div class="card" style="max-width:560px;">
  <div class="card-header"><h3>📥 Process Book Return</h3></div>
  <div class="card-body">
    <form method="POST">
      <div class="form-group">
        <label>Select Transaction *</label>
        <select name="tx_id" class="form-control" required>
          <option value="">-- Select Active Borrow --</option>
          <?php while ($t = $activeTx->fetch_assoc()): ?>
            <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['label']) ?></option>
          <?php endwhile; ?>
        </select>
        <?php if ($activeTx->num_rows === 0): ?>
          <small style="color:#888;">No active borrows at this time.</small>
        <?php endif; ?>
      </div>
      <div class="form-group">
        <label>Book Condition on Return</label>
        <select name="return_condition" class="form-control">
          <option>Good</option><option>Fair</option><option>Poor</option><option>Damaged</option>
        </select>
      </div>
      <div class="form-group">
        <label>Notes (optional)</label>
        <textarea name="notes" class="form-control" rows="2" placeholder="Any notes about the return..."></textarea>
      </div>
      <div class="alert alert-info">
        💡 Fine: Rs.<?= getSetting($conn,'fine_per_day') ?>/day for overdue books
      </div>
      <button type="submit" class="btn btn-info" style="width:100%;justify-content:center;">📥 Process Return</button>
    </form>
  </div>
</div>

<?php endif; ?>

<?php include '../includes/layout_end.php'; ?>
