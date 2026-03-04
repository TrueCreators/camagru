<?php

use Core\View;

$errors = View::errors();
$user = $user ?? [];
?>
<div class="max-w-2xl mx-auto">
    <h1 class="text-3xl font-bold mb-8">Profile Settings</h1>

    <!-- Profile Update Form -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-xl font-semibold mb-4">Account Information</h2>

        <form id="profile-form" action="/profile" method="POST" class="space-y-4">
            <?= View::csrf() ?>

            <div>
                <label for="username" class="block text-sm font-medium text-gray-700 mb-1">
                    Username
                </label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    value="<?= View::e(View::old('username') ?: $user['username'] ?? '') ?>"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 <?= isset($errors['username']) ? 'border-red-500' : 'border-gray-300' ?>"
                    required
                    minlength="3"
                    maxlength="50"
                >
                <?php if (isset($errors['username'])): ?>
                    <p class="mt-1 text-sm text-red-600"><?= View::e($errors['username'][0]) ?></p>
                <?php endif; ?>
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                    Email
                </label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?= View::e(View::old('email') ?: $user['email'] ?? '') ?>"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 <?= isset($errors['email']) ? 'border-red-500' : 'border-gray-300' ?>"
                    required
                >
                <?php if (isset($errors['email'])): ?>
                    <p class="mt-1 text-sm text-red-600"><?= View::e($errors['email'][0]) ?></p>
                <?php endif; ?>
            </div>

            <div class="flex items-center">
                <input
                    type="checkbox"
                    id="notify_comments"
                    name="notify_comments"
                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                    <?= ($user['notify_comments'] ?? true) ? 'checked' : '' ?>
                >
                <label for="notify_comments" class="ml-2 block text-sm text-gray-700">
                    Receive email notifications when someone comments on my photos
                </label>
            </div>

            <button
                type="submit"
                class="bg-blue-600 text-white py-2 px-6 rounded-lg hover:bg-blue-700 transition font-semibold"
            >
                Update Profile
            </button>
        </form>
    </div>

    <!-- Password Change Form -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold mb-4">Change Password</h2>

        <form id="password-form" action="/profile/password" method="POST" class="space-y-4">
            <?= View::csrf() ?>

            <div>
                <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">
                    Current Password
                </label>
                <input
                    type="password"
                    id="current_password"
                    name="current_password"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 <?= isset($errors['current_password']) ? 'border-red-500' : 'border-gray-300' ?>"
                    required
                >
                <?php if (isset($errors['current_password'])): ?>
                    <p class="mt-1 text-sm text-red-600"><?= View::e($errors['current_password'][0]) ?></p>
                <?php endif; ?>
            </div>

            <div>
                <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">
                    New Password
                </label>
                <input
                    type="password"
                    id="new_password"
                    name="new_password"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 <?= isset($errors['new_password']) ? 'border-red-500' : 'border-gray-300' ?>"
                    required
                    minlength="8"
                >
                <?php if (isset($errors['new_password'])): ?>
                    <p class="mt-1 text-sm text-red-600"><?= View::e($errors['new_password'][0]) ?></p>
                <?php endif; ?>
                <p class="mt-1 text-xs text-gray-500">At least 8 characters with uppercase, lowercase, and number</p>
            </div>

            <div>
                <label for="new_password_confirm" class="block text-sm font-medium text-gray-700 mb-1">
                    Confirm New Password
                </label>
                <input
                    type="password"
                    id="new_password_confirm"
                    name="new_password_confirm"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    required
                >
            </div>

            <button
                type="submit"
                class="bg-green-600 text-white py-2 px-6 rounded-lg hover:bg-green-700 transition font-semibold"
            >
                Change Password
            </button>
        </form>
    </div>
</div>
