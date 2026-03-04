<?php

use Core\View;

$errors = View::errors();
$token = $token ?? '';
?>
<div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-8">
    <h1 class="text-2xl font-bold text-center mb-6">Reset Password</h1>

    <form id="reset-password-form" action="/reset-password" method="POST" class="space-y-4">
        <?= View::csrf() ?>
        <input type="hidden" name="token" value="<?= View::e($token) ?>">

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                New Password
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
                Confirm New Password
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
            Reset Password
        </button>
    </form>
</div>
