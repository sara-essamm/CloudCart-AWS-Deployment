<?php
require __DIR__ . '/../src/bootstrap.php';

if (is_logged_in()) {
    redirect('index.php');
}

$email = '';
$next = safe_page($_GET['next'] ?? null) ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $email    = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $next     = safe_page($_POST['next'] ?? null) ?? '';

    $user = db_one('SELECT * FROM users WHERE email = ?', [$email]);

    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);       // new session id after login
        unset($_SESSION['csrf']);          // and a fresh CSRF token
        $_SESSION['user_id']   = (int) $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['is_admin']  = (bool) $user['is_admin'];
        flash('success', 'Welcome back, ' . $user['name'] . '!');
        redirect($next !== '' ? $next : 'index.php');
    }

    flash('danger', 'Incorrect email or password.');
}

$page_title = 'Login';
require __DIR__ . '/../src/header.php';
?>

<section class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card form-card">
                <div class="card-body p-4">
                    <h2 class="section-title mb-4">Log in</h2>

                    <form method="post" action="login.php">
                        <?= csrf_field() ?>
                        <input type="hidden" name="next" value="<?= e($next) ?>">

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email"
                                   value="<?= e($email) ?>" required autofocus>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Log in</button>
                    </form>

                    <p class="text-center mt-3 mb-0 small">
                        New to CloudCart? <a href="register.php">Create an account</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../src/footer.php'; ?>
