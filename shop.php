<?php
$categorySlug = $_GET['category'] ?? null;
$search = $_GET['q'] ?? null;
$pageTitle = 'Shop — LUMEEGY';
$pageDescription = 'Browse our full collection of luxury clothing, accessories, and fashion essentials.';
require_once __DIR__ . '/includes/header.php';

$categories = get_categories();
$products = get_products([
    'category_slug' => $categorySlug,
    'search' => $search,
    'limit' => 24,
]);
$productIds = array_column($products, 'id');
$productColors = get_product_color_swatches($productIds);

// Build second images for hover effect (from gallery or variant images)
$hoverImages = [];
foreach ($products as $p) {
    $pid = (int)$p['id'];
    // Try gallery first
    $gallery = json_decode($p['gallery'] ?? 'null', true) ?: [];
    $mainImg = product_image($p);
    foreach ($gallery as $gImg) {
        if ($gImg && $gImg !== $mainImg) {
            $hoverImages[$pid] = $gImg;
            break;
        }
    }
    // Fall back to first variant image if no gallery hover
    if (!isset($hoverImages[$pid]) && !empty($productColors[$pid])) {
        foreach ($productColors[$pid] as $cn => $cData) {
            if (!empty($cData['image']) && $cData['image'] !== $mainImg) {
                $hoverImages[$pid] = $cData['image'];
                break;
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
            <?php if ($categorySlug): ?> / <?= h(ucfirst(str_replace('-',' ',$categorySlug))) ?><?php endif; ?>
        </p>
    </div>
</section>

<section class="lume-section container" id="shop-grid">
    <div class="lume-filters lume-reveal">
        <a href="<?= SITE_URL ?>/shop.php" class="lume-filter-btn <?= !$categorySlug ? 'active' : '' ?>">All</a>
        <?php foreach ($categories as $cat): ?>
        <a href="<?= SITE_URL ?>/shop.php?category=<?= h($cat['slug']) ?>" class="lume-filter-btn <?= $categorySlug === $cat['slug'] ? 'active' : '' ?>"><?= h($cat['name']) ?></a>
        <?php endforeach; ?>
    </div>

    <?php if (empty($products)): ?>
    <p style="text-align:center;color:var(--muted);padding:60px 0;font-size:.9rem">No products found.</p>
    <?php else: ?>
    <div class="lume-products">
        <?php foreach ($products as $p): ?>
        <?php $pid = (int)$p['id']; ?>
        <div class="lume-product-card lume-reveal">
            <a href="<?= SITE_URL ?>/product.php?slug=<?= h($p['slug']) ?>" class="lume-product-card__img-wrap">
                <img src="<?= SITE_URL ?>/<?= product_image($p) ?>" 
                     alt="<?= h($p['name']) ?>" 
                     class="lume-product-card__img" 
                     loading="lazy"
                     <?php if (!empty($hoverImages[$pid])): ?>
                     data-hover-src="<?= SITE_URL ?>/<?= h($hoverImages[$pid]) ?>"
                     <?php endif; ?>>
                <?php if (!empty($p['sale_price'])): ?>
                <span class="lume-product-card__badge">Sale</span>
                <?php endif; ?>
            </a>
            <div class="lume-product-card__body">
                <p class="lume-product-card__cat"><?= h($p['category_name'] ?? '') ?></p>
                <h3 class="lume-product-card__name"><a href="<?= SITE_URL ?>/product.php?slug=<?= h($p['slug']) ?>"><?= h($p['name']) ?></a></h3>
                <div class="lume-product-card__price"><?= product_price($p) ?></div>
                
                <?php if (!empty($productColors[$pid])): ?>
                <div class="lume-product-card__swatches">
                    <?php foreach ($productColors[$pid] as $cn => $cData): ?>
                    <span class="lume-product-card__swatch-dot" 
                          style="background:<?= h($cData['hex']) ?>" 
                          title="<?= h($cn) ?>"
                          <?php if (!empty($cData['image'])): ?>
                          data-image="<?= SITE_URL ?>/<?= h($cData['image']) ?>"
                          <?php endif; ?>></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <div class="lume-product-card__actions">
                    <button class="btn-add-cart" onclick="addToCart(<?= (int)$p['id'] ?>)">Add to Bag</button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
