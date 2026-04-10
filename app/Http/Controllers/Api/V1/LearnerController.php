<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLearnerRequest;
use App\Services\LearnerService;
use Illuminate\Http\Request;
use App\DTO\LearnerOperationDTO;
use App\Enums\LearnerOperation;
use App\Http\Requests\LearnerOperationRequest;
use App\Services\LearnerOperationService;

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
}
