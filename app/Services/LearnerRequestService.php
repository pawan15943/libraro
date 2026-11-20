<?php

namespace App\Services;

use App\Models\Learner;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Learner requests (table learner_request): the learner asks, the library answers in Learners → Requests.
 * request_status: 0 pending, 1 done, 2 not possible. The library makes the real change (renew, swap …) with its
 * usual screens; this only records the ask and the answer.
 */
class LearnerRequestService
{
    public const TYPES = [
        'renewPlan' => 'Renew plan',
        'changePlan' => 'Change plan',
        'upgradePlan' => 'Upgrade / downgrade plan',
        'swapSeat' => 'Change seat',
        'closePlan' => 'Close plan',
    ];

    public const PENDING = 0;

    public const DONE = 1;

    public const REJECTED = 2;

    /** A learner asks. The same kind of request still waiting is updated instead of sent twice. */
    public function create(Learner $learner, string $type, string $description): int
    {
        if (! isset(self::TYPES[$type])) {
            throw ValidationException::withMessages(['request_name' => 'Please choose what you need.']);
        }
        $open = DB::table('learner_request')->where('learner_id', $learner->id)->where('request_name', $type)
            ->where('request_status', self::PENDING)->whereNull('deleted_at')->orderByDesc('id')->first();
        if ($open) {
            DB::table('learner_request')->where('id', $open->id)->update(['description' => $description, 'updated_at' => now()]);

            return (int) $open->id;
        }

        return (int) DB::table('learner_request')->insertGetId([
            'learner_id' => $learner->id,
            'request_name' => $type,
            'description' => $description,
            'request_date' => now()->toDateString(),
            'request_status' => self::PENDING,
            'created_at' => now(),
        ]);
    }

    /** Requests of learners in the library / branch the signed-in user works in. */
    public function forLibrary(): Builder
    {
        return DB::table('learner_request as r')
            ->join('learners as l', 'l.id', '=', 'r.learner_id')
            ->where('l.library_id', getLibraryId())
            ->when(getCurrentBranch(), fn ($q) => $q->where('l.branch_id', getCurrentBranch()))
            ->whereNull('r.deleted_at');
    }

    /** The library answers: done (made the change) or not possible, with a note the learner sees. */
    public function decide(int $id, bool $done, ?string $reply): void
    {
        $row = $this->forLibrary()->where('r.id', $id)->select('r.*')->first();
        if (! $row) {
            abort(404);
        }
        if ((int) $row->request_status !== self::PENDING) {
            throw ValidationException::withMessages(['reply' => 'This request was already answered.']);
        }
        if (! $done && trim((string) $reply) === '') {
            throw ValidationException::withMessages(['reply' => 'Tell the learner why it is not possible.']);
        }

        [$type, $by] = Auth::guard('library_user')->check()
            ? ['library_user', Auth::guard('library_user')->id()]
            : ['library', Auth::guard('library')->id()];

        DB::table('learner_request')->where('id', $id)->update([
            'request_status' => $done ? self::DONE : self::REJECTED,
            'reply' => $reply !== null && trim($reply) !== '' ? mb_substr(trim($reply), 0, 1000) : null,
            'handled_at' => now(),
            'handled_by_type' => $type,
            'handled_by_id' => $by,
            'updated_at' => now(),
        ]);
    }
}
