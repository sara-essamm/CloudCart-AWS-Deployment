<?php
require __DIR__ . '/../src/bootstrap.php';

$products = db_all('SELECT * FROM products ORDER BY id LIMIT 8');

$page_title = 'Home';
require __DIR__ . '/../src/header.php';
?>

<!-- Simple hero banner -->
<section class="hero text-center">
    <div class="container">
        <h1 class="fw-bold">Welcome to CloudCart</h1>
        <p class="lead mb-4">Quality electronics and accessories, delivered to your door.</p>
        <a href="products.php" class="btn btn-light btn-lg">Shop All Products</a>
    </div>
</section>

<section class="container mt-5">
    <h2 class="section-title mb-4">Featured Products</h2>

    <?php if ($products): ?>
    <div class="row g-4">
        <?php foreach ($products as $product): ?>
            <?php include __DIR__ . '/../src/product_card.php'; ?>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <p class="text-muted">No products are available yet. Please check back soon.</p>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/../src/footer.php'; ?>
