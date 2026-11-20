<?php

namespace Tests\Feature;

use App\Models\Complaint;
use App\Models\Library;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/** A library can change only its own learners' complaints (clarification.submit.status). */
class ComplaintScopeTest extends TestCase
{
    use DatabaseTransactions;

    public function test_complaint_of_another_library_cannot_be_changed(): void
    {
        $this->withoutMiddleware([\App\Http\Middleware\LicenseCheck::class, \App\Http\Middleware\LoggerMiddleware::class]);
        $mine = Library::whereNotNull('library_type')->orderBy('id')->first() ?? $this->markTestSkipped('no library');
        $other = Library::where('id', '!=', $mine->id)->first() ?? $this->markTestSkipped('only one library');
        $c = Complaint::create(['library_id' => $other->id, 'learner_id' => null, 'title' => 'Fan not working', 'description' => 'Seat 4', 'status' => 0]);

        $this->actingAs($mine, 'library');
        $this->postJson(route('clarification.submit.status'), ['row_id' => $c->id, 'status' => 1, 'remark' => 'hacked'])->assertNotFound();
        $this->assertSame(0, (int) $c->fresh()->status);
        $this->assertNull($c->fresh()->response);
    }
}
