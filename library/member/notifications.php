<?php
require_once '../includes/auth.php';
requireMember();

$pageTitle = 'Notifications';
$activePage = 'notifications';
$mid = $_SESSION['member_id'];

// Mark all as read
$conn->query("UPDATE notifications SET is_read=1 WHERE member_id=$mid AND is_read=0");

$notifs = $conn->query("SELECT * FROM notifications WHERE member_id=$mid ORDER BY created_at DESC LIMIT 50");

include 'layout.php';
?>
<div class="card">
  <div class="card-header"><h3>🔔 Notifications (<?= $notifs->num_rows ?>)</h3></div>
  <div class="card-body" style="padding:0;">
    <?php if ($notifs->num_rows===0): ?>
      <div class="empty-state"><div class="icon">🔔</div><p>No notifications</p></div>
    <?php else: while ($n=$notifs->fetch_assoc()): ?>
      <div style="padding:14px 20px;border-bottom:1px solid #f0f0f0;display:flex;gap:12px;align-items:flex-start;">
        <div style="font-size:24px;">
          <?php echo ['issued'=>'📤','due_reminder'=>'⏰','overdue'=>'🚨','fine'=>'💰','reservation'=>'📖','general'=>'📢'][$n['type']] ?? '📢'; ?>
        </div>
        <div style="flex:1;">
          <strong style="font-size:14px;"><?= htmlspecialchars($n['title']) ?></strong>
          <p style="font-size:13px;color:#555;margin-top:4px;"><?= htmlspecialchars($n['message']) ?></p>
          <small style="color:#aaa;"><?= date('d M Y H:i', strtotime($n['created_at'])) ?></small>
        </div>
      </div>
    <?php endwhile; endif; ?>
  </div>
</div>
<?php include 'layout_end.php'; ?>
