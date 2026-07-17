<?php
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/admin/orders.php';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
session_start();
$_SESSION['user_id'] = 1; // Assuming admin user ID is 1
require_once 'includes/functions.php';
// Mock db to ensure user 1 is admin
db()->prepare("UPDATE users SET role = 'admin' WHERE id = 1")->execute();
require_once 'admin/orders.php';
