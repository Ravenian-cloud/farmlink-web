<?php

namespace App\Core\Middleware;

class RoleMiddleware
{
    public function handle(string $allowedRoles): void
    {
        if (empty($_SESSION['user_id'])) {
            $config = require dirname(__DIR__, 2) . '/Config/app.php';
            header('Location: ' . $config['url'] . '/login');
            exit;
        }

        $roles = explode('|', $allowedRoles);
        $userRole = $_SESSION['user_role'] ?? '';

        if (!in_array($userRole, $roles, true)) {
            http_response_code(403);
            exit('403 Forbidden: You do not have permission to access this FarmLink module.');
        }
    }
}
