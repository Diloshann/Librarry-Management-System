<?php
require_once '../includes/auth.php';
requireAdmin();
$pageTitle = 'Audit Log'; $activePage = 'audit';

$logs = $conn->query("SELECT * FROM audit_log ORDER BY created_at DESC LIMIT 500");
include '../includes/layout.php';
?>
<div class="card">
  <div class="card-header"><h3>🔍 Audit Log (last 500)</h3></div>
  <div class="card-body" style="padding:0;">
    <div class="table-wrap">
      <table>
        <thead><tr><th>#</th><th>Date/Time</th><th>Role</th><th>User ID</th><th>Action</th><th>Details</th><th>IP</th></tr></thead>
        <tbody>
          <?php if ($logs->num_rows===0): ?><tr><td colspan="7"><div class="empty-state">No logs</div></td></tr>
          <?php else: while ($l=$logs->fetch_assoc()): ?>
            <tr>
              <td><?= $l['id'] ?></td>
              <td><?= $l['created_at'] ?></td>
              <td><span class="badge badge-info"><?= $l['user_type'] ?></span></td>
              <td><?= $l['user_id'] ?></td>
              <td><?= htmlspecialchars($l['action']) ?></td>
              <td><?= htmlspecialchars($l['details']) ?></td>
              <td style="color:#aaa;"><?= $l['ip_address'] ?></td>
            </tr>
          <?php endwhile; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php include '../includes/layout_end.php'; ?>
