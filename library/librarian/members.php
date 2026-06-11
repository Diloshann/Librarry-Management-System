<?php
require_once '../includes/auth.php';
requireLibrarian();

$pageTitle = 'Manage Members';
$activePage = 'members';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $sid     = clean($_POST['student_id']);
        $name    = clean($_POST['full_name']);
        $nic     = clean($_POST['nic']);
        $dept    = clean($_POST['department']);
        $course  = clean($_POST['course']);
        $batch   = clean($_POST['batch']);
        $email   = clean($_POST['email']);
        $phone   = clean($_POST['phone']);
        $addr    = clean($_POST['address']);
        $uname   = clean($_POST['username']);
        $pass    = password_hash($_POST['password'], PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO members
            (student_id,full_name,nic,department,course,batch,email,phone,address,username,password)
            VALUES (?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("sssssssssss",$sid,$name,$nic,$dept,$course,$batch,$email,$phone,$addr,$uname,$pass);
        if ($stmt->execute()) {
            setFlash('success','Member registered successfully.');
            logAction($conn,$_SESSION['role'],$_SESSION['user_id'],'ADD_MEMBER',"Name: $name");
        } else {
            setFlash('danger','Error: ' . $conn->error);
        }
        $stmt->close();
    } elseif ($action === 'status') {
        $id     = (int)$_POST['member_id'];
        $status = clean($_POST['status']);
        $conn->query("UPDATE members SET status='$status' WHERE id=$id");
        setFlash('success','Member status updated.');
    } elseif ($action === 'delete') {
        $id = (int)$_POST['member_id'];
        // Only delete if no active borrows
        $active = $conn->query("SELECT COUNT(*) AS c FROM borrow_transactions WHERE member_id=$id AND status IN ('borrowed','overdue')")->fetch_assoc()['c'];
        if ($active > 0) {
            setFlash('danger','Cannot delete: member has active borrows.');
        } else {
            $conn->query("DELETE FROM members WHERE id=$id");
            setFlash('success','Member deleted.');
        }
    }
    redirect('members.php');
}

$search = clean($_GET['q'] ?? '');
$where = $search ? "WHERE student_id LIKE '%$search%' OR full_name LIKE '%$search%' OR email LIKE '%$search%'" : '';
$members = $conn->query("SELECT * FROM members $where ORDER BY full_name");

include '../includes/layout.php';
?>

<div class="search-bar">
  <form method="GET" style="display:flex;gap:8px;">
    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" class="form-control" placeholder="🔍 Search name, student ID, email...">
    <button class="btn btn-primary" type="submit">Search</button>
    <a href="members.php" class="btn btn-secondary">Clear</a>
  </form>
  <button class="btn btn-success" onclick="openModal('addMemberModal')" style="margin-left:auto;">➕ Add Member</button>
</div>

<div class="card">
  <div class="card-header"><h3>👥 Members (<?= $members->num_rows ?>)</h3></div>
  <div class="card-body" style="padding:0;">
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>Student ID</th><th>Name</th><th>Course / Batch</th><th>Email</th><th>Phone</th><th>Status</th><th>Actions</th></tr>
        </thead>
        <tbody>
          <?php if ($members->num_rows === 0): ?>
            <tr><td colspan="7"><div class="empty-state"><div class="icon">👥</div>No members found</div></td></tr>
          <?php else: while ($m = $members->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($m['student_id']) ?></td>
              <td><strong><?= htmlspecialchars($m['full_name']) ?></strong></td>
              <td><?= htmlspecialchars($m['course']) ?> / <?= htmlspecialchars($m['batch']) ?></td>
              <td><?= htmlspecialchars($m['email']) ?></td>
              <td><?= htmlspecialchars($m['phone']) ?></td>
              <td>
                <?php $sc = $m['status']==='active'?'success':($m['status']==='suspended'?'warning':'secondary'); ?>
                <span class="badge badge-<?= $sc ?>"><?= ucfirst($m['status']) ?></span>
              </td>
              <td>
                <a href="member_detail.php?id=<?= $m['id'] ?>" class="btn btn-sm btn-info">👁️</a>
                <!-- Status toggle -->
                <form method="POST" style="display:inline;">
                  <input type="hidden" name="action" value="status">
                  <input type="hidden" name="member_id" value="<?= $m['id'] ?>">
                  <?php if ($m['status'] === 'active'): ?>
                    <input type="hidden" name="status" value="suspended">
                    <button class="btn btn-sm btn-warning" type="submit" title="Suspend">⛔</button>
                  <?php else: ?>
                    <input type="hidden" name="status" value="active">
                    <button class="btn btn-sm btn-success" type="submit" title="Activate">✅</button>
                  <?php endif; ?>
                </form>
                <form method="POST" style="display:inline;" onsubmit="return confirmDelete('Delete this member?')">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="member_id" value="<?= $m['id'] ?>">
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

<!-- ADD MEMBER MODAL -->
<div class="modal-overlay" id="addMemberModal">
  <div class="modal" style="max-width:600px;">
    <div class="modal-header">
      <h3>➕ Register New Member</h3>
      <span class="modal-close" onclick="closeModal('addMemberModal')">✕</span>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="add">
      <div class="form-grid">
        <div class="form-group"><label>Student ID *</label><input type="text" name="student_id" class="form-control" required placeholder="e.g. VAV/IT/2324/F/001"></div>
        <div class="form-group"><label>Full Name *</label><input type="text" name="full_name" class="form-control" required></div>
        <div class="form-group"><label>NIC</label><input type="text" name="nic" class="form-control" placeholder="200012345678"></div>
        <div class="form-group"><label>Department</label><input type="text" name="department" class="form-control" value="IT"></div>
        <div class="form-group"><label>Course</label><input type="text" name="course" class="form-control" value="HND IT"></div>
        <div class="form-group"><label>Batch</label><input type="text" name="batch" class="form-control" placeholder="2023/24"></div>
        <div class="form-group"><label>Email</label><input type="email" name="email" class="form-control"></div>
        <div class="form-group"><label>Phone</label><input type="text" name="phone" class="form-control"></div>
        <div class="form-group"><label>Username *</label><input type="text" name="username" class="form-control" required></div>
        <div class="form-group"><label>Password *</label><input type="password" name="password" class="form-control" required></div>
      </div>
      <div class="form-group"><label>Address</label><textarea name="address" class="form-control" rows="2"></textarea></div>
      <div class="flex-row" style="justify-content:flex-end;margin-top:16px;">
        <button type="button" class="btn btn-secondary" onclick="closeModal('addMemberModal')">Cancel</button>
        <button type="submit" class="btn btn-success">Register Member</button>
      </div>
    </form>
  </div>
</div>

<?php include '../includes/layout_end.php'; ?>
