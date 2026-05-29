<?php
/**
 * LUMEEGY — Paymob Webhook Callback
 * 
 * Handles server-to-server transaction status updates from Paymob.
 */
require_once __DIR__ . '/../includes/functions.php';

// 1. Ensure method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

// 2. Read JSON payload
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    exit('Bad Request');
}

// Paymob sometimes sends different events, we only care about Transaction Processed
if (($data['type'] ?? '') !== 'TRANSACTION') {
    http_response_code(200); // Acknowledge other events
    exit;
}

$obj = $data['obj'] ?? [];
if (!$obj) {
    http_response_code(400);
    exit('Missing object data');
}

// 3. Verify HMAC for security
$hmacSecret = setting('paymob_hmac');
if (empty($hmacSecret)) {
    error_log('Paymob Webhook Error: HMAC Secret is not configured in settings.');
    http_response_code(500);
    exit('Server Configuration Error');
}

$receivedHmac = $_GET['hmac'] ?? '';
if (!$receivedHmac) {
    error_log('Paymob Webhook Error: Missing HMAC parameter.');
    http_response_code(401);
    exit('Unauthorized');
}

// Paymob requires concatenating these specific fields in this exact order to generate the HMAC string
$hmacFields = [
    'amount_cents',
    'created_at',
    'currency',
    'error_occured',
    'has_parent_transaction',
    'id',
    'integration_id',
    'is_3d_secure',
    'is_auth',
    'is_capture',
    'is_refunded',
    'is_standalone_payment',
    'is_voided',
    'order', // This is an array, we extract the id
    'owner',
    'pending',
    'source_data', // This is an array, we extract pan, type, sub_type
    'success'
];

$hmacString = '';
foreach ($hmacFields as $field) {
    if ($field === 'order' && isset($obj['order']['id'])) {
        $hmacString .= $obj['order']['id'];
    } elseif ($field === 'source_data' && isset($obj['source_data'])) {
        $hmacString .= ($obj['source_data']['pan'] ?? '') . ($obj['source_data']['sub_type'] ?? '') . ($obj['source_data']['type'] ?? '');
    } else {
        $val = $obj[$field] ?? '';
        // Convert booleans to string 'true' or 'false'
        if (is_bool($val)) {
            $val = $val ? 'true' : 'false';
        }
        $hmacString .= $val;
    }
}

$calculatedHmac = hash_hmac('sha512', $hmacString, $hmacSecret);

if (!hash_equals(strtolower($calculatedHmac), strtolower($receivedHmac))) {
    error_log('Paymob Webhook Error: HMAC signature mismatch.');
    http_response_code(401);
    exit('Unauthorized: Invalid HMAC');
}

// 4. Update Order Status
$merchantOrderIdRaw = $obj['order']['merchant_order_id'] ?? '';
// Note: We appended '_' . time() to avoid duplicate errors in Paymob. We must extract the actual order number.
$parts = explode('_', $merchantOrderIdRaw);
$orderNumber = $parts[0];
$success     = (bool)($obj['success'] ?? false);
$transactionId = $obj['id'] ?? null;

// Find the order
$stmt = db()->prepare('SELECT id, status FROM orders WHERE order_number = ?');
$stmt->execute([$orderNumber]);
$order = $stmt->fetch();

if (!$order) {
    error_log('Paymob Webhook Error: Order ' . $orderNumber . ' not found in DB.');
    http_response_code(200); // Acknowledge to stop retries
    exit('Order not found');
}

// Idempotency: never downgrade a paid/delivered order
$terminalStatuses = ['paid', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'];
if (in_array($order['status'], $terminalStatuses) && !$success) {
    // Already resolved — acknowledge silently
    http_response_code(200);
    exit('OK');
}

if ($success) {
    // Only mark paid if not already paid
    if ($order['status'] !== 'paid') {
        db()->prepare('UPDATE orders SET status = "paid", payment_ref = ?, updated_at = NOW() WHERE id = ?')
            ->execute([$transactionId, $order['id']]);
    }
} else {
    // Payment failed or was declined — auto-cancel and restore stock
    cancel_order((int)$order['id']);

    // Append a system note with the failed transaction ID for admin visibility
    if ($transactionId) {
        db()->prepare(
            'UPDATE orders SET notes = CONCAT(IFNULL(notes, ""), "\n[System] Declined Paymob transaction ID: ", ?) WHERE id = ?'
        )->execute([$transactionId, $order['id']]);
    }
}

http_response_code(200);
exit('OK');
