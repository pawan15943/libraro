<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\DashboardController;
use App\Models\Branch;
use App\Models\Library;
use Illuminate\Http\Request;

$library = Library::where('status', 1)->first();
if (!$library) {
    echo "No active library found.\n";
    exit;
}

Auth::guard('library')->setUser($library);
session(['library_id' => $library->id]);
$branch = Branch::where('library_id', $library->id)->first();
if ($branch) {
    session(['branch_id' => $branch->id]);
    $library->update(['current_branch' => $branch->id]);
}

echo "=== BENCHMARKING TOTAL DASHBOARD RENDER SPEED ===\n\n";

$t1 = microtime(true);
$controller = app(DashboardController::class);
$response = $controller->libraryDashboard(new Request());
$t2 = microtime(true);

$timeMs = round(($t2 - $t1) * 1000, 2);
echo "Total Dashboard Controller Execution Time: {$timeMs} ms\n";
