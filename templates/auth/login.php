<?php

use Core\View;

$errors = View::errors();
?>
<div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-8">
    <h1 class="text-2xl font-bold text-center mb-6">Login</h1>

    <form id="login-form" action="/login" method="POST" class="space-y-4">
        <?= View::csrf() ?>

        <div>
            <label for="login" class="block text-sm font-medium text-gray-700 mb-1">
                Email or Username
            </label>
            <input
                type="text"
                id="login"
                name="login"
                value="<?= View::old('login') ?>"
                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 <?= isset($errors['login']) ? 'border-red-500' : 'border-gray-300' ?>"
                required
            >
            <?php if (isset($errors['login'])): ?>
                <p class="mt-1 text-sm text-red-600"><?= View::e($errors['login'][0]) ?></p>
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
            >
            <?php if (isset($errors['password'])): ?>
                <p class="mt-1 text-sm text-red-600"><?= View::e($errors['password'][0]) ?></p>
            <?php endif; ?>
        </div>

        <button
            type="submit"
            class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition font-semibold"
        >
            Login
        </button>
    </form>

    <div class="mt-6 text-center space-y-2">
        <p class="text-gray-600">
            <a href="/forgot-password" class="text-blue-600 hover:underline">Forgot your password?</a>
        </p>
        <p class="text-gray-600">
            Don't have an account?
            <a href="/register" class="text-blue-600 hover:underline">Register</a>
        </p>
    </div>
</div>
