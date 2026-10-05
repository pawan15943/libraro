<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$libraries = App\Models\Library::take(5)->get(['id', 'library_name', 'library_type']);
echo "Libraries:\n";
foreach ($libraries as $lib) {
    echo "ID: {$lib->id}, Name: {$lib->library_name}, Type: {$lib->library_type}\n";
    $sub = App\Models\Subscription::find($lib->library_type);
    if ($sub) {
        $mp = $sub->monthly_price ?? 'N/A';
        $yp = $sub->yearly_price ?? 'N/A';
        echo "  Plan: {$sub->name}, Monthly: {$mp}, Yearly: {$yp}\n";
    }
    $lastTx = App\Models\LibraryTransaction::withoutGlobalScopes()->where('library_id', $lib->id)->where('is_paid', 1)->orderBy('id', 'desc')->first();
    if ($lastTx) {
        echo "  LastTx: amount={$lastTx->amount}, month={$lastTx->month}, subscription={$lastTx->subscription}\n";
    } else {
        echo "  LastTx: None\n";
    }
}
