<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Subscription;

echo "=== SUBSCRIPTIONS TABLE ===\n";
$subs = Subscription::all();
foreach ($subs as $s) {
    echo "ID: {$s->id} | Name: {$s->name} | Price: {$s->price} | Status: {$s->status}\n";
}
