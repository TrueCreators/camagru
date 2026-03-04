<?php
/**
 * Скрипт для генерации PNG-оверлеев
 * Запустите один раз, чтобы создать файлы оверлеев
 */

$overlaysDir = dirname(__DIR__) . '/assets/overlays/';

if (!is_dir($overlaysDir)) {
    mkdir($overlaysDir, 0755, true);
}

$width = 640;
$height = 480;

// Рамка 1 - Простая рамка по краю
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

// Рамка 2 - Угловые украшения
$img = imagecreatetruecolor($width, $height);
imagealphablending($img, false);
imagesavealpha($img, true);
$transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
imagefill($img, 0, 0, $transparent);

$white = imagecolorallocate($img, 255, 255, 255);
$cornerSize = 80;

// Левый верхний угол
imageline($img, 0, 0, $cornerSize, 0, $white);
imageline($img, 0, 0, 0, $cornerSize, $white);
imageline($img, 5, 5, $cornerSize - 5, 5, $white);
imageline($img, 5, 5, 5, $cornerSize - 5, $white);

// Правый верхний угол
imageline($img, $width - 1, 0, $width - $cornerSize - 1, 0, $white);
imageline($img, $width - 1, 0, $width - 1, $cornerSize, $white);
imageline($img, $width - 6, 5, $width - $cornerSize + 4, 5, $white);
imageline($img, $width - 6, 5, $width - 6, $cornerSize - 5, $white);

// Левый нижний угол
imageline($img, 0, $height - 1, $cornerSize, $height - 1, $white);
imageline($img, 0, $height - 1, 0, $height - $cornerSize - 1, $white);
imageline($img, 5, $height - 6, $cornerSize - 5, $height - 6, $white);
imageline($img, 5, $height - 6, 5, $height - $cornerSize + 4, $white);

// Правый нижний угол
imageline($img, $width - 1, $height - 1, $width - $cornerSize - 1, $height - 1, $white);
imageline($img, $width - 1, $height - 1, $width - 1, $height - $cornerSize - 1, $white);
imageline($img, $width - 6, $height - 6, $width - $cornerSize + 4, $height - 6, $white);
imageline($img, $width - 6, $height - 6, $width - 6, $height - $cornerSize + 4, $white);

imagepng($img, $overlaysDir . 'corners_white.png');
imagedestroy($img);
echo "Created corners_white.png\n";

// Рамка 3 - Эффект виньетки
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

// Рамка 4 - Сердца
$img = imagecreatetruecolor($width, $height);
imagealphablending($img, false);
imagesavealpha($img, true);
$transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
imagefill($img, 0, 0, $transparent);
imagealphablending($img, true);

$red = imagecolorallocate($img, 255, 0, 100);
$pink = imagecolorallocate($img, 255, 105, 180);

// Рисуем несколько сердец по углам
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

// Рамка 5 - Звезды
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

    // Простая 4-конечная звезда
    imageline($img, $pos[0] - $size, $pos[1], $pos[0] + $size, $pos[1], $color);
    imageline($img, $pos[0], $pos[1] - $size, $pos[0], $pos[1] + $size, $color);
    imageline($img, $pos[0] - $size*0.7, $pos[1] - $size*0.7, $pos[0] + $size*0.7, $pos[1] + $size*0.7, $color);
    imageline($img, $pos[0] + $size*0.7, $pos[1] - $size*0.7, $pos[0] - $size*0.7, $pos[1] + $size*0.7, $color);
}

imagepng($img, $overlaysDir . 'stars.png');
imagedestroy($img);
echo "Created stars.png\n";

// Рамка 6 - Стиль Polaroid
$img = imagecreatetruecolor($width, $height);
imagealphablending($img, false);
imagesavealpha($img, true);
$transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
imagefill($img, 0, 0, $transparent);

$white = imagecolorallocatealpha($img, 255, 255, 255, 10);
$shadow = imagecolorallocatealpha($img, 0, 0, 0, 100);

// Белая рамка (стиль polaroid - толще снизу)
imagefilledrectangle($img, 0, 0, 20, $height, $white); // слева
imagefilledrectangle($img, $width - 21, 0, $width - 1, $height, $white); // справа
imagefilledrectangle($img, 0, 0, $width, 20, $white); // сверху
imagefilledrectangle($img, 0, $height - 60, $width, $height, $white); // снизу (толще)

imagepng($img, $overlaysDir . 'polaroid.png');
imagedestroy($img);
echo "Created polaroid.png\n";

echo "\nAll overlays created successfully!\n";
