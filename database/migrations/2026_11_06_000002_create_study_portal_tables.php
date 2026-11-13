<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Student study portal: exam goal, study stopwatch sessions, library quizzes
 * (up to 4 subjects / 40 questions) and the Libraro daily GK quiz.
 */
return new class extends Migration
{
    public function up(): void
    {
        // One goal per learner: target exam, exam date and the syllabus they follow.
        Schema::create('learner_goals', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('learner_id')->unique();
            $t->unsignedBigInteger('library_id')->nullable()->index();
            $t->unsignedBigInteger('exam_id')->nullable();
            $t->string('exam_name')->nullable();       // free text when the exam is not in the list
            $t->date('exam_date')->nullable();
            $t->text('syllabus')->nullable();           // one topic per line
            $t->timestamps();
        });

        // Stopwatch sessions saved by the learner.
        Schema::create('study_sessions', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('learner_id')->index();
            $t->unsignedBigInteger('library_id')->nullable();
            $t->string('subject', 120)->nullable();
            $t->string('mode', 20)->default('study');   // study | questions
            $t->unsignedInteger('seconds');
            $t->unsignedInteger('questions')->default(0);
            $t->date('study_date')->index();
            $t->timestamps();
        });

        // Quizzes made by a library owner.
        Schema::create('quizzes', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('library_id')->index();
            $t->string('title');
            $t->text('description')->nullable();
            $t->unsignedBigInteger('exam_id')->nullable();
            $t->unsignedSmallInteger('duration_minutes')->default(0); // 0 = no limit
            $t->boolean('is_published')->default(false);
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('quiz_questions', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('quiz_id')->index();
            $t->string('subject', 120);
            $t->text('question');
            $t->string('option_a');
            $t->string('option_b');
            $t->string('option_c')->nullable();
            $t->string('option_d')->nullable();
            $t->char('correct', 1);                      // a | b | c | d
            $t->text('explanation')->nullable();
            $t->unsignedSmallInteger('position')->default(0);
            $t->timestamps();
        });

        // Libraro daily GK bank (platform level, not per library).
        Schema::create('gk_questions', function (Blueprint $t) {
            $t->id();
            $t->string('category', 60)->default('General');
            $t->text('question');
            $t->string('option_a');
            $t->string('option_b');
            $t->string('option_c');
            $t->string('option_d');
            $t->char('correct', 1);
            $t->text('explanation')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        // One row per finished quiz (quiz_id null + kind 'gk' = daily GK quiz).
        Schema::create('quiz_attempts', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('learner_id')->index();
            $t->unsignedBigInteger('quiz_id')->nullable()->index();
            $t->string('kind', 10)->default('library'); // library | gk
            $t->date('quiz_date')->nullable();          // for the daily quiz
            $t->unsignedSmallInteger('total');
            $t->unsignedSmallInteger('correct');
            $t->unsignedSmallInteger('wrong');
            $t->unsignedSmallInteger('skipped');
            $t->unsignedInteger('seconds')->default(0);
            $t->json('answers')->nullable();            // {question_id: "a"}
            $t->json('by_subject')->nullable();         // {subject: {total, correct}}
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['quiz_attempts', 'gk_questions', 'quiz_questions', 'quizzes', 'study_sessions', 'learner_goals'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
