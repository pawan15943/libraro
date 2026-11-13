<?php

namespace Tests\Feature;

use App\Models\Learner;
use App\Models\Library;
use App\Models\Quiz;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/** Student study corner: goal, stopwatch, daily GK quiz, and library quizzes (builder + student side). */
class StudyPortalTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->useStoragePath(sys_get_temp_dir().'/libraro-test-storage');
        foreach (array_keys(config('logging.channels', [])) as $channel) {
            if (config("logging.channels.$channel.path")) {
                config(["logging.channels.$channel.path" => sys_get_temp_dir()."/libraro-test-$channel.log"]);
            }
        }
        $this->withoutMiddleware([\App\Http\Middleware\LicenseCheck::class, \App\Http\Middleware\LoggerMiddleware::class]);
    }

    private function learner(): Learner
    {
        $learner = Learner::withoutGlobalScopes()->where('status', 1)->whereNull('deleted_at')->whereNotNull('library_id')->orderByDesc('id')->first();
        if (! $learner) {
            $this->markTestSkipped('no active learner in this database');
        }

        return $learner;
    }

    public function test_student_pages_goal_stopwatch_and_daily_quiz(): void
    {
        $learner = $this->learner();
        $this->actingAs($learner, 'learner');

        $home = $this->get('/learner/home');
        $home->assertOk()->assertSee('Daily GK quiz')->assertSee('Study streak')->assertSee('Study stopwatch');

        $this->get('/learner/profile')->assertOk()->assertSee('lr-crumbs', false)->assertSee('My library');
        $this->get('/learner/goal')->assertOk();
        $this->get('/learner/stopwatch')->assertOk();
        $this->get('/learner/quizzes')->assertOk();

        $date = now()->addDays(30)->toDateString();
        $this->post('/learner/goal', ['exam_id' => 'other', 'exam_name' => 'Test Exam', 'exam_date' => $date, 'syllabus' => "Polity\nGeography"])
            ->assertRedirect('/learner/goal');
        $this->get('/learner/home')->assertOk()->assertSee('Test Exam')->assertSee('30')->assertSee('Polity');

        $this->postJson('/learner/stopwatch', ['seconds' => 1800, 'mode' => 'study', 'subject' => 'Polity'])
            ->assertOk()->assertJsonPath('stats.today_seconds', fn ($v) => $v >= 1800);
        $this->postJson('/learner/stopwatch', ['seconds' => 3, 'mode' => 'study'])->assertStatus(422);
        $this->get('/learner/stopwatch')->assertOk()->assertSee('Polity');

        $this->get('/learner/daily-quiz')->assertOk()->assertSee('Submit quiz');
        $this->post('/learner/daily-quiz', ['a' => [], 'seconds' => 20])->assertRedirect('/learner/daily-quiz');
        $this->get('/learner/daily-quiz')->assertOk()->assertSee('Answers')->assertDontSee('Submit quiz');
        $this->post('/learner/daily-quiz', ['a' => []])->assertRedirect('/learner/daily-quiz'); // only once a day
    }

    public function test_library_quiz_builder_and_student_attempt(): void
    {
        $learner = $this->learner();
        $library = Library::find($learner->library_id);
        $this->actingAs($library, 'library');

        $q = fn ($n, $subject) => ['subject' => $subject, 'question' => "Question $n?", 'a' => 'One', 'b' => 'Two', 'c' => 'Three', 'd' => '', 'correct' => 'b', 'explanation' => 'Because'];
        $payload = [
            'title' => 'Mock test',
            'duration_minutes' => 20,
            'is_published' => 1,
            'subjects' => ['Maths', 'English', '', ''],
            'q' => [$q(1, 0), $q(2, 1), $q(3, 1)],
        ];

        // a question on an unnamed subject is kept under the first named subject (Maths)
        $fallback = $payload; $fallback['title'] = 'Fallback test'; $fallback['q'][1]['subject'] = 2;
        $this->post('/library/quizzes', $fallback)->assertSessionHasNoErrors()->assertRedirect('/library/quizzes');
        $fb = Quiz::where('library_id', $library->id)->where('title', 'Fallback test')->firstOrFail();
        $this->assertSame(['Maths', 'Maths', 'English'], $fb->questions()->orderBy('position')->pluck('subject')->all());
        // no subject named at all: everything goes under "General"
        $general = $payload; $general['title'] = 'General test'; $general['subjects'] = ['', '', '', ''];
        $this->post('/library/quizzes', $general)->assertSessionHasNoErrors();
        $this->assertSame(['General'], Quiz::where('library_id', $library->id)->where('title', 'General test')->firstOrFail()->questions()->pluck('subject')->unique()->values()->all());
        // more than 40 questions is refused
        $many = $payload; $many['q'] = array_map(fn ($i) => $q($i, 0), range(1, 41));
        $this->post('/library/quizzes', $many)->assertSessionHasErrors('q');

        $this->post('/library/quizzes', $payload)->assertRedirect('/library/quizzes');
        $quiz = Quiz::where('library_id', $library->id)->where('title', 'Mock test')->firstOrFail();
        $this->assertSame(3, $quiz->questions()->count());

        $this->get('/library/quizzes')->assertOk()->assertSee('Mock test');
        $this->get("/library/quizzes/{$quiz->id}/edit")->assertOk();

        // student side
        $this->app['auth']->forgetGuards();
        $this->actingAs($learner, 'learner');
        $this->get('/learner/quizzes')->assertOk()->assertSee('Mock test');
        $this->get("/learner/quizzes/{$quiz->id}")->assertOk()->assertSee('Maths')->assertSee('Question 1?');

        $ids = $quiz->questions->pluck('id');
        $res = $this->post("/learner/quizzes/{$quiz->id}", ['a' => [$ids[0] => 'b', $ids[1] => 'a'], 'seconds' => 90]);
        $attempt = \App\Models\QuizAttempt::where('quiz_id', $quiz->id)->firstOrFail();
        $this->assertSame([1, 1, 1], [$attempt->correct, $attempt->wrong, $attempt->skipped]);
        $res->assertRedirect("/learner/quizzes/result/{$attempt->id}");
        $this->get("/learner/quizzes/result/{$attempt->id}")->assertOk()->assertSee('1 of 3 correct')->assertSee('Subject wise')->assertSee('lr-crumbs', false);

        // a draft is not visible to students
        $quiz->update(['is_published' => false]);
        $this->get("/learner/quizzes/{$quiz->id}")->assertNotFound();
    }

    public function test_registration_goal_fills_blanks_only(): void
    {
        $learner = $this->learner();
        $svc = app(\App\Services\StudyPortalService::class);
        \App\Models\LearnerGoal::where('learner_id', $learner->id)->delete();

        $svc->saveFromRegistration($learner, ['exam_id' => null, 'exam_date' => null, 'syllabus' => null]);
        $this->assertNull($svc->goalRow($learner)); // nothing given, nothing saved

        $date = now()->addDays(10)->toDateString();
        $svc->saveFromRegistration($learner, ['exam_date' => $date, 'syllabus' => "A\nB"]);
        $svc->saveFromRegistration($learner, ['exam_date' => '', 'syllabus' => '']); // blank must not wipe
        $goal = $svc->goal($learner);
        $this->assertSame(10, $goal['days_left']);
        $this->assertSame(['A', 'B'], $goal['topics']);
    }
}
