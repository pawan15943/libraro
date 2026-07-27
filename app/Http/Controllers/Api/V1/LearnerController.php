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
use App\Helpers\HelperService;
use App\Models\Hour;
use App\Models\Learner;
use App\Models\LearnerDetail;
use App\Models\LearnerOperationsLog;
use App\Models\Plan;
use App\Models\PlanType;
use App\Services\LearnerOperationService;
use App\Services\LearnerSeatSwapService;
use App\Services\DashboardService;
use App\Services\SeatAvailabilityService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class LearnerController extends Controller
{
    public function store(StoreLearnerRequest $request, LearnerService $service)
    {
        if ($denied = $this->denyWithoutAppPermission(['Book Seat', 'book-seat'])) {
            return $denied;
        }

        try {

            $processData = $request->prepareData();
            Log::info([
                'processData'=>$processData
            ]);

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
                ]);
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
            $request->validate([
                'status' => 'nullable|in:all,active,about_to_expire,extended,future,expired,deleted,closed,pending_payment,non_expiry',
                'plan_type_id' => 'nullable',
                'plan_type_id.*' => 'integer|exists:plan_types,id',
                'sort_by' => 'nullable|in:seat_no,name,expire_date,gen',
                'sort_order' => 'nullable|in:asc,desc',
                'from_date' => 'nullable|date',
                'to_date' => 'nullable|date|after_or_equal:from_date',
            ]);

            // map page_no to page
            if ($request->has('page_no')) {
                $request->merge([
                    'page' => $request->page_no
                ]);
            }

            $filters = [
                'search' => $request->search,
                'status' => $request->status,
                'plan_type_id' => is_array($request->plan_type_id)
                    ? $request->plan_type_id
                    : (isset($request->plan_type_id) ? [(int) $request->plan_type_id] : []),
                'sort_by' => $request->sort_by,
                'sort_order' => $request->sort_order,
                'from_date' => $request->from_date,
                'to_date' => $request->to_date,
            ];

            $data = $service->getLearnersList($filters);

            return response()->json([
                'status' => true,
                'list_count' => $data->total(),
                'data' => $data->items()
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function pendingPayment(Request $request, LearnerService $service, DashboardService $dashboardService)
    {
        try {
            $request->validate([
                'search' => 'nullable|string',
                'filter' => 'nullable|in:all,pending,adjusted,overdue,expired,received',
                'plan_type_id' => 'nullable',
                'plan_type_id.*' => 'integer|exists:plan_types,id',
                'sort_by' => 'nullable|in:seat_no,name,expire_date,gen',
                'sort_order' => 'nullable|in:asc,desc',
                'from_date' => 'nullable|date',
                'to_date' => 'nullable|date|after_or_equal:from_date',
            ]);

            if ($request->has('page_no')) {
                $request->merge([
                    'page' => $request->page_no
                ]);
            }

            $filters = [
                'search' => $request->search,
                'status' => 'pending_payment',
                'due_filter' => $request->filter ?? 'all',
                'plan_type_id' => is_array($request->plan_type_id)
                    ? $request->plan_type_id
                    : (isset($request->plan_type_id) ? [(int) $request->plan_type_id] : []),
                'sort_by' => $request->sort_by,
                'sort_order' => $request->sort_order,
                'from_date' => $request->from_date,
                'to_date' => $request->to_date,
            ];

            Log::info([
                'filter pending'=>$filters
            ]);

            $data = $service->getLearnersList($filters);

           
            return response()->json([
                'status' => true,
                'list_count' => $data->total(),
                'data' => $data->items(),
                'summary' => $dashboardService->pendingPaymentSummary(getCurrentBranch()),
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function expiredList(Request $request, LearnerService $service)
    {
        try {
            $request->validate([
                'search' => 'nullable|string',
                'plan_type_id' => 'nullable',
                'plan_type_id.*' => 'integer|exists:plan_types,id',
                'sort_by' => 'nullable|in:seat_no,name,expire_date,gen',
                'sort_order' => 'nullable|in:asc,desc',
                'from_date' => 'nullable|date',
                'to_date' => 'nullable|date|after_or_equal:from_date',
            ]);

            if ($request->has('page_no')) {
                $request->merge([
                    'page' => $request->page_no
                ]);
            }

            $filters = [
                'search' => $request->search,
                'status' => 'expired',
                'plan_type_id' => is_array($request->plan_type_id)
                    ? $request->plan_type_id
                    : (isset($request->plan_type_id) ? [(int) $request->plan_type_id] : []),
                'sort_by' => $request->sort_by,
                'sort_order' => $request->sort_order,
                'from_date' => $request->from_date,
                'to_date' => $request->to_date,
            ];

            $data = $service->getLearnersList($filters);

            return response()->json([
                'status' => true,
                'list_count' => $data->total(),
                'data' => $data->items(),
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function extendedList(Request $request, LearnerService $service)
    {
        try {
            $request->validate([
                'search' => 'nullable|string',
                'plan_type_id' => 'nullable',
                'plan_type_id.*' => 'integer|exists:plan_types,id',
                'sort_by' => 'nullable|in:seat_no,name,expire_date,gen',
                'sort_order' => 'nullable|in:asc,desc',
                'from_date' => 'nullable|date',
                'to_date' => 'nullable|date|after_or_equal:from_date',
            ]);

            if ($request->has('page_no')) {
                $request->merge([
                    'page' => $request->page_no
                ]);
            }

            $filters = [
                'search' => $request->search,
                'status' => 'extended',
                'plan_type_id' => is_array($request->plan_type_id)
                    ? $request->plan_type_id
                    : (isset($request->plan_type_id) ? [(int) $request->plan_type_id] : []),
                'sort_by' => $request->sort_by,
                'sort_order' => $request->sort_order,
                'from_date' => $request->from_date,
                'to_date' => $request->to_date,
            ];

            $data = $service->getLearnersList($filters);

            return response()->json([
                'status' => true,
                'list_count' => $data->total(),
                'data' => $data->items(),
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function listByType(Request $request, LearnerService $service, DashboardService $dashboardService)
    {
        $validated = $request->validate([
            'list_type' => 'required|in:extend_expired,payment_due',
            'search' => 'nullable|string',
            'plan_type_id' => 'nullable',
            'plan_type_id.*' => 'integer|exists:plan_types,id',
            'sort_by' => 'nullable|in:seat_no,name,expire_date,gen',
            'sort_order' => 'nullable|in:asc,desc',
        ]);

        if ($request->has('page_no')) {
            $request->merge([
                'page' => $request->page_no
            ]);
        }

        if ($validated['list_type'] === 'payment_due') {
            return response()->json([
                'status' => true,
                'list_type' => 'payment_due',
                'data' => $dashboardService->duePaymentList(getCurrentBranch(), null),
            ]);
        }

        $baseFilters = [
            'search' => $request->search,
            'plan_type_id' => is_array($request->plan_type_id)
                ? $request->plan_type_id
                : (isset($request->plan_type_id) ? [(int) $request->plan_type_id] : []),
            'sort_by' => $request->sort_by,
            'sort_order' => $request->sort_order,
        ];

        return response()->json([
            'status' => true,
            'list_type' => 'extend_expired',
            'data' => [
                'extended' => $service->getLearnersList(array_merge($baseFilters, ['status' => 'extended'])),
                'about_to_expire' => $service->getLearnersList(array_merge($baseFilters, ['status' => 'about_to_expire'])),
            ],
        ]);
    }

    public function process(LearnerOperationRequest $request, LearnerOperationService $service)
    {
       
        $permission = $this->permissionForLearnerOperation((string) $request->operation);

        if ($permission && $denied = $this->denyWithoutAppPermission($permission)) {
            return $denied;
        }

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
        $operation = strtolower((string) $request->input('operation'));
        $permission = $operation === 'delete'
            ? ['Delete Seat', 'delete-seat']
            : ['Close Seat', 'close-seat'];

        if ($denied = $this->denyWithoutAppPermission($permission)) {
            return $denied;
        }

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
            'plan_type_status' => 'nullable|integer|in:1,2,3,4,5,6,7,8',
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

        $status = 200;
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
            Log::info('Transaction detail API request', [
                'request' => $request->all(),
                'validated' => $validated,
            ]);

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
        if ($denied = $this->denyWithoutAppPermission(['Delete Seat', 'delete-seat'])) {
            return $denied;
        }

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
        if ($denied = $this->denyWithoutAppPermission(['Delete Seat', 'delete-seat'])) {
            return $denied;
        }

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

    private function permissionForLearnerOperation(string $operation): ?array
    {
        return match (strtolower($operation)) {
            'editlearner', LearnerOperation::EDIT->value => ['Edit Seat', 'edit-seat'],
            LearnerOperation::RENEW->value => ['Renew Seat', 'renew-seat'],
            LearnerOperation::UPGRADE->value => ['Upgrade Seat Plan', 'upgrade-seat-plan'],
            LearnerOperation::CHANGE_PLAN->value => ['Change Plan', 'change-plan'],
            LearnerOperation::REACTIVE->value => ['Reactive Seat', 'reactive-seat'],
            LearnerOperation::SWAP_SEAT->value => ['Swap Seat', 'swap-seat'],
            LearnerOperation::CLOSE_SEAT->value => ['Close Seat', 'close-seat'],
            LearnerOperation::DELETE_SEAT->value => ['Delete Seat', 'delete-seat'],
            LearnerOperation::FREEZE_SEAT->value => ['Freez Days', 'freez-days', 'Freeze Days', 'freeze-days'],
            LearnerOperation::GIFT_DAYS->value => ['Gift Days', 'gift-days'],
            LearnerOperation::MISC_PAYMENT->value => ['Add Misllaneous Payment', 'add-misllaneous-payment'],
            default => null,
        };
    }

    public function activity(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'search' => 'nullable|string|max:100',
            'date' => 'nullable|date',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'activity_type' => 'nullable',
            'operation' => 'nullable',
            'operation_type' => 'nullable',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page_no' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        if ($request->has('page_no')) {
            $request->merge(['page' => $request->page_no]);
        }

        $filterValue = $request->input('activity_type', $request->input('operation_type', $request->input('operation')));
        $operations = $this->activityOperationsFromFilter($filterValue);
        $selectedFilters = $this->activitySelectedFilters($filterValue);

        $query = LearnerOperationsLog::query()
            ->where('branch_id', getCurrentBranch())
            // withoutGlobalScopes(): Learner's own 'branch' scope (HasBranch) filters
            // by the *currently logged-in* user's branch, which can silently null out
            // the relation here (e.g. a learner later moved to another branch) even
            // though this log row is already correctly branch-scoped above.
            ->with(['learner' => fn ($q) => $q->withoutGlobalScopes()]);

        if (! empty($operations)) {
            $query->whereIn('operation', $operations);
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        } else {
            if ($request->filled('from_date')) {
                $query->whereDate('created_at', '>=', $request->from_date);
            }

            if ($request->filled('to_date')) {
                $query->whereDate('created_at', '<=', $request->to_date);
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('learner', function ($q) use ($search) {
                $q->where('name', 'LIKE', '%' . $search . '%')
                    ->orWhere('mobile', 'LIKE', '%' . $search . '%')
                    ->orWhere('email', 'LIKE', '%' . $search . '%')
                    ->orWhere('seat_no', 'LIKE', '%' . $search . '%');
            });
        }

        $logs = $query->latest()->paginate((int) $request->input('per_page', 20));
        $logItems = collect($logs->items());

        $updatedByIds = $logItems->pluck('updated_by')->filter()->unique()->values();
        $updatedByMap = DB::table('library_users')->whereIn('id', $updatedByIds)->pluck('name', 'id')->all();
        $missingIds = $updatedByIds->diff(array_keys($updatedByMap))->values();
        if ($missingIds->isNotEmpty()) {
            $updatedByMap += DB::table('libraries')->whereIn('id', $missingIds)->pluck('library_name', 'id')->all();
        }

        $planIds = $logItems->whereIn('operation', ['renewSeat'])
            ->flatMap(fn ($log) => [$log->old_value, $log->new_value])
            ->filter()->unique()->values();
        $planNames = $planIds->isNotEmpty()
            ? Plan::whereIn('id', $planIds)->pluck('name', 'id')->all()
            : [];

        $planTypeIds = $logItems->whereIn('operation', ['learnerUpgrade', 'changePlan'])
            ->flatMap(fn ($log) => [$log->old_value, $log->new_value])
            ->filter()->unique()->values();
        $planTypeNames = $planTypeIds->isNotEmpty()
            ? PlanType::whereIn('id', $planTypeIds)->pluck('name', 'id')->all()
            : [];

        $seatMap = generateSeatNumbers();

        $activities = $logItems
            ->map(fn ($log) => $this->formatLearnerActivity($log, $updatedByMap, $planNames, $planTypeNames, $seatMap))
            ->groupBy('group_date_key')
            ->map(function ($items) {
                $first = $items->first();

                return [
                    'date_label' => $first['date_label'],
                    'date' => $first['date'],
                    'items' => $items->map(function ($item) {
                        unset($item['group_date_key'], $item['date_label']);

                        return $item;
                    })->values(),
                ];
            })
            ->values();

        return response()->json([
            'status' => true,
            'selected_date' => (string) ($request->date ?? ''),
            'search' => (string) ($request->search ?? ''),
            'activity_type' => is_array($filterValue) ? $selectedFilters : (string) ($filterValue ?? ''),
            'filters' => $this->activityFilters($selectedFilters),
            'data' => $activities,
            'pagination' => [
                'current_page' => $logs->currentPage(),
                'per_page' => $logs->perPage(),
                'last_page' => $logs->lastPage(),
                'total' => $logs->total(),
            ],
        ]);
    }

    public function statusList()
    {
        return response()->json([
            'status' => true,
            'data' => [
                
                ['key' => 'active', 'label' => 'Active'],
                ['key' => 'about_to_expire', 'label' => 'About to expire'],
                ['key' => 'extended', 'label' => 'Extended'],
                ['key' => 'future', 'label' => 'Future Booking'],
                ['key' => 'expired', 'label' => 'Expired'],
                ['key' => 'deleted', 'label' => 'Deleted'],
                ['key' => 'closed', 'label' => 'Closed'],
                ['key' => 'pending_payment', 'label' => 'Pending Payment'],
                ['key' => 'non_expiry', 'label' => 'Non Expire'],
            ],
        ]);
    }

    private function formatLearnerActivity($log, array $updatedByMap = [], array $planNames = [], array $planTypeNames = [], array $seatMap = []): array
    {
        $log->updated_by_name = $updatedByMap[$log->updated_by] ?? 'System';
        $details = HelperService::getOperationDetails($log, $planNames, $planTypeNames, $seatMap);
        $meta = $this->activityMeta($log->operation);
        $createdAt = Carbon::parse($log->created_at);
        $operationType = $details['operation_type'] ?: $meta['label'];

        return [
            'id' => (int) $log->id,
            'learner_id' => (int) $log->learner_id,
            // 'learner_detail_id' => $log->learner_detail_id ? (int) $log->learner_detail_id : null,
            'learner_name' => optional($log->learner)->name ?? '',
            'seat_no' => HelperService::formatSeatDisplay(optional($log->learner)->seat_no, $seatMap),
            // 'operation_key' => (string) $log->operation,
            'operation_type' => $operationType,
            'filter_key' => $meta['filter_key'],
            'color_code' => $meta['color_code'],
            'title' => $operationType,
            'message' => trim(strip_tags(str_replace('<br>', ' ', $details['message']))),
            'message_html' => $details['message'],
            'field_updated' => (string) ($log->field_updated ?? ''),
            'old_value' => (string) ($log->old_value ?? ''),
            'new_value' => (string) ($log->new_value ?? ''),
            // 'updated_by' => (string) ($log->updated_by ?? ''),
            'updated_by_name' => $log->updated_by_name,
            'time' => $createdAt->format('h:i A'),
            // 'created_at' => $createdAt->toDateTimeString(),
            'date' => $createdAt->format('d M Y'),
            'date_label' => $this->activityDateLabel($createdAt),
            'group_date_key' => $createdAt->toDateString(),
        ];
    }

    private function activityFilters(array $selectedFilters = []): array
    {
        $filters = [
            ['key' => 'renew', 'label' => 'Seat Booking', 'color_code' => '#10B7D9'],
            ['key' => 'modify', 'label' => 'Modify Plan', 'color_code' => '#E19A00'],
            ['key' => 'swap', 'label' => 'Swap Seat', 'color_code' => '#D633E9'],
            ['key' => 'all_day', 'label' => 'All Day', 'color_code' => '#25176E'],
            ['key' => 'pending_payment', 'label' => 'Pending Payment', 'color_code' => '#6B7280'],
            ['key' => 'miscellaneous_payment', 'label' => 'Miscellaneous Payment', 'color_code' => '#64748B'],
            ['key' => 'settle', 'label' => 'Settle', 'color_code' => '#22C55E'],
            ['key' => 'edit', 'label' => 'Edit', 'color_code' => '#6366F1'],
            ['key' => 'refund', 'label' => 'Refund', 'color_code' => '#EF4444'],
            ['key' => 'close_plan', 'label' => 'Close Plan', 'color_code' => '#F97316'],
            ['key' => 'gift_day', 'label' => 'Gift Day', 'color_code' => '#14B8A6'],
            ['key' => 'delete', 'label' => 'Delete', 'color_code' => '#DC2626'],
            ['key' => 'freeze_plan', 'label' => 'Freeze Plan', 'color_code' => '#0EA5E9'],
        ];

        return collect($filters)->map(function ($filter) use ($selectedFilters) {
            $filter['selected'] = in_array($filter['key'], $selectedFilters, true);

            return $filter;
        })->all();
    }

    private function activityOperationsFromFilter($filter): array
    {
        $values = is_array($filter) ? $filter : explode(',', (string) $filter);
        $map = [
            'renew' => ['renewSeat', 'renewDelete'],
            'seat_booking' => ['renewSeat'],
            'modify' => ['learnerUpgrade', 'changePlan'],
            'modify_plan' => ['learnerUpgrade', 'changePlan'],
            'swap' => ['swapseat'],
            'swap_seat' => ['swapseat'],
            'close_plan' => ['closeSeat'],
            'delete' => ['deleteSeat'],
            'restore' => ['restoreSeat'],
            'reactive' => ['reactive'],
            'freeze_plan' => ['freezePlan'],
            'gift_day' => ['giftDays'],
            'all' => [],
            'all_day' => [],
        ];

        $operations = [];
        foreach ($values as $value) {
            $key = strtolower(trim((string) $value));
            if ($key === '') {
                continue;
            }

            if (array_key_exists($key, $map)) {
                if ($map[$key] === []) {
                    return [];
                }

                $operations = array_merge($operations, $map[$key]);
                continue;
            }

            $operations[] = trim((string) $value);
        }

        return array_values(array_unique($operations));
    }

    private function activitySelectedFilters($filter): array
    {
        $values = is_array($filter) ? $filter : explode(',', (string) $filter);
        $aliases = [
            'seat_booking' => 'renew',
            'modify_plan' => 'modify',
            'swap_seat' => 'swap',
            'all' => 'all_day',
        ];

        $selected = [];
        foreach ($values as $value) {
            $key = strtolower(trim((string) $value));
            if ($key === '') {
                continue;
            }

            $selected[] = $aliases[$key] ?? $key;
        }

        return array_values(array_unique($selected));
    }

    private function activityMeta(?string $operation): array
    {
        $map = [
            'renewSeat' => ['label' => 'Renew Seat', 'filter_key' => 'renew', 'color_code' => '#10B7D9'],
            'renewDelete' => ['label' => 'Renew Delete', 'filter_key' => 'renew', 'color_code' => '#10B7D9'],
            'learnerUpgrade' => ['label' => 'Plan Upgraded', 'filter_key' => 'modify', 'color_code' => '#E19A00'],
            'changePlan' => ['label' => 'Change Plan', 'filter_key' => 'modify', 'color_code' => '#E19A00'],
            'swapseat' => ['label' => 'Seat Swapped', 'filter_key' => 'swap', 'color_code' => '#D633E9'],
            'reactive' => ['label' => 'Reactive Seat', 'filter_key' => 'reactive', 'color_code' => '#22C55E'],
            'closeSeat' => ['label' => 'Seat Closed', 'filter_key' => 'close_plan', 'color_code' => '#F97316'],
            'deleteSeat' => ['label' => 'Delete Seat', 'filter_key' => 'delete', 'color_code' => '#DC2626'],
            'restoreSeat' => ['label' => 'Restore Seat', 'filter_key' => 'restore', 'color_code' => '#14B8A6'],
            'freezePlan' => ['label' => 'Plan Frozen', 'filter_key' => 'freeze_plan', 'color_code' => '#0EA5E9'],
            'unfreezePlan' => ['label' => 'Plan Unfrozen', 'filter_key' => 'freeze_plan', 'color_code' => '#0EA5E9'],
            'giftDays' => ['label' => 'Added Gift Days', 'filter_key' => 'gift_day', 'color_code' => '#14B8A6'],
        ];

        return $map[$operation] ?? [
            'label' => ucwords(str_replace(['_', '-'], ' ', (string) $operation)),
            'filter_key' => (string) $operation,
            'color_code' => '#6B7280',
        ];
    }

    private function activityDateLabel(Carbon $date): string
    {
        if ($date->isToday()) {
            return 'Today';
        }

        if ($date->isYesterday()) {
            return 'Yesterday';
        }

        return $date->format('d M Y');
    }

}
