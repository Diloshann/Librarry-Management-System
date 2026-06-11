<?php
require_once '../includes/auth.php';
requireAdmin();
$pageTitle = 'Categories'; $activePage = 'categories';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $name = clean($_POST['name']); $desc = clean($_POST['description']??'');
        $stmt = $conn->prepare("INSERT INTO categories (name,description) VALUES (?,?)");
        $stmt->bind_param("ss",$name,$desc); $stmt->execute(); $stmt->close();
        setFlash('success','Category added.');
    } elseif ($action === 'edit') {
        $id = (int)$_POST['cat_id']; $name = clean($_POST['name']); $desc = clean($_POST['description']??'');
        $stmt = $conn->prepare("UPDATE categories SET name=?,description=? WHERE id=?");
        $stmt->bind_param("ssi",$name,$desc,$id); $stmt->execute(); $stmt->close();
        setFlash('success','Category updated.');
    } elseif ($action === 'delete') {
        $id = (int)$_POST['cat_id'];
        $conn->query("UPDATE books SET category_id=NULL WHERE category_id=$id");
        $conn->query("DELETE FROM categories WHERE id=$id");
        setFlash('success','Category deleted.');
    }
    redirect('categories.php');
}
$cats = $conn->query("SELECT c.*, COUNT(b.id) AS book_count FROM categories c LEFT JOIN books b ON b.category_id=c.id GROUP BY c.id ORDER BY c.name");
include '../includes/layout.php';
?>
<div style="display:flex;justify-content:flex-end;margin-bottom:16px;">
  <button class="btn btn-success" onclick="openModal('addCatModal')">➕ Add Category</button>
</div>
<div class="card">
  <div class="card-header"><h3>🏷️ Categories</h3></div>
  <div class="card-body" style="padding:0;">
    <table><thead><tr><th>#</th><th>Name</th><th>Description</th><th>Books</th><th>Actions</th></tr></thead><tbody>
      <?php if ($cats->num_rows===0): ?><tr><td colspan="5"><div class="empty-state">No categories</div></td></tr>
      <?php else: $i=1; while ($c=$cats->fetch_assoc()): ?>
        <tr>
          <td><?= $i++ ?></td><td><strong><?= htmlspecialchars($c['name']) ?></strong></td>
          <td><?= htmlspecialchars(substr($c['description']??'',0,60)) ?></td><td><?= $c['book_count'] ?></td>
          <td>
            <button class="btn btn-sm btn-warning" onclick='editCat(<?= json_encode($c) ?>)'>✏️</button>
            <form method="POST" style="display:inline;" onsubmit="return confirmDelete()">
              <input type="hidden" name="action" value="delete"><input type="hidden" name="cat_id" value="<?= $c['id'] ?>">
              <button class="btn btn-sm btn-danger">🗑️</button>
            </form>
          </td>
        </tr>
      <?php endwhile; endif; ?>
    </tbody></table>
  </div>
</div>
<!-- Add -->
<div class="modal-overlay" id="addCatModal">
  <div class="modal"><div class="modal-header"><h3>➕ Add Category</h3><span class="modal-close" onclick="closeModal('addCatModal')">✕</span></div>
    <form method="POST"><input type="hidden" name="action" value="add">
      <div class="form-group"><label>Name *</label><input type="text" name="name" class="form-control" required></div>
      <div class="form-group"><label>Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
      <div class="flex-row" style="justify-content:flex-end"><button type="button" class="btn btn-secondary" onclick="closeModal('addCatModal')">Cancel</button><button type="submit" class="btn btn-success">Save</button></div>
    </form>
  </div>
</div>
<!-- Edit -->
<div class="modal-overlay" id="editCatModal">
  <div class="modal"><div class="modal-header"><h3>✏️ Edit Category</h3><span class="modal-close" onclick="closeModal('editCatModal')">✕</span></div>
    <form method="POST"><input type="hidden" name="action" value="edit"><input type="hidden" name="cat_id" id="edit_cat_id">
      <div class="form-group"><label>Name *</label><input type="text" name="name" id="edit_cat_name" class="form-control" required></div>
      <div class="form-group"><label>Description</label><textarea name="description" id="edit_cat_desc" class="form-control" rows="2"></textarea></div>
      <div class="flex-row" style="justify-content:flex-end"><button type="button" class="btn btn-secondary" onclick="closeModal('editCatModal')">Cancel</button><button type="submit" class="btn btn-warning">Update</button></div>
    </form>
  </div>
</div>
<script>
function editCat(c){document.getElementById('edit_cat_id').value=c.id;document.getElementById('edit_cat_name').value=c.name;document.getElementById('edit_cat_desc').value=c.description||'';openModal('editCatModal');}
</script>
<?php include '../includes/layout_end.php'; ?>
