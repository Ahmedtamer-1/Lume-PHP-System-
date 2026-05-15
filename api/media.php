<?php
/**
 * LUMEEGY — Media Library API
 * Handles AJAX upload, list, and delete for the media manager.
 */
require_once __DIR__ . '/../includes/functions.php';
lume_session_start();

// Auth check — admin only
$user = current_user();
if (!$user || ($user['role'] ?? '') !== 'admin') {
    json_response(['success' => false, 'message' => 'Unauthorized'], 403);
}

// ── LIST ──
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $page   = max(1, (int)($_GET['page'] ?? 1));
    $limit  = 40;
    $offset = ($page - 1) * $limit;
    $search = trim($_GET['search'] ?? '');

    if ($search) {
        $stmt = db()->prepare(
            'SELECT * FROM media_library WHERE filename LIKE ? OR alt_text LIKE ? ORDER BY created_at DESC LIMIT ? OFFSET ?'
        );
        $term = '%' . $search . '%';
        $stmt->execute([$term, $term, $limit, $offset]);
    } else {
        $stmt = db()->prepare('SELECT * FROM media_library ORDER BY created_at DESC LIMIT ? OFFSET ?');
        $stmt->execute([$limit, $offset]);
    }

    $items = $stmt->fetchAll();
    $total = media_count();

    $out = [];
    foreach ($items as $m) {
        $out[] = [
            'id'        => (int)$m['id'],
            'filename'  => $m['filename'],
            'filepath'  => $m['filepath'],
            'url'       => SITE_URL . '/' . $m['filepath'],
            'filetype'  => $m['filetype'],
            'filesize'  => (int)$m['filesize'],
            'width'     => $m['width'] ? (int)$m['width'] : null,
            'height'    => $m['height'] ? (int)$m['height'] : null,
            'alt_text'  => $m['alt_text'] ?? '',
            'created_at'=> $m['created_at'],
        ];
    }

    json_response([
        'success' => true,
        'items'   => $out,
        'total'   => $total,
        'page'    => $page,
        'pages'   => ceil($total / $limit),
    ]);
}

// ── POST: Upload / Delete / Update ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'upload';

    if ($action === 'upload') {
        $results = [];

        if (!empty($_FILES['files'])) {
            $files = $_FILES['files'];
            $count = is_array($files['name']) ? count($files['name']) : 1;

            for ($i = 0; $i < $count; $i++) {
                $file = [
                    'name'     => is_array($files['name']) ? $files['name'][$i] : $files['name'],
                    'type'     => is_array($files['type']) ? $files['type'][$i] : $files['type'],
                    'tmp_name' => is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'],
                    'error'    => is_array($files['error']) ? $files['error'][$i] : $files['error'],
                    'size'     => is_array($files['size']) ? $files['size'][$i] : $files['size'],
                ];

                $result = media_upload($file, (int)$user['id']);
                if ($result) {
                    $results[] = $result;
                }
            }
        }

        if (empty($results)) {
            json_response(['success' => false, 'message' => 'No files uploaded or invalid file types'], 400);
        }

        json_response(['success' => true, 'uploaded' => $results, 'count' => count($results)]);
    }

    if ($action === 'delete') {
        $id = (int)($_POST['media_id'] ?? 0);
        if ($id <= 0) json_response(['success' => false, 'message' => 'Invalid ID'], 400);

        $ok = media_delete($id);
        json_response(['success' => $ok]);
    }

    if ($action === 'update_alt') {
        $id  = (int)($_POST['media_id'] ?? 0);
        $alt = trim($_POST['alt_text'] ?? '');
        if ($id <= 0) json_response(['success' => false, 'message' => 'Invalid ID'], 400);

        db()->prepare('UPDATE media_library SET alt_text = ? WHERE id = ?')->execute([$alt, $id]);
        json_response(['success' => true]);
    }
}

json_response(['error' => 'Bad request'], 400);
