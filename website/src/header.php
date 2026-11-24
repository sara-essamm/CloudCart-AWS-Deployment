<?php
/**
 * header.php - top of every page: <head>, navigation bar and flash messages.
 * Each page sets $page_title before including this file.
 */
$page_title = $page_title ?? 'Home';
$cartCount = cart_count();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($page_title) ?> | CloudCart</title>

    <!-- Bootstrap 5 (loaded from a CDN) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Our own styles -->
    <link href="css/style.css" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100">

    <!-- ===== Navigation bar ===== -->
    <nav class="navbar navbar-expand-lg navbar-dark cc-navbar">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
                <img src="images/logo.svg" alt="" width="32" height="32">
                <span class="fw-bold">CloudCart</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                    data-bs-target="#mainNav" aria-controls="mainNav"
                    aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="products.php">Products</a></li>
                    <?php if (session_ready() && !empty($_SESSION['is_admin'])): ?>
                    <li class="nav-item"><a class="nav-link" href="admin.php">Admin</a></li>
                    <?php endif; ?>
                </ul>

                <ul class="navbar-nav ms-auto align-items-lg-center">
                    <?php if (is_logged_in()): ?>
                    <li class="nav-item"><span class="navbar-text me-lg-3">Hi, <?= e($_SESSION['user_name'] ?? '') ?></span></li>
                    <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                    <li class="nav-item"><a class="nav-link" href="register.php">Register</a></li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="cart.php">
                            Cart
                            <?php if ($cartCount > 0): ?><span class="badge rounded-pill bg-light text-dark"><?= (int) $cartCount ?></span><?php endif; ?>
                        </a>
                    </li>
                    <?php if (is_logged_in()): ?>
                    <li class="nav-item">
                        <form method="post" action="logout.php" class="d-inline">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-outline-light btn-sm ms-lg-2 mt-2 mt-lg-0">Logout</button>
                        </form>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- ===== Page content ===== -->
    <main class="flex-grow-1">
        <div class="container pt-3">
            <?php foreach (get_flashes() as $flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show" role="alert">
                <?= e($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endforeach; ?>
        </div>
