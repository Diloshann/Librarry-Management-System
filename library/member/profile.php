<?php
require_once '../includes/auth.php';
requireMember();

$pageTitle = 'My Profile';
$activePage = 'profile';
$mid = $_SESSION['member_id'];

$member = $conn->query("SELECT * FROM members WHERE id=$mid")->fetch_assoc();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $email = clean($_POST['email']);
        $phone = clean($_POST['phone']);
        $addr  = clean($_POST['address']);
        $stmt  = $conn->prepare("UPDATE members SET email=?, phone=?, address=? WHERE id=?");
        $stmt->bind_param("sssi",$email,$phone,$addr,$mid);
        $stmt->execute(); $stmt->close();
        setFlash('success','Profile updated.');
        redirect('profile.php');
    } elseif ($action === 'change_password') {
        $old  = $_POST['old_password'];
        $new  = $_POST['new_password'];
        $new2 = $_POST['new_password2'];
        if (!password_verify($old, $member['password'])) {
            $error = 'Current password is incorrect.';
        } elseif ($new !== $new2) {
            $error = 'New passwords do not match.';
        } elseif (strlen($new) < 6) {
            $error = 'New password must be at least 6 characters.';
        } else {
            $hashed = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE members SET password=? WHERE id=?");
            $stmt->bind_param("si",$hashed,$mid); $stmt->execute(); $stmt->close();
            setFlash('success','Password changed successfully.');
            redirect('profile.php');
        }
    }
}

include 'layout.php';
?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
  <!-- Profile Info -->
  <div class="card">
    <div class="card-header"><h3>👤 My Profile</h3></div>
    <div class="card-body">
      <div style="text-align:center;margin-bottom:16px;">
        <div style="font-size:64px;">👤</div>
        <h3><?= htmlspecialchars($member['full_name']) ?></h3>
        <p style="color:#888;"><?= htmlspecialchars($member['student_id']) ?></p>
        <span class="badge badge-success"><?= ucfirst($member['status']) ?></span>
      </div>
      <table style="width:100%;font-size:13px;">
        <tr><td style="color:#888;padding:5px 0;">Course</td><td><?= htmlspecialchars($member['course']) ?></td></tr>
        <tr><td style="color:#888;padding:5px 0;">Department</td><td><?= htmlspecialchars($member['department']) ?></td></tr>
        <tr><td style="color:#888;padding:5px 0;">Batch</td><td><?= htmlspecialchars($member['batch']) ?></td></tr>
        <tr><td style="color:#888;padding:5px 0;">NIC</td><td><?= htmlspecialchars($member['nic']) ?></td></tr>
        <tr><td style="color:#888;padding:5px 0;">Username</td><td><?= htmlspecialchars($member['username']) ?></td></tr>
        <tr><td style="color:#888;padding:5px 0;">Joined</td><td><?= date('d M Y',strtotime($member['created_at'])) ?></td></tr>
      </table>
    </div>
  </div>

  <div>
    <!-- Update Contact -->
    <div class="card" style="margin-bottom:20px;">
      <div class="card-header"><h3>✏️ Update Contact Info</h3></div>
      <div class="card-body">
        <form method="POST">
          <input type="hidden" name="action" value="update_profile">
          <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($member['email']) ?>">
          </div>
          <div class="form-group">
            <label>Phone</label>
            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($member['phone']) ?>">
          </div>
          <div class="form-group">
            <label>Address</label>
            <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($member['address']) ?></textarea>
          </div>
          <button type="submit" class="btn btn-primary">💾 Update</button>
        </form>
      </div>
    </div>

    <!-- Change Password -->
    <div class="card">
      <div class="card-header"><h3>🔒 Change Password</h3></div>
      <div class="card-body">
        <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
        <form method="POST">
          <input type="hidden" name="action" value="change_password">
          <div class="form-group">
            <label>Current Password</label>
            <input type="password" name="old_password" class="form-control" required>
          </div>
          <div class="form-group">
            <label>New Password</label>
            <input type="password" name="new_password" class="form-control" required minlength="6">
          </div>
          <div class="form-group">
            <label>Confirm New Password</label>
            <input type="password" name="new_password2" class="form-control" required>
          </div>
          <button type="submit" class="btn btn-warning">🔒 Change Password</button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php include 'layout_end.php'; ?>
