<?php
/**
 * LUMEEGY — Shared Footer
 * The marquee section can be toggled via settings.
 */

// Check settings for showing/hiding footer sections
$showMarquee    = setting('show_marquee', '1') === '1';
$marqueeText    = setting('marquee_text', '');
?>
</main><!-- /#main-content -->

<?php if ($showMarquee): ?>
<!-- ═══════════════════════════════════════════
     MARQUEE
═══════════════════════════════════════════ -->
<div class="lume-marquee" aria-hidden="true">
    <div class="lume-marquee__track">
        <?php
        if ($marqueeText) {
            // Custom marquee items from settings (pipe-separated)
            $items = array_map('trim', explode('|', $marqueeText));
        } else {
            $freeShipOver = setting('free_shipping_over', '2000');
            $items = [setting('site_tagline', SITE_TAGLINE), '✦', 'Luxury Fashion', '✦', 'Crafted in Egypt', '✦'];
            if ($freeShipOver > 0) {
                $items[] = 'Free Shipping Over ' . currency_symbol() . number_format((float)$freeShipOver, 0);
                $items[] = '✦';
            }
            $items = array_merge($items, ['New Arrivals', '✦']);
        }
        // Double for infinite scroll effect
        foreach (array_merge($items, $items) as $item):
        ?>
            <span class="lume-marquee__text"><?= h($item) ?></span>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>



<!-- ═══════════════════════════════════════════
     FOOTER
═══════════════════════════════════════════ -->
<footer class="lume-footer" aria-label="Site footer">
    <div class="lume-footer__grid">
        <div class="lume-footer__col">
            <?php $siteLogo = setting('site_logo'); if ($siteLogo): ?>
                <img src="<?= SITE_URL . '/' . h($siteLogo) ?>" alt="<?= h(setting('site_name', SITE_NAME)) ?>" style="max-height:40px;width:auto;margin-bottom:12px;display:block">
            <?php else: ?>
                <p class="lume-logo-text lume-footer__brand-logo"><?= h(setting('site_name', SITE_NAME)) ?></p>
            <?php endif; ?>
            <p class="lume-footer__brand-text">
                <?= nl2br(h(setting('footer_brand_text', 'Fine jewelry for the woman who is her own occasion. Born in Egypt, made for the everyday.'))) ?>
            </p>
            <div class="lume-footer__social">
                <?php $ig = setting('instagram_url'); if ($ig): ?>
                <a href="<?= h($ig) ?>" class="lume-footer__social-link" target="_blank" rel="noopener" aria-label="Instagram">
                    <svg viewBox="0 0 24 24" width="18" height="18"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
                </a>
                <?php endif; ?>
                <?php $tt = setting('tiktok_url'); if ($tt): ?>
                <a href="<?= h($tt) ?>" class="lume-footer__social-link" target="_blank" rel="noopener" aria-label="TikTok">
                    <svg viewBox="0 0 24 24" width="18" height="18"><path d="M9 12a4 4 0 1 0 4 4V4a5 5 0 0 0 5 5"/></svg>
                </a>
                <?php endif; ?>
                <?php $fb = setting('facebook_url'); if ($fb): ?>
                <a href="<?= h($fb) ?>" class="lume-footer__social-link" target="_blank" rel="noopener" aria-label="Facebook">
                    <svg viewBox="0 0 24 24" width="18" height="18"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="lume-footer__col">
            <h3 class="lume-footer__col-title">Shop</h3>
            <?php foreach (get_categories() as $cat): ?>
            <a href="<?= SITE_URL ?>/shop.php?category=<?= h($cat['slug']) ?>" class="lume-footer__link"><?= h($cat['name']) ?></a>
            <?php endforeach; ?>
            <a href="<?= SITE_URL ?>/shop.php" class="lume-footer__link">All Products</a>
        </div>

        <div class="lume-footer__col">
            <h3 class="lume-footer__col-title">Help</h3>
            <a href="<?= SITE_URL ?>/contact.php" class="lume-footer__link">Contact Us</a>
            <a href="<?= SITE_URL ?>/shipping-returns.php" class="lume-footer__link">Shipping & Returns</a>
        </div>

        <div class="lume-footer__col">
            <h3 class="lume-footer__col-title">Company</h3>
            <a href="<?= SITE_URL ?>/about.php" class="lume-footer__link">About <?= h(setting('site_name', SITE_NAME)) ?></a>
            <a href="<?= SITE_URL ?>/privacy.php" class="lume-footer__link">Privacy Policy</a>
            <a href="<?= SITE_URL ?>/terms.php" class="lume-footer__link">Terms of Service</a>
        </div>
    </div>

    <div class="lume-footer__bottom">
        <p>&copy; <?= date('Y') ?> <?= h(setting('site_name', SITE_NAME)) ?>. All rights reserved. &nbsp;|&nbsp;
           <a href="<?= SITE_URL ?>/privacy.php" class="lume-footer__legal-link">Privacy Policy</a> &nbsp;|&nbsp;
           <a href="<?= SITE_URL ?>/terms.php" class="lume-footer__legal-link">Terms of Service</a>
        </p>
    </div>
</footer>

<!-- Scripts -->
<script src="<?= SITE_URL ?>/assets/js/main.js?v=<?= filemtime(ROOT_PATH.'/assets/js/main.js') ?>"></script>
<style>
/* Elevate content above the background layer */
section, main, .container, footer {
    position: relative;
    z-index: 1;
}

.lume-hieroglyphs-bg {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    z-index: 0; /* Behind relative sections, but above html background */
    pointer-events: none;
    overflow: hidden;
}

.lume-h-char {
    position: absolute;
    color: var(--gold);
    opacity: 0.05;
    font-family: serif;
    will-change: opacity, color;
    animation: hieroglyph-wave 6s infinite linear;
}

@keyframes hieroglyph-wave {
    0% { opacity: 0.05; color: var(--gold); }
    10% { opacity: 0.4; color: #fff; }
    20% { opacity: 0.05; color: var(--gold); }
    100% { opacity: 0.05; }
}
</style>
<script>
window.addEventListener('load', function() {
    const bgContainer = document.createElement('div');
    bgContainer.className = 'lume-hieroglyphs-bg';
    // Prepend to be the first element, rendering behind others
    document.body.prepend(bgContainer);

    const hieroglyphs = [
        '𓀀', '𓀁', '𓀂', '𓀃', '𓀄', '𓀅', '𓀆', '𓀇', '𓀈', '𓀉', 
        '𓃀', '𓃁', '𓃂', '𓃃', '𓃄', '𓃅', '𓃆', '𓃇', '𓃈', '𓃉', 
        '𓄀', '𓄁', '𓄂', '𓄃', '𓄄', '𓄅', '𓄆', '𓄇', '𓄈', '𓄉',
        '𓅀', '𓅁', '𓅂', '𓅃', '𓅄', '𓅅', '𓅆', '𓅇', '𓅈', '𓅉',
        '𓆀', '𓆁', '𓆂', '𓆃', '𓆄', '𓆅', '𓆆', '𓆇', '𓆈', '𓆉'
    ];

    const docHeight = Math.max(document.body.scrollHeight, document.documentElement.scrollHeight);
    bgContainer.style.height = docHeight + 'px';

    const colWidth = 90;
    const rowHeight = 90;
    const numCols = Math.ceil(window.innerWidth / colWidth);
    const numRows = Math.ceil(docHeight / rowHeight);
    
    for (let i = 0; i < numCols; i++) {
        for (let j = 0; j < numRows; j++) {
            // Very occasional gaps to look like worn ancient text, but mostly structured
            if (Math.random() > 0.85) continue;

            const span = document.createElement('span');
            span.className = 'lume-h-char';
            span.textContent = hieroglyphs[Math.floor(Math.random() * hieroglyphs.length)];
            
            // Perfect grid alignment
            const x = i * colWidth;
            const y = j * rowHeight;
            
            span.style.left = `${x}px`;
            span.style.top = `${y}px`;
            
            // Adjusted size
            span.style.fontSize = `2.5rem`;

            const waveSpeed = 250; 
            const baseDelay = x / waveSpeed;
            // Slight delay variation so the wave isn't a rigid perfectly straight line
            const randomDelay = baseDelay + (Math.random() * 0.2);
            
            span.style.animationDelay = `-${randomDelay % 6}s`;
            
            bgContainer.appendChild(span);
        }
    }
});
</script>
</body>
</html>
