<?php
require_once '../includes/auth.php';
requireAdmin();
$pageTitle = 'Announcements'; $activePage = 'announcements';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $title = clean($_POST['title']); $content = clean($_POST['content']);
        $stmt = $conn->prepare("INSERT INTO announcements (title,content,created_by) VALUES (?,?,?)");
        $stmt->bind_param("ssi",$title,$content,$_SESSION['user_id']); $stmt->execute(); $stmt->close();
        setFlash('success','Announcement added.');
    } elseif ($action === 'toggle') {
        $id = (int)$_POST['ann_id'];
        $conn->query("UPDATE announcements SET is_active = 1 - is_active WHERE id=$id");
        setFlash('success','Status toggled.');
    } elseif ($action === 'delete') {
        $id = (int)$_POST['ann_id'];
        $conn->query("DELETE FROM announcements WHERE id=$id");
        setFlash('success','Deleted.');
    }
    redirect('announcements.php');
}
$anns = $conn->query("SELECT a.*, u.full_name AS created_by_name FROM announcements a JOIN users u ON u.id=a.created_by ORDER BY a.created_at DESC");
include '../includes/layout.php';
?>
<div style="display:flex;justify-content:flex-end;margin-bottom:16px;">
  <button class="btn btn-success" onclick="openModal('addAnnModal')">➕ New Announcement</button>
</div>
<div class="card">
  <div class="card-header"><h3>📢 Announcements</h3></div>
  <div class="card-body" style="padding:0;">
    <table><thead><tr><th>Title</th><th>Content</th><th>Created By</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead><tbody>
      <?php if ($anns->num_rows===0): ?><tr><td colspan="6"><div class="empty-state">No announcements</div></td></tr>
      <?php else: while ($a=$anns->fetch_assoc()): ?>
        <tr>
          <td><strong><?= htmlspecialchars($a['title']) ?></strong></td>
          <td><?= htmlspecialchars(substr($a['content'],0,80)) ?>...</td>
          <td><?= htmlspecialchars($a['created_by_name']) ?></td>
          <td><?= date('d M Y', strtotime($a['created_at'])) ?></td>
          <td><span class="badge <?= $a['is_active']?'badge-success':'badge-secondary' ?>"><?= $a['is_active']?'Active':'Inactive' ?></span></td>
          <td>
            <form method="POST" style="display:inline;"><input type="hidden" name="action" value="toggle"><input type="hidden" name="ann_id" value="<?= $a['id'] ?>"><button class="btn btn-sm btn-warning"><?= $a['is_active']?'Deactivate':'Activate' ?></button></form>
            <form method="POST" style="display:inline;" onsubmit="return confirmDelete()"><input type="hidden" name="action" value="delete"><input type="hidden" name="ann_id" value="<?= $a['id'] ?>"><button class="btn btn-sm btn-danger">🗑️</button></form>
          </td>
        </tr>
      <?php endwhile; endif; ?>
    </tbody></table>
  </div>
</div>
<div class="modal-overlay" id="addAnnModal">
  <div class="modal"><div class="modal-header"><h3>➕ New Announcement</h3><span class="modal-close" onclick="closeModal('addAnnModal')">✕</span></div>
    <form method="POST"><input type="hidden" name="action" value="add">
      <div class="form-group"><label>Title *</label><input type="text" name="title" class="form-control" required></div>
      <div class="form-group"><label>Content *</label><textarea name="content" class="form-control" rows="5" required></textarea></div>
      <div class="flex-row" style="justify-content:flex-end"><button type="button" class="btn btn-secondary" onclick="closeModal('addAnnModal')">Cancel</button><button type="submit" class="btn btn-success">Publish</button></div>
    </form>
  </div>
</div>
<?php include '../includes/layout_end.php'; ?>
