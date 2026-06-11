<?php
require_once '../includes/auth.php';
requireLibrarian();

$pageTitle = 'Issue Book';
$activePage = 'issue';

$success = $receipt = null;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $member_id = (int)$_POST['member_id'];
    $book_id   = (int)$_POST['book_id'];
    $condition = clean($_POST['book_condition'] ?? 'Good');

    $borrowDays = (int)getSetting($conn, 'borrow_days');
    $maxBooks   = (int)getSetting($conn, 'max_books_per_member');

    // Validate member
    $mem = $conn->query("SELECT * FROM members WHERE id=$member_id AND status='active'")->fetch_assoc();
    if (!$mem) { $error = 'Member not found or inactive.'; }

    // Check max books
    if (!$error) {
        $cnt = $conn->query("SELECT COUNT(*) AS c FROM borrow_transactions WHERE member_id=$member_id AND status IN ('borrowed','overdue')")->fetch_assoc()['c'];
        if ($cnt >= $maxBooks) $error = "Member has already borrowed $maxBooks books (max allowed).";
    }

    // Check availability
    if (!$error) {
        $book = $conn->query("SELECT * FROM books WHERE id=$book_id AND status='active'")->fetch_assoc();
        if (!$book) { $error = 'Book not found.'; }
        elseif ($book['available_copies'] < 1) { $error = 'No copies available for this book.'; }
    }

    if (!$error) {
        $borrowDate = date('Y-m-d');
        $dueDate = date('Y-m-d', strtotime("+$borrowDays days"));

        // Insert transaction
        $stmt = $conn->prepare("INSERT INTO borrow_transactions
            (member_id, book_id, issued_by, borrow_date, due_date, book_condition_on_issue)
            VALUES (?,?,?,?,?,?)");
        $stmt->bind_param("iiisss", $member_id, $book_id, $_SESSION['user_id'], $borrowDate, $dueDate, $condition);
        $stmt->execute();
        $txId = $stmt->insert_id;
        $stmt->close();

        // Update book count
        $conn->query("UPDATE books SET borrowed_copies=borrowed_copies+1, available_copies=available_copies-1 WHERE id=$book_id");

        // Notification
        $notifTitle = "Book Issued: " . $book['title'];
        $notifMsg = "You have borrowed '{$book['title']}'. Due date: $dueDate.";
        $stmt2 = $conn->prepare("INSERT INTO notifications (member_id, title, message, type) VALUES (?,?,?,'issued')");
        $stmt2->bind_param("iss", $member_id, $notifTitle, $notifMsg);
        $stmt2->execute(); $stmt2->close();

        logAction($conn, $_SESSION['role'], $_SESSION['user_id'], 'ISSUE_BOOK',
            "TxID: $txId | Member: {$mem['full_name']} | Book: {$book['title']}");

        $receipt = [
            'tx_id'   => $txId,
            'member'  => $mem['full_name'],
            'student_id' => $mem['student_id'],
            'book'    => $book['title'],
            'isbn'    => $book['isbn'],
            'shelf'   => $book['shelf_location'],
            'issued_by' => $_SESSION['user_name'],
            'borrow_date' => $borrowDate,
            'due_date'    => $dueDate,
        ];
    }
}

$members = $conn->query("SELECT id, CONCAT(student_id,' - ',full_name) AS label FROM members WHERE status='active' ORDER BY full_name");
$books   = $conn->query("SELECT id, CONCAT(title,' [Avail: ',available_copies,']') AS label FROM books WHERE status='active' AND available_copies>0 ORDER BY title");

include '../includes/layout.php';
?>

<?php if ($error): ?>
  <div class="alert alert-danger"><?= $error ?></div>
<?php endif; ?>

<?php if ($receipt): ?>
  <!-- RECEIPT -->
  <div class="card" style="border-left:4px solid var(--success);max-width:560px;">
    <div class="card-header" style="background:#d4edda;">
      <h3>✅ Book Issued Successfully!</h3>
      <button class="btn btn-sm btn-secondary" onclick="printDiv('receipt')">🖨️ Print</button>
    </div>
    <div class="card-body" id="receipt">
      <h3 style="text-align:center;margin-bottom:16px;">📚 ATI Library — Issue Receipt</h3>
      <table style="width:100%;border-collapse:collapse;">
        <tr><td style="padding:6px;font-weight:600;width:40%;">Transaction ID</td><td style="padding:6px;">#<?= $receipt['tx_id'] ?></td></tr>
        <tr style="background:#f9f9f9;"><td style="padding:6px;font-weight:600;">Member</td><td style="padding:6px;"><?= htmlspecialchars($receipt['member']) ?></td></tr>
        <tr><td style="padding:6px;font-weight:600;">Student ID</td><td style="padding:6px;"><?= htmlspecialchars($receipt['student_id']) ?></td></tr>
        <tr style="background:#f9f9f9;"><td style="padding:6px;font-weight:600;">Book Title</td><td style="padding:6px;"><?= htmlspecialchars($receipt['book']) ?></td></tr>
        <tr><td style="padding:6px;font-weight:600;">ISBN</td><td style="padding:6px;"><?= htmlspecialchars($receipt['isbn']) ?></td></tr>
        <tr style="background:#f9f9f9;"><td style="padding:6px;font-weight:600;">Shelf</td><td style="padding:6px;"><?= htmlspecialchars($receipt['shelf']) ?></td></tr>
        <tr><td style="padding:6px;font-weight:600;">Issued By</td><td style="padding:6px;"><?= htmlspecialchars($receipt['issued_by']) ?></td></tr>
        <tr style="background:#f9f9f9;"><td style="padding:6px;font-weight:600;">Borrow Date</td><td style="padding:6px;"><?= $receipt['borrow_date'] ?></td></tr>
        <tr><td style="padding:6px;font-weight:600;color:var(--danger);">Due Date</td><td style="padding:6px;color:var(--danger);font-weight:700;"><?= $receipt['due_date'] ?></td></tr>
      </table>
      <p style="text-align:center;margin-top:16px;font-size:12px;color:#888;">Please return the book by the due date to avoid fines.</p>
    </div>
  </div>
  <a href="issue.php" class="btn btn-primary">➕ Issue Another</a>

<?php else: ?>

<div class="card" style="max-width:560px;">
  <div class="card-header"><h3>📤 Issue Book to Member</h3></div>
  <div class="card-body">
    <form method="POST">
      <div class="form-group">
        <label>Select Member *</label>
        <select name="member_id" class="form-control" required>
          <option value="">-- Choose Member --</option>
          <?php while ($m = $members->fetch_assoc()): ?>
            <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['label']) ?></option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Select Book *</label>
        <select name="book_id" class="form-control" required>
          <option value="">-- Choose Book --</option>
          <?php while ($bk = $books->fetch_assoc()): ?>
            <option value="<?= $bk['id'] ?>"><?= htmlspecialchars($bk['label']) ?></option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Book Condition</label>
        <select name="book_condition" class="form-control">
          <option>Good</option><option>Fair</option><option>Poor</option>
        </select>
      </div>
      <?php
        $borrowDays = getSetting($conn, 'borrow_days');
        $duePreview = date('Y-m-d', strtotime("+$borrowDays days"));
      ?>
      <div class="alert alert-info">
        📅 Borrow period: <strong><?= $borrowDays ?> days</strong> — Due: <strong><?= $duePreview ?></strong>
      </div>
      <button type="submit" class="btn btn-success" style="width:100%;justify-content:center;">📤 Issue Book</button>
    </form>
  </div>
</div>

<?php endif; ?>

<?php include '../includes/layout_end.php'; ?>
