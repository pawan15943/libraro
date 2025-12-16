<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Learner;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\FlareClient\View;
use Illuminate\Support\Facades\Cookie;

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

        $verifyToken = hash_hmac(
            'sha256',
            $learner->id . '|' . now()->timestamp,
            config('app.key')
        );

        session([
            'attendance_verified' => true,
            'learner_id' => $learner->id,
            'verify_token' => $verifyToken,
        ]);

        $cookieValue = hash_hmac('sha256', $learner->id, config('app.key'));

        Cookie::queue(
            Cookie::make('learner_key', $cookieValue, 60 * 24 * 30) // 30 days
        );

        return response()->json([
            'verify_token' => $verifyToken
        ]);
    }
    
    public function autoVerify(Request $request)
    {
       $cookie = $request->cookie('learner_key');
        if (!$cookie) {
            return response()->json(['status' => false]);
        }

        $learner = Learner::all()->first(function ($l) use ($cookie) {
            return hash_hmac('sha256', $l->id, config('app.key')) === $cookie;
        });
        if (!$learner) {
            return response()->json(['status'=>false]);
        }

        session([
            'attendance_verified' => true,
            'learner_id' => $learner->id,
            'verify_token' => Str::random(40)
        ]);

        return response()->json([
            'status' => true,
            'verify_token' => session('verify_token')
        ]);
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


        if (!session('attendance_verified') ||
            $request->verify_token !== session('verify_token')) {
            return response()->json(['message'=>'Unauthorized'], 403);
        }
        
          \Log::info('SESSION CHECK', [
            'verified' => session('attendance_verified'),
            'session_token' => session('verify_token'),
            'request_token' => $request->verify_token
        ]);

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
                 if (!$existingAttendance->in_time){
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

        session()->forget(['attendance_verified','verify_token']);
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
    \Log::info('SCAN HIT', $request->all());

    /* 1️⃣ Decode QR */
    $decoded = base64_decode($request->qr, true);
    if (!$decoded) {
        \Log::warning('QR decode failed');
        return response()->json(['message' => 'Invalid QR'], 403);
    }

    [$learnerId, $signature] = explode('|', $decoded);

    /* 2️⃣ Verify QR signature */
    $expected = hash_hmac('sha256', $learnerId, config('app.key'));

    if (!hash_equals($expected, $signature)) {
        \Log::warning('QR signature mismatch', compact('learnerId'));
        return response()->json(['message' => 'QR tampered'], 403);
    }

    /* 3️⃣ Learner validation */
    $learner = Learner::where('id', $learnerId)
        ->where('status', 1)
        ->first();

    if (!$learner) {
        \Log::warning('Learner not found', compact('learnerId'));
        return response()->json(['message' => 'Learner not found'], 404);
    }

    /* 4️⃣ Plan validation */
    if ($learner->plan_end_date < today()) {
        \Log::warning('Plan expired', compact('learnerId'));
        return response()->json(['message' => 'Plan expired'], 403);
    }

    /* 5️⃣ Attendance logic */
    $attendance = Attendance::where('learner_id', $learnerId)
        ->where('date', today())
        ->first();

    if (!$attendance) {
        Attendance::create([
            'learner_id' => $learnerId,
            'library_id' => auth()->user()->library_id,
            'date'       => today(),
            'in_time'    => now(),
            'attendance' => 1
        ]);

        \Log::info('Punch IN', compact('learnerId'));

        return response()->json([
            'message' => 'Punch IN successful'
        ]);
    }

    // Punch OUT
    $attendance->update([
        'out_time' => now(),
        'attendance' => 1
    ]);

    \Log::info('Punch OUT', compact('learnerId'));

    return response()->json([
        'message' => 'Punch OUT successful'
    ]);
}



}
