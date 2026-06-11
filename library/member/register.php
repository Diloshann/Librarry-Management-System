<?php
require_once '../includes/config.php';

// Redirect if already logged in
if (isset($_SESSION['member_id'])) redirect('dashboard.php');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sid    = clean($_POST['student_id']);
    $name   = clean($_POST['full_name']);
    $nic    = clean($_POST['nic']);
    $dept   = clean($_POST['department']);
    $course = clean($_POST['course']);
    $batch  = clean($_POST['batch']);
    $email  = clean($_POST['email']);
    $phone  = clean($_POST['phone']);
    $addr   = clean($_POST['address']);
    $uname  = clean($_POST['username']);
    $pass   = $_POST['password'];
    $pass2  = $_POST['password2'];

    if ($pass !== $pass2) {
        $error = 'Passwords do not match.';
    } elseif (strlen($pass) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        // Check duplicate username / student_id
        $check = $conn->query("SELECT id FROM members WHERE username='$uname' OR student_id='$sid' LIMIT 1");
        if ($check->num_rows > 0) {
            $error = 'Username or Student ID already exists.';
        } else {
            $hashed = password_hash($pass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO members (student_id,full_name,nic,department,course,batch,email,phone,address,username,password) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param("sssssssssss",$sid,$name,$nic,$dept,$course,$batch,$email,$phone,$addr,$uname,$hashed);
            if ($stmt->execute()) {
                $success = 'Registration successful! You can now login.';
                logAction($conn,'member',$stmt->insert_id,'REGISTER',"Student: $sid");
            } else {
                $error = 'Registration failed: ' . $conn->error;
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Member Registration - ATI Library</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="login-page" style="padding:30px 0;">
  <div class="login-box" style="max-width:600px;">
    <div class="login-logo">
      <div style="font-size:44px;">📚</div>
      <h1>ATI Library</h1>
      <p>Student Registration</p>
    </div>

    <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
    <?php if ($success): ?>
      <div class="alert alert-success"><?= $success ?></div>
      <div style="text-align:center;"><a href="../index.php" class="btn btn-primary">Login Now</a></div>
    <?php else: ?>

    <form method="POST">
      <div class="form-grid">
        <div class="form-group">
          <label>Student ID *</label>
          <input type="text" name="student_id" class="form-control" required placeholder="e.g. VAV/IT/2324/F/033">
        </div>
        <div class="form-group">
          <label>Full Name *</label>
          <input type="text" name="full_name" class="form-control" required>
        </div>
        <div class="form-group">
          <label>NIC Number</label>
          <input type="text" name="nic" class="form-control" placeholder="200012345678">
        </div>
        <div class="form-group">
          <label>Department</label>
          <input type="text" name="department" class="form-control" value="IT">
        </div>
        <div class="form-group">
          <label>Course</label>
          <input type="text" name="course" class="form-control" value="HND IT">
        </div>
        <div class="form-group">
          <label>Batch</label>
          <input type="text" name="batch" class="form-control" placeholder="2023/24">
        </div>
        <div class="form-group">
          <label>Email</label>
          <input type="email" name="email" class="form-control">
        </div>
        <div class="form-group">
          <label>Phone</label>
          <input type="text" name="phone" class="form-control">
        </div>
        <div class="form-group">
          <label>Username *</label>
          <input type="text" name="username" class="form-control" required>
        </div>
        <div class="form-group">
          <label>Password *</label>
          <input type="password" name="password" class="form-control" required minlength="6">
        </div>
        <div class="form-group">
          <label>Confirm Password *</label>
          <input type="password" name="password2" class="form-control" required>
        </div>
      </div>
      <div class="form-group">
        <label>Address</label>
        <textarea name="address" class="form-control" rows="2"></textarea>
      </div>
      <button type="submit" class="btn btn-success" style="width:100%;justify-content:center;padding:11px;margin-top:8px;">
        🎓 Register
      </button>
    </form>
    <p style="text-align:center;margin-top:14px;font-size:13px;">
      Already have an account? <a href="../index.php" style="color:var(--primary);">Login here</a>
    </p>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
