<?php
require_once __DIR__ . '/includes/functions.php';
lume_session_start();
$action = $_GET['action'] ?? ($_POST['action'] ?? 'login');
$error = ''; $success = '';
$user = current_user();

// Handle logout
if ($action === 'logout') { logout_user(); redirect('/account.php'); }

// If logged in, show dashboard
if ($user && !in_array($action, ['logout'])) { $action = 'dashboard'; }

// Handle login POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'login') {
    if (!csrf_verify($_POST['csrf'] ?? '')) { $error = 'Invalid request.'; }
    else {
        $email = trim($_POST['email'] ?? '');
        $pass  = $_POST['password'] ?? '';

        // Rate limiting: block after 5 failed attempts in 15 min
        if (is_rate_limited($email)) {
            $error = 'Too many login attempts. Please wait 15 minutes.';
        } else {
            $stmt  = db()->prepare('SELECT id, password_hash FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $row = $stmt->fetch();
            if ($row && password_verify($pass, $row['password_hash'])) {
                clear_login_attempts($email);
                record_login_attempt($email, true);
                login_user((int)$row['id']);
                $redir = $_GET['redirect'] ?? '/account.php';
                redirect($redir);
            } else {
                record_login_attempt($email, false);
                $error = 'Invalid email or password.';
            }
        }
    }
}

// Handle register POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'register') {
    if (!csrf_verify($_POST['csrf'] ?? '')) { $error = 'Invalid request.'; }
    else {
        $fn    = trim($_POST['first_name'] ?? '');
        $ln    = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $pass  = $_POST['password'] ?? '';
        $pass2 = $_POST['password_confirm'] ?? '';

        if (!$fn || !$ln || !$email || !$pass) { $error = 'All fields are required.'; }
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $error = 'Invalid email.'; }
        elseif (strlen($pass) < 6) { $error = 'Password must be at least 6 characters.'; }
        elseif ($pass !== $pass2) { $error = 'Passwords do not match.'; }
        else {
            $exists = db()->prepare('SELECT id FROM users WHERE email = ?');
            $exists->execute([$email]);
            if ($exists->fetch()) { $error = 'An account with this email already exists.'; }
            else {
                $hash = password_hash($pass, PASSWORD_BCRYPT);
                db()->prepare('INSERT INTO users (first_name, last_name, email, password_hash) VALUES (?,?,?,?)')
                    ->execute([$fn, $ln, $email, $hash]);
                login_user((int)db()->lastInsertId());
                redirect('/account.php');
            }
        }
    }
}

$pageTitle = $action === 'dashboard' ? 'My Account — LUMEEGY' : ($action === 'register' ? 'Register — LUMEEGY' : 'Login — LUMEEGY');
require_once __DIR__ . '/includes/header.php';

if ($action === 'dashboard'):
    $orders = db()->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 10');
    $orders->execute([$user['id']]);
    $orderList = $orders->fetchAll();
?>
<section class="lume-page-header">
    <div class="container">
        <h1 class="lume-page-header__title">My Account</h1>
        <p class="lume-page-header__breadcrumb">Welcome back, <?= h($user['first_name']) ?> &nbsp;|&nbsp; <a href="<?= SITE_URL ?>/account.php?action=logout">Logout</a></p>
    </div>
</section>
<section class="lume-section container">
    <h2 style="font-family:var(--font-serif);font-size:1.4rem;text-transform:uppercase;margin-bottom:24px">Recent Orders</h2>
    <?php if (empty($orderList)): ?>
    <p style="color:var(--muted)">No orders yet. <a href="<?= SITE_URL ?>/shop.php" style="color:var(--terracotta)">Start shopping</a></p>
    <?php else: ?>
    <table class="lume-cart-table">
        <thead><tr><th>Order</th><th>Date</th><th>Status</th><th>Total</th></tr></thead>
        <tbody>
        <?php foreach ($orderList as $o): ?>
        <tr>
            <td><?= h($o['order_number']) ?></td>
            <td><?= date('d M Y', strtotime($o['created_at'])) ?></td>
            <td><span style="text-transform:capitalize"><?= h($o['status']) ?></span></td>
            <td><?= money((float)$o['total']) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</section>

<?php elseif ($action === 'register'): ?>
<section class="lume-auth">
    <h1 class="lume-auth__title">Create Account</h1>
    <p class="lume-auth__sub">Join the LUMEEGY ritual</p>
    <?php if ($error): ?><div class="lume-alert lume-alert--error"><?= h($error) ?></div><?php endif; ?>
    <form method="post" class="lume-form">
        <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="register">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
            <div class="lume-form__group"><label class="lume-form__label">First Name</label><input class="lume-form__input" type="text" name="first_name" required></div>
            <div class="lume-form__group"><label class="lume-form__label">Last Name</label><input class="lume-form__input" type="text" name="last_name" required></div>
        </div>
        <div class="lume-form__group"><label class="lume-form__label">Email</label><input class="lume-form__input" type="email" name="email" required></div>
        <div class="lume-form__group"><label class="lume-form__label">Password</label><input class="lume-form__input" type="password" name="password" required></div>
        <div class="lume-form__group"><label class="lume-form__label">Confirm Password</label><input class="lume-form__input" type="password" name="password_confirm" required></div>
        <button type="submit" class="lume-btn lume-btn--full">Create Account</button>
    </form>
    <p class="lume-auth__toggle">Already have an account? <a href="<?= SITE_URL ?>/account.php">Login</a></p>
</section>

<?php else: ?>
<section class="lume-auth">
    <h1 class="lume-auth__title">Welcome Back</h1>
    <p class="lume-auth__sub">Sign in to your account</p>
    <?php if ($error): ?><div class="lume-alert lume-alert--error"><?= h($error) ?></div><?php endif; ?>
    <form method="post" class="lume-form">
        <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="login">
        <div class="lume-form__group"><label class="lume-form__label">Email</label><input class="lume-form__input" type="email" name="email" required></div>
        <div class="lume-form__group"><label class="lume-form__label">Password</label><input class="lume-form__input" type="password" name="password" required></div>
        <button type="submit" class="lume-btn lume-btn--full">Sign In</button>
    </form>
    <p class="lume-auth__toggle">Don't have an account? <a href="<?= SITE_URL ?>/account.php?action=register">Register</a></p>
</section>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
