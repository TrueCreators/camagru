<?php

declare(strict_types=1);

namespace Core;

/**
 * Базовый контроллер
 */
abstract class Controller
{
    protected function view(string $template, array $data = []): void
    {
        $templatePath = dirname(__DIR__, 2) . '/templates/' . $template . '.php';

        if (!file_exists($templatePath)) {
            throw new \RuntimeException("Template not found: {$template}");
        }

        // Извлечение данных в переменные
        extract($data);

        // Запуск буферизации вывода
        ob_start();
        require $templatePath;
        $content = ob_get_clean();

        echo $content;
    }

    protected function render(string $template, array $data = [], string $layout = 'layouts/main'): void
    {
        $templatePath = dirname(__DIR__, 2) . '/templates/' . $template . '.php';

        if (!file_exists($templatePath)) {
            throw new \RuntimeException("Template not found: {$template}");
        }

        // Извлечение данных в переменные
        extract($data);

        // Рендер содержимого шаблона
        ob_start();
        require $templatePath;
        $content = ob_get_clean();

        // Рендер шаблона страницы с содержимым
        $layoutPath = dirname(__DIR__, 2) . '/templates/' . $layout . '.php';

        if (!file_exists($layoutPath)) {
            throw new \RuntimeException("Layout not found: {$layout}");
        }

        require $layoutPath;
    }

    protected function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    protected function redirect(string $url, int $status = 302): void
    {
        http_response_code($status);
        header('Location: ' . $url);
        exit;
    }

    protected function getRequestBody(): array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (str_contains($contentType, 'application/json')) {
            $body = file_get_contents('php://input');
            return json_decode($body, true) ?? [];
        }

        return $_POST;
    }

    protected function requireAuth(): void
    {
        if (!Session::isLoggedIn()) {
            if ($this->isAjax()) {
                $this->json(['error' => 'Unauthorized'], 401);
            }
            $this->redirect('/login');
        }
    }

    protected function requireGuest(): void
    {
        if (Session::isLoggedIn()) {
            $this->redirect('/');
        }
    }

    protected function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    protected function validateCsrf(): bool
    {
        if (!CSRF::validateRequest()) {
            if ($this->isAjax()) {
                $this->json(['error' => 'Invalid CSRF token'], 403);
            }
            Session::flash('error', 'Invalid form submission. Please try again.');
            $this->redirect($_SERVER['HTTP_REFERER'] ?? '/');
        }
        return true;
    }

    protected function getInput(string $key, mixed $default = null): mixed
    {
        $body = $this->getRequestBody();
        return $body[$key] ?? $_GET[$key] ?? $default;
    }

    protected function validate(array $data, array $rules): Validator
    {
        $validator = new Validator($data);

        foreach ($rules as $field => $fieldRules) {
            foreach ($fieldRules as $rule) {
                if (is_string($rule)) {
                    $validator->$rule($field);
                } elseif (is_array($rule)) {
                    $method = array_shift($rule);
                    $validator->$method($field, ...$rule);
                }
            }
        }

        return $validator;
    }
}
