<?php

use Core\View;
use Core\Session;

$images = $images ?? ['data' => [], 'total_pages' => 0, 'current_page' => 1, 'has_more' => false];
$isLoggedIn = View::isLoggedIn();
?>
<div class="max-w-4xl mx-auto">
    <h1 class="text-3xl font-bold mb-8">Gallery</h1>

    <?php if ($isLoggedIn): ?>
        <div class="mb-6">
            <a href="/editor" class="inline-block bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">
                Create New Photo
            </a>
        </div>
    <?php endif; ?>

    <div id="gallery-container" class="space-y-8">
        <?php if (empty($images['data'])): ?>
            <div class="text-center py-12 bg-white rounded-lg shadow">
                <p class="text-gray-500 text-lg">No images yet. Be the first to share!</p>
                <?php if (!$isLoggedIn): ?>
                    <p class="mt-4">
                        <a href="/register" class="text-blue-600 hover:underline">Register</a>
                        to start creating photos.
                    </p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <?php foreach ($images['data'] as $image): ?>
                <?php include dirname(__DIR__) . '/gallery/_image-card.php'; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Индикатор загрузки пагинации / бесконечной прокрутки -->
    <?php if ($images['has_more']): ?>
        <div id="load-more-container" class="mt-8 text-center">
            <button
                id="load-more-btn"
                data-page="<?= $images['current_page'] + 1 ?>"
                class="bg-gray-200 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-300 transition"
            >
                Load More
            </button>
            <div id="loading-spinner" class="hidden">
                <svg class="animate-spin h-8 w-8 mx-auto text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="/js/gallery.js"></script>
