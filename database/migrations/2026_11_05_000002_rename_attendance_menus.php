<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Shorter attendance labels in the library sidebar. Only the names change; urls and
 * has_permissions (the permission keys) stay as they are. The parent and the
 * "Mark Attendance" child share the url `attendance`, so they are told apart by parent_id.
 */
class RenameAttendanceMenus extends Migration
{
    // [url, is a child?, old name, new name]
    private array $names = [
        ['attendance', false, 'Manage Attendance', 'Attendance'],
        ['attendance', true, 'Mark Attendance', 'Manual'],
        ['attendance.apply', true, 'QR Attendance', 'Via QR / Scanner'],
        ['get.learner.attendance', true, 'Learner Attendance', 'Summary'],
    ];

    public function up()
    {
        $this->rename(3);
    }

    public function down()
    {
        $this->rename(2);
    }

    private function rename(int $to): void
    {
        foreach ($this->names as $row) {
            DB::table('menus')->where('guard', 'library')->where('url', $row[0])
                ->when($row[1], fn ($q) => $q->whereNotNull('parent_id'), fn ($q) => $q->whereNull('parent_id'))
                ->update(['name' => $row[$to], 'updated_at' => now()]);
        }
    }
}
