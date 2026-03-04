<?php
/**
 * Script to generate overlay PNG images
 * Run this once to create the overlay files
 */

$overlaysDir = dirname(__DIR__) . '/assets/overlays/';

if (!is_dir($overlaysDir)) {
    mkdir($overlaysDir, 0755, true);
}

$width = 640;
$height = 480;

// Frame 1 - Simple border frame
$img = imagecreatetruecolor($width, $height);
imagealphablending($img, false);
imagesavealpha($img, true);
$transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
imagefill($img, 0, 0, $transparent);

$gold = imagecolorallocate($img, 218, 165, 32);
imagesetthickness($img, 10);
imagerectangle($img, 5, 5, $width - 6, $height - 6, $gold);
imagerectangle($img, 15, 15, $width - 16, $height - 16, $gold);

imagepng($img, $overlaysDir . 'frame_gold.png');
imagedestroy($img);
echo "Created frame_gold.png\n";

// Frame 2 - Corner decorations
$img = imagecreatetruecolor($width, $height);
imagealphablending($img, false);
imagesavealpha($img, true);
$transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
imagefill($img, 0, 0, $transparent);

$white = imagecolorallocate($img, 255, 255, 255);
$cornerSize = 80;

// Top-left corner
imageline($img, 0, 0, $cornerSize, 0, $white);
imageline($img, 0, 0, 0, $cornerSize, $white);
imageline($img, 5, 5, $cornerSize - 5, 5, $white);
imageline($img, 5, 5, 5, $cornerSize - 5, $white);

// Top-right corner
imageline($img, $width - 1, 0, $width - $cornerSize - 1, 0, $white);
imageline($img, $width - 1, 0, $width - 1, $cornerSize, $white);
imageline($img, $width - 6, 5, $width - $cornerSize + 4, 5, $white);
imageline($img, $width - 6, 5, $width - 6, $cornerSize - 5, $white);

// Bottom-left corner
imageline($img, 0, $height - 1, $cornerSize, $height - 1, $white);
imageline($img, 0, $height - 1, 0, $height - $cornerSize - 1, $white);
imageline($img, 5, $height - 6, $cornerSize - 5, $height - 6, $white);
imageline($img, 5, $height - 6, 5, $height - $cornerSize + 4, $white);

// Bottom-right corner
imageline($img, $width - 1, $height - 1, $width - $cornerSize - 1, $height - 1, $white);
imageline($img, $width - 1, $height - 1, $width - 1, $height - $cornerSize - 1, $white);
imageline($img, $width - 6, $height - 6, $width - $cornerSize + 4, $height - 6, $white);
imageline($img, $width - 6, $height - 6, $width - 6, $height - $cornerSize + 4, $white);

imagepng($img, $overlaysDir . 'corners_white.png');
imagedestroy($img);
echo "Created corners_white.png\n";

// Frame 3 - Vignette effect
$img = imagecreatetruecolor($width, $height);
imagealphablending($img, true);
imagesavealpha($img, true);
$transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
imagefill($img, 0, 0, $transparent);

$centerX = $width / 2;
$centerY = $height / 2;
$maxDist = sqrt($centerX * $centerX + $centerY * $centerY);

for ($y = 0; $y < $height; $y++) {
    for ($x = 0; $x < $width; $x++) {
        $dist = sqrt(pow($x - $centerX, 2) + pow($y - $centerY, 2));
        $alpha = (int)(($dist / $maxDist) * 100);
        if ($alpha > 80) {
            $color = imagecolorallocatealpha($img, 0, 0, 0, 127 - $alpha + 80);
            imagesetpixel($img, $x, $y, $color);
        }
    }
}

imagepng($img, $overlaysDir . 'vignette.png');
imagedestroy($img);
echo "Created vignette.png\n";

// Frame 4 - Hearts
$img = imagecreatetruecolor($width, $height);
imagealphablending($img, false);
imagesavealpha($img, true);
$transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
imagefill($img, 0, 0, $transparent);
imagealphablending($img, true);

$red = imagecolorallocate($img, 255, 0, 100);
$pink = imagecolorallocate($img, 255, 105, 180);

// Draw some hearts in corners
$heartPositions = [
    [30, 30], [50, 60], [$width - 50, 40], [$width - 80, 70],
    [40, $height - 50], [70, $height - 80], [$width - 60, $height - 40], [$width - 40, $height - 70]
];

foreach ($heartPositions as $i => $pos) {
    $color = $i % 2 == 0 ? $red : $pink;
    $size = rand(15, 25);
    imagefilledellipse($img, $pos[0] - $size/3, $pos[1], $size/1.5, $size, $color);
    imagefilledellipse($img, $pos[0] + $size/3, $pos[1], $size/1.5, $size, $color);
    $points = [
        $pos[0] - $size/1.5, $pos[1],
        $pos[0] + $size/1.5, $pos[1],
        $pos[0], $pos[1] + $size
    ];
    imagefilledpolygon($img, $points, 3, $color);
}

imagepng($img, $overlaysDir . 'hearts.png');
imagedestroy($img);
echo "Created hearts.png\n";

// Frame 5 - Stars
$img = imagecreatetruecolor($width, $height);
imagealphablending($img, false);
imagesavealpha($img, true);
$transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
imagefill($img, 0, 0, $transparent);
imagealphablending($img, true);

$yellow = imagecolorallocate($img, 255, 215, 0);
$white = imagecolorallocate($img, 255, 255, 255);

$starPositions = [
    [40, 40], [100, 25], [$width - 50, 50], [$width - 120, 30],
    [50, $height - 45], [120, $height - 30], [$width - 40, $height - 50], [$width - 100, $height - 35]
];

foreach ($starPositions as $i => $pos) {
    $color = $i % 2 == 0 ? $yellow : $white;
    $size = rand(8, 15);

    // Simple 4-pointed star
    imageline($img, $pos[0] - $size, $pos[1], $pos[0] + $size, $pos[1], $color);
    imageline($img, $pos[0], $pos[1] - $size, $pos[0], $pos[1] + $size, $color);
    imageline($img, $pos[0] - $size*0.7, $pos[1] - $size*0.7, $pos[0] + $size*0.7, $pos[1] + $size*0.7, $color);
    imageline($img, $pos[0] + $size*0.7, $pos[1] - $size*0.7, $pos[0] - $size*0.7, $pos[1] + $size*0.7, $color);
}

imagepng($img, $overlaysDir . 'stars.png');
imagedestroy($img);
echo "Created stars.png\n";

// Frame 6 - Polaroid style
$img = imagecreatetruecolor($width, $height);
imagealphablending($img, false);
imagesavealpha($img, true);
$transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
imagefill($img, 0, 0, $transparent);

$white = imagecolorallocatealpha($img, 255, 255, 255, 10);
$shadow = imagecolorallocatealpha($img, 0, 0, 0, 100);

// White border (polaroid style - thicker at bottom)
imagefilledrectangle($img, 0, 0, 20, $height, $white); // left
imagefilledrectangle($img, $width - 21, 0, $width - 1, $height, $white); // right
imagefilledrectangle($img, 0, 0, $width, 20, $white); // top
imagefilledrectangle($img, 0, $height - 60, $width, $height, $white); // bottom (thicker)

imagepng($img, $overlaysDir . 'polaroid.png');
imagedestroy($img);
echo "Created polaroid.png\n";

echo "\nAll overlays created successfully!\n";
