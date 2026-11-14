<?php

return [
    'name'            => $_ENV['APP_NAME'] ?? 'FarmLink',
    'env'             => $_ENV['APP_ENV'] ?? 'local',
    'debug'           => filter_var($_ENV['APP_DEBUG'] ?? true, FILTER_VALIDATE_BOOLEAN),
    'url'             => rtrim($_ENV['APP_URL'] ?? 'http://localhost/farmlink-web/public', '/'),
    'timezone'        => $_ENV['APP_TIMEZONE'] ?? 'Africa/Nairobi',
    'commission_rate' => (float) ($_ENV['PLATFORM_COMMISSION_RATE'] ?? 0.05),
];
