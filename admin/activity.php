<?php
/**
 * Admin — Activity Log
 */
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Activity Log';
$adminPage = 'activity';

// ── CLEAR LOGS ──
if (($_GET['action'] ?? '') === 'clear') {
    db()->prepare('DELETE FROM activity_log')->execute();
    header('Location: ' . SITE_URL . '/admin/activity.php?msg=cleared');
    exit;
}

$success = '';
if (($_GET['msg'] ?? '') === 'cleared') {
    $success = 'Activity log cleared successfully.';
}

// ── PAGINATION ──
$page = (int)($_GET['page'] ?? 1);
$perPage = 50;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $perPage;

$totalQuery = db()->query('SELECT COUNT(*) FROM activity_log');
$totalRows = (int)$totalQuery->fetchColumn();
$totalPages = ceil($totalRows / $perPage);

// ── FETCH ACTIVITIES ──
$stmt = db()->prepare(
    'SELECT a.*, u.first_name, u.last_name, u.email 
     FROM activity_log a 
     LEFT JOIN users u ON a.user_id = u.id 
     ORDER BY a.created_at DESC 
     LIMIT ? OFFSET ?'
);
$stmt->bindValue(1, $perPage, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<?php if ($success): ?><div class="admin-alert admin-alert--success"><?= h($success) ?></div><?php endif; ?>

<div class="admin-toolbar">
    <div class="admin-toolbar__left">
        <h2 style="font-size:1.1rem">Activity Log (<?= $totalRows ?> entries)</h2>
    </div>
    <div class="admin-toolbar__right">
        <?php if ($totalRows > 0): ?>
        <a href="<?= SITE_URL ?>/admin/activity.php?action=clear" class="admin-btn admin-btn--danger admin-btn--sm" onclick="return confirm('Are you sure you want to clear the entire activity log? This cannot be undone.')">Clear Log</a>
        <?php endif; ?>
    </div>
</div>

<?php if (empty($logs)): ?>
<div class="admin-empty">
    <div class="admin-empty__icon">🕒</div>
    <p class="admin-empty__text">No activity recorded yet.</p>
</div>
<?php else: ?>
<div class="admin-table-wrap">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>User</th>
                <th>Action</th>
                <th>Entity</th>
                <th>Entity ID</th>
                <th>Details</th>
                <th>IP Address</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($logs as $log): ?>
            <tr>
                <td style="white-space:nowrap;color:var(--a-muted);font-size:.8rem">
                    <?= date('Y-m-d H:i:s', strtotime($log['created_at'])) ?>
                </td>
                <td>
                    <?php if ($log['first_name'] || $log['last_name']): ?>
                        <strong><?= h(trim($log['first_name'] . ' ' . $log['last_name'])) ?></strong>
                        <div style="font-size:.75rem;color:var(--a-muted)"><?= h($log['email']) ?></div>
                    <?php else: ?>
                        <span style="color:var(--a-muted)">System/Guest</span>
                    <?php endif; ?>
                </td>
                <td><span class="admin-badge" style="background:rgba(255,255,255,0.1);color:#eee"><?= h($log['action']) ?></span></td>
                <td style="color:var(--a-muted);font-size:.85rem"><?= h($log['entity_type'] ?? '—') ?></td>
                <td style="color:var(--a-muted);font-size:.85rem"><?= $log['entity_id'] ? (int)$log['entity_id'] : '—' ?></td>
                <td style="font-size:.85rem"><?= h($log['details'] ?? '—') ?></td>
                <td style="font-size:.8rem;color:var(--a-muted)"><?= h($log['ip_address'] ?? '—') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<div style="display:flex;justify-content:center;gap:8px;margin-top:24px">
    <?php if ($page > 1): ?>
    <a href="?page=<?= $page - 1 ?>" class="admin-btn admin-btn--sm">← Prev</a>
    <?php endif; ?>
    
    <span style="display:flex;align-items:center;padding:0 12px;font-size:.85rem;color:var(--a-muted)">
        Page <?= $page ?> of <?= $totalPages ?>
    </span>

    <?php if ($page < $totalPages): ?>
    <a href="?page=<?= $page + 1 ?>" class="admin-btn admin-btn--sm">Next →</a>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
