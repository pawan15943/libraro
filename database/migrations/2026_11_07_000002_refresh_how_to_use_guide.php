<?php

use App\Support\HowToUseGuide;
use App\Support\HowToUseGuideContent;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rewrites the "How Libraro works" guide for the panel as it is today (English + Hindi),
 * groups it by topic and adds every newer feature (Limo AI & Support, advanced shifts,
 * chat, study portal, referral, alerts, exports …).
 *
 * Existing rows are updated in place (matched by their old operation name) so their ids —
 * used by the mobile app — stay the same. Rows that no longer describe the panel are removed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('how-to-use', function (Blueprint $table) {
            if (! Schema::hasColumn('how-to-use', 'category')) {
                $table->string('category', 40)->nullable()->after('id');
            }
            if (! Schema::hasColumn('how-to-use', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->after('category');
            }
        });

        $order = array_flip(array_keys(HowToUseGuide::categories()));
        $existing = DB::table('how-to-use')->get(['id', 'operation_name']);
        $kept = [];
        $pos = [];

        foreach (HowToUseGuideContent::entries() as $e) {
            $cat = $e['cat'];
            $pos[$cat] = ($pos[$cat] ?? 0) + 1;
            $row = [
                'category' => $cat,
                'sort_order' => ($order[$cat] ?? 99) * 100 + $pos[$cat],
                'operation_name' => $e['title'],
                'hindi_question' => $e['title_hi'],
                'usage_english' => implode("\n", $e['en']),
                'usage_hindi' => implode("\n", $e['hi']),
            ];

            // Same guide as before? (old name matches the pattern, or the same title)
            $match = $existing->first(function ($x) use ($e, $kept) {
                if (in_array($x->id, $kept, true)) {
                    return false;
                }
                $old = (string) $x->operation_name;

                return strcasecmp(trim($old), $e['title']) === 0 || (! empty($e['was']) && preg_match($e['was'], $old));
            });

            if ($match) {
                DB::table('how-to-use')->where('id', $match->id)->update($row);
                $kept[] = $match->id;
            } else {
                $kept[] = DB::table('how-to-use')->insertGetId($row);
            }
        }

        DB::table('how-to-use')->whereNotIn('id', $kept)->delete();
    }

    public function down(): void
    {
        // Content changes are not reverted; only the new columns are removed.
        Schema::table('how-to-use', function (Blueprint $table) {
            foreach (['category', 'sort_order'] as $col) {
                if (Schema::hasColumn('how-to-use', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
