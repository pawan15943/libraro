<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLearnerRequest;
use App\Services\LearnerService;
use Illuminate\Http\Request;

class LearnerController extends Controller
{
    public function learnerStore(StoreLearnerRequest $request, LearnerService $service)
    {

        try {

            $processData = $request->prepareData();

            $result = $service->processLearnerStore($processData);

            if ($request->expectsJson()) {
                return response()->json($result);
            }

            if ($result['success']) {
                return redirect()->route('learners')
                    ->with('success',$result['message']);
            }

            return redirect()->back()
                ->with('error',$result['message']);

        } catch (\Exception $e) {

            \Log::error("Learner Create Error: ".$e->getMessage(),[
                'line'=>$e->getLine(),
                'file'=>$e->getFile()
            ]);

            return response()->json([
                'success'=>false,
                'message'=>'Something went wrong while creating learner!'
            ],500);
        }
    }
    public function show($id, LearnerService $service)
    {
        try {

            $data = $service->getLearnerDetails($id);

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 404);
        }
    }
    public function index(Request $request, LearnerService $service)
    {
        try {

            $filters = [
                'search' => $request->search,
                'status' => $request->status
            ];

            $data = $service->getLearnersList($filters);

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
