<?php

namespace App\Http\Controllers\Api\V1\Learner;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Plan;
use App\Services\LearnerService;
use Illuminate\Http\Request;

class LearnerBranchController extends Controller
{
    /**
     * Public seat availability map for a branch (browse-before-booking).
     * Same data source as the staff seat-map (LearnerService::getSeatMapDetails,
     * branch_id already an explicit param there — no code duplicated), but with
     * every occupant's name/learner_no/pending_amount/due_date/etc. stripped:
     * getSeatMapDetails() is written for the staff dashboard and embeds full
     * learner PII/financial data per seat, which must never reach an
     * unauthenticated public endpoint. Only occupancy status is exposed here.
     */
    public function seatMap(Request $request, LearnerService $service)
    {
        $validated = $request->validate([
            'uuid' => 'required|string|exists:branches,uuid',
            'plan_type_id' => 'nullable|integer|exists:plan_types,id',
        ]);

        $branchId = resolveBranchId($validated['uuid']);

        try {
            $data = $service->getSeatMapDetails(
                $branchId,
                isset($validated['plan_type_id']) ? (int) $validated['plan_type_id'] : null,
                null
            );

            return response()->json([
                'status' => true,
                'message' => 'Seat map fetched successfully',
                'data' => [
                    'plan_type_status' => $data['plan_type_status'],
                    'numbered' => $this->stripLearnerPii($data['numbered']),
                    'general' => $this->stripLearnerPii($data['general']),
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    private function stripLearnerPii(array $floors): array
    {
        return collect($floors)->map(function ($floor) {
            $floor['seats'] = collect($floor['seats'] ?? [])->map(function ($seat) {
                $seat['plantype'] = collect($seat['plantype'] ?? [])->map(function ($planType) {
                    // 'future' carries a learner card (for the staff-side response) but the
                    // seat has no *current* occupant, so it must not read as booked here.
                    $planType['is_booked'] = $planType['learner'] !== null
                        && ($planType['plan_type_status'] ?? null) !== 'future';
                    unset($planType['learner']);

                    return $planType;
                })->values()->all();

                return $seat;
            })->values()->all();

            return $floor;
        })->values()->all();
    }

    /**
     * Same shape as the public MasterController@plans, just keyed by
     * branch_id (consistent with the other branch-browsing endpoints)
     * instead of requiring the app to already know library_id.
     */
    public function plans(Request $request)
    {
        $validated = $request->validate([
            'uuid' => 'required|string|exists:branches,uuid',
        ]);

        $libraryId = Branch::where('uuid', $validated['uuid'])->value('library_id');

        $plans = Plan::where('library_id', $libraryId)->get(['id', 'name']);

        return response()->json([
            'status' => true,
            'message' => 'Plans fetched successfully',
            'data' => $plans,
        ]);
    }
}
