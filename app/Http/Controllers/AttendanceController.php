<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Learner;
use App\Models\LearnerDetail;
use Illuminate\Support\Str;
use Spatie\FlareClient\View;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Validator;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\AttendanceService;
use Mpdf\Mpdf;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Mpdf\HTMLParserMode;

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
       

         $detail = LearnerDetail::withoutGlobalScopes()
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
                'branches.id as branch_id'
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

        return response()->json(AttendanceService::attendanceQrTokens((int) $branchId));
    }
    //Learner enters UID + Mobile page
    public function showLink(){
        return view('attendance.link-page');
    }
    //Server validates UID + Mobile ->(only if valid) Save verified learner token (session / cookie)
    public function verifyLearner(Request $request)
    {
        \Log::info('Attendqance verify', ['request' => $request->all()]);
       $validator = Validator::make($request->all(), [
            'login_with' => 'required|in:dob,email,learner_no',
            'uid'        => 'required',
            'mobile'     => 'required|regex:/^[5-9]\d{9}$/'
        ], [
            'login_with.required' => 'Please choose login type',
            'uid.required'        => 'This field is required',
            'mobile.required'     => 'Mobile number is required',
            'mobile.regex'        => 'Enter valid a mobile number'
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
            $dob = Carbon::createFromFormat('d/m/Y', $request->uid)->format('Y-m-d');
        } catch (\Exception $e) {
            $dob = null;
        }
            \Log::info('Attendqance dob', ['dob' => $dob]);

 
        $learner = Learner::withoutGlobalScopes()
        ->where('mobile', encryptData($request->mobile))
        ->when($request->login_with === 'learner_no' && $request->uid, function ($q) use ($request) {
            $q->where('learner_no', $request->uid);
        })
        ->when($request->login_with === 'dob' && $dob, function ($q) use ($dob) {
            
            $q->where('dob', $dob);
        })
        ->when(
            $request->login_with === 'email' &&
            filter_var($request->uid, FILTER_VALIDATE_EMAIL),
            function ($q) use ($request) {
                $q->where('email', encryptData($request->uid));
            }
        )->first();
       
        
        if (!$learner) {
            return response()->json([
                'status' => false,
                'message' => 'Sorry, we couldn’t find your record. Please verify your details and try again.'
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
        return AttendanceService::validateQrToken($qrToken);

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
            $learner = Learner::where('id', $learnerId)->first();

            if (!$learner) {
                \Log::warning('Learner not found');
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Learner not found'
                ], 404);
            }

            // 2. Validate QR (your existing logic)
            $branchId = $this->validateQrToken($request->qr);


              /**
         * 🔁 DUPLICATE SCAN PROTECTION (MOST IMPORTANT)
            * Same QR + same session → ignore for 15 seconds
            */
            $now = now()->timestamp;
            $lastScanAt = session('last_scan_at', 0);

            if (($now - $lastScanAt) < 5) {
                // 🚫 DO NOT validate QR
                return response()->json([
                    'status'  => 'success',
                    'message' => 'Attendance captured'
                ]);
            }

            // Update scan time immediately (important)
            session(['last_scan_at' => $now]);
    


            \Log::info('SESSION CHECK', [
                'verified' => session('attendance_verified'),
                'session_token' => session('verify_token'),
                'request_token' => $request->verify_token,
                'branchId'=>$branchId,
                'request-qr'=>$request->qr
            ]);

            if (!$branchId) {
                
                return response()->json([
                    'status'  => false,
                    'type'    => 'error',
                    'message' => 'QR expired or invalid'
                ], 403);
            }


        $result = $this->processAttendance($learnerId, $branchId, 'QR');

        return response()->json($result, $result['code'] ?? 200);

           

        //         /* 🔹 Get latest learner plan */
        // $learnerDetail = LearnerDetail::where('learner_id', $learnerId)
        //     ->orderBy('plan_end_date', 'DESC')
        //     ->first();

        // /* 1️⃣ No plan OR inactive plan */
        // if (!$learnerDetail || $learnerDetail->status != 1) {

        //     $detail = LearnerDetail::withTrashed()
        //         ->where('learner_id', $learnerId)
        //         ->orderBy('plan_end_date', 'DESC')
        //         ->first();

        //     if ($detail) {

        //         $operation = DB::table('learner_operations_log')
        //             ->where('learner_detail_id', $detail->id)
        //             ->value('operation');

        //         if ($operation === 'deleteSeat') {
        //             return response()->json([
        //                 'status'  => 'error',
        //                 'message' => 'Your plan has been deleted'
        //             ], 403);
        //         }

        //         if ($operation === 'closeSeat') {
        //             return response()->json([
        //                 'status'  => 'error',
        //                 'message' => 'Your plan has been closed'
        //             ], 403);
        //         }
        //     }

        //     return response()->json([
        //         'status'  => 'expired',
        //         'message' => 'Plan expired'
        //     ], 403);
        // }

        // /* 2️⃣ Wrong branch QR */
        // if ($learnerDetail->branch_id != $branchId) {
        //     return response()->json([
        //         'status'  => 'error',
        //         'message' => 'Ohh, it seems like you scanned the wrong library QR code.'
        //     ], 403);
        // }
        // $branch = Branch::where('id', $branchId)->select('extend_days','library_id')->first();
      
        // $extendDay = $branch->extend_days; // assume integer
       
        // $today = Carbon::today();
        // $detail= LearnerDetail::where('learner_id', $learnerId)
        //     ->orderBy('plan_end_date', 'DESC')->where('status',1)->first();
        // $endDate = Carbon::parse($detail->plan_end_date);
        
        // $diffInDays = $today->diffInDays($endDate, false);
        // if ($extendDay > 0) {
        //     $inextendDate = $endDate->copy()->addDays($extendDay);
        // } else {
        //     $inextendDate = $endDate; // fallback to original end date
        // }
      
        // $diffExtendDay = $today->diffInDays($inextendDate, false);

        // /* 3️⃣ Plan expired */
        // // if ($learnerDetail->plan_end_date < date('Y-m-d')) {
        // \Log::info('diffExtendDay',['diff'=>$diffExtendDay]);
        // \Log::info('diffInDays',['diff'=>$diffInDays]);
        // if ($diffExtendDay < 0 || !$detail) {
        //     return response()->json([
        //         'status'  => 'expired',
        //         'message' => 'Plan expired'
        //     ], 403);
        // }
        // $extension=false;

        // if ($diffInDays < 0 && $diffExtendDay >= 0){
        //     $extension=true;
        // }

        // \Log::info('success part hit extension',['extension'=>$extension]);
            
       
            
        //     $attendance = 1;
        //     $date = date('Y-m-d');
        //     $currentTime = now();
        //     $libraryId=$branch->library_id;

        //     $existingAttendance = Attendance::where('learner_id', $learnerId)
        //         ->where('date', $date)
        //         ->first();

        //   try {
        //     DB::beginTransaction();
        //        // 🔐 Log insert MUST succeed
        //     $data = [
        //         'learner_id'     => $learnerId,
        //         'branch_id'      => $branchId,
        //         'punch_datetime' => $currentTime,
        //         'source'         => 'QR'
        //     ];

        //     $this->logInsert($data); // throws exception if failed
        //     if ($existingAttendance) {

                \Log::info('attendance update',['extension'=>$extension]);

        //         $existingAttendance->out_time = $currentTime;

        //         if (!$existingAttendance->in_time) {
        //             $existingAttendance->in_time = $currentTime;
        //         }

        //         $existingAttendance->save();

        //     } else {

                \Log::info('attendance add',['extension'=>$extension]);

        //         Attendance::create([
        //             'learner_id' => $learnerId,
        //             'attendance' => $attendance,
        //             'date'       => $date,
        //             'in_time'    => $currentTime,
        //             'out_time'   => $currentTime,
        //             'library_id' => $branch->library_id,
        //             'branch_id'  => $branchId,
        //         ]);
        //     }

         
        //     DB::commit();
        //     if($extension==true){
        //         return response()->json([
        //             'status'  => 'extension',
        //             'message' => 'Thank You! Attendance marked'
        //         ]);
        //     }else{
        //          return response()->json([
        //             'status'  => 'success',
        //             'message' => 'Thank You! Attendance marked'
        //         ]);
        //     }

           

        // } catch (\Throwable $e) {

        //     DB::rollBack();

        //     \Log::error('Attendance or log failed', [
        //         'learner_id' => $learnerId,
        //         'error'      => $e->getMessage(),
        //     ]);

        //     return response()->json([
        //         'status'  => 'error',
        //         'message' => 'Attendance not marked. Please try again.'
        //     ], 500);
        // }

    }


    //Success Page
    public function markSuccess(){
        return view('attendance.success');
    }

   
private function processAttendance($learnerId, $branchId, $source)
{
    if (!$branchId) {
        return [
            'status'  => false,
            'type'    => 'error',
            'message' => 'QR expired or invalid',
            'code'    => 403
        ];
    }

    $learnerDetail = LearnerDetail::where('learner_id', $learnerId)
        ->orderBy('plan_end_date', 'DESC')
        ->first();
    $learner=Learner::where('id',$learnerId)->withTrashed()->select('status')->first();

    if (!$learnerDetail || $learner->status != 1) {
         
        $detail = LearnerDetail::withTrashed()
            ->where('learner_id', $learnerId)
            ->orderBy('plan_end_date', 'DESC')
            ->first();
         \Log::info('processAttendance', [
                'detail' => $detail->id,
                'branchdetail'=>$learnerDetail->branch_id,
                'branch'=> $branchId
               
            ]);
        if ($detail) {
            $operation = DB::table('learner_operations_log')
                ->where('learner_detail_id', $detail->id)
                ->value('operation');

            if ($operation === 'deleteSeat') {
                return [
                    'status'  => false,
                    'type'    => 'error',
                    'message' => 'Your plan has been deleted',
                    'code'    => 403
                ];
            }

            if ($operation === 'closeSeat') {
                return [
                    'status'  => false,
                    'type'    => 'error',
                    'message' => 'Your plan has been closed',
                    'code'    => 403
                ];
            }
        }

        return [
            'status'  => false,
            'type'    => 'expired',
            'message' => 'Plan expired',
            'code'    => 403
        ];
    }

    if ($learnerDetail->branch_id != $branchId) {
        return [
            'status'  => false,
            'type'    => 'error',
            'message' => 'Ohh, it seems like you scanned the wrong library QR code.',
            'code'    => 403
        ];
    }

    $branch = Branch::where('id', $branchId)
        ->select('extend_days', 'library_id')
        ->first();

    $extendDay = $branch->extend_days;
    $today = Carbon::today();

    $endDate = Carbon::parse($learnerDetail->plan_end_date);

    $diffInDays = $today->diffInDays($endDate, false);

    $inextendDate = $extendDay > 0
        ? $endDate->copy()->addDays($extendDay)
        : $endDate;

    $diffExtendDay = $today->diffInDays($inextendDate, false);

      \Log::info('Process', [
                
                'diffExtendDay' => $diffExtendDay,
                'diffInDays'=>$diffInDays ,
                'request-diffExtendDay'=>$diffExtendDay < 0
            ]);

    if ($diffExtendDay < 0) {
        return [
            'status'  => false,
            'type'    => 'expired',
            'message' => 'Plan expired',
            'code'    => 403
        ];
    }

    $extension = ($diffInDays < 0 && $diffExtendDay >= 0);

    $date = today();
    $currentTime = now();

    $existingAttendance = Attendance::where('learner_id', $learnerId)
        ->where('date', $date)
        ->first();

    try {
        DB::beginTransaction();

        $data = [
            'learner_id'     => $learnerId,
            'branch_id'      => $branchId,
            'punch_datetime' => $currentTime,
            'source'         => $source
        ];

        $this->logInsert($data);

        if ($existingAttendance) {
            $existingAttendance->out_time = $currentTime;

            if (!$existingAttendance->in_time) {
                $existingAttendance->in_time = $currentTime;
            }

            $existingAttendance->save();
        } else {
            Attendance::create([
                'learner_id' => $learnerId,
                'attendance' => 1,
                'date'       => $date,
                'in_time'    => $currentTime,
                'out_time'   => $currentTime,
                'library_id' => $branch->library_id,
                'branch_id'  => $branchId,
            ]);
        }

        DB::commit();

        return [
            'status'  => true,
            'type'    => $extension ? 'extension' : 'success',
            'message' => 'Thank You! Attendance marked',
            'code'    => 200
        ];

    } catch (\Throwable $e) {
        DB::rollBack();

        \Log::error('Attendance or log failed', [
            'learner_id' => $learnerId,
            'error'      => $e->getMessage(),
        ]);

        return [
            'status'  => false,
            'type'    => 'error',
            'message' => 'Attendance not marked. Please try again.',
            'code'    => 500
        ];
    }
}


public function scan(Request $request)
{
    \Log::info('SCAN HIT', $request->all());

    
    $qrPayload = trim($request->qr ?? '');

    if (!$qrPayload) {
        \Log::warning('learnerNo failed');
        return response()->json([
        'status'  => 'error',
        'message' => 'Invalid QR'
    ], 403);
    }

    $decryptedData = decryptLearnerQrPayload($qrPayload);
    if ($decryptedData && isset($decryptedData['l_no'])) {
        $learnerNo = $decryptedData['l_no'];
    } else {
        $learnerNo = $qrPayload;
    }

    /* 3️⃣ Learner validation */
    $learner = Learner::where('learner_no', $learnerNo)
        ->first();

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

  
    $result = $this->processAttendance($learner->id, $learner->branch_id, 'SCAN');

    return response()->json($result, $result['code'] ?? 200);

    // $learnerDetail=LearnerDetail::where('learner_id',$learner->id)->where('status',1)->select('plan_end_date')->first();
    //  /* 1️⃣ No active plan */
    // if (!$learnerDetail) {
    //     return response()->json([
    //         'status'  => 'error',
    //         'message' => 'No active plan found'
    //     ], 403);
    // }



    // $branch = Branch::where('id', $learner->branch_id)->select('extend_days')->first();
    // $extendDay = $branch->extend_days; // assume integer
    // $today = Carbon::today();
    // $endDate = Carbon::parse($learnerDetail->plan_end_date);

    // $diffInDays = $today->diffInDays($endDate, false);
    // if ($extendDay > 0) {
    //     $inextendDate = $endDate->copy()->addDays($extendDay);
    // } else {
    //     $inextendDate = $endDate; // fallback to original end date
    // }
    // $diffExtendDay = $today->diffInDays($inextendDate, false);
    
    
   
    

    //     /* 2️⃣ Plan expired check */
    // //  if (Carbon::parse($learnerDetail->plan_end_date)->lt(today())) {
    // if ($diffExtendDay < 0) {
    //     return response()->json([
    //         'status'  => 'expired',
    //         'message' => 'Plan expired'
    //     ], 403);
    // }

    // $extension=false;

    // if ($diffInDays < 0 && $diffExtendDay > 0){
    //     $extension=true;
    // }

    // \Log::info('extension',$extension);
    // /* 5️⃣ Attendance logic */
    // $attendance = Attendance::where('learner_id', $learner->id)
    //     ->where('date', today())
    //     ->first();
    // $data = [
    //     'learner_id'     => $learner->id,
    //     'branch_id'      => $learner->branch_id,
    //     'punch_datetime' => now(),
    //     'source'         => 'SCAN'
    //     ];

    // try {
    //     DB::beginTransaction();
    //     $this->logInsert($data);
    //     /* -------------------------
    //      | Punch IN
    //      |--------------------------*/
    //     if (!$attendance) {

    //         Attendance::create([
    //             'learner_id' => $learner->id,
    //             'library_id' => $learner->library_id,
    //             'branch_id'  => $learner->branch_id,
    //             'date'       => today(),
    //             'in_time'    => now(),
    //             'attendance' => 1
    //         ]);

    //         $this->logInsert($data);

    //         DB::commit();
    //         if($extension==true){
    //             return response()->json([
    //                 'status'  => 'extension',
    //                 'message' => 'Thank You! Punch IN successful'
    //             ]);
    //         }else{
    //              return response()->json([
    //                 'status'  => 'success',
    //                 'message' => 'Thank You! Punch IN successful'
    //             ]);
    //         }
           
    //     }

    //     /* -------------------------
    //      | Punch OUT
    //      |--------------------------*/
    //     $attendance->update([
    //         'out_time'   => now(),
    //         'attendance' => 1
    //     ]);

        

    //     DB::commit();
    //     if($extension==true){
    //         return response()->json([
    //             'status'  => 'extension',
    //             'message' => 'Thank You! Punch OUT successful'
    //         ]);
    //     }else{
    //             return response()->json([
    //             'status'  => 'success',
    //             'message' => 'Thank You! Punch OUT successful'
    //         ]);
    //     }
       

    // } catch (\Throwable $e) {

    //     DB::rollBack();

    //     \Log::error('Attendance scan failed', [
    //         'learner_id' => $learner->id,
    //         'error'      => $e->getMessage(),
    //     ]);

    //     return response()->json([
    //         'status'  => false,
    //         'message' => 'Attendance not marked. Please try again.'
    //     ], 500);
    // }


    
}


    /* ===============================
       2️⃣ Attendance Summary Page (calendar)
    =============================== */
public function summary(Request $request, $learner)
{
    $learnerId = (int) $learner;
    $learnerModel = Learner::findOrFail($learnerId);
    $learnerName = $learnerModel->name;

    $today = Carbon::today();

    // Joining date = earliest plan_start_date across every learner_detail row
    // (their original join date, not just the currently active plan).
    $joiningDateRaw = LearnerDetail::withTrashed()
        ->where('learner_id', $learnerId)
        ->min('plan_start_date');
    $joiningDate = $joiningDateRaw ? Carbon::parse($joiningDateRaw)->startOfDay() : $today->copy();
    if ($joiningDate->gt($today)) {
        $joiningDate = $today->copy();
    }

    // Requested month, clamped to [joining month, current month] — no browsing
    // before the learner joined or past today.
    $minMonth = $joiningDate->copy()->startOfMonth();
    $maxMonth = $today->copy()->startOfMonth();
    $requestedMonth = Carbon::createFromDate(
        (int) $request->query('year', $today->year),
        (int) $request->query('month', $today->month),
        1
    )->startOfMonth();
    $displayMonth = $requestedMonth->lt($minMonth) ? $minMonth->copy() : $requestedMonth;
    $displayMonth = $displayMonth->gt($maxMonth) ? $maxMonth->copy() : $displayMonth;

    $canGoPrev = $displayMonth->gt($minMonth);
    $canGoNext = $displayMonth->lt($maxMonth);

    // Selected date, clamped to [joiningDate, today]; defaults to today when
    // today falls inside the displayed month, else the first valid day shown.
    $selectedDate = $request->query('date')
        ? Carbon::parse($request->query('date'))->startOfDay()
        : ($displayMonth->isSameMonth($today) ? $today->copy() : $displayMonth->copy());
    if ($selectedDate->lt($joiningDate)) {
        $selectedDate = $joiningDate->copy();
    }
    if ($selectedDate->gt($today)) {
        $selectedDate = $today->copy();
    }

    // Learner's assigned shift end time — used as the implied checkout when a
    // learner punched IN but never punched OUT.
    $activeDetail = LearnerDetail::with('planType')
        ->where('learner_id', $learnerId)
        ->where('status', 1)
        ->latest('id')
        ->first();
    $shiftEndTime = $activeDetail?->planType?->end_time;

    $attendanceRows = Attendance::where('learner_id', $learnerId)
        ->whereYear('date', $displayMonth->year)
        ->whereMonth('date', $displayMonth->month)
        ->get()
        ->keyBy(fn ($row) => Carbon::parse($row->date)->format('Y-m-d'));

    $daysInMonth = $displayMonth->daysInMonth;
    $startWeekday = $displayMonth->copy()->startOfMonth()->dayOfWeek;
    $totalCells = (int) (ceil(($startWeekday + $daysInMonth) / 7) * 7);

    $calendarCells = [];
    $logs = [];
    $presentCount = 0;
    $halfCount = 0;
    $absentCount = 0;
    $totalMinutes = 0;

    for ($i = 0; $i < $totalCells; $i++) {
        $cellDate = $displayMonth->copy()->startOfMonth()->addDays($i - $startWeekday);
        $inMonth = $cellDate->month === $displayMonth->month;

        if (!$inMonth) {
            $calendarCells[] = [
                'day' => $cellDate->day,
                'date' => null,
                'in_month' => false,
                'in_range' => false,
                'status' => 'none',
                'hours' => null,
            ];
            continue;
        }

        $dateStr = $cellDate->format('Y-m-d');
        $inRange = $cellDate->between($joiningDate, $today);
        $status = 'none';
        $hours = null;
        $checkIn = null;
        $checkOut = null;
        $impliedOut = false;

        if ($inRange) {
            $row = $attendanceRows->get($dateStr);

            if ($row && $row->in_time) {
                $checkIn = Carbon::parse($row->in_time);
                $hasRealOut = $row->out_time && !Carbon::parse($row->out_time)->eq($checkIn);

                if ($hasRealOut) {
                    $checkOut = Carbon::parse($row->out_time);
                    $status = 'full';
                } else {
                    $status = 'half';
                    $impliedOut = true;

                    if ($shiftEndTime) {
                        $checkOut = Carbon::parse($dateStr . ' ' . Carbon::parse($shiftEndTime)->format('H:i:s'));
                        if ($checkOut->lt($checkIn)) {
                            $checkOut->addDay();
                        }
                    }
                }

                if ($checkOut) {
                    $minutes = max(0, $checkIn->diffInMinutes($checkOut));
                    $hours = round($minutes / 60, 1);
                    $totalMinutes += $minutes;
                }
            } else {
                $status = 'absent';
            }

            match ($status) {
                'full' => $presentCount++,
                'half' => $halfCount++,
                'absent' => $absentCount++,
                default => null,
            };
        }

        $calendarCells[] = [
            'day' => $cellDate->day,
            'date' => $dateStr,
            'in_month' => true,
            'in_range' => $inRange,
            'status' => $status,
            'hours' => $hours,
        ];

        if ($inRange) {
            $logs[] = [
                'date' => $dateStr,
                'day_name' => $cellDate->format('D'),
                'day_num' => $cellDate->format('d'),
                'status' => $status,
                'check_in' => $checkIn?->format('h:i A'),
                'check_out' => $checkOut?->format('h:i A'),
                'implied_out' => $impliedOut,
                'hours' => $hours,
            ];
        }
    }

    $totalHoursLabel = sprintf('%d:%02d', intdiv($totalMinutes, 60), $totalMinutes % 60);

    return view('attendance.summary', [
        'learnerId' => $learnerId,
        'learnerName' => $learnerName,
        'displayMonth' => $displayMonth,
        'canGoPrev' => $canGoPrev,
        'canGoNext' => $canGoNext,
        'prevMonth' => $displayMonth->copy()->subMonth(),
        'nextMonth' => $displayMonth->copy()->addMonth(),
        'joiningDate' => $joiningDate->format('Y-m-d'),
        'today' => $today->format('Y-m-d'),
        'selectedDate' => $selectedDate->format('Y-m-d'),
        'calendarCells' => $calendarCells,
        'logs' => $logs,
        'presentCount' => $presentCount,
        'halfCount' => $halfCount,
        'absentCount' => $absentCount,
        'totalHoursLabel' => $totalHoursLabel,
    ]);
}

    /* ===============================
       Attendance Logs Detail Page (single date)
    =============================== */
public function logsPage($learner, $date)
{
    $learnerId = (int) $learner;
    $learnerModel = Learner::findOrFail($learnerId);

    $dateObj = Carbon::parse($date)->startOfDay();
    $today = Carbon::today();

    $joiningDateRaw = LearnerDetail::withTrashed()
        ->where('learner_id', $learnerId)
        ->min('plan_start_date');
    $joiningDate = $joiningDateRaw ? Carbon::parse($joiningDateRaw)->startOfDay() : $today->copy();

    if ($dateObj->lt($joiningDate) || $dateObj->gt($today)) {
        abort(404);
    }

    $attendanceRow = Attendance::where('learner_id', $learnerId)
        ->whereDate('date', $dateObj->format('Y-m-d'))
        ->first();

    $punchRows = DB::table('learner_attendance_logs')
        ->where('learner_id', $learnerId)
        ->whereDate('punch_datetime', $dateObj->format('Y-m-d'))
        ->orderBy('punch_datetime')
        ->get();

    $totalPunches = $punchRows->count();
    $punches = $punchRows->values()->map(function ($row, $index) use ($totalPunches) {
        $punchType = ($index === $totalPunches - 1) ? 'OUT' : ($index % 2 === 0 ? 'IN' : 'OUT');

        return [
            'punch' => $punchType,
            'time' => Carbon::parse($row->punch_datetime)->format('h:i A'),
            'source' => $row->source,
        ];
    });

    return view('attendance.logs', [
        'learnerId' => $learnerId,
        'learnerName' => $learnerModel->name,
        'date' => $dateObj->format('Y-m-d'),
        'dateLabel' => $dateObj->format('j M Y'),
        'punches' => $punches,
        'attendanceRow' => $attendanceRow,
    ]);
}


public function logs(Request $request)
{
    try {

        if (!$request->learner_id || !$request->date) {
            return response()->json([]);
        }

        // ✅ Validate date safely
        try {
            $date = Carbon::createFromFormat('Y-m-d', $request->date)->toDateString();
        } catch (\Exception $e) {
            return response()->json([]);
        }

        $logs = DB::table('learner_attendance_logs')
            ->where('learner_id', $request->learner_id)
            ->whereDate('punch_datetime', $date)
            ->orderBy('punch_datetime')
            ->get();

        // ✅ Handle empty records safely
        if ($logs->isEmpty()) {
            return response()->json([]);
        }

        // ✅ Format response
        $response = $logs->map(function ($row) {
            return [
                'punch_time' => Carbon::parse($row->punch_datetime)->format('h:i A'),'source'=>$row->source
            ];
        });

        return response()->json($response);

    } catch (\Throwable $e) {

        // 🔥 Log exact error
        Log::error('Attendance log error', [
            'message' => $e->getMessage(),
            'line'    => $e->getLine(),
            'file'    => $e->getFile(),
        ]);

        return response()->json([], 500);
    }
}

public function logInsert(array $data): void
{
   $inserted= DB::table('learner_attendance_logs')->insert([
        'learner_id'     => $data['learner_id'],
        'branch_id'      => $data['branch_id'],
        'punch_datetime' => $data['punch_datetime'],
        'source'         => $data['source'],
        'created_at'     => now(),
        
    ]);

     if (!$inserted) {
        throw new \Exception('Attendance log insert failed');
    }
}

public function view()
{
    return view('attendance.attendance-instructions');
}







public function downloadPdf()
{
    /* ---------------- BASIC CONFIG ---------------- */

    $defaultConfig = (new ConfigVariables())->getDefaults();
    $fontDirs = $defaultConfig['fontDir'];

    $defaultFontConfig = (new FontVariables())->getDefaults();
    $fontData = $defaultFontConfig['fontdata'];

    $mpdf = new Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4-L',

        'margin_top' => 15,
        'margin_bottom' => 15,
        'margin_left' => 15,
        'margin_right' => 15,

        /* REQUIRED FOR MULTI-LANGUAGE */
        'autoScriptToLang' => true,
        'autoLangToFont'   => true,
        'useOTL'           => 0xFF,

        'fontDir' => array_merge($fontDirs, [
            public_path('fonts'),
        ]),

        /* 🔥 IMPORTANT: array_merge (NOT +) */
        'fontdata' => array_merge($fontData, [
            'dejavu' => [
                'R' => 'DejaVuSans.ttf',
            ],
            'notodeva' => [
                'R' => 'NotoSansDevanagari-Regular.ttf',
            ],
        ]),

        /* Default font MUST be Unicode-safe */
        'default_font' => 'dejavu',

        /* Prevent font cache issues */
        'tempDir' => storage_path('app/mpdf'),
    ]);

    /* ---------------- QR CODE ---------------- */

    $qrPath = '';

    // QrCode::format('png')
    //     ->size(250)
    //     ->margin(2)
    //     ->generate('ATTENDANCE_QR_CODE', $qrPath);

    /* ---------------- HTML ---------------- */

    $html = '
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body {
        font-family: dejavu;
        font-size: 14px;
    }

    .page {
        border: 3px solid #0b4aa2;
        padding: 15px;
    }

    .header {
        text-align: center;
        margin-bottom: 12px;
    }

    .header h1 {
        margin: 0;
        font-size: 22px;
        color: #0b4aa2;
    }

    .header p {
        margin-top: 4px;
        font-size: 13px;
        color: #555;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    td {
        width: 33.33%;
        vertical-align: top;
        border: 2px solid #dce3f0;
        padding: 12px;
    }

    h2 {
        margin-top: 0;
        font-size: 16px;
        color: #0b4aa2;
        border-bottom: 2px solid #0b4aa2;
        padding-bottom: 5px;
    }

    ul {
        padding-left: 18px;
        line-height: 1.6;
    }

    .qr {
        text-align: center;
        margin-top: 25px;
    }

    .qr img {
        width: 160px;
    }

    .qr-text {
        margin-top: 10px;
        font-size: 13px;
    }
</style>
</head>

<body>

<div class="page">

    <div class="header">
        <h1>Attendance QR App – User Instructions</h1>
        <p>Please read the instructions carefully</p>
    </div>

    <table>
        <tr>
            <td style="font-family:dejavu;">
                <h2>English Instructions</h2>
                <ul>
                    <li>Download and install the Attendance QR App.</li>
                    <li>Allow camera permission.</li>
                    <li>Scan the QR code displayed in the library.</li>
                    <li>Wait for confirmation after scanning.</li>
                    <li>Do not scan the QR code repeatedly.</li>
                </ul>
            </td>

            <td style="font-family:notodeva;">
                <h2>हिंदी निर्देश</h2>
                <ul>
                    <li>Attendance QR App डाउनलोड करें।</li>
                    <li>कैमरा की अनुमति दें।</li>
                    <li>लाइब्रेरी में प्रदर्शित QR कोड स्कैन करें।</li>
                    <li>सफल स्कैन के बाद पुष्टि संदेश की प्रतीक्षा करें।</li>
                    <li>बार-बार QR कोड स्कैन न करें।</li>
                </ul>
            </td>

            <td>
                <h2>Download App</h2>
                <div class="qr">
                    <img src="' . $qrPath . '">
                    <div class="qr-text">
                        Scan QR code from library screen<br>
                        to mark your attendance
                    </div>
                </div>
            </td>
        </tr>
    </table>

</div>

</body>
</html>
';

    $mpdf->WriteHTML($html, HTMLParserMode::HTML_BODY);

    return response($mpdf->Output('attendance_instructions.pdf', 'S'))
        ->header('Content-Type', 'application/pdf')
        ->header('Content-Disposition', 'attachment; filename="attendance_instructions.pdf"');
}



}
