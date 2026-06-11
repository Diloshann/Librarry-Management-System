<?php
require_once '../includes/auth.php';
requireLibrarian();

$pageTitle = 'Authors';
$activePage = 'authors';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $name = clean($_POST['name']); $bio = clean($_POST['bio'] ?? '');
        $stmt = $conn->prepare("INSERT INTO authors (name,bio) VALUES (?,?)");
        $stmt->bind_param("ss",$name,$bio); $stmt->execute(); $stmt->close();
        setFlash('success','Author added.');
    } elseif ($action === 'edit') {
        $id = (int)$_POST['author_id']; $name = clean($_POST['name']); $bio = clean($_POST['bio']??'');
        $stmt = $conn->prepare("UPDATE authors SET name=?,bio=? WHERE id=?");
        $stmt->bind_param("ssi",$name,$bio,$id); $stmt->execute(); $stmt->close();
        setFlash('success','Author updated.');
    } elseif ($action === 'delete') {
        $id = (int)$_POST['author_id'];
        $conn->query("UPDATE books SET author_id=NULL WHERE author_id=$id");
        $conn->query("DELETE FROM authors WHERE id=$id");
        setFlash('success','Author deleted.');
    }
    redirect('authors.php');
}

$authors = $conn->query("SELECT a.*, COUNT(b.id) AS book_count FROM authors a LEFT JOIN books b ON b.author_id=a.id GROUP BY a.id ORDER BY a.name");
include '../includes/layout.php';
?>
<div style="display:flex;justify-content:flex-end;margin-bottom:16px;">
  <button class="btn btn-success" onclick="openModal('addAuthorModal')">➕ Add Author</button>
</div>
<div class="card">
  <div class="card-header"><h3>✍️ Authors</h3></div>
  <div class="card-body" style="padding:0;">
    <table>
      <thead><tr><th>#</th><th>Name</th><th>Bio</th><th>Books</th><th>Actions</th></tr></thead>
      <tbody>
        <?php if ($authors->num_rows===0): ?>
          <tr><td colspan="5"><div class="empty-state">No authors added</div></td></tr>
        <?php else: $i=1; while ($a=$authors->fetch_assoc()): ?>
          <tr>
            <td><?= $i++ ?></td>
            <td><strong><?= htmlspecialchars($a['name']) ?></strong></td>
            <td><?= htmlspecialchars(substr($a['bio']??'',0,60)) ?></td>
            <td><?= $a['book_count'] ?></td>
            <td>
              <button class="btn btn-sm btn-warning" onclick='editAuthor(<?= json_encode($a) ?>)'>✏️</button>
              <form method="POST" style="display:inline;" onsubmit="return confirmDelete()">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="author_id" value="<?= $a['id'] ?>">
                <button class="btn btn-sm btn-danger">🗑️</button>
              </form>
            </td>
          </tr>
        <?php endwhile; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add Modal -->
<div class="modal-overlay" id="addAuthorModal">
  <div class="modal">
    <div class="modal-header"><h3>➕ Add Author</h3><span class="modal-close" onclick="closeModal('addAuthorModal')">✕</span></div>
    <form method="POST">
      <input type="hidden" name="action" value="add">
      <div class="form-group"><label>Name *</label><input type="text" name="name" class="form-control" required></div>
      <div class="form-group"><label>Bio</label><textarea name="bio" class="form-control" rows="3"></textarea></div>
      <div class="flex-row" style="justify-content:flex-end">
        <button type="button" class="btn btn-secondary" onclick="closeModal('addAuthorModal')">Cancel</button>
        <button type="submit" class="btn btn-success">Save</button>
      </div>
    </form>
  </div>
</div>
<!-- Edit Modal -->
<div class="modal-overlay" id="editAuthorModal">
  <div class="modal">
    <div class="modal-header"><h3>✏️ Edit Author</h3><span class="modal-close" onclick="closeModal('editAuthorModal')">✕</span></div>
    <form method="POST">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="author_id" id="edit_author_id">
      <div class="form-group"><label>Name *</label><input type="text" name="name" id="edit_author_name" class="form-control" required></div>
      <div class="form-group"><label>Bio</label><textarea name="bio" id="edit_author_bio" class="form-control" rows="3"></textarea></div>
      <div class="flex-row" style="justify-content:flex-end">
        <button type="button" class="btn btn-secondary" onclick="closeModal('editAuthorModal')">Cancel</button>
        <button type="submit" class="btn btn-warning">Update</button>
      </div>
    </form>
  </div>
</div>
<script>
function editAuthor(a) {
    document.getElementById('edit_author_id').value = a.id;
    document.getElementById('edit_author_name').value = a.name;
    document.getElementById('edit_author_bio').value = a.bio || '';
    openModal('editAuthorModal');
}
</script>
<?php include '../includes/layout_end.php'; ?>
