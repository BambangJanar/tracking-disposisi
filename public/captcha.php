<?php
// public/captcha.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Gregwar\Captcha\CaptchaBuilder;

// Ambil tema warna dari pengaturan
$settings = dbSelectOne("SELECT theme_color FROM settings WHERE id = 1 LIMIT 1");
$themeColor = $settings['theme_color'] ?? 'blue';

// Mapping warna tema (shade 100 agar selalu terang)
$themeColorsRgb = [
    'blue' => [219, 234, 254],
    'indigo' => [224, 231, 255],
    'red' => [254, 226, 226],
    'emerald' => [209, 250, 229],
    'orange' => [255, 237, 213],
    'purple' => [243, 232, 255],
    'cyan' => [207, 250, 254],
    'slate' => [241, 245, 249]
];
$rgb = $themeColorsRgb[$themeColor] ?? [219, 234, 254];

// Create a captcha builder
$builder = new CaptchaBuilder;
$builder->setBackgroundColor($rgb[0], $rgb[1], $rgb[2]); // Set background dinamis
$builder->setMaxBehindLines(0);
$builder->setMaxFrontLines(3);
// Generate the captcha code (5 characters is usually good)
$builder->build(150, 40, null); // width, height, font
$_SESSION['captcha_code'] = $builder->getPhrase();

// Output image directly
header('Content-type: image/jpeg');
$builder->output();
