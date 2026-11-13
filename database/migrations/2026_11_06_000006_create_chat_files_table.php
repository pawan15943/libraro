<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Encrypted attachment blobs of the chat. The server only sees size and uploader, never name, type or content. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_files', function (Blueprint $t) {
            $t->id();
            $t->uuid('token')->unique();
            $t->unsignedBigInteger('thread_id')->index();
            $t->string('uploader_type', 10);
            $t->unsignedInteger('size');
            $t->string('path');
            $t->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_files');
    }
};
