<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Other fee: what it was for is saved and shown in transactions (otherPaymentTitle()). */
class OtherPaymentTitleTest extends TestCase
{
    use DatabaseTransactions;

    public function test_other_fee_keeps_its_name_everywhere(): void
    {
        $this->app->useStoragePath(sys_get_temp_dir().'/libraro-test-storage');
        $this->withoutMiddleware([\App\Http\Middleware\LicenseCheck::class, \App\Http\Middleware\LoggerMiddleware::class, \App\Http\Middleware\ProtectPortalPages::class]);
        $tx = DB::table('learner_transactions as t')->join('learners as l', 'l.id', '=', 't.learner_id')
            ->whereNull('t.deleted_at')->whereNull('l.deleted_at')->select('t.learner_id', 'l.library_id', 't.learner_detail_id')->orderByDesc('t.id')->first();
        if (! $tx) {
            $this->markTestSkipped('no transaction');
        }
        $this->actingAs(\App\Models\Library::withoutGlobalScopes()->find($tx->library_id), 'library');

        // no name → asked for it
        $this->post(route('learner.other.payment.store'), ['learner_id' => $tx->learner_id, 'payment_type' => 'miscellaneous', 'fees' => 150, 'payment_mode' => 2])
            ->assertSessionHasErrors('payment_for');

        $this->post(route('learner.other.payment.store'), ['learner_id' => $tx->learner_id, 'payment_type' => 'miscellaneous', 'payment_for' => 'Exam form fee', 'fees' => 150, 'payment_mode' => 2])
            ->assertSessionHasNoErrors();

        $act = DB::table('learner_transaction_activity')->where('learner_id', $tx->learner_id)->orderByDesc('id')->first();
        $this->assertSame('MISCELLANEOUS', $act->payment_type);
        $this->assertSame('Exam form fee', $act->particular);
        $this->assertSame('Cr', $act->dr_cr);

        $tabs = app(\App\Services\LearnerLifecycleService::class)->transactionTabs((int) $tx->learner_id);
        $titles = collect($tabs['other_payment']['payments'] ?? [])->pluck('title');
        $this->assertContains('Exam form fee', $titles->all());
        $this->assertSame('Other fee', otherPaymentTitle('MISCELLANEOUS', 'Paid By Trans'));
        $this->assertSame('Token money', otherPaymentTitle('TOKEN MONEY', 'Paid By Trans'));
    }
}
