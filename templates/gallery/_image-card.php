<?php

use Core\View;

$isLoggedIn = View::isLoggedIn();
$currentUser = View::user();
$userLiked = isset($image['user_liked']) && $image['user_liked'] > 0;
?>
<article class="bg-white rounded-lg shadow-md overflow-hidden image-card" data-image-id="<?= $image['id'] ?>">
    <!-- Изображение -->
    <div class="relative">
        <img
            src="/uploads/images/<?= View::e($image['filename']) ?>"
            alt="Photo by <?= View::e($image['username']) ?>"
            class="w-full h-auto"
            loading="lazy"
        >
    </div>

    <!-- Содержимое -->
    <div class="p-4">
        <!-- Автор и дата -->
        <div class="flex items-center justify-between mb-3">
            <span class="font-semibold text-gray-800"><?= View::e($image['username']) ?></span>
            <span class="text-sm text-gray-500"><?= View::timeAgo($image['created_at']) ?></span>
        </div>

        <!-- Действия -->
        <div class="flex items-center space-x-6">
            <!-- Кнопка лайка -->
            <button
                class="like-btn flex items-center space-x-2 <?= $isLoggedIn ? 'hover:text-red-500' : 'cursor-default' ?> transition <?= $userLiked ? 'text-red-500' : 'text-gray-600' ?>"
                data-image-id="<?= $image['id'] ?>"
                <?= !$isLoggedIn ? 'disabled title="Login to like"' : '' ?>
            >
                <svg class="w-6 h-6 <?= $userLiked ? 'fill-current' : '' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                </svg>
                <span class="likes-count"><?= $image['likes_count'] ?></span>
            </button>

            <!-- Переключатель комментариев -->
            <button
                class="comments-toggle flex items-center space-x-2 text-gray-600 hover:text-blue-500 transition"
                data-image-id="<?= $image['id'] ?>"
            >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                </svg>
                <span class="comments-count"><?= $image['comments_count'] ?></span>
            </button>

            <!-- Кнопка "Поделиться" -->
            <button
                class="share-btn flex items-center space-x-2 text-gray-600 hover:text-green-500 transition"
                data-image-id="<?= $image['id'] ?>"
                data-url="<?= View::url('/gallery#' . $image['id']) ?>"
            >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path>
                </svg>
            </button>
        </div>

        <!-- Секция комментариев (по умолчанию скрыта) -->
        <div class="comments-section hidden mt-4 pt-4 border-t">
            <div class="comments-list space-y-3 max-h-60 overflow-y-auto">
                <!-- Комментарии будут загружены через AJAX -->
            </div>

            <?php if ($isLoggedIn): ?>
                <form class="comment-form mt-4 flex space-x-2" data-image-id="<?= $image['id'] ?>">
                    <input
                        type="text"
                        name="content"
                        placeholder="Add a comment..."
                        class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        required
                        maxlength="1000"
                    >
                    <button
                        type="submit"
                        class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition"
                    >
                        Post
                    </button>
                </form>
            <?php else: ?>
                <p class="mt-4 text-gray-500 text-sm">
                    <a href="/login" class="text-blue-600 hover:underline">Login</a> to comment
                </p>
            <?php endif; ?>
        </div>
    </div>
</article>
