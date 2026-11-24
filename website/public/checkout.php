<?php
require __DIR__ . '/../src/bootstrap.php';

require_login();

/** Used to cancel the checkout transaction with a message for the user. */
class CheckoutException extends Exception
{
}

$userId = current_user_id();
[$items, $totalCents] = get_cart($userId);

if (!$items) {
    flash('warning', 'Your cart is empty.');
    redirect('cart.php');
}

$form = [
    'shipping_name'        => (string) ($_SESSION['user_name'] ?? ''),
    'shipping_address'     => '',
    'shipping_city'        => '',
    'shipping_postal_code' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    foreach ($form as $field => $unused) {
        $form[$field] = trim((string) ($_POST[$field] ?? ''));
    }

    $limits = ['shipping_name' => 100, 'shipping_address' => 255,
               'shipping_city' => 100, 'shipping_postal_code' => 20];
    $labels = ['shipping_name' => 'Full name', 'shipping_address' => 'Address',
               'shipping_city' => 'City', 'shipping_postal_code' => 'Postal code'];

    $errors = [];
    foreach ($limits as $field => $maxLength) {
        if ($form[$field] === '') {
            $errors[] = $labels[$field] . ' is required.';
        } elseif (mb_strlen($form[$field]) > $maxLength) {
            $errors[] = $labels[$field] . ' is too long.';
        }
    }

    if (!$errors) {
        $pdo = db();
        try {
            // Everything below succeeds together or is rolled back together.
            $pdo->beginTransaction();

            $cartRows = db_all(
                'SELECT c.product_id, c.quantity, p.name, p.price
                 FROM cart_items c JOIN products p ON p.id = c.product_id
                 WHERE c.user_id = ? ORDER BY c.product_id',
                [$userId]
            );
            if (!$cartRows) {
                throw new CheckoutException('Your cart is empty.');
            }

            $orderCents = 0;
            foreach ($cartRows as $row) {
                // Reduce stock only if enough is left. Doing the check inside the
                // UPDATE means two buyers can never take the last item.
                $changed = db_exec(
                    'UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?',
                    [$row['quantity'], $row['product_id'], $row['quantity']]
                );
                if ($changed !== 1) {
                    throw new CheckoutException(
                        "Sorry, there is not enough stock left for '" . $row['name'] . "'. Please update your cart."
                    );
                }
                $orderCents += to_cents($row['price']) * (int) $row['quantity'];
            }

            $orderId = db_insert(
                'INSERT INTO orders (user_id, total_amount, shipping_name, shipping_address,
                                     shipping_city, shipping_postal_code)
                 VALUES (?, ?, ?, ?, ?, ?)',
                [$userId, from_cents($orderCents), $form['shipping_name'], $form['shipping_address'],
                 $form['shipping_city'], $form['shipping_postal_code']]
            );

            foreach ($cartRows as $row) {
                db_exec(
                    'INSERT INTO order_items (order_id, product_id, product_name, price, quantity)
                     VALUES (?, ?, ?, ?, ?)',
                    [$orderId, $row['product_id'], $row['name'], $row['price'], $row['quantity']]
                );
            }

            db_exec('DELETE FROM cart_items WHERE user_id = ?', [$userId]);

            $pdo->commit();
        } catch (CheckoutException $ex) {
            $pdo->rollBack();
            flash('danger', $ex->getMessage());
            redirect('cart.php');
        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $ex;
        }

        flash('success', 'Thank you! Your order has been placed.');
        redirect('order.php?id=' . $orderId);
    }

    foreach ($errors as $message) {
        flash('danger', $message);
    }
}

$page_title = 'Checkout';
require __DIR__ . '/../src/header.php';
?>

<section class="container mt-4">
    <h2 class="section-title mb-4">Checkout</h2>

    <div class="row g-4">
        <!-- Shipping address form -->
        <div class="col-lg-7">
            <div class="card form-card">
                <div class="card-body p-4">
                    <h4 class="mb-3">Shipping address</h4>

                    <form method="post" action="checkout.php" data-single-submit>
                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label for="shipping_name" class="form-label">Full name</label>
                            <input type="text" class="form-control" id="shipping_name" name="shipping_name"
                                   value="<?= e($form['shipping_name']) ?>" maxlength="100" required>
                        </div>
                        <div class="mb-3">
                            <label for="shipping_address" class="form-label">Address</label>
                            <input type="text" class="form-control" id="shipping_address" name="shipping_address"
                                   value="<?= e($form['shipping_address']) ?>" maxlength="255" required>
                        </div>
                        <div class="row">
                            <div class="col-md-7 mb-3">
                                <label for="shipping_city" class="form-label">City</label>
                                <input type="text" class="form-control" id="shipping_city" name="shipping_city"
                                       value="<?= e($form['shipping_city']) ?>" maxlength="100" required>
                            </div>
                            <div class="col-md-5 mb-3">
                                <label for="shipping_postal_code" class="form-label">Postal code</label>
                                <input type="text" class="form-control" id="shipping_postal_code"
                                       name="shipping_postal_code" value="<?= e($form['shipping_postal_code']) ?>"
                                       maxlength="20" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg w-100">Confirm Order</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Cart summary -->
        <div class="col-lg-5">
            <div class="card form-card">
                <div class="card-body p-4">
                    <h4 class="mb-3">Order summary</h4>

                    <ul class="list-group list-group-flush mb-3">
                        <?php foreach ($items as $item): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-start px-0">
                            <div>
                                <div class="fw-semibold"><?= e($item['name']) ?></div>
                                <div class="small text-muted"><?= (int) $item['quantity'] ?> &times; <?= e(money($item['price'])) ?></div>
                            </div>
                            <span><?= e(money(from_cents($item['subtotal_cents']))) ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>

                    <div class="d-flex justify-content-between fs-5">
                        <span>Total</span>
                        <strong class="price"><?= e(money(from_cents($totalCents))) ?></strong>
                    </div>
                    <a href="cart.php" class="small d-inline-block mt-3">&larr; Edit cart</a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../src/footer.php'; ?>
