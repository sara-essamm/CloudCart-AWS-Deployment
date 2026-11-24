<?php
/**
 * product_card.php - one product card. Include it inside a loop that sets $product.
 * Used by index.php and products.php.
 */
?>
<div class="col-6 col-md-4 col-lg-3">
    <div class="card h-100 product-card">
        <img src="<?= image_src($product['image_url']) ?>" class="card-img-top product-img"
             alt="<?= e($product['name']) ?>">
        <div class="card-body d-flex flex-column">
            <h5 class="card-title product-name"><?= e($product['name']) ?></h5>
            <p class="card-text text-muted small product-desc"><?= e($product['description']) ?></p>
            <div class="mt-auto">
                <p class="price mb-2"><?= e(money($product['price'])) ?></p>
                <?php if ((int) $product['stock'] > 0): ?>
                <form method="post" action="cart_action.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                    <button type="submit" class="btn btn-primary w-100">Add to Cart</button>
                </form>
                <?php else: ?>
                <button type="button" class="btn btn-secondary w-100" disabled>Out of Stock</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
