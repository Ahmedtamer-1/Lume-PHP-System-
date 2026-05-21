<?php
/**
 * Admin header — shared layout top
 */
$adminPage = $adminPage ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($pageTitle ?? 'Admin') ?> — <?= SITE_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Red+Hat+Display:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= SITE_URL ?>/admin/assets/css/admin.css">
</head>
<body>
<div class="admin-layout">
    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <div class="admin-sidebar__logo">
            <a href="<?= SITE_URL ?>/admin/"><?= SITE_NAME ?></a>
        </div>
        <nav class="admin-sidebar__nav">
            <a href="<?= SITE_URL ?>/admin/" class="admin-nav-link <?= $adminPage === 'dashboard' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                <span>Dashboard</span>
            </a>
            <a href="<?= SITE_URL ?>/admin/products.php" class="admin-nav-link <?= $adminPage === 'products' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                <span>Products</span>
            </a>
            <a href="<?= SITE_URL ?>/admin/categories.php" class="admin-nav-link <?= $adminPage === 'categories' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                <span>Categories</span>
            </a>
            <a href="<?= SITE_URL ?>/admin/orders.php" class="admin-nav-link <?= $adminPage === 'orders' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                <span>Orders</span>
            </a>
            <a href="<?= SITE_URL ?>/admin/users.php" class="admin-nav-link <?= $adminPage === 'users' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                <span>Users</span>
            </a>
            <a href="<?= SITE_URL ?>/admin/media.php" class="admin-nav-link <?= $adminPage === 'media' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                <span>Media</span>
            </a>
            <a href="<?= SITE_URL ?>/admin/homepage.php" class="admin-nav-link <?= $adminPage === 'homepage' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                <span>Homepage</span>
            </a>
            <a href="<?= SITE_URL ?>/admin/export.php" class="admin-nav-link <?= $adminPage === 'export' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                <span>Export / Import</span>
            </a>
            <a href="<?= SITE_URL ?>/admin/shipping.php" class="admin-nav-link <?= $adminPage === 'shipping' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                <span>Shipping Zones</span>
            </a>
            <a href="<?= SITE_URL ?>/admin/settings.php" class="admin-nav-link <?= $adminPage === 'settings' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                <span>Settings</span>
            </a>
            <a href="<?= SITE_URL ?>/admin/pages.php" class="admin-nav-link <?= $adminPage === 'pages' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                <span>Pages</span>
            </a>
            <a href="<?= SITE_URL ?>/admin/activity.php" class="admin-nav-link <?= $adminPage === 'activity' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24"><path d="M2 12h4l3-9 5 18 3-9h5"/></svg>
                <span>Activity Log</span>
            </a>
        </nav>
        <div class="admin-sidebar__bottom">
            <a href="<?= SITE_URL ?>/" class="admin-nav-link" target="_blank">
                <svg viewBox="0 0 24 24"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                <span>View Site</span>
            </a>
            <a href="<?= SITE_URL ?>/account.php?action=logout" class="admin-nav-link">
                <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                <span>Logout</span>
            </a>
        </div>
    </aside>

    <!-- Main content -->
    <div class="admin-main">
        <header class="admin-topbar">
            <h1 class="admin-topbar__title"><?= h($pageTitle ?? 'Dashboard') ?></h1>
            <span class="admin-topbar__user">Hi, <?= h($adminUser['first_name']) ?></span>
        </header>
        <div class="admin-content">
