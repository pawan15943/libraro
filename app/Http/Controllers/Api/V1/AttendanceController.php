<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Learner;
use Illuminate\Http\Request;
use App\Services\AttendanceService;

class AttendanceController extends Controller
{
    public function qrToken()
    {
        $owner = auth()->user();
        $branchId = (int) ($owner->current_branch ?? getCurrentBranch());

        if (!$branchId) {
            return response()->json([
                'status' => false,
                'message' => 'Branch not found',
            ], 422);
        }

        return response()->json([
            'status' => true,
            'data' => AttendanceService::attendanceQrTokens($branchId),
        ]);
    }

    public function summary(Request $request, AttendanceService $service)
    {
        try {
            return $service->summary($request);
        } catch (\Throwable $e) {
            \Log::error('Attendance Summary Error', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong'
            ], 500);
        }
    }

    public function logs(Request $request, AttendanceService $service)
    {
        return $service->attendanceLogs($request);
    }

    public function qrScanAttendance(Request $request, AttendanceService $service)
    {

        $request->validate([
            'qr' => 'required|string',
        ]);

        // ✅ Learner (logged-in user)
        $learner = auth()->user();

        if (!$learner) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        // ✅ Validate Branch QR or Learner Encrypted QR (Permanent ID Card QR)
        $branchId = AttendanceService::validateQrToken($request->qr);

        if (!$branchId) {
            $decryptedLearnerQr = decryptLearnerQrPayload($request->qr);
            if ($decryptedLearnerQr && isset($decryptedLearnerQr['l_no'])) {
                $branchId = $learner->branch_id;
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'QR expired or invalid'
                ], 403);
            }
        }

        // ✅ Duplicate protection
        $cacheKey = 'scan_' . $learner->id;

        if (cache()->has($cacheKey)) {
            return response()->json([
                'status' => true,
                'message' => 'Attendance already captured'
            ]);
        }

        cache()->put($cacheKey, true, 5);

        // ✅ Mark attendance
        $result = $service->processAttendance($learner->id, $branchId, 'QR');

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
                'status' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $branchId = $owner->current_branch;

        // ✅ Decode learner QR (AES Decrypt payload or fallback to plain learner_no)
        $qrPayload = trim($request->qr);
        $decryptedData = decryptLearnerQrPayload($qrPayload);

        if ($decryptedData && isset($decryptedData['l_no'])) {
            $learnerNo = $decryptedData['l_no'];
        } else {
            $learnerNo = $qrPayload;
        }

        $learner = Learner::where('learner_no', $learnerNo)->first();

        if (!$learner) {
            return response()->json([
                'status' => false,
                'message' => 'Learner not found'
            ], 404);
        }

        // ✅ Security: same branch
        if ($learner->branch_id != $branchId) {
            return response()->json([
                'status' => false,
                'message' => 'Wrong library QR'
            ], );
        }
       

        // ✅ Duplicate protection
        $cacheKey = 'scan_' . $learner->id;

        if (cache()->has($cacheKey)) {
            return response()->json([
                'status' => true,
                'message' => 'Attendance already captured'
            ]);
        }

        cache()->put($cacheKey, true, 5);

        // ✅ Mark attendance
        $result = $service->processAttendance($learner->id, $branchId, 'SCAN');

        return response()->json($result);
    }

    public function manualAttendance(Request $request, AttendanceService $service)
    {
    $request->validate([
            'learner_id' => 'required|integer|exists:learners,id',
            'attendance' => 'nullable|integer|in:0,1',
            'time'       => 'required|date_format:H:i:s',
            'date'       => 'nullable|date',
            'time_type'  => 'required|in:in,out',
        ]);

        $owner = auth()->user();

        $branchId  = $owner->current_branch;
        $libraryId = getLibraryId();
    

        $learner = Learner::find($request->learner_id);

        if (!$learner) {
            return response()->json([
                'status' => false,
                'message' => 'Learner not found'
            ]);
        }

        if ($learner->branch_id != $branchId) {
            return response()->json([
                'status' => false,
                'message' => 'Learner belongs to another branch'
            ]);
        }

        try {

            $service->manualAttendance(
                $request->learner_id,
                $request->attendance ?? 1,
                $request->date ?? today()->toDateString(),
                $request->time,
                $request->time_type,
                $libraryId,
                $branchId
            );

            return response()->json([
                'status' => true,
                'message' => 'Attendance marked successfully'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
