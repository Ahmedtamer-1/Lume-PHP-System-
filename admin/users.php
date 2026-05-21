<?php
/**
 * Admin — Users Management
 */
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Users';
$adminPage = 'users';

$action  = $_GET['action'] ?? 'list';
$success = '';
$error   = '';

// ── DELETE ──
if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    if ($id === (int)$adminUser['id']) {
        $error = 'You cannot delete your own account.';
    } else {
        db()->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
        log_activity('delete_user', 'user', $id);
        header('Location: ' . SITE_URL . '/admin/users.php?msg=deleted');
        exit;
    }
}

// ── UPDATE ROLE ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['role'])) {
    $uid  = (int) $_POST['user_id'];
    $role = $_POST['role'];
    if (!in_array($role, ['customer', 'admin'])) {
        $error = 'Invalid role.';
    } elseif ($uid === (int)$adminUser['id'] && $role !== 'admin') {
        $error = 'You cannot remove your own admin role.';
    } else {
        db()->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$role, $uid]);
        log_activity('update_user_role', 'user', $uid, "Role: $role");
        header('Location: ' . SITE_URL . '/admin/users.php?msg=updated');
        exit;
    }
}

if (isset($_GET['msg'])) {
    $msgs = ['updated' => 'User updated!', 'deleted' => 'User deleted.'];
    $success = $msgs[$_GET['msg']] ?? '';
}

// ── VIEW SINGLE USER ──
if (isset($_GET['id']) && $action !== 'delete') {
    $uid = (int) $_GET['id'];
    $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$uid]);
    $viewUser = $stmt->fetch();
    if (!$viewUser) { header('Location: ' . SITE_URL . '/admin/users.php'); exit; }

    $orders = db()->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 20');
    $orders->execute([$uid]);
    $userOrders = $orders->fetchAll();

    $pageTitle = $viewUser['first_name'] . ' ' . $viewUser['last_name'];
    require_once __DIR__ . '/includes/header.php';
    ?>

    <?php if ($success): ?><div class="admin-alert admin-alert--success"><?= h($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="admin-alert admin-alert--error"><?= h($error) ?></div><?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 320px;gap:24px">
        <div>
            <!-- User Info -->
            <div style="background:var(--a-surface);border:1px solid var(--a-border);border-radius:var(--a-radius);padding:24px;margin-bottom:24px">
                <h3 style="font-size:.85rem;text-transform:uppercase;letter-spacing:.08em;margin-bottom:16px;color:var(--a-gold)">User Details</h3>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;font-size:.85rem">
                    <div><span style="color:var(--a-muted);display:block;font-size:.72rem;text-transform:uppercase;letter-spacing:.1em;margin-bottom:4px">Name</span><?= h($viewUser['first_name'] . ' ' . $viewUser['last_name']) ?></div>
                    <div><span style="color:var(--a-muted);display:block;font-size:.72rem;text-transform:uppercase;letter-spacing:.1em;margin-bottom:4px">Email</span><?= h($viewUser['email']) ?></div>
                    <div><span style="color:var(--a-muted);display:block;font-size:.72rem;text-transform:uppercase;letter-spacing:.1em;margin-bottom:4px">Role</span><span class="admin-badge admin-badge--<?= $viewUser['role'] === 'admin' ? 'shipped' : 'active' ?>"><?= h($viewUser['role']) ?></span></div>
                    <div><span style="color:var(--a-muted);display:block;font-size:.72rem;text-transform:uppercase;letter-spacing:.1em;margin-bottom:4px">Joined</span><?= date('d M Y', strtotime($viewUser['created_at'])) ?></div>
                </div>
            </div>

            <!-- User Orders -->
            <h3 style="font-size:.9rem;font-weight:600;margin-bottom:16px">Orders (<?= count($userOrders) ?>)</h3>
            <?php if (empty($userOrders)): ?>
                <p style="color:var(--a-muted);font-size:.85rem">No orders from this user.</p>
            <?php else: ?>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead><tr><th>Order</th><th>Status</th><th>Total</th><th>Date</th></tr></thead>
                    <tbody>
                    <?php foreach ($userOrders as $o): ?>
                        <tr>
                            <td><a href="<?= SITE_URL ?>/admin/orders.php?id=<?= $o['id'] ?>" style="color:var(--a-accent)"><?= h($o['order_number']) ?></a></td>
                            <td><span class="admin-badge admin-badge--<?= h($o['status']) ?>"><?= h($o['status']) ?></span></td>
                            <td><?= money((float)$o['total']) ?></td>
                            <td style="color:var(--a-muted)"><?= date('d M Y', strtotime($o['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar Actions -->
        <div>
            <div style="background:var(--a-surface);border:1px solid var(--a-border);border-radius:var(--a-radius);padding:24px;margin-bottom:16px">
                <h3 style="font-size:.85rem;text-transform:uppercase;letter-spacing:.08em;margin-bottom:16px;color:var(--a-gold)">Change Role</h3>
                <form method="post" style="display:flex;gap:8px">
                    <input type="hidden" name="user_id" value="<?= $viewUser['id'] ?>">
                    <select name="role" style="flex:1;padding:8px 12px;background:var(--a-bg);border:1px solid var(--a-border);border-radius:var(--a-radius);color:var(--a-text);font-size:.82rem">
                        <option value="customer" <?= $viewUser['role'] === 'customer' ? 'selected' : '' ?>>Customer</option>
                        <option value="admin" <?= $viewUser['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                    </select>
                    <button type="submit" class="admin-btn admin-btn--primary admin-btn--sm">Save</button>
                </form>
            </div>

            <?php if ($uid !== (int)$adminUser['id']): ?>
            <a href="<?= SITE_URL ?>/admin/users.php?action=delete&id=<?= $viewUser['id'] ?>"
               class="admin-btn admin-btn--danger admin-btn--full"
               onclick="return confirm('Delete this user? This cannot be undone.')">Delete User</a>
            <?php endif; ?>

            <a href="<?= SITE_URL ?>/admin/users.php" class="admin-btn admin-btn--full" style="margin-top:8px">← Back to Users</a>
        </div>
    </div>

    <?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// ── LIST ──
$search = trim($_GET['q'] ?? '');
$filterRole = $_GET['role'] ?? '';

$whereStr = '';
$params = [];

if ($search) {
    $whereStr .= ' AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)';
    $term = '%' . $search . '%';
    $params = array_merge($params, [$term, $term, $term]);
}

if ($filterRole && in_array($filterRole, ['customer', 'admin'])) {
    $whereStr .= ' AND u.role = ?';
    $params[] = $filterRole;
}

$stmt = db()->prepare(
    'SELECT u.*, (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) AS order_count,
     (SELECT COALESCE(SUM(o.total),0) FROM orders o WHERE o.user_id = u.id AND o.status NOT IN ("cancelled","refunded")) AS total_spent
     FROM users u WHERE 1=1' . $whereStr . ' ORDER BY u.created_at DESC'
);
$stmt->execute($params);
$users = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<?php if ($success): ?><div class="admin-alert admin-alert--success"><?= h($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="admin-alert admin-alert--error"><?= h($error) ?></div><?php endif; ?>

<div class="admin-toolbar">
    <div class="admin-toolbar__left">
        <span style="font-size:.85rem;color:var(--a-muted)"><?= count($users) ?> users</span>
        <div class="admin-filter-tabs">
            <a href="?role=&q=<?= urlencode($search) ?>" class="admin-filter-tab <?= empty($filterRole) ? 'active' : '' ?>">All</a>
            <a href="?role=customer&q=<?= urlencode($search) ?>" class="admin-filter-tab <?= $filterRole === 'customer' ? 'active' : '' ?>">Customers</a>
            <a href="?role=admin&q=<?= urlencode($search) ?>" class="admin-filter-tab <?= $filterRole === 'admin' ? 'active' : '' ?>">Admins</a>
        </div>
    </div>
    <div class="admin-toolbar__right">
        <form method="get" style="display:flex;gap:8px">
            <input type="hidden" name="role" value="<?= h($filterRole) ?>">
            <input type="text" name="q" value="<?= h($search) ?>" placeholder="Search users…" class="admin-search-input">
            <button type="submit" class="admin-btn admin-btn--sm">Search</button>
        </form>
    </div>
</div>

<?php if (empty($users)): ?>
<div class="admin-empty">
    <div class="admin-empty__icon">👤</div>
    <p class="admin-empty__text">No users found.</p>
</div>
<?php else: ?>
<div class="admin-table-wrap">
    <table class="admin-table">
        <thead>
            <tr>
                <th>User</th>
                <th>Email</th>
                <th>Role</th>
                <th>Orders</th>
                <th>Total Spent</th>
                <th>Joined</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $u): ?>
            <tr>
                <td><strong><?= h($u['first_name'] . ' ' . $u['last_name']) ?></strong></td>
                <td style="color:var(--a-muted)"><?= h($u['email']) ?></td>
                <td><span class="admin-badge admin-badge--<?= $u['role'] === 'admin' ? 'shipped' : 'active' ?>"><?= h($u['role']) ?></span></td>
                <td><?= (int)$u['order_count'] ?></td>
                <td><?= money((float)$u['total_spent']) ?></td>
                <td style="color:var(--a-muted)"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                <td><a href="<?= SITE_URL ?>/admin/users.php?id=<?= $u['id'] ?>" class="admin-btn admin-btn--sm">View</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
