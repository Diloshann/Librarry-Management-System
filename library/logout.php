<?php
require_once 'includes/config.php';

// Log the logout
if (isset($_SESSION['user_id'])) {
    logAction($conn, $_SESSION['role'], $_SESSION['user_id'], 'LOGOUT', 'User logged out');
} elseif (isset($_SESSION['member_id'])) {
    logAction($conn, 'member', $_SESSION['member_id'], 'LOGOUT', 'Member logged out');
}

session_destroy();
redirect('index.php');
?>
