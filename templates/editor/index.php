<?php

use Core\View;

$overlays = $overlays ?? [];
$userImages = $userImages ?? ['data' => []];
?>
<div class="max-w-6xl mx-auto">
    <h1 class="text-3xl font-bold mb-8">Photo Editor</h1>

    <div class="grid lg:grid-cols-2 gap-8">
        <!-- Camera / Preview Section -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold mb-4">Capture</h2>

            <!-- Mode selection -->
            <div class="flex space-x-4 mb-4">
                <button
                    id="webcam-mode-btn"
                    class="px-4 py-2 rounded-lg bg-blue-600 text-white transition"
                >
                    Webcam
                </button>
                <button
                    id="upload-mode-btn"
                    class="px-4 py-2 rounded-lg bg-gray-200 text-gray-700 hover:bg-gray-300 transition"
                >
                    Upload
                </button>
            </div>

            <!-- Webcam section -->
            <div id="webcam-section">
                <div class="relative bg-black rounded-lg overflow-hidden aspect-[4/3]">
                    <video id="webcam-video" class="w-full h-full object-cover" autoplay playsinline></video>
                    <canvas id="preview-canvas" class="absolute top-0 left-0 w-full h-full pointer-events-none"></canvas>

                    <!-- Webcam error message -->
                    <div id="webcam-error" class="hidden absolute inset-0 flex items-center justify-center bg-gray-800 text-white text-center p-4">
                        <div>
                            <p class="mb-2">Unable to access webcam</p>
                            <p class="text-sm text-gray-400">Please allow camera access or use the upload option</p>
                        </div>
                    </div>
                </div>

                <div class="mt-4 flex justify-center">
                    <button
                        id="capture-btn"
                        class="bg-red-500 hover:bg-red-600 text-white px-8 py-3 rounded-full font-semibold transition disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        Capture
                    </button>
                </div>
            </div>

            <!-- Upload section (hidden by default) -->
            <div id="upload-section" class="hidden">
                <div class="relative bg-gray-100 rounded-lg overflow-hidden aspect-[4/3] flex items-center justify-center">
                    <img id="upload-preview" class="max-w-full max-h-full object-contain hidden">
                    <canvas id="upload-preview-canvas" class="absolute top-0 left-0 w-full h-full pointer-events-none hidden"></canvas>

                    <div id="upload-placeholder" class="text-center">
                        <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <p class="text-gray-500">Click to select an image or drag & drop</p>
                    </div>

                    <input
                        type="file"
                        id="file-input"
                        accept="image/jpeg,image/png,image/gif"
                        class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                    >
                </div>

                <div class="mt-4 flex justify-center">
                    <button
                        id="upload-btn"
                        class="bg-green-500 hover:bg-green-600 text-white px-8 py-3 rounded-lg font-semibold transition disabled:opacity-50 disabled:cursor-not-allowed"
                        disabled
                    >
                        Save Photo
                    </button>
                </div>
            </div>
        </div>

        <!-- Overlays Section -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold mb-4">Choose Overlay</h2>

            <?php if (empty($overlays)): ?>
                <p class="text-gray-500">No overlays available</p>
            <?php else: ?>
                <div class="grid grid-cols-3 gap-4 max-h-96 overflow-y-auto">
                    <!-- No overlay option -->
                    <button
                        class="overlay-option border-2 border-blue-500 rounded-lg p-2 transition hover:border-blue-600"
                        data-overlay=""
                    >
                        <div class="aspect-square bg-gray-100 rounded flex items-center justify-center">
                            <span class="text-gray-500 text-sm">None</span>
                        </div>
                    </button>

                    <?php foreach ($overlays as $overlay): ?>
                        <button
                            class="overlay-option border-2 border-transparent rounded-lg p-2 transition hover:border-blue-400"
                            data-overlay="<?= View::e($overlay['name']) ?>"
                        >
                            <img
                                src="<?= View::e($overlay['url']) ?>"
                                alt="<?= View::e($overlay['name']) ?>"
                                class="aspect-square object-contain bg-gray-100 rounded"
                            >
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- User's Images Section -->
    <div class="mt-8 bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold mb-4">My Photos</h2>

        <div id="my-images-container" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">
            <?php if (empty($userImages['data'])): ?>
                <p class="text-gray-500 col-span-full">No photos yet. Capture your first one!</p>
            <?php else: ?>
                <?php foreach ($userImages['data'] as $img): ?>
                    <div class="relative group" data-image-id="<?= $img['id'] ?>">
                        <img
                            src="/uploads/images/<?= View::e($img['filename']) ?>"
                            alt="My photo"
                            class="w-full aspect-square object-cover rounded-lg"
                        >
                        <button
                            class="delete-image-btn absolute top-2 right-2 bg-red-500 text-white p-2 rounded-full opacity-0 group-hover:opacity-100 transition"
                            data-image-id="<?= $img['id'] ?>"
                            title="Delete"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                        <div class="absolute bottom-2 left-2 flex space-x-2 text-white text-sm">
                            <span class="bg-black bg-opacity-50 px-2 py-1 rounded">
                                <span class="likes-count"><?= $img['likes_count'] ?></span> likes
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Status message -->
<div id="status-message" class="fixed bottom-4 right-4 hidden">
    <div class="bg-gray-800 text-white px-6 py-3 rounded-lg shadow-lg"></div>
</div>

<script src="/js/webcam.js"></script>
<script src="/js/editor.js"></script>
