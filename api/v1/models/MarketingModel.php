<?php
/**
 * Headless API Marketing Model
 */

class MarketingModel {
    /**
     * Subscribe to newsletter
     */
    public static function subscribeNewsletter(string $email, string $source = 'footer'): array {
        $stmt = db()->prepare('SELECT id, status FROM newsletter_subscribers WHERE email = ?');
        $stmt->execute([$email]);
        $existing = $stmt->fetch();

        if ($existing) {
            if ($existing['status'] === 'unsubscribed') {
                db()->prepare("UPDATE newsletter_subscribers SET status = 'active', subscribed_at = CURRENT_TIMESTAMP WHERE id = ?")
                    ->execute([$existing['id']]);
                return ['success' => true, 'message' => 'Resubscribed successfully!'];
            }
            return ['success' => true, 'message' => 'You are already subscribed!'];
        }

        db()->prepare("INSERT INTO newsletter_subscribers (email, status, source) VALUES (?, 'active', ?)")
            ->execute([$email, $source]);
            
        return ['success' => true, 'message' => 'Subscribed successfully!'];
    }

    /**
     * Submit contact message
     */
    public static function submitContact(string $name, string $email, string $subject, string $message): array {
        $ip = get_client_ip();
        
        // Anti-spam rate limiting for contact form
        $identifier = 'contact|' . $ip;
        if (is_rate_limited($identifier, 3, 3600)) { // Max 3 messages per hour
            return ['success' => false, 'message' => 'You have sent too many messages. Please try again later.'];
        }

        db()->prepare('INSERT INTO contact_messages (name, email, subject, message, ip) VALUES (?,?,?,?,?)')
            ->execute([$name, $email, $subject, $message, $ip]);
            
        record_login_attempt($identifier, true); // Use this to count attempts
        return ['success' => true, 'message' => 'Message sent successfully. We will get back to you soon.'];
    }
}
