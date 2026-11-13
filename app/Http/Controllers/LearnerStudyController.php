<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\StudySession;
use App\Services\StudyPortalService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Student portal: exam goal, study stopwatch, daily GK quiz and the library's quizzes. */
class LearnerStudyController extends Controller
{
    public function __construct(private StudyPortalService $study)
    {
    }

    private function learner()
    {
        return Auth::guard('learner')->user();
    }

    private function libraryQuiz(int $id): Quiz
    {
        return Quiz::where('library_id', $this->learner()->library_id)->where('is_published', true)->findOrFail($id);
    }

    // ---------- Goal ----------

    public function goal()
    {
        $learner = $this->learner();

        return view('learner.study.goal', [
            'goal' => $this->study->goal($learner),
            'goalRow' => $this->study->goalRow($learner),
            'exams' => Exam::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function goalSave(Request $request)
    {
        if ($request->input('exam_id') === 'other' || $request->input('exam_id') === '') {
            $request->merge(['exam_id' => null]);
        }
        $data = $request->validate([
            'exam_id' => 'nullable|integer|exists:exams,id',
            'exam_name' => 'nullable|string|max:120',
            'exam_date' => 'nullable|date|after_or_equal:today',
            'syllabus' => 'nullable|string|max:5000',
        ]);
        $this->study->saveGoal($this->learner(), $data);

        return redirect()->route('learner.goal')->with('success', 'Your exam goal is saved.');
    }

    // ---------- Stopwatch ----------

    public function stopwatch()
    {
        $learner = $this->learner();
        $goal = $this->study->goal($learner);

        return view('learner.study.stopwatch', [
            'stats' => $this->study->stats($learner),
            'recent' => $this->study->recentSessions($learner),
            'topics' => $goal['topics'] ?? [],
        ]);
    }

    public function stopwatchSave(Request $request)
    {
        $data = $request->validate([
            'seconds' => 'required|integer|min:10|max:57600',
            'subject' => 'nullable|string|max:120',
            'mode' => 'required|in:study,questions',
            'questions' => 'nullable|integer|min:0|max:2000',
        ]);
        $learner = $this->learner();

        $s = StudySession::create([
            'learner_id' => $learner->id,
            'library_id' => $learner->library_id,
            'subject' => ($data['subject'] ?? null) ?: null,
            'mode' => $data['mode'],
            'seconds' => $data['seconds'],
            'questions' => $data['questions'] ?? 0,
            'study_date' => Carbon::today(),
        ]);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'id' => $s->id, 'stats' => $this->study->stats($learner)]);
        }

        return back()->with('success', 'Session saved.');
    }

    // ---------- Daily GK quiz ----------

    public function gk()
    {
        $learner = $this->learner();
        $questions = $this->study->dailyQuestions();
        $attempt = $this->study->todayAttempt($learner);

        return view('learner.study.gk', [
            'questions' => $questions,
            'attempt' => $attempt,
            'streak' => $this->study->gkStreak($learner),
            'past' => QuizAttempt::where('learner_id', $learner->id)->where('kind', 'gk')->orderByDesc('quiz_date')->limit(7)->get(),
        ]);
    }

    public function gkSubmit(Request $request)
    {
        $learner = $this->learner();
        if ($this->study->todayAttempt($learner)) {
            return redirect()->route('learner.gk')->with('error', 'You have already taken today\'s quiz.');
        }
        $questions = $this->study->dailyQuestions();
        abort_if($questions->isEmpty(), 404);

        $answers = (array) $request->input('a', []);
        $r = $this->study->score($questions, $answers);

        QuizAttempt::create([
            'learner_id' => $learner->id,
            'quiz_id' => null,
            'kind' => 'gk',
            'quiz_date' => Carbon::today(),
            'total' => $r['total'], 'correct' => $r['correct'], 'wrong' => $r['wrong'], 'skipped' => $r['skipped'],
            'seconds' => min(86400, max(0, (int) $request->input('seconds', 0))),
            'answers' => $answers,
            'by_subject' => $r['by_subject'],
        ]);

        return redirect()->route('learner.gk')->with('success', 'Quiz done. See how you scored.');
    }

    // ---------- Library quizzes ----------

    public function quizzes()
    {
        $learner = $this->learner();

        return view('learner.study.quizzes', [
            'quizzes' => $this->study->libraryQuizzes($learner),
            'overview' => $this->study->overview($learner),
        ]);
    }

    public function quizPlay(int $id)
    {
        $quiz = $this->libraryQuiz($id);
        $quiz->load('questions');
        abort_if($quiz->questions->isEmpty(), 404);

        return view('learner.study.quiz-play', ['quiz' => $quiz, 'subjects' => $quiz->questions->groupBy('subject')]);
    }

    public function quizSubmit(Request $request, int $id)
    {
        $quiz = $this->libraryQuiz($id);
        $questions = $quiz->questions;
        abort_if($questions->isEmpty(), 404);

        $answers = (array) $request->input('a', []);
        $r = $this->study->score($questions, $answers);

        $attempt = QuizAttempt::create([
            'learner_id' => $this->learner()->id,
            'quiz_id' => $quiz->id,
            'kind' => 'library',
            'quiz_date' => Carbon::today(),
            'total' => $r['total'], 'correct' => $r['correct'], 'wrong' => $r['wrong'], 'skipped' => $r['skipped'],
            'seconds' => min(86400, max(0, (int) $request->input('seconds', 0))),
            'answers' => $answers,
            'by_subject' => $r['by_subject'],
        ]);

        return redirect()->route('learner.quiz.result', $attempt->id);
    }

    public function quizResult(int $attempt)
    {
        $attempt = QuizAttempt::where('learner_id', $this->learner()->id)->where('kind', 'library')->findOrFail($attempt);
        $quiz = Quiz::withTrashed()->with('questions')->findOrFail($attempt->quiz_id);

        return view('learner.study.quiz-result', ['attempt' => $attempt, 'quiz' => $quiz]);
    }
}
