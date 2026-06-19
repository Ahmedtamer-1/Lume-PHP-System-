<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = setting('site_name', SITE_NAME) . ' — ' . setting('site_tagline', SITE_TAGLINE);
$pageDescription = setting('default_meta_description', 'Discover ' . setting('site_name', SITE_NAME) . ' — a luxury fashion brand.');
require_once __DIR__ . '/includes/header.php';

// Load dynamic homepage sections from DB
$sections = [];
try {
    $sections = get_homepage_sections(true);
} catch (Exception $e) {
    // Fallback: table may not exist yet
    $sections = [];
}

$featured = get_featured_products(4);

// If no sections in DB, render defaults
if (empty($sections)) {
    $sections = [
        ['section_type' => 'hero',              'title' => SITE_NAME, 'subtitle' => SITE_TAGLINE, 'button_text' => 'Explore the Collection', 'button_url' => '/shop.php', 'settings' => '{"bg_image":"assets/images/hero-bg.png","show_particles":true}', 'image' => null, 'content' => null],
        ['section_type' => 'featured_products', 'title' => 'Featured Products', 'subtitle' => 'Our most-loved pieces, hand-picked by our editors.', 'button_text' => 'View All Products', 'button_url' => '/shop.php', 'settings' => '{"eyebrow":"Curated for You","product_count":4}', 'image' => null, 'content' => null],
        ['section_type' => 'brand_story',       'title' => 'Born from Light', 'subtitle' => null, 'content' => "LUMEEGY was born from a simple belief — that style is a ritual, not a routine. Rooted in the spirit of Egyptian elegance, each piece is crafted to bring a moment of luxury into your everyday.\n\nFrom our signature silhouettes to carefully selected fabrics, every detail is designed to illuminate — your look, your confidence, your spirit.", 'button_text' => 'Read Our Story', 'button_url' => '/about.php', 'settings' => '{"eyebrow":"Our Story","image":"assets/images/hero-bg.png"}', 'image' => null],
    ];
}

foreach ($sections as $section):
    $sett = json_decode($section['settings'] ?? '{}', true) ?: [];
    
    $padStyle = '';
    if (isset($sett['padding_top']) && $sett['padding_top'] !== '') $padStyle .= 'padding-top:' . (int)$sett['padding_top'] . 'px;';
    if (isset($sett['padding_bottom']) && $sett['padding_bottom'] !== '') $padStyle .= 'padding-bottom:' . (int)$sett['padding_bottom'] . 'px;';

    switch ($section['section_type']):

        // ═══════════════════════════════════════
        // HERO
        // ═══════════════════════════════════════
        case 'hero':
            $bgImage = !empty($section['image']) ? $section['image'] : ($sett['bg_image'] ?? 'assets/images/hero-bg.png');
            $showParticles = isset($sett['show_particles']) ? filter_var($sett['show_particles'], FILTER_VALIDATE_BOOLEAN) : true;
            $textColor = $sett['text_color'] ?? '#ffffff';
            $btnColor = $sett['button_color'] ?? '#ffffff';
?>
<section class="lume-hero" id="hero" style="color: <?= h($textColor) ?>">
    <div class="lume-hero__bg" style="background-image:url('<?= SITE_URL ?>/<?= h($bgImage) ?>')"></div>
    <div class="lume-hero__overlay" style="opacity: <?= h($sett['overlay'] ?? '1') ?>;"></div>
    <?php if ($showParticles): ?>
    <div class="lume-hero__particles">
        <div class="lume-particle"></div><div class="lume-particle"></div><div class="lume-particle"></div>
        <div class="lume-particle"></div><div class="lume-particle"></div><div class="lume-particle"></div>
        <div class="lume-particle"></div><div class="lume-particle"></div>
    </div>
    <?php endif; ?>
    <div class="lume-hero__content">
        <h1 class="lume-hero__title" style="color: <?= h($textColor) ?>"><?= h($section['title'] ?? SITE_NAME) ?></h1>
        <p class="lume-hero__subtitle" style="color: <?= h($textColor) ?>"><?= h($section['subtitle'] ?? SITE_TAGLINE) ?></p>
        <?php if (!empty($section['button_text'])): ?>
        <a href="<?= SITE_URL ?>/<?= ltrim(h($section['button_url'] ?? 'shop.php'), '/') ?>" class="lume-hero__cta" style="--btn-color: <?= h($btnColor) ?>; border-color: var(--btn-color); color: var(--btn-color);"><?= h($section['button_text']) ?></a>
        <?php endif; ?>
    </div>
</section>
<?php break;

        // ═══════════════════════════════════════
        // FEATURED PRODUCTS
        // ═══════════════════════════════════════
        case 'featured_products':
            $eyebrow = $sett['eyebrow'] ?? 'Curated for You';
            $count   = (int)($sett['product_count'] ?? 4);
            $featured = get_featured_products($count);
?>
<section class="lume-section lume-section--center container" id="featured" style="<?= $padStyle ?>">
    <p class="lume-section__eyebrow lume-reveal"><?= h($eyebrow) ?></p>
    <h2 class="lume-section__title lume-reveal"><?= h($section['title'] ?? 'Featured Products') ?></h2>
    <div class="lume-divider lume-reveal" style="margin-bottom:16px"></div>
    <p class="lume-section__subtitle lume-reveal"><?= h($section['subtitle'] ?? '') ?></p>
    <div class="lume-products">
        <?php foreach ($featured as $p): ?>
        <div class="lume-product-card lume-reveal">
            <a href="<?= SITE_URL ?>/product.php?slug=<?= h($p['slug']) ?>" class="lume-product-card__img-wrap">
                <img src="<?= product_image($p) ?>" alt="<?= h($p['name']) ?>" class="lume-product-card__img" loading="lazy">
                <?php if (!empty($p['sale_price'])): ?>
                <span class="lume-product-card__badge">Sale</span>
                <?php endif; ?>
            </a>
            <div class="lume-product-card__body">
                <p class="lume-product-card__cat"><?= h($p['category_name'] ?? '') ?></p>
                <h3 class="lume-product-card__name"><a href="<?= SITE_URL ?>/product.php?slug=<?= h($p['slug']) ?>"><?= h($p['name']) ?></a></h3>
                <div class="lume-product-card__price"><?= product_price($p) ?></div>
                <div class="lume-product-card__actions">
                    <button class="btn-add-cart" onclick="addToCart(<?= (int)$p['id'] ?>)">Add to Bag</button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php if (!empty($section['button_text'])): ?>
    <div style="text-align:center;margin-top:48px" class="lume-reveal">
        <a href="<?= SITE_URL ?>/<?= ltrim(h($section['button_url'] ?? 'shop.php'), '/') ?>" class="lume-btn"><?= h($section['button_text']) ?></a>
    </div>
    <?php endif; ?>
</section>
<?php break;

        // ═══════════════════════════════════════
        // BRAND STORY
        // ═══════════════════════════════════════
        case 'brand_story':
            $eyebrow  = $sett['eyebrow'] ?? 'Our Story';
            $storyImg = !empty($section['image']) ? $section['image'] : ($sett['image'] ?? 'assets/images/hero-bg.png');
?>
<section class="lume-section container" id="story" style="<?= $padStyle ?>">
    <div class="lume-about-intro">
        <div class="lume-about-intro__img lume-reveal-left">
            <img src="<?= SITE_URL ?>/<?= h($storyImg) ?>" alt="<?= h($section['title'] ?? 'Brand story') ?>" loading="lazy">
        </div>
        <div class="lume-about-intro__text lume-reveal-right">
            <p class="lume-section__eyebrow"><?= h($eyebrow) ?></p>
            <h2><?= h($section['title'] ?? 'Our Story') ?></h2>
            <?php foreach (explode("\n\n", $section['content'] ?? '') as $para): ?>
            <p><?= h(trim($para)) ?></p>
            <?php endforeach; ?>
            <?php if (!empty($section['button_text'])): ?>
            <a href="<?= SITE_URL ?>/<?= ltrim(h($section['button_url'] ?? 'about.php'), '/') ?>" class="lume-btn" style="margin-top:24px"><?= h($section['button_text']) ?></a>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php break;

        // ═══════════════════════════════════════
        // IMAGE BANNER
        // ═══════════════════════════════════════
        case 'image_banner':
            $bannerImg = !empty($section['image']) ? $section['image'] : ($sett['image'] ?? 'assets/images/hero-bg.png');
?>
<section class="lume-section" style="padding:0; <?= $padStyle ?>">
    <div style="position:relative;aspect-ratio:21/9;overflow:hidden;background:#111">
        <img src="<?= SITE_URL ?>/<?= h($bannerImg) ?>" alt="<?= h($section['title'] ?? '') ?>" loading="lazy" style="width:100%;height:100%;object-fit:cover;opacity:.7">
        <?php if (!empty($section['title'])): ?>
        <div style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:24px">
            <h2 style="font-family:var(--font-serif);font-size:clamp(2rem,5vw,4rem);text-transform:uppercase;letter-spacing:.04em;margin-bottom:12px" class="lume-reveal"><?= h($section['title']) ?></h2>
            <?php if (!empty($section['subtitle'])): ?>
            <p style="font-size:.85rem;color:var(--gold);letter-spacing:.15em;text-transform:uppercase;margin-bottom:24px" class="lume-reveal"><?= h($section['subtitle']) ?></p>
            <?php endif; ?>
            <?php if (!empty($section['button_text'])): ?>
            <a href="<?= SITE_URL ?>/<?= ltrim(h($section['button_url'] ?? '#'), '/') ?>" class="lume-btn lume-reveal"><?= h($section['button_text']) ?></a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</section>
<?php break;

        // ═══════════════════════════════════════
        // TEXT BLOCK
        // ═══════════════════════════════════════
        case 'text_block':
            $eyebrow = $sett['eyebrow'] ?? '';
?>
<section class="lume-section lume-section--center container" style="<?= $padStyle ?>">
    <?php if ($eyebrow): ?><p class="lume-section__eyebrow lume-reveal"><?= h($eyebrow) ?></p><?php endif; ?>
    <?php if (!empty($section['title'])): ?><h2 class="lume-section__title lume-reveal"><?= h($section['title']) ?></h2><?php endif; ?>
    <div class="lume-divider lume-reveal" style="margin-bottom:16px"></div>
    <?php if (!empty($section['content'])): ?>
    <div class="lume-reveal" style="max-width:700px;margin:0 auto;color:var(--muted);line-height:1.8">
        <?= nl2br(h($section['content'])) ?>
    </div>
    <?php endif; ?>
    <?php if (!empty($section['button_text'])): ?>
    <div class="lume-reveal" style="margin-top:32px">
        <a href="<?= SITE_URL ?>/<?= ltrim(h($section['button_url'] ?? '#'), '/') ?>" class="lume-btn"><?= h($section['button_text']) ?></a>
    </div>
    <?php endif; ?>
</section>
<?php break;

        // ═══════════════════════════════════════
        // CATEGORY GRID
        // ═══════════════════════════════════════
        case 'category_grid':
            $categories = get_categories();
?>
<section class="lume-section lume-section--center container" style="<?= $padStyle ?>">
    <h2 class="lume-section__title lume-reveal"><?= h($section['title'] ?? 'Shop by Category') ?></h2>
    <div class="lume-divider lume-reveal" style="margin-bottom:32px"></div>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:20px;margin-top:32px">
        <?php foreach ($categories as $cat): ?>
        <a href="<?= SITE_URL ?>/shop.php?category=<?= h($cat['slug']) ?>" class="lume-product-card lume-reveal" style="text-align:center;padding:40px 20px">
            <h3 style="font-family:var(--font-serif);font-size:1.2rem;text-transform:uppercase;letter-spacing:.05em"><?= h($cat['name']) ?></h3>
        </a>
        <?php endforeach; ?>
    </div>
</section>
<?php break;

        // ═══════════════════════════════════════
        // SOCIAL CAROUSEL
        // ═══════════════════════════════════════
        case 'social_carousel':
            $imagesText = $sett['images'] ?? '';
            $imagesList = array_filter(array_map('trim', explode("\n", $imagesText)));
            if (empty($imagesList)) break; // Skip if no images
?>
<section class="lume-section" style="padding-bottom: 0; <?= $padStyle ?>">
    <?php if (!empty($section['title'])): ?>
    <div class="container lume-section--center">
        <h2 class="lume-section__title lume-reveal"><?= h($section['title']) ?></h2>
        <?php if (!empty($section['subtitle'])): ?>
        <p class="lume-section__subtitle lume-reveal"><?= h($section['subtitle']) ?></p>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <div class="lume-social-carousel lume-reveal" style="margin-top: <?= empty($section['title']) ? '0' : '48px' ?>;">
        <div class="lume-social-carousel__track">
            <?php foreach ($imagesList as $imgUrl): ?>
            <div class="lume-social-carousel__item">
                <img src="<?= SITE_URL ?>/<?= ltrim(h($imgUrl), '/') ?>" alt="Social image" loading="lazy">
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php break;

        // ═══════════════════════════════════════
        // TESTIMONIALS CAROUSEL
        // ═══════════════════════════════════════
        case 'testimonials':
            $reviewsText = $sett['reviews'] ?? '';
            $reviewsList = array_filter(array_map('trim', explode("\n", $reviewsText)));
            if (empty($reviewsList)) break;
?>
<section class="lume-section container" style="<?= $padStyle ?>">
    <?php if (!empty($section['title'])): ?>
    <div class="lume-section--center">
        <h2 class="lume-section__title lume-reveal"><?= h($section['title']) ?></h2>
        <?php if (!empty($section['subtitle'])): ?>
        <p class="lume-section__subtitle lume-reveal"><?= h($section['subtitle']) ?></p>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <div class="lume-testimonials lume-reveal" style="margin-top: <?= empty($section['title']) ? '0' : '48px' ?>;">
        <div class="lume-testimonials__track" id="testimonial-track-<?= $s['id'] ?? '1' ?>">
            <?php foreach ($reviewsList as $rev): 
                $parts = array_map('trim', explode('|', $rev));
                if (count($parts) < 2) continue;
                $text = $parts[0];
                $author = $parts[1];
                $stars = isset($parts[2]) ? (int)$parts[2] : 5;
                $initial = mb_substr($author, 0, 1, 'UTF-8');
            ?>
            <div class="lume-testimonial-card">
                <div class="lume-testimonial-card__stars">
                    <?= str_repeat('★', $stars) ?><?= str_repeat('☆', 5 - $stars) ?>
                </div>
                <p class="lume-testimonial-card__text"><?= h($text) ?></p>
                <div class="lume-testimonial-card__author-wrap">
                    <div class="lume-testimonial-card__avatar"><?= h(strtoupper($initial)) ?></div>
                    <div class="lume-testimonial-card__author-info">
                        <strong><?= h($author) ?></strong>
                        <span>Verified buyer</span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="lume-testimonials__nav">
            <button class="lume-nav-btn lume-nav-btn--prev" onclick="document.getElementById('testimonial-track-<?= $s['id'] ?? '1' ?>').scrollBy({left: -320, behavior: 'smooth'})">◄</button>
            <button class="lume-nav-btn lume-nav-btn--next" onclick="document.getElementById('testimonial-track-<?= $s['id'] ?? '1' ?>').scrollBy({left: 320, behavior: 'smooth'})">►</button>
        </div>
    </div>
</section>
<?php break;

    endswitch;
endforeach;
?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
