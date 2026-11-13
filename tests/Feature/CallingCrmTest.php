<?php

namespace Tests\Feature;

use App\Models\Crm\CrmCall;
use App\Models\Crm\CrmDisposition;
use App\Models\Crm\CrmLead;
use App\Models\Crm\CrmReminder;
use App\Models\Crm\CrmStage;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CallingCrmTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        return User::find(1);
    }

    private function agent(): User
    {
        $u = User::create(['name' => 'Test Agent', 'email' => 'agent'.uniqid().'@example.com', 'password' => bcrypt('x')]);
        $u->assignRole(Role::findByName('Calling Agent', 'web'));
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        return $u->fresh();
    }

    private function lead(array $extra = []): CrmLead
    {
        return app(\App\Services\Crm\CrmLeadService::class)->create(array_merge([
            'library_name' => 'Flow Lib', 'contact_name' => 'Asha', 'mobile' => '95'.random_int(10000000, 99999999), 'priority' => 'warm',
        ], $extra), $this->admin());
    }

    public function test_agent_permissions(): void
    {
        $agent = $this->agent();
        $this->actingAs($agent, 'web')->get('/administrator/crm/queue')->assertOk();
        $this->actingAs($agent, 'web')->get('/administrator/crm/dashboard')->assertOk();
        $this->actingAs($agent, 'web')->get('/administrator/crm/settings')->assertForbidden();
        $this->actingAs($agent, 'web')->get('/administrator/crm/import')->assertForbidden();
        $this->actingAs($agent, 'web')->get('/administrator/crm/leads/export')->assertForbidden();

        $mine = $this->lead(['assigned_to' => $agent->id]);
        $other = $this->lead(['assigned_to' => 1]);
        $pool = $this->lead();
        $this->actingAs($agent, 'web')->get('/administrator/crm/leads/'.$mine->id)->assertOk();
        $this->actingAs($agent, 'web')->get('/administrator/crm/leads/'.$pool->id)->assertOk();
        $this->actingAs($agent, 'web')->get('/administrator/crm/leads/'.$other->id)->assertNotFound();
    }

    public function test_log_call_with_follow_up_from_the_sheet(): void
    {
        $lead = $this->lead();
        $d = CrmDisposition::where('slug', 'call-back-later')->first();
        $this->actingAs($this->admin(), 'web')->from('/administrator/crm/queue')
            ->post('/administrator/crm/leads/'.$lead->id.'/calls', [
                'disposition_id' => $d->id, 'notes' => 'call at 6', 'duration_seconds' => 95,
                'follow_up_at' => now()->addHours(4)->format('Y-m-d\TH:i'), 'priority' => 'hot',
            ])->assertRedirect('/administrator/crm/queue')->assertSessionHas('success');

        $lead->refresh();
        $this->assertSame(1, $lead->calls_count);
        $this->assertSame('hot', $lead->priority);
        $this->assertSame(95, CrmCall::where('lead_id', $lead->id)->value('duration_seconds'));
        $this->assertSame(1, CrmReminder::where('lead_id', $lead->id)->pending()->count());
        $this->assertNotNull($lead->next_follow_up_at);
    }

    public function test_call_sheet_history_shows_earlier_calls_and_notes(): void
    {
        $agent = $this->agent();
        $lead = $this->lead(['assigned_to' => 1, 'notes' => 'Owner prefers evening calls']);
        $svc = app(\App\Services\Crm\CrmLeadService::class);
        $svc->logCall($lead, $this->admin(), ['disposition_id' => CrmDisposition::where('slug', 'no-answer')->value('id')]);
        $svc->logCall($lead->fresh(), $this->admin(), ['disposition_id' => CrmDisposition::where('slug', 'interested')->value('id'), 'notes' => 'Wants yearly plan price', 'duration_seconds' => 130]);
        $svc->addNote($lead->fresh(), $this->admin(), 'Brother runs a second branch');

        $json = $this->actingAs($this->admin(), 'web')->getJson('/administrator/crm/leads/'.$lead->id.'/history')->assertOk()->json();
        $this->assertSame(2, $json['summary']['calls']);
        $this->assertSame('Owner prefers evening calls', $json['summary']['lead_notes']);
        $results = array_column(array_filter($json['items'], fn ($i) => $i['kind'] === 'call'), 'result');
        $this->assertEqualsCanonicalizing(['No answer', 'Interested'], $results);
        $this->assertContains('Wants yearly plan price', array_column($json['items'], 'notes'));
        $this->assertContains('Brother runs a second branch', array_column($json['items'], 'notes'));
        $this->assertSame('2m 10s', collect($json['items'])->firstWhere('result', 'Interested')['duration']);

        // an agent cannot read the history of a lead owned by someone else
        $this->actingAs($agent, 'web')->getJson('/administrator/crm/leads/'.$lead->id.'/history')->assertNotFound();
    }

    public function test_follow_up_in_the_past_is_rejected(): void
    {
        $lead = $this->lead();
        $d = CrmDisposition::where('slug', 'busy')->first();
        $this->actingAs($this->admin(), 'web')->post('/administrator/crm/leads/'.$lead->id.'/calls', [
            'disposition_id' => $d->id, 'follow_up_at' => now()->subHour()->format('Y-m-d\TH:i'),
        ])->assertSessionHasErrors('follow_up_at');
    }

    public function test_status_update_to_not_interested_removes_from_queue(): void
    {
        $lead = $this->lead();
        $lost = CrmStage::where('slug', 'not-interested')->first();
        $sub = $lost->children()->first();
        $this->actingAs($this->admin(), 'web')->post('/administrator/crm/leads/'.$lead->id.'/stage', [
            'stage_id' => $lost->id, 'sub_stage_id' => $sub->id, 'note' => 'uses excel',
        ])->assertSessionHas('success');
        $this->assertTrue($lead->fresh()->is_closed);
        $html = $this->actingAs($this->admin(), 'web')->get('/administrator/crm/queue?tab=all&q='.$lead->mobile)->getContent();
        $this->assertStringNotContainsString($lead->mobile, strip_tags(explode('id="crmConfig"', $html)[0]));
    }

    public function test_reminder_message_and_note(): void
    {
        $lead = $this->lead();
        $this->actingAs($this->admin(), 'web')->post('/administrator/crm/leads/'.$lead->id.'/follow-ups', [
            'remind_at' => now()->addDay()->format('Y-m-d\TH:i'), 'note' => 'demo',
        ])->assertSessionHas('success');
        $rem = CrmReminder::where('lead_id', $lead->id)->pending()->firstOrFail();

        $this->actingAs($this->admin(), 'web')->postJson('/administrator/crm/leads/'.$lead->id.'/messages', [
            'channel' => 'whatsapp', 'text' => 'Hi Asha', 'template' => 'Introduction',
        ])->assertOk()->assertJson(['ok' => true]);
        $this->assertDatabaseHas('crm_activities', ['lead_id' => $lead->id, 'type' => 'whatsapp']);

        $this->actingAs($this->admin(), 'web')->post('/administrator/crm/leads/'.$lead->id.'/note', ['note' => 'spoke to son'])->assertSessionHas('success');
        $this->actingAs($this->admin(), 'web')->post('/administrator/crm/follow-ups/'.$rem->id.'/done')->assertSessionHas('success');
        $this->assertSame('done', $rem->fresh()->status);
        $this->actingAs($this->admin(), 'web')->get('/administrator/crm/leads/'.$lead->id)->assertOk()->assertSee('spoke to son')->assertSee('Hi Asha');
    }

    public function test_due_reminders_endpoint_notifies_once(): void
    {
        $lead = $this->lead(['assigned_to' => 1]);
        CrmReminder::create(['lead_id' => $lead->id, 'user_id' => 1, 'remind_at' => now()->subMinute()]);
        $first = $this->actingAs($this->admin(), 'web')->getJson('/administrator/crm/follow-ups/due')->assertOk()->json();
        $this->assertGreaterThanOrEqual(1, $first['count']);
        $this->assertContains($lead->display_name, array_column($first['items'], 'name'));
        $second = $this->actingAs($this->admin(), 'web')->getJson('/administrator/crm/follow-ups/due')->json();
        $this->assertNotContains($lead->display_name, array_column($second['items'], 'name'));
    }

    public function test_import_paste_and_assign_and_export(): void
    {
        $agent = $this->agent();
        $n1 = '93'.random_int(10000000, 99999999);
        $n2 = '93'.random_int(10000000, 99999999);
        $this->actingAs($this->admin(), 'web')->post('/administrator/crm/import', [
            'method' => 'paste',
            'rows' => "Alpha Library, Raj, {$n1}, Kota\nBeta Study | {$n2} | Jaipur\nbad line 123\nAlpha again, {$n1}",
            'assigned_to' => $agent->id,
        ])->assertRedirect('/administrator/crm/import')->assertSessionHas('success');

        $a = CrmLead::where('mobile', $n1)->firstOrFail();
        $this->assertSame('Alpha Library', $a->library_name);
        $this->assertSame('Raj', $a->contact_name);
        $this->assertSame('Kota', $a->city);
        $this->assertSame($agent->id, $a->assigned_to);
        $this->assertSame('Jaipur', CrmLead::where('mobile', $n2)->value('city'));

        $this->actingAs($this->admin(), 'web')->post('/administrator/crm/leads/assign', ['ids' => [$a->id], 'agent_id' => 1])->assertSessionHas('success');
        $this->assertSame(1, $a->fresh()->assigned_to);

        $csv = $this->actingAs($this->admin(), 'web')->get('/administrator/crm/leads/export?q='.$n1)->streamedContent();
        $this->assertStringContainsString('Alpha Library', $csv);
    }

    public function test_csv_upload_with_header(): void
    {
        $n = '94'.random_int(10000000, 99999999);
        $path = tempnam(sys_get_temp_dir(), 'crm').'.csv';
        file_put_contents($path, "\xEF\xBB\xBFLibrary Name,Owner,Phone Number,City,Priority\nGamma Hall,Neha,+91 {$n},Pune,HOT\n");
        $file = new \Illuminate\Http\UploadedFile($path, 'leads.csv', 'text/csv', null, true);
        $this->actingAs($this->admin(), 'web')->post('/administrator/crm/import', ['method' => 'csv', 'file' => $file])->assertSessionHas('success');
        $l = CrmLead::where('mobile', $n)->firstOrFail();
        $this->assertSame(['Gamma Hall', 'Neha', 'Pune', 'hot'], [$l->library_name, $l->contact_name, $l->city, $l->priority]);
    }

    public function test_create_edit_and_settings(): void
    {
        $n = '96'.random_int(10000000, 99999999);
        $this->actingAs($this->admin(), 'web')->post('/administrator/crm/leads', [
            'library_name' => 'Delta', 'mobile' => $n, 'priority' => 'cold', 'source' => 'referral',
        ])->assertRedirect();
        $l = CrmLead::where('mobile', $n)->firstOrFail();
        $this->actingAs($this->admin(), 'web')->put('/administrator/crm/leads/'.$l->id, [
            'library_name' => 'Delta Library', 'mobile' => $n, 'priority' => 'hot', 'city' => 'Indore',
        ])->assertRedirect('/administrator/crm/leads/'.$l->id);
        $this->assertSame('Delta Library', $l->fresh()->library_name);

        $this->actingAs($this->admin(), 'web')->post('/administrator/crm/settings/stages', ['name' => 'Callback by owner', 'parent_id' => CrmStage::where('slug', 'follow-up')->value('id')])->assertSessionHas('success');
        $this->actingAs($this->admin(), 'web')->post('/administrator/crm/settings/templates', ['name' => 'T', 'channel' => 'sms', 'body' => 'Hi {name}'])->assertSessionHas('success');
        $this->actingAs($this->admin(), 'web')->post('/administrator/crm/settings/dispositions', ['name' => 'Asked brochure', 'is_connected' => 1, 'follow_up_minutes' => 60])->assertSessionHas('success');
    }
}
