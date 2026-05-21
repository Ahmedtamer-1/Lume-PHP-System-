<?php
/**
 * Admin Dashboard — Enhanced Analytics
 */
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Dashboard';
$adminPage = 'dashboard';

// Ensure ad_spend table exists just in case
try {
    db()->exec("CREATE TABLE IF NOT EXISTS ad_spend (
        id INT AUTO_INCREMENT PRIMARY KEY,
        platform VARCHAR(50) NOT NULL,
        campaign_name VARCHAR(100) NULL,
        amount DECIMAL(10,2) NOT NULL,
        date_logged DATE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
} catch (Exception $e) {}

// ── Date Range Filter ──
$range = $_GET['range'] ?? 'this_month';
$startDate = '';
$endDate = '';
$prevStartDate = '';
$prevEndDate = '';
$rangeLabel = '';

$today = date('Y-m-d');
switch ($range) {
    case 'today':
        $startDate = $today;
        $endDate = $today;
        $prevStartDate = date('Y-m-d', strtotime('-1 day'));
        $prevEndDate = $prevStartDate;
        $rangeLabel = 'Today';
        break;
    case 'yesterday':
        $startDate = date('Y-m-d', strtotime('-1 day'));
        $endDate = $startDate;
        $prevStartDate = date('Y-m-d', strtotime('-2 days'));
        $prevEndDate = $prevStartDate;
        $rangeLabel = 'Yesterday';
        break;
    case '7days':
        $startDate = date('Y-m-d', strtotime('-6 days')); // Last 7 days including today
        $endDate = $today;
        $prevStartDate = date('Y-m-d', strtotime('-13 days'));
        $prevEndDate = date('Y-m-d', strtotime('-7 days'));
        $rangeLabel = 'Last 7 Days';
        break;
    case '30days':
        $startDate = date('Y-m-d', strtotime('-29 days')); // Last 30 days including today
        $endDate = $today;
        $prevStartDate = date('Y-m-d', strtotime('-59 days'));
        $prevEndDate = date('Y-m-d', strtotime('-30 days'));
        $rangeLabel = 'Last 30 Days';
        break;
    case 'this_month':
        $startDate = date('Y-m-01');
        $endDate = date('Y-m-t');
        $prevStartDate = date('Y-m-01', strtotime('-1 month'));
        $prevEndDate = date('Y-m-t', strtotime('-1 month'));
        $rangeLabel = 'This Month';
        break;
    case 'last_month':
        $startDate = date('Y-m-01', strtotime('-1 month'));
        $endDate = date('Y-m-t', strtotime('-1 month'));
        $prevStartDate = date('Y-m-01', strtotime('-2 months'));
        $prevEndDate = date('Y-m-t', strtotime('-2 months'));
        $rangeLabel = 'Last Month';
        break;
    case 'all_time':
    default:
        $startDate = '1970-01-01';
        $endDate = '2099-12-31';
        $prevStartDate = '1970-01-01';
        $prevEndDate = '1970-01-01';
        $rangeLabel = 'All Time';
        break;
}

$startDt = $startDate . ' 00:00:00';
$endDt   = $endDate . ' 23:59:59';
$pStartDt = $prevStartDate . ' 00:00:00';
$pEndDt   = $prevEndDate . ' 23:59:59';

// ── Global Stats (All Time) ──
$totalProducts  = (int) db()->query('SELECT COUNT(*) FROM products WHERE is_active = 1')->fetchColumn();
$totalOrders    = (int) db()->query('SELECT COUNT(*) FROM orders')->fetchColumn();
$totalCustomers = (int) db()->query('SELECT COUNT(*) FROM users WHERE role = "customer"')->fetchColumn();

// ── Filtered Stats ──
$stmtRev = db()->prepare('SELECT COALESCE(SUM(total),0) FROM orders WHERE status = "delivered" AND created_at >= ? AND created_at <= ?');
$stmtRev->execute([$startDt, $endDt]);
$periodRevenue = (float) $stmtRev->fetchColumn();

$stmtPrevRev = db()->prepare('SELECT COALESCE(SUM(total),0) FROM orders WHERE status = "delivered" AND created_at >= ? AND created_at <= ?');
$stmtPrevRev->execute([$pStartDt, $pEndDt]);
$prevPeriodRevenue = (float) $stmtPrevRev->fetchColumn();

$stmtOrd = db()->prepare('SELECT COUNT(*) FROM orders WHERE created_at >= ? AND created_at <= ?');
$stmtOrd->execute([$startDt, $endDt]);
$periodOrders = (int) $stmtOrd->fetchColumn();

$stmtPrevOrd = db()->prepare('SELECT COUNT(*) FROM orders WHERE created_at >= ? AND created_at <= ?');
$stmtPrevOrd->execute([$pStartDt, $pEndDt]);
$prevPeriodOrders = (int) $stmtPrevOrd->fetchColumn();

$stmtNewCust = db()->prepare('SELECT COUNT(*) FROM users WHERE created_at >= ? AND created_at <= ?');
$stmtNewCust->execute([$startDt, $endDt]);
$newCustomers = (int) $stmtNewCust->fetchColumn();

// ── Revenue Chart (Dynamic based on range) ──
$revenueDays = db()->prepare(
    'SELECT DATE(created_at) AS day, SUM(total) AS revenue
     FROM orders WHERE status = "delivered"
     AND created_at >= ? AND created_at <= ?
     GROUP BY DATE(created_at) ORDER BY day ASC'
);
$revenueDays->execute([$startDt, $endDt]);
$revenueDataRaw = $revenueDays->fetchAll();

$chartLabels = [];
$chartData   = [];

// Determine chart scale based on range
if ($range === 'today' || $range === 'yesterday') {
    // Show last 7 days anyway for chart context
    for ($i = 6; $i >= 0; $i--) {
        $day = date('Y-m-d', strtotime("-{$i} days"));
        $chartLabels[] = date('D', strtotime($day));
        $found = false;
        foreach ($revenueDataRaw as $r) {
            if ($r['day'] === $day) { $chartData[] = (float)$r['revenue']; $found = true; break; }
        }
        if (!$found) $chartData[] = 0;
    }
} else if ($range === 'this_month' || $range === 'last_month' || $range === '30days') {
    // Show weeks or just all days? Let's sample 15 data points or just group by day if < 31 days
    $diff = (strtotime($endDate) - strtotime($startDate)) / 86400;
    for ($i = 0; $i <= $diff; $i++) {
        $day = date('Y-m-d', strtotime($startDate . " +$i days"));
        // Only push label every few days to avoid crowding
        $chartLabels[] = ($i % 3 === 0) ? date('M j', strtotime($day)) : '';
        $found = false;
        foreach ($revenueDataRaw as $r) {
            if ($r['day'] === $day) { $chartData[] = (float)$r['revenue']; $found = true; break; }
        }
        if (!$found) $chartData[] = 0;
    }
} else if ($range === '7days') {
    for ($i = 6; $i >= 0; $i--) {
        $day = date('Y-m-d', strtotime($endDate . " -{$i} days"));
        $chartLabels[] = date('D', strtotime($day));
        $found = false;
        foreach ($revenueDataRaw as $r) {
            if ($r['day'] === $day) { $chartData[] = (float)$r['revenue']; $found = true; break; }
        }
        if (!$found) $chartData[] = 0;
    }
} else {
    // All time - group by month? For simplicity, we'll just show the raw returned days limited to 30 points
    // Let's just use the raw data if it's all time
    foreach ($revenueDataRaw as $idx => $r) {
        if ($idx > 30) break; // Limit
        $chartLabels[] = date('M j', strtotime($r['day']));
        $chartData[] = (float)$r['revenue'];
    }
    if (empty($chartData)) {
        $chartLabels = ['No Data'];
        $chartData = [0];
    }
}

// ── Order status breakdown ──
$stmtStatus = db()->prepare('SELECT status, COUNT(*) AS cnt FROM orders WHERE created_at >= ? AND created_at <= ? GROUP BY status ORDER BY cnt DESC');
$stmtStatus->execute([$startDt, $endDt]);
$statusBreakdown = $stmtStatus->fetchAll();

// ── Top products ──
$stmtTop = db()->prepare(
    'SELECT oi.name, SUM(oi.quantity) AS total_qty, SUM(oi.subtotal) AS total_revenue
     FROM order_items oi
     JOIN orders o ON o.id = oi.order_id
     WHERE o.status = "delivered" AND o.created_at >= ? AND o.created_at <= ?
     GROUP BY oi.name ORDER BY total_qty DESC LIMIT 5'
);
$stmtTop->execute([$startDt, $endDt]);
$topProducts = $stmtTop->fetchAll();

// ── Recent orders ──
$stmtRecent = db()->prepare('SELECT * FROM orders WHERE created_at >= ? AND created_at <= ? ORDER BY created_at DESC LIMIT 5');
$stmtRecent->execute([$startDt, $endDt]);
$recentOrders = $stmtRecent->fetchAll();

// ── Advanced Financials ──
$stmtValidCount = db()->prepare('SELECT COUNT(*) FROM orders WHERE status = "delivered" AND created_at >= ? AND created_at <= ?');
$stmtValidCount->execute([$startDt, $endDt]);
$validOrdersCount = (int) $stmtValidCount->fetchColumn();

$aov = $validOrdersCount > 0 ? $periodRevenue / $validOrdersCount : 0;

$stmtDisc = db()->prepare('SELECT COALESCE(SUM(discount),0) FROM orders WHERE status = "delivered" AND created_at >= ? AND created_at <= ?');
$stmtDisc->execute([$startDt, $endDt]);
$totalDiscounts = (float) $stmtDisc->fetchColumn();

$stmtShip = db()->prepare('SELECT COALESCE(SUM(shipping_cost),0) FROM orders WHERE status = "delivered" AND created_at >= ? AND created_at <= ?');
$stmtShip->execute([$startDt, $endDt]);
$totalShipping = (float) $stmtShip->fetchColumn();

$netProductRevenue = $periodRevenue - $totalShipping; // total includes shipping and discount.

$stmtCogs = db()->prepare('
    SELECT COALESCE(SUM(oi.cost_price * oi.quantity), 0)
    FROM order_items oi
    JOIN orders o ON o.id = oi.order_id
    WHERE o.status = "delivered" AND o.created_at >= ? AND o.created_at <= ?
');
$stmtCogs->execute([$startDt, $endDt]);
$totalCogs = (float) $stmtCogs->fetchColumn();

// ── Ad Spend ──
$stmtAd = db()->prepare('SELECT COALESCE(SUM(amount), 0) FROM ad_spend WHERE date_logged >= ? AND date_logged <= ?');
$stmtAd->execute([$startDate, $endDate]);
$totalAdSpend = (float) $stmtAd->fetchColumn();

$grossProfit = $netProductRevenue - $totalCogs;
$netProfit = $grossProfit - $totalAdSpend;

// Category revenue
$categoryRevenue = [];
try {
    $stmtCatRev = db()->prepare(
        'SELECT c.name, SUM(oi.subtotal) AS cat_revenue, SUM(oi.quantity) as items_sold
         FROM order_items oi
         JOIN orders o ON o.id = oi.order_id
         JOIN products p ON p.id = oi.product_id
         JOIN categories c ON c.id = p.category_id
         WHERE o.status = "delivered" AND o.created_at >= ? AND o.created_at <= ?
         GROUP BY c.id
         ORDER BY cat_revenue DESC LIMIT 5'
    );
    $stmtCatRev->execute([$startDt, $endDt]);
    $categoryRevenue = $stmtCatRev->fetchAll();
} catch (Exception $e) {}

// Newsletter subs
$totalSubs = (int) db()->query('SELECT COUNT(*) FROM newsletter_subscribers WHERE status = "active"')->fetchColumn();

// Recent activity
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
$revenueTrend = trendPct($periodRevenue, $prevPeriodRevenue);
$ordersTrend  = trendPct($periodOrders, $prevPeriodOrders);

require_once __DIR__ . '/includes/header.php';
?>

<!-- HEADER WITH FILTERS -->
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;">
    <div>
        <!-- Keep global quick actions here if you want -->
    </div>
    <form method="get" id="date-filter-form" style="display:flex;align-items:center;gap:12px;background:var(--a-surface);padding:8px 16px;border-radius:var(--a-radius);border:1px solid var(--a-border);">
        <label style="font-size:.85rem;font-weight:600;color:var(--a-muted)">Date Range:</label>
        <select name="range" onchange="document.getElementById('date-filter-form').submit()" style="background:var(--a-bg);border:1px solid var(--a-border);color:var(--a-text);padding:6px 12px;border-radius:4px;font-size:.85rem;">
            <option value="today" <?= $range === 'today' ? 'selected' : '' ?>>Today</option>
            <option value="yesterday" <?= $range === 'yesterday' ? 'selected' : '' ?>>Yesterday</option>
            <option value="7days" <?= $range === '7days' ? 'selected' : '' ?>>Last 7 Days</option>
            <option value="30days" <?= $range === '30days' ? 'selected' : '' ?>>Last 30 Days</option>
            <option value="this_month" <?= $range === 'this_month' ? 'selected' : '' ?>>This Month</option>
            <option value="last_month" <?= $range === 'last_month' ? 'selected' : '' ?>>Last Month</option>
            <option value="all_time" <?= $range === 'all_time' ? 'selected' : '' ?>>All Time</option>
        </select>
    </form>
</div>

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
    <a href="<?= SITE_URL ?>/admin/ad-spend.php" class="admin-quick-action" style="border-color:var(--a-gold);color:var(--a-gold)">
        <svg viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        Log Ad Spend
    </a>
    <a href="<?= SITE_URL ?>/admin/subscribers.php" class="admin-quick-action">
        <svg viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22 6 12 13 2 6"/></svg>
        Subscribers
    </a>
</div>

<!-- STAT CARDS (GLOBAL) -->
<div class="admin-stats">
    <div class="admin-stat-card">
        <p class="admin-stat-card__label">Total Customers (All Time)</p>
        <p class="admin-stat-card__value"><?= $totalCustomers ?></p>
    </div>
    <div class="admin-stat-card">
        <p class="admin-stat-card__label">Total Products (All Time)</p>
        <p class="admin-stat-card__value"><?= $totalProducts ?></p>
    </div>
    <div class="admin-stat-card">
        <p class="admin-stat-card__label">Total Orders (All Time)</p>
        <p class="admin-stat-card__value"><?= $totalOrders ?></p>
    </div>
    <div class="admin-stat-card">
        <p class="admin-stat-card__label">Newsletter Subs (All Time)</p>
        <p class="admin-stat-card__value"><?= $totalSubs ?></p>
    </div>
</div>

<!-- FILTERED STATS WITH TRENDS -->
<h2 style="font-size:1.1rem;font-weight:600;margin:32px 0 16px;color:var(--a-text)">Metrics for: <span style="color:var(--a-accent)"><?= $rangeLabel ?></span></h2>
<div class="admin-stats" style="margin-bottom:32px">
    <div class="admin-stat-card" style="border-left:3px solid var(--a-accent)">
        <p class="admin-stat-card__label">Revenue (<?= $rangeLabel ?>)</p>
        <p class="admin-stat-card__value" style="font-size:1.4rem"><?= money($periodRevenue) ?></p>
        <?php if ($revenueTrend != 0): ?>
        <span class="admin-stat-card__trend admin-stat-card__trend--<?= $revenueTrend >= 0 ? 'up' : 'down' ?>">
            <svg viewBox="0 0 24 24"><polyline points="<?= $revenueTrend >= 0 ? '18 15 12 9 6 15' : '6 9 12 15 18 9' ?>"/></svg>
            <?= abs($revenueTrend) ?>% vs prev. period
        </span>
        <?php endif; ?>
    </div>
    <div class="admin-stat-card" style="border-left:3px solid var(--a-accent)">
        <p class="admin-stat-card__label">Orders (<?= $rangeLabel ?>)</p>
        <p class="admin-stat-card__value" style="font-size:1.4rem"><?= $periodOrders ?></p>
        <?php if ($ordersTrend != 0): ?>
        <span class="admin-stat-card__trend admin-stat-card__trend--<?= $ordersTrend >= 0 ? 'up' : 'down' ?>">
            <svg viewBox="0 0 24 24"><polyline points="<?= $ordersTrend >= 0 ? '18 15 12 9 6 15' : '6 9 12 15 18 9' ?>"/></svg>
            <?= abs($ordersTrend) ?>% vs prev. period
        </span>
        <?php endif; ?>
    </div>
    <div class="admin-stat-card" style="border-left:3px solid var(--a-green)">
        <p class="admin-stat-card__label">New Customers (<?= $rangeLabel ?>)</p>
        <p class="admin-stat-card__value" style="font-size:1.4rem"><?= $newCustomers ?></p>
    </div>
    <div class="admin-stat-card" style="border-left:3px solid var(--a-gold)">
        <p class="admin-stat-card__label">Average Order Value</p>
        <p class="admin-stat-card__value" style="font-size:1.4rem"><?= money($aov) ?></p>
    </div>
</div>

<!-- FINANCIAL INSIGHTS (Net Profit) -->
<h2 style="font-size:1.1rem;font-weight:600;margin-bottom:16px;color:var(--a-text)">Financial Insights (<?= $rangeLabel ?>)</h2>
<div class="admin-stats" style="margin-bottom:32px">
    <div class="admin-stat-card">
        <p class="admin-stat-card__label">Gross Product Revenue</p>
        <p class="admin-stat-card__value green"><?= money($netProductRevenue) ?></p>
    </div>
    <div class="admin-stat-card">
        <p class="admin-stat-card__label">Cost of Goods (COGS)</p>
        <p class="admin-stat-card__value" style="color:var(--a-red)">-<?= money($totalCogs) ?></p>
    </div>
    <div class="admin-stat-card">
        <p class="admin-stat-card__label">Ad Spend (Marketing)</p>
        <p class="admin-stat-card__value" style="color:var(--a-red)">-<?= money($totalAdSpend) ?></p>
    </div>
    <div class="admin-stat-card" style="border-left:3px solid var(--a-gold);background:rgba(200,184,154,0.05)">
        <p class="admin-stat-card__label">Net Profit (<?= $rangeLabel ?>)</p>
        <p class="admin-stat-card__value" style="color:var(--a-gold);font-size:1.4rem"><?= money($netProfit) ?></p>
        <p style="font-size:.7rem;color:var(--a-muted);margin-top:4px">Revenue - COGS - Ad Spend</p>
    </div>
</div>

<div class="admin-dashboard-grid--equal" style="margin-bottom:32px">
    <!-- REVENUE BY CATEGORY -->
    <div class="admin-dashboard-panel">
        <h3 class="admin-dashboard-panel__title">Revenue by Category (<?= $rangeLabel ?>)</h3>
        <?php if (empty($categoryRevenue)): ?>
            <p style="color:var(--a-muted);font-size:.85rem">No category sales data yet</p>
        <?php else: ?>
            <?php foreach ($categoryRevenue as $idx => $cr): ?>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;<?= $idx < count($categoryRevenue)-1 ? 'border-bottom:1px solid var(--a-border)' : '' ?>">
                <div>
                    <span style="font-size:.85rem;font-weight:500"><?= h($cr['name']) ?></span>
                    <br><span style="font-size:.72rem;color:var(--a-muted)"><?= (int)$cr['items_sold'] ?> items sold</span>
                </div>
                <div style="text-align:right">
                    <span style="font-size:.85rem;color:var(--a-green)"><?= money((float)$cr['cat_revenue']) ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <!-- ADDITIONAL FINANCIAL METRICS -->
    <div class="admin-dashboard-panel">
        <h3 class="admin-dashboard-panel__title">Deductions & Collections (<?= $rangeLabel ?>)</h3>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid var(--a-border)">
            <span style="font-size:.85rem;font-weight:500">Shipping Collected</span>
            <span style="font-size:.85rem;color:var(--a-green)">+<?= money($totalShipping) ?></span>
        </div>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;">
            <span style="font-size:.85rem;font-weight:500">Discounts Given</span>
            <span style="font-size:.85rem;color:var(--a-red)">-<?= money($totalDiscounts) ?></span>
        </div>
        <p style="font-size:.75rem;color:var(--a-muted);margin-top:12px">Total Revenue at the top already includes shipping fees and has discounts deducted.</p>
    </div>
</div>

<div class="admin-dashboard-grid">
    <!-- REVENUE CHART -->
    <div class="admin-dashboard-panel">
        <h3 class="admin-dashboard-panel__title">Revenue Trend</h3>
        <div id="chart" style="display:flex;align-items:flex-end;gap:8px;height:180px;padding-top:8px;overflow-x:auto;">
            <?php
            $maxVal = max(1, empty($chartData) ? 0 : max($chartData));
            foreach ($chartData as $i => $val):
                $pct = ($val / $maxVal) * 100;
                $height = max(4, $pct) . '%';
            ?>
            <div style="flex:1;min-width:30px;display:flex;flex-direction:column;align-items:center;gap:6px;height:100%">
                <span style="font-size:.65rem;color:var(--a-muted)"><?= $val > 0 ? money($val) : '—' ?></span>
                <div style="flex:1;width:100%;display:flex;align-items:flex-end">
                    <div style="width:100%;height:<?= $height ?>;background:linear-gradient(180deg,var(--a-accent),rgba(196,113,74,.3));border-radius:3px 3px 0 0;transition:height .3s"></div>
                </div>
                <span style="font-size:.65rem;color:var(--a-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100%;"><?= $chartLabels[$i] ?? '' ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ORDER STATUS BREAKDOWN -->
    <div class="admin-dashboard-panel">
        <h3 class="admin-dashboard-panel__title">Order Status (<?= $rangeLabel ?>)</h3>
        <?php if (empty($statusBreakdown)): ?>
            <p style="color:var(--a-muted);font-size:.85rem">No orders in this period</p>
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
        <h3 class="admin-dashboard-panel__title">Top Products (<?= $rangeLabel ?>)</h3>
        <?php if (empty($topProducts)): ?>
            <p style="color:var(--a-muted);font-size:.85rem">No sales data in this period</p>
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
            <p style="color:var(--a-muted);font-size:.85rem">No orders in this period</p>
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
