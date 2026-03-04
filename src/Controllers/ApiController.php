<?php

declare(strict_types=1);

namespace Controllers;

use Core\Controller;
use Core\Session;
use Core\Validator;
use Core\CSRF;
use Models\Image;
use Models\Comment;
use Models\Like;
use Models\User;
use Services\ImageService;
use Services\AuthService;
use Services\EmailService;

class ApiController extends Controller
{
    // Auth endpoints

    public function login(): void
    {
        $this->validateCsrf();

        $data = $this->getRequestBody();

        $validator = new Validator($data);
        $validator
            ->required('login', 'Email or username is required')
            ->required('password', 'Password is required');

        if ($validator->fails()) {
            $this->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $result = AuthService::login($data['login'], $data['password']);

        if ($result['success']) {
            $this->json([
                'success' => true,
                'user' => [
                    'id' => $result['user']['id'],
                    'username' => $result['user']['username'],
                    'email' => $result['user']['email']
                ],
                'csrf_token' => CSRF::getToken()
            ]);
        }

        $this->json(['success' => false, 'error' => $result['error']], 401);
    }

    public function register(): void
    {
        $this->validateCsrf();

        $data = $this->getRequestBody();

        $validator = new Validator($data);
        $validator
            ->required('username')
            ->minLength('username', 3)
            ->maxLength('username', 50)
            ->username('username')
            ->required('email')
            ->email('email')
            ->required('password')
            ->minLength('password', 8)
            ->password('password')
            ->confirmed('password', 'password_confirm');

        if ($validator->fails()) {
            $this->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $result = AuthService::register($data['username'], $data['email'], $data['password']);

        if ($result['success']) {
            $this->json(['success' => true, 'message' => $result['message']]);
        }

        $this->json(['success' => false, 'error' => $result['error']], 400);
    }

    public function logout(): void
    {
        $this->validateCsrf();
        AuthService::logout();
        $this->json(['success' => true]);
    }

    public function forgotPassword(): void
    {
        $this->validateCsrf();

        $data = $this->getRequestBody();

        $validator = new Validator($data);
        $validator->required('email')->email('email');

        if ($validator->fails()) {
            $this->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $result = AuthService::requestPasswordReset($data['email']);
        $this->json(['success' => true, 'message' => $result['message']]);
    }

    public function resetPassword(): void
    {
        $this->validateCsrf();

        $data = $this->getRequestBody();

        $validator = new Validator($data);
        $validator
            ->required('token')
            ->required('password')
            ->minLength('password', 8)
            ->password('password')
            ->confirmed('password', 'password_confirm');

        if ($validator->fails()) {
            $this->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $result = AuthService::resetPassword($data['token'], $data['password']);

        if ($result['success']) {
            $this->json(['success' => true, 'message' => $result['message']]);
        }

        $this->json(['success' => false, 'error' => $result['error']], 400);
    }

    // User endpoints

    public function getProfile(): void
    {
        $this->requireAuth();

        $user = AuthService::getCurrentUser();
        $this->json([
            'success' => true,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'notify_comments' => (bool)$user['notify_comments']
            ]
        ]);
    }

    public function updateProfile(): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $data = $this->getRequestBody();

        $validator = new Validator($data);
        $validator
            ->required('username')
            ->minLength('username', 3)
            ->maxLength('username', 50)
            ->username('username')
            ->required('email')
            ->email('email');

        if ($validator->fails()) {
            $this->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $updateData = [
            'username' => $data['username'],
            'email' => $data['email'],
            'notify_comments' => isset($data['notify_comments']) ? 1 : 0
        ];

        $result = AuthService::updateProfile($updateData);

        if ($result['success']) {
            $this->json([
                'success' => true,
                'user' => [
                    'id' => $result['user']['id'],
                    'username' => $result['user']['username'],
                    'email' => $result['user']['email'],
                    'notify_comments' => (bool)$result['user']['notify_comments']
                ]
            ]);
        }

        $this->json(['success' => false, 'error' => $result['error']], 400);
    }

    // Gallery endpoints

    public function getGallery(): void
    {
        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 5);

        if ($page < 1) $page = 1;
        if ($limit < 1 || $limit > 50) $limit = 5;

        $userId = Session::getUserId();
        $images = Image::getAllWithDetails($page, $limit, $userId);

        $this->json([
            'success' => true,
            'data' => $images['data'],
            'pagination' => [
                'total' => $images['total'],
                'per_page' => $images['per_page'],
                'current_page' => $images['current_page'],
                'total_pages' => $images['total_pages'],
                'has_more' => $images['has_more']
            ]
        ]);
    }

    public function toggleLike(string $id): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $imageId = (int)$id;
        $userId = Session::getUserId();

        // Check if image exists
        $image = Image::find($imageId);
        if ($image === null) {
            $this->json(['success' => false, 'error' => 'Image not found'], 404);
        }

        $result = Like::toggle($imageId, $userId);

        $this->json([
            'success' => true,
            'liked' => $result['liked'],
            'count' => $result['count']
        ]);
    }

    public function getComments(string $id): void
    {
        $imageId = (int)$id;
        $page = (int)($_GET['page'] ?? 1);

        // Check if image exists
        $image = Image::find($imageId);
        if ($image === null) {
            $this->json(['success' => false, 'error' => 'Image not found'], 404);
        }

        $comments = Comment::getByImage($imageId, $page);

        $this->json([
            'success' => true,
            'data' => $comments['data'],
            'pagination' => [
                'total' => $comments['total'],
                'per_page' => $comments['per_page'],
                'current_page' => $comments['current_page'],
                'total_pages' => $comments['total_pages'],
                'has_more' => $comments['has_more']
            ]
        ]);
    }

    public function addComment(string $id): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $imageId = (int)$id;
        $userId = Session::getUserId();
        $data = $this->getRequestBody();

        // Validate
        $validator = new Validator($data);
        $validator
            ->required('content', 'Comment cannot be empty')
            ->maxLength('content', 1000);

        if ($validator->fails()) {
            $this->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // Check if image exists
        $image = Image::find($imageId);
        if ($image === null) {
            $this->json(['success' => false, 'error' => 'Image not found'], 404);
        }

        // Add comment
        $content = trim($data['content']);
        $commentId = Comment::addComment($imageId, $userId, $content);

        // Send notification
        EmailService::notifyCommentOnImage($imageId, $userId);

        // Get user info for response
        $user = Session::getUser();

        $this->json([
            'success' => true,
            'comment' => [
                'id' => $commentId,
                'content' => htmlspecialchars($content, ENT_QUOTES, 'UTF-8'),
                'username' => $user['username'],
                'created_at' => date('Y-m-d H:i:s')
            ]
        ]);
    }

    // Editor endpoints

    public function captureImage(): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $data = $this->getRequestBody();

        if (empty($data['image'])) {
            $this->json(['success' => false, 'error' => 'No image data provided'], 400);
        }

        $overlayName = $data['overlay'] ?? '';

        // Process webcam capture
        $filename = ImageService::processWebcamCapture($data['image'], $overlayName);

        if ($filename === null) {
            $this->json(['success' => false, 'error' => 'Failed to process image'], 500);
        }

        // Save to database
        $userId = Session::getUserId();
        $imageId = Image::create([
            'user_id' => $userId,
            'filename' => $filename
        ]);

        $this->json([
            'success' => true,
            'image' => [
                'id' => $imageId,
                'filename' => $filename,
                'url' => '/uploads/images/' . $filename
            ]
        ]);
    }

    public function uploadImage(): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            $this->json(['success' => false, 'error' => 'No file uploaded or upload error'], 400);
        }

        $overlayName = $_POST['overlay'] ?? '';

        // Process uploaded file
        $filename = ImageService::processUploadedImage($_FILES['image'], $overlayName);

        if ($filename === null) {
            $this->json(['success' => false, 'error' => 'Failed to process image. Make sure it\'s a valid image file.'], 500);
        }

        // Save to database
        $userId = Session::getUserId();
        $imageId = Image::create([
            'user_id' => $userId,
            'filename' => $filename
        ]);

        $this->json([
            'success' => true,
            'image' => [
                'id' => $imageId,
                'filename' => $filename,
                'url' => '/uploads/images/' . $filename
            ]
        ]);
    }

    public function deleteImage(string $id): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $imageId = (int)$id;
        $userId = Session::getUserId();

        // Check if image belongs to user
        $image = Image::find($imageId);
        if ($image === null) {
            $this->json(['success' => false, 'error' => 'Image not found'], 404);
        }

        if ($image['user_id'] !== $userId) {
            $this->json(['success' => false, 'error' => 'Unauthorized'], 403);
        }

        // Delete file
        ImageService::deleteImage($image['filename']);

        // Delete from database
        Image::delete($imageId);

        $this->json(['success' => true]);
    }

    public function getUserImages(): void
    {
        $this->requireAuth();

        $userId = Session::getUserId();
        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 10);

        $images = Image::getUserImagesWithDetails($userId, $page, $limit);

        $this->json([
            'success' => true,
            'data' => $images['data'],
            'pagination' => [
                'total' => $images['total'],
                'per_page' => $images['per_page'],
                'current_page' => $images['current_page'],
                'total_pages' => $images['total_pages'],
                'has_more' => $images['has_more']
            ]
        ]);
    }
}
