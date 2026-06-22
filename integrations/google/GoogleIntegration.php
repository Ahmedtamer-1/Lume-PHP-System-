<?php
/**
 * Headless API Google GA4 Measurement Protocol Integration
 */

class GoogleIntegration {
    /**
     * Send Purchase Event to GA4 Measurement Protocol
     */
    public static function sendPurchaseEvent(int $orderId, array $orderData, array $items): void {
        $measurementId = setting('ga4_measurement_id', '');
        $apiSecret = setting('ga4_api_secret', '');
        $enabled = setting('google_events_enabled', '0') === '1';

        if (!$enabled || !$measurementId || !$apiSecret) return;

        // GA4 needs a client_id. Since this is server-side, we ideally want the _ga cookie value.
        // If it's missing, we use a fallback hash.
        $clientId = $_COOKIE['_ga'] ?? null;
        if ($clientId && preg_match('/^GA1\.\d+\.(.+)$/', $clientId, $matches)) {
            $clientId = $matches[1];
        } else {
            $clientId = md5(get_client_ip() . ($_SERVER['HTTP_USER_AGENT'] ?? ''));
        }

        $url = "https://www.google-analytics.com/mp/collect?measurement_id={$measurementId}&api_secret={$apiSecret}";

        $gaItems = [];
        foreach ($items as $item) {
            $gaItems[] = [
                'item_id' => (string)($item['sku'] ?: $item['product_id']),
                'item_name' => $item['name'],
                'price' => (float)$item['price'],
                'quantity' => (int)$item['quantity']
            ];
        }

        $event = [
            'client_id' => $clientId,
            'events' => [
                [
                    'name' => 'purchase',
                    'params' => [
                        'currency' => setting('currency', 'EGP'),
                        'value' => (float)$orderData['total'],
                        'transaction_id' => $orderData['order_number'] ?? 'ORD-' . $orderId,
                        'shipping' => (float)($orderData['shipping_cost'] ?? 0),
                        'tax' => (float)($orderData['tax'] ?? 0),
                        'items' => $gaItems
                    ]
                ]
            ]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($event));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // GA4 MP typically returns 204 No Content on success
        $success = in_array($httpCode, [200, 204]);
        
        db()->prepare('INSERT INTO pixel_event_log (order_id, platform, event_name, success, response) VALUES (?,?,?,?,?)')
            ->execute([$orderId, 'google', 'purchase', $success ? 1 : 0, $response]);

        if ($success) {
            db()->prepare('UPDATE orders SET google_event_sent = 1 WHERE id = ?')->execute([$orderId]);
        }
    }
}
