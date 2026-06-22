<?php
/**
 * Headless API TikTok Events API Integration
 */

class TiktokIntegration {
    /**
     * Send Purchase Event to TikTok Events API
     */
    public static function sendPurchaseEvent(int $orderId, array $orderData, array $items): void {
        $pixelId = setting('tiktok_pixel_id', '');
        $token = setting('tiktok_access_token', '');
        $enabled = setting('tiktok_enabled', '0') === '1';

        if (!$enabled || !$pixelId || !$token) return;

        $eventId = 'order_' . $orderId;
        $url = "https://business-api.tiktok.com/open_api/v1.3/pixel/track/";

        $contents = [];
        foreach ($items as $item) {
            $contents[] = [
                'content_id' => (string)($item['sku'] ?: $item['product_id']),
                'content_type' => 'product',
                'quantity' => (int)$item['quantity'],
                'price' => (float)$item['price']
            ];
        }

        $emailHash = hash('sha256', strtolower(trim($orderData['guest_email'] ?: ($orderData['user_email'] ?? ''))));
        $phoneHash = hash('sha256', preg_replace('/[^0-9]/', '', $orderData['phone'] ?? ''));

        $event = [
            'pixel_code' => $pixelId,
            'event' => 'CompletePayment',
            'event_id' => $eventId,
            'timestamp' => date('c'),
            'context' => [
                'user' => [
                    'emails' => [$emailHash],
                    'phone_numbers' => [$phoneHash]
                ],
                'ip' => get_client_ip(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ],
            'properties' => [
                'contents' => $contents,
                'currency' => setting('currency', 'EGP'),
                'value' => (float)$orderData['total']
            ]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($event));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Access-Token: ' . $token
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $success = $httpCode === 200;
        
        db()->prepare('INSERT INTO pixel_event_log (order_id, platform, event_name, success, response) VALUES (?,?,?,?,?)')
            ->execute([$orderId, 'tiktok', 'CompletePayment', $success ? 1 : 0, $response]);

        if ($success) {
            db()->prepare('UPDATE orders SET tiktok_event_sent = 1 WHERE id = ?')->execute([$orderId]);
        }
    }
}
