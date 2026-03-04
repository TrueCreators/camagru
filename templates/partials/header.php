<?php

use Core\View;
use Core\Session;

$user = View::user();
?>
<header class="bg-white shadow-md">
    <nav class="container mx-auto px-4 py-4">
        <div class="flex items-center justify-between">
            <a href="/" class="text-2xl font-bold text-blue-600">Camagru</a>

            <div class="hidden md:flex items-center space-x-6">
                <a href="/gallery" class="text-gray-600 hover:text-blue-600 transition">Gallery</a>

                <?php if (View::isLoggedIn()): ?>
                    <a href="/editor" class="text-gray-600 hover:text-blue-600 transition">Editor</a>
                    <a href="/profile" class="text-gray-600 hover:text-blue-600 transition">Profile</a>
                    <span class="text-gray-500">Hello, <?= View::e($user['username'] ?? '') ?></span>
                    <form action="/logout" method="POST" class="inline">
                        <?= View::csrf() ?>
                        <button type="submit" class="text-red-600 hover:text-red-800 transition">Logout</button>
                    </form>
                <?php else: ?>
                    <a href="/login" class="text-gray-600 hover:text-blue-600 transition">Login</a>
                    <a href="/register" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">
                        Register
                    </a>
                <?php endif; ?>
            </div>

            <!-- Mobile menu button -->
            <button id="mobile-menu-btn" class="md:hidden text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
        </div>

        <!-- Mobile menu -->
        <div id="mobile-menu" class="hidden md:hidden mt-4 space-y-4">
            <a href="/gallery" class="block text-gray-600 hover:text-blue-600 transition">Gallery</a>

            <?php if (View::isLoggedIn()): ?>
                <a href="/editor" class="block text-gray-600 hover:text-blue-600 transition">Editor</a>
                <a href="/profile" class="block text-gray-600 hover:text-blue-600 transition">Profile</a>
                <form action="/logout" method="POST">
                    <?= View::csrf() ?>
                    <button type="submit" class="text-red-600 hover:text-red-800 transition">Logout</button>
                </form>
            <?php else: ?>
                <a href="/login" class="block text-gray-600 hover:text-blue-600 transition">Login</a>
                <a href="/register" class="block text-gray-600 hover:text-blue-600 transition">Register</a>
            <?php endif; ?>
        </div>
    </nav>
</header>
