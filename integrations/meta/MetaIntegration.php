<?php
/**
 * Headless API Meta Conversions API Integration
 */

class MetaIntegration {
    /**
     * Send Purchase Event to Meta Conversions API
     */
    public static function sendPurchaseEvent(int $orderId, array $orderData, array $items): void {
        $pixelId = setting('meta_pixel_id', '');
        $token = setting('meta_conversions_api_token', '');
        $enabled = setting('meta_capi_enabled', '0') === '1';

        if (!$enabled || !$pixelId || !$token) return;

        // Dedup key: order ID
        $eventId = 'order_' . $orderId;
        $url = "https://graph.facebook.com/v19.0/{$pixelId}/events";

        $contents = [];
        foreach ($items as $item) {
            $contents[] = [
                'id' => $item['sku'] ?: $item['product_id'],
                'quantity' => (int)$item['quantity'],
                'item_price' => (float)$item['price']
            ];
        }

        $userData = [
            'em' => hash('sha256', strtolower(trim($orderData['guest_email'] ?: ($orderData['user_email'] ?? '')))),
            'ph' => hash('sha256', preg_replace('/[^0-9]/', '', $orderData['phone'] ?? '')),
            'fn' => hash('sha256', strtolower(trim(explode(' ', $orderData['shipping_name'])[0] ?? ''))),
            'client_ip_address' => get_client_ip(),
            'client_user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        ];

        $event = [
            'data' => [
                [
                    'event_name' => 'Purchase',
                    'event_time' => time(),
                    'event_id' => $eventId,
                    'action_source' => 'website',
                    'user_data' => array_filter($userData),
                    'custom_data' => [
                        'currency' => setting('currency', 'EGP'),
                        'value' => (float)$orderData['total'],
                        'contents' => $contents
                    ]
                ]
            ]
        ];

        $testCode = setting('meta_test_event_code', '');
        if ($testCode) {
            $event['test_event_code'] = $testCode;
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($event));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $success = $httpCode === 200;
        
        // Log event
        db()->prepare('INSERT INTO pixel_event_log (order_id, platform, event_name, success, response) VALUES (?,?,?,?,?)')
            ->execute([$orderId, 'meta', 'Purchase', $success ? 1 : 0, $response]);

        if ($success) {
            db()->prepare('UPDATE orders SET meta_pixel_event_sent = 1 WHERE id = ?')->execute([$orderId]);
        }
    }
}
