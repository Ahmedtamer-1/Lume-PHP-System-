<?php
/**
 * LUMEEGY — Track Order Page
 */
require_once __DIR__ . '/includes/functions.php';
lume_session_start();

$pageTitle = 'Track Order — LUMEEGY';
$error = '';
$order = null;

$orderNumber = trim($_GET['order_number'] ?? '');
$email = trim($_GET['email'] ?? '');

if (!empty($_GET) && ($orderNumber || $email)) {
    if (!$orderNumber || !$email) {
        $error = 'Please provide both your order number and email address.';
    } else {
        $stmt = db()->prepare('
            SELECT o.*, u.email as user_email 
            FROM orders o 
            LEFT JOIN users u ON u.id = o.user_id 
            WHERE o.order_number = ?
        ');
        $stmt->execute([$orderNumber]);
        $order = $stmt->fetch();

        if (!$order) {
            $error = 'Order not found. Please check your order number and try again.';
        } else {
            $orderEmail = $order['guest_email'] ?: $order['user_email'];
            if (strtolower(trim($orderEmail)) !== strtolower($email)) {
                $error = 'Order not found. Please check your email and try again.';
                $order = null;
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="lume-page-header">
    <div class="container">
        <h1 class="lume-page-header__title">Track Your Order</h1>
        <p class="lume-page-header__breadcrumb"><a href="<?= SITE_URL ?>/">Home</a> / Track Order</p>
    </div>
</section>

<section class="container" style="padding-top:40px;padding-bottom:120px;max-width:800px">
    
    <?php if ($error): ?>
        <div class="lume-alert lume-alert--error" style="margin-bottom:24px"><?= h($error) ?></div>
    <?php endif; ?>

    <?php if (!$order): ?>
    <div style="background:var(--surface);border:1px solid var(--border);border-radius:4px;padding:32px">
        <form method="get" class="lume-form" action="<?= SITE_URL ?>/track-order.php">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:24px">
                <div class="lume-form__group">
                    <label class="lume-form__label" for="order_number">Order Number</label>
                    <input class="lume-form__input" type="text" id="order_number" name="order_number" placeholder="e.g. #1042" value="<?= h($orderNumber) ?>" required>
                </div>
                <div class="lume-form__group">
                    <label class="lume-form__label" for="email">Email Address</label>
                    <input class="lume-form__input" type="email" id="email" name="email" placeholder="Email used during checkout" value="<?= h($email) ?>" required>
                </div>
            </div>
            <button type="submit" class="lume-btn lume-btn--solid">Track Order</button>
        </form>
    </div>
    <?php else: 
        $items = db()->prepare('SELECT * FROM order_items WHERE order_id = ?');
        $items->execute([$order['id']]);
        $orderItems = $items->fetchAll();
    ?>
    
    <div style="background:var(--surface);border:1px solid var(--border);border-radius:4px;padding:32px">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;border-bottom:1px solid var(--border);padding-bottom:24px;margin-bottom:24px">
            <div>
                <h2 style="font-family:var(--font-serif);font-size:1.8rem;margin-bottom:8px">Order <span style="color:var(--gold)"><?= h($order['order_number']) ?></span></h2>
                <p style="color:var(--muted)">Placed on <?= date('F j, Y', strtotime($order['created_at'])) ?></p>
            </div>
            <div>
                <span style="display:inline-block;padding:6px 12px;border-radius:4px;font-size:.85rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;background:rgba(212,163,115,.1);color:var(--gold)">
                    <?= h(ucfirst($order['status'])) ?>
                </span>
            </div>
        </div>

        <div style="margin-bottom:40px">
            <h3 style="font-family:var(--font-serif);font-size:1.2rem;text-transform:uppercase;margin-bottom:16px">Status Updates</h3>
            
            <?php 
            $statuses = ['pending', 'processing', 'shipped', 'delivered'];
            $currentIndex = array_search($order['status'], $statuses);
            $isCancelled = in_array($order['status'], ['cancelled', 'refunded']);
            ?>
            
            <?php if ($isCancelled): ?>
                <div class="lume-alert lume-alert--error" style="border:1px solid var(--terracotta);background:transparent;color:var(--terracotta)">This order has been <?= h($order['status']) ?>.</div>
            <?php else: ?>
                <div style="display:flex;justify-content:space-between;position:relative">
                    <div style="position:absolute;top:15px;left:0;right:0;height:2px;background:var(--border);z-index:1"></div>
                    <?php if ($currentIndex !== false): ?>
                        <div style="position:absolute;top:15px;left:0;width:<?= ($currentIndex / 3) * 100 ?>%;height:2px;background:var(--gold);z-index:2;transition:width 1s"></div>
                    <?php endif; ?>
                    
                    <?php foreach ($statuses as $i => $s): 
                        $active = $currentIndex !== false && $currentIndex >= $i;
                        $current = $currentIndex === $i;
                    ?>
                    <div style="position:relative;z-index:3;display:flex;flex-direction:column;align-items:center;width:80px">
                        <div style="width:32px;height:32px;border-radius:50%;background:<?= $active ? 'var(--gold)' : 'var(--surface)' ?>;border:2px solid <?= $active ? 'var(--gold)' : 'var(--border)' ?>;display:flex;align-items:center;justify-content:center;margin-bottom:8px;color:<?= $active ? '#fff' : 'var(--muted)' ?>">
                            <?php if ($active): ?>✓<?php else: ?><?= $i+1 ?><?php endif; ?>
                        </div>
                        <span style="font-size:.8rem;font-weight:<?= $current ? '600' : '400' ?>;color:<?= $active ? 'var(--text)' : 'var(--muted)' ?>;text-align:center"><?= ucfirst($s) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div style="display:grid;grid-template-columns:1fr;gap:32px">
            <div>
                <h3 style="font-family:var(--font-serif);font-size:1.2rem;text-transform:uppercase;margin-bottom:16px">Items Ordered</h3>
                <div style="border:1px solid var(--border);border-radius:4px">
                    <?php foreach ($orderItems as $item): ?>
                    <div style="display:flex;justify-content:space-between;padding:16px;border-bottom:1px solid var(--border)">
                        <div>
                            <strong><?= h($item['name']) ?></strong> × <?= (int)$item['quantity'] ?>
                            <?php if (!empty($item['variant_size']) || !empty($item['variant_color'])): ?>
                            <div style="font-size:.85rem;color:var(--muted);margin-top:4px">
                                <?= h(trim($item['variant_size'] . ' ' . $item['variant_color'])) ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div style="font-weight:500"><?= money((float)$item['subtotal']) ?></div>
                    </div>
                    <?php endforeach; ?>
                    
                    <div style="padding:16px;background:var(--bg)">
                        <div style="display:flex;justify-content:space-between;margin-bottom:8px;font-size:.9rem;color:var(--muted)">
                            <span>Subtotal</span><span><?= money((float)$order['subtotal']) ?></span>
                        </div>
                        <div style="display:flex;justify-content:space-between;margin-bottom:8px;font-size:.9rem;color:var(--muted)">
                            <span>Shipping</span><span><?= money((float)$order['shipping_cost']) ?></span>
                        </div>
                        <?php if ((float)$order['discount'] > 0): ?>
                        <div style="display:flex;justify-content:space-between;margin-bottom:8px;font-size:.9rem;color:var(--terracotta)">
                            <span>Discount</span><span>-<?= money((float)$order['discount']) ?></span>
                        </div>
                        <?php endif; ?>
                        <div style="display:flex;justify-content:space-between;margin-top:16px;padding-top:16px;border-top:1px solid var(--border);font-size:1.1rem;font-weight:600">
                            <span>Total</span><span><?= money((float)$order['total']) ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px">
                <div>
                    <h3 style="font-family:var(--font-serif);font-size:1rem;text-transform:uppercase;margin-bottom:12px">Shipping Details</h3>
                    <p style="font-size:.9rem;line-height:1.6;color:var(--muted)">
                        <strong><?= h($order['shipping_name']) ?></strong><br>
                        <?= h($order['shipping_addr']) ?><br>
                        <?= h($order['shipping_city']) ?><br>
                        <?= h($order['shipping_country']) ?>
                    </p>
                </div>
                <div>
                    <h3 style="font-family:var(--font-serif);font-size:1rem;text-transform:uppercase;margin-bottom:12px">Contact</h3>
                    <p style="font-size:.9rem;line-height:1.6;color:var(--muted)">
                        <?= h($order['phone'] ?? '—') ?><br>
                        <?= h($orderEmail) ?>
                    </p>
                </div>
            </div>
        </div>
        
        <div style="margin-top:40px;text-align:center">
            <a href="<?= SITE_URL ?>/track-order.php" class="lume-btn">Track Another Order</a>
        </div>
    </div>
    <?php endif; ?>

</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
