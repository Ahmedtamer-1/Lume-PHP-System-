<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
/**
 * Admin — Orders Management
 */
require_once __DIR__ . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/mailer.php';
$pageTitle = 'Orders';
$adminPage = 'orders';

// ── DELETE ORDER ──
if (($_GET['action'] ?? '') === 'delete' && isset($_GET['id'])) {
    $delId = (int) $_GET['id'];
    db()->prepare('DELETE FROM order_items WHERE order_id = ?')->execute([$delId]);
    db()->prepare('DELETE FROM orders WHERE id = ?')->execute([$delId]);
    log_activity('delete_order', 'order', $delId);
    header('Location: ' . SITE_URL . '/admin/orders.php?msg=deleted');
    exit;
}

// ── BULK ACTIONS ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action']) && !empty($_POST['bulk_ids'])) {
    $action = $_POST['bulk_action'];
    $ids = $_POST['bulk_ids']; // array of IDs
    
    // Sanitize IDs
    $cleanIds = array_map('intval', $ids);
    $idPlaceholders = implode(',', array_fill(0, count($cleanIds), '?'));
    
    if ($action === 'delete') {
        db()->prepare("DELETE FROM order_items WHERE order_id IN ($idPlaceholders)")->execute($cleanIds);
        db()->prepare("DELETE FROM orders WHERE id IN ($idPlaceholders)")->execute($cleanIds);
        log_activity('bulk_delete_orders', 'order', 0, count($cleanIds) . " orders deleted");
        header('Location: ' . SITE_URL . '/admin/orders.php?msg=bulk_deleted');
        exit;
    } elseif (strpos($action, 'status_') === 0) {
        $status = substr($action, 7);
        $validStatuses = ['pending', 'pending_payment', 'payment_uploaded', 'paid', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'];
        if (in_array($status, $validStatuses)) {
            $params = array_merge([$status], $cleanIds);
            db()->prepare("UPDATE orders SET status = ? WHERE id IN ($idPlaceholders)")->execute($params);
            log_activity('bulk_status_update', 'order', 0, count($cleanIds) . " orders marked as $status");
            header('Location: ' . SITE_URL . '/admin/orders.php?msg=bulk_updated');
            exit;
        }
    }
}

// ── UPDATE STATUS ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $validStatuses = ['pending', 'pending_payment', 'payment_uploaded', 'paid', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'];
    $newStatus = $_POST['status'];
    if (in_array($newStatus, $validStatuses)) {
        db()->prepare('UPDATE orders SET status = ? WHERE id = ?')
            ->execute([$newStatus, (int) $_POST['order_id']]);
        log_activity('update_order_status', 'order', (int) $_POST['order_id'], "Status: $newStatus");

        // Send shipping notification email when order is marked shipped
        if ($newStatus === 'shipped') {
            send_order_email('shipped', (int) $_POST['order_id']);
        } elseif ($newStatus === 'paid') {
            send_order_email('paid', (int) $_POST['order_id']);
        }
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
$msgs = [
    'updated' => 'Order status updated.', 
    'deleted' => 'Order deleted.',
    'bulk_deleted' => 'Selected orders were deleted.',
    'bulk_updated' => 'Selected orders were updated.'
];
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
                            <td>
                                <strong><?= h($i['name']) ?></strong>
                                <?php if (!empty($i['variant_size']) || !empty($i['variant_color'])): ?>
                                    <div style="font-size:0.8rem;color:var(--text-muted);margin-top:4px;">
                                        <?php if (!empty($i['variant_size'])) echo 'Size: ' . h($i['variant_size']) . '&nbsp;&nbsp;'; ?>
                                        <?php if (!empty($i['variant_color'])) echo 'Color: ' . h($i['variant_color']); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
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
            <div class="admin-dashboard-panel">
                <h3 class="admin-dashboard-panel__title">Shipping Details</h3>
                <p style="margin-bottom:6px"><strong><?= h($order['shipping_name'] ?? '—') ?></strong></p>
                <p style="color:var(--text-muted);font-size:.85rem;margin-bottom:4px;"><?= h($order['shipping_addr'] ?? '') ?></p>
                <p style="color:var(--text-muted);font-size:.85rem;margin-bottom:8px;"><?= h($order['shipping_city'] ?? '') ?>, <?= h($order['shipping_country'] ?? '') ?></p>
                <?php if (!empty($order['phone'])): ?>
                    <p style="color:var(--text-muted);font-size:.85rem;margin-bottom:4px;"><strong>Phone:</strong> <?= h($order['phone']) ?></p>
                <?php endif; ?>
                <?php if (!empty($order['guest_email'])): ?>
                    <p style="color:var(--accent);font-size:.85rem;margin-top:4px"><?= h($order['guest_email']) ?></p>
                <?php endif; ?>
                <?php if (!empty($order['notes'])): ?>
                    <p style="color:var(--text-muted);font-size:.82rem;margin-top:12px;font-style:italic">"<?= h($order['notes']) ?>"</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Order Summary Sidebar -->
        <div>
            <div class="admin-dashboard-panel">
                <h3 class="admin-dashboard-panel__title">Summary</h3>
                <div style="display:flex;justify-content:space-between;margin-bottom:8px;font-size:.85rem">
                    <span style="color:var(--text-muted)">Date</span><span><?= date('M j, Y', strtotime($order['created_at'])) ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;margin-bottom:8px;font-size:.85rem">
                    <span style="color:var(--text-muted)">Payment</span><span style="text-transform:uppercase"><?= h($order['payment_method'] ?? 'COD') ?></span>
                </div>
                <hr style="border:none;border-top:0.5px solid var(--border);margin:12px 0">
                <div style="display:flex;justify-content:space-between;margin-bottom:8px;font-size:.85rem">
                    <span style="color:var(--text-muted)">Subtotal</span><span><?= money((float)$order['subtotal']) ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;margin-bottom:8px;font-size:.85rem">
                    <span style="color:var(--text-muted)">Shipping</span><span><?= money((float)$order['shipping_cost']) ?></span>
                </div>
                <?php if ((float)$order['discount'] > 0): ?>
                <div style="display:flex;justify-content:space-between;margin-bottom:8px;font-size:.85rem">
                    <span style="color:var(--text-muted)">Discount</span><span style="color:var(--green)">-<?= money((float)$order['discount']) ?></span>
                </div>
                <?php endif; ?>
                <div style="display:flex;justify-content:space-between;padding-top:12px;border-top:0.5px solid var(--border);font-weight:600;font-size:1rem;margin-top:8px">
                    <span>Total</span><span><?= money((float)$order['total']) ?></span>
                </div>
                <?php if (!empty($order['instapay_receipt'])): ?>
                <div style="margin-top:16px;padding-top:12px;border-top:0.5px solid var(--border);">
                    <h4 style="font-size:0.85rem;color:var(--gold);margin-bottom:8px;">InstaPay Receipt</h4>
                    <a href="<?= SITE_URL ?>/<?= h($order['instapay_receipt']) ?>" target="_blank">
                        <img src="<?= SITE_URL ?>/<?= h($order['instapay_receipt']) ?>" alt="Receipt" style="max-width:100%; border:1px solid var(--border); border-radius:4px;">
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <!-- Status Update -->
            <div class="admin-dashboard-panel" style="margin-top:16px">
                <h3 class="admin-dashboard-panel__title">Update Status</h3>
                <form method="post" style="display:flex;gap:8px">
                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                    <select name="status" class="admin-form__group" style="flex:1;padding:8px 12px;background:var(--bg-input);border:0.5px solid var(--border);border-radius:var(--radius-sm);color:var(--text-primary);font-size:.82rem">
                        <?php foreach (['pending', 'pending_payment', 'payment_uploaded', 'paid', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'] as $s): ?>
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

if ($filterStatus && in_array($filterStatus, ['pending', 'pending_payment', 'payment_uploaded', 'paid', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'])) {
    $query .= ' AND status = ?';
    $params[] = $filterStatus;
}

$sort = $_GET['sort'] ?? 'created_at';
$dir = ($_GET['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
$validSorts = ['order_number', 'shipping_name', 'status', 'total', 'created_at'];
if (!in_array($sort, $validSorts)) $sort = 'created_at';

$query .= " ORDER BY $sort $dir";
$stmt = db()->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll();
require_once __DIR__ . '/includes/header.php';
?>

<?php if ($success): ?><div class="admin-alert admin-alert--success"><?= h($success) ?></div><?php endif; ?>

<div class="admin-toolbar">
    <div class="admin-toolbar__left">
        <span style="font-size:.85rem;color:var(--text-muted)"><?= count($orders) ?> orders</span>
        <div class="admin-filter-tabs">
            <a href="?status_filter=&search=<?= urlencode($search) ?>" class="admin-filter-tab <?= empty($filterStatus) ? 'active' : '' ?>">All</a>
            <?php foreach(['pending', 'pending_payment', 'payment_uploaded', 'paid', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'] as $st): ?>
            <a href="?status_filter=<?= $st ?>&search=<?= urlencode($search) ?>" class="admin-filter-tab <?= $filterStatus === $st ? 'active' : '' ?>"><?= ucfirst($st) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="admin-toolbar__right" style="display:flex; gap:12px; align-items:center">
        <form method="get" style="display:flex;gap:8px">
            <input type="hidden" name="status_filter" value="<?= h($filterStatus) ?>">
            <input type="text" name="search" value="<?= h($search) ?>" placeholder="Search orders…" class="admin-search-input">
            <button type="submit" class="admin-btn admin-btn--sm">Search</button>
        </form>
        <a href="<?= SITE_URL ?>/admin/order-new.php" class="admin-btn admin-btn--primary">
            <svg viewBox="0 0 24 24" style="width:16px;height:16px;margin-right:6px"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add Order
        </a>
    </div>
</div>

<?php if (empty($orders)): ?>
<div class="admin-empty">
    <div class="admin-empty__icon">📋</div>
    <p class="admin-empty__text">No orders yet</p>
</div>
<?php else: ?>
<form id="bulk-orders-form" method="POST" action="?">
    <input type="hidden" name="bulk_action" id="bulk_action_input">
    <div class="admin-table-wrap" style="position:relative; padding-bottom:60px;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width:40px"><input type="checkbox" id="selectAll" onclick="toggleAllCheckboxes(this)"></th>
                    <th onclick="window.location='?sort=order_number&dir=<?= $sort === 'order_number' && $dir === 'ASC' ? 'desc' : 'asc' ?>'" class="<?= $sort === 'order_number' ? 'sort-' . strtolower($dir) : '' ?>">Order</th>
                    <th onclick="window.location='?sort=shipping_name&dir=<?= $sort === 'shipping_name' && $dir === 'ASC' ? 'desc' : 'asc' ?>'" class="<?= $sort === 'shipping_name' ? 'sort-' . strtolower($dir) : '' ?>">Customer</th>
                    <th onclick="window.location='?sort=status&dir=<?= $sort === 'status' && $dir === 'ASC' ? 'desc' : 'asc' ?>'" class="<?= $sort === 'status' ? 'sort-' . strtolower($dir) : '' ?>">Status</th>
                    <th onclick="window.location='?sort=total&dir=<?= $sort === 'total' && $dir === 'ASC' ? 'desc' : 'asc' ?>'" class="<?= $sort === 'total' ? 'sort-' . strtolower($dir) : '' ?>">Total</th>
                    <th>Payment</th>
                    <th onclick="window.location='?sort=created_at&dir=<?= $sort === 'created_at' && $dir === 'ASC' ? 'desc' : 'asc' ?>'" class="<?= $sort === 'created_at' ? 'sort-' . strtolower($dir) : '' ?>">Date</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($orders as $o): ?>
                <tr>
                    <td><input type="checkbox" name="bulk_ids[]" value="<?= $o['id'] ?>" class="row-checkbox" onclick="updateBulkActionBar()"></td>
                <td><a href="<?= SITE_URL ?>/admin/orders.php?id=<?= $o['id'] ?>" style="color:var(--accent)"><strong><?= h($o['order_number']) ?></strong></a></td>
                <td><?= h($o['shipping_name'] ?? $o['guest_email'] ?? '—') ?></td>
                <td>
                    <select class="admin-badge admin-badge--<?= h($o['status']) ?>" 
                            style="border:none; cursor:pointer; outline:none; padding-right:20px; font-family:inherit; font-weight:600; text-transform:uppercase" 
                            onchange="quickUpdateStatus(this, <?= $o['id'] ?>)">
                        <?php foreach(['pending','paid','processing','shipped','delivered','cancelled','refunded'] as $s): ?>
                            <option value="<?= $s ?>" <?= $o['status'] === $s ? 'selected' : '' ?> style="background:var(--bg-primary); color:var(--text-primary); text-transform:uppercase"><?= h($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td><?= money((float)$o['total']) ?></td>
                <td style="color:var(--text-muted);font-size:.8rem"><?= h($o['payment_method'] ?? '—') ?></td>
                <td style="color:var(--text-muted)"><?= date('d M Y', strtotime($o['created_at'])) ?></td>
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
</form>

<!-- Floating Bulk Action Bar -->
<div id="bulk-action-bar" style="display:none; position:fixed; bottom:20px; left:50%; transform:translateX(-50%); background:var(--bg-surface); border:0.5px solid var(--border); padding:12px 24px; border-radius:30px; box-shadow:0 10px 30px rgba(0,0,0,0.2); z-index:100; align-items:center; gap:16px;">
    <span id="bulk-count" style="font-weight:600; font-size:.9rem; color:var(--text-primary)">0 selected</span>
    <div style="width:1px; height:20px; background:var(--border)"></div>
    <select id="bulk-action-select" style="padding:6px 12px; background:var(--bg-input); border:0.5px solid var(--border); border-radius:4px; color:var(--text-primary)">
        <option value="">Choose action...</option>
        <option value="status_pending">Mark Pending</option>
        <option value="status_paid">Mark Paid</option>
        <option value="status_processing">Mark Processing</option>
        <option value="status_shipped">Mark Shipped</option>
        <option value="status_delivered">Mark Delivered</option>
        <option value="status_cancelled">Mark Cancelled</option>
        <option value="status_refunded">Mark Refunded</option>
        <option value="delete">Delete</option>
    </select>
    <button type="button" class="admin-btn admin-btn--primary admin-btn--sm" onclick="applyBulkAction()">Apply</button>
</div>

<?php endif; ?>

<script>
function toggleAllCheckboxes(masterCheckbox) {
    const checkboxes = document.querySelectorAll('.row-checkbox');
    checkboxes.forEach(cb => cb.checked = masterCheckbox.checked);
    updateBulkActionBar();
}

function updateBulkActionBar() {
    const checkboxes = document.querySelectorAll('.row-checkbox:checked');
    const bar = document.getElementById('bulk-action-bar');
    const count = document.getElementById('bulk-count');
    
    if (checkboxes.length > 0) {
        bar.style.display = 'flex';
        count.innerText = checkboxes.length + ' selected';
    } else {
        bar.style.display = 'none';
        document.getElementById('selectAll').checked = false;
    }
}

function applyBulkAction() {
    const select = document.getElementById('bulk-action-select');
    const action = select.value;
    if (!action) {
        alert('Please select an action.');
        return;
    }
    
    if (action === 'delete') {
        if (!confirm('Are you sure you want to delete the selected orders? This cannot be undone.')) {
            return;
        }
    }
    
    document.getElementById('bulk_action_input').value = action;
    document.getElementById('bulk-orders-form').submit();
}

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
