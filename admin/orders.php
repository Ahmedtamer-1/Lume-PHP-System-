<?php
/**
 * Admin — Orders Management
 */
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Orders';
$adminPage = 'orders';

// ── DELETE ORDER ──
if (($_GET['action'] ?? '') === 'delete' && isset($_GET['id'])) {
    $delId = (int) $_GET['id'];
    db()->prepare('DELETE FROM order_items WHERE order_id = ?')->execute([$delId]);
    db()->prepare('DELETE FROM orders WHERE id = ?')->execute([$delId]);
    header('Location: ' . SITE_URL . '/admin/orders.php?msg=deleted');
    exit;
}

// ── UPDATE STATUS ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $validStatuses = ['pending', 'paid', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'];
    $newStatus = $_POST['status'];
    if (in_array($newStatus, $validStatuses)) {
        db()->prepare('UPDATE orders SET status = ? WHERE id = ?')
            ->execute([$newStatus, (int) $_POST['order_id']]);
        
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;
        }

        header('Location: ' . SITE_URL . '/admin/orders.php?msg=updated');
        exit;
    }
}

$success = '';
$msgs = ['updated' => 'Order status updated.', 'deleted' => 'Order deleted.'];
if (isset($_GET['msg'], $msgs[$_GET['msg']])) {
    $success = $msgs[$_GET['msg']];
}

// ── VIEW SINGLE ORDER ──
if (isset($_GET['id'])) {
    $orderId = (int) $_GET['id'];
    $stmt = db()->prepare('SELECT * FROM orders WHERE id = ?');
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    if (!$order) { header('Location: ' . SITE_URL . '/admin/orders.php'); exit; }

    $items = db()->prepare('SELECT * FROM order_items WHERE order_id = ?');
    $items->execute([$orderId]);
    $orderItems = $items->fetchAll();

    $pageTitle = 'Order ' . $order['order_number'];
    require_once __DIR__ . '/includes/header.php';
    ?>

    <?php if ($success): ?><div class="admin-alert admin-alert--success"><?= h($success) ?></div><?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 320px;gap:24px">
        <!-- Order Items -->
        <div>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr><th>Product</th><th>SKU</th><th>Price</th><th>Qty</th><th>Subtotal</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($orderItems as $i): ?>
                        <tr>
                            <td><strong><?= h($i['name']) ?></strong></td>
                            <td style="color:var(--a-muted)"><?= h($i['sku'] ?? '—') ?></td>
                            <td><?= money((float)$i['price']) ?></td>
                            <td><?= (int)$i['quantity'] ?></td>
                            <td><?= money((float)$i['subtotal']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Shipping Info -->
            <div style="margin-top:24px;background:var(--a-surface);border:1px solid var(--a-border);border-radius:var(--a-radius);padding:24px">
                <h3 style="font-size:.85rem;text-transform:uppercase;letter-spacing:.08em;margin-bottom:16px;color:var(--a-gold)">Shipping Details</h3>
                <p style="margin-bottom:6px"><strong><?= h($order['shipping_name'] ?? '—') ?></strong></p>
                <p style="color:var(--a-muted);font-size:.85rem"><?= h($order['shipping_addr'] ?? '') ?></p>
                <p style="color:var(--a-muted);font-size:.85rem"><?= h($order['shipping_city'] ?? '') ?>, <?= h($order['shipping_country'] ?? '') ?></p>
                <?php if (!empty($order['guest_email'])): ?>
                    <p style="color:var(--a-accent);font-size:.85rem;margin-top:8px"><?= h($order['guest_email']) ?></p>
                <?php endif; ?>
                <?php if (!empty($order['notes'])): ?>
                    <p style="color:var(--a-muted);font-size:.82rem;margin-top:12px;font-style:italic">"<?= h($order['notes']) ?>"</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Order Summary Sidebar -->
        <div>
            <div style="background:var(--a-surface);border:1px solid var(--a-border);border-radius:var(--a-radius);padding:24px">
                <h3 style="font-size:.85rem;text-transform:uppercase;letter-spacing:.08em;margin-bottom:16px;color:var(--a-gold)">Summary</h3>
                <div style="display:flex;justify-content:space-between;margin-bottom:8px;font-size:.85rem">
                    <span style="color:var(--a-muted)">Subtotal</span><span><?= money((float)$order['subtotal']) ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;margin-bottom:8px;font-size:.85rem">
                    <span style="color:var(--a-muted)">Shipping</span><span><?= money((float)$order['shipping_cost']) ?></span>
                </div>
                <?php if ((float)$order['discount'] > 0): ?>
                <div style="display:flex;justify-content:space-between;margin-bottom:8px;font-size:.85rem">
                    <span style="color:var(--a-muted)">Discount</span><span style="color:var(--a-green)">-<?= money((float)$order['discount']) ?></span>
                </div>
                <?php endif; ?>
                <div style="display:flex;justify-content:space-between;padding-top:12px;border-top:1px solid var(--a-border);font-weight:600;font-size:1rem;margin-top:8px">
                    <span>Total</span><span><?= money((float)$order['total']) ?></span>
                </div>
            </div>

            <!-- Status Update -->
            <div style="background:var(--a-surface);border:1px solid var(--a-border);border-radius:var(--a-radius);padding:24px;margin-top:16px">
                <h3 style="font-size:.85rem;text-transform:uppercase;letter-spacing:.08em;margin-bottom:16px;color:var(--a-gold)">Update Status</h3>
                <form method="post" style="display:flex;gap:8px">
                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                    <select name="status" class="admin-form__group" style="flex:1;padding:8px 12px;background:var(--a-bg);border:1px solid var(--a-border);border-radius:var(--a-radius);color:var(--a-text);font-size:.82rem">
                        <?php foreach (['pending','paid','processing','shipped','delivered','cancelled','refunded'] as $s): ?>
                        <option value="<?= $s ?>" <?= $order['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="admin-btn admin-btn--primary admin-btn--sm">Update</button>
                </form>
            </div>

            <div style="margin-top:16px;display:flex;gap:8px">
                <a href="<?= SITE_URL ?>/admin/orders.php" class="admin-btn admin-btn--full">← Back to Orders</a>
                <button type="button" class="admin-btn" onclick="window.print()" title="Print Invoice"><svg viewBox="0 0 24 24"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg></button>
            </div>
            <div style="margin-top:12px">
                <a href="<?= SITE_URL ?>/admin/orders.php?action=delete&id=<?= $order['id'] ?>" class="admin-btn admin-btn--danger admin-btn--full"
                   onclick="return confirm('Delete this order permanently? This cannot be undone.')">Delete Order</a>
            </div>
        </div>
    </div>

    <?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// ── LIST ──
$search = trim($_GET['search'] ?? '');
$filterStatus = $_GET['status_filter'] ?? '';

$query = 'SELECT * FROM orders WHERE 1=1';
$params = [];

if ($search) {
    $query .= ' AND (order_number LIKE ? OR shipping_name LIKE ?)';
    $term = '%' . $search . '%';
    $params[] = $term;
    $params[] = $term;
}

if ($filterStatus && in_array($filterStatus, ['pending','paid','processing','shipped','delivered','cancelled','refunded'])) {
    $query .= ' AND status = ?';
    $params[] = $filterStatus;
}

$query .= ' ORDER BY created_at DESC';
$stmt = db()->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll();
require_once __DIR__ . '/includes/header.php';
?>

<?php if ($success): ?><div class="admin-alert admin-alert--success"><?= h($success) ?></div><?php endif; ?>

<div class="admin-toolbar">
    <div class="admin-toolbar__left">
        <span style="font-size:.85rem;color:var(--a-muted)"><?= count($orders) ?> orders</span>
        <div class="admin-filter-tabs">
            <a href="?status_filter=&search=<?= urlencode($search) ?>" class="admin-filter-tab <?= empty($filterStatus) ? 'active' : '' ?>">All</a>
            <?php foreach(['pending','paid','processing','shipped','delivered','cancelled','refunded'] as $st): ?>
            <a href="?status_filter=<?= $st ?>&search=<?= urlencode($search) ?>" class="admin-filter-tab <?= $filterStatus === $st ? 'active' : '' ?>"><?= ucfirst($st) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="admin-toolbar__right">
        <form method="get" style="display:flex;gap:8px">
            <input type="hidden" name="status_filter" value="<?= h($filterStatus) ?>">
            <input type="text" name="search" value="<?= h($search) ?>" placeholder="Search orders…" class="admin-search-input">
            <button type="submit" class="admin-btn admin-btn--sm">Search</button>
        </form>
    </div>
</div>

<?php if (empty($orders)): ?>
<div class="admin-empty">
    <div class="admin-empty__icon">📋</div>
    <p class="admin-empty__text">No orders yet</p>
</div>
<?php else: ?>
<div class="admin-table-wrap">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Order</th>
                <th>Customer</th>
                <th>Status</th>
                <th>Total</th>
                <th>Payment</th>
                <th>Date</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($orders as $o): ?>
            <tr>
                <td><a href="<?= SITE_URL ?>/admin/orders.php?id=<?= $o['id'] ?>" style="color:var(--a-accent)"><strong><?= h($o['order_number']) ?></strong></a></td>
                <td><?= h($o['shipping_name'] ?? $o['guest_email'] ?? '—') ?></td>
                <td>
                    <select class="admin-badge admin-badge--<?= h($o['status']) ?>" 
                            style="border:none; cursor:pointer; outline:none; padding-right:20px; font-family:inherit; font-weight:600; text-transform:uppercase" 
                            onchange="quickUpdateStatus(this, <?= $o['id'] ?>)">
                        <?php foreach(['pending','paid','processing','shipped','delivered','cancelled','refunded'] as $s): ?>
                            <option value="<?= $s ?>" <?= $o['status'] === $s ? 'selected' : '' ?> style="background:var(--a-bg); color:var(--a-text); text-transform:uppercase"><?= h($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td><?= money((float)$o['total']) ?></td>
                <td style="color:var(--a-muted);font-size:.8rem"><?= h($o['payment_method'] ?? '—') ?></td>
                <td style="color:var(--a-muted)"><?= date('d M Y', strtotime($o['created_at'])) ?></td>
                <td style="white-space:nowrap">
                    <a href="<?= SITE_URL ?>/admin/orders.php?id=<?= $o['id'] ?>" class="admin-btn admin-btn--sm">View</a>
                    <a href="<?= SITE_URL ?>/admin/orders.php?action=delete&id=<?= $o['id'] ?>" class="admin-btn admin-btn--sm admin-btn--danger"
                       onclick="return confirm('Delete order <?= h($o['order_number']) ?>? This cannot be undone.')">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<script>
function quickUpdateStatus(select, orderId) {
    select.style.opacity = '0.5';
    
    const formData = new FormData();
    formData.append('order_id', orderId);
    formData.append('status', select.value);
    
    fetch('<?= SITE_URL ?>/admin/orders.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        select.style.opacity = '1';
        if (data.success) {
            AdminUI.toast('Order status updated', 'success');
            select.className = 'admin-badge admin-badge--' + select.value;
        } else {
            AdminUI.toast('Failed to update status', 'error');
        }
    })
    .catch(() => {
        select.style.opacity = '1';
        AdminUI.toast('Error updating status', 'error');
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
