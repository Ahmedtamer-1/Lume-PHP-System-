<?php
/**
 * Admin — Messages (Contact Form Submissions)
 */
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Messages';
$adminPage = 'messages';

$success = '';
$error   = '';

// ── MARK AS READ/UNREAD ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message_id'], $_POST['toggle_read'])) {
    $mid = (int) $_POST['message_id'];
    $newRead = (int) $_POST['toggle_read'];
    db()->prepare('UPDATE contact_messages SET is_read = ? WHERE id = ?')->execute([$newRead, $mid]);
    $label = $newRead ? 'read' : 'unread';
    header('Location: ' . SITE_URL . '/admin/messages.php?id=' . $mid . '&msg=marked_' . $label);
    exit;
}

// ── DELETE ──
if (($_GET['action'] ?? '') === 'delete' && isset($_GET['id'])) {
    $delId = (int) $_GET['id'];
    db()->prepare('DELETE FROM contact_messages WHERE id = ?')->execute([$delId]);
    header('Location: ' . SITE_URL . '/admin/messages.php?msg=deleted');
    exit;
}

if (isset($_GET['msg'])) {
    $msgs = [
        'deleted'       => 'Message deleted.',
        'marked_read'   => 'Message marked as read.',
        'marked_unread' => 'Message marked as unread.',
    ];
    $success = $msgs[$_GET['msg']] ?? '';
}

// ── Unread count (always needed) ──
$unreadCount = (int) db()->query('SELECT COUNT(*) FROM contact_messages WHERE is_read = 0')->fetchColumn();

// ── VIEW SINGLE MESSAGE ──
if (isset($_GET['id']) && ($_GET['action'] ?? '') !== 'delete') {
    $mid = (int) $_GET['id'];
    $stmt = db()->prepare('SELECT * FROM contact_messages WHERE id = ?');
    $stmt->execute([$mid]);
    $message = $stmt->fetch();
    if (!$message) { header('Location: ' . SITE_URL . '/admin/messages.php'); exit; }

    // Auto-mark as read when viewing
    if (!$message['is_read']) {
        db()->prepare('UPDATE contact_messages SET is_read = 1 WHERE id = ?')->execute([$mid]);
        $message['is_read'] = 1;
        $unreadCount = max(0, $unreadCount - 1);
    }

    $pageTitle = 'Message from ' . $message['name'];
    require_once __DIR__ . '/includes/header.php';
    ?>

    <?php if ($success): ?><div class="admin-alert admin-alert--success"><?= h($success) ?></div><?php endif; ?>

    <div class="msg-detail-layout">
        <!-- Message Content -->
        <div>
            <div class="msg-detail-card">
                <div class="msg-detail-card__header">
                    <h3 class="msg-detail-card__subject"><?= h($message['subject']) ?></h3>
                    <span class="admin-badge admin-badge--<?= $message['is_read'] ? 'active' : 'shipped' ?>">
                        <?= $message['is_read'] ? 'Read' : 'Unread' ?>
                    </span>
                </div>
                <div class="msg-detail-card__body">
                    <?= nl2br(h($message['message'])) ?>
                </div>
                <div class="msg-detail-card__footer">
                    <span style="color:var(--a-muted);font-size:.75rem">
                        Received <?= date('d M Y \a\t H:i', strtotime($message['created_at'])) ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div>
            <div class="msg-sidebar-card">
                <h3 class="msg-sidebar-card__title">Sender Info</h3>
                <div class="msg-sidebar-card__field">
                    <span class="msg-sidebar-card__label">Name</span>
                    <span><?= h($message['name']) ?></span>
                </div>
                <div class="msg-sidebar-card__field">
                    <span class="msg-sidebar-card__label">Email</span>
                    <a href="mailto:<?= h($message['email']) ?>" style="color:var(--a-accent);word-break:break-all"><?= h($message['email']) ?></a>
                </div>
                <div class="msg-sidebar-card__field">
                    <span class="msg-sidebar-card__label">IP Address</span>
                    <span style="color:var(--a-muted)"><?= h($message['ip'] ?? '—') ?></span>
                </div>
                <div class="msg-sidebar-card__field">
                    <span class="msg-sidebar-card__label">Date</span>
                    <span style="color:var(--a-muted)"><?= date('d M Y H:i', strtotime($message['created_at'])) ?></span>
                </div>
            </div>

            <!-- Actions -->
            <form method="post" style="margin-top:12px">
                <input type="hidden" name="message_id" value="<?= $message['id'] ?>">
                <input type="hidden" name="toggle_read" value="<?= $message['is_read'] ? '0' : '1' ?>">
                <button type="submit" class="admin-btn admin-btn--full">
                    <?= $message['is_read'] ? 'Mark as Unread' : 'Mark as Read' ?>
                </button>
            </form>

            <a href="<?= SITE_URL ?>/admin/messages.php?action=delete&id=<?= $message['id'] ?>"
               class="admin-btn admin-btn--danger admin-btn--full" style="margin-top:8px"
               onclick="return confirm('Delete this message permanently?')">Delete Message</a>

            <a href="<?= SITE_URL ?>/admin/messages.php" class="admin-btn admin-btn--full" style="margin-top:8px">← Back to Messages</a>
        </div>
    </div>

    <style>
    .msg-detail-layout{display:grid;grid-template-columns:1fr 320px;gap:24px}
    .msg-detail-card{background:var(--a-surface);border:1px solid var(--a-border);border-radius:var(--a-radius);overflow:hidden}
    .msg-detail-card__header{display:flex;justify-content:space-between;align-items:center;padding:20px 24px;border-bottom:1px solid var(--a-border);gap:12px;flex-wrap:wrap}
    .msg-detail-card__subject{font-size:1rem;font-weight:600}
    .msg-detail-card__body{padding:24px;font-size:.9rem;line-height:1.8;color:var(--a-text)}
    .msg-detail-card__footer{padding:16px 24px;border-top:1px solid var(--a-border)}
    .msg-sidebar-card{background:var(--a-surface);border:1px solid var(--a-border);border-radius:var(--a-radius);padding:24px}
    .msg-sidebar-card__title{font-size:.85rem;text-transform:uppercase;letter-spacing:.08em;margin-bottom:16px;color:var(--a-gold)}
    .msg-sidebar-card__field{margin-bottom:12px;font-size:.85rem}
    .msg-sidebar-card__label{display:block;font-size:.72rem;text-transform:uppercase;letter-spacing:.1em;color:var(--a-muted);margin-bottom:4px}
    @media(max-width:768px){
        .msg-detail-layout{grid-template-columns:1fr}
    }
    </style>

    <?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// ── LIST VIEW ──
$filter = $_GET['filter'] ?? 'all';
$sql = 'SELECT * FROM contact_messages';
if ($filter === 'unread') {
    $sql .= ' WHERE is_read = 0';
} elseif ($filter === 'read') {
    $sql .= ' WHERE is_read = 1';
}
$sql .= ' ORDER BY created_at DESC';
$messages = db()->query($sql)->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<?php if ($success): ?><div class="admin-alert admin-alert--success"><?= h($success) ?></div><?php endif; ?>

<div class="admin-toolbar">
    <div class="admin-toolbar__left">
        <span style="font-size:.85rem;color:var(--a-muted)">
            <?= count($messages) ?> messages
            <?php if ($unreadCount > 0): ?>
                · <strong style="color:var(--a-accent)"><?= $unreadCount ?> unread</strong>
            <?php endif; ?>
        </span>
    </div>
    <div class="admin-toolbar__right">
        <a href="<?= SITE_URL ?>/admin/messages.php"
           class="admin-btn admin-btn--sm <?= $filter === 'all' ? 'admin-btn--primary' : '' ?>">All</a>
        <a href="<?= SITE_URL ?>/admin/messages.php?filter=unread"
           class="admin-btn admin-btn--sm <?= $filter === 'unread' ? 'admin-btn--primary' : '' ?>">Unread</a>
        <a href="<?= SITE_URL ?>/admin/messages.php?filter=read"
           class="admin-btn admin-btn--sm <?= $filter === 'read' ? 'admin-btn--primary' : '' ?>">Read</a>
    </div>
</div>

<?php if (empty($messages)): ?>
<div class="admin-empty">
    <div class="admin-empty__icon">✉️</div>
    <p class="admin-empty__text">No messages found.</p>
</div>
<?php else: ?>
<div class="admin-table-wrap admin-responsive-table">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Sender</th>
                <th>Email</th>
                <th>Subject</th>
                <th>Date</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($messages as $m): ?>
            <tr class="<?= !$m['is_read'] ? 'msg-row--unread' : '' ?>">
                <td><strong><?= h($m['name']) ?></strong></td>
                <td style="color:var(--a-muted)"><?= h($m['email']) ?></td>
                <td>
                    <a href="<?= SITE_URL ?>/admin/messages.php?id=<?= $m['id'] ?>" style="color:var(--a-accent)">
                        <?= h($m['subject']) ?>
                    </a>
                </td>
                <td style="color:var(--a-muted)"><?= date('d M Y', strtotime($m['created_at'])) ?></td>
                <td>
                    <span class="admin-badge admin-badge--<?= $m['is_read'] ? 'active' : 'shipped' ?>">
                        <?= $m['is_read'] ? 'Read' : 'Unread' ?>
                    </span>
                </td>
                <td style="white-space:nowrap">
                    <a href="<?= SITE_URL ?>/admin/messages.php?id=<?= $m['id'] ?>" class="admin-btn admin-btn--sm">View</a>
                    <a href="<?= SITE_URL ?>/admin/messages.php?action=delete&id=<?= $m['id'] ?>" class="admin-btn admin-btn--sm admin-btn--danger"
                       onclick="return confirm('Delete this message?')">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<style>
.msg-row--unread td{border-left-color:var(--a-accent)}
.msg-row--unread{position:relative}
.msg-row--unread td:first-child{border-left:3px solid var(--a-accent)}
.admin-responsive-table{overflow-x:auto;-webkit-overflow-scrolling:touch}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
