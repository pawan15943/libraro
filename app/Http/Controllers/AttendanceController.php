<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Learner;
use App\Models\LearnerDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\FlareClient\View;
use Illuminate\Support\Facades\Cookie;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class AttendanceController extends Controller
{
    public function index(){
        return view('attendance.attendance-qr');
    }
    public function dashboard(){
        // 🔐 Must be verified via session (created in verify / autoVerify)
        if (!session('attendance_verified') || !session('learner_id')) {
            return redirect()->route('qr.attendance.link');
            
        }

        $learnerId = session('learner_id');
        $learner=Learner::withTrashed()->where('id',$learnerId)->first();
       

         $detail = LearnerDetail::query()
            ->where('learner_detail.learner_id', $learnerId)

            // Joins
            ->leftJoin('branches', 'learner_detail.branch_id', '=', 'branches.id')
            ->leftJoin('libraries', 'learner_detail.library_id', '=', 'libraries.id')

            // Select only required columns
            ->select([
                'learner_detail.id',
                'learner_detail.learner_id',
                'learner_detail.seat_no',
                'learner_detail.plan_id',
                'learner_detail.plan_type_id',
                'learner_detail.plan_start_date',
                'learner_detail.plan_end_date',
                'branches.name as branch_name',
                'libraries.library_name as library_name',
                'libraries.email as library_email',
                'libraries.library_mobile as library_mobile',
                'branches.library_address as library_address',
                'branches.library_images as library_images',
            ])

            // Eloquent relations
            ->with([
                'plan:id,name',
                'planType:id,name,start_time,end_time'
            ])

            ->latest('learner_detail.id')
            ->first();

       
         return view('attendance.dashboard', [
            'learner' => $learner,
            'detail'  => $detail,
            'today'   => Carbon::today(),
        ]);
        
    }
    public function generate()
    {
        
        $branchId = getCurrentBranch(); // library logged in
         \Log::info('getCurrentBranch', ['getCurrentBranch' => $branchId]);
        $slot = floor(now()->timestamp / 30); // 5-second slot

        $token = $this->makeToken($branchId, $slot);

        // fallback token (30 sec window)
        $fallbackSlot = floor(now()->timestamp / 30);
        $fallback = $this->makeToken($branchId, $fallbackSlot);

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
       $validator = Validator::make($request->all(), [
            'login_with' => 'required|in:dob,email,learner_no',
            'uid'        => 'required',
            'mobile'     => 'required|regex:/^[6-9]\d{9}$/'
        ], [
            'login_with.required' => 'Please choose login type',
            'uid.required'        => 'This field is required',
            'mobile.required'     => 'Mobile number is required',
            'mobile.regex'        => 'Enter valid 10 digit mobile number'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()
            ], 422);
        }
        $dob = null;

        // Safe DOB conversion
        try {
            $dob = Carbon::parse($request->uid)->format('Y-m-d');
        } catch (\Exception $e) {
            $dob = null;
        }

       $learner = Learner::where(function ($query) use ($request,$dob) {
                    $query->where('learner_no', $request->uid);
                        if ($dob) {
                            \Log::info('dob part hit',['dob'=>$dob]);
                            $query->orWhere('dob', $dob);
                        }
                       // Email (only if valid)
                        if (filter_var($request->uid, FILTER_VALIDATE_EMAIL)) {
                            $query->orWhere('email', encryptData($request->uid));
                        }
                })
                ->where('mobile', encryptData($request->mobile))
                ->first();
        
        if (!$learner) {
            return response()->json([
                'status' => false,
                'message' => 'We found that the details you entered are invalid.'
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
            'success'      => true,
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
        \Log::info('QR decoded payload', ['payload' => $decoded]);
        if (!$decoded) {
            return false;
        }

        // STEP 2: Split payload
        $parts = explode('|', $decoded);
        if (count($parts) !== 3) {
            return false;
        }
        \Log::info('part 1');
        [$branchId, $slot, $signature] = $parts;

        // STEP 3: Verify signature (ANTI-TAMPER)
        $payload = $branchId . '|' . $slot;
        $expectedSignature = hash_hmac(
            'sha256',
            $payload,
            config('app.key')
        );
        \Log::info('part 2');
        if (!hash_equals($expectedSignature, $signature)) {
            return false;
        }

        // STEP 4: Time validation (5 sec window ±1 slot)
        $currentSlot = floor(now()->timestamp / 30);
        \Log::info('part 3');
        if (abs($currentSlot - (int)$slot) > 1) {
            return false; // QR expired
        }
        \Log::info('part 4');
        // STEP 5: Final validation – library exists
        if (!Branch::where('id', $branchId)->exists()) {
            return false;
        }

        return (int)$branchId;
    }

    //Server validates:- QR token (5 sec / 10 min) and Learner verification token
    public function scanAttendance(Request $request)
    {
            \Log::info('SCAN REQUEST RECEIVED', $request->all());


            if (!session('attendance_verified') ||
                $request->verify_token !== session('verify_token')) {
                return response()->json([
                    'status'  => 'error',
                    'message'=>'Unauthorized'
                ], 403);
            }
            $learnerId = session('learner_id');

            // 2. Validate QR (your existing logic)
            $branchId = $this->validateQrToken($request->qr);


            \Log::info('SESSION CHECK', [
                'verified' => session('attendance_verified'),
                'session_token' => session('verify_token'),
                'request_token' => $request->verify_token,
                'branchId'=>$branchId,
                'request-qr'=>$request->qr
            ]);

            if (!$branchId) {
                
                return response()->json([
                    'status'  => 'error',
                    'message' => 'QR expired or invalid'
                ], 403);
            }
           

                /* 🔹 Get latest learner plan */
        $learnerDetail = LearnerDetail::where('learner_id', $learnerId)
            ->orderBy('plan_end_date', 'DESC')
            ->first();

        /* 1️⃣ No plan OR inactive plan */
        if (!$learnerDetail || $learnerDetail->status != 1) {

            $detail = LearnerDetail::withTrashed()
                ->where('learner_id', $learnerId)
                ->orderBy('plan_end_date', 'DESC')
                ->first();

            if ($detail) {

                $operation = DB::table('learner_operations_log')
                    ->where('learner_detail_id', $detail->id)
                    ->value('operation');

                if ($operation === 'deleteSeat') {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Your plan has been deleted'
                    ], 403);
                }

                if ($operation === 'closeSeat') {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Your plan has been closed'
                    ], 403);
                }
            }

            return response()->json([
                'status'  => 'error',
                'message' => 'No active plan found'
            ], 403);
        }

        /* 2️⃣ Wrong branch QR */
        if ($learnerDetail->branch_id != $branchId) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Ohh, it seems like you scanned the wrong library QR code.'
            ], 403);
        }

        /* 3️⃣ Plan expired */
        if ($learnerDetail->plan_end_date < date('Y-m-d')) {
            return response()->json([
                'status'  => 'expired',
                'message' => 'Plan expired'
            ], 403);
        }


            \Log::info('success part hit');
            
         /**
     * 🔁 DUPLICATE SCAN PROTECTION (MOST IMPORTANT)
        * Same QR + same session → ignore for 15 seconds
        */
            $now = now()->timestamp;
            $lastScanAt = session('last_scan_at', 0);

            if (($now - $lastScanAt) < 31) {
                // 🚫 DO NOT validate QR
                return response()->json([
                    'status'  => 'success',
                    'message' => 'Attendance captured'
                ]);
            }

            // Update scan time immediately (important)
            session(['last_scan_at' => $now]);
    
            
            $attendance = 1;
            $date = date('Y-m-d');
            $currentTime = now();
            $libraryId=Branch::where('id',$branchId)->select('library_id')->first();

            $existingAttendance = Attendance::where('learner_id', $learnerId)
                ->where('date', $date)
                ->first();

            if ($existingAttendance) {
                \Log::info('attendence update');
                 $existingAttendance->out_time = $currentTime;
                 if (!$existingAttendance->in_time){
                    $existingAttendance->in_time = $currentTime;
                 }

                $existingAttendance->save();
            } else {
                \Log::info('attendence add');
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
           
        
        
        return response()->json([
            'status'  => 'success',
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

        // /* 1️⃣ Decode QR */
        // $decoded = base64_decode($request->qr, true);
        // if (!$decoded) {
        //     \Log::warning('QR decode failed');
        //     return response()->json(['message' => 'Invalid QR'], 403);
        // }

        // [$learnerId, $signature] = explode('|', $decoded);

        // /* 2️⃣ Verify QR signature */
        // $expected = hash_hmac('sha256', $learnerId, config('app.key'));

        // if (!hash_equals($expected, $signature)) {
        //     \Log::warning('QR signature mismatch', compact('learnerId'));
        //     return response()->json(['message' => 'QR tampered'], 403);
        // }
        $learnerNo = trim($request->qr);

        if (!$learnerNo) {
            \Log::warning('learnerNo failed');
            return response()->json([
            'status'  => 'error',
            'message' => 'Invalid QR'
        ], 403);
        }

        /* 3️⃣ Learner validation */
        $learner = Learner::where('learner_no', $learnerNo)->first();

        if (!$learner) {
            \Log::warning('Learner not found');
            return response()->json([
                'status'  => 'error',
                'message' => 'Learner not found'
            ], 404);
        }
        
        if($learner->branch_id != getCurrentBranch()){
                return response()->json([
                'status'  => 'expired',
                 'message' => 'Ohh it seems like you scan wrong Library QR code.'
            ], 403);
        }

        $learnerDetail=LearnerDetail::where('learner_id',$learner->id)->where('status',1)->select('plan_end_date')->first();
        
            
        /* 1️⃣ No active plan */
        if (!$learnerDetail || $learner->status != 1 || $learnerDetail->status != 1) {
               /* 2️⃣ Plan expired check */
      
            $detail = LearnerDetail::withTrashed()
                ->where('learner_id', $learner->id)
                ->orderBy('plan_end_date', 'DESC')
                ->first();

            if ($detail) {

                $operation = DB::table('learner_operations_log')
                    ->where('learner_detail_id', $detail->id)
                    ->value('operation');

                if ($operation === 'deleteSeat') {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Your plan has been deleted'
                    ], 403);
                }

                if ($operation === 'closeSeat') {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Your plan has been closed'
                    ], 403);
                }
            }

            return response()->json([
                'status'  => 'error',
                'message' => 'No active plan found'
            ], 403);
        }

        if ($learnerDetail->plan_end_date < date('Y-m-d')){
        
            return response()->json([
                'status'  => 'expired',
                'message' => 'Plan expired'
            ], 403);
        }
        
        $branch = Branch::where('id', $learner->branch_id)->select('extend_days')->first();
        $extendDay = $branch->extend_days; // assume integer
        $today = Carbon::today();
        $endDate = Carbon::parse($learnerDetail->plan_end_date);

        $diffInDays = $today->diffInDays($endDate, false);
        if ($extendDay > 0) {
            $inextendDate = $endDate->copy()->addDays($extendDay);
        } else {
            $inextendDate = $endDate; // fallback to original end date
        }
        $diffExtendDay = $today->diffInDays($inextendDate, false);
    
        /* 5️⃣ Attendance logic */
        $attendance = Attendance::where('learner_id', $learner->id)
            ->where('date', today())
            ->first();

        if (!$attendance) {
            Attendance::create([
                'learner_id' => $learner->id,
                'library_id' => $learner->library_id,
                'branch_id' => $learner->branch_id,
                'date'       => today(),
                'in_time'    => now(),
                'attendance' => 1
            ]);

            

            return response()->json([
                'status'  => 'success',
                'message' => 'Thank You! Punch IN successful'
            ]);
        }

        // Punch OUT
        $attendance->update([
            'out_time' => now(),
            'attendance' => 1
        ]);


        return response()->json([
            'status'  => 'success',
            'message' => 'Thank You! Punch OUT successful'
        ]);
    }



}
