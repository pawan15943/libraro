<?php

namespace App\Listeners;

use App\Events\LibraryRegistered;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendLibraryVerificationEmail
{
    public function handle(LibraryRegistered $event)
    {
       $library = $event->library;

    try {

        $controller = app(\App\Http\Controllers\LibraryController::class);
        $controller->sendVerificationEmail($library);

    } catch (\Throwable $e) {

        \Log::error('Verification email failed', [
            'library_id' => $library->id,
            'message' => $e->getMessage()
        ]);

        // DO NOT throw
        return;
    }
    }
}
