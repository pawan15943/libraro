<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLearnerRequest;
use App\Services\LearnerGiftDaysService;
use App\Services\LearnerLifecycleService;
use App\Services\LearnerService;
use Illuminate\Http\Request;
use App\DTO\LearnerOperationDTO;
use App\Enums\LearnerOperation;
use App\Http\Requests\LearnerOperationRequest;
use App\Models\Hour;
use App\Models\Learner;
use App\Models\LearnerDetail;
use App\Services\LearnerOperationService;
use App\Services\LearnerSeatSwapService;
use App\Services\SeatAvailabilityService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class LearnerController extends Controller
{
    public function store(StoreLearnerRequest $request, LearnerService $service)
    {
       

        try {

            $processData = $request->prepareData();

            $result = $service->processLearnerStore($processData);

            return response()->json([
                'status'  => $result['success'],   
              
                'message' => $result['message'],
              
            ]);


        } catch (\Exception $e) {

            \Log::error("Learner Create Error: ".$e->getMessage(),[
                'line'=>$e->getLine(),
                'file'=>$e->getFile()
            ]);

            return response()->json([
                'status'=>false,
                'message'=>'Something went wrong while creating learner!'
            ],500);
        }
    }
  

    public function show(Request $request, LearnerService $service)
    {
        try {

            $id = $request->id; // or $request->input('id');

            if (!$id) {
                return response()->json([
                    'status' => false,
                    'message' => 'Learner id is required'
                ], 400);
            }

            $data = $service->getLearnerDetails($id);

            return response()->json([
                'status' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 404);
        }
    }
    public function index(Request $request, LearnerService $service)
    {
        try {

            // map page_no to page
            if ($request->has('page_no')) {
                $request->merge([
                    'page' => $request->page_no
                ]);
            }

            $filters = [
                'search' => $request->search,
                'status' => $request->status
            ];

            $data = $service->getLearnersList($filters);

            return response()->json([
                'status' => true,
                'data' => $data->items()
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function process(LearnerOperationRequest $request, LearnerOperationService $service)
    {

         $dto = LearnerOperationDTO::fromRequest($request);
        
        if($dto->operation == 'EDITLEARNER'){
            $service->updateLearner($dto);

            return response()->json([
                'status' => true,
                'message' => 'Learner updated successfully',
            ]);
            
         }else{
             return response()->json(
                $service->process($dto)
            );
         }
       

    }

    public function closeDelete(Request $request, LearnerLifecycleService $service)
    {
        $refundSelected = null;
        if ($request->has('isRefund')) {
            $refundSelected = filter_var($request->input('isRefund'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            if ($refundSelected !== null) {
                $request->merge([
                    'isRefund' => $refundSelected,
                ]);
            }
        }

         $validator = Validator::make($request->all(), [
            'learner_id' => 'required|integer|exists:learners,id',
            'operation' => 'required|in:close,delete',
            'isRefund' => 'required|boolean',
            'refund_amount' => [Rule::requiredIf($refundSelected === true), 'nullable', 'numeric', 'min:0'],
            'payment_mode' => [Rule::requiredIf($refundSelected === true), 'nullable', 'in:1,2,3'],
            'pendind_refund' => 'nullable|numeric|min:0',
            'pending_refund' => 'nullable|numeric|min:0',
            // 'transaction' => [Rule::requiredIf($request->input('operation') === 'delete'), 'nullable', 'in:current,all'],
            'remark' => 'nullable|string|max:1000',
        ], [
            'learner_id.required' => 'Learner id is required.',
            'learner_id.exists' => 'Learner not found.',
            'operation.required' => 'Operation is required.',
            'operation.in' => 'Operation must be close or delete.',
            'isRefund.required' => 'Refund option is required.',
            'isRefund.boolean' => 'Refund option must be true or false.',
            'refund_amount.required' => 'Refund amount is required when refund is selected.',
            'refund_amount.numeric' => 'Refund amount must be a valid number.',
            'payment_mode.required' => 'Payment mode is required when refund is selected.',
            'payment_mode.in' => 'Payment mode must be 1, 2, or 3.',
            'pendind_refund.numeric' => 'Pending refund amount must be a valid number.',
            'pending_refund.numeric' => 'Pending refund amount must be a valid number.',
            // 'transaction.required' => 'Transaction option is required when delete is selected.',
            // 'transaction.in' => 'Transaction option must be current or all.',
        ]);

        $validator->after(function ($validator) use ($request, $refundSelected) {
            if ($refundSelected && ! $request->filled('pendind_refund') && ! $request->filled('pending_refund')) {
                $validator->errors()->add('pending_refund', 'Pending refund amount is required when refund is selected.');
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $validated = $validator->validated();
        $validated['isRefund'] = $refundSelected;

        if (! isset($validated['pendind_refund']) && array_key_exists('pending_refund', $validated)) {
            $validated['pendind_refund'] = $validated['pending_refund'];
        }

        try {
            $result = $service->closedelete($validated);

            return response()->json([
                'status' => $result['ok'],
                'message' => $result['message'],
            ], $result['ok'] ? 200 : 422);

        } catch (\Throwable $e) {
            \Log::error('Learner close API error: '.$e->getMessage(), [
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'An error occurred while closing learner: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Swap-seat availability — same rules as web `getSeatStatus` via SeatAvailabilityService.
     */
    public function seatStatus(Request $request, SeatAvailabilityService $seatAvailability)
    {
        $validated = $request->validate([
            'new_seat_id' => 'required',
            'learner_id' => 'required',
            'plan_type_id' => 'required',
        ]);

        $code = $seatAvailability->getSwapSeatStatusCode(
            $validated['new_seat_id'],
            $validated['learner_id'],
            $validated['plan_type_id']
        );

        return response()->json([
            'status' => true,
            'code' => $code,
        ]);
    }

    /**
     * Persist seat swap — same rules as web `learners.swap-seat` via LearnerSeatSwapService.
     */
    public function swapSeat(Request $request, LearnerSeatSwapService $swapService)
    {
        $validated = $request->validate([
            'learner_id' => 'required',
            'seat_id' => 'required',
        ]);

        try {
            $swapService->swap($validated['learner_id'], $validated['seat_id']);

            return response()->json([
                'status' => true,
                'message' => 'Seat swapped successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Seats available to swap for the learner’s plan type (same rules as `seatStatus`).
     * Each item is a full `generateSeatNumbers2` row: main, floor, floor_name, floor_no, display.
     */
    public function getAvailableSeat(Request $request, SeatAvailabilityService $seatAvailability)
    {
        $validated = $request->validate([
            'learner_id' => 'required',
        ]);

        $learner = Learner::query()
            ->select('id', 'branch_id')
            ->addSelect([
                'plan_type_id' => LearnerDetail::query()
                    ->select('plan_type_id')
                    ->whereColumn('learner_id', 'learners.id')
                    ->orderByDesc('id')
                    ->limit(1),
            ])
            ->where('id', $validated['learner_id'])
            ->first();

        if (! $learner) {
            return response()->json([
                'status' => false,
                'message' => 'Learner not found',
            ], 404);
        }

        if ($learner->plan_type_id === null) {
            return response()->json([
                'status' => false,
                'message' => 'Plan type not found for this learner',
            ], 422);
        }

        $totalSeats = (int) (Hour::withoutGlobalScopes()
            ->where('branch_id', $learner->branch_id)
            ->value('seats') ?? 0);

        if ($totalSeats <= 0) {
            return response()->json([
                'status' => true,
                'data' => [],
            ]);
        }

        $codeMap = $seatAvailability->getSwapSeatStatusCodesMap(
            (int) $learner->id,
            (int) $learner->plan_type_id,
            $totalSeats
        );

        $availableSeatNos = [];
        foreach ($codeMap as $seatNo => $code) {
            if ($code === 1) {
                $availableSeatNos[] = (int) $seatNo;
            }
        }

        $byMain = collect(generateSeatNumbers2((int) $learner->branch_id))->keyBy('main');
        $data = collect($availableSeatNos)
            ->map(fn (int $n) => $byMain->get($n))
            ->filter()
            ->values()
            ->all();

        return response()->json([
            'status' => true,
            'data' => $data,
        ]);
    }

    public function seatMap(Request $request, LearnerService $service)
    {
        $validator = Validator::make($request->all(), [
            'plan_type_id' => 'nullable|integer|exists:plan_types,id',
            'plan_type_status' => 'nullable|integer|in:1,2,3,4,5,6,7',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $validated = $validator->validated();
        $branch_id=getCurrentBranch();
        try {
            $data = $service->getSeatMapDetails(
                $branch_id ? (int) $branch_id : null,
                isset($validated['plan_type_id']) ? (int) $validated['plan_type_id'] : null,
                isset($validated['plan_type_status']) ? (int) $validated['plan_type_status'] : null
            );

            return response()->json([
                'status' => true,
                'message' => 'Seat Map details fetched successfully',
                'data' => $data,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Assign or update total gift days (same rules as web assign-gift-days).
     * Blocked when learner frozen_status is 1.
     */
    public function assignGiftDays(Request $request, LearnerGiftDaysService $giftDaysService)
    {
        $validated = $request->validate([
            'learner_id' => 'required|integer',
            'gift_days' => 'required|integer',
        ]);

        $result = $giftDaysService->assign(
            (int) $validated['learner_id'],
            (int) $validated['gift_days']
        );

        if (! $result['ok']) {
            return response()->json([
                'status' => false,
                'message' => $result['message'],
            ], $result['status_code'] ?? 422);
        }

        return response()->json([
            'status' => true,
            'message' => $result['message'],
        ]);
    }

    public function settlement(Request $request, LearnerService $service)
    {
        if ($request->has('adjust')) {
            $adjust = filter_var($request->input('adjust'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            if ($adjust !== null) {
                $request->merge([
                    'adjust' => $adjust,
                ]);
            }
        }

        $validated = $request->validate([
            'learner_id' => 'required|integer|exists:learners,id',
            'pending_amount' => 'nullable|numeric|min:0',
            'refund_amount' => 'nullable|numeric|min:0',
            'payment_mode' => 'required|in:1,2,3',
            'adjust' => 'required|boolean',
        ]);

        if (($validated['pending_amount'] ?? 0) <= 0 && ($validated['refund_amount'] ?? 0) <= 0 && ! $validated['adjust']) {
            return response()->json([
                'status' => false,
                'message' => 'Pending amount or refund amount is required when adjust is false.',
            ], 422);
        }

        try {
            $result = $service->settlement((object) $validated);

            return response()->json([
                'status' => true,
                'message' => $result['message'],
                // 'data' => $result['settlement'],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Single entry: operation + learner_id (optional delete_all for permanent_delete).
     * Operations: restore, permanent_delete, freeze, unfreeze
     */
    public function lifecycle(Request $request, LearnerLifecycleService $service)
    {
        if ($request->has('with_revenue')) {
            $withRevenue = filter_var($request->input('with_revenue'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            if ($withRevenue !== null) {
                $request->merge([
                    'with_revenue' => $withRevenue,
                ]);
            }
        }

        $validated = $request->validate([
            'operation'   => 'required|in:restore,permanent_delete,freeze,unfreeze',
            'learner_id'  => 'required|integer|exists:learners,id',
            'with_revenue' => [
                Rule::requiredIf($request->input('operation') === 'permanent_delete'),
                'boolean',
            ],
        ]);

        $result = $service->run(
            $validated['operation'],
            (int) $validated['learner_id'],
            [
                'with_revenue' => $validated['with_revenue'] ?? true,
            ]
        );

        $status = $result['ok'] ? 200 : 422;
        $body = [
            'status'  => $result['ok'],
            'message' => $result['message'],
        ];
        if (array_key_exists('frozen_days', $result)) {
            $body['frozen_days'] = $result['frozen_days'];
        }

        return response()->json($body, $status);
    }

    public function otherPaymentApi(Request $request, LearnerLifecycleService $service)
    {
         $validated = $request->validate([
          'learner_id'    => 'required|exists:learner_transactions,learner_id',
            'payment_type'  => 'required|in:token_money,miscellaneous,pending_refund',
            'fees'          => 'required|numeric|min:1',
        ]);



        $response = $service->handleLearnerOtherPayment($request);

        return response()->json($response);
    }

    public function transactions(Request $request, LearnerLifecycleService $service)
    {
        $validator = Validator::make($request->all(), [
        
            'learner_id' => 'required|integer|exists:learners,id',
        ]);

        if ($validator->fails()) {

            return response()->json([

                'status' => false,

                'message' => $validator->errors()->first(),
            ]);
        }

        try {

            $validated = $validator->validated();

            return response()->json([

                'status' => true,

                'data' => $service->transactionTabs((int) $validated['learner_id']),
            ]);

        } catch (\Throwable $e) {

            return response()->json([

                'status' => false,

                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function transactionsDetail(Request $request, LearnerLifecycleService $service)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:learner_transactions,id',
            'paid_amount' => 'nullable|numeric|min:0',
            'due_date' => 'nullable|date',
            'paid_date' => 'nullable|date',
            'payment_mode' => 'nullable|in:1,2,3',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ]);
        }

        try {
            $validated = $validator->validated();
            $id = (int) $validated['id'];
            unset($validated['id']);

            $data = empty($validated)
                ? $service->transactionDetail($id)
                : $service->updateTransaction($id, $validated);

            return response()->json([
                'status' => true,
                'message' => empty($validated) ? 'Transaction detail.' : 'Transaction updated successfully.',
                'data' => $data,
                
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function transactionsDelete(Request $request, LearnerLifecycleService $service)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:learner_transactions,id',
            'confirm' => 'nullable|in:delete,Delete,DELETE',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ]);
        }

        if ($request->filled('confirm') && strtolower($request->confirm) !== 'delete') {
            return response()->json([
                'status' => false,
                'message' => 'Type delete to confirm this operation.',
            ]);
        }

        try {
            $service->deleteTransaction((int) $request->id);

            return response()->json([
                'status' => true,
                'message' => 'Transaction deleted successfully.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function transactionsActivityDetail(Request $request, LearnerLifecycleService $service)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:learner_transaction_activity,id',
            'payment_date' => 'nullable|date',
            'paid_amount' => 'nullable|numeric|min:0',
            'payment_mode' => 'nullable|in:1,2,3',
          
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ]);
        }

        try {
            $validated = $validator->validated();
            $id = (int) $validated['id'];
            unset($validated['id']);

            $data = empty($validated)
                ? $service->transactionActivityDetail($id)
                : $service->updateTransactionActivity($id, $validated);

            return response()->json([
                'status' => true,
                'data' => $data,
                'message' => empty($validated) ? 'Transaction activity detail.' : 'Transaction activity updated successfully.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function transactionsActivityDelete(Request $request, LearnerLifecycleService $service)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:learner_transaction_activity,id',
           'confirm' => 'nullable|in:delete,Delete,DELETE',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ]);
        }

         if ($request->filled('confirm') && strtolower($request->confirm) !== 'delete') {
            return response()->json([
                'status' => false,
                'message' => 'Type delete to confirm this operation.',
            ]);
        }

        try {
            $service->deleteTransactionActivity((int) $request->id);

            return response()->json([
                'status' => true,
                'message' => 'Transaction activity deleted successfully.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
    
}
