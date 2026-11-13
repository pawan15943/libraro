<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** End-to-end encrypted chat between a learner and the library owner. The server only stores ciphertext. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_keys', function (Blueprint $t) {
            $t->id();
            $t->string('owner_type', 10);                 // learner | library
            $t->unsignedBigInteger('owner_id');
            $t->json('public_jwk');
            $t->string('fingerprint', 64);
            $t->timestamp('revoked_at')->nullable();
            $t->timestamps();
            $t->index(['owner_type', 'owner_id']);
        });

        Schema::create('chat_threads', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('learner_id')->unique();
            $t->unsignedBigInteger('library_id')->index();
            $t->timestamp('last_message_at')->nullable();
            $t->timestamps();
        });

        Schema::create('chat_messages', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('thread_id')->index();
            $t->string('sender_type', 10);                // learner | library
            $t->text('ciphertext');
            $t->string('iv', 32);
            $t->unsignedBigInteger('sender_key_id');
            $t->unsignedBigInteger('recipient_key_id');
            $t->timestamp('read_at')->nullable();
            $t->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        foreach (['chat_messages', 'chat_threads', 'chat_keys'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
