<?php

use Core\View;

$errors = View::errors();
?>
<div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-8">
    <h1 class="text-2xl font-bold text-center mb-6">Forgot Password</h1>

    <p class="text-gray-600 text-center mb-6">
        Enter your email address and we'll send you a link to reset your password.
    </p>

    <form id="forgot-password-form" action="/forgot-password" method="POST" class="space-y-4">
        <?= View::csrf() ?>

        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                Email
            </label>
            <input
                type="email"
                id="email"
                name="email"
                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 <?= isset($errors['email']) ? 'border-red-500' : 'border-gray-300' ?>"
                required
            >
            <?php if (isset($errors['email'])): ?>
                <p class="mt-1 text-sm text-red-600"><?= View::e($errors['email'][0]) ?></p>
            <?php endif; ?>
        </div>

        <button
            type="submit"
            class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition font-semibold"
        >
            Send Reset Link
        </button>
    </form>

    <div class="mt-6 text-center">
        <p class="text-gray-600">
            Remember your password?
            <a href="/login" class="text-blue-600 hover:underline">Login</a>
        </p>
    </div>
</div>
