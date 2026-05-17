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
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\CarbonPeriod;


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

        $fromDate = $this->parseAttendanceDate($request->from_date);
        $toDate   = $this->parseAttendanceDate($request->to_date);

    } elseif ($request->filled('date')) {

        $fromDate = $this->parseAttendanceDate($request->date);
        $toDate   = $this->parseAttendanceDate($request->date);

    } else {

        $fromDate = Carbon::today()->toDateString();
        $toDate   = Carbon::today()->toDateString();
    }

    /*
    |--------------------------------------------------------------------------
    | Future Date Validation
    |--------------------------------------------------------------------------
    */

    if (
        Carbon::parse($fromDate)->isFuture() ||
        Carbon::parse($toDate)->isFuture()
    ) {
        return response()->json([
            'status' => false,
            'message' => 'Future dates are not allowed'
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Date Range Validation
    |--------------------------------------------------------------------------
    */

    if ($fromDate > $toDate) {

        return response()->json([
            'status' => false,
            'message' => 'From date cannot be greater than To date'
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Branch Filter
    |--------------------------------------------------------------------------
    */

    $branchId = $request->filled('branch_id')
        ? $request->branch_id
        : getCurrentBranch();

    /*
    |--------------------------------------------------------------------------
    | Main Learner Query
    |--------------------------------------------------------------------------
    */

    $query = DB::table('learners')

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

        ->leftJoin(
            'plan_types',
            'learner_detail.plan_type_id',
            '=',
            'plan_types.id'
        )

        ->where('learners.status', 1)

        ->where('learners.library_id', authLibraryId());

    /*
    |--------------------------------------------------------------------------
    | Learner Filters
    |--------------------------------------------------------------------------
    */

    if ($request->filled('learner_id')) {

        $query->where('learners.id', $request->learner_id);
    }

    if ($request->filled('learner_name')) {

        $query->where(
            'learners.name',
            'like',
            '%' . $request->learner_name . '%'
        );
    }

    if ($request->filled('search')) {

        $query->where(
            'learners.name',
            'like',
            '%' . $request->search . '%'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Branch Filter
    |--------------------------------------------------------------------------
    */

    if ($branchId) {

        $query->where('learners.branch_id', $branchId);
    }

    /*
    |--------------------------------------------------------------------------
    | Limit
    |--------------------------------------------------------------------------
    */

    $limit = (int) $request->input('limit', 100);

    $limit = $limit > 0 && $limit <= 200
        ? $limit
        : 100;

    /*
    |--------------------------------------------------------------------------
    | Fetch Learners
    |--------------------------------------------------------------------------
    */

    $learners = $query->select(

            'learners.id as learner_id',
            'learners.name',
            'learners.mobile',
            'learners.seat_no',

            'plan_types.name as plan_type_name',
            'plan_types.start_time',
            'plan_types.end_time',

            'learner_detail.plan_end_date'
        )

        ->latest('learners.id')

        ->limit($limit)

        ->get();

    /*
    |--------------------------------------------------------------------------
    | Create Date Range
    |--------------------------------------------------------------------------
    */

    $dateRange = CarbonPeriod::create($fromDate, $toDate);

    /*
    |--------------------------------------------------------------------------
    | Final Attendance Data
    |--------------------------------------------------------------------------
    */

    $data = collect();

    foreach ($learners as $learner) {

        /*
        |--------------------------------------------------------------------------
        | Clone Date Range
        |--------------------------------------------------------------------------
        |
        | Important because CarbonPeriod gets exhausted in loops
        |
        */

        foreach (CarbonPeriod::create($fromDate, $toDate) as $date) {

            $formattedDate = $date->format('Y-m-d');

            /*
            |--------------------------------------------------------------------------
            | Attendance Query
            |--------------------------------------------------------------------------
            */

            $attendance = DB::table('attendances')

                ->where('learner_id', $learner->learner_id)

                ->whereDate('date', $formattedDate)

                ->first();

            /*
            |--------------------------------------------------------------------------
            | Response Row
            |--------------------------------------------------------------------------
            */

            $data->push([

                'learner_id' => $learner->learner_id,

                'name' => $learner->name,

                'seat_no' => $learner->seat_no,

                'plan_type' => $learner->plan_type_name,

                'plan_end_date' => $learner->plan_end_date
                    ? Carbon::parse($learner->plan_end_date)
                        ->format('Y-m-d')
                    : null,

                'learner_plan_status' => $this->safeLearnerPlanStatus(
                    $learner->plan_end_date,
                    $learner->learner_id
                ),

                'shift_timing' => $this->formatShiftTiming(
                    $learner->start_time,
                    $learner->end_time
                ),

                /*
                |--------------------------------------------------------------------------
                | Always Show Date
                |--------------------------------------------------------------------------
                */

                'attendance_date' => $formattedDate,

                /*
                |--------------------------------------------------------------------------
                | Punch In
                |--------------------------------------------------------------------------
                */

                'punch_in' => (
                    $attendance &&
                    $attendance->in_time
                )
                    ? Carbon::parse($attendance->in_time)
                        ->format('h:i A')
                    : null,

                /*
                |--------------------------------------------------------------------------
                | Punch Out
                |--------------------------------------------------------------------------
                */

                'punch_out' => (
                    $attendance &&
                    $attendance->out_time
                )
                    ? Carbon::parse($attendance->out_time)
                        ->format('h:i A')
                    : null,

                /*
                |--------------------------------------------------------------------------
                | Duration
                |--------------------------------------------------------------------------
                */

                'duration_in_library' => $attendance
                    ? $this->formatAttendanceDuration(
                        $attendance->in_time,
                        $attendance->out_time
                    )
                    : null,

                /*
                |--------------------------------------------------------------------------
                | Attendance Status
                |--------------------------------------------------------------------------
                */

                'attendance_status' => (
                    $attendance &&
                    $attendance->attendance == 1
                )
                    ? 'Present'
                    : 'Absent',
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Summary Counts
    |--------------------------------------------------------------------------
    */

    $totalStudents = $data->count();

    $presentStudents = $data
        ->where('attendance_status', 'Present')
        ->count();

    $absentStudents = $data
        ->where('attendance_status', 'Absent')
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

                'total_members' => $totalStudents,

                'present_members' => $presentStudents,

                'absent_members' => $absentStudents,
            ],

            'filters' => [

                'from_date' => Carbon::parse($fromDate)
                    ->format('d/m/Y'),

                'to_date' => Carbon::parse($toDate)
                    ->format('d/m/Y'),
            ],

            'attendance' => $data
        ]
    ]);
}

public function attendanceLogs($request)
{
    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    $validator = Validator::make($request->all(), [

        'learner_id' => 'required|integer|exists:learners,id',

        'date' => 'required|date_format:Y-m-d',
    ]);

    /*
    |--------------------------------------------------------------------------
    | Validation Error Response
    |--------------------------------------------------------------------------
    */

    if ($validator->fails()) {

        return response()->json([

            'status' => false,

            'message' => 'Validation failed',

            'errors' => $validator->errors(),

        ], 422);
    }

    try {

        /*
        |--------------------------------------------------------------------------
        | Format Date
        |--------------------------------------------------------------------------
        */

      $date = $request->date;

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

                'status' => true,

                'message' => 'No attendance logs found',

                'data' => [

                    'learner_id' => $request->learner_id,

                    'date' => $request->date,

                    'logs' => []
                ]
            ]);
        }


        $totalLogs = $logs->count();


        /*
        |--------------------------------------------------------------------------
        | Format Response
        |--------------------------------------------------------------------------
        |
        | Logic:
        |
        | 1st Punch  => IN
        | 2nd Punch  => OUT
        | 3rd Punch  => IN
        | 4th Punch  => OUT
        |
        | Last Punch Always OUT
        |--------------------------------------------------------------------------
        */

        /*
        |--------------------------------------------------------------------------
        | Format Logs
        |--------------------------------------------------------------------------
        */
        $response = $logs

            ->values()

            ->map(function ($row, $index) use ($totalLogs) {

                /*
                |--------------------------------------------------------------------------
                | Punch Type
                |--------------------------------------------------------------------------
                */

                if ($index == ($totalLogs - 1)) {

                    /*
                    |--------------------------------------------------------------------------
                    | Last Punch Always OUT
                    |--------------------------------------------------------------------------
                    */

                    $punchType = 'OUT';

                } else {

                    /*
                    |--------------------------------------------------------------------------
                    | Alternate IN / OUT
                    |--------------------------------------------------------------------------
                    */

                    $punchType = $index % 2 == 0
                        ? 'IN'
                        : 'OUT';
                }

                return [

                    'punch' => $punchType,

                    'punch_time' => Carbon::parse(
                        $row->punch_datetime
                    )->format('h:i A'),

                    'source' => $row->source,

                    'datetime' => Carbon::parse(
                        $row->punch_datetime
                    )->format('Y-m-d h:i A'),
                ];
            });

        /*
        |--------------------------------------------------------------------------
        | First Punch Time
        |--------------------------------------------------------------------------
        */

        $firstPunch = Carbon::parse(
            $logs->first()->punch_datetime
        )->format('h:i A');

        /*
        |--------------------------------------------------------------------------
        | Last Punch Time
        |--------------------------------------------------------------------------
        */

        $lastPunch = Carbon::parse(
            $logs->last()->punch_datetime
        )->format('h:i A');

        /*
        |--------------------------------------------------------------------------
        | Final Response
        |--------------------------------------------------------------------------
        */

        return response()->json([

            'status' => true,

            'message' => 'Attendance logs fetched successfully',

            'data' => [

                'learner_id' => $request->learner_id,

                'date' => Carbon::parse($date)
                    ->format('Y-m-d'),

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

            'line' => $e->getLine(),

            'file' => $e->getFile(),
        ]);

        /*
        |--------------------------------------------------------------------------
        | Error Response
        |--------------------------------------------------------------------------
        */

        return response()->json([

            'status' => false,

            'message' => $e->getMessage(),

            'data' => []

        ], 500);
    }
}

private function parseAttendanceDate($date)
{
    return Carbon::parse($date)->format('Y-m-d');
}

private function formatShiftTiming($startTime, $endTime)
{
    if (!$startTime || !$endTime) {
        return null;
    }

    return $this->formatTime($startTime) . ' to ' . $this->formatTime($endTime);
}

private function formatTime($time)
{
    return Carbon::parse($time)->format('h:i A');
}

private function formatAttendanceDuration($inTime, $outTime)
{
    if (!$inTime || !$outTime) {
        return null;
    }

    $in = Carbon::parse($inTime);
    $out = Carbon::parse($outTime);

    if ($out->lessThan($in)) {
        return null;
    }

    $totalMinutes = $in->diffInMinutes($out);
    $hours = intdiv($totalMinutes, 60);
    $minutes = $totalMinutes % 60;

    if ($hours > 0 && $minutes > 0) {
        return $hours . ':' . str_pad($minutes, 2, '0', STR_PAD_LEFT) . ' Hrs';
    }

    if ($hours > 0) {
        return $hours . ' Hrs';
    }

    return $minutes . ' Min';
}

private function safeLearnerPlanStatus($planEndDate, $learnerId)
{
    if (!$planEndDate) {
        return null;
    }

    try {
        return strip_tags(getUserStatusWithSpan($planEndDate, $learnerId));
    } catch (\Throwable $e) {
        Log::error('Attendance summary plan status error', [
            'learner_id' => $learnerId,
            'message' => $e->getMessage(),
        ]);

        return null;
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

public function manualAttendance(
    $learnerId,
    $attendance,
    $date,
    $time,
    $timeType,
    $libraryId,
    $branchId
) {

    /*
    |--------------------------------------------------------------------------
    | Current Datetime
    |--------------------------------------------------------------------------
    */

    $currentTime = $this->manualPunchDateTime($date, $time);
   

    /*
    |--------------------------------------------------------------------------
    | Find Existing Attendance
    |--------------------------------------------------------------------------
    */

    $attendanceRecord = Attendance::where('learner_id', $learnerId)
        ->whereDate('date', $date)
        ->first();

    /*
    |--------------------------------------------------------------------------
    | IN Time
    |--------------------------------------------------------------------------
    */

    if ($timeType == 'in') {


        /*
        |--------------------------------------------------------------------------
        | Create Attendance
        |--------------------------------------------------------------------------
        */

        if (!$attendanceRecord) {

            Attendance::create([

                'learner_id' => $learnerId,

                'attendance' => $attendance,

                'date' => $date,

                'in_time' => $currentTime,

                'library_id' => $libraryId,

                'branch_id' => $branchId,
            ]);

        } else {

            /*
            |--------------------------------------------------------------------------
            | Update Existing IN Time
            |--------------------------------------------------------------------------
            */

            $attendanceRecord->update([

                'in_time' => $currentTime,

                'attendance' => $attendance,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Attendance Log
        |--------------------------------------------------------------------------
        */

        $this->logInsert([

            'learner_id' => $learnerId,

            'branch_id' => $branchId,

            'punch_datetime' => $currentTime,

            'source' => 'MANUAL'
        ]);

        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | OUT Time
    |--------------------------------------------------------------------------
    */

    if ($timeType == 'out') {

        /*
        |--------------------------------------------------------------------------
        | Attendance Must Exist
        |--------------------------------------------------------------------------
        */

        if (!$attendanceRecord) {

            throw new \Exception(
                'Cannot mark OUT before IN attendance'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Update OUT Time
        |--------------------------------------------------------------------------
        */
        

        $attendanceRecord->update([

            'out_time' => $currentTime,

            'attendance' => $attendance,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Attendance Log
        |--------------------------------------------------------------------------
        */

        $this->logInsert([

            'learner_id' => $learnerId,

            'branch_id' => $branchId,

            'punch_datetime' => $currentTime,

            'source' => 'MANUAL'
        ]);

        return true;
    }

    return false;
}

private function manualPunchDateTime($date, $time)
{
    if (in_array($time, ['in', 'out'])) {
        return now();
    }

    return Carbon::parse($date . ' ' . $time);
}


}
