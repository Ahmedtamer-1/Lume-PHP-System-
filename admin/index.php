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

require_once __DIR__ . '/includes/header.php';
?>

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

<!-- MONTH STATS -->
<div class="admin-stats" style="margin-bottom:32px">
    <div class="admin-stat-card" style="border-left:3px solid var(--a-accent)">
        <p class="admin-stat-card__label">This Month Revenue</p>
        <p class="admin-stat-card__value" style="font-size:1.4rem"><?= money($monthRevenue) ?></p>
    </div>
    <div class="admin-stat-card" style="border-left:3px solid var(--a-accent)">
        <p class="admin-stat-card__label">This Month Orders</p>
        <p class="admin-stat-card__value" style="font-size:1.4rem"><?= $monthOrders ?></p>
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

<div style="display:grid;grid-template-columns:2fr 1fr;gap:24px;margin-bottom:32px">
    <!-- REVENUE CHART -->
    <div style="background:var(--a-surface);border:1px solid var(--a-border);border-radius:var(--a-radius);padding:24px">
        <h3 style="font-size:.85rem;text-transform:uppercase;letter-spacing:.08em;margin-bottom:20px;color:var(--a-gold)">Revenue — Last 7 Days</h3>
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
    <div style="background:var(--a-surface);border:1px solid var(--a-border);border-radius:var(--a-radius);padding:24px">
        <h3 style="font-size:.85rem;text-transform:uppercase;letter-spacing:.08em;margin-bottom:20px;color:var(--a-gold)">Order Status</h3>
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
                    <div style="height:100%;width:<?= $pct ?>%;background:var(--a-accent);border-radius:3px"></div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px">
    <!-- TOP PRODUCTS -->
    <div style="background:var(--a-surface);border:1px solid var(--a-border);border-radius:var(--a-radius);padding:24px">
        <h3 style="font-size:.85rem;text-transform:uppercase;letter-spacing:.08em;margin-bottom:16px;color:var(--a-gold)">Top Products</h3>
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
    <div style="background:var(--a-surface);border:1px solid var(--a-border);border-radius:var(--a-radius);padding:24px">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
            <h3 style="font-size:.85rem;text-transform:uppercase;letter-spacing:.08em;color:var(--a-gold)">Recent Orders</h3>
            <a href="<?= SITE_URL ?>/admin/orders.php" style="font-size:.72rem;color:var(--a-accent);text-transform:uppercase;letter-spacing:.06em">View All →</a>
        </div>
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

<?php require_once __DIR__ . '/includes/footer.php'; ?>
