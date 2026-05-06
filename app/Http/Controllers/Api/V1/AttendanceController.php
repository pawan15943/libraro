<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
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
        $result = $service->processAttendance($learner->id, $branchId, 'SCAN');

        return response()->json($result, $result['code'] ?? 200);
    }

    public function manualAttendance(Request $request, AttendanceService $service)
{
    $request->validate([
        'learner_id' => 'required|integer|exists:learners,id',
        'attendance' => 'required|integer|in:0,1',
        'time'       => 'required|in:in,out',
        'date'       => 'nullable|date',
    ]);

    $owner = auth()->user();

    if (!$owner) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Unauthorized'
        ], 401);
    }

    $branchId  = $owner->branch_id;
    $libraryId = $owner->library_id;

    $learnerId = $request->learner_id;
    $attendance = $request->attendance;
    $date = $request->date ?? today()->toDateString();
    $currentTime = now();

    $learner = Learner::where('id', $learnerId)
        ->select('id', 'name', 'branch_id')
        ->first();

    if (!$learner) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Learner not found'
        ], 404);
    }

    // ✅ Branch security
    if ($learner->branch_id != $branchId) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Learner belongs to another branch'
        ], 403);
    }

    $existingAttendance = Attendance::where('learner_id', $learnerId)
        ->where('date', $date)
        ->first();

    try {

        DB::beginTransaction();

        if ($existingAttendance) {

            if ($request->time == 'in') {
                $existingAttendance->in_time = $currentTime;
            }

            if ($request->time == 'out') {
                $existingAttendance->out_time = $currentTime;
            }

            $existingAttendance->attendance = $attendance;
            $existingAttendance->save();

        } else {

            Attendance::create([
                'learner_id' => $learnerId,
                'attendance' => $attendance,
                'date'       => $date,
                'in_time'    => $request->time == 'in' ? $currentTime : null,
                'out_time'   => $request->time == 'out' ? $currentTime : null,
                'library_id' => $libraryId,
                'branch_id'  => $branchId,
            ]);
        }

        // ✅ Attendance log
        $service->logInsert([
            'learner_id'     => $learnerId,
            'branch_id'      => $branchId,
            'punch_datetime' => $currentTime,
            'source'         => 'MANUAL'
        ]);

        DB::commit();

        return response()->json([
            'status'  => 'success',
            'message' => $attendance == 1
                ? 'Attendance marked Present'
                : 'Attendance marked Absent'
        ]);

    } catch (\Throwable $e) {

        DB::rollBack();

        \Log::error('Manual Attendance Error', [
            'error' => $e->getMessage()
        ]);

        return response()->json([
            'status'  => 'error',
            'message' => 'Attendance not marked'
        ], 500);
    }
}
}
