<?php

namespace App\Http\Controllers;

use App\Models\Learner;
use App\Models\LearnerDetail;
use App\Models\LearnerTransaction;
use Illuminate\Http\Request;
use App\Services\LearnerDeleteService;
use Illuminate\Support\Facades\Validator;
use Exception;
use DB;

class LearnerDeleteController extends Controller
{
   protected $service;

    public function __construct(LearnerDeleteService $service)
    {
        $this->service = $service;
    }

    public function softDeleteV2Details(Request $request, $id)
    {
        try {
            $learner = Learner::withTrashed()
                ->where('id', $id)
                ->where('library_id', getLibraryId())
                ->when(getCurrentBranch(), fn ($q) => $q->where('branch_id', getCurrentBranch()))
                ->firstOrFail();

            $details = LearnerDetail::query()
                ->where('learner_id', $learner->id)
                ->whereNull('deleted_at')
                ->orderByDesc('id')
                ->get();

            $rows = $details->map(function ($detail) use ($learner) {
                $tx = LearnerTransaction::query()
                    ->where('learner_id', $learner->id)
                    ->where('learner_detail_id', $detail->id)
                    ->orderByDesc('id')
                    ->first();

                $paid = (float) ($tx->paid_amount ?? 0);
                $pending = (float) ($tx->pending_amount ?? 0);
                $used = max($paid - $pending, 0);
                $extra = (float) ($tx->refund); // refund is a pending refund which is extra amount

                return [
                    'id' => (int) $detail->id,
                    'seat_no' => $detail->seat_no ?? 'GEN',
                    'plan_name' => optional($detail->plan)->name ?? 'N/A',
                    'plan_type_name' => optional($detail->planType)->name ?? 'N/A',
                    'plan_start_date' => $detail->plan_start_date,
                    'plan_end_date' => $detail->plan_end_date,
                    'paid_amount' => $paid,
                    'used_amount' => $used,
                    'pending_amount' => $pending,
                    'extra_amount' => $extra,
                ];
            })->values();

            return response()->json([
                'success' => true,
                'learner' => [
                    'id' => (int) $learner->id,
                    'name' => $learner->name,
                    'learner_no' => $learner->learner_no,
                ],
                'details' => $rows,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * STEP 1 → Summary + Case Detection
     */
    public function prepare(Request $request, $learnerId)
    {
        $validator = Validator::make($request->all(), [
            'learner_detail_ids' => 'required|array|min:1',
            'learner_detail_ids.*' => 'integer|exists:learner_detail,id',
            'full_learner_delete' => 'nullable|in:0,1',

            'settlement_mode' => 'nullable|in:refund_pending_future,pending_pay_future,adjust',
            'is_refund' => 'nullable|in:0,1',
            'refund_amount' => 'nullable|numeric|min:0',
            'pay_amount' => 'nullable|numeric|min:0',
            'payment_mode' => 'nullable|in:1,2,3',
            'remark' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->errors()->first(),
            ], 422);
        }

        $totalDetails = LearnerDetail::where('learner_id', $learnerId)->count();

        $selectedDetails = LearnerDetail::where('learner_id', $learnerId)
            ->whereIn('id', $request->learner_detail_ids ?? [])
            ->count();

        if ($totalDetails > 0 && $totalDetails === $selectedDetails && $request->full_learner_delete != 1) {
            return response()->json([
                'error' => 'You selected all records. Please use "Full Learner Delete checkbox".'
            ], 422);
        }

        try {

            DB::beginTransaction();

            // ✅ STEP 2 (Settlement)
            $this->service->processSettlement($request, $learnerId);

            // ✅ FINAL DELETE
            $data = $this->service->executeDelete($request, $learnerId);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Deleted successfully',
                'data' => $data
            ]);

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

   
}
