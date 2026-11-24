<?php
/**
 * bootstrap.php - every page includes this file first.
 * It loads the settings, the database helpers and starts the session.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

// If something unexpected goes wrong (e.g. the database is down),
// log the details and show a friendly page instead of a blank screen.
set_exception_handler(function (Throwable $e) {
    error_log('CloudCart error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    if (APP_DEBUG) {
        echo '<pre>' . e((string) $e) . '</pre>';
        exit;
    }
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Server Error | CloudCart</title>'
       . '<meta name="viewport" content="width=device-width, initial-scale=1">'
       . '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">'
       . '</head><body class="text-center py-5"><h1 class="display-4 fw-bold">500</h1>'
       . '<p class="text-muted">Something went wrong on our side. Please try again in a moment.</p>'
       . '<a href="index.php" class="btn btn-primary">Back to Home</a></body></html>';
    exit;
});

start_session();
