<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Learner;
use App\Models\LearnerDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\DB;


class AttendanceService
{
    
public function summary($request)
{
    /*
    |--------------------------------------------------------------------------
    | Default Date Filter
    |--------------------------------------------------------------------------
    */

    if ($request->filled('from_date') && $request->filled('to_date')) {

        $fromDate = Carbon::parse($request->from_date)->toDateString();
        $toDate   = Carbon::parse($request->to_date)->toDateString();

    } elseif ($request->filled('date')) {

        $fromDate = Carbon::parse($request->date)->toDateString();
        $toDate   = Carbon::parse($request->date)->toDateString();

    } else {

        $fromDate = Carbon::now()->startOfMonth()->toDateString();
        $toDate   = Carbon::today()->toDateString();
    }

    /*
    |--------------------------------------------------------------------------
    | Main Query
    |--------------------------------------------------------------------------
    */

    $query = Learner::leftJoin('attendances', 'learners.id', '=', 'attendances.learner_id')

        ->leftJoin('learner_detail', function ($join) {

            $join->on('learners.id', '=', 'learner_detail.learner_id')
                ->where('learner_detail.id', function ($query) {

                    $query->select('id')
                        ->from('learner_detail as ld')
                        ->whereColumn('ld.learner_id', 'learner_detail.learner_id')
                        ->where('ld.status', 1)
                        ->latest()
                        ->limit(1);
                });
        })

        ->leftJoin('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')

        ->where('learners.status', 1)

        ->whereBetween('attendances.date', [$fromDate, $toDate]);

    /*
    |--------------------------------------------------------------------------
    | Learner Filter
    |--------------------------------------------------------------------------
    */

    if ($request->filled('learner_id')) {

        $query->where('learners.id', $request->learner_id);
    }

    /*
    |--------------------------------------------------------------------------
    | Branch Filter (Optional)
    |--------------------------------------------------------------------------
    */

    if ($request->filled('branch_id')) {

        $query->where('attendances.branch_id', $request->branch_id);
    }

    /*
    |--------------------------------------------------------------------------
    | Select Data
    |--------------------------------------------------------------------------
    */

    $attendance = $query->select(
            'learners.id as learner_id',
            'learners.name',
            'learners.mobile',
            'learners.seat_no',
            'plan_types.name as plan_type_name',
            'learner_detail.plan_end_date',
            'attendances.in_time',
            'attendances.out_time',
            'attendances.attendance',
            'attendances.date'
        )
        ->latest('attendances.date')
        ->get();

    /*
    |--------------------------------------------------------------------------
    | Format Response Data
    |--------------------------------------------------------------------------
    */

    $data = $attendance->map(function ($item) {

        return [

            'learner_id'        => $item->learner_id,
            'name'              => $item->name,
            'seat_no'           => $item->seat_no,
            'plan_type'         => $item->plan_type_name,
            'plan_end_date'     => $item->plan_end_date
                                        ? Carbon::parse($item->plan_end_date)->format('d/m/Y')
                                        : null,

            'attendance_date'   => $item->date
                                        ? Carbon::parse($item->date)->format('d/m/Y')
                                        : null,

            'punch_in'          => $item->in_time
                                        ? Carbon::parse($item->in_time)->format('h:i A')
                                        : null,

            'punch_out'         => $item->out_time
                                        ? Carbon::parse($item->out_time)->format('h:i A')
                                        : null,

            'attendance_status' => $item->attendance == 1
                                        ? 'Present'
                                        : 'Absent',
        ];
    });

    /*
    |--------------------------------------------------------------------------
    | Summary Counts
    |--------------------------------------------------------------------------
    */

    $totalStudents   = $attendance->count();

    $presentStudents = $attendance
                        ->where('attendance', 1)
                        ->count();

    $absentStudents  = $attendance
                        ->where('attendance', 0)
                        ->count();

    /*
    |--------------------------------------------------------------------------
    | Final Response
    |--------------------------------------------------------------------------
    */

   return response()->json([

        'status' => true,

        'message' => 'Attendance fetched successfully',

        'data' => [

            'summary' => [

                'total_members'   => $totalStudents,
                'present_members' => $presentStudents,
                'absent_members'  => $absentStudents,
            ],

            'filters' => [

                'from_date' => Carbon::parse($fromDate)->format('d/m/Y'),
                'to_date'   => Carbon::parse($toDate)->format('d/m/Y'),
            ],

            'attendance' => $data
        ]

    ]);
}

public function attendanceLogs($request)
{
    
    try {

        /*
        |--------------------------------------------------------------------------
        | Validation
        |--------------------------------------------------------------------------
        */

        $request->validate([

            'learner_id' => 'required|integer|exists:learners,id',
            'date'       => 'required|date_format:d/m/Y',
        ]);

       

        /*
        |--------------------------------------------------------------------------
        | Format Date
        |--------------------------------------------------------------------------
        */

        $date = Carbon::createFromFormat(
                    'd/m/Y',
                    $request->date
                )->format('Y-m-d');

        /*
        |--------------------------------------------------------------------------
        | Get Logs
        |--------------------------------------------------------------------------
        */

        $logs = DB::table('learner_attendance_logs')

            ->where('learner_id', $request->learner_id)

            ->whereDate('punch_datetime', $date)

            ->orderBy('punch_datetime')

            ->get();

        /*
        |--------------------------------------------------------------------------
        | Empty Logs
        |--------------------------------------------------------------------------
        */

        if ($logs->isEmpty()) {

            return response()->json([

                'status'  => true,
                'message' => 'No attendance logs found',
                'data'    => [

                    'learner_id' => $request->learner_id,
                    'date'       => $request->date,
                    'logs'       => []

                ]

            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Format Logs
        |--------------------------------------------------------------------------
        */

        $response = $logs->map(function ($row) {

            return [

                'punch_time' => Carbon::parse(
                                    $row->punch_datetime
                                )->format('h:i A'),

                'source' => $row->source,

                'datetime' => Carbon::parse(
                                    $row->punch_datetime
                                )->format('d/m/Y h:i A'),
            ];
        });

        /*
        |--------------------------------------------------------------------------
        | Final Response
        |--------------------------------------------------------------------------
        */

        return response()->json([

            'status'  => true,

            'message' => 'Attendance logs fetched successfully',

            'data' => [

                'learner_id' => $request->learner_id,

                'date' => Carbon::parse($date)
                            ->format('d/m/Y'),

                'logs' => $response
            ]

        ]);

    } catch (\Throwable $e) {

        /*
        |--------------------------------------------------------------------------
        | Error Log
        |--------------------------------------------------------------------------
        */

        Log::error('Attendance log error', [

            'message' => $e->getMessage(),
            'line'    => $e->getLine(),
            'file'    => $e->getFile(),
        ]);

        /*
        |--------------------------------------------------------------------------
        | Error Response
        |--------------------------------------------------------------------------
        */

        return response()->json([

            'status'  => false,

            'message' => 'Something went wrong',

            'data'    => []

        ], 500);
    }
}

public function processAttendance($learnerId, $branchId, $source)
{
    if (!$branchId) {
        return [
            'status'  => 'error',
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
                    'status'  => 'error',
                    'message' => 'Your plan has been deleted',
                    'code'    => 403
                ];
            }

            if ($operation === 'closeSeat') {
                return [
                    'status'  => 'error',
                    'message' => 'Your plan has been closed',
                    'code'    => 403
                ];
            }
        }

        return [
            'status'  => 'expired',
            'message' => 'Plan expired',
            'code'    => 403
        ];
    }

    if ($learnerDetail->branch_id != $branchId) {
        return [
            'status'  => 'error',
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
            'status'  => 'expired',
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
            'status'  => $extension ? 'extension' : 'success',
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
            'status'  => 'error',
            'message' => 'Attendance not marked. Please try again.',
            'code'    => 500
        ];
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


}
