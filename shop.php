<?php
// ─── Input sanitisation ───
require_once __DIR__ . '/includes/functions.php';

// Sanitise all query params at the top of the file before any DB calls
$categorySlug = trim(preg_replace('/[^a-z0-9\-]/', '', strtolower($_GET['category'] ?? ''))) ?: null;
$search       = mb_substr(trim(strip_tags($_GET['q'] ?? '')), 0, 120) ?: null;
$sort         = in_array($_GET['sort'] ?? '', ['newest','price_asc','price_desc','name_asc']) ? $_GET['sort'] : 'newest';
$page         = max(1, (int)($_GET['page'] ?? 1));
$perPage      = 24; // products per page
$offset       = ($page - 1) * $perPage;

$siteName = setting('site_name', SITE_NAME);

// ─── Data fetching ───
$where = ['p.is_active = 1'];
$params = [];

if ($categorySlug) {
    $where[] = 'c.slug = ?';
    $params[] = $categorySlug;
}
if ($search) {
    $where[] = '(p.name LIKE ? OR p.description LIKE ?)';
    $term = '%' . $search . '%';
    $params[] = $term;
    $params[] = $term;
}

$whereClause = implode(' AND ', $where);

// Count total products for pagination
$countSql = "SELECT COUNT(p.id) FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE $whereClause";
$stmt = db()->prepare($countSql);
$stmt->execute($params);
$totalProducts = (int) $stmt->fetchColumn();

$totalPages = max(1, ceil($totalProducts / $perPage));

// Guard pagination
if ($page > $totalPages && $totalProducts > 0) {
    $q = $_GET;
    $q['page'] = 1;
    redirect('/shop.php?' . http_build_query($q));
}

// Order map
$orderMap = [
    'newest'     => 'p.created_at DESC',
    'price_asc'  => 'COALESCE(p.sale_price, p.price) ASC',
    'price_desc' => 'COALESCE(p.sale_price, p.price) DESC',
    'name_asc'   => 'p.name ASC',
];
$orderBy = $orderMap[$sort];

// Fetch products
$sql = "SELECT p.*, c.name AS category_name, c.slug AS category_slug 
        FROM products p 
        LEFT JOIN categories c ON c.id = p.category_id 
        WHERE $whereClause 
        ORDER BY $orderBy 
        LIMIT $perPage OFFSET $offset";

$stmt = db()->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$productIds = array_column($products, 'id');

// Fetch variant indicators
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

// Fetch colors
$productColors = get_product_color_swatches($productIds);

// Fetch categories for filter bar with count
$catStmt = db()->query("
    SELECT MIN(c.id) as id, c.name, c.slug, COUNT(p.id) as p_count 
    FROM categories c 
    LEFT JOIN products p ON p.category_id = c.id AND p.is_active = 1 
    GROUP BY c.name, c.slug 
    ORDER BY MIN(c.sort_order) ASC, c.name ASC
");
$categories = $catStmt->fetchAll();

// ─── Image resolution helper ───
$displayImages = [];
$hoverImages = [];

foreach ($products as $p) {
    $pid = (int)$p['id'];
    $mainImg = product_image($p);
    $hasValidMain = !empty($p['image']) && $p['image'] !== 'assets/images/placeholder.jpg';
    
    $colorGals = json_decode($p['color_galleries'] ?? 'null', true) ?: [];
    $firstColorImg = null;
    $secondColorImg = null;
    
    foreach ($colorGals as $cName => $imgs) {
        if (is_array($imgs) && !empty($imgs)) {
            if (!$firstColorImg) $firstColorImg = $imgs[0];
            if (count($imgs) > 1 && !$secondColorImg) $secondColorImg = $imgs[1];
            break;
        }
    }
    
    if (!$firstColorImg && !empty($productColors[$pid])) {
        foreach ($productColors[$pid] as $cn => $cData) {
            if (!empty($cData['image'])) {
                $firstColorImg = $cData['image'];
                break;
            }
        }
    }
    
    if ($hasValidMain) {
        $displayImages[$pid] = $mainImg;
    } elseif ($firstColorImg) {
        $displayImages[$pid] = $firstColorImg;
    } else {
        $displayImages[$pid] = $mainImg;
    }
    
    $gallery = json_decode($p['gallery'] ?? 'null', true) ?: [];
    foreach ($gallery as $gImg) {
        if ($gImg && $gImg !== $displayImages[$pid]) {
            $hoverImages[$pid] = $gImg;
            break;
        }
    }
    
    if (!isset($hoverImages[$pid])) {
        if ($secondColorImg && $secondColorImg !== $displayImages[$pid]) {
            $hoverImages[$pid] = $secondColorImg;
        } elseif ($firstColorImg && $firstColorImg !== $displayImages[$pid]) {
            $hoverImages[$pid] = $firstColorImg;
        } elseif (!empty($productColors[$pid])) {
            foreach ($productColors[$pid] as $cn => $cData) {
                if (!empty($cData['image']) && $cData['image'] !== $displayImages[$pid]) {
                    $hoverImages[$pid] = $cData['image'];
                    break;
                }
            }
        }
    }
}

// ─── URL builder helper ───
$shopUrl = function(int $p) use ($categorySlug, $search, $sort): string {
    $params = [];
    if ($categorySlug) $params['category'] = $categorySlug;
    if ($search) $params['q'] = $search;
    if ($sort !== 'newest') $params['sort'] = $sort;
    if ($p > 1) $params['page'] = $p;
    
    $qs = http_build_query($params);
    return SITE_URL . '/shop.php' . ($qs ? '?' . $qs : '');
};

// ─── SEO meta ───
$pageTitle       = ($search ? 'Search: ' . h($search) . ' — ' : '') . ($categorySlug ? ucfirst(str_replace('-',' ',$categorySlug)) . ' — ' : '') . 'Shop — ' . $siteName;
$pageDescription = 'Browse our full collection of luxury clothing, accessories, and fashion essentials.';
$pageKeywords    = 'shop, ' . $siteName . ', fashion, clothing, accessories';
$canonicalUrl    = SITE_URL . '/shop.php' . ($categorySlug ? '?category=' . urlencode($categorySlug) : '');

require_once __DIR__ . '/includes/header.php';

// Inject rel prev/next directly
if ($page > 1) {
    echo '<link rel="prev" href="' . h($shopUrl($page - 1)) . '">' . "\n";
}
if ($page < $totalPages) {
    echo '<link rel="next" href="' . h($shopUrl($page + 1)) . '">' . "\n";
}
?>

<!-- Page header / breadcrumb -->
<section class="lume-page-header">
    <div class="container">
        <h1 class="lume-page-header__title">Shop</h1>
        <p class="lume-page-header__breadcrumb">
            <a href="<?= SITE_URL ?>/">Home</a> / Shop
            <?php if ($categorySlug): ?> / <?= h(ucfirst(str_replace('-',' ',$categorySlug))) ?><?php endif; ?>
        </p>
    </div>
</section>

<!-- Main shop section -->
<section class="lume-section container" id="shop-grid">
    <!-- Category filters -->
    <div class="lume-filters lume-reveal">
        <a href="<?= SITE_URL ?>/shop.php" class="lume-filter-btn <?= !$categorySlug ? 'active' : '' ?>">All</a>
        <?php foreach ($categories as $cat): ?>
        <a href="<?= SITE_URL ?>/shop.php?category=<?= h($cat['slug'] ?? '') ?>" class="lume-filter-btn <?= $categorySlug === $cat['slug'] ? 'active' : '' ?>">
            <?= h($cat['name'] ?? '') ?> <span style="font-size: 0.85em; opacity: 0.7;">(<?= (int)$cat['p_count'] ?>)</span>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Toolbar: result count + sort -->
    <div class="lume-shop-toolbar" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <!-- Left: result count + active search query label -->
        <p class="lume-shop-count" style="margin: 0; color: var(--muted);">
            Showing <strong><?= $totalProducts > 0 ? $offset + 1 : 0 ?>–<?= min($offset + $perPage, $totalProducts) ?></strong> of <strong><?= $totalProducts ?></strong> products<?= $search ? ' for "<em>'.h($search).'</em>"' : '' ?>
        </p>

        <!-- Right: sort dropdown -->
        <form method="get" id="sort-form" class="lume-shop-sort" style="margin: 0;">
            <!-- preserve category and q params as hidden inputs handled by JS -->
            <select name="sort" onchange="this.form.submit()" aria-label="Sort products" style="padding: 0.5rem; border: 1px solid var(--border); background: var(--bg-card); color: inherit; border-radius: 4px; font-family: var(--font-sans);">
                <option value="newest"     <?= $sort==='newest'     ? 'selected':'' ?>>Newest</option>
                <option value="price_asc"  <?= $sort==='price_asc'  ? 'selected':'' ?>>Price: Low to High</option>
                <option value="price_desc" <?= $sort==='price_desc' ? 'selected':'' ?>>Price: High to Low</option>
                <option value="name_asc"   <?= $sort==='name_asc'   ? 'selected':'' ?>>Name: A–Z</option>
            </select>
        </form>
    </div>

    <!-- Product grid OR empty state -->
    <?php if (empty($products)): ?>
        <div class="lume-empty-state" style="text-align: center; padding: 4rem 1rem; background: var(--bg-card); border-radius: 8px; border: 1px solid var(--border);">
            <svg style="width: 64px; height: 64px; margin-bottom: 1rem; color: var(--muted);" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <h2 style="margin-bottom: 0.5rem; font-family: var(--font-serif);">No products found</h2>
            <p style="color: var(--muted); margin-bottom: 1.5rem;"><?= $search ? 'Try a different search term.' : 'Check back soon for new arrivals.' ?></p>
            <?php if ($search || $categorySlug): ?>
            <a href="<?= SITE_URL ?>/shop.php" class="lume-btn" style="display: inline-block; padding: 0.75rem 1.5rem; background: var(--gold); color: var(--bg); text-decoration: none; border-radius: 4px; font-weight: 600;">Browse All Products</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="lume-products">
            <?php foreach ($products as $p): ?>
            <?php $pid = (int)$p['id']; ?>
            <div class="lume-product-card lume-reveal" data-pid="<?= $pid ?>" style="display: flex; flex-direction: column;">
                <a href="<?= SITE_URL ?>/product.php?slug=<?= h($p['slug'] ?? '') ?>" class="lume-product-card__img-wrap" style="overflow: hidden;">
                    <img src="<?= asset_url(h($displayImages[$pid] ?? product_image($p))) ?>" 
                         alt="<?= h($p['name'] ?? '') ?>" 
                         class="lume-product-card__img" 
                         loading="lazy"
                         <?php if (!empty($hoverImages[$pid])): ?>
                         data-hover-src="<?= asset_url(h($hoverImages[$pid])) ?>"
                         <?php endif; ?>>
                    <?php if (!empty($p['sale_price'])): ?>
                    <span class="lume-product-card__badge">Sale</span>
                    <?php endif; ?>
                    <?php if (!empty($productColors[$pid])): ?>
                    <div class="lume-product-card__swatches">
                        <?php foreach ($productColors[$pid] as $cn => $cData): ?>
                        <span class="lume-product-card__swatch-dot" 
                              style="background:<?= h($cData['hex'] ?? '') ?>" 
                              title="<?= h($cn ?? '') ?>"
                              <?php if (!empty($cData['image'])): ?>
                              data-image="<?= asset_url(h($cData['image'] ?? '')) ?>"
                              <?php endif; ?>></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </a>
                <div class="lume-product-card__body">
                    <h3 class="lume-product-card__name"><a href="<?= SITE_URL ?>/product.php?slug=<?= h($p['slug'] ?? '') ?>"><?= h($p['name'] ?? '') ?></a></h3>
                    <div class="lume-product-card__price"><?= product_price($p) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <nav class="lume-pagination" aria-label="Shop pages" style="margin-top: 3rem; display: flex; justify-content: center; gap: 0.5rem;">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="<?= h($shopUrl($i)) ?>" class="lume-pagination__btn <?= $i===$page ? 'active':'' ?>" <?= $i===$page ? 'aria-current="page"' : '' ?> style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; border: 1px solid <?= $i===$page ? 'var(--gold)' : 'var(--border)' ?>; background: <?= $i===$page ? 'var(--gold)' : 'transparent' ?>; color: <?= $i===$page ? 'var(--bg)' : 'inherit' ?>; text-decoration: none; border-radius: 4px; transition: all 0.3s ease;"><?= $i ?></a>
            <?php endfor; ?>
        </nav>
        <?php endif; ?>
    <?php endif; ?>
</section>

<script>
// ─── Color swatch → card image swap ───
document.querySelectorAll('.lume-product-card__swatch-dot').forEach(dot => {
  dot.addEventListener('mouseenter', () => {
    const img = dot.closest('.lume-product-card').querySelector('.lume-product-card__img');
    if (img && dot.dataset.image) {
      img.dataset.originalSrc = img.dataset.originalSrc || img.src;
      img.src = dot.dataset.image;
    }
  });
  dot.addEventListener('mouseleave', () => {
    const img = dot.closest('.lume-product-card').querySelector('.lume-product-card__img');
    if (img && img.dataset.originalSrc) img.src = img.dataset.originalSrc;
  });
});

// ─── Sort form: preserve hidden params ───
const sortForm = document.getElementById('sort-form');
if (sortForm) {
  const params = new URLSearchParams(location.search);
  ['category','q'].forEach(k => {
    if (params.has(k) && !sortForm.querySelector('[name="'+k+'"]')) {
      const h = document.createElement('input');
      h.type = 'hidden'; h.name = k; h.value = params.get(k);
      sortForm.appendChild(h);
    }
  });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
