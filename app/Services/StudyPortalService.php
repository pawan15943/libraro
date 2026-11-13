<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\GkQuestion;
use App\Models\Learner;
use App\Models\LearnerGoal;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\StudySession;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Everything the student dashboard needs beyond the seat/plan summary:
 * exam goal and countdown, study stopwatch stats, daily GK quiz and library quizzes.
 */
class StudyPortalService
{
    public const DAILY_QUESTIONS = 10;

    // ---------- Goal ----------

    public function goalRow(Learner $learner): ?LearnerGoal
    {
        return LearnerGoal::where('learner_id', $learner->id)->first();
    }

    /** Goal for display: exam name, date, countdown and syllabus topics. Null when nothing was set. */
    public function goal(Learner $learner): ?array
    {
        $g = $this->goalRow($learner);
        $examName = $g?->exam_name ?: ($g?->exam_id ? Exam::withTrashed()->where('id', $g->exam_id)->value('name') : null);

        // older learners only have the exam picked at registration
        if (! $examName) {
            $legacy = DB::table('learner_detail')->where('learner_id', $learner->id)->whereNotNull('exam_id')->orderByDesc('id')->value('exam_id');
            $examName = $legacy ? Exam::withTrashed()->where('id', $legacy)->value('name') : null;
        }

        if (! $examName && ! $g?->exam_date && ! $g?->syllabus) {
            return null;
        }

        $date = $g?->exam_date ? Carbon::parse($g->exam_date)->startOfDay() : null;
        $days = $date ? (int) Carbon::today()->diffInDays($date, false) : null;

        return [
            'exam_name' => $examName,
            'exam_id' => $g?->exam_id,
            'exam_date' => $date,
            'days_left' => $days,
            'is_past' => $days !== null && $days < 0,
            'topics' => $g ? $g->topics() : [],
            'syllabus' => $g?->syllabus,
        ];
    }

    public function saveGoal(Learner $learner, array $data): LearnerGoal
    {
        $examId = ! empty($data['exam_id']) ? (int) $data['exam_id'] : null;

        return LearnerGoal::updateOrCreate(['learner_id' => $learner->id], [
            'library_id' => $learner->library_id,
            'exam_id' => $examId,
            'exam_name' => $examId ? null : (trim((string) ($data['exam_name'] ?? '')) ?: null),
            'exam_date' => ! empty($data['exam_date']) ? $data['exam_date'] : null,
            'syllabus' => isset($data['syllabus']) ? trim((string) $data['syllabus']) ?: null : null,
        ]);
    }

    /**
     * Goal taken while registering a learner (library form or QR booking). Only fills what was
     * given, so a blank field never wipes what the student already set.
     */
    public function saveFromRegistration(Learner $learner, array $data): void
    {
        $examId = ! empty($data['exam_id']) ? (int) $data['exam_id'] : null;
        $date = ! empty($data['exam_date']) ? $data['exam_date'] : null;
        $syllabus = trim((string) ($data['syllabus'] ?? '')) ?: null;
        if (! $examId && ! $date && ! $syllabus) {
            return;
        }

        $goal = LearnerGoal::firstOrNew(['learner_id' => $learner->id]);
        $goal->library_id = $learner->library_id;
        $goal->exam_id = $examId ?: $goal->exam_id;
        if ($examId) {
            $goal->exam_name = null;
        }
        $goal->exam_date = $date ?: $goal->exam_date;
        $goal->syllabus = $syllabus ?: $goal->syllabus;
        $goal->save();
    }

    // ---------- Stopwatch ----------

    public function stats(Learner $learner): array
    {
        $today = Carbon::today();
        $rows = StudySession::where('learner_id', $learner->id)
            ->whereDate('study_date', '>=', $today->copy()->subDays(60))
            ->selectRaw('study_date, SUM(seconds) as secs, SUM(questions) as qs')
            ->groupBy('study_date')->get()
            ->keyBy(fn ($r) => Carbon::parse($r->study_date)->toDateString());

        $week = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = $today->copy()->subDays($i);
            $week[] = ['label' => $d->format('D'), 'date' => $d->toDateString(), 'seconds' => (int) ($rows[$d->toDateString()]->secs ?? 0), 'today' => $i === 0];
        }

        // streak: consecutive days with study, today may still be empty
        $streak = 0;
        $cursor = $today->copy();
        if (! isset($rows[$cursor->toDateString()])) {
            $cursor->subDay();
        }
        while (isset($rows[$cursor->toDateString()])) {
            $streak++;
            $cursor->subDay();
        }

        $todayRow = $rows[$today->toDateString()] ?? null;

        return [
            'today_seconds' => (int) ($todayRow->secs ?? 0),
            'today_questions' => (int) ($todayRow->qs ?? 0),
            'week_seconds' => array_sum(array_column($week, 'seconds')),
            'week' => $week,
            'week_max' => max(1, max(array_column($week, 'seconds'))),
            'streak' => $streak,
            'subjects' => StudySession::where('learner_id', $learner->id)->whereDate('study_date', '>=', $today->copy()->subDays(6))
                ->whereNotNull('subject')->selectRaw('subject, SUM(seconds) as secs')->groupBy('subject')->orderByDesc('secs')->limit(5)->get(),
        ];
    }

    public function recentSessions(Learner $learner, int $limit = 8): Collection
    {
        return StudySession::where('learner_id', $learner->id)->orderByDesc('id')->limit($limit)->get();
    }

    // ---------- Daily GK quiz ----------

    /** Same questions for every learner on a given day; rotates through the whole bank. */
    public function dailyQuestions(?Carbon $day = null): Collection
    {
        $day = $day ?: Carbon::today();
        $ids = GkQuestion::where('is_active', true)->orderBy('id')->pluck('id');
        if ($ids->isEmpty()) {
            return collect();
        }
        $n = $ids->count();
        $take = min(self::DAILY_QUESTIONS, $n);
        $start = (($day->copy()->startOfDay()->timestamp / 86400) * $take) % $n;
        $picked = collect(range(0, $take - 1))->map(fn ($i) => $ids[(int) (($start + $i) % $n)]);

        $questions = GkQuestion::whereIn('id', $picked)->get()->keyBy('id');

        return $picked->map(fn ($id) => $questions[$id])->values();
    }

    public function todayAttempt(Learner $learner): ?QuizAttempt
    {
        return QuizAttempt::where('learner_id', $learner->id)->where('kind', 'gk')->whereDate('quiz_date', Carbon::today())->first();
    }

    public function gkStreak(Learner $learner): int
    {
        $dates = QuizAttempt::where('learner_id', $learner->id)->where('kind', 'gk')->whereNotNull('quiz_date')
            ->orderByDesc('quiz_date')->limit(120)->pluck('quiz_date')->map(fn ($d) => Carbon::parse($d)->toDateString())->flip();
        $cursor = Carbon::today();
        if (! isset($dates[$cursor->toDateString()])) {
            $cursor->subDay();
        }
        $n = 0;
        while (isset($dates[$cursor->toDateString()])) {
            $n++;
            $cursor->subDay();
        }

        return $n;
    }

    // ---------- Library quizzes ----------

    public function libraryQuizzes(Learner $learner): Collection
    {
        $mine = QuizAttempt::where('learner_id', $learner->id)->where('kind', 'library')->orderByDesc('id')->get()->groupBy('quiz_id');

        return Quiz::where('library_id', $learner->library_id)->where('is_published', true)
            ->withCount('questions')->orderByDesc('id')->get()
            ->filter(fn ($q) => $q->questions_count > 0)
            ->each(function ($q) use ($mine) {
                $q->setRelation('my_attempts', $mine->get($q->id, collect()));
            });
    }

    /** Score answers against questions. $questions: collection with ->correct, ->id and optional ->subject. */
    public function score(Collection $questions, array $answers): array
    {
        $correct = $wrong = $skipped = 0;
        $by = [];
        foreach ($questions as $q) {
            $given = strtolower((string) ($answers[$q->id] ?? ''));
            $subject = $q->subject ?? $q->category ?? 'General';
            $by[$subject] ??= ['total' => 0, 'correct' => 0];
            $by[$subject]['total']++;
            if ($given === '') {
                $skipped++;
            } elseif ($given === $q->correct) {
                $correct++;
                $by[$subject]['correct']++;
            } else {
                $wrong++;
            }
        }

        return ['total' => $questions->count(), 'correct' => $correct, 'wrong' => $wrong, 'skipped' => $skipped, 'by_subject' => $by];
    }

    public function overview(Learner $learner): array
    {
        $attempts = QuizAttempt::where('learner_id', $learner->id)->get();

        return [
            'quizzes_taken' => $attempts->count(),
            'avg' => $attempts->count() ? (int) round($attempts->avg(fn ($a) => $a->total ? $a->correct * 100 / $a->total : 0)) : 0,
        ];
    }
}
