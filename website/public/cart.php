<?php
require __DIR__ . '/../src/bootstrap.php';

require_login();

[$items, $totalCents] = get_cart(current_user_id());

$page_title = 'Your Cart';
require __DIR__ . '/../src/header.php';
?>

<section class="container mt-4">
    <h2 class="section-title mb-4">Shopping Cart</h2>

    <?php if ($items): ?>
    <div class="table-responsive">
        <table class="table align-middle cart-table">
            <thead>
                <tr>
                    <th scope="col">Product</th>
                    <th scope="col" class="text-end">Price</th>
                    <th scope="col" class="text-center">Quantity</th>
                    <th scope="col" class="text-end">Subtotal</th>
                    <th scope="col"><span class="visually-hidden">Remove</span></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-3">
                            <img src="<?= image_src($item['image_url']) ?>" alt="<?= e($item['name']) ?>" class="cart-thumb">
                            <span class="fw-semibold"><?= e($item['name']) ?></span>
                        </div>
                    </td>
                    <td class="text-end"><?= e(money($item['price'])) ?></td>
                    <td class="text-center">
                        <!-- Decrease / increase the quantity -->
                        <form method="post" action="cart_action.php" class="d-inline-flex align-items-center gap-2">
                            <?= csrf_field() ?>
                            <input type="hidden" name="product_id" value="<?= (int) $item['product_id'] ?>">
                            <button type="submit" name="action" value="decrease"
                                    class="btn btn-outline-primary btn-sm qty-btn"
                                    aria-label="Decrease quantity">&minus;</button>
                            <span class="fw-semibold qty-number"><?= (int) $item['quantity'] ?></span>
                            <button type="submit" name="action" value="increase"
                                    class="btn btn-outline-primary btn-sm qty-btn"
                                    aria-label="Increase quantity"
                                    <?= $item['quantity'] >= $item['stock'] ? 'disabled' : '' ?>>+</button>
                        </form>
                    </td>
                    <td class="text-end fw-semibold"><?= e(money(from_cents($item['subtotal_cents']))) ?></td>
                    <td class="text-end">
                        <form method="post" action="cart_action.php"
                              data-confirm="Remove this item from your cart?">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="remove">
                            <input type="hidden" name="product_id" value="<?= (int) $item['product_id'] ?>">
                            <button type="submit" class="btn btn-outline-danger btn-sm">Remove</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mt-3">
        <a href="products.php" class="btn btn-outline-primary">&larr; Continue Shopping</a>
        <div class="text-md-end">
            <p class="fs-4 mb-2">Total: <strong class="price"><?= e(money(from_cents($totalCents))) ?></strong></p>
            <a href="checkout.php" class="btn btn-primary btn-lg">Proceed to Checkout</a>
        </div>
    </div>

    <?php else: ?>
    <div class="text-center py-5">
        <p class="fs-4 mb-1">Your cart is empty.</p>
        <p class="text-muted">Find something you like and add it to your cart.</p>
        <a href="products.php" class="btn btn-primary">Browse Products</a>
    </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/../src/footer.php'; ?>
