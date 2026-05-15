<?php
/**
 * LUMEEGY — One-time Admin Setup
 * Creates the first admin user. DELETE this file after use!
 */
require_once __DIR__ . '/includes/functions.php';

$done = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fn = trim($_POST['first_name'] ?? '');
    $ln = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';

    if (!$fn || !$email || !$pass) {
        $error = 'All fields are required.';
    } elseif (strlen($pass) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        // Check if admin already exists
        $existing = db()->prepare('SELECT id FROM users WHERE role = "admin" LIMIT 1');
        $existing->execute();
        if ($existing->fetch()) {
            $error = 'An admin account already exists. Delete this file.';
        } else {
            $hash = password_hash($pass, PASSWORD_BCRYPT);
            db()->prepare(
                'INSERT INTO users (first_name, last_name, email, password_hash, role) VALUES (?,?,?,?,?)'
            )->execute([$fn, $ln ?: $fn, $email, $hash, 'admin']);
            $done = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Admin — LUMEEGY</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0
        }

        body {
            font-family: system-ui, sans-serif;
            background: #0d0d0d;
            color: #e8e8e4;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 24px
        }

        .box {
            max-width: 400px;
            width: 100%;
            background: #161616;
            border: 1px solid #2a2a2a;
            border-radius: 8px;
            padding: 40px 32px
        }

        h1 {
            font-size: 1.4rem;
            text-align: center;
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: .06em
        }

        p.sub {
            text-align: center;
            color: #777;
            font-size: .8rem;
            margin-bottom: 24px
        }

        label {
            display: block;
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .1em;
            color: #C8B89A;
            margin-bottom: 6px;
            margin-top: 16px
        }

        input {
            width: 100%;
            padding: 10px 14px;
            background: #0d0d0d;
            border: 1px solid #2a2a2a;
            border-radius: 6px;
            color: #e8e8e4;
            font-size: .85rem
        }

        input:focus {
            outline: none;
            border-color: #C4714A
        }

        button {
            width: 100%;
            margin-top: 24px;
            padding: 12px;
            background: #C4714A;
            border: none;
            border-radius: 6px;
            color: #fff;
            font-size: .85rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            cursor: pointer
        }

        button:hover {
            background: #d4845d
        }

        .err {
            background: rgba(196, 74, 74, .1);
            border: 1px solid #c44a4a;
            color: #c44a4a;
            padding: 10px;
            border-radius: 6px;
            font-size: .82rem;
            margin-bottom: 16px
        }

        .ok {
            background: rgba(61, 153, 112, .1);
            border: 1px solid #3d9970;
            color: #3d9970;
            padding: 16px;
            border-radius: 6px;
            font-size: .85rem;
            text-align: center
        }

        .ok a {
            color: #C4714A;
            text-decoration: underline
        }

        .warn {
            color: #c44a4a;
            font-size: .72rem;
            text-align: center;
            margin-top: 16px
        }
    </style>
</head>

<body>
    <div class="box">
        <?php if ($done): ?>
            <div class="ok">
                <p><strong>✓ Admin account created!</strong></p>
                <p style="margin-top:8px"><a href="<?= SITE_URL ?>/admin/">Go to Admin Panel →</a></p>
            </div>
            <p class="warn">⚠ DELETE this file (setup-admin.php) from the server now!</p>
        <?php else: ?>
            <h1>LUMEEGY</h1>
            <p class="sub">Create Admin Account</p>
            <?php if ($error): ?>
                <div class="err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <form method="post">
                <label>First Name</label>
                <input type="text" name="first_name" required autofocus>
                <label>Last Name</label>
                <input type="text" name="last_name">
                <label>Email</label>
                <input type="email" name="email" required>
                <label>Password</label>
                <input type="password" name="password" required minlength="6">
                <button type="submit">Create Admin</button>
            </form>
        <?php endif; ?>
    </div>
</body>

</html>