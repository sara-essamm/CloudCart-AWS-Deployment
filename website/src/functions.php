<?php
/**
 * functions.php - helper functions used by all pages:
 * sessions, flash messages, CSRF protection, login checks, cart, formatting.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/DbSessionHandler.php';

// =====================================================================
// Output helpers
// =====================================================================

/** Escape text before printing it in HTML (prevents XSS). Use e() on ALL user data. */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Price as text:  19.9 -> $19.90 */
function money($value): string
{
    return '$' . number_format((float) $value, 2);
}

/** Convert a price like "79.99" to whole cents (7999) so totals have no rounding errors. */
function to_cents($value): int
{
    return (int) round(((float) $value) * 100);
}

/** Convert cents back to a "79.99" string. */
function from_cents(int $cents): string
{
    return number_format($cents / 100, 2, '.', '');
}

/**
 * Turn a product's image_url into something the browser can load.
 * Full URLs (e.g. an Amazon S3 link) are used as they are; anything else is a
 * file inside the "public" folder (e.g. images/mouse.svg).
 */
function image_src(?string $path): string
{
    if ($path === null || $path === '') {
        $path = DEFAULT_IMAGE;
    }
    if (preg_match('#^https?://#i', $path)) {
        return e($path);
    }
    return e(ltrim($path, '/'));
}

// =====================================================================
// Sessions, flash messages, redirects
// =====================================================================

function is_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }
    // Behind the Application Load Balancer the ALB tells us the original protocol.
    return BEHIND_PROXY && (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
}

function start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_set_save_handler(new DbSessionHandler(), true);
    session_name('cloudcart_session');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.gc_maxlifetime', '86400');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function session_ready(): bool
{
    return session_status() === PHP_SESSION_ACTIVE;
}

/** Save a message to show on the next page. Types: success, danger, warning, info. */
function flash(string $type, string $message): void
{
    if (session_ready()) {
        $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
    }
}

/** Get (and clear) the saved messages. */
function get_flashes(): array
{
    if (!session_ready() || empty($_SESSION['flash'])) {
        return [];
    }
    $messages = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $messages;
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/**
 * Only allow redirects to our own pages like "cart.php" or "products.php?q=x".
 * Returns the URL if it is safe, otherwise null (prevents "open redirect" attacks).
 */
function safe_page(?string $target): ?string
{
    if ($target !== null && preg_match('#^[A-Za-z0-9_\-]+\.php(\?[A-Za-z0-9_=&%.\-+]*)?$#', $target)) {
        return $target;
    }
    return null;
}

/** The page the visitor came from (only if it is on our own site), as a safe relative URL. */
function referrer_page(): ?string
{
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    $parts = parse_url($ref);
    if (!$parts || empty($parts['host']) || empty($parts['path'])) {
        return null;
    }
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $refHost = $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '');
    if (strcasecmp($refHost, $host) !== 0) {
        return null;
    }
    $page = basename($parts['path']);
    if (!empty($parts['query'])) {
        $page .= '?' . $parts['query'];
    }
    return safe_page($page);
}

function redirect_back(string $default = 'products.php'): void
{
    redirect(referrer_page() ?? $default);
}

// =====================================================================
// CSRF protection (stops other websites from submitting our forms)
// =====================================================================

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

/** Prints the hidden input every POST form must contain. */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/** Call at the top of every page that handles a POST form. */
function verify_csrf(): void
{
    $sent = $_POST['csrf_token'] ?? '';
    $expected = $_SESSION['csrf'] ?? '';
    if (!is_string($sent) || $sent === '' || $expected === '' || !hash_equals($expected, $sent)) {
        show_error(400, 'Bad Request',
            'Your session expired or the form was invalid. Please go back, refresh the page and try again.');
    }
}

/** For pages that only accept POST (cart actions, logout). */
function require_post(string $fallback = 'index.php'): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect($fallback);
    }
    verify_csrf();
}

// =====================================================================
// Login checks
// =====================================================================

function is_logged_in(): bool
{
    return session_ready() && !empty($_SESSION['user_id']);
}

function current_user_id(): int
{
    return (int) ($_SESSION['user_id'] ?? 0);
}

function require_login(): void
{
    if (is_logged_in()) {
        return;
    }
    flash('warning', 'Please log in to continue.');
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $next = basename($_SERVER['SCRIPT_NAME']);
        if (!empty($_SERVER['QUERY_STRING'])) {
            $next .= '?' . $_SERVER['QUERY_STRING'];
        }
    } else {
        $next = referrer_page();
    }
    $next = safe_page($next);
    redirect('login.php' . ($next ? '?next=' . urlencode($next) : ''));
}

/** Only admins may continue (checked in the database every time). */
function require_admin(): void
{
    require_login();
    $user = db_one('SELECT is_admin FROM users WHERE id = ?', [current_user_id()]);
    if (!$user || !$user['is_admin']) {
        show_error(403, 'Forbidden', 'You do not have permission to open this page.');
    }
}

// =====================================================================
// Cart
// =====================================================================

/** Number of items in the cart (for the navbar badge). */
function cart_count(): int
{
    if (!is_logged_in()) {
        return 0;
    }
    try {
        $row = db_one('SELECT COALESCE(SUM(quantity), 0) AS total FROM cart_items WHERE user_id = ?',
                      [current_user_id()]);
        return (int) $row['total'];
    } catch (Throwable $e) {
        return 0; // never break a page just because of the badge
    }
}

/** Returns [items, total_in_cents] for a user's cart. */
function get_cart(int $userId): array
{
    $items = db_all(
        'SELECT c.product_id, c.quantity, p.name, p.price, p.image_url, p.stock
         FROM cart_items c JOIN products p ON p.id = c.product_id
         WHERE c.user_id = ? ORDER BY c.id',
        [$userId]
    );
    $total = 0;
    foreach ($items as &$item) {
        $item['quantity'] = (int) $item['quantity'];
        $item['stock'] = (int) $item['stock'];
        $item['subtotal_cents'] = to_cents($item['price']) * $item['quantity'];
        $total += $item['subtotal_cents'];
    }
    unset($item);
    return [$items, $total];
}

// =====================================================================
// Error page
// =====================================================================

/** Show a friendly error page and stop. */
function show_error(int $code, string $title, string $message): void
{
    http_response_code($code);
    $page_title = $title;
    require __DIR__ . '/header.php';
    echo '<section class="container text-center py-5">';
    echo '<h1 class="display-4 fw-bold price">' . (int) $code . '</h1>';
    echo '<h2 class="mb-3">' . e($title) . '</h2>';
    echo '<p class="text-muted mb-4">' . e($message) . '</p>';
    echo '<a href="index.php" class="btn btn-primary">Back to Home</a>';
    echo '</section>';
    require __DIR__ . '/footer.php';
    exit;
}
