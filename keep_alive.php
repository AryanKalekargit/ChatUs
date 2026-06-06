<?php
// keep_alive.php
// This endpoint is meant to be hit by a Vercel Cron Job every 2-3 days
// to keep the Supabase free-tier project from pausing due to inactivity.

// Secure the endpoint visually so it can only be run by Vercel Cron
$cron_secret = getenv('CRON_SECRET');
if ($cron_secret) {
    $auth_header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if ($auth_header !== 'Bearer ' . $cron_secret) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit();
    }
}

require_once 'config/database.php';

try {
    $db = (new Database())->getConnection();
    // A simple query to wake up/keep active the Supabase instance
    $stmt = $db->query("SELECT 1");
    if ($stmt) {
        echo json_encode(['status' => 'success', 'message' => 'Supabase is alive and active.']);
    } else {
        throw new Exception("Query failed");
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $e->getMessage()]);
}
?>
