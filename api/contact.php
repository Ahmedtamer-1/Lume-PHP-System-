<?php
require_once __DIR__ . '/../includes/functions.php';
lume_session_start();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['success'=>false,'message'=>'Method not allowed'],405);
if (!csrf_verify($_POST['csrf'] ?? '')) json_response(['success'=>false,'message'=>'Invalid request'],403);

$name    = trim($_POST['name'] ?? '');
$email   = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

if (!$name || !$email || !$message) json_response(['success'=>false,'message'=>'Please fill in all required fields.']);

try {
    db()->prepare('INSERT INTO contact_messages (name, email, subject, message, ip) VALUES (?,?,?,?,?)')
        ->execute([$name, $email, $subject, $message, get_client_ip()]);
    json_response(['success'=>true,'message'=>'Message sent successfully! We\'ll get back to you soon.']);
} catch (Exception $e) {
    json_response(['success'=>false,'message'=>'Something went wrong. Please try again.']);
}
