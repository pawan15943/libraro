<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
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

    public function qrScanAttendance(Request $request)
    {
        dd(auth()->user());
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

    public function idCardScanAttendance(Request $request)
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

    $branchId = $owner->branch_id;

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
    $result = $this->processAttendance($learner->id, $branchId, 'ID');

    return response()->json($result, $result['code'] ?? 200);
}
}
