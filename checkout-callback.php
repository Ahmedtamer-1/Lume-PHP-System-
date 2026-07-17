<?php
/**
 * LUMEEGY — Paymob Checkout Callback (Redirect)
 * 
 * Handles the user redirect after they complete or fail the payment in the iframe.
 * On failure: auto-cancels the pending order, restores stock, and restores the cart
 * so the user can retry without losing their items.
 */
require_once __DIR__ . '/includes/functions.php';
lume_session_start();

$receivedHmac = $_GET['hmac'] ?? '';
if (!$receivedHmac) {
    // Missing HMAC — likely a direct hit or bookmark; redirect gracefully
    redirect('/shop.php');
}

// ── Verify HMAC ──────────────────────────────────────────────
$hmacSecret = setting('paymob_hmac');
$hmacFields = [
    'amount_cents',
    'created_at',
    'currency',
    'error_occured',
    'has_parent_transaction',
    'id',
    'integration_id',
    'is_3d_secure',
    'is_auth',
    'is_capture',
    'is_refunded',
    'is_standalone_payment',
    'is_voided',
    'order',
    'owner',
    'pending',
    'source_data_pan',
    'source_data_sub_type',
    'source_data_type',
    'success'
];

$hmacString = '';
foreach ($hmacFields as $field) {
    if (isset($_GET[$field])) {
        $hmacString .= $_GET[$field];
    }
}

$calculatedHmac = hash_hmac('sha512', $hmacString, $hmacSecret);

if (!hash_equals(strtolower($calculatedHmac), strtolower($receivedHmac))) {
    // Invalid signature — show a generic error, do not touch the order
    $error = "Payment verification failed. Security signature mismatch.";
} else {
    // ── Signature valid — check outcome ──────────────────────
    $success = ($_GET['success'] ?? 'false') === 'true';

    if ($success) {
        // Payment succeeded — the server-to-server webhook will mark the order paid.
        // We just redirect to the success screen here.
        $orderId = $_SESSION['paymob_pending_order'] ?? null;
        if ($orderId) {
            unset($_SESSION['paymob_pending_order']);
            $_SESSION['order_success'] = $orderId;
            redirect('/checkout.php?success=1');
        } else {
            // Session lost (e.g. opened in different tab) — redirect to account page
            redirect('/account.php');
        }
    } else {
        // ── Payment declined / cancelled ─────────────────────
        $pendingOrderId = $_SESSION['paymob_pending_order'] ?? null;

        if ($pendingOrderId) {
            // Cancel the order and restore stock
            cancel_order((int)$pendingOrderId);

            // Restore the cart so the user can retry without re-adding items
            cart_restore_from_order((int)$pendingOrderId);

            // Clear the pending session reference
            unset($_SESSION['paymob_pending_order']);
        }

        $error = "Your payment was declined or cancelled. Your items have been restored to your cart — please try again.";
    }
}

// ── Render failure page ───────────────────────────────────────
$pageTitle = 'Payment Failed — ' . setting('site_name', SITE_NAME);
require_once __DIR__ . '/includes/header.php';
?>
<section class="lume-section container" style="text-align:center;padding:160px 0">
    <div style="max-width:520px;margin:0 auto">
        <div style="width:72px;height:72px;border-radius:50%;border:2px solid var(--accent);display:flex;align-items:center;justify-content:center;margin:0 auto 24px;color:var(--accent)">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </div>
        <h1 class="lume-section__title">Payment Failed</h1>
        <p style="color:var(--muted);margin:16px auto;max-width:400px;line-height:1.7"><?= h($error) ?></p>
        <div style="margin-top:32px;display:flex;gap:16px;justify-content:center;flex-wrap:wrap">
            <a href="<?= SITE_URL ?>/cart.php" class="lume-btn lume-btn--solid">Try Again</a>
            <a href="<?= SITE_URL ?>/shop.php" class="lume-btn">Continue Shopping</a>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
