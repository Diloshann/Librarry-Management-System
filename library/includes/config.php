<?php
// ============================================================
// ATI Library Management System
// includes/config.php - Database connection & global config
// ============================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'ati_library');

define('SITE_NAME', 'ATI Library');
define('BASE_URL', 'http://localhost/library/');
define('UPLOAD_PATH', __DIR__ . '/../assets/uploads/books/');
define('UPLOAD_URL', BASE_URL . 'assets/uploads/books/');

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Connect to database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("<div style='font-family:Arial;padding:20px;color:red;'>
        <h2>Database Connection Failed</h2>
        <p>" . $conn->connect_error . "</p>
        <p>Please check your XAMPP MySQL is running and the database <strong>" . DB_NAME . "</strong> exists.</p>
    </div>");
}
$conn->set_charset('utf8mb4');

// -----------------------------------------------
// Helper: Audit log
// -----------------------------------------------
function logAction($conn, $userType, $userId, $action, $details = '') {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $stmt = $conn->prepare("INSERT INTO audit_log (user_type, user_id, action, details, ip_address) VALUES (?,?,?,?,?)");
    $stmt->bind_param("sisss", $userType, $userId, $action, $details, $ip);
    $stmt->execute();
    $stmt->close();
}

// -----------------------------------------------
// Helper: Get setting value
// -----------------------------------------------
function getSetting($conn, $key) {
    $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row ? $row['setting_value'] : null;
}

// -----------------------------------------------
// Helper: Sanitize input
// -----------------------------------------------
function clean($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// -----------------------------------------------
// Helper: Redirect
// -----------------------------------------------
function redirect($url) {
    header("Location: $url");
    exit();
}

// -----------------------------------------------
// Helper: Flash messages
// -----------------------------------------------
function setFlash($type, $msg) {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

// -----------------------------------------------
// Helper: Check overdue & update fines
// -----------------------------------------------
function updateOverdueStatus($conn) {
    $finePerDay = getSetting($conn, 'fine_per_day');
    // Mark overdue
    $conn->query("UPDATE borrow_transactions SET status='overdue'
        WHERE status='borrowed' AND due_date < CURDATE()");
    // Create fine records for overdue not yet fined
    $res = $conn->query("SELECT bt.id, bt.member_id, DATEDIFF(CURDATE(), bt.due_date) AS overdue_days
        FROM borrow_transactions bt
        LEFT JOIN fines f ON f.transaction_id = bt.id
        WHERE bt.status='overdue' AND f.id IS NULL");
    while ($row = $res->fetch_assoc()) {
        $days = $row['overdue_days'];
        $total = $days * $finePerDay;
        $stmt = $conn->prepare("INSERT INTO fines (transaction_id, member_id, overdue_days, fine_per_day, total_fine, balance)
            VALUES (?,?,?,?,?,?)");
        $stmt->bind_param("iiiddd", $row['id'], $row['member_id'], $days, $finePerDay, $total, $total);
        $stmt->execute();
        $stmt->close();
    }
}
?>
