<?php
require_once '../includes/auth.php';
requireAdmin();
$pageTitle = 'Librarians'; $activePage = 'librarians';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $name  = clean($_POST['full_name']);
        $uname = clean($_POST['username']);
        $email = clean($_POST['email']);
        $phone = clean($_POST['phone']);
        $pass  = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $roleId = 2; // librarian
        $stmt = $conn->prepare("INSERT INTO users (role_id,username,password,full_name,email,phone) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param("isssss",$roleId,$uname,$pass,$name,$email,$phone);
        if ($stmt->execute()) setFlash('success','Librarian added.'); else setFlash('danger','Error: '.$conn->error);
        $stmt->close();
    } elseif ($action === 'toggle') {
        $id = (int)$_POST['lib_id'];
        $conn->query("UPDATE users SET status = IF(status='active','inactive','active') WHERE id=$id");
        setFlash('success','Status toggled.');
    } elseif ($action === 'delete') {
        $id = (int)$_POST['lib_id'];
        $conn->query("DELETE FROM users WHERE id=$id AND role_id=2");
        setFlash('success','Librarian deleted.');
    }
    redirect('librarians.php');
}
$libs = $conn->query("SELECT * FROM users WHERE role_id=2 ORDER BY full_name");
include '../includes/layout.php';
?>
<div style="display:flex;justify-content:flex-end;margin-bottom:16px;">
  <button class="btn btn-success" onclick="openModal('addLibModal')">➕ Add Librarian</button>
</div>
<div class="card">
  <div class="card-header"><h3>👤 Librarians</h3></div>
  <div class="card-body" style="padding:0;">
    <table><thead><tr><th>Name</th><th>Username</th><th>Email</th><th>Phone</th><th>Status</th><th>Actions</th></tr></thead><tbody>
      <?php if ($libs->num_rows===0): ?><tr><td colspan="6"><div class="empty-state">No librarians added</div></td></tr>
      <?php else: while ($l=$libs->fetch_assoc()): ?>
        <tr>
          <td><strong><?= htmlspecialchars($l['full_name']) ?></strong></td>
          <td><?= htmlspecialchars($l['username']) ?></td>
          <td><?= htmlspecialchars($l['email']) ?></td>
          <td><?= htmlspecialchars($l['phone']) ?></td>
          <td><span class="badge badge-<?= $l['status']==='active'?'success':'secondary' ?>"><?= ucfirst($l['status']) ?></span></td>
          <td>
            <form method="POST" style="display:inline;"><input type="hidden" name="action" value="toggle"><input type="hidden" name="lib_id" value="<?= $l['id'] ?>"><button class="btn btn-sm btn-warning"><?= $l['status']==='active'?'Deactivate':'Activate' ?></button></form>
            <form method="POST" style="display:inline;" onsubmit="return confirmDelete()"><input type="hidden" name="action" value="delete"><input type="hidden" name="lib_id" value="<?= $l['id'] ?>"><button class="btn btn-sm btn-danger">🗑️</button></form>
          </td>
        </tr>
      <?php endwhile; endif; ?>
    </tbody></table>
  </div>
</div>
<div class="modal-overlay" id="addLibModal">
  <div class="modal"><div class="modal-header"><h3>➕ Add Librarian</h3><span class="modal-close" onclick="closeModal('addLibModal')">✕</span></div>
    <form method="POST"><input type="hidden" name="action" value="add">
      <div class="form-grid">
        <div class="form-group"><label>Full Name *</label><input type="text" name="full_name" class="form-control" required></div>
        <div class="form-group"><label>Username *</label><input type="text" name="username" class="form-control" required></div>
        <div class="form-group"><label>Email</label><input type="email" name="email" class="form-control"></div>
        <div class="form-group"><label>Phone</label><input type="text" name="phone" class="form-control"></div>
        <div class="form-group"><label>Password *</label><input type="password" name="password" class="form-control" required></div>
      </div>
      <div class="flex-row" style="justify-content:flex-end;margin-top:16px;"><button type="button" class="btn btn-secondary" onclick="closeModal('addLibModal')">Cancel</button><button type="submit" class="btn btn-success">Add Librarian</button></div>
    </form>
  </div>
</div>
<?php include '../includes/layout_end.php'; ?>
