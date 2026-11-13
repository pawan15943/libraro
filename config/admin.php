<?php

return [
    // Admin login URL. Set ADMIN_LOGIN_PATH in .env to change it (old /administrator/login then 404s).
    'login_path' => trim((string) env('ADMIN_LOGIN_PATH', 'administrator/login'), '/') ?: 'administrator/login',
];
