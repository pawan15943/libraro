<?php

namespace App\Http\Controllers\Crm;

use App\Services\Crm\CrmDashboardService;
use Illuminate\Http\Request;

class CrmDashboardController extends CrmBaseController
{
    public const RANGES = ['today' => 'Today', 'yesterday' => 'Yesterday', 'week' => 'This week', 'month' => 'This month', '30' => 'Last 30 days', 'custom' => 'Custom'];

    public function index(Request $request, CrmDashboardService $dashboard)
    {
        $range = array_key_exists($request->get('range'), self::RANGES) ? $request->get('range') : 'week';
        [$from, $to] = CrmCallController::range($range, $request->get('from'), $request->get('to'));
        $agentId = $request->integer('agent') ?: null;

        return view('crm.dashboard', [
            'd' => $dashboard->build($this->user(), $from, $to, $agentId),
            'range' => $range,
            'from' => $from,
            'to' => $to,
            'agentId' => $agentId,
        ]);
    }
}
