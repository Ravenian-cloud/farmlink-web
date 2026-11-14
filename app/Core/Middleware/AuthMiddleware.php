<?php

namespace App\Core\Middleware;

class AuthMiddleware
{
    public function handle(): void
    {
        if (empty($_SESSION['user_id'])) {
            $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
            if (str_contains($accept, 'application/json')) {
                http_response_code(401);
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Unauthenticated.']);
                exit;
            }

            $config = require dirname(__DIR__, 2) . '/Config/app.php';
            header('Location: ' . $config['url'] . '/login');
            exit;
        }
    }
}
