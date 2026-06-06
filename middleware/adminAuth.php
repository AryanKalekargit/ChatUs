<?php
// middleware/adminAuth.php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth.php");
    exit();
}
?>
