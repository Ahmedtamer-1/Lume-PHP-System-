<?php
/**
 * Settings API Route
 */
require_once __DIR__ . '/../models/SettingModel.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $settings = SettingModel::getPublicSettings();
    echo json_encode(['status' => 200, 'data' => $settings]);
} else {
    http_response_code(405);
    echo json_encode(['status' => 405, 'error' => 'Method Not Allowed']);
}
