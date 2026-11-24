<?php
/**
 * cart_action.php - handles the cart buttons (POST only):
 *   action=add       add one of a product (from the product cards)
 *   action=increase  +1 quantity   (cart page)
 *   action=decrease  -1 quantity; removes the item at 0 (cart page)
 *   action=remove    remove the item (cart page)
 */
require __DIR__ . '/../src/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('cart.php');
}
require_login();
verify_csrf();

$userId    = current_user_id();
$action    = (string) ($_POST['action'] ?? '');
$productId = (int) ($_POST['product_id'] ?? 0);

if ($action === 'add') {
    $product = db_one('SELECT id, name, stock FROM products WHERE id = ?', [$productId]);
    if (!$product) {
        show_error(404, 'Not Found', 'That product does not exist.');
    }
    if ((int) $product['stock'] <= 0) {
        flash('warning', 'Sorry, ' . $product['name'] . ' is out of stock.');
        redirect_back();
    }

    $existing = db_one('SELECT quantity FROM cart_items WHERE user_id = ? AND product_id = ?',
                       [$userId, $productId]);
    try {
        if ($existing) {
            if ((int) $existing['quantity'] >= (int) $product['stock']) {
                flash('warning', 'You already have the maximum available quantity of ' . $product['name'] . '.');
                redirect_back();
            }
            db_exec('UPDATE cart_items SET quantity = quantity + 1 WHERE user_id = ? AND product_id = ?',
                    [$userId, $productId]);
        } else {
            db_exec('INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?, ?, 1)',
                    [$userId, $productId]);
        }
    } catch (PDOException $ex) {
        if ($ex->getCode() !== '23000') {
            throw $ex;   // a double-click already added it - ignore only that case
        }
    }

    flash('success', $product['name'] . ' added to your cart.');
    redirect_back();
}

// The remaining actions work on an item that is already in the cart.
$item = db_one(
    'SELECT c.quantity, p.stock FROM cart_items c JOIN products p ON p.id = c.product_id
     WHERE c.user_id = ? AND c.product_id = ?',
    [$userId, $productId]
);
if (!$item) {
    flash('warning', 'That item is not in your cart.');
    redirect('cart.php');
}

if ($action === 'increase') {
    if ((int) $item['quantity'] + 1 > (int) $item['stock']) {
        flash('warning', 'Only ' . (int) $item['stock'] . ' in stock.');
    } else {
        db_exec('UPDATE cart_items SET quantity = quantity + 1 WHERE user_id = ? AND product_id = ?',
                [$userId, $productId]);
    }
} elseif ($action === 'decrease') {
    if ((int) $item['quantity'] <= 1) {
        db_exec('DELETE FROM cart_items WHERE user_id = ? AND product_id = ?', [$userId, $productId]);
    } else {
        db_exec('UPDATE cart_items SET quantity = quantity - 1 WHERE user_id = ? AND product_id = ?',
                [$userId, $productId]);
    }
} elseif ($action === 'remove') {
    db_exec('DELETE FROM cart_items WHERE user_id = ? AND product_id = ?', [$userId, $productId]);
    flash('info', 'Item removed from your cart.');
}

redirect('cart.php');
