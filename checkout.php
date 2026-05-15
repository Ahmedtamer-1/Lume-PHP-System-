<?php
/**
 * LUMEEGY — Checkout Page
 * 
 * All redirect logic runs BEFORE header.php to avoid "headers already sent" errors.
 */
require_once __DIR__ . '/includes/functions.php';
lume_session_start();

// ══════════════════════════════════════════════
// PHASE 1: Handle all redirects BEFORE any HTML
// ══════════════════════════════════════════════

// Check for order success first (cart will be empty after order)
$showSuccess = false;
$successId   = null;
if (!empty($_GET['success']) && !empty($_SESSION['order_success'])) {
    $successId   = $_SESSION['order_success'];
    $showSuccess = true;
    unset($_SESSION['order_success']);
}

// If NOT showing success, check cart
if (!$showSuccess) {
    $items = cart_items();
    if (empty($items)) {
        redirect('/cart.php');
    }
    $subtotal = cart_total();
}

$user  = current_user();
$error = '';

// Settings
$freeShippingOver = (float)setting('free_shipping_over', '2000');
$codEnabled       = setting('cod_enabled', '1') === '1';
$codLabel         = setting('cod_label', 'Cash on Delivery');
$codFee           = (float)setting('cod_extra_fee', '0');
$phoneLabel       = setting('phone_display_name', 'Phone Number');

// Fetch active shipping zones
$zones = db()->query('SELECT id, name, cost FROM shipping_zones WHERE is_active = 1 ORDER BY sort_order ASC, name ASC')->fetchAll();

// Handle POST submission (before any HTML output so redirect works)
if (!$showSuccess && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf'] ?? '')) { $error = 'Invalid request.'; }
    else {
        $name    = trim($_POST['shipping_name'] ?? '');
        $email   = trim($_POST['email'] ?? '');
        $phone   = trim($_POST['phone'] ?? '');
        $addr    = trim($_POST['address'] ?? '');
        $zoneId  = (int)($_POST['shipping_zone'] ?? 0);
        $country = trim($_POST['country'] ?? 'Egypt');
        $notes   = trim($_POST['notes'] ?? '');
        $paymentMethod = $_POST['payment_method'] ?? 'online';

        // Validate
        if (!$name || !$email || !$phone || !$addr || !$zoneId) {
            $error = 'Please fill in all required fields, including your phone number and city.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email.';
        } elseif ($paymentMethod === 'cod' && !$codEnabled) {
            $error = 'Cash on delivery is not available.';
        } else {
            // Find zone
            $selectedZone = null;
            foreach ($zones as $z) {
                if ($z['id'] == $zoneId) { $selectedZone = $z; break; }
            }
            if (!$selectedZone) {
                $error = 'Invalid shipping zone selected.';
            } else {
                // Calculate final shipping
                $shipping = ($freeShippingOver > 0 && $subtotal >= $freeShippingOver) ? 0 : (float)$selectedZone['cost'];
                $total = $subtotal + $shipping;
                
                if ($paymentMethod === 'cod') {
                    $total += $codFee;
                }

                $orderItems = [];
                foreach ($items as $i) {
                    $orderItems[] = [
                        'product_id'    => $i['product_id'],
                        'variant_id'    => $i['pv_id'] ?? null,
                        'variant_size'  => $i['variant_size'] ?? null,
                        'variant_color' => $i['variant_color'] ?? null,
                        'name'          => $i['name'],
                        'sku'           => $i['variant_sku'] ?? $i['sku'] ?? null,
                        'price'         => item_effective_price($i),
                        'quantity'      => (int)$i['quantity'],
                    ];
                }

                // Insert the order
                $orderId = create_order([
                    'user_id'        => $user['id'] ?? null,
                    'guest_email'    => $user ? null : $email,
                    'subtotal'       => $subtotal,
                    'shipping_cost'  => $shipping,
                    'total'          => $total,
                    'payment_method' => $paymentMethod,
                    'shipping_name'  => $name,
                    'shipping_addr'  => $addr,
                    'shipping_city'  => $selectedZone['name'],
                    'shipping_country' => $country,
                    'notes'          => $notes,
                    'items'          => $orderItems,
                ]);

                // Update the phone & zone explicitly
                db()->prepare('UPDATE orders SET phone = ?, shipping_zone = ? WHERE id = ?')->execute([$phone, $selectedZone['name'], $orderId]);

                cart_clear();

                // Set session and redirect — NO HTML has been output yet, so this works
                $_SESSION['order_success'] = $orderId;
                redirect('/checkout.php?success=1');
            }
        }
    }
}

// ══════════════════════════════════════════════
// PHASE 2: Render HTML (no more redirects below)
// ══════════════════════════════════════════════

$pageTitle = $showSuccess ? 'Order Confirmed — LUMEEGY' : 'Checkout — LUMEEGY';
require_once __DIR__ . '/includes/header.php';

// ── Success Page ──
if ($showSuccess):
?>
<section class="lume-section container" style="text-align:center;padding:200px 0">
    <div style="max-width:520px;margin:0 auto">
        <div style="width:72px;height:72px;border-radius:50%;border:2px solid var(--gold);display:flex;align-items:center;justify-content:center;margin:0 auto 24px;animation:heroFadeUp .8s .2s cubic-bezier(.16,1,.3,1) forwards;opacity:0">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="var(--gold)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
        </div>
        <p class="lume-section__eyebrow">Thank You</p>
        <h1 class="lume-section__title">Order Confirmed</h1>
        <p style="color:var(--muted);margin:16px auto;max-width:400px;line-height:1.7">Your order <strong style="color:var(--gold)">#<?= h($successId) ?></strong> has been received. We'll send you a confirmation email shortly.</p>
        <a href="<?= SITE_URL ?>/shop.php" class="lume-btn lume-btn--solid" style="margin-top:32px">Continue Shopping</a>
    </div>
</section>
<?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
endif;
?>

<!-- ── Checkout Form ── -->
<section class="lume-page-header">
    <div class="container">
        <h1 class="lume-page-header__title">Checkout</h1>
        <p class="lume-page-header__breadcrumb"><a href="<?= SITE_URL ?>/">Home</a> / <a href="<?= SITE_URL ?>/cart.php">Cart</a> / Checkout</p>
    </div>
</section>

<section class="container">
    <div class="lume-checkout">
        <div>
            <?php if ($error): ?>
            <div class="lume-alert lume-alert--error"><?= h($error) ?></div>
            <?php endif; ?>
            <form method="post" class="lume-form" id="checkout-form">
                <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                
                <h2 style="font-family:var(--font-serif);font-size:1.4rem;text-transform:uppercase;margin-bottom:4px">Contact & Shipping</h2>
                <div class="lume-form__group">
                    <label class="lume-form__label" for="shipping_name">Full Name *</label>
                    <input class="lume-form__input" type="text" id="shipping_name" name="shipping_name" value="<?= h($_POST['shipping_name'] ?? ($user ? $user['first_name'].' '.$user['last_name'] : '')) ?>" required>
                </div>
                
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                    <div class="lume-form__group">
                        <label class="lume-form__label" for="email">Email *</label>
                        <input class="lume-form__input" type="email" id="email" name="email" value="<?= h($_POST['email'] ?? ($user['email'] ?? '')) ?>" required>
                    </div>
                    <div class="lume-form__group">
                        <label class="lume-form__label" for="phone"><?= h($phoneLabel) ?> *</label>
                        <input class="lume-form__input" type="tel" id="phone" name="phone" value="<?= h($_POST['phone'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="lume-form__group">
                    <label class="lume-form__label" for="address">Address *</label>
                    <input class="lume-form__input" type="text" id="address" name="address" value="<?= h($_POST['address'] ?? '') ?>" required>
                </div>
                
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                    <div class="lume-form__group">
                        <label class="lume-form__label" for="shipping_zone">City / Governorate *</label>
                        <select class="lume-form__input" id="shipping_zone" name="shipping_zone" required>
                            <option value="">Select your city...</option>
                            <?php foreach ($zones as $z): ?>
                                <option value="<?= $z['id'] ?>" data-cost="<?= $z['cost'] ?>" <?= (($_POST['shipping_zone'] ?? '') == $z['id']) ? 'selected' : '' ?>>
                                    <?= h($z['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="lume-form__group">
                        <label class="lume-form__label" for="country">Country</label>
                        <input class="lume-form__input" type="text" id="country" name="country" value="Egypt" readonly>
                    </div>
                </div>
                <div class="lume-form__group">
                    <label class="lume-form__label" for="notes">Notes (optional)</label>
                    <textarea class="lume-form__textarea" id="notes" name="notes" rows="3"><?= h($_POST['notes'] ?? '') ?></textarea>
                </div>

                <!-- Payment Method -->
                <h2 style="font-family:var(--font-serif);font-size:1.4rem;text-transform:uppercase;margin:32px 0 16px">Payment Method</h2>
                <div style="display:flex;flex-direction:column;gap:12px;margin-bottom:24px">
                    <label style="display:flex;align-items:center;gap:10px;padding:16px;border:1px solid var(--border);border-radius:4px;cursor:pointer" id="label-online">
                        <input type="radio" name="payment_method" value="online" checked style="accent-color:var(--terracotta);width:18px;height:18px">
                        <span>Online Payment (Credit / Debit)</span>
                    </label>
                    <?php if ($codEnabled): ?>
                    <label style="display:flex;align-items:center;gap:10px;padding:16px;border:1px solid var(--border);border-radius:4px;cursor:pointer" id="label-cod">
                        <input type="radio" name="payment_method" value="cod" style="accent-color:var(--terracotta);width:18px;height:18px">
                        <span><?= h($codLabel) ?> <?= $codFee > 0 ? '(+ '.money($codFee).')' : '' ?></span>
                    </label>
                    <?php endif; ?>
                </div>

                <button type="submit" class="lume-btn lume-btn--full lume-btn--solid" id="checkout-btn">Place Order — Calculating...</button>
            </form>
        </div>
        <div class="lume-checkout__summary">
            <h3 style="font-family:var(--font-serif);font-size:1.1rem;text-transform:uppercase;margin-bottom:20px">Order Summary</h3>
            <?php foreach ($items as $i):
                $p = item_effective_price($i);
                $vLabel = '';
                if (!empty($i['variant_size'])) $vLabel .= $i['variant_size'];
                if (!empty($i['variant_color'])) $vLabel .= ($vLabel ? ' / ' : '') . $i['variant_color'];
            ?>
            <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border);font-size:.85rem">
                <span>
                    <?= h($i['name']) ?> × <?= (int)$i['quantity'] ?>
                    <?php if ($vLabel): ?><br><span style="font-size:.72rem;color:var(--muted)"><?= h($vLabel) ?></span><?php endif; ?>
                </span>
                <span><?= money($p * (int)$i['quantity']) ?></span>
            </div>
            <?php endforeach; ?>
            <div class="lume-cart-summary__row" style="margin-top:16px"><span>Subtotal</span><span><?= money($subtotal) ?></span></div>
            <div class="lume-cart-summary__row" id="row-shipping"><span>Shipping</span><span id="txt-shipping">—</span></div>
            <?php if ($codEnabled): ?>
            <div class="lume-cart-summary__row" id="row-cod" style="display:none;color:var(--muted)"><span>COD Fee</span><span><?= money($codFee) ?></span></div>
            <?php endif; ?>
            <div class="lume-cart-summary__row total"><span>Total</span><span id="txt-total">—</span></div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const subtotal = <?= $subtotal ?>;
    const freeShippingOver = <?= $freeShippingOver ?>;
    const codFee = <?= $codFee ?>;
    const currency = <?= json_encode(currency_symbol()) ?>;
    
    const selectZone = document.getElementById('shipping_zone');
    const radios = document.querySelectorAll('input[name="payment_method"]');
    const txtShipping = document.getElementById('txt-shipping');
    const txtTotal = document.getElementById('txt-total');
    const btnSubmit = document.getElementById('checkout-btn');
    const rowCod = document.getElementById('row-cod');
    const labelOnline = document.getElementById('label-online');
    const labelCod = document.getElementById('label-cod');

    function formatMoney(amount) {
        return currency + parseFloat(amount).toFixed(2);
    }

    function updateTotal() {
        let shipping = 0;
        const opt = selectZone.options[selectZone.selectedIndex];
        
        if (opt && opt.value) {
            shipping = parseFloat(opt.getAttribute('data-cost')) || 0;
            if (freeShippingOver > 0 && subtotal >= freeShippingOver) {
                shipping = 0;
            }
        }

        let total = subtotal + shipping;
        let isCod = false;
        
        // Active border styling for radio buttons
        if(labelOnline) labelOnline.style.borderColor = 'var(--border)';
        if(labelCod) labelCod.style.borderColor = 'var(--border)';

        radios.forEach(r => {
            if (r.checked) {
                if (r.value === 'cod') {
                    isCod = true;
                    total += codFee;
                    if(labelCod) labelCod.style.borderColor = 'var(--terracotta)';
                } else {
                    if(labelOnline) labelOnline.style.borderColor = 'var(--terracotta)';
                }
            }
        });

        // Update UI
        if (!opt || !opt.value) {
            txtShipping.innerText = 'Select a city';
        } else {
            txtShipping.innerText = shipping === 0 ? 'Free' : formatMoney(shipping);
        }

        if (rowCod) {
            rowCod.style.display = (isCod && codFee > 0) ? 'flex' : 'none';
        }

        txtTotal.innerText = formatMoney(total);
        btnSubmit.innerText = 'Place Order — ' + formatMoney(total);
    }

    selectZone.addEventListener('change', updateTotal);
    radios.forEach(r => r.addEventListener('change', updateTotal));
    
    updateTotal(); // initial run
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
