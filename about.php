<?php
$pageTitle = 'About — LUMEEGY';
$pageDescription = 'The story of LUMEEGY — a luxury fashion brand born in Egypt, crafted to illuminate your everyday style.';
require_once __DIR__ . '/includes/header.php';
?>

<section class="lume-page-header">
    <div class="container">
        <h1 class="lume-page-header__title">Our Story</h1>
        <p class="lume-page-header__breadcrumb"><a href="<?= SITE_URL ?>/">Home</a> / About</p>
    </div>
</section>

<section class="container">
    <div class="lume-about-intro">
        <div class="lume-about-intro__img lume-reveal-left">
            <img src="<?= SITE_URL ?>/assets/images/hero-bg.png" alt="LUMEEGY story" loading="lazy">
        </div>
        <div class="lume-about-intro__text lume-reveal-right">
            <p class="lume-section__eyebrow">The Beginning</p>
            <h2>Born from Light</h2>
            <p>LUMEEGY was born from a simple belief — that style is a ritual, not a routine. Rooted in the spirit of Egyptian elegance, each piece is crafted to bring a moment of luxury into your everyday.</p>
            <p>We source the finest fabrics and craft each piece with meticulous attention to detail. The result is a collection that honours heritage while delivering timeless, modern style.</p>
            <p>Every texture, every scent, every detail is intentional. Because we believe the way you care for yourself says everything about who you are.</p>
        </div>
    </div>
</section>

<section class="lume-section lume-section--center container lume-reveal">
    <p class="lume-section__eyebrow">Our Values</p>
    <h2 class="lume-section__title">What We Stand For</h2>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:40px;margin-top:48px;text-align:center">
        <div>
            <div style="font-size:2rem;margin-bottom:12px">✦</div>
            <h3 style="font-family:var(--font-serif);font-size:1.1rem;text-transform:uppercase;margin-bottom:8px">Heritage</h3>
            <p style="font-size:.85rem;color:var(--muted);line-height:1.7">Inspired by 5,000 years of Egyptian elegance, reimagined for the modern world.</p>
        </div>
        <div>
            <div style="font-size:2rem;margin-bottom:12px">✦</div>
            <h3 style="font-family:var(--font-serif);font-size:1.1rem;text-transform:uppercase;margin-bottom:8px">Luxury</h3>
            <p style="font-size:.85rem;color:var(--muted);line-height:1.7">Premium fabrics and tailoring that feel as extraordinary as they look.</p>
        </div>
        <div>
            <div style="font-size:2rem;margin-bottom:12px">✦</div>
            <h3 style="font-family:var(--font-serif);font-size:1.1rem;text-transform:uppercase;margin-bottom:8px">Intention</h3>
            <p style="font-size:.85rem;color:var(--muted);line-height:1.7">Every product is designed to transform your routine into a mindful, sensory ritual.</p>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
