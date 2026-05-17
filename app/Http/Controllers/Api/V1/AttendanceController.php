<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Learner;
use Illuminate\Http\Request;
use App\Services\AttendanceService;

class AttendanceController extends Controller
{
    public function summary(Request $request, AttendanceService $service)
    {
        return $service->summary($request);
    }

    public function logs(Request $request, AttendanceService $service)
    {
        return $service->attendanceLogs($request);
    }

    // this function not perfact
    public function qrScanAttendance(Request $request)
    {
        
        $request->validate([
            'qr' => 'required|string',
        ]);

        // ✅ Learner (logged-in user)
        $learner = auth()->user();

        if (!$learner) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 401);
        }

        // ✅ Validate Branch QR (your existing logic)
        $branchId = AttendanceService::validateQrToken($request->qr);

        if (!$branchId) {
            return response()->json([
                'status' => 'error',
                'message' => 'QR expired or invalid'
            ], 403);
        }

        // ✅ Duplicate protection
        $cacheKey = 'scan_' . $learner->id;

        if (cache()->has($cacheKey)) {
            return response()->json([
                'status' => 'success',
                'message' => 'Attendance already captured'
            ]);
        }

        cache()->put($cacheKey, true, 5);

        // ✅ Mark attendance
        $result = $this->processAttendance($learner->id, $branchId, 'QR');

        return response()->json($result, $result['code'] ?? 200);
    }

    public function idCardScanAttendance(Request $request, AttendanceService $service)
    {
        $request->validate([
            'qr' => 'required|string',
        ]);

        // ✅ Owner / staff
        $owner = auth()->user();
       

        if (!$owner) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 401);
        }

        $branchId = $owner->current_branch;

        // ✅ Decode learner QR
        $learnerNo = trim($request->qr);

        $learner = Learner::where('learner_no', $learnerNo)->first();

        if (!$learner) {
            return response()->json([
                'status' => 'error',
                'message' => 'Learner not found'
            ], 404);
        }

        // ✅ Security: same branch
        if ($learner->branch_id != $branchId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Wrong library QR'
            ], 403);
        }
       

        // ✅ Duplicate protection
        $cacheKey = 'scan_' . $learner->id;

        if (cache()->has($cacheKey)) {
            return response()->json([
                'status' => 'success',
                'message' => 'Attendance already captured'
            ]);
        }

        cache()->put($cacheKey, true, 5);

        // ✅ Mark attendance
        $result = $service->processAttendance($learner->id, $branchId, 'SCAN');

        return response()->json($result, $result['code'] ?? 200);
    }

public function manualAttendance(Request $request, AttendanceService $service)
{
    $request->validate([
        'learner_id' => 'required|integer|exists:learners,id',
        'attendance' => 'nullable|integer|in:0,1',
        'time'       => ['required', 'regex:/^(in|out|([01][0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?)$/'],
        'date'       => 'nullable|date',
    ]);

    $owner = auth()->user();

    $branchId  = $owner->current_branch;
    $libraryId = auth('library_api')->id();
   

    $learner = Learner::find($request->learner_id);

    if (!$learner) {
        return response()->json([
            'status' => 'error',
            'message' => 'Learner not found'
        ], 404);
    }

    if ($learner->branch_id != $branchId) {
        return response()->json([
            'status' => 'error',
            'message' => 'Learner belongs to another branch'
        ], 403);
    }

    try {

        $service->manualAttendance(
            $request->learner_id,
            $request->attendance ?? 1,
            $request->date ?? today()->toDateString(),
            $request->time,
            $libraryId,
            $branchId
        );

        return response()->json([
            'status' => 'success',
            'message' => $request->attendance == 1
                ? 'Attendance marked Present'
                : 'Attendance marked Absent'
        ]);

    } catch (\Throwable $e) {

        return response()->json([
            'status' => 'error',
            'message' => 'Attendance not marked'
        ], 500);
    }
}
}
