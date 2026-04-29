<?php

namespace App\Http\Controllers;

use App\Services\IdCardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IdCardController extends Controller
{
    public function link(Request $request, IdCardService $idCardService): JsonResponse
    {
        $validated = $request->validate([
            'learner_detail_id' => 'required|integer',
        ]);

        $links = $idCardService->idCardLinks((int) $validated['learner_detail_id']);

        return response()->json([
             'status' => true,
            'data' => $links,
            'message'=>'Successfully ID card link fetch'
        ]);
    }

    public function pdf(int $id, IdCardService $idCardService)
    {
        $learnerDetail = $idCardService->findLearnerDetail($id);

        return $idCardService->pdfResponse($learnerDetail);
    }

    public function mobile(int $id, IdCardService $idCardService)
    {
        $learnerDetail = $idCardService->findLearnerDetail($id);

        return $idCardService->viewResponse($learnerDetail);
    }
}
