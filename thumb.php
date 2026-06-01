<?php
/**
 * LUMEEGY — Image Thumbnail Generator
 * Dynamically resizes images, converts to WebP, and caches them.
 */

// Basic setup
define('ROOT_PATH', __DIR__);
define('CACHE_DIR', ROOT_PATH . '/assets/images/cache');

$src = $_GET['src'] ?? '';
$w = (int)($_GET['w'] ?? 0);

if (!$src || $w <= 0) {
    header('HTTP/1.1 400 Bad Request');
    exit('Missing src or width');
}

// Clean and validate source path
$src = ltrim(parse_url($src, PHP_URL_PATH), '/'); // Remove leading slash and query params
$srcPath = ROOT_PATH . '/' . $src;

if (!file_exists($srcPath)) {
    // If original file doesn't exist, we can't resize it.
    header('HTTP/1.1 404 Not Found');
    exit('Image not found');
}

// Create cache directory if it doesn't exist
if (!is_dir(CACHE_DIR)) {
    @mkdir(CACHE_DIR, 0755, true);
}

// Generate a unique hash for the cached file based on path, width, and file modified time
$filemtime = filemtime($srcPath);
$hash = md5($src . $w . $filemtime);
$cachePath = CACHE_DIR . '/' . $hash . '.webp';

// If cache exists and is fresh, serve it immediately
if (file_exists($cachePath)) {
    serve_image($cachePath, 'image/webp');
}

// Otherwise, generate the thumbnail
$imgInfo = @getimagesize($srcPath);
if (!$imgInfo) {
    header('HTTP/1.1 415 Unsupported Media Type');
    exit('Not a valid image');
}

$origW = $imgInfo[0];
$origH = $imgInfo[1];
$mime = $imgInfo['mime'];

// If requested width is larger than original, just use original
if ($w >= $origW) {
    serve_image($srcPath, $mime);
}

// Calculate new dimensions while maintaining aspect ratio
$h = (int)round(($w / $origW) * $origH);

// Load original image into GD
switch ($mime) {
    case 'image/jpeg':
        $sourceImage = @imagecreatefromjpeg($srcPath);
        break;
    case 'image/png':
        $sourceImage = @imagecreatefrompng($srcPath);
        break;
    case 'image/webp':
        $sourceImage = @imagecreatefromwebp($srcPath);
        break;
    case 'image/gif':
        $sourceImage = @imagecreatefromgif($srcPath);
        break;
    default:
        // Unsupported format for resizing, serve original
        serve_image($srcPath, $mime);
}

if (!$sourceImage) {
    serve_image($srcPath, $mime);
}

// Create new true color image
$thumb = imagecreatetruecolor($w, $h);

// Preserve transparency for PNG and WebP
if ($mime === 'image/png' || $mime === 'image/webp' || $mime === 'image/gif') {
    imagealphablending($thumb, false);
    imagesavealpha($thumb, true);
    $transparent = imagecolorallocatealpha($thumb, 255, 255, 255, 127);
    imagefilledrectangle($thumb, 0, 0, $w, $h, $transparent);
}

// Resize
imagecopyresampled($thumb, $sourceImage, 0, 0, 0, 0, $w, $h, $origW, $origH);

// Save to cache as WebP (quality 80)
imagewebp($thumb, $cachePath, 80);

// Free up memory
imagedestroy($sourceImage);
imagedestroy($thumb);

// Serve the newly created cache
if (file_exists($cachePath)) {
    serve_image($cachePath, 'image/webp');
} else {
    // Fallback if cache write failed (permissions issue)
    serve_image($srcPath, $mime);
}


/**
 * Helper to serve the image file directly to the browser
 */
function serve_image($path, $mime) {
    header('Content-Type: ' . $mime);
    header('Cache-Control: public, max-age=31536000, immutable'); // Cache for 1 year
    header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + 31536000));
    header('Content-Length: ' . filesize($path));
    readfile($path);
    exit;
}
