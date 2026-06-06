<?php
// middleware/auth.php
require_once dirname(__DIR__) . '/config/session.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit();
}
?>
