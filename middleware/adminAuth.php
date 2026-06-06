<?php
// middleware/adminAuth.php
require_once dirname(__DIR__) . '/config/session.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth.php");
    exit();
}
?>
