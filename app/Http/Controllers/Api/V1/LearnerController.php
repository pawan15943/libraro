<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLearnerRequest;
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
        
       
        return response()->json(
            $service->process($dto)
        );

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

    /**
     * Single entry: operation + learner_id (optional delete_all for permanent_delete).
     * Operations: restore, permanent_delete, freeze, unfreeze
     */
    public function lifecycle(Request $request, LearnerLifecycleService $service)
    {
        $validated = $request->validate([
            'operation'   => 'required|in:restore,permanent_delete,freeze,unfreeze',
            'learner_id'  => 'required|integer|exists:learners,id',
            'delete_all'  => 'nullable|boolean',
        ]);

        $result = $service->run(
            $validated['operation'],
            (int) $validated['learner_id'],
            [
                'delete_all' => $request->boolean('delete_all', true),
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
}
