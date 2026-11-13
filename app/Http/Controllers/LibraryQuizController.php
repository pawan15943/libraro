<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Library owner: build quizzes (up to 4 subjects, 40 questions) that the library's learners can take. */
class LibraryQuizController extends Controller
{
    private function mine(int $id): Quiz
    {
        return Quiz::where('library_id', getLibraryId())->findOrFail($id);
    }

    public function index()
    {
        $quizzes = Quiz::where('library_id', getLibraryId())
            ->withCount(['questions', 'attempts'])
            ->withAvg('attempts as avg_correct', 'correct')
            ->orderByDesc('id')->get();

        return view('library.quiz.index', compact('quizzes'));
    }

    public function form(?int $id = null)
    {
        $quiz = $id ? $this->mine($id)->load('questions') : null;
        $subjects = $quiz ? $quiz->questions->pluck('subject')->unique()->values()->all() : [];

        return view('library.quiz.form', [
            'quiz' => $quiz,
            'subjects' => array_pad($subjects, Quiz::MAX_SUBJECTS, ''),
            'questions' => $quiz ? $quiz->questions->map(fn ($q) => [
                'subject' => (int) array_search($q->subject, $subjects, true),
                'question' => $q->question,
                'a' => $q->option_a, 'b' => $q->option_b, 'c' => $q->option_c, 'd' => $q->option_d,
                'correct' => $q->correct, 'explanation' => $q->explanation,
            ])->values() : collect(),
            'exams' => Exam::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function save(Request $request, ?int $id = null)
    {
        $data = $request->validate([
            'title' => 'required|string|max:150',
            'description' => 'nullable|string|max:1000',
            'exam_id' => 'nullable|integer|exists:exams,id',
            'duration_minutes' => 'nullable|integer|min:0|max:600',
            'subjects' => 'required|array|max:' . Quiz::MAX_SUBJECTS,
            'subjects.*' => 'nullable|string|max:60',
            'q' => 'required|array|min:1|max:' . Quiz::MAX_QUESTIONS,
            'q.*.subject' => 'required|integer|between:0,' . (Quiz::MAX_SUBJECTS - 1),
            'q.*.question' => 'required|string|max:1000',
            'q.*.a' => 'required|string|max:255',
            'q.*.b' => 'required|string|max:255',
            'q.*.c' => 'nullable|string|max:255',
            'q.*.d' => 'nullable|string|max:255',
            'q.*.correct' => 'required|in:a,b,c,d',
            'q.*.explanation' => 'nullable|string|max:1000',
        ], [
            'q.required' => 'Add at least one question.',
            'q.max' => 'A quiz can have up to ' . Quiz::MAX_QUESTIONS . ' questions.',
            'q.*.question.required' => 'Every question needs its text.',
            'q.*.a.required' => 'Every question needs at least options A and B.',
            'q.*.b.required' => 'Every question needs at least options A and B.',
            'q.*.correct.required' => 'Choose the correct answer for every question.',
        ]);

        $names = array_map(fn ($s) => trim((string) $s), $data['subjects']);
        // No subject named → everything goes under "General"; a question pointing at an
        // empty subject falls back to the first named one.
        $firstNamed = collect($names)->search(fn ($n) => $n !== '');
        if ($firstNamed === false) {
            $names[0] = 'General';
            $firstNamed = 0;
        }
        $rows = array_values($data['q']);
        foreach ($rows as $i => $row) {
            if (($names[$row['subject']] ?? '') === '') {
                $rows[$i]['subject'] = $row['subject'] = $firstNamed;
            }
            if (empty(trim((string) ($row[$row['correct']] ?? '')))) {
                throw ValidationException::withMessages(['q' => 'Question ' . ($i + 1) . ': the correct answer points to an empty option.']);
            }
        }

        $quiz = DB::transaction(function () use ($id, $data, $rows, $names, $request) {
            $quiz = $id ? $this->mine($id) : new Quiz(['library_id' => getLibraryId()]);
            $quiz->fill([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'exam_id' => $data['exam_id'] ?? null,
                'duration_minutes' => (int) ($data['duration_minutes'] ?? 0),
                'is_published' => $request->boolean('is_published'),
            ])->save();

            $quiz->questions()->delete();
            foreach ($rows as $pos => $r) {
                QuizQuestion::create([
                    'quiz_id' => $quiz->id,
                    'subject' => $names[$r['subject']],
                    'question' => trim($r['question']),
                    'option_a' => trim($r['a']), 'option_b' => trim($r['b']),
                    'option_c' => trim((string) ($r['c'] ?? '')) ?: null, 'option_d' => trim((string) ($r['d'] ?? '')) ?: null,
                    'correct' => $r['correct'],
                    'explanation' => trim((string) ($r['explanation'] ?? '')) ?: null,
                    'position' => $pos,
                ]);
            }

            return $quiz;
        });

        return redirect()->route('quiz.index')->with('success', $quiz->is_published ? 'Quiz saved and visible to your students.' : 'Quiz saved as a draft.');
    }

    public function toggle(int $id)
    {
        $quiz = $this->mine($id);
        $quiz->update(['is_published' => ! $quiz->is_published]);

        return back()->with('success', $quiz->is_published ? 'Quiz is now visible to students.' : 'Quiz is hidden from students.');
    }

    public function destroy(int $id)
    {
        $this->mine($id)->delete();

        return redirect()->route('quiz.index')->with('success', 'Quiz deleted.');
    }
}
