<?php
$pageTitle = 'Shipping & Returns — ' . (defined('SITE_NAME') ? SITE_NAME : 'LUMEEGY');
$pageDescription = 'Learn about our shipping options, delivery times, and return policy.';
require_once __DIR__ . '/includes/header.php';
$brandName = defined('SITE_NAME') ? SITE_NAME : 'LUMEEGY';
$contactEmail = setting('contact_email', 'hello@lumeegy.com');
$freeShipOver = setting('free_shipping_over', '2000');
?>

<section class="lume-section container" style="padding-top:60px;padding-bottom:80px">
    <div class="lume-legal">
        <p class="lume-section__eyebrow lume-reveal">Support</p>
        <h1 class="lume-section__title lume-reveal" style="font-size:clamp(1.8rem,4vw,2.8rem)">Shipping & Returns</h1>
        <div class="lume-divider lume-reveal" style="margin-bottom:12px"></div>

        <div class="lume-legal__body lume-reveal">
            <?php $content = setting('page_shipping_content'); if ($content): echo $content; else: ?>

            <h2>Shipping</h2>

            <h3>Domestic Shipping (Egypt)</h3>
            <p>We offer nationwide shipping within Egypt. Orders are processed within 1–2 business days and typically arrive within 3–7 business days depending on your location.</p>
            <?php if ($freeShipOver > 0): ?>
            <p><strong>Free shipping</strong> on orders over <?= currency_symbol() . number_format((float)$freeShipOver, 0) ?>.</p>
            <?php endif; ?>

            <h3>International Shipping</h3>
            <p>International shipping is available to select destinations. Delivery times vary by country and typically range from 7–21 business days. International orders may be subject to customs duties and taxes, which are the responsibility of the customer.</p>

            <h3>Order Tracking</h3>
            <p>Once your order ships, you will receive a confirmation email with tracking information. You can also check your order status by logging into your account.</p>

            <hr style="border:none;border-top:1px solid var(--border);margin:40px 0">

            <h2>Returns & Exchanges</h2>

            <h3>Return Policy</h3>
            <p>We want you to love your purchase. If you're not completely satisfied, you may return eligible items within <strong>14 days</strong> of delivery.</p>

            <h3>Conditions for Return</h3>
            <ul>
                <li>Items must be unworn, unwashed, and unaltered</li>
                <li>Items must be in original packaging with all tags attached</li>
                <li>Proof of purchase is required</li>
            </ul>

            <h3>Non-Returnable Items</h3>
            <ul>
                <li>Items marked as "Final Sale"</li>
                <li>Intimate apparel and accessories (for hygiene reasons)</li>
                <li>Gift cards</li>
                <li>Items that have been worn, washed, or altered</li>
            </ul>

            <h3>How to Return</h3>
            <ol>
                <li>Contact us at <a href="mailto:<?= h($contactEmail) ?>" style="color:var(--gold)"><?= h($contactEmail) ?></a> with your order number</li>
                <li>We'll provide you with return instructions and a shipping address</li>
                <li>Ship the item back in its original packaging</li>
                <li>Once received and inspected, your refund will be processed</li>
            </ol>

            <h3>Refunds</h3>
            <p>Refunds will be processed to the original payment method within 7–14 business days of receiving the returned item. Original shipping costs are non-refundable. Return shipping costs are the customer's responsibility unless the item was defective or we made an error.</p>

            <h3>Exchanges</h3>
            <p>We currently process exchanges as a return and new order. Contact us to arrange your exchange.</p>

            <hr style="border:none;border-top:1px solid var(--border);margin:40px 0">

            <h2>Questions?</h2>
            <p>If you have any questions about shipping or returns, please <a href="<?= SITE_URL ?>/contact.php" style="color:var(--gold)">contact us</a> and we'll be happy to help.</p>

            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
