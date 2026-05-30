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

// ── Inventory Alerts ──
$lowStockCount = 0;
try {
    $lowStockCount = (int) db()->query('SELECT COUNT(*) FROM products WHERE stock <= 5 AND is_active = 1')->fetchColumn();
} catch (Exception $e) {}

// ── Abandoned Carts ──
$abandonedCarts = 0;
try {
    $abandonedCarts = (int) db()->query('SELECT COUNT(DISTINCT session_key) FROM cart_sessions WHERE updated_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)')->fetchColumn();
} catch (Exception $e) {}

// ── Sparkline Data ──
$sparklineData = !empty($chartData) ? json_encode(array_slice($chartData, -7)) : '[]';

require_once __DIR__ . '/includes/header.php';
?>

<!-- INVENTORY ALERT -->
<?php if ($lowStockCount > 0): ?>
<div class="inventory-alert">
    <div class="inventory-alert__icon">
        <svg viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
    </div>
    <div class="inventory-alert__text">
        <div class="inventory-alert__title"><?= $lowStockCount ?> Products Low on Stock</div>
        <div class="inventory-alert__sub">Inventory is running low and needs to be restocked.</div>
    </div>
    <a href="<?= SITE_URL ?>/admin/products.php" class="inventory-alert__link">View Products</a>
</div>
<?php endif; ?>

<!-- TOP 4 METRIC CARDS -->
<div class="admin-stats">
    <!-- Revenue -->
    <div class="admin-stat-card admin-stat-card--accent">
        <div class="admin-stat-card__label">Revenue</div>
        <div class="admin-stat-card__value accent"><?= money($periodRevenue) ?></div>
        <?php if ($revenueTrend != 0): ?>
        <span class="admin-stat-card__trend admin-stat-card__trend--<?= $revenueTrend >= 0 ? 'up' : 'down' ?>">
            <svg viewBox="0 0 24 24"><polyline points="<?= $revenueTrend >= 0 ? '18 15 12 9 6 15' : '6 9 12 15 18 9' ?>"/></svg>
            <?= $revenueTrend > 0 ? '+' : '' ?><?= $revenueTrend ?>%
        </span>
        <?php endif; ?>
        <svg class="metric-sparkline metric-sparkline--green" viewBox="0 0 100 32" preserveAspectRatio="none" id="sparkline-revenue"></svg>
    </div>

    <!-- Orders -->
    <div class="admin-stat-card">
        <div class="admin-stat-card__label">Orders</div>
        <div class="admin-stat-card__value"><?= $periodOrders ?></div>
        <?php if ($ordersTrend != 0): ?>
        <span class="admin-stat-card__trend admin-stat-card__trend--<?= $ordersTrend >= 0 ? 'up' : 'down' ?>">
            <svg viewBox="0 0 24 24"><polyline points="<?= $ordersTrend >= 0 ? '18 15 12 9 6 15' : '6 9 12 15 18 9' ?>"/></svg>
            <?= $ordersTrend > 0 ? '+' : '' ?><?= $ordersTrend ?>%
        </span>
        <?php endif; ?>
    </div>

    <!-- AOV -->
    <div class="admin-stat-card">
        <div class="admin-stat-card__label">Avg Order Value</div>
        <div class="admin-stat-card__value"><?= money($aov) ?></div>
    </div>

    <!-- Abandoned Carts -->
    <div class="admin-stat-card">
        <div class="admin-stat-card__label">Abandoned Carts</div>
        <div class="admin-stat-card__value"><?= $abandonedCarts ?></div>
        <span class="admin-stat-card__trend" style="margin-top:8px;display:block;opacity:0.6;font-weight:normal;">Last 30 days sessions</span>
    </div>
</div>

<!-- FINANCIAL INSIGHTS: RADIAL DIALS -->
<div class="radial-dials">
    <?php
    // Calculate percentages relative to Gross Product Revenue
    $revPct = 100;
    $cogsPct = $netProductRevenue > 0 ? min(100, round(($totalCogs / $netProductRevenue) * 100)) : 0;
    $adPct = $netProductRevenue > 0 ? min(100, round(($totalAdSpend / $netProductRevenue) * 100)) : 0;
    $profitPct = $netProductRevenue > 0 ? max(0, min(100, round(($netProfit / $netProductRevenue) * 100))) : 0;

    function renderDial($pct, $val, $label, $colorVar) {
        // Circumference of r=52 is ~326
        $dashTotal = 326;
        $dashOffset = $dashTotal - ($dashTotal * ($pct / 100));
        return '
        <div class="radial-dial-card">
            <div class="radial-dial__wrap">
                <svg class="radial-dial-svg" style="--dash-total: '.$dashTotal.'; --dash-offset: '.$dashOffset.';">
                    <circle class="dial-track" cx="60" cy="60" r="52"></circle>
                    <circle class="dial-progress" cx="60" cy="60" r="52" style="stroke: var('.$colorVar.');"></circle>
                </svg>
                <div class="radial-dial__center">
                    <div class="radial-dial__value">'.money($val).'</div>
                </div>
            </div>
            <div class="radial-dial__name">'.$label.'</div>
        </div>';
    }
    echo renderDial($revPct, $netProductRevenue, 'Product Rev', '--dial-revenue');
    echo renderDial($cogsPct, $totalCogs, 'COGS', '--dial-cogs');
    echo renderDial($adPct, $totalAdSpend, 'Ad Spend', '--dial-adspend');
    echo renderDial($profitPct, $netProfit, 'Net Profit', '--dial-profit');
    ?>
</div>

<!-- MAIN CHARTS ROW -->
<div class="admin-dashboard-grid">
    <!-- Revenue Line Chart -->
    <div class="admin-dashboard-panel">
        <div class="admin-dashboard-panel__title">Revenue Trend</div>
        <div class="chart-container">
            <?php if (empty($chartData)): ?>
                <div class="admin-empty" style="padding: 40px 20px;">
                    <div class="admin-empty__icon">
                        <svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                    </div>
                    <div class="admin-empty__text">No orders yet</div>
                </div>
            <?php else: ?>
                <canvas id="revenueChart"></canvas>
            <?php endif; ?>
        </div>
    </div>

    <!-- Deductions Table -->
    <div class="admin-dashboard-panel">
        <div class="admin-dashboard-panel__title">Deductions</div>
        <table class="deductions-table">
            <tr>
                <td class="d-label">Shipping Collected</td>
                <td class="d-value d-pos">+<?= money($totalShipping) ?></td>
            </tr>
            <tr>
                <td class="d-label">Discounts Given</td>
                <td class="d-value d-neg">-<?= money($totalDiscounts) ?></td>
            </tr>
            <tr>
                <td class="d-label">Cost of Goods</td>
                <td class="d-value d-neg">-<?= money($totalCogs) ?></td>
            </tr>
            <tr>
                <td class="d-label">Ad Spend</td>
                <td class="d-value d-neg">-<?= money($totalAdSpend) ?></td>
            </tr>
        </table>
    </div>
</div>

<!-- BOTTOM WIDGETS ROW -->
<div class="admin-dashboard-grid--3">
    <!-- Conversion Funnel -->
    <div class="admin-dashboard-panel">
        <div class="admin-dashboard-panel__title">Conversion Funnel</div>
        <div class="funnel-wrap">
            <div class="funnel-step">
                <div class="funnel-step__label">Visitors</div>
                <div class="funnel-step__bar-wrap"><div class="funnel-step__bar" style="width: 100%; opacity: 0.2"></div></div>
                <div class="funnel-step__count">--</div>
            </div>
            <div class="funnel-step">
                <div class="funnel-step__label">Add to Cart</div>
                <div class="funnel-step__bar-wrap"><div class="funnel-step__bar" style="width: 60%; opacity: 0.4"></div></div>
                <div class="funnel-step__count">--</div>
            </div>
            <div class="funnel-step">
                <div class="funnel-step__label">Checkout</div>
                <div class="funnel-step__bar-wrap"><div class="funnel-step__bar" style="width: 30%; opacity: 0.7"></div></div>
                <div class="funnel-step__count">--</div>
            </div>
            <div class="funnel-step">
                <div class="funnel-step__label">Orders</div>
                <div class="funnel-step__bar-wrap"><div class="funnel-step__bar" style="width: <?= min(100, max(5, $periodOrders)) ?>%"></div></div>
                <div class="funnel-step__count"><?= $periodOrders ?></div>
            </div>
            <div style="font-size:11px;color:var(--text-muted);text-align:center;margin-top:10px;">Connect Analytics for complete funnel.</div>
        </div>
    </div>

    <!-- Product Performance -->
    <div class="admin-dashboard-panel">
        <div class="admin-dashboard-panel__title">Top Products</div>
        <?php if (empty($topProducts)): ?>
            <div class="admin-empty" style="padding:20px 0;"><div class="admin-empty__text">No sales data</div></div>
        <?php else: ?>
            <table class="admin-table" style="font-size:12px;">
                <thead>
                    <tr>
                        <th style="padding:6px 0;background:transparent;border:none;">Product</th>
                        <th style="padding:6px 0;background:transparent;border:none;text-align:right;">Sold</th>
                        <th style="padding:6px 0;background:transparent;border:none;text-align:right;">Rev</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($topProducts as $tp): ?>
                    <tr>
                        <td style="padding:8px 0;border-bottom:0.5px solid var(--border);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:120px;"><?= h($tp['name']) ?></td>
                        <td style="padding:8px 0;border-bottom:0.5px solid var(--border);text-align:right;"><?= (int)$tp['total_qty'] ?></td>
                        <td style="padding:8px 0;border-bottom:0.5px solid var(--border);text-align:right;"><?= money((float)$tp['total_revenue']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- Activity Log -->
    <div class="admin-dashboard-panel">
        <div class="admin-dashboard-panel__title">
            Activity Log
            <a href="<?= SITE_URL ?>/admin/activity.php">View All</a>
        </div>
        <?php if (empty($recentActivity)): ?>
            <div class="admin-empty" style="padding:20px 0;"><div class="admin-empty__text">No recent activity</div></div>
        <?php else: ?>
            <div class="admin-timeline">
                <?php foreach ($recentActivity as $act): 
                    $who = trim(($act['first_name'] ?? '') . ' ' . ($act['last_name'] ?? ''));
                    if (!$who) $who = 'System';
                    $diff = time() - strtotime($act['created_at']);
                    if ($diff < 60) $timeAgo = 'now';
                    elseif ($diff < 3600) $timeAgo = floor($diff/60) . 'm ago';
                    elseif ($diff < 86400) $timeAgo = floor($diff/3600) . 'h ago';
                    else $timeAgo = floor($diff/86400) . 'd ago';
                ?>
                <div class="admin-timeline__item">
                    <div class="admin-timeline__dot"></div>
                    <div class="admin-timeline__content">
                        <strong><?= h($who) ?></strong> <?= h($act['action']) ?>
                    </div>
                    <div class="admin-timeline__meta"><?= $timeAgo ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
window.chartLabels = <?= json_encode($chartLabels) ?>;
window.chartData = <?= json_encode($chartData) ?>;
window.sparklineData = <?= $sparklineData ?>;
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
