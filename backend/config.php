<?php
// Default config; replace values or use environment variables on Hostinger.
return [
    'db' => [
        'host' => getenv('FBA_DB_HOST') ?: 'localhost',
    'name' => getenv('FBA_DB_NAME') ?: 'u289267434_fbabrasilbanco',
    'user' => getenv('FBA_DB_USER') ?: 'u289267434_fbabrasilbanco',
    'pass' => getenv('FBA_DB_PASS') ?: 'Fbabrasil@2025',
        'charset' => 'utf8mb4',
    ],
    'mail' => [
    'from' => getenv('FBA_MAIL_FROM') ?: 'no-reply@blue-turkey-597782.hostingersite.com',
    'verify_base_url' => getenv('FBA_VERIFY_BASE_URL') ?: 'https://blue-turkey-597782.hostingersite.com/api/verify.php?token=',
    'reset_base_url' => getenv('FBA_RESET_BASE_URL') ?: 'https://blue-turkey-597782.hostingersite.com/reset-password.php?token=',
    ],
    'app' => [
        'cap_min' => 618,
        'cap_max' => 648,
        'debug_reset_link' => getenv('FBA_DEBUG_RESET_LINK') ?: false,
    ],
];
