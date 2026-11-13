<?php

use App\Services\Ai\LibraroKnowledgeBase;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Limo AI assistant:
 *  - ai_chat_histories gets who asked, the detected intent, the question an answer belongs to,
 *    and 👍/👎 feedback (so admins can see unanswered and disliked questions);
 *  - ai_knowledge_entries holds the editable tips / FAQ (seeded from LibraroKnowledgeBase);
 *  - admin menu items for both pages.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_chat_histories', function (Blueprint $table) {
            if (! Schema::hasColumn('ai_chat_histories', 'actor')) {
                $table->string('actor', 40)->nullable()->after('branch_id')->index();
            }
            if (! Schema::hasColumn('ai_chat_histories', 'intent')) {
                $table->string('intent', 40)->nullable()->after('sender')->index();
            }
            if (! Schema::hasColumn('ai_chat_histories', 'question')) {
                $table->string('question', 500)->nullable()->after('message');
            }
            if (! Schema::hasColumn('ai_chat_histories', 'feedback')) {
                $table->tinyInteger('feedback')->nullable()->after('question');
            }
        });

        if (! Schema::hasTable('ai_knowledge_entries')) {
            Schema::create('ai_knowledge_entries', function (Blueprint $table) {
                $table->id();
                $table->text('keys');                  // one phrase per line, any language
                $table->string('title_en', 191);
                $table->string('title_hi', 191);
                $table->text('steps_en');              // one step per line
                $table->text('steps_hi');
                $table->json('links')->nullable();     // [[route, en label, hi label], ...]
                $table->boolean('status')->default(true);
                $table->timestamps();
            });

            $now = now();
            foreach (LibraroKnowledgeBase::entries() as $e) {
                DB::table('ai_knowledge_entries')->insert([
                    'keys' => implode("\n", $e['keys']),
                    'title_en' => $e['en'][0],
                    'title_hi' => $e['hi'][0],
                    'steps_en' => implode("\n", $e['en'][1]),
                    'steps_hi' => implode("\n", $e['hi'][1]),
                    'links' => isset($e['links']) ? json_encode($e['links']) : null,
                    'status' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        if (! DB::table('menus')->where('url', 'admin.limo.questions')->exists()) {
            $order = (int) DB::table('menus')->where('guard', 'web')->whereNull('parent_id')->max('order');
            $parentId = DB::table('menus')->insertGetId([
                'name' => 'Limo AI', 'url' => 'admin.limo.questions', 'icon' => null, 'parent_id' => null,
                'order' => $order + 1, 'guard' => 'web', 'status' => 1, 'has_permissions' => null,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('menus')->insert([
                ['name' => 'Unanswered Questions', 'url' => 'admin.limo.questions', 'icon' => null, 'parent_id' => $parentId, 'order' => 1, 'guard' => 'web', 'status' => 1, 'has_permissions' => null, 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Knowledge Base', 'url' => 'admin.limo.knowledge', 'icon' => null, 'parent_id' => $parentId, 'order' => 2, 'guard' => 'web', 'status' => 1, 'has_permissions' => null, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }
    }

    public function down(): void
    {
        DB::table('menus')->whereIn('url', ['admin.limo.questions', 'admin.limo.knowledge'])->delete();
        Schema::dropIfExists('ai_knowledge_entries');
        Schema::table('ai_chat_histories', function (Blueprint $table) {
            foreach (['actor', 'intent', 'question', 'feedback'] as $col) {
                if (Schema::hasColumn('ai_chat_histories', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
