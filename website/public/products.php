<?php
require __DIR__ . '/../src/bootstrap.php';

$query = trim((string) ($_GET['q'] ?? ''));

if ($query !== '') {
    $like = '%' . $query . '%';
    $products = db_all(
        'SELECT * FROM products WHERE name LIKE ? OR description LIKE ? ORDER BY name',
        [$like, $like]
    );
} else {
    $products = db_all('SELECT * FROM products ORDER BY name');
}

$page_title = 'Products';
require __DIR__ . '/../src/header.php';
?>

<section class="container mt-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <h2 class="section-title mb-0">Products</h2>

        <!-- Simple search -->
        <form method="get" action="products.php" class="d-flex search-form" role="search">
            <input type="search" name="q" value="<?= e($query) ?>" class="form-control me-2"
                   placeholder="Search products..." aria-label="Search products">
            <button type="submit" class="btn btn-primary">Search</button>
        </form>
    </div>

    <?php if ($query !== ''): ?>
    <p class="text-muted">
        <?= count($products) ?> result(s) for "<strong><?= e($query) ?></strong>"
        &middot; <a href="products.php">Show all products</a>
    </p>
    <?php endif; ?>

    <?php if ($products): ?>
    <div class="row g-4">
        <?php foreach ($products as $product): ?>
            <?php include __DIR__ . '/../src/product_card.php'; ?>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="text-center text-muted py-5">
        <p class="fs-5 mb-1">No products found.</p>
        <p>Try a different search word.</p>
    </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/../src/footer.php'; ?>
