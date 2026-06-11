<?php
// includes/auth.php - Role-based session guards
require_once __DIR__ . '/config.php';

function requireAdmin() {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        redirect('../index.php?msg=Please+login+as+Admin');
    }
}

function requireLibrarian() {
    if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','librarian'])) {
        redirect('../index.php?msg=Please+login+as+Librarian');
    }
}

function requireMember() {
    if (!isset($_SESSION['member_id'])) {
        redirect('../index.php?msg=Please+login+as+Member');
    }
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) || isset($_SESSION['member_id']);
}
?>
