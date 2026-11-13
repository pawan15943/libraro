<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Library sidebar: Expense Names and Exam Names move from Settings to Setup (after Global Discount).
 * Rows are found by url, so ids may differ between databases.
 */
class MoveExpenseExamMenusToSetup extends Migration
{
    private function parentOf(string $childUrl): ?int
    {
        $tops = DB::table('menus')->where('guard', 'library')->whereNull('deleted_at')->whereNull('parent_id')->pluck('id');

        return DB::table('menus')->whereNull('deleted_at')->where('url', $childUrl)->whereIn('parent_id', $tops)->value('parent_id');
    }

    private function move(string $url, ?int $from, ?int $to, int $order): void
    {
        if ($from && $to) {
            DB::table('menus')->where('url', $url)->where('parent_id', $from)
                ->update(['parent_id' => $to, 'order' => $order, 'updated_at' => now()]);
        }
    }

    public function up()
    {
        $setup = $this->parentOf('branch.list');
        $settings = $this->parentOf('expense.index');
        $this->move('expense.index', $settings, $setup, 8);
        $this->move('exam.index', $settings, $setup, 9);
    }

    public function down()
    {
        $setup = $this->parentOf('branch.list');
        $settings = $this->parentOf('library.settings');
        $this->move('expense.index', $setup, $settings, 3);
        $this->move('exam.index', $setup, $settings, 4);
    }
}
