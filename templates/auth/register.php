<?php

use Core\View;

$errors = View::errors();
?>
<div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-8">
    <h1 class="text-2xl font-bold text-center mb-6">Register</h1>

    <form id="register-form" action="/register" method="POST" class="space-y-4">
        <?= View::csrf() ?>

        <div>
            <label for="username" class="block text-sm font-medium text-gray-700 mb-1">
                Username
            </label>
            <input
                type="text"
                id="username"
                name="username"
                value="<?= View::old('username') ?>"
                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 <?= isset($errors['username']) ? 'border-red-500' : 'border-gray-300' ?>"
                required
                minlength="3"
                maxlength="50"
            >
            <?php if (isset($errors['username'])): ?>
                <p class="mt-1 text-sm text-red-600"><?= View::e($errors['username'][0]) ?></p>
            <?php endif; ?>
            <p class="mt-1 text-xs text-gray-500">Letters, numbers, and underscores only</p>
        </div>

        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                Email
            </label>
            <input
                type="email"
                id="email"
                name="email"
                value="<?= View::old('email') ?>"
                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 <?= isset($errors['email']) ? 'border-red-500' : 'border-gray-300' ?>"
                required
            >
            <?php if (isset($errors['email'])): ?>
                <p class="mt-1 text-sm text-red-600"><?= View::e($errors['email'][0]) ?></p>
            <?php endif; ?>
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                Password
            </label>
            <input
                type="password"
                id="password"
                name="password"
                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 <?= isset($errors['password']) ? 'border-red-500' : 'border-gray-300' ?>"
                required
                minlength="8"
            >
            <?php if (isset($errors['password'])): ?>
                <p class="mt-1 text-sm text-red-600"><?= View::e($errors['password'][0]) ?></p>
            <?php endif; ?>
            <p class="mt-1 text-xs text-gray-500">At least 8 characters with uppercase, lowercase, and number</p>
        </div>

        <div>
            <label for="password_confirm" class="block text-sm font-medium text-gray-700 mb-1">
                Confirm Password
            </label>
            <input
                type="password"
                id="password_confirm"
                name="password_confirm"
                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                required
            >
        </div>

        <button
            type="submit"
            class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition font-semibold"
        >
            Register
        </button>
    </form>

    <div class="mt-6 text-center">
        <p class="text-gray-600">
            Already have an account?
            <a href="/login" class="text-blue-600 hover:underline">Login</a>
        </p>
    </div>
</div>
