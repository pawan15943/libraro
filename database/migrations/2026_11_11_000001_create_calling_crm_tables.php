<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Calling CRM of the platform admin portal (docs/CALLING_CRM.md).
 * Leads with stages / sub-stages, every call with its disposition, follow-up
 * reminders, a timeline per lead, WhatsApp / SMS templates and imports.
 * Also adds the crm-* permissions, the "CRM Manager" and "Calling Agent"
 * roles, and gives every crm-* permission to superadmin.
 */
return new class extends Migration
{
    private const PERMISSIONS = [
        'crm-view-dashboard' => 'See the lead dashboard and team performance',
        'crm-view-leads' => 'Work the call queue: own and unassigned leads',
        'crm-view-all-leads' => 'See every agent\'s leads, calls and reports',
        'crm-create-leads' => 'Add a lead by hand',
        'crm-edit-leads' => 'Edit a lead and change its stage / status',
        'crm-delete-leads' => 'Delete a lead',
        'crm-assign-leads' => 'Assign leads to agents',
        'crm-import-leads' => 'Upload leads from a CSV file or a pasted list',
        'crm-export-leads' => 'Download leads as CSV',
        'crm-log-calls' => 'Call a lead and log the call with its disposition',
        'crm-send-messages' => 'Send WhatsApp and text message reminders',
        'crm-manage-reminders' => 'Set and close follow-up reminders',
        'crm-manage-settings' => 'Manage stages, sub-stages, dispositions and templates',
    ];

    private const AGENT_PERMISSIONS = [
        'crm-view-dashboard', 'crm-view-leads', 'crm-create-leads', 'crm-edit-leads',
        'crm-log-calls', 'crm-send-messages', 'crm-manage-reminders',
    ];

    public function up(): void
    {
        Schema::create('crm_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('crm_stages')->cascadeOnDelete();
            $table->string('name', 80);
            $table->string('slug', 100)->unique();
            $table->string('color', 9)->default('#64748b');
            $table->unsignedSmallInteger('sort')->default(0);
            $table->boolean('is_closed')->default(false); // closed leads never show in the call queue
            $table->boolean('is_won')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('crm_dispositions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('slug', 100)->unique();
            $table->boolean('is_connected')->default(true);
            $table->foreignId('stage_id')->nullable()->constrained('crm_stages')->nullOnDelete();
            $table->foreignId('sub_stage_id')->nullable()->constrained('crm_stages')->nullOnDelete();
            $table->unsignedInteger('follow_up_minutes')->nullable(); // suggested next call
            $table->boolean('requires_follow_up')->default(false);
            $table->string('color', 9)->default('#64748b');
            $table->unsignedSmallInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('crm_imports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('file_name')->nullable();
            $table->string('method', 10)->default('csv'); // csv | paste
            $table->unsignedInteger('total')->default(0);
            $table->unsignedInteger('created')->default(0);
            $table->unsignedInteger('duplicates')->default(0);
            $table->unsignedInteger('invalid')->default(0);
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->timestamps();
        });

        Schema::create('crm_leads', function (Blueprint $table) {
            $table->id();
            $table->string('contact_name')->nullable();
            $table->string('library_name')->nullable();
            $table->string('mobile', 15)->unique(); // last 10 digits
            $table->string('alt_mobile', 15)->nullable();
            $table->string('email')->nullable();
            $table->string('city', 100)->nullable()->index();
            $table->string('state', 100)->nullable();
            $table->text('address')->nullable();
            $table->string('source', 30)->default('manual')->index();
            $table->unsignedBigInteger('library_id')->nullable()->index();
            $table->foreignId('stage_id')->constrained('crm_stages');
            $table->foreignId('sub_stage_id')->nullable()->constrained('crm_stages')->nullOnDelete();
            $table->string('priority', 10)->default('warm')->index(); // hot | warm | cold
            $table->unsignedBigInteger('assigned_to')->nullable()->index();
            $table->timestamp('next_follow_up_at')->nullable()->index();
            $table->timestamp('last_called_at')->nullable()->index();
            $table->foreignId('last_disposition_id')->nullable()->constrained('crm_dispositions')->nullOnDelete();
            $table->unsignedInteger('calls_count')->default(0);
            $table->unsignedInteger('connected_count')->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('closed_at')->nullable()->index();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreignId('import_id')->nullable()->constrained('crm_imports')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['stage_id', 'assigned_to']);
        });

        Schema::create('crm_calls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('crm_leads')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id')->index();
            $table->foreignId('disposition_id')->nullable()->constrained('crm_dispositions')->nullOnDelete();
            $table->boolean('is_connected')->default(false);
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('stage_from_id')->nullable();
            $table->unsignedBigInteger('stage_to_id')->nullable();
            $table->unsignedBigInteger('sub_stage_to_id')->nullable();
            $table->timestamp('follow_up_at')->nullable();
            $table->timestamp('called_at')->useCurrent()->index();
            $table->timestamps();
            $table->index(['user_id', 'called_at']);
        });

        Schema::create('crm_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('crm_leads')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id')->index();
            $table->foreignId('call_id')->nullable()->constrained('crm_calls')->nullOnDelete();
            $table->timestamp('remind_at')->useCurrent()->index();
            $table->string('note')->nullable();
            $table->string('status', 12)->default('pending')->index(); // pending | done | cancelled
            $table->timestamp('notified_at')->nullable();
            $table->timestamp('done_at')->nullable();
            $table->timestamps();
        });

        Schema::create('crm_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('crm_leads')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('type', 30)->index(); // created | call | stage | note | whatsapp | sms | assigned | reminder | import | sync | edit
            $table->string('title');
            $table->text('body')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->index(['lead_id', 'created_at']);
        });

        Schema::create('crm_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('channel', 10)->index(); // whatsapp | sms
            $table->text('body');
            $table->unsignedSmallInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $this->seedStages();
        $this->seedDispositions();
        $this->seedTemplates();
        $this->seedPermissions();
        $this->copyOldLeads();
    }

    public function down(): void
    {
        foreach (['crm_templates', 'crm_activities', 'crm_reminders', 'crm_calls', 'crm_leads', 'crm_imports', 'crm_dispositions', 'crm_stages'] as $t) {
            Schema::dropIfExists($t);
        }

        $ids = DB::table('permissions')->where('guard_name', 'web')->whereIn('name', array_keys(self::PERMISSIONS))->pluck('id');
        DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('model_has_permissions')->whereIn('permission_id', $ids)->delete();
        if (Schema::hasTable('admin_user_permissions')) {
            DB::table('admin_user_permissions')->whereIn('permission_id', $ids)->delete();
        }
        DB::table('permissions')->whereIn('id', $ids)->delete();
        DB::table('roles')->where('guard_name', 'web')->whereIn('name', ['CRM Manager', 'Calling Agent'])->delete();
        DB::table('permission_categories')->where('name', 'Calling CRM')->delete();
        $this->forgetPermissionCache();
    }

    private function seedStages(): void
    {
        // [name, colour, closed, won, sub-stages]
        $stages = [
            ['New', '#2563eb', false, false, ['Fresh lead', 'Uploaded', 'Registered – not paid', 'Website / demo request']],
            ['Attempting', '#64748b', false, false, ['No answer', 'Busy', 'Switched off / not reachable', 'Asked to call back']],
            ['Interested', '#7c3aed', false, false, ['Hot – ready to buy', 'Warm – evaluating', 'Wants demo', 'Price discussion']],
            ['Demo', '#0891b2', false, false, ['Demo scheduled', 'Demo done', 'Trial account given']],
            ['Follow-up', '#d97706', false, false, ['Thinking', 'Comparing other software', 'Budget issue', 'Decision maker not available']],
            ['Enrolled', '#16a34a', true, true, ['Paid online', 'Paid offline', 'Trial converted']],
            ['Not Interested', '#dc2626', true, false, ['Using other software', 'Excel / manual is enough', 'Too costly', 'Library closed', 'Not a library owner']],
            ['Invalid', '#6b7280', true, false, ['Wrong number', 'Duplicate', 'Junk']],
        ];

        $now = now();
        foreach ($stages as $i => [$name, $color, $closed, $won, $subs]) {
            $id = DB::table('crm_stages')->insertGetId([
                'name' => $name, 'slug' => Str::slug($name), 'color' => $color, 'sort' => ($i + 1) * 10,
                'is_closed' => $closed, 'is_won' => $won, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
            ]);
            foreach ($subs as $j => $sub) {
                DB::table('crm_stages')->insert([
                    'parent_id' => $id, 'name' => $sub, 'slug' => Str::slug($name.' '.$sub), 'color' => $color, 'sort' => ($j + 1) * 10,
                    'is_closed' => $closed, 'is_won' => $won, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }
    }

    private function seedDispositions(): void
    {
        $stage = fn (string $slug) => DB::table('crm_stages')->where('slug', $slug)->value('id');

        // [name, connected, stage slug, sub-stage slug, follow-up minutes, requires follow-up, colour]
        $rows = [
            ['Interested', true, 'interested', 'interested-warm-evaluating', 1440, true, '#7c3aed'],
            ['Wants demo', true, 'demo', 'demo-demo-scheduled', 1440, true, '#0891b2'],
            ['Call back later', true, 'attempting', 'attempting-asked-to-call-back', 120, true, '#d97706'],
            ['Thinking / will decide', true, 'follow-up', 'follow-up-thinking', 2880, true, '#d97706'],
            ['Price issue', true, 'follow-up', 'follow-up-budget-issue', 4320, true, '#d97706'],
            ['Enrolled / paid', true, 'enrolled', 'enrolled-paid-online', null, false, '#16a34a'],
            ['Not interested', true, 'not-interested', null, null, false, '#dc2626'],
            ['Using other software', true, 'not-interested', 'not-interested-using-other-software', null, false, '#dc2626'],
            ['No answer', false, 'attempting', 'attempting-no-answer', 180, true, '#64748b'],
            ['Busy', false, 'attempting', 'attempting-busy', 60, true, '#64748b'],
            ['Switched off', false, 'attempting', 'attempting-switched-off-not-reachable', 240, true, '#64748b'],
            ['Call disconnected', false, 'attempting', null, 30, true, '#64748b'],
            ['Wrong number', false, 'invalid', 'invalid-wrong-number', null, false, '#6b7280'],
        ];

        $now = now();
        foreach ($rows as $i => [$name, $connected, $stageSlug, $subSlug, $minutes, $requires, $color]) {
            DB::table('crm_dispositions')->insert([
                'name' => $name, 'slug' => Str::slug($name), 'is_connected' => $connected,
                'stage_id' => $stage($stageSlug), 'sub_stage_id' => $subSlug ? $stage($subSlug) : null,
                'follow_up_minutes' => $minutes, 'requires_follow_up' => $requires, 'color' => $color,
                'sort' => ($i + 1) * 10, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    private function seedTemplates(): void
    {
        $rows = [
            ['Introduction', 'whatsapp', "Hi {name}, this is {agent} from Libraro 👋\n\nLibraro runs your library: seat booking, fees, attendance, receipts and WhatsApp reminders — from your phone.\n\nTry it free: {login_link}\nPlans start at just ₹49/month.\n\nShall I show you a quick demo?"],
            ['Complete your payment', 'whatsapp', "Hi {name}, your library *{library}* is registered on Libraro but the plan is not active yet.\n\nComplete the payment here to start using it: {login_link}\n\nNeed help? Just reply to this message. — {agent}, Libraro"],
            ['Follow-up', 'whatsapp', 'Hi {name}, {agent} from Libraro here. Following up on our call about {library}. Is now a good time to take this forward?'],
            ['Demo reminder', 'whatsapp', 'Hi {name}, a reminder of your Libraro demo. I will call you as planned. — {agent}'],
            ['Payment reminder', 'sms', 'Hi {name}, your Libraro registration for {library} is pending payment. Activate now: {login_link} - Libraro'],
            ['Call back', 'sms', 'Hi {name}, {agent} from Libraro tried calling you. Please let us know a good time to talk. - Libraro'],
        ];

        $now = now();
        foreach ($rows as $i => [$name, $channel, $body]) {
            DB::table('crm_templates')->insert([
                'name' => $name, 'channel' => $channel, 'body' => $body, 'sort' => ($i + 1) * 10,
                'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    private function seedPermissions(): void
    {
        $now = now();
        $categoryId = DB::table('permission_categories')->where('name', 'Calling CRM')->value('id')
            ?? DB::table('permission_categories')->insertGetId(['name' => 'Calling CRM', 'created_at' => $now, 'updated_at' => $now]);

        $ids = [];
        foreach (self::PERMISSIONS as $name => $description) {
            $id = DB::table('permissions')->where('name', $name)->where('guard_name', 'web')->value('id');
            if (! $id) {
                $id = DB::table('permissions')->insertGetId([
                    'name' => $name, 'slug' => $name, 'guard_name' => 'web', 'permission_category_id' => $categoryId,
                    'description' => $description, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
            $ids[$name] = $id;
        }

        $grant = function (string $role, array $names) use ($ids, $now) {
            $roleId = DB::table('roles')->where('name', $role)->where('guard_name', 'web')->value('id')
                ?? DB::table('roles')->insertGetId(['name' => $role, 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now]);
            foreach ($names as $name) {
                DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $ids[$name], 'role_id' => $roleId]);
            }
        };

        $grant('CRM Manager', array_keys(self::PERMISSIONS));
        $grant('Calling Agent', self::AGENT_PERMISSIONS);
        if (DB::table('roles')->where('name', 'superadmin')->where('guard_name', 'web')->exists()) {
            $grant('superadmin', array_keys(self::PERMISSIONS));
        }

        $this->forgetPermissionCache();
    }

    /** Leads of the old /leads page (lead_contacts) move over with their comments. */
    private function copyOldLeads(): void
    {
        if (! Schema::hasTable('lead_contacts')) {
            return;
        }

        $new = DB::table('crm_stages')->where('slug', 'new-uploaded')->first();
        $now = now();
        foreach (DB::table('lead_contacts')->orderBy('id')->get() as $old) {
            $mobile = substr(preg_replace('/\D/', '', (string) $old->mobile), -10);
            if (strlen($mobile) !== 10 || DB::table('crm_leads')->where('mobile', $mobile)->exists()) {
                continue;
            }
            $leadId = DB::table('crm_leads')->insertGetId([
                'contact_name' => $old->name ?? null, 'library_name' => $old->library_name, 'mobile' => $mobile,
                'city' => $old->city, 'source' => 'old-leads', 'stage_id' => $new->parent_id, 'sub_stage_id' => $new->id,
                'notes' => trim(($old->lead_status ? 'Old lead status: '.$old->lead_status : '').($old->status ? ' · call status: '.$old->status : '')) ?: null,
                'created_at' => $old->created_at ?? $now, 'updated_at' => $now,
            ]);
            foreach ((array) json_decode((string) $old->comments, true) as $c) {
                if (! empty($c['comment'])) {
                    DB::table('crm_activities')->insert([
                        'lead_id' => $leadId, 'type' => 'note', 'title' => 'Note (old leads page)', 'body' => $c['comment'],
                        'created_at' => $c['time'] ?? $now, 'updated_at' => $now,
                    ]);
                }
            }
        }
    }

    private function forgetPermissionCache(): void
    {
        try {
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        } catch (\Throwable $e) {
            // cache store not writable from the CLI: the cache expires by itself
        }
    }
};
