<?php
/**
 * Admin Dashboard — Enhanced Analytics
 */
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Dashboard';
$adminPage = 'dashboard';

// ── Stats ──
$totalProducts  = (int) db()->query('SELECT COUNT(*) FROM products WHERE is_active = 1')->fetchColumn();
$totalOrders    = (int) db()->query('SELECT COUNT(*) FROM orders')->fetchColumn();
$totalRevenue   = (float) db()->query('SELECT COALESCE(SUM(total),0) FROM orders WHERE status NOT IN ("cancelled","refunded")')->fetchColumn();
$totalCustomers = (int) db()->query('SELECT COUNT(*) FROM users WHERE role = "customer"')->fetchColumn();

// ── This month stats ──
$monthOrders  = (int) db()->query('SELECT COUNT(*) FROM orders WHERE MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())')->fetchColumn();
$monthRevenue = (float) db()->query('SELECT COALESCE(SUM(total),0) FROM orders WHERE status NOT IN ("cancelled","refunded") AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())')->fetchColumn();

// ── Last month for comparison ──
$lastMonthRevenue = (float) db()->query('SELECT COALESCE(SUM(total),0) FROM orders WHERE status NOT IN ("cancelled","refunded") AND MONTH(created_at) = MONTH(DATE_SUB(NOW(), INTERVAL 1 MONTH)) AND YEAR(created_at) = YEAR(DATE_SUB(NOW(), INTERVAL 1 MONTH))')->fetchColumn();
$lastMonthOrders  = (int) db()->query('SELECT COUNT(*) FROM orders WHERE MONTH(created_at) = MONTH(DATE_SUB(NOW(), INTERVAL 1 MONTH)) AND YEAR(created_at) = YEAR(DATE_SUB(NOW(), INTERVAL 1 MONTH))')->fetchColumn();

// ── Revenue last 7 days ──
$revenueDays = db()->query(
    'SELECT DATE(created_at) AS day, SUM(total) AS revenue, COUNT(*) AS orders
     FROM orders WHERE status NOT IN ("cancelled","refunded")
     AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
     GROUP BY DATE(created_at) ORDER BY day ASC'
)->fetchAll();

// Fill in missing days
$chartLabels = [];
$chartData   = [];
for ($i = 6; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-{$i} days"));
    $chartLabels[] = date('D', strtotime($day));
    $found = false;
    foreach ($revenueDays as $r) {
        if ($r['day'] === $day) { $chartData[] = (float)$r['revenue']; $found = true; break; }
    }
    if (!$found) $chartData[] = 0;
}

// ── Order status breakdown ──
$statusBreakdown = db()->query(
    'SELECT status, COUNT(*) AS cnt FROM orders GROUP BY status ORDER BY cnt DESC'
)->fetchAll();

// ── Top products by order quantity ──
$topProducts = db()->query(
    'SELECT oi.name, SUM(oi.quantity) AS total_qty, SUM(oi.subtotal) AS total_revenue
     FROM order_items oi
     JOIN orders o ON o.id = oi.order_id
     WHERE o.status NOT IN ("cancelled","refunded")
     GROUP BY oi.name ORDER BY total_qty DESC LIMIT 5'
)->fetchAll();

// ── Recent orders ──
$recentOrders = db()->query('SELECT * FROM orders ORDER BY created_at DESC LIMIT 5')->fetchAll();

// ── New customers this week ──
$newCustomers = (int) db()->query('SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)')->fetchColumn();

// ── Newsletter subs ──
$totalSubs = (int) db()->query('SELECT COUNT(*) FROM newsletter_subscribers WHERE status = "active"')->fetchColumn();

// ── Unread messages ──
$unreadMsgs = 0;
try {
    $unreadMsgs = (int) db()->query('SELECT COUNT(*) FROM contact_messages WHERE is_read = 0')->fetchColumn();
} catch (Exception $e) {}

// ── Recent activity ──
$recentActivity = [];
try {
    $recentActivity = db()->query(
        'SELECT al.*, u.first_name, u.last_name 
         FROM activity_log al 
         LEFT JOIN users u ON u.id = al.user_id 
         ORDER BY al.created_at DESC LIMIT 5'
    )->fetchAll();
} catch (Exception $e) {}

// Helper for trend calculation
function trendPct($current, $previous) {
    if ($previous == 0) return $current > 0 ? 100 : 0;
    return round((($current - $previous) / $previous) * 100);
}
$revenueTrend = trendPct($monthRevenue, $lastMonthRevenue);
$ordersTrend  = trendPct($monthOrders, $lastMonthOrders);

require_once __DIR__ . '/includes/header.php';
?>

<!-- QUICK ACTIONS -->
<div class="admin-quick-actions">
    <a href="<?= SITE_URL ?>/admin/products.php" class="admin-quick-action">
        <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Add Product
    </a>
    <a href="<?= SITE_URL ?>/admin/orders.php" class="admin-quick-action">
        <svg viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/></svg>
        View Orders
    </a>
    <?php if ($unreadMsgs > 0): ?>
    <a href="<?= SITE_URL ?>/admin/messages.php?filter=unread" class="admin-quick-action" style="border-color:var(--a-accent);color:var(--a-accent)">
        <svg viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        <?= $unreadMsgs ?> Unread Messages
    </a>
    <?php endif; ?>
    <a href="<?= SITE_URL ?>/admin/subscribers.php" class="admin-quick-action">
        <svg viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22 6 12 13 2 6"/></svg>
        Subscribers
    </a>
</div>

<!-- STAT CARDS -->
<div class="admin-stats">
    <div class="admin-stat-card">
        <p class="admin-stat-card__label">Total Revenue</p>
        <p class="admin-stat-card__value green"><?= money($totalRevenue) ?></p>
    </div>
    <div class="admin-stat-card">
        <p class="admin-stat-card__label">Orders</p>
        <p class="admin-stat-card__value accent"><?= $totalOrders ?></p>
    </div>
    <div class="admin-stat-card">
        <p class="admin-stat-card__label">Customers</p>
        <p class="admin-stat-card__value"><?= $totalCustomers ?></p>
    </div>
    <div class="admin-stat-card">
        <p class="admin-stat-card__label">Products</p>
        <p class="admin-stat-card__value"><?= $totalProducts ?></p>
    </div>
</div>

<!-- MONTH STATS WITH TRENDS -->
<div class="admin-stats" style="margin-bottom:32px">
    <div class="admin-stat-card" style="border-left:3px solid var(--a-accent)">
        <p class="admin-stat-card__label">This Month Revenue</p>
        <p class="admin-stat-card__value" style="font-size:1.4rem"><?= money($monthRevenue) ?></p>
        <?php if ($revenueTrend != 0): ?>
        <span class="admin-stat-card__trend admin-stat-card__trend--<?= $revenueTrend >= 0 ? 'up' : 'down' ?>">
            <svg viewBox="0 0 24 24"><polyline points="<?= $revenueTrend >= 0 ? '18 15 12 9 6 15' : '6 9 12 15 18 9' ?>"/></svg>
            <?= abs($revenueTrend) ?>% vs last month
        </span>
        <?php endif; ?>
    </div>
    <div class="admin-stat-card" style="border-left:3px solid var(--a-accent)">
        <p class="admin-stat-card__label">This Month Orders</p>
        <p class="admin-stat-card__value" style="font-size:1.4rem"><?= $monthOrders ?></p>
        <?php if ($ordersTrend != 0): ?>
        <span class="admin-stat-card__trend admin-stat-card__trend--<?= $ordersTrend >= 0 ? 'up' : 'down' ?>">
            <svg viewBox="0 0 24 24"><polyline points="<?= $ordersTrend >= 0 ? '18 15 12 9 6 15' : '6 9 12 15 18 9' ?>"/></svg>
            <?= abs($ordersTrend) ?>% vs last month
        </span>
        <?php endif; ?>
    </div>
    <div class="admin-stat-card" style="border-left:3px solid var(--a-green)">
        <p class="admin-stat-card__label">New Customers (7d)</p>
        <p class="admin-stat-card__value" style="font-size:1.4rem"><?= $newCustomers ?></p>
    </div>
    <div class="admin-stat-card" style="border-left:3px solid var(--a-gold)">
        <p class="admin-stat-card__label">Newsletter Subs</p>
        <p class="admin-stat-card__value" style="font-size:1.4rem"><?= $totalSubs ?></p>
    </div>
</div>

<div class="admin-dashboard-grid">
    <!-- REVENUE CHART -->
    <div class="admin-dashboard-panel">
        <h3 class="admin-dashboard-panel__title">Revenue — Last 7 Days</h3>
        <div id="chart" style="display:flex;align-items:flex-end;gap:8px;height:180px;padding-top:8px">
            <?php
            $maxVal = max(1, max($chartData));
            foreach ($chartData as $i => $val):
                $pct = ($val / $maxVal) * 100;
                $height = max(4, $pct) . '%';
            ?>
            <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:6px;height:100%">
                <span style="font-size:.65rem;color:var(--a-muted)"><?= $val > 0 ? money($val) : '—' ?></span>
                <div style="flex:1;width:100%;display:flex;align-items:flex-end">
                    <div style="width:100%;height:<?= $height ?>;background:linear-gradient(180deg,var(--a-accent),rgba(196,113,74,.3));border-radius:3px 3px 0 0;transition:height .3s"></div>
                </div>
                <span style="font-size:.7rem;color:var(--a-muted)"><?= $chartLabels[$i] ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ORDER STATUS BREAKDOWN -->
    <div class="admin-dashboard-panel">
        <h3 class="admin-dashboard-panel__title">Order Status</h3>
        <?php if (empty($statusBreakdown)): ?>
            <p style="color:var(--a-muted);font-size:.85rem">No orders yet</p>
        <?php else: ?>
            <?php
            $totalStatusOrders = array_sum(array_column($statusBreakdown, 'cnt'));
            foreach ($statusBreakdown as $sb):
                $pct = round(($sb['cnt'] / max(1, $totalStatusOrders)) * 100);
            ?>
            <div style="margin-bottom:14px">
                <div style="display:flex;justify-content:space-between;font-size:.8rem;margin-bottom:4px">
                    <span style="text-transform:capitalize"><?= h($sb['status']) ?></span>
                    <span style="color:var(--a-muted)"><?= $sb['cnt'] ?> (<?= $pct ?>%)</span>
                </div>
                <div style="height:6px;background:var(--a-bg);border-radius:3px;overflow:hidden">
                    <div style="height:100%;width:<?= $pct ?>%;background:var(--a-accent);border-radius:3px;transition:width .6s ease"></div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<div class="admin-dashboard-grid--equal">
    <!-- TOP PRODUCTS -->
    <div class="admin-dashboard-panel">
        <h3 class="admin-dashboard-panel__title">Top Products</h3>
        <?php if (empty($topProducts)): ?>
            <p style="color:var(--a-muted);font-size:.85rem">No sales data yet</p>
        <?php else: ?>
            <?php foreach ($topProducts as $idx => $tp): ?>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;<?= $idx < count($topProducts)-1 ? 'border-bottom:1px solid var(--a-border)' : '' ?>">
                <div>
                    <span style="font-size:.7rem;color:var(--a-accent);margin-right:6px">#<?= $idx + 1 ?></span>
                    <span style="font-size:.85rem"><?= h($tp['name']) ?></span>
                </div>
                <div style="text-align:right">
                    <span style="font-size:.82rem"><?= (int)$tp['total_qty'] ?> sold</span>
                    <br><span style="font-size:.72rem;color:var(--a-muted)"><?= money((float)$tp['total_revenue']) ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- RECENT ORDERS -->
    <div class="admin-dashboard-panel">
        <h3 class="admin-dashboard-panel__title">
            Recent Orders
            <a href="<?= SITE_URL ?>/admin/orders.php">View All →</a>
        </h3>
        <?php if (empty($recentOrders)): ?>
            <p style="color:var(--a-muted);font-size:.85rem">No orders yet</p>
        <?php else: ?>
            <?php foreach ($recentOrders as $idx => $o): ?>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;<?= $idx < count($recentOrders)-1 ? 'border-bottom:1px solid var(--a-border)' : '' ?>">
                <div>
                    <a href="<?= SITE_URL ?>/admin/orders.php?id=<?= $o['id'] ?>" style="font-size:.85rem;color:var(--a-accent)"><?= h($o['order_number']) ?></a>
                    <br><span style="font-size:.72rem;color:var(--a-muted)"><?= h($o['shipping_name'] ?? '') ?></span>
                </div>
                <div style="text-align:right">
                    <span class="admin-badge admin-badge--<?= h($o['status']) ?>"><?= h($o['status']) ?></span>
                    <br><span style="font-size:.82rem;margin-top:2px;display:inline-block"><?= money((float)$o['total']) ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($recentActivity)): ?>
<!-- RECENT ACTIVITY -->
<div class="admin-dashboard-panel" style="margin-top:24px">
    <h3 class="admin-dashboard-panel__title">
        Recent Activity
        <a href="<?= SITE_URL ?>/admin/activity.php">View All →</a>
    </h3>
    <div class="admin-timeline">
        <?php foreach ($recentActivity as $act): 
            $icons = ['create' => '➕', 'update' => '✏️', 'delete' => '🗑️', 'login' => '🔑', 'export' => '📤'];
            $icon = '📋';
            foreach ($icons as $key => $emoji) {
                if (stripos($act['action'], $key) !== false) { $icon = $emoji; break; }
            }
            $who = trim(($act['first_name'] ?? '') . ' ' . ($act['last_name'] ?? ''));
            if (!$who) $who = 'System';
            $timeAgo = '';
            $diff = time() - strtotime($act['created_at']);
            if ($diff < 60) $timeAgo = 'just now';
            elseif ($diff < 3600) $timeAgo = floor($diff/60) . 'm ago';
            elseif ($diff < 86400) $timeAgo = floor($diff/3600) . 'h ago';
            else $timeAgo = floor($diff/86400) . 'd ago';
        ?>
        <div class="admin-timeline__item">
            <div class="admin-timeline__dot"></div>
            <div class="admin-timeline__content">
                <span style="margin-right:4px"><?= $icon ?></span>
                <strong><?= h($who) ?></strong> <?= h($act['action']) ?>
                <?php if ($act['entity_type']): ?>
                    <span style="color:var(--a-muted)"><?= h($act['entity_type']) ?></span>
                <?php endif; ?>
            </div>
            <div class="admin-timeline__meta">
                <span><?= $timeAgo ?></span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
