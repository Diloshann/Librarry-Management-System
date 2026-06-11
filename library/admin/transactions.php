<?php
require_once '../includes/auth.php';
requireLibrarian();
updateOverdueStatus($conn);

$pageTitle = 'All Transactions';
$activePage = 'transactions';

$filter = clean($_GET['filter'] ?? 'all');
$search = clean($_GET['q'] ?? '');

$where = "WHERE 1=1";
if ($filter !== 'all') $where .= " AND bt.status='$filter'";
if ($search) $where .= " AND (m.full_name LIKE '%$search%' OR b.title LIKE '%$search%' OR m.student_id LIKE '%$search%')";

$txs = $conn->query("SELECT bt.*, m.full_name AS member_name, m.student_id,
    b.title AS book_title, u.full_name AS issued_by_name
    FROM borrow_transactions bt
    JOIN members m ON m.id = bt.member_id
    JOIN books b ON b.id = bt.book_id
    JOIN users u ON u.id = bt.issued_by
    $where ORDER BY bt.created_at DESC LIMIT 200");

include '../includes/layout.php';
?>

<div class="search-bar">
  <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;">
    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" class="form-control" placeholder="🔍 Search member, book...">
    <select name="filter" class="form-control" style="max-width:140px;" onchange="this.form.submit()">
      <option value="all"      <?= $filter==='all'     ?'selected':'' ?>>All Status</option>
      <option value="borrowed" <?= $filter==='borrowed'?'selected':'' ?>>Borrowed</option>
      <option value="returned" <?= $filter==='returned'?'selected':'' ?>>Returned</option>
      <option value="overdue"  <?= $filter==='overdue' ?'selected':'' ?>>Overdue</option>
      <option value="renewed"  <?= $filter==='renewed' ?'selected':'' ?>>Renewed</option>
    </select>
    <button class="btn btn-primary" type="submit">Search</button>
    <a href="transactions.php" class="btn btn-secondary">Clear</a>
  </form>
</div>

<div class="card">
  <div class="card-header"><h3>📋 Transactions (<?= $txs->num_rows ?>)</h3></div>
  <div class="card-body" style="padding:0;">
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>#ID</th><th>Member</th><th>Book</th><th>Issued By</th><th>Borrow Date</th><th>Due Date</th><th>Return Date</th><th>Status</th></tr>
        </thead>
        <tbody>
          <?php if ($txs->num_rows === 0): ?>
            <tr><td colspan="8"><div class="empty-state">No transactions found</div></td></tr>
          <?php else: while ($t = $txs->fetch_assoc()): ?>
            <tr>
              <td>#<?= $t['id'] ?></td>
              <td>
                <?= htmlspecialchars($t['member_name']) ?><br>
                <small style="color:#888;"><?= htmlspecialchars($t['student_id']) ?></small>
              </td>
              <td><?= htmlspecialchars(substr($t['book_title'],0,35)) ?>...</td>
              <td><?= htmlspecialchars($t['issued_by_name']) ?></td>
              <td><?= $t['borrow_date'] ?></td>
              <td style="<?= (!$t['return_date'] && $t['due_date'] < date('Y-m-d'))?'color:var(--danger);font-weight:600;':'' ?>">
                <?= $t['due_date'] ?>
              </td>
              <td><?= $t['return_date'] ?: '—' ?></td>
              <td>
                <?php $s = $t['status'];
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

<?php include '../includes/layout_end.php'; ?>
