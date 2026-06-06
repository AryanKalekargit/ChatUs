<?php
require 'config/database.php';
try { 
    $db = (new Database())->getConnection(); 
    $db->exec('ALTER TABLE group_members ADD COLUMN IF NOT EXISTS nickname VARCHAR(50) DEFAULT NULL;'); 
    echo 'Success'; 
} catch(Exception $e) { 
    echo $e->getMessage(); 
}
?>
