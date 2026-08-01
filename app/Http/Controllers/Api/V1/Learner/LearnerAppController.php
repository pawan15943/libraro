<?php

namespace App\Http\Controllers\Api\V1\Learner;

use App\DTO\LearnerOperationDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\LearnerRenewRequest;
use App\Models\Branch;
use App\Models\Feature;
use App\Models\LearnerDetail;
use App\Services\AttendanceService;
use App\Services\LearnerOperationService;
use App\Services\LearnerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LearnerAppController extends Controller
{
    /**
     * Self-service learner detail — same payload shape as the staff
     * "library/learners/detail" API (LearnerService::getLearnerDetails()),
     * scoped to the authenticated learner's own record.
     */
    public function detail(LearnerService $service)
    {
        try {
            return response()->json([
                'status' => true,
                'data' => $service->getLearnerDetails(auth('learner_api')->id()),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Self-service renew — same fields/engine as the staff
     * "library/learners/operation" (payment_type=RENEW) flow, but
     * learner_id/payment_type are forced from the auth token
     * (see LearnerRenewRequest) so a learner can only renew their own seat.
     */
    public function renew(LearnerRenewRequest $request, LearnerOperationService $service)
    {
        $dto = LearnerOperationDTO::fromRequest($request);

        return response()->json($service->process($dto));
    }

    /**
     * Mirrors DashboardController::learnerDashboard() (web) as JSON.
     */
    public function dashboard()
    {
        $learner = auth('learner_api')->user();

        $learners = LearnerDetail::withoutGlobalScopes()
            ->where('learner_id', $learner->id)
            ->leftJoin('plans', 'learner_detail.plan_id', '=', 'plans.id')
            ->leftJoin('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')
            ->select('learner_detail.*', 'plans.name as plan_name', 'plan_types.name as plan_type_name')
            ->get();

        $branch = Branch::where('id', $learner->branch_id)
            ->select('name as library_name', 'features')
            ->first();

        $learnerRequest = DB::table('learner_request')->where('learner_id', $learner->id)->get();

        $featuresArray = $branch && $branch->features
            ? (is_array($branch->features) ? $branch->features : json_decode($branch->features, true))
            : [];

        $features = Feature::whereIn('id', $featuresArray)->get();

        return response()->json([
            'status' => true,
            'data' => [
                'learner_details' => $learners,
                'library_name' => optional($branch)->library_name,
                'features' => $features,
                'learner_requests' => $learnerRequest,
            ],
        ]);
    }

    /**
     * Self-service per-day punch log — reuses AttendanceService::attendanceLogs()
     * unchanged, forcing learner_id from the auth token.
     */
    public function attendanceLogs(Request $request, AttendanceService $service)
    {
        $request->merge([
            'learner_id' => auth('learner_api')->id(),
            'date' => $request->input('date', today()->toDateString()),
        ]);

        return $service->attendanceLogs($request);
    }
}
