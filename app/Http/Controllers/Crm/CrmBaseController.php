<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\Crm\CrmDisposition;
use App\Models\Crm\CrmReminder;
use App\Models\Crm\CrmStage;
use App\Models\Crm\CrmTemplate;
use App\Models\User;
use App\Services\Crm\CrmLeadService;
use App\Services\Crm\CrmSyncService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

/** Shared data for every Calling CRM screen (sheets, filters, permissions). */
abstract class CrmBaseController extends Controller
{
    public function __construct(protected CrmLeadService $leads, protected CrmSyncService $sync)
    {
        $this->middleware(function ($request, $next) {
            if ($request->isMethod('get') && ! $request->ajax()) {
                $this->sync->maybeSync();
                View::share('crm', $this->shared());
            }

            return $next($request);
        });
    }

    protected function user(): User
    {
        return Auth::guard('web')->user();
    }

    protected function shared(): array
    {
        $user = $this->user();
        $can = fn ($p) => CrmLeadService::can($user, $p);

        return [
            'stages' => CrmStage::tree(),
            'dispositions' => CrmDisposition::active()->get(),
            'templates' => CrmTemplate::active()->get(),
            'agents' => $can('crm-assign-leads') || CrmLeadService::seesAll($user) ? $this->leads->agents() : collect(),
            'seesAll' => CrmLeadService::seesAll($user),
            'dueCount' => CrmReminder::pending()->where('user_id', $user->id)->where('remind_at', '<=', now())
                ->whereHas('lead', fn ($q) => $q->open())->count(),
            'can' => [
                'dashboard' => $can('crm-view-dashboard'),
                'create' => $can('crm-create-leads'),
                'edit' => $can('crm-edit-leads'),
                'delete' => $can('crm-delete-leads'),
                'assign' => $can('crm-assign-leads'),
                'import' => $can('crm-import-leads'),
                'export' => $can('crm-export-leads'),
                'call' => $can('crm-log-calls'),
                'message' => $can('crm-send-messages'),
                'remind' => $can('crm-manage-reminders'),
                'settings' => $can('crm-manage-settings'),
            ],
        ];
    }
}
