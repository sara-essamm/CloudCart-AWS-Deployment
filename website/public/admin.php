<?php
/**
 * admin.php - basic admin page: add, edit and delete products.
 * Only users with is_admin = 1 can open it.
 */
require __DIR__ . '/../src/bootstrap.php';

require_admin();

/** Validate the product form. Returns [clean_values, errors]. */
function parse_product(array $input): array
{
    $errors = [];
    $name        = trim((string) ($input['name'] ?? ''));
    $description = trim((string) ($input['description'] ?? ''));
    $imageUrl    = trim((string) ($input['image_url'] ?? ''));
    $priceText   = trim((string) ($input['price'] ?? ''));
    $stockText   = trim((string) ($input['stock'] ?? ''));

    if ($imageUrl === '') {
        $imageUrl = DEFAULT_IMAGE;
    }

    if ($name === '') {
        $errors[] = 'Product name is required.';
    } elseif (mb_strlen($name) > 150) {
        $errors[] = 'Product name must be 150 characters or fewer.';
    }

    if (mb_strlen($description) > 2000) {
        $errors[] = 'Description must be 2000 characters or fewer.';
    }

    if (strlen($imageUrl) > 255) {
        $errors[] = 'Image path/URL must be 255 characters or fewer.';
    } elseif (!preg_match('#^https?://#i', $imageUrl)
              && (strpos($imageUrl, ':') !== false || strpos($imageUrl, '..') !== false)) {
        $errors[] = 'Image must be a path inside public/ (images/photo.jpg) or an http(s) URL.';
    }

    $price = null;
    if (!preg_match('/^\d{1,8}(\.\d{1,2})?$/', $priceText) || (float) $priceText <= 0) {
        $errors[] = 'Price must be a number greater than 0, e.g. 19.99.';
    } else {
        $price = number_format((float) $priceText, 2, '.', '');
    }

    $stock = null;
    if (!preg_match('/^\d{1,7}$/', $stockText)) {
        $errors[] = 'Stock must be a whole number (0 or more).';
    } else {
        $stock = (int) $stockText;
    }

    return [
        ['name' => $name, 'description' => $description, 'price' => $price,
         'stock' => $stock, 'image_url' => $imageUrl],
        $errors,
    ];
}

$formValues = [];     // what to show in the form
$editId     = 0;      // 0 = "add" mode, otherwise the id being edited

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action    = (string) ($_POST['action'] ?? '');
    $productId = (int) ($_POST['product_id'] ?? 0);

    if ($action === 'delete') {
        $product = db_one('SELECT name FROM products WHERE id = ?', [$productId]);
        if (!$product) {
            show_error(404, 'Not Found', 'That product does not exist.');
        }
        db_exec('DELETE FROM products WHERE id = ?', [$productId]);
        flash('info', "Product '" . $product['name'] . "' deleted.");
        redirect('admin.php');
    }

    if ($action === 'add' || $action === 'edit') {
        if ($action === 'edit' && !db_one('SELECT id FROM products WHERE id = ?', [$productId])) {
            show_error(404, 'Not Found', 'That product does not exist.');
        }

        [$clean, $errors] = parse_product($_POST);

        if (!$errors) {
            if ($action === 'add') {
                db_exec(
                    'INSERT INTO products (name, description, price, image_url, stock) VALUES (?, ?, ?, ?, ?)',
                    [$clean['name'], $clean['description'], $clean['price'], $clean['image_url'], $clean['stock']]
                );
                flash('success', "Product '" . $clean['name'] . "' added.");
            } else {
                db_exec(
                    'UPDATE products SET name = ?, description = ?, price = ?, image_url = ?, stock = ? WHERE id = ?',
                    [$clean['name'], $clean['description'], $clean['price'], $clean['image_url'],
                     $clean['stock'], $productId]
                );
                flash('success', "Product '" . $clean['name'] . "' updated.");
            }
            redirect('admin.php');
        }

        // Validation failed: show the errors and keep what the admin typed.
        foreach ($errors as $message) {
            flash('danger', $message);
        }
        http_response_code(400);
        $formValues = [
            'name' => (string) ($_POST['name'] ?? ''), 'description' => (string) ($_POST['description'] ?? ''),
            'price' => (string) ($_POST['price'] ?? ''), 'stock' => (string) ($_POST['stock'] ?? ''),
            'image_url' => (string) ($_POST['image_url'] ?? ''),
        ];
        $editId = ($action === 'edit') ? $productId : 0;
    }
} elseif (isset($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    $product = db_one('SELECT * FROM products WHERE id = ?', [$editId]);
    if (!$product) {
        flash('warning', 'That product does not exist.');
        redirect('admin.php');
    }
    $formValues = [
        'name' => $product['name'], 'description' => (string) $product['description'],
        'price' => number_format((float) $product['price'], 2, '.', ''),
        'stock' => $product['stock'], 'image_url' => $product['image_url'],
    ];
}

$products = db_all('SELECT * FROM products ORDER BY id');

$page_title = 'Admin';
require __DIR__ . '/../src/header.php';
?>

<section class="container mt-4">
    <h2 class="section-title mb-4">Admin &ndash; Manage Products</h2>

    <div class="row g-4">
        <!-- Add / edit form -->
        <div class="col-lg-4">
            <div class="card form-card">
                <div class="card-body p-4">
                    <h4 class="mb-3"><?= $editId ? 'Edit product #' . (int) $editId : 'Add a product' ?></h4>

                    <form method="post" action="admin.php">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="<?= $editId ? 'edit' : 'add' ?>">
                        <input type="hidden" name="product_id" value="<?= (int) $editId ?>">

                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="name" name="name"
                                   value="<?= e($formValues['name'] ?? '') ?>" maxlength="150" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"
                                      maxlength="2000"><?= e($formValues['description'] ?? '') ?></textarea>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label for="price" class="form-label">Price ($)</label>
                                <input type="number" class="form-control" id="price" name="price"
                                       value="<?= e($formValues['price'] ?? '') ?>" min="0.01" step="0.01" required>
                            </div>
                            <div class="col-6 mb-3">
                                <label for="stock" class="form-label">Stock</label>
                                <input type="number" class="form-control" id="stock" name="stock"
                                       value="<?= e($formValues['stock'] ?? '') ?>" min="0" step="1" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="image_url" class="form-label">Image path or URL</label>
                            <input type="text" class="form-control" id="image_url" name="image_url"
                                   value="<?= e($formValues['image_url'] ?? '') ?>" maxlength="255"
                                   placeholder="images/placeholder.svg">
                            <div class="form-text">
                                A file inside <code>public/</code> (e.g. <code>images/mouse.svg</code>)
                                or a full image URL (later: your S3 link). Leave empty for the default image.
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <?= $editId ? 'Save changes' : 'Add product' ?>
                        </button>
                        <?php if ($editId): ?>
                        <a href="admin.php" class="btn btn-outline-secondary w-100 mt-2">Cancel</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>

        <!-- Product list -->
        <div class="col-lg-8">
            <div class="table-responsive">
                <table class="table align-middle admin-table">
                    <thead>
                        <tr>
                            <th scope="col">Product</th>
                            <th scope="col" class="text-end">Price</th>
                            <th scope="col" class="text-center">Stock</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <img src="<?= image_src($product['image_url']) ?>" alt="" class="cart-thumb">
                                    <span class="fw-semibold"><?= e($product['name']) ?></span>
                                </div>
                            </td>
                            <td class="text-end"><?= e(money($product['price'])) ?></td>
                            <td class="text-center"><?= (int) $product['stock'] ?></td>
                            <td class="text-end text-nowrap">
                                <a href="admin.php?edit=<?= (int) $product['id'] ?>"
                                   class="btn btn-outline-primary btn-sm">Edit</a>
                                <form method="post" action="admin.php" class="d-inline"
                                      data-confirm="<?= e("Delete '" . $product['name'] . "'? This cannot be undone.") ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                                    <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (!$products): ?>
                        <tr><td colspan="4" class="text-center text-muted py-4">No products yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../src/footer.php'; ?>
