<?php

namespace App\Core;

use RuntimeException;

abstract class Controller
{
    protected function view(string $viewPath, array $data = [], ?string $layout = 'main'): void
    {
        extract($data, EXTR_SKIP);

        $viewFile = dirname(__DIR__, 2) . '/resources/views/' . str_replace('.', '/', $viewPath) . '.php';

        if (!file_exists($viewFile)) {
            throw new RuntimeException("View not found: {$viewPath}");
        }

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        if ($layout !== null) {
            $layoutFile = dirname(__DIR__, 2) . '/resources/views/layouts/' . $layout . '.php';
            if (!file_exists($layoutFile)) {
                throw new RuntimeException("Layout not found: {$layout}");
            }
            require $layoutFile;
        } else {
            echo $content;
        }
    }

    protected function json(array $payload, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function redirect(string $path): void
    {
        $config = require dirname(__DIR__) . '/Config/app.php';
        $target = str_starts_with($path, 'http') ? $path : $config['url'] . '/' . ltrim($path, '/');
        header("Location: {$target}");
        exit;
    }

    protected function verifyCsrf(): void
    {
        $token = $_POST['_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
            if ($this->isJsonRequest()) {
                $this->json(['status' => 'error', 'message' => 'Invalid CSRF token.'], 419);
            }
            http_response_code(419);
            exit('419 Page Expired: Invalid CSRF Token.');
        }
    }

    protected function isJsonRequest(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        return str_contains($accept, 'application/json') || str_contains($contentType, 'application/json');
    }
}
