<?php
require_once __DIR__ . '/includes/functions.php';
lume_session_start();

$clientId = defined('GOOGLE_CLIENT_ID') ? GOOGLE_CLIENT_ID : '';
$clientSecret = defined('GOOGLE_CLIENT_SECRET') ? GOOGLE_CLIENT_SECRET : '';
$redirectUri = rtrim(SITE_URL, '/') . '/google-callback.php';

if (!$clientId || !$clientSecret) {
    die('Google OAuth is not configured. Please add GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET to your environment variables.');
}

if (isset($_GET['error'])) {
    redirect('/account.php?error=' . urlencode('Google login failed or was cancelled.'));
}

if (isset($_GET['code'])) {
    $code = $_GET['code'];

    // 1. Exchange the code for an access token
    $tokenUrl = 'https://oauth2.googleapis.com/token';
    $postData = [
        'code' => $code,
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'redirect_uri' => $redirectUri,
        'grant_type' => 'authorization_code'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $tokenUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error || !$response) {
        redirect('/account.php?error=' . urlencode('Failed to connect to Google (token).'));
    }

    $tokenData = json_decode($response, true);
    if (!isset($tokenData['access_token'])) {
        redirect('/account.php?error=' . urlencode('Invalid access token from Google.'));
    }

    $accessToken = $tokenData['access_token'];

    // 2. Fetch the user's profile info
    $infoUrl = 'https://www.googleapis.com/oauth2/v2/userinfo';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $infoUrl);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error || !$response) {
        redirect('/account.php?error=' . urlencode('Failed to connect to Google (user info).'));
    }

    $userInfo = json_decode($response, true);
    if (!isset($userInfo['id']) || !isset($userInfo['email'])) {
        redirect('/account.php?error=' . urlencode('Failed to retrieve user information from Google.'));
    }

    $googleId = $userInfo['id'];
    $email = $userInfo['email'];
    $firstName = $userInfo['given_name'] ?? 'Google';
    $lastName = $userInfo['family_name'] ?? 'User';

    // 3. Check if user exists by google_id
    $stmt = db()->prepare('SELECT id FROM users WHERE google_id = ?');
    $stmt->execute([$googleId]);
    $user = $stmt->fetch();

    if ($user) {
        // User exists via google_id, log them in
        login_user((int)$user['id']);
        redirect('/account.php');
    } else {
        // Check if user exists by email
        $stmt = db()->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $userByEmail = $stmt->fetch();

        if ($userByEmail) {
            // User exists by email, update their google_id to link accounts
            $update = db()->prepare('UPDATE users SET google_id = ? WHERE id = ?');
            $update->execute([$googleId, $userByEmail['id']]);
            login_user((int)$userByEmail['id']);
            redirect('/account.php');
        } else {
            // Create a new user
            $dummyPassword = bin2hex(random_bytes(16)); // Random secure password since they use Google
            $hash = password_hash($dummyPassword, PASSWORD_BCRYPT);
            
            // Try to insert new user
            try {
                $insert = db()->prepare('INSERT INTO users (first_name, last_name, email, password_hash, google_id) VALUES (?, ?, ?, ?, ?)');
                $insert->execute([$firstName, $lastName, $email, $hash, $googleId]);
                login_user((int)db()->lastInsertId());
                redirect('/account.php');
            } catch (Exception $e) {
                redirect('/account.php?error=' . urlencode('Failed to create account.'));
            }
        }
    }
}

// Fallback if accessed directly without code
redirect('/account.php');
