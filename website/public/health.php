<?php
/**
 * health.php - for the Application Load Balancer health check.
 *   /health.php        -> is PHP running?         (use this one for the target group)
 *   /health.php?db=1   -> also tests the database (handy when testing RDS)
 *
 * It does not start a session, so it is fast and works even if the database is down.
 */
require __DIR__ . '/../src/config.php';

header('Content-Type: application/json');

if (isset($_GET['db'])) {
    require __DIR__ . '/../src/db.php';
    if (db_check()) {
        echo json_encode(['status' => 'ok', 'database' => 'connected']);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'database' => 'unreachable']);
    }
    exit;
}

echo json_encode(['status' => 'ok']);
