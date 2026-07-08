<?php
require_once __DIR__ . '/includes/functions.php';
lume_session_start();

$orderId = (int)($_GET['order_id'] ?? 0);
if (!$orderId) {
    redirect('/');
}

$stmt = db()->prepare('SELECT * FROM orders WHERE id = ?');
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order || $order['status'] !== 'pending_payment') {
    redirect('/track-order.php'); // Or some suitable page
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf'] ?? '')) {
        $error = 'Invalid request.';
    } elseif (isset($_FILES['receipt']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['receipt'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
        
        if (!in_array($ext, $allowed)) {
            $error = 'Invalid file format. Please upload JPG, PNG, or PDF.';
        } elseif ($file['size'] > 5 * 1024 * 1024) {
            $error = 'File too large (max 5MB).';
        } else {
            $uploadDir = __DIR__ . '/assets/uploads/receipts/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $filename = 'receipt_' . $order['order_number'] . '_' . time() . '.' . $ext;
            $dest = $uploadDir . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $dbPath = 'assets/uploads/receipts/' . $filename;
                db()->prepare("UPDATE orders SET status = 'payment_uploaded', instapay_receipt = ? WHERE id = ?")
                    ->execute([$dbPath, $orderId]);
                
                $success = 'Receipt uploaded successfully! We will verify and process your order shortly.';
                $order['status'] = 'payment_uploaded';
            } else {
                $error = 'Failed to save the file. Please try again.';
            }
        }
    } else {
        $error = 'Please select a file to upload.';
    }
}

$pageTitle = 'Upload InstaPay Receipt — ' . setting('site_name', SITE_NAME);
require_once __DIR__ . '/includes/header.php';
?>

<section class="lume-page-header">
    <div class="container">
        <h1 class="lume-page-header__title">Complete Your Payment</h1>
    </div>
</section>

<section class="container" style="max-width: 600px; padding: 40px 0; min-height: 50vh;">
    <?php if ($success): ?>
        <div class="lume-alert lume-alert--success" style="background:var(--cream); color:var(--text); border:1px solid var(--gold); padding:24px; border-radius:8px; text-align:center;">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--gold)" stroke-width="2" style="margin-bottom:16px;">
                <circle cx="12" cy="12" r="10"></circle>
                <path d="M8 12l2 2 4-4"></path>
            </svg>
            <h2 style="font-size:1.2rem; margin-bottom:8px;">Thank You</h2>
            <p><?= h($success) ?></p>
            <a href="<?= SITE_URL ?>/shop.php" class="lume-btn lume-btn--solid" style="margin-top:24px;">Return to Shop</a>
        </div>
    <?php else: ?>
        <div style="background:var(--bg-card); padding:32px; border-radius:8px; border:1px solid var(--border);">
            <p style="font-size:1.1rem; margin-bottom: 24px;">Your order <strong style="color:var(--gold);">#<?= h($order['order_number']) ?></strong> has been created.</p>
            
            <div style="background:rgba(200,184,154,0.1); padding:20px; border-radius:4px; margin-bottom:24px;">
                <h3 style="font-size:1rem; margin-bottom:12px; color:var(--gold);">Instructions:</h3>
                <p style="margin-bottom:8px;">1. Open the InstaPay app.</p>
                <p style="margin-bottom:8px;">2. Send exactly <strong><?= money((float)$order['total']) ?></strong> to the following InstaPay address:</p>
                <div style="font-size:1.2rem; font-weight:bold; letter-spacing:1px; background:var(--bg); padding:12px; text-align:center; border:1px dashed var(--border); margin: 12px 0;">
                    <?= h(setting('instapay_username', 'your_instapay_username@instapay')) ?>
                </div>
                <p style="margin-bottom:0;">3. Take a screenshot of the successful transfer and upload it below.</p>
            </div>

            <?php if ($error): ?>
                <div class="lume-alert lume-alert--error" style="margin-bottom:24px;"><?= h($error) ?></div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data" class="lume-form">
                <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                <div class="lume-form__group">
                    <label class="lume-form__label" for="receipt">Upload Receipt (Screenshot)</label>
                    <input class="lume-form__input" type="file" name="receipt" id="receipt" accept="image/*,.pdf" required style="padding: 12px; background:var(--bg);">
                </div>
                <button type="submit" class="lume-btn lume-btn--full lume-btn--solid" style="margin-top:16px;">Submit Payment Proof</button>
            </form>
        </div>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
