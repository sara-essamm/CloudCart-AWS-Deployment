<?php
require __DIR__ . '/../src/bootstrap.php';

require_post();   // logout is a POST form (with CSRF token)

$_SESSION = [];                 // forget the user
session_regenerate_id(true);    // and start a fresh session
flash('info', 'You have been logged out.');
redirect('index.php');
