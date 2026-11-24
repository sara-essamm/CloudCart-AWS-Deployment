<?php
require __DIR__ . '/../src/bootstrap.php';

require_login();

$orderId = (int) ($_GET['id'] ?? 0);

// The user_id check makes sure people can only open their OWN orders.
$order = db_one('SELECT * FROM orders WHERE id = ? AND user_id = ?', [$orderId, current_user_id()]);
if (!$order) {
    show_error(404, 'Not Found', 'That order was not found.');
}
$orderItems = db_all('SELECT * FROM order_items WHERE order_id = ? ORDER BY id', [$orderId]);

$page_title = 'Order Confirmed';
require __DIR__ . '/../src/header.php';
?>

<section class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card form-card">
                <div class="card-body p-4">
                    <h2 class="section-title mb-1">Order #<?= (int) $order['id'] ?> confirmed</h2>
                    <p class="text-muted">Thank you for shopping with CloudCart.</p>

                    <h5 class="mt-4">Items</h5>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th class="text-end">Price</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orderItems as $item): ?>
                                <tr>
                                    <td><?= e($item['product_name']) ?></td>
                                    <td class="text-end"><?= e(money($item['price'])) ?></td>
                                    <td class="text-center"><?= (int) $item['quantity'] ?></td>
                                    <td class="text-end"><?= e(money(from_cents(to_cents($item['price']) * (int) $item['quantity']))) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="3" class="text-end">Total</th>
                                    <th class="text-end price"><?= e(money($order['total_amount'])) ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <h5 class="mt-3">Shipping to</h5>
                    <address class="mb-4">
                        <?= e($order['shipping_name']) ?><br>
                        <?= e($order['shipping_address']) ?><br>
                        <?= e($order['shipping_city']) ?>, <?= e($order['shipping_postal_code']) ?>
                    </address>

                    <a href="products.php" class="btn btn-primary">Continue Shopping</a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../src/footer.php'; ?>
