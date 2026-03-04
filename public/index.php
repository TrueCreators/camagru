<?php

declare(strict_types=1);

/**
 * Camagru - Точка входа
 */

// Настройка отчётов об ошибках
error_reporting(E_ALL);
ini_set('display_errors', '0');

// Автозагрузчик
spl_autoload_register(function (string $class) {
    $paths = [
        dirname(__DIR__) . '/src/',
        dirname(__DIR__) . '/config/',
    ];

    $classPath = str_replace('\\', '/', $class) . '.php';

    foreach ($paths as $path) {
        $file = $path . $classPath;
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Загрузка конфигурации
require_once dirname(__DIR__) . '/config/config.php';
Config::load();

// Включение отображения ошибок в режиме отладки
if (Config::isDebug()) {
    ini_set('display_errors', '1');
}

// Инициализация сессии
use Core\Session;
use Core\Router;
use Core\CSRF;

Session::start();

// Проверка существования директории загрузок
use Services\ImageService;
ImageService::ensureUploadDir();

// Создание роутера
$router = new Router();

// Определения промежуточных обработчиков
$router->addMiddleware('auth', function () {
    if (!Session::isLoggedIn()) {
        if (str_starts_with($_SERVER['REQUEST_URI'], '/api/')) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized']);
            return false;
        }
        header('Location: /login');
        return false;
    }
    return true;
});

$router->addMiddleware('guest', function () {
    if (Session::isLoggedIn()) {
        if (str_starts_with($_SERVER['REQUEST_URI'], '/api/')) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Already authenticated']);
            return false;
        }
        header('Location: /gallery');
        return false;
    }
    return true;
});

$router->addMiddleware('csrf', function () {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'DELETE') {
        if (!CSRF::validateRequest()) {
            if (str_starts_with($_SERVER['REQUEST_URI'], '/api/')) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Invalid CSRF token']);
                return false;
            }
            Session::flash('error', 'Invalid form submission. Please try again.');
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
            return false;
        }
    }
    return true;
});

// Импорт контроллеров
use Controllers\HomeController;
use Controllers\AuthController;
use Controllers\UserController;
use Controllers\GalleryController;
use Controllers\EditorController;
use Controllers\ApiController;

// Веб-маршруты
$router->get('/', [HomeController::class, 'index']);

// Маршруты аутентификации
$router->get('/login', [AuthController::class, 'showLogin'], ['guest']);
$router->post('/login', [AuthController::class, 'login'], ['guest', 'csrf']);
$router->get('/register', [AuthController::class, 'showRegister'], ['guest']);
$router->post('/register', [AuthController::class, 'register'], ['guest', 'csrf']);
$router->post('/logout', [AuthController::class, 'logout'], ['auth', 'csrf']);
$router->get('/verify', [AuthController::class, 'verify']);
$router->get('/forgot-password', [AuthController::class, 'showForgotPassword'], ['guest']);
$router->post('/forgot-password', [AuthController::class, 'forgotPassword'], ['guest', 'csrf']);
$router->get('/reset-password', [AuthController::class, 'showResetPassword'], ['guest']);
$router->post('/reset-password', [AuthController::class, 'resetPassword'], ['guest', 'csrf']);

// Маршруты пользователя
$router->get('/profile', [UserController::class, 'showProfile'], ['auth']);
$router->post('/profile', [UserController::class, 'updateProfile'], ['auth', 'csrf']);
$router->post('/profile/password', [UserController::class, 'changePassword'], ['auth', 'csrf']);

// Маршруты галереи
$router->get('/gallery', [GalleryController::class, 'index']);

// Маршруты редактора
$router->get('/editor', [EditorController::class, 'index'], ['auth']);

// API-маршруты
$router->post('/api/auth/login', [ApiController::class, 'login'], ['csrf']);
$router->post('/api/auth/register', [ApiController::class, 'register'], ['csrf']);
$router->post('/api/auth/logout', [ApiController::class, 'logout'], ['csrf']);
$router->post('/api/auth/forgot-password', [ApiController::class, 'forgotPassword'], ['csrf']);
$router->post('/api/auth/reset-password', [ApiController::class, 'resetPassword'], ['csrf']);

$router->get('/api/user/profile', [ApiController::class, 'getProfile'], ['auth']);
$router->post('/api/user/update', [ApiController::class, 'updateProfile'], ['auth', 'csrf']);

$router->get('/api/gallery', [ApiController::class, 'getGallery']);
$router->post('/api/gallery/like/{id}', [ApiController::class, 'toggleLike'], ['auth', 'csrf']);
$router->get('/api/gallery/comments/{id}', [ApiController::class, 'getComments']);
$router->post('/api/gallery/comment/{id}', [ApiController::class, 'addComment'], ['auth', 'csrf']);

$router->post('/api/editor/capture', [ApiController::class, 'captureImage'], ['auth', 'csrf']);
$router->post('/api/editor/upload', [ApiController::class, 'uploadImage'], ['auth', 'csrf']);
$router->delete('/api/editor/image/{id}', [ApiController::class, 'deleteImage'], ['auth', 'csrf']);
$router->get('/api/editor/my-images', [ApiController::class, 'getUserImages'], ['auth']);

// Обработка запроса
try {
    $router->dispatch();
} catch (Throwable $e) {
    if (Config::isDebug()) {
        http_response_code(500);
        echo '<h1>Error</h1>';
        echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    } else {
        http_response_code(500);
        if (str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/api/')) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Internal Server Error']);
        } else {
            echo '<!DOCTYPE html><html><head><title>Error</title></head>';
            echo '<body><h1>500 - Internal Server Error</h1></body></html>';
        }
    }
}
