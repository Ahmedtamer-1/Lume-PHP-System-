<?php
require_once __DIR__ . '/includes/functions.php';
$categorySlug = $_GET['category'] ?? null;
$search = $_GET['q'] ?? null;
$siteName = setting('site_name', SITE_NAME);
$pageTitle = 'Shop — ' . $siteName;
$pageDescription = 'Browse our full collection of luxury clothing, accessories, and fashion essentials.';
$pageKeywords = 'shop, ' . $siteName . ', fashion, clothing, accessories';
$canonicalUrl = SITE_URL . '/shop.php' . ($categorySlug ? '?category=' . urlencode($categorySlug) : '');
require_once __DIR__ . '/includes/header.php';

$categories = get_categories();
$products = get_products([
    'category_slug' => $categorySlug,
    'search'        => $search,
    'limit'         => 10000,
]);
$productIds    = array_column($products, 'id');
$productColors = get_product_color_swatches($productIds);

// Track which products have variants
$variantProducts = [];
if (!empty($productIds)) {
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    $stmt = db()->prepare("SELECT id, has_variants FROM products WHERE id IN ($placeholders)");
    $stmt->execute($productIds);
    foreach ($stmt->fetchAll() as $row) {
        if (!empty($row['has_variants'])) {
            $variantProducts[(int)$row['id']] = true;
        }
    }
}

// Build display and hover images per product
$displayImages = [];
$hoverImages   = [];
foreach ($products as $p) {
    $pid          = (int)$p['id'];
    $mainImg      = product_image($p);
    $hasValidMain = !empty($p['image']) && $p['image'] !== 'assets/images/placeholder.jpg';
    $colorGals    = json_decode($p['color_galleries'] ?? 'null', true) ?: [];

    $firstColorImg  = null;
    $secondColorImg = null;
    foreach ($colorGals as $cName => $imgs) {
        if (is_array($imgs) && !empty($imgs)) {
            if (!$firstColorImg)  $firstColorImg  = $imgs[0];
            if (count($imgs) > 1 && !$secondColorImg) $secondColorImg = $imgs[1];
            break;
        }
    }
    if (!$firstColorImg && !empty($productColors[$pid])) {
        foreach ($productColors[$pid] as $cn => $cData) {
            if (!empty($cData['image'])) { $firstColorImg = $cData['image']; break; }
        }
    }

    $displayImages[$pid] = $hasValidMain ? $mainImg : ($firstColorImg ?: $mainImg);

    $gallery = json_decode($p['gallery'] ?? 'null', true) ?: [];
    foreach ($gallery as $gImg) {
        if ($gImg && $gImg !== $displayImages[$pid]) { $hoverImages[$pid] = $gImg; break; }
    }
    if (!isset($hoverImages[$pid])) {
        if ($secondColorImg && $secondColorImg !== $displayImages[$pid]) {
            $hoverImages[$pid] = $secondColorImg;
        } elseif ($firstColorImg && $firstColorImg !== $displayImages[$pid]) {
            $hoverImages[$pid] = $firstColorImg;
        } elseif (!empty($productColors[$pid])) {
            foreach ($productColors[$pid] as $cn => $cData) {
                if (!empty($cData['image']) && $cData['image'] !== $displayImages[$pid]) {
                    $hoverImages[$pid] = $cData['image']; break;
                }
            }
        }
    }
}
?>

<section class="lume-page-header">
    <div class="container">
        <h1 class="lume-page-header__title">Shop</h1>
        <p class="lume-page-header__breadcrumb">
            <a href="<?= SITE_URL ?>/">Home</a> / Shop
            <?php if ($categorySlug): ?> / <?= h(ucfirst(str_replace('-', ' ', $categorySlug))) ?><?php endif; ?>
        </p>
    </div>
</section>

<section class="lume-section container" id="shop-grid">

    <!-- Category filters -->
    <div class="lume-filters">
        <a href="<?= SITE_URL ?>/shop.php" class="lume-filter-btn <?= !$categorySlug ? 'active' : '' ?>">All</a>
        <?php foreach ($categories as $cat): ?>
        <a href="<?= SITE_URL ?>/shop.php?category=<?= h($cat['slug']) ?>"
           class="lume-filter-btn <?= $categorySlug === $cat['slug'] ? 'active' : '' ?>">
            <?= h($cat['name']) ?>
        </a>
        <?php endforeach; ?>
    </div>



    <?php if (empty($products)): ?>
    <p style="text-align:center;color:var(--muted);padding:60px 0;font-size:.9rem">No products found.</p>
    <?php else: ?>
    <div class="lume-products" id="product-grid">
        <?php foreach ($products as $p): ?>
        <?php $pid = (int)$p['id']; ?>
        <div class="lume-product-card lume-reveal">
            <a href="<?= SITE_URL ?>/product.php?slug=<?= h($p['slug']) ?>" class="lume-product-card__img-wrap">
                <img src="<?= asset_url(h($displayImages[$pid] ?? product_image($p))) ?>"
                     alt="<?= h($p['name']) ?>"
                     class="lume-product-card__img"
                     loading="lazy"
                     <?php if (!empty($hoverImages[$pid])): ?>data-hover-src="<?= asset_url(h($hoverImages[$pid])) ?>"<?php endif; ?>>
                <?php if (!empty($p['sale_price'])): ?>
                <span class="lume-product-card__badge">Sale</span>
                <?php endif; ?>
                <?php if (!empty($productColors[$pid])): ?>
                <div class="lume-product-card__swatches">
                    <?php foreach ($productColors[$pid] as $cn => $cData): ?>
                    <span class="lume-product-card__swatch-dot"
                          style="background:<?= h($cData['hex']) ?>"
                          title="<?= h($cn) ?>"
                          <?php if (!empty($cData['image'])): ?>data-image="<?= asset_url(h($cData['image'])) ?>"<?php endif; ?>></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </a>
            <div class="lume-product-card__body">
                <p class="lume-product-card__cat"><?= h($p['category_name'] ?? '') ?></p>
                <h3 class="lume-product-card__name">
                    <a href="<?= SITE_URL ?>/product.php?slug=<?= h($p['slug']) ?>"><?= h($p['name']) ?></a>
                </h3>
                <div class="lume-product-card__price"><?= product_price($p) ?></div>
                <div class="lume-product-card__actions">
                    <?php if (!empty($variantProducts[$pid])): ?>
                    <a href="<?= SITE_URL ?>/product.php?slug=<?= h($p['slug']) ?>"
                       class="btn-add-cart"
                       style="display:block;text-align:center;text-decoration:none">Select Options</a>
                    <?php else: ?>
                    <button class="btn-add-cart" onclick="addToCart(<?= (int)$p['id'] ?>)">Add to Bag</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</section>



<?php require_once __DIR__ . '/includes/footer.php'; ?>
