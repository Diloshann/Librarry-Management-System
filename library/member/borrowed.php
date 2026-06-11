<?php
require_once '../includes/auth.php';
requireMember();
updateOverdueStatus($conn);

$pageTitle = 'My Borrowed Books';
$activePage = 'borrowed';
$mid = $_SESSION['member_id'];

$books = $conn->query("SELECT bt.*, b.title, b.isbn, b.shelf_location, b.cover_image,
    a.name AS author_name,
    DATEDIFF(bt.due_date, CURDATE()) AS days_left
    FROM borrow_transactions bt
    JOIN books b ON b.id=bt.book_id
    LEFT JOIN authors a ON a.id=b.author_id
    WHERE bt.member_id=$mid AND bt.status IN ('borrowed','overdue')
    ORDER BY bt.due_date ASC");

include 'layout.php';
?>

<div class="card">
  <div class="card-header">
    <h3>📤 Currently Borrowed Books (<?= $books->num_rows ?>)</h3>
  </div>
  <div class="card-body" style="padding:0;">
    <?php if ($books->num_rows === 0): ?>
      <div class="empty-state"><div class="icon">📚</div><p>You have no active borrowed books.</p>
        <a href="search.php" class="btn btn-primary" style="margin-top:12px;">🔍 Search Books</a>
      </div>
    <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Book</th><th>Author</th><th>ISBN</th><th>Shelf</th><th>Borrowed On</th><th>Due Date</th><th>Days Left</th><th>Status</th></tr></thead>
          <tbody>
            <?php while ($b=$books->fetch_assoc()): ?>
              <tr>
                <td><strong><?= htmlspecialchars($b['title']) ?></strong></td>
                <td><?= htmlspecialchars($b['author_name']??'—') ?></td>
                <td><?= htmlspecialchars($b['isbn']??'—') ?></td>
                <td><?= htmlspecialchars($b['shelf_location']??'—') ?></td>
                <td><?= $b['borrow_date'] ?></td>
                <td style="<?= $b['days_left']<0?'color:var(--danger);font-weight:700;':($b['days_left']<=3?'color:var(--warning);font-weight:600;':'') ?>">
                  <?= $b['due_date'] ?>
                </td>
                <td>
                  <?php if ($b['days_left'] < 0): ?>
                    <span class="badge badge-danger"><?= abs($b['days_left']) ?> days late!</span>
                  <?php elseif ($b['days_left'] == 0): ?>
                    <span class="badge badge-warning">Due today!</span>
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
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<div class="alert alert-info">
  💡 To return a book, please visit the library and see the Librarian. Overdue books are fined at 
  <strong>Rs.<?= getSetting($conn,'fine_per_day') ?>/day</strong>.
</div>

<?php include 'layout_end.php'; ?>
