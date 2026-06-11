<?php
require_once '../includes/auth.php';
requireAdmin();

$pageTitle = 'System Settings';
$activePage = 'settings';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $keys = ['fine_per_day','borrow_days','max_books_per_member','library_name','library_email','library_phone'];
    foreach ($keys as $k) {
        if (isset($_POST[$k])) {
            $val = clean($_POST[$k]);
            $stmt = $conn->prepare("UPDATE settings SET setting_value=? WHERE setting_key=?");
            $stmt->bind_param("ss",$val,$k);
            $stmt->execute(); $stmt->close();
        }
    }
    setFlash('success','Settings saved.');
    logAction($conn,'admin',$_SESSION['user_id'],'UPDATE_SETTINGS','Settings updated');
    redirect('settings.php');
}

// Load settings
$settingsResult = $conn->query("SELECT * FROM settings");
$s = [];
while ($row = $settingsResult->fetch_assoc()) $s[$row['setting_key']] = $row['setting_value'];

include '../includes/layout.php';
?>

<div class="card" style="max-width:560px;">
  <div class="card-header"><h3>⚙️ System Settings</h3></div>
  <div class="card-body">
    <form method="POST">
      <div class="form-group">
        <label>Library Name</label>
        <input type="text" name="library_name" class="form-control" value="<?= htmlspecialchars($s['library_name']??'ATI Library') ?>">
      </div>
      <div class="form-group">
        <label>Library Email</label>
        <input type="email" name="library_email" class="form-control" value="<?= htmlspecialchars($s['library_email']??'') ?>">
      </div>
      <div class="form-group">
        <label>Library Phone</label>
        <input type="text" name="library_phone" class="form-control" value="<?= htmlspecialchars($s['library_phone']??'') ?>">
      </div>
      <hr class="divider">
      <div class="form-group">
        <label>Fine Per Day (Rs.) *</label>
        <input type="number" name="fine_per_day" class="form-control" value="<?= $s['fine_per_day']??50 ?>" min="1">
      </div>
      <div class="form-group">
        <label>Borrow Period (Days) *</label>
        <input type="number" name="borrow_days" class="form-control" value="<?= $s['borrow_days']??14 ?>" min="1">
      </div>
      <div class="form-group">
        <label>Max Books Per Member *</label>
        <input type="number" name="max_books_per_member" class="form-control" value="<?= $s['max_books_per_member']??3 ?>" min="1">
      </div>
      <button type="submit" class="btn btn-primary">💾 Save Settings</button>
    </form>
  </div>
</div>

<?php include '../includes/layout_end.php'; ?>
