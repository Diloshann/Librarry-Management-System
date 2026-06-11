<?php
require_once '../includes/auth.php';
requireLibrarian();

$pageTitle = 'Manage Books';
$activePage = 'books';

// Handle Add/Edit/Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $isbn      = clean($_POST['isbn'] ?? '');
        $title     = clean($_POST['title'] ?? '');
        $author_id = (int)$_POST['author_id'];
        $publisher = clean($_POST['publisher'] ?? '');
        $edition   = clean($_POST['edition'] ?? '');
        $cat_id    = (int)$_POST['category_id'];
        $language  = clean($_POST['language'] ?? 'English');
        $shelf     = clean($_POST['shelf_location'] ?? '');
        $copies    = max(1, (int)$_POST['total_copies']);
        $desc      = clean($_POST['description'] ?? '');
        $date_added = $_POST['date_added'] ?: date('Y-m-d');
        $cover     = 'no-cover.png';

        // Handle cover upload
        if (!empty($_FILES['cover_image']['name'])) {
            $ext = strtolower(pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','webp'])) {
                $newName = 'book_' . time() . '_' . rand(100,999) . '.' . $ext;
                $dest = UPLOAD_PATH . $newName;
                if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $dest)) {
                    $cover = $newName;
                }
            }
        }

        if ($action === 'add') {
            $stmt = $conn->prepare("INSERT INTO books
                (isbn,title,author_id,publisher,edition,category_id,language,shelf_location,total_copies,available_copies,cover_image,description,date_added)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param("ssisssississs",
                $isbn,$title,$author_id,$publisher,$edition,$cat_id,$language,$shelf,$copies,$copies,$cover,$desc,$date_added);
            $stmt->execute(); $stmt->close();
            setFlash('success', 'Book added successfully.');
            logAction($conn, $_SESSION['role'], $_SESSION['user_id'], 'ADD_BOOK', "Title: $title");
        } else {
            $id = (int)$_POST['book_id'];
            // keep old cover if no new upload
            if ($cover === 'no-cover.png') {
                $old = $conn->query("SELECT cover_image FROM books WHERE id=$id")->fetch_assoc();
                $cover = $old['cover_image'] ?? 'no-cover.png';
            }
            $stmt = $conn->prepare("UPDATE books SET isbn=?,title=?,author_id=?,publisher=?,edition=?,
                category_id=?,language=?,shelf_location=?,total_copies=?,cover_image=?,description=?,date_added=? WHERE id=?");
            $stmt->bind_param("ssisssisssssi",
                $isbn,$title,$author_id,$publisher,$edition,$cat_id,$language,$shelf,$copies,$cover,$desc,$date_added,$id);
            $stmt->execute(); $stmt->close();
            // recalc available
            $conn->query("UPDATE books SET available_copies = total_copies - borrowed_copies WHERE id=$id");
            setFlash('success', 'Book updated successfully.');
            logAction($conn, $_SESSION['role'], $_SESSION['user_id'], 'EDIT_BOOK', "ID: $id");
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['book_id'];
        $conn->query("UPDATE books SET status='inactive' WHERE id=$id");
        setFlash('success', 'Book removed.');
        logAction($conn, $_SESSION['role'], $_SESSION['user_id'], 'DELETE_BOOK', "ID: $id");
    }
    redirect('books.php');
}

// Filters
$search = clean($_GET['q'] ?? '');
$catFilter = (int)($_GET['cat'] ?? 0);
$where = "WHERE b.status='active'";
if ($search) $where .= " AND (b.title LIKE '%$search%' OR b.isbn LIKE '%$search%' OR a.name LIKE '%$search%')";
if ($catFilter) $where .= " AND b.category_id=$catFilter";

$books = $conn->query("SELECT b.*, a.name AS author_name, c.name AS category_name
    FROM books b
    LEFT JOIN authors a ON a.id = b.author_id
    LEFT JOIN categories c ON c.id = b.category_id
    $where ORDER BY b.created_at DESC");

$authors    = $conn->query("SELECT * FROM authors ORDER BY name");
$categories = $conn->query("SELECT * FROM categories ORDER BY name");

include '../includes/layout.php';
?>

<div class="search-bar">
  <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;">
    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" class="form-control" placeholder="🔍 Search title, ISBN, author...">
    <select name="cat" class="form-control" style="max-width:160px;">
      <option value="">All Categories</option>
      <?php $categories->data_seek(0); while ($c = $categories->fetch_assoc()): ?>
        <option value="<?= $c['id'] ?>" <?= $catFilter==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option>
      <?php endwhile; ?>
    </select>
    <button class="btn btn-primary" type="submit">Search</button>
    <a href="books.php" class="btn btn-secondary">Clear</a>
  </form>
  <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'librarian'): ?>
  <button class="btn btn-success" onclick="openModal('addBookModal')" style="margin-left:auto;">➕ Add Book</button>
  <?php endif; ?>
</div>

<div class="card">
  <div class="card-header">
    <h3>📚 Books (<?= $books->num_rows ?>)</h3>
  </div>
  <div class="card-body" style="padding:0;">
    <div class="table-wrap">
      <table id="booksTable">
        <thead>
          <tr><th>Cover</th><th>ISBN</th><th>Title</th><th>Author</th><th>Category</th><th>Shelf</th><th>Total</th><th>Available</th><th>Actions</th></tr>
        </thead>
        <tbody>
          <?php if ($books->num_rows === 0): ?>
            <tr><td colspan="9"><div class="empty-state"><div class="icon">📚</div>No books found</div></td></tr>
          <?php else: while ($b = $books->fetch_assoc()): ?>
            <tr>
              <td><img src="<?= $b['cover_image'] !== 'no-cover.png' ? UPLOAD_URL . htmlspecialchars($b['cover_image']) : '../assets/img/no-cover.png' ?>"
                   style="width:40px;height:52px;object-fit:cover;border-radius:3px;"
                   onerror="this.src='data:image/svg+xml,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'40\' height=\'52\'><rect fill=\'%23ddd\' width=\'40\' height=\'52\'/><text x=\'50%25\' y=\'50%25\' fill=\'%23999\' dominant-baseline=\'middle\' text-anchor=\'middle\' font-size=\'8\'>Book</text></svg>'">
              </td>
              <td><?= htmlspecialchars($b['isbn']) ?></td>
              <td><strong><?= htmlspecialchars($b['title']) ?></strong></td>
              <td><?= htmlspecialchars($b['author_name'] ?? '—') ?></td>
              <td><?= htmlspecialchars($b['category_name'] ?? '—') ?></td>
              <td><?= htmlspecialchars($b['shelf_location']) ?></td>
              <td><?= $b['total_copies'] ?></td>
              <td>
                <span class="badge <?= $b['available_copies']>0?'badge-success':'badge-danger' ?>">
                  <?= $b['available_copies'] ?>
                </span>
              </td>
              <td>
                <button class="btn btn-sm btn-warning" onclick='editBook(<?= json_encode($b) ?>)'>✏️</button>
                <form method="POST" style="display:inline;" onsubmit="return confirmDelete()">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="book_id" value="<?= $b['id'] ?>">
                  <button class="btn btn-sm btn-danger" type="submit">🗑️</button>
                </form>
              </td>
            </tr>
          <?php endwhile; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- ADD BOOK MODAL -->
<div class="modal-overlay" id="addBookModal">
  <div class="modal" style="max-width:640px;">
    <div class="modal-header">
      <h3>➕ Add New Book</h3>
      <span class="modal-close" onclick="closeModal('addBookModal')">✕</span>
    </div>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="add">
      <div class="form-grid">
        <div class="form-group"><label>ISBN</label><input type="text" name="isbn" class="form-control" placeholder="e.g. 978-3-16..."></div>
        <div class="form-group"><label>Title *</label><input type="text" name="title" class="form-control" required></div>
        <div class="form-group">
          <label>Author</label>
          <select name="author_id" class="form-control">
            <option value="0">-- Select Author --</option>
            <?php $authors->data_seek(0); while ($a = $authors->fetch_assoc()): ?>
              <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['name']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="form-group"><label>Publisher</label><input type="text" name="publisher" class="form-control"></div>
        <div class="form-group"><label>Edition</label><input type="text" name="edition" class="form-control" placeholder="e.g. 3rd Edition"></div>
        <div class="form-group">
          <label>Category</label>
          <select name="category_id" class="form-control">
            <option value="0">-- Select Category --</option>
            <?php $categories->data_seek(0); while ($c = $categories->fetch_assoc()): ?>
              <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="form-group"><label>Language</label>
          <select name="language" class="form-control">
            <option>English</option><option>Sinhala</option><option>Tamil</option>
          </select>
        </div>
        <div class="form-group"><label>Shelf Location</label><input type="text" name="shelf_location" class="form-control" placeholder="e.g. A-12"></div>
        <div class="form-group"><label>Total Copies</label><input type="number" name="total_copies" class="form-control" value="1" min="1"></div>
        <div class="form-group"><label>Date Added</label><input type="date" name="date_added" class="form-control" value="<?= date('Y-m-d') ?>"></div>
      </div>
      <div class="form-group">
        <label>Description</label>
        <textarea name="description" class="form-control" rows="3"></textarea>
      </div>
      <div class="form-group">
        <label>Cover Image</label>
        <input type="file" name="cover_image" class="form-control" accept="image/*" onchange="previewImage(this,'addCoverPreview')">
        <img id="addCoverPreview" src="" style="margin-top:8px;max-height:80px;display:none;border-radius:4px;">
      </div>
      <div class="flex-row" style="justify-content:flex-end;margin-top:16px;">
        <button type="button" class="btn btn-secondary" onclick="closeModal('addBookModal')">Cancel</button>
        <button type="submit" class="btn btn-success">Save Book</button>
      </div>
    </form>
  </div>
</div>

<!-- EDIT BOOK MODAL -->
<div class="modal-overlay" id="editBookModal">
  <div class="modal" style="max-width:640px;">
    <div class="modal-header">
      <h3>✏️ Edit Book</h3>
      <span class="modal-close" onclick="closeModal('editBookModal')">✕</span>
    </div>
    <form method="POST" enctype="multipart/form-data" id="editBookForm">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="book_id" id="edit_book_id">
      <div class="form-grid">
        <div class="form-group"><label>ISBN</label><input type="text" name="isbn" id="edit_isbn" class="form-control"></div>
        <div class="form-group"><label>Title *</label><input type="text" name="title" id="edit_title" class="form-control" required></div>
        <div class="form-group">
          <label>Author</label>
          <select name="author_id" id="edit_author_id" class="form-control">
            <option value="0">-- Select Author --</option>
            <?php $authors->data_seek(0); while ($a = $authors->fetch_assoc()): ?>
              <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['name']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="form-group"><label>Publisher</label><input type="text" name="publisher" id="edit_publisher" class="form-control"></div>
        <div class="form-group"><label>Edition</label><input type="text" name="edition" id="edit_edition" class="form-control"></div>
        <div class="form-group">
          <label>Category</label>
          <select name="category_id" id="edit_category_id" class="form-control">
            <option value="0">-- Select --</option>
            <?php $categories->data_seek(0); while ($c = $categories->fetch_assoc()): ?>
              <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="form-group"><label>Shelf Location</label><input type="text" name="shelf_location" id="edit_shelf" class="form-control"></div>
        <div class="form-group"><label>Total Copies</label><input type="number" name="total_copies" id="edit_copies" class="form-control" min="1"></div>
        <div class="form-group"><label>Date Added</label><input type="date" name="date_added" id="edit_date" class="form-control"></div>
      </div>
      <div class="form-group">
        <label>Description</label>
        <textarea name="description" id="edit_desc" class="form-control" rows="3"></textarea>
      </div>
      <div class="form-group">
        <label>New Cover Image (optional)</label>
        <input type="file" name="cover_image" class="form-control" accept="image/*">
      </div>
      <div class="flex-row" style="justify-content:flex-end;margin-top:16px;">
        <button type="button" class="btn btn-secondary" onclick="closeModal('editBookModal')">Cancel</button>
        <button type="submit" class="btn btn-warning">Update Book</button>
      </div>
    </form>
  </div>
</div>

<script>
function editBook(b) {
    document.getElementById('edit_book_id').value = b.id;
    document.getElementById('edit_isbn').value = b.isbn || '';
    document.getElementById('edit_title').value = b.title;
    document.getElementById('edit_author_id').value = b.author_id || 0;
    document.getElementById('edit_publisher').value = b.publisher || '';
    document.getElementById('edit_edition').value = b.edition || '';
    document.getElementById('edit_category_id').value = b.category_id || 0;
    document.getElementById('edit_shelf').value = b.shelf_location || '';
    document.getElementById('edit_copies').value = b.total_copies;
    document.getElementById('edit_date').value = b.date_added || '';
    document.getElementById('edit_desc').value = b.description || '';
    openModal('editBookModal');
}
document.getElementById('addCoverPreview').addEventListener('load', function(){this.style.display='block';});
</script>

<?php include '../includes/layout_end.php'; ?>
