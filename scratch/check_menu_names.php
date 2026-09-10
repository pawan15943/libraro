<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Menu;

echo "=== ALL LIBRARY MENUS ===\n";
$menus = Menu::where('status', 1)
    ->where(function ($query) {
        $query->where('guard', 'library')->orWhereNull('guard');
    })
    ->whereNull('parent_id')
    ->orderBy('order')
    ->get(['id', 'name', 'url', 'guard']);

foreach ($menus as $m) {
    echo "ID: {$m->id} | Name: {$m->name} | URL: {$m->url} | Guard: {$m->guard}\n";
}
