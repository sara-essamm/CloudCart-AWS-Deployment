<?php
require __DIR__ . '/../src/bootstrap.php';

if (is_logged_in()) {
    redirect('index.php');
}

$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $name     = trim((string) ($_POST['name'] ?? ''));
    $email    = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $confirm  = (string) ($_POST['confirm_password'] ?? '');

    $errors = [];
    if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
        $errors[] = 'Name must be between 2 and 100 characters.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    } elseif (strlen($password) > 72) {
        $errors[] = 'Password must be 72 characters or fewer.';  // bcrypt limit
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    // Prevent duplicate email registrations
    if (!$errors && db_one('SELECT id FROM users WHERE email = ?', [$email])) {
        $errors[] = 'An account with this email already exists.';
    }

    if (!$errors) {
        try {
            db_exec(
                'INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)',
                [$name, $email, password_hash($password, PASSWORD_DEFAULT)]
            );
        } catch (PDOException $ex) {
            if ($ex->getCode() === '23000') {
                // Two people registering the same email at the same moment:
                // the UNIQUE constraint in MySQL protects us.
                $errors[] = 'An account with this email already exists.';
            } else {
                throw $ex;
            }
        }
    }

    if (!$errors) {
        flash('success', 'Account created! You can now log in.');
        redirect('login.php');
    }

    foreach ($errors as $message) {
        flash('danger', $message);
    }
}

$page_title = 'Register';
require __DIR__ . '/../src/header.php';
?>

<section class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card form-card">
                <div class="card-body p-4">
                    <h2 class="section-title mb-4">Create an account</h2>

                    <form method="post" action="register.php" id="register-form" novalidate>
                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label for="name" class="form-label">Full name</label>
                            <input type="text" class="form-control" id="name" name="name"
                                   value="<?= e($name) ?>" maxlength="100" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email"
                                   value="<?= e($email) ?>" maxlength="255" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password"
                                   minlength="8" required>
                            <div class="form-text">At least 8 characters.</div>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm password</label>
                            <input type="password" class="form-control" id="confirm_password"
                                   name="confirm_password" minlength="8" required>
                            <div class="invalid-feedback">Passwords do not match.</div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Register</button>
                    </form>

                    <p class="text-center mt-3 mb-0 small">
                        Already have an account? <a href="login.php">Log in</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../src/footer.php'; ?>
