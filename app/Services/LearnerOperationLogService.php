<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LearnerOperationLogService
{
    public function log(
        int $learnerId,
        ?int $learnerDetailId,
        string $operation,
        string $fieldUpdated,
        mixed $oldValue = null,
        mixed $newValue = null,
        ?string $summary = null
    ): void {
        $createdAt = now();
        while (DB::table('learner_operations_log')
            ->where('learner_id', $learnerId)
            ->where('operation', $operation)
            ->where('created_at', $createdAt->format('Y-m-d H:i:s'))
            ->exists()) {
            $createdAt = $createdAt->copy()->addSecond();
        }

        $payload = [
            'learner_id' => $learnerId,
            'learner_detail_id' => $learnerDetailId,
            'library_id' => getLibraryId(),
            'operation' => $operation,
            'field_updated' => $fieldUpdated,
            'old_value' => $this->stringValue($oldValue),
            'new_value' => $this->stringValue($newValue),
            'updated_by' => $this->actorId(),
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ];

        if (Schema::hasColumn('learner_operations_log', 'branch_id')) {
            $payload['branch_id'] = getCurrentBranch();
        }

        if (Schema::hasColumn('learner_operations_log', 'summary')) {
            $payload['summary'] = $summary;
        }

        DB::table('learner_operations_log')->insert($payload);
    }

    private function actorId(): int
    {
        if (Auth::guard('library_user')->check()) {
            return (int) Auth::guard('library_user')->id();
        }

        if (Auth::guard('library')->check()) {
            return (int) Auth::guard('library')->id();
        }

        return (int) getLibraryId();
    }

    private function stringValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return (string) $value;
    }
}
