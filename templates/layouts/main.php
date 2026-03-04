<?php

use Core\View;
use Core\Session;
use Core\CSRF;

$title = $title ?? 'Camagru';
$user = View::user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= View::e($title) ?> - Camagru</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/css/style.css">
    <meta name="csrf-token" content="<?= View::csrfToken() ?>">
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
    <?php require dirname(__DIR__) . '/partials/header.php'; ?>

    <main class="flex-1 container mx-auto px-4 py-8">
        <?php
        // Flash messages
        $success = Session::getFlash('success');
        $error = Session::getFlash('error');

        if ($success): ?>
            <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded" role="alert">
                <?= View::e($success) ?>
            </div>
        <?php endif;

        if ($error): ?>
            <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded" role="alert">
                <?= View::e($error) ?>
            </div>
        <?php endif; ?>

        <?= $content ?>
    </main>

    <?php require dirname(__DIR__) . '/partials/footer.php'; ?>

    <script src="/js/app.js"></script>
</body>
</html>
