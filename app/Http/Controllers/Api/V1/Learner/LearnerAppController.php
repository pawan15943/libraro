<?php

namespace App\Http\Controllers\Api\V1\Learner;

use App\DTO\LearnerOperationDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\LearnerRenewRequest;
use App\Models\Branch;
use App\Models\Feature;
use App\Models\LearnerDetail;
use App\Services\AttendanceService;
use App\Services\LearnerOperationService;
use App\Services\LearnerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LearnerAppController extends Controller
{
    /**
     * Self-service learner detail — same payload shape as the staff
     * "library/learners/detail" API (LearnerService::getLearnerDetails()),
     * scoped to the authenticated learner's own record.
     */
    public function detail(LearnerService $service)
    {
        try {
            $learnerId = auth('learner_api')->id();

            if (!$learnerId) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Unauthenticated learner session.',
                ], 401);
            }

            $data = $service->getLearnerDetails($learnerId);

            return response()->json([
                'status' => true,
                'data'   => $data,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => $e->getMessage(),
            ], 200);
        }
    }

    /**
     * Self-service renew — same fields/engine as the staff
     * "library/learners/operation" (payment_type=RENEW) flow, but
     * learner_id/payment_type are forced from the auth token
     * (see LearnerRenewRequest) so a learner can only renew their own seat.
     */
    public function renew(LearnerRenewRequest $request, LearnerOperationService $service)
    {
        $dto = LearnerOperationDTO::fromRequest($request);

        return response()->json($service->process($dto));
    }

    /**
     * Mirrors DashboardController::learnerDashboard() (web) as JSON.
     */
    public function dashboard()
    {
        $learner = auth('learner_api')->user();

        if (!$learner) {
            return response()->json([
                'status'  => false,
                'message' => 'Unauthenticated learner session.',
            ], 401);
        }

        $activeDetail = LearnerDetail::withoutGlobalScopes()
            ->where('learner_id', $learner->id)
            ->leftJoin('plans', 'learner_detail.plan_id', '=', 'plans.id')
            ->leftJoin('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')
            ->select(
                'learner_detail.*',
                'plans.name as plan_name',
                'plan_types.name as plan_type_name',
                'plan_types.slot as shift_time'
            )
            ->orderBy('learner_detail.id', 'DESC')
            ->first();

        $branch = Branch::where('id', $learner->branch_id)
            ->select('id', 'name as library_name', 'features', 'library_address as address')
            ->first();

        // Pending Payment Status
        $txnStatus = learnerTransactionStatus($learner->id);
        $pendingAmount = (float) ($txnStatus['pending_amount'] ?? 0);
        $hasPending = $pendingAmount > 0;
        $dueDate = $txnStatus['due_date'] ?? '';

        $formattedNotice = $hasPending && !empty($dueDate)
            ? "Pending Payment {$pendingAmount} due on " . Carbon::parse($dueDate)->format('j M Y')
            : ($hasPending ? "Pending Payment {$pendingAmount}" : "No Pending Payment");

        // Days Remaining
        $expiryDate = $activeDetail?->plan_end_date ? Carbon::parse($activeDetail->plan_end_date) : null;
        $daysLeft = $expiryDate ? max(0, (int) now()->diffInDays($expiryDate, false)) : 0;

        // QR Code Payload for 3D ID Card
        $qrPayload = "LIBRARO:UID:{$learner->learner_no}|NAME:{$learner->name}|PLAN:" . ($activeDetail?->plan_name ?? 'N/A') . "|EXP:" . ($expiryDate ? $expiryDate->timestamp : 0);

        // Banners
        $banners = [
            [
                'id'          => 'ban_01',
                'title'       => optional($branch)->library_name ?? 'Smart Library System',
                'subtitle'    => 'Track your study sessions and seat status effortlessly',
                'imageUrl'    => asset('assets/img/banner1.jpg'),
                'actionUrl'   => null,
            ],
        ];

        // Name split
        $nameParts = explode(' ', trim($learner->name ?? ''));
        $firstName = $nameParts[0] ?? $learner->name;

        // Unread Notification Count
        $unreadCount = DB::table('notifications')
            ->where('notifiable_id', $learner->id)
            ->where('notifiable_type', get_class($learner))
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'status'  => true,
            'message' => 'Home dashboard data fetched successfully.',
            'data'    => [
                'student' => [
                    'id'                      => (string) $learner->id,
                    'uid'                     => $learner->learner_no ?? '',
                    'firstName'               => $firstName,
                    'fullName'                => strtoupper($learner->name ?? ''),
                    'email'                   => $learner->email ?? '',
                    'phone'                   => $learner->mobile ?? '',
                    'profileImageUrl'         => $learner->profile_image ?? null,
                    'status'                  => (int) $learner->status === 1 ? 'ACTIVE' : 'INACTIVE',
                    'unreadNotificationCount' => $unreadCount,
                    'library'                 => [
                        'id'      => (string) ($branch?->id ?? ''),
                        'name'    => $branch?->library_name ?? '',
                        'address' => $branch?->address ?? '',
                    ],
                ],
                'banners' => $banners,
                'idCard'  => [
                    'uid'            => $learner->learner_no ?? '',
                    'status'         => (int) $learner->status === 1 ? 'ACTIVE' : 'INACTIVE',
                    'planName'       => $activeDetail?->plan_name ?? 'No Active Plan',
                    'planType'       => $activeDetail?->plan_type_name ?? 'N/A',
                    'shiftTime'      => $activeDetail?->shift_time ?? 'N/A',
                    'planStartDate'  => $activeDetail?->plan_start_date ?? '',
                    'planExpiryDate' => $activeDetail?->plan_end_date ?? '',
                    'daysLeft'       => $daysLeft,
                    'pendingPayment' => [
                        'hasPending'      => $hasPending,
                        'amount'          => $pendingAmount,
                        'dueDate'         => $dueDate,
                        'formattedNotice' => $formattedNotice,
                    ],
                    'qrPayload'      => $qrPayload,
                ],
            ],
        ], 200);
    }

    /**
     * Self-service per-day punch log — reuses AttendanceService::attendanceLogs()
     * unchanged, forcing learner_id from the auth token.
     */
    public function attendanceLogs(Request $request, AttendanceService $service)
    {
        $request->merge([
            'learner_id' => auth('learner_api')->id(),
            'date' => $request->input('date', today()->toDateString()),
        ]);

        return $service->attendanceLogs($request);
    }

    /**
     * Learner self-service notifications list & total unread count.
     */
    public function notifications(Request $request)
    {
        $learner = auth('learner_api')->user();

        if (!$learner) {
            return response()->json([
                'status'  => false,
                'message' => 'Unauthenticated learner session.',
            ], 401);
        }

        $page  = max(1, (int) $request->input('page', 1));
        $limit = max(1, (int) $request->input('limit', 20));

        $query = DB::table('notifications')
            ->where('notifiable_id', $learner->id)
            ->where('notifiable_type', get_class($learner))
            ->orderBy('created_at', 'DESC');

        $totalUnread = DB::table('notifications')
            ->where('notifiable_id', $learner->id)
            ->where('notifiable_type', get_class($learner))
            ->whereNull('read_at')
            ->count();

        $notifications = $query->skip(($page - 1) * $limit)
            ->take($limit)
            ->get()
            ->map(function ($notif) {
                $data = is_string($notif->data) ? json_decode($notif->data, true) : (array) $notif->data;
                return [
                    'id'        => (string) $notif->id,
                    'title'     => $data['title'] ?? 'Notification',
                    'message'   => $data['message'] ?? ($data['body'] ?? ''),
                    'type'      => $data['type'] ?? 'GENERAL',
                    'isRead'    => !is_null($notif->read_at),
                    'createdAt' => Carbon::parse($notif->created_at)->toISOString(),
                ];
            });

        return response()->json([
            'status'  => true,
            'message' => 'Notifications retrieved successfully.',
            'data'    => [
                'totalUnread'   => $totalUnread,
                'notifications' => $notifications,
            ],
        ], 200);
    }
}
