<?php
/**
 * PWA Icon Generator Script
 * Usage: php generate_pwa_icons.php
 * 
 * This script generates PWA icons in various sizes from the source logo.
 * Source: public/images/jri-official-logo.png (or company logo from storage)
 */

// Size configurations for PWA icons
$sizes = [72, 96, 128, 144, 152, 192, 384, 512];

// Check for source image
$sourceImage = null;
$possibleSources = [
    __DIR__ . '/storage/app/public/logos/logo.png',
    __DIR__ . '/public/images/jri-official-logo.png',
    __DIR__ . '/public/images/jicos_logo.png',
    __DIR__ . '/public/logo.png',
];

foreach ($possibleSources as $source) {
    if (file_exists($source)) {
        $sourceImage = $source;
        echo "✓ Found source image: $source\n";
        break;
    }
}

if (!$sourceImage) {
    die("❌ No source logo found. Please ensure one of these exists:\n" . implode("\n", $possibleSources) . "\n");
}

// Ensure icons directory exists
$iconsDir = __DIR__ . '/public/icons';
if (!is_dir($iconsDir)) {
    mkdir($iconsDir, 0755, true);
    echo "✓ Created icons directory\n";
}

// Load source image
$imageInfo = getimagesize($sourceImage);
$sourceWidth = $imageInfo[0];
$sourceHeight = $imageInfo[1];
$mimeType = $imageInfo['mime'];

echo "✓ Source image: {$sourceWidth}x{$sourceHeight} ($mimeType)\n";

// Create source GD image based on type
switch ($mimeType) {
    case 'image/png':
        $source = imagecreatefrompng($sourceImage);
        break;
    case 'image/jpeg':
        $source = imagecreatefromjpeg($sourceImage);
        break;
    case 'image/gif':
        $source = imagecreatefromgif($sourceImage);
        break;
    default:
        die("❌ Unsupported image type: $mimeType\n");
}

// Enable alpha blending
imagealphablending($source, true);
imagesavealpha($source, true);

// Generate icons for each size
foreach ($sizes as $size) {
    $outputPath = "$iconsDir/icon-{$size}x{$size}.png";
    
    // Create new image with proper size
    $dest = imagecreatetruecolor($size, $size);
    
    // Enable alpha for transparency
    imagealphablending($dest, false);
    imagesavealpha($dest, true);
    
    // Fill with transparent background
    $transparent = imagecolorallocatealpha($dest, 0, 0, 0, 127);
    imagefill($dest, 0, 0, $transparent);
    
    // Enable blending for the copy
    imagealphablending($dest, true);
    
    // Calculate aspect ratio and positioning
    $aspectRatio = $sourceWidth / $sourceHeight;
    
    if ($aspectRatio > 1) {
        // Wider than tall
        $newWidth = $size;
        $newHeight = (int)($size / $aspectRatio);
        $x = 0;
        $y = (int)(($size - $newHeight) / 2);
    } else {
        // Taller than wide or square
        $newWidth = (int)($size * $aspectRatio);
        $newHeight = $size;
        $x = (int)(($size - $newWidth) / 2);
        $y = 0;
    }
    
    // Resize and copy
    imagecopyresampled($dest, $source, $x, $y, 0, 0, $newWidth, $newHeight, $sourceWidth, $sourceHeight);
    
    // Save as PNG
    imagesavealpha($dest, true);
    imagepng($dest, $outputPath, 9);
    imagedestroy($dest);
    
    echo "✓ Generated: icon-{$size}x{$size}.png\n";
}

// Clean up
imagedestroy($source);

echo "\n=== PWA Icons Generated Successfully! ===\n";
echo "Icons are located in: $iconsDir\n";
echo "\n⚠️  Remember to delete this script after use!\n";
