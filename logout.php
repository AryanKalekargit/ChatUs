<?php
// logout.php
require_once 'controllers/AuthController.php';

$auth = new AuthController();
$auth->logout();

header("Location: auth.php");
exit();
?>
