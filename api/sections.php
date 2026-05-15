<?php
/**
 * LUMEEGY — Homepage Sections API
 * Handles AJAX operations for the homepage section builder.
 */
require_once __DIR__ . '/../includes/functions.php';
lume_session_start();

$user = current_user();
if (!$user || ($user['role'] ?? '') !== 'admin') {
    json_response(['success' => false, 'message' => 'Unauthorized'], 403);
}

// ── GET: List sections ──
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $sections = get_homepage_sections(false);
    $out = [];
    foreach ($sections as $s) {
        $out[] = [
            'id'           => (int)$s['id'],
            'section_type' => $s['section_type'],
            'title'        => $s['title'],
            'subtitle'     => $s['subtitle'],
            'content'      => $s['content'],
            'image'        => $s['image'],
            'button_text'  => $s['button_text'],
            'button_url'   => $s['button_url'],
            'settings'     => json_decode($s['settings'] ?? '{}', true),
            'is_active'    => (int)$s['is_active'],
            'sort_order'   => (int)$s['sort_order'],
        ];
    }
    json_response(['success' => true, 'sections' => $out]);
}

// ── POST ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Add new section
    if ($action === 'add') {
        $type     = trim($_POST['section_type'] ?? 'text_block');
        $title    = trim($_POST['title'] ?? '');
        $subtitle = trim($_POST['subtitle'] ?? '');
        $content  = trim($_POST['content'] ?? '');
        $image    = trim($_POST['image'] ?? '');
        $btnText  = trim($_POST['button_text'] ?? '');
        $btnUrl   = trim($_POST['button_url'] ?? '');
        $settings = $_POST['settings'] ?? '{}';
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        // Get max sort order
        $maxSort = (int)db()->query('SELECT COALESCE(MAX(sort_order),0) FROM homepage_sections')->fetchColumn();

        db()->prepare(
            'INSERT INTO homepage_sections (section_type, title, subtitle, content, image, button_text, button_url, settings, is_active, sort_order)
             VALUES (?,?,?,?,?,?,?,?,?,?)'
        )->execute([$type, $title ?: null, $subtitle ?: null, $content ?: null, $image ?: null, $btnText ?: null, $btnUrl ?: null, $settings, $isActive, $maxSort + 1]);

        json_response(['success' => true, 'id' => (int)db()->lastInsertId()]);
    }

    // Update section
    if ($action === 'update') {
        $id       = (int)($_POST['section_id'] ?? 0);
        $title    = trim($_POST['title'] ?? '');
        $subtitle = trim($_POST['subtitle'] ?? '');
        $content  = trim($_POST['content'] ?? '');
        $image    = trim($_POST['image'] ?? '');
        $btnText  = trim($_POST['button_text'] ?? '');
        $btnUrl   = trim($_POST['button_url'] ?? '');
        $settings = $_POST['settings'] ?? '{}';
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($id <= 0) json_response(['success' => false, 'message' => 'Invalid ID'], 400);

        db()->prepare(
            'UPDATE homepage_sections SET title=?, subtitle=?, content=?, image=?, button_text=?, button_url=?, settings=?, is_active=? WHERE id=?'
        )->execute([$title ?: null, $subtitle ?: null, $content ?: null, $image ?: null, $btnText ?: null, $btnUrl ?: null, $settings, $isActive, $id]);

        json_response(['success' => true]);
    }

    // Delete section
    if ($action === 'delete') {
        $id = (int)($_POST['section_id'] ?? 0);
        if ($id <= 0) json_response(['success' => false, 'message' => 'Invalid ID'], 400);

        db()->prepare('DELETE FROM homepage_sections WHERE id = ?')->execute([$id]);
        json_response(['success' => true]);
    }

    // Toggle active
    if ($action === 'toggle') {
        $id = (int)($_POST['section_id'] ?? 0);
        if ($id <= 0) json_response(['success' => false, 'message' => 'Invalid ID'], 400);

        db()->prepare('UPDATE homepage_sections SET is_active = NOT is_active WHERE id = ?')->execute([$id]);
        json_response(['success' => true]);
    }

    // Reorder sections
    if ($action === 'reorder') {
        $orderJson = $_POST['order'] ?? '[]';
        $order     = json_decode($orderJson, true);
        if (is_array($order)) {
            $stmt = db()->prepare('UPDATE homepage_sections SET sort_order = ? WHERE id = ?');
            foreach ($order as $i => $id) {
                $stmt->execute([$i + 1, (int)$id]);
            }
        }
        json_response(['success' => true]);
    }
}

json_response(['error' => 'Bad request'], 400);
