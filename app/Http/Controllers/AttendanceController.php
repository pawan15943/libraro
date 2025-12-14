<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Learner;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\FlareClient\View;

class AttendanceController extends Controller
{
    public function index(){
        return view('attendance.attendance-qr');
    }
    public function generate()
    {
        
        $libraryId = getCurrentBranch(); // library logged in
        $slot = floor(now()->timestamp / 5); // 5-second slot

        $token = $this->makeToken($libraryId, $slot);

        // fallback token (30 sec window)
        $fallbackSlot = floor(now()->timestamp / 30);
        $fallback = $this->makeToken($libraryId, $fallbackSlot);

        return response()->json([
            'primary'  => $token,
            'fallback' => $fallback
        ]);
    }

    private function makeToken($libraryId, $slot)
    {
        $payload = $libraryId . '|' . $slot;
        $signature = hash_hmac('sha256', $payload, config('app.key'));

        return base64_encode($payload . '|' . $signature);
    }
    //Learner enters UID + Mobile page
    public function showLink(){
        return view('attendance.link-page');
    }
    //Server validates UID + Mobile ->(only if valid) Save verified learner token (session / cookie)
    public function verifyLearner(Request $request)
    {
        $request->validate([
            'uid' => 'required',
            'mobile' => 'required'
        ]);

        $learner = Learner::where('learner_no', $request->uid) ->where('mobile', encryptData($request->mobile))->first();
        
        if (!$learner) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid UID or Mobile'
            ], 401);
        }

        $token = hash_hmac(
            'sha256',
            $learner->id . '|' . now()->timestamp,
            config('app.key')
        );

        return response()->json([
            'status' => true,
            'learner_id' => $learner->id,
            'verify_token' => $token
        ])->withCookie(
            cookie('attendance_learner', json_encode([
                'learner_id' => $learner->id,
                'token' => $token
            ]), 60 * 24 * 7) // 7 days
        );
    }
    //Scanner opens ->Learner scans CURRENT QR
    private function validateQrToken(string $qrToken)
    {
        // STEP 1: Decode QR
        $decoded = base64_decode($qrToken, true);
        if (!$decoded) {
            return false;
        }

        // STEP 2: Split payload
        $parts = explode('|', $decoded);
        if (count($parts) !== 3) {
            return false;
        }

        [$libraryId, $slot, $signature] = $parts;

        // STEP 3: Verify signature (ANTI-TAMPER)
        $payload = $libraryId . '|' . $slot;
        $expectedSignature = hash_hmac(
            'sha256',
            $payload,
            config('app.key')
        );

        if (!hash_equals($expectedSignature, $signature)) {
            return false;
        }

        // STEP 4: Time validation (5 sec window ±1 slot)
        $currentSlot = floor(now()->timestamp / 5);

        if (abs($currentSlot - (int)$slot) > 1) {
            return false; // QR expired
        }

        // STEP 5: Final validation – library exists
        if (!Branch::where('id', $libraryId)->exists()) {
            return false;
        }

        return (int)$libraryId;
    }

    //Server validates:- QR token (5 sec / 10 min) and Learner verification token
    public function scanAttendance(Request $request)
    {
        \Log::info('SCAN REQUEST RECEIVED', $request->all());
            $expected = hash_hmac(
                'sha256',
                $request->learner_id . '|' . session()->get('verify_time', now()->timestamp),
                config('app.key')
            );

            if (!$request->verify_token) {
                return response()->json(['message'=>'Verification required'], 403);
            }
        


        // 2. Validate QR (your existing logic)
        $branchId = $this->validateQrToken($request->qr);
        if (!$branchId) {
            return response()->json([
                'message' => 'QR expired or invalid'
            ], 403);
        }
        \Log::info('QR VALIDATION RESULT', [
            'branch_id' => $branchId
        ]);

        
       
        // Extract variables from the request
            $learnerId =session('learner_id');
            $attendance = 1;
            $date = date('Y-m-d');
            $currentTime = now();
            $libraryId=Branch::where('id',$branchId)->select('library_id')->first();

            $existingAttendance = Attendance::where('learner_id', $learnerId)
                ->where('date', $date)
                ->first();

            if ($existingAttendance) {

                 $existingAttendance->out_time = $currentTime;
                 if($existingAttendance->in_time){
                    $existingAttendance->in_time = $currentTime;
                 }

                $existingAttendance->save();
            } else {
                // // 3. Mark attendance (safe)
                Attendance::create([
                    'learner_id' => $learnerId,
                    'attendance' => $attendance,
                    'date' => $date,
                    'in_time' => $currentTime ? $currentTime : null,
                    'out_time' => $currentTime ? $currentTime : null,
                    'library_id' => $libraryId->library_id,
                    'branch_id' => $branchId,
                ]);
            }
            \Log::info('ATTENDANCE SAVE DATA', [
                'learner_id' => $learnerId,
                'date' => $date,
                
            ]);

        
        return response()->json([
            'message' => 'Thank You! Attendance marked'
        ]);
    }


    //Success Page
    public function markSuccess(){
        return view('attendance.success');
    }

    public function scan(Request $request)
    {
        $qr = $request->qr;
        $decoded = base64_decode($qr);

        if (!$decoded) {
            return response()->json(['message'=>'Invalid QR'], 403);
        }

        [$libraryId, $slot, $sign] = explode('|', $decoded);

        $payload = $libraryId . '|' . $slot;
        $expected = hash_hmac('sha256', $payload, config('app.key'));

        if (!hash_equals($expected, $sign)) {
            return response()->json(['message'=>'QR tampered'], 403);
        }

        $currentSlot = floor(now()->timestamp / 5);

        // ✅ ±1 SLOT GRACE
        if (abs($currentSlot - (int)$slot) > 1) {
            return response()->json(['message'=>'QR expired'], 403);
        }

        // Attendance logic (safe & idempotent)
        Attendance::firstOrCreate([
            'learner_uid' => $request->uid,
            'attendance_date' => today()
        ], [
            'library_id' => $libraryId,
            'punch_in' => now()->format('H:i:s')
        ]);

        return response()->json(['message'=>'Thank You! Attendance marked']);
    }

}
