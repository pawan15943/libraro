<?php

namespace App\Http\Controllers\Api\V1\Learner;

use App\DTO\LearnerOperationDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\LearnerRenewRequest;
use App\Models\Branch;
use App\Models\Feature;
use App\Models\Learner;
use App\Models\LearnerDetail;
use App\Models\LearnerTransaction;
use App\Services\AttendanceService;
use App\Services\FileUploadService;
use App\Services\LearnerOperationService;
use App\Services\LearnerService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

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
                'plan_types.start_time',
                'plan_types.end_time'
            )
            ->orderBy('learner_detail.id', 'DESC')
            ->first();

        $shiftTime = 'N/A';
        if ($activeDetail && !empty($activeDetail->start_time) && !empty($activeDetail->end_time)) {
            try {
                $start = Carbon::parse($activeDetail->start_time)->format('h:i A');
                $end = Carbon::parse($activeDetail->end_time)->format('h:i A');
                $shiftTime = "{$start} - {$end}";
            } catch (\Throwable $e) {
                $shiftTime = "{$activeDetail->start_time} - {$activeDetail->end_time}";
            }
        }

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

        // Name split
        $nameParts = explode(' ', trim($learner->name ?? ''));
        $firstName = $nameParts[0] ?? $learner->name;

        // Top Banners (Same as library top banner)
        $today = Carbon::today();
        $banners = [];

        // Festival wishes
        $festival = DB::table('india_festivals')
            ->whereDate('festival_date', $today->toDateString())
            ->select('festival_name', 'description')
            ->first();

        if ($festival) {
            $banners[] = [
                'id'          => 'ban_festival',
                'type'        => 'festival',
                'title'       => 'Wish you happy ' . $festival->festival_name,
                'subtitle'    => $festival->description ?? 'Have a wonderful and blessed day!',
                'imageUrl'    => asset('public/img/slider/topbanner.jpeg'),
                'actionUrl'   => null,
            ];
        }

        // Birthday wishes for the learner
        if (!empty($learner->dob)) {
            try {
                $dob = Carbon::parse($learner->dob);
                if ((int) $dob->month === (int) $today->month && (int) $dob->day === (int) $today->day) {
                    $banners[] = [
                        'id'          => 'ban_birthday',
                        'type'        => 'birthday',
                        'title'       => 'Happy Birthday, ' . $firstName . '! 🎉',
                        'subtitle'    => 'Wishing you great success in your studies and goals today!',
                        'imageUrl'    => asset('public/img/slider/topbanner.jpeg'),
                        'actionUrl'   => null,
                    ];
                }
            } catch (\Throwable $e) {
                // Ignore parse error
            }
        }

        // Main Library Top Banner
        $banners[] = [
            'id'          => 'ban_01',
            'type'        => 'image',
            'title'       => optional($branch)->library_name ?? 'Smart Library System',
            'subtitle'    => 'Track your study sessions and seat status effortlessly',
            'imageUrl'    => asset('public/img/slider/topbanner.jpeg'),
            'actionUrl'   => null,
        ];

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
                    'shiftTime'      => $shiftTime,
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
     * Learner self-service notifications list with tab filtering (all, active, expired),
     * unread indicators, and attachment support matching mobile UI.
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

        $today = Carbon::today()->toDateString();
        $tab   = strtolower((string) $request->input('tab', $request->input('status', 'all')));
        $page  = max(1, (int) $request->input('page', 1));
        $limit = max(1, (int) $request->input('limit', $request->input('per_page', 20)));

        $baseQuery = DB::table('notifications')
            ->where(function ($q) use ($learner) {
                $q->where(function ($sub) use ($learner) {
                    $sub->where('notifiable_id', $learner->id)
                        ->where('notifiable_type', get_class($learner));
                })->orWhere(function ($sub) use ($learner) {
                    $sub->where('notifiable_id', $learner->id)
                        ->where('guard', 'learner');
                });
            });

        // Tab counts
        $totalCount   = (clone $baseQuery)->count();
        $activeCount  = (clone $baseQuery)->where(function ($q) use ($today) {
            $q->whereNull('end_date')->orWhereDate('end_date', '>=', $today);
        })->count();
        $expiredCount = (clone $baseQuery)->whereNotNull('end_date')->whereDate('end_date', '<', $today)->count();
        $unreadCount  = (clone $baseQuery)->whereNull('read_at')->count();

        // Apply Tab Filter
        $query = clone $baseQuery;
        if ($tab === 'active') {
            $query->where(function ($q) use ($today) {
                $q->whereNull('end_date')->orWhereDate('end_date', '>=', $today);
            });
        } elseif ($tab === 'expired') {
            $query->whereNotNull('end_date')->whereDate('end_date', '<', $today);
        }

        $totalFiltered = $query->count();

        $notifications = $query->orderBy('created_at', 'DESC')
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get()
            ->map(function ($notif) use ($today) {
                $data = is_string($notif->data) ? json_decode($notif->data, true) : (array) $notif->data;
                $isExpired = !empty($notif->end_date) && Carbon::parse($notif->end_date)->startOfDay()->lt(Carbon::parse($today));
                $attachmentUrl = $data['link'] ?? ($data['image'] ?? ($data['attachment'] ?? null));

                return [
                    'id'                => (string) $notif->id,
                    'batch_id'          => $notif->batch_id ?? null,
                    'title'             => $data['title'] ?? 'Notification',
                    'description'       => $data['description'] ?? ($data['message'] ?? ($data['body'] ?? '')),
                    'message'           => $data['description'] ?? ($data['message'] ?? ($data['body'] ?? '')),
                    'notification_type' => $data['notification_type'] ?? ($data['type'] ?? 'general'),
                    'type'              => $data['notification_type'] ?? ($data['type'] ?? 'general'),
                    'is_read'           => !is_null($notif->read_at),
                    'isRead'            => !is_null($notif->read_at),
                    'read_at'           => $notif->read_at ? Carbon::parse($notif->read_at)->format('d-m-Y H:i:s') : null,
                    'status'            => $isExpired ? 'expired' : 'active',
                    'is_active'         => !$isExpired,
                    'start_date'        => $notif->start_date ?? null,
                    'end_date'          => $notif->end_date ?? null,
                    'date_time'         => Carbon::parse($notif->created_at)->format('d-m-Y H:i:s'),
                    'createdAt'         => Carbon::parse($notif->created_at)->toISOString(),
                    'attachment'        => [
                        'has_attachment' => !empty($attachmentUrl),
                        'url'            => $attachmentUrl,
                        'name'           => !empty($attachmentUrl) ? basename($attachmentUrl) : null,
                    ],
                    'link'              => $data['link'] ?? null,
                    'image'             => $data['image'] ?? null,
                ];
            });

        return response()->json([
            'status'  => true,
            'message' => 'Notifications retrieved successfully.',
            'data'    => [
                'current_tab'   => $tab,
                'totalUnread'   => $unreadCount,
                'unread_count'  => $unreadCount,
                'counts'        => [
                    'all'     => $totalCount,
                    'active'  => $activeCount,
                    'expired' => $expiredCount,
                    'unread'  => $unreadCount,
                ],
                'notifications' => $notifications,
                'pagination'    => [
                    'current_page' => $page,
                    'per_page'     => $limit,
                    'total'        => $totalFiltered,
                    'last_page'    => (int) ceil($totalFiltered / $limit),
                    'has_more'     => ($page * $limit) < $totalFiltered,
                ],
            ],
        ], 200);
    }

    /**
     * Mark single notification or all notifications as read for the authenticated learner.
     */
    public function markNotificationRead(Request $request)
    {
        $learner = auth('learner_api')->user();

        if (!$learner) {
            return response()->json([
                'status'  => false,
                'message' => 'Unauthenticated learner session.',
            ], 401);
        }

        $notificationId = $request->input('id', $request->input('notification_id'));

        $query = DB::table('notifications')
            ->where(function ($q) use ($learner) {
                $q->where(function ($sub) use ($learner) {
                    $sub->where('notifiable_id', $learner->id)
                        ->where('notifiable_type', get_class($learner));
                })->orWhere(function ($sub) use ($learner) {
                    $sub->where('notifiable_id', $learner->id)
                        ->where('guard', 'learner');
                });
            });

        if (!empty($notificationId) && $notificationId !== 'all') {
            $query->where('id', $notificationId);
        }

        $affected = $query->whereNull('read_at')->update([
            'read_at'    => now(),
            'updated_at' => now(),
        ]);

        $unreadCount = DB::table('notifications')
            ->where(function ($q) use ($learner) {
                $q->where(function ($sub) use ($learner) {
                    $sub->where('notifiable_id', $learner->id)
                        ->where('notifiable_type', get_class($learner));
                })->orWhere(function ($sub) use ($learner) {
                    $sub->where('notifiable_id', $learner->id)
                        ->where('guard', 'learner');
                });
            })
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'status'  => true,
            'message' => $affected > 0 ? 'Notification marked as read successfully.' : 'Notification already marked as read.',
            'data'    => [
                'unread_count' => $unreadCount,
            ],
        ], 200);
    }

    /**
     * Learner self-service profile update.
     * Supports updating personal info (name, email, mobile, dob, father_name, address)
     * and adding, updating, or removing profile picture.
     */
    public function updateProfile(Request $request, LearnerService $learnerService)
    {
        try {
            $learner = auth('learner_api')->user() ?? $request->user('learner_api');

            if (!$learner) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Unauthenticated learner session.',
                ], 401);
            }

            // Standardize field inputs
            $email = $request->has('email') ? trim((string) $request->input('email')) : null;
            $mobile = $request->has('mobile') ? trim((string) $request->input('mobile')) : ($request->has('phone') ? trim((string) $request->input('phone')) : null);
            $name = $request->has('name') ? trim((string) $request->input('name')) : null;
            $dob = $request->has('dob') ? trim((string) $request->input('dob')) : null;
            $fatherName = $request->has('father_name') ? trim((string) $request->input('father_name')) : null;
            $alternateMobile = $request->has('alternate_mobile') ? trim((string) $request->input('alternate_mobile')) : null;
            $address = $request->has('address') ? trim((string) $request->input('address')) : null;

            // Validator
            $validator = Validator::make($request->all(), [
                'name'                   => 'nullable|string|max:255',
                'email'                  => 'nullable|email|max:255',
                'mobile'                 => 'nullable|digits:10',
                'phone'                  => 'nullable|digits:10',
                'dob'                    => 'nullable|date',
                'father_name'            => 'nullable|string|max:255',
                'alternate_mobile'       => 'nullable|string|max:20',
                'address'                => 'nullable|string|max:500',
                'profile_picture'        => 'nullable',
                'profile_picture_image'  => 'nullable|image|mimes:jpeg,png,jpg,webp|max:3072',
                'image'                  => 'nullable',
                'avatar'                 => 'nullable',
                'remove_profile_picture' => 'nullable',
                'remove_image'           => 'nullable',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => false,
                    'message' => $validator->errors()->first(),
                ], 422);
            }

            // Email uniqueness check (excluding current learner)
            if (!empty($email)) {
                $encryptedEmail = encryptData($email);
                $emailExists = Learner::withoutGlobalScopes()
                    ->where('id', '!=', $learner->id)
                    ->where(function ($q) use ($email, $encryptedEmail) {
                        $q->where('email', $encryptedEmail)
                          ->orWhere('email', $email);
                    })
                    ->exists();

                if ($emailExists) {
                    return response()->json([
                        'status'  => false,
                        'message' => 'Email address is already in use by another learner.',
                    ], 200);
                }
                $learner->email = $encryptedEmail;
            }

            // Mobile uniqueness check (excluding current learner)
            if (!empty($mobile)) {
                $encryptedMobile = encryptData($mobile);
                $mobileExists = Learner::withoutGlobalScopes()
                    ->where('id', '!=', $learner->id)
                    ->where(function ($q) use ($mobile, $encryptedMobile) {
                        $q->where('mobile', $encryptedMobile)
                          ->orWhere('mobile', $mobile);
                    })
                    ->exists();

                if ($mobileExists) {
                    return response()->json([
                        'status'  => false,
                        'message' => 'Mobile number is already in use by another learner.',
                    ], 200);
                }
                $learner->mobile = $encryptedMobile;
            }

            if ($request->has('name') && !empty($name)) {
                $learner->name = $name;
            }

            if ($request->has('dob')) {
                if (!empty($dob)) {
                    $learner->dob = Carbon::parse($dob)->format('Y-m-d');
                } else {
                    $learner->dob = null;
                }
            }

            if ($request->has('father_name')) {
                $learner->father_name = $fatherName;
            }

            if ($request->has('alternate_mobile')) {
                $learner->alternate_mobile = $alternateMobile;
            }

            if ($request->has('address')) {
                $learner->address = $address;
            }

            // Profile Picture add, update, remove handling (matching LearnerOperationService)
            $removeRequested = $request->boolean('remove_profile_picture') 
                || $request->boolean('remove_image')
                || $request->input('action') === 'remove_image'
                || $request->input('profile_picture') === 'remove'
                || ($request->has('profile_picture') && (is_null($request->input('profile_picture')) || $request->input('profile_picture') === ''));

            $imageFile = $request->file('profile_picture_image') 
                ?? $request->file('profile_picture') 
                ?? $request->file('image') 
                ?? $request->file('avatar');

            $imageString = $request->input('profile_picture') 
                ?? $request->input('profile_picture_image') 
                ?? $request->input('image') 
                ?? $request->input('avatar') 
                ?? $request->input('temp_path');

            if ($imageFile) {
                // Delete existing old profile picture from disk
                if ($learner->profile_picture) {
                    $oldPath = public_path(str_replace('public/', '', $learner->profile_picture));
                    if (File::exists($oldPath)) {
                        @File::delete($oldPath);
                    }
                }
                $newPath = app(FileUploadService::class)->moveTempFileToPublic($imageFile, 'profile_picture', 'upload/profile_picture');
                if ($newPath) {
                    $learner->profile_picture = $newPath;
                }
            } elseif ($removeRequested) {
                // Delete existing profile picture from disk
                if ($learner->profile_picture) {
                    $oldPath = public_path(str_replace('public/', '', $learner->profile_picture));
                    if (File::exists($oldPath)) {
                        @File::delete($oldPath);
                    }
                }
                $learner->profile_picture = null;
            } elseif (!empty($imageString) && is_string($imageString) && $imageString !== 'remove') {
                // String path / temp image path / URL passed
                $newPath = app(FileUploadService::class)->moveTempFileToPublic($imageString, 'profile_picture', 'upload/profile_picture');
                if ($newPath) {
                    if ($learner->profile_picture && $learner->profile_picture !== $newPath) {
                        $oldPath = public_path(str_replace('public/', '', $learner->profile_picture));
                        if (File::exists($oldPath)) {
                            @File::delete($oldPath);
                        }
                    }
                    $learner->profile_picture = $newPath;
                }
            }

            $learner->save();

            // Reload fresh record
            $learner = $learner->fresh();

            $profilePictureUrl = $learner->profile_picture ? asset($learner->profile_picture) : null;
            $learnerDetails = null;

            try {
                $learnerDetails = $learnerService->getLearnerDetails($learner->id);
            } catch (\Throwable $th) {
                // Ignore if details not available
            }

            return response()->json([
                'status'  => true,
                'message' => 'Profile updated successfully.',
                'data'    => [
                    'student' => [
                        'id'                  => (string) $learner->id,
                        'uid'                 => $learner->learner_no ?? '',
                        'name'                => $learner->name,
                        'email'               => $learner->email ?? '',
                        'phone'               => $learner->mobile ?? '',
                        'dob'                 => $learner->dob ?? '',
                        'fatherName'          => $learner->father_name ?? '',
                        'alternateMobile'     => $learner->alternate_mobile ?? '',
                        'address'             => $learner->address ?? '',
                        'profile_picture'     => $learner->profile_picture ?? null,
                        'profile_picture_url' => $profilePictureUrl,
                        'profileImageUrl'     => $profilePictureUrl,
                    ],
                    'learner_details' => $learnerDetails,
                ],
            ], 200);

        } catch (\Throwable $e) {
            Log::error('Learner Profile Update Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status'  => false,
                'message' => 'Failed to update profile: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Learner self-service My Subscriptions list with tab filtering (all, active, upcoming, expired),
     * progress metrics, days used, and receipt downloads.
     */
    public function subscriptions(Request $request)
    {
        $learner = auth('learner_api')->user();

        if (!$learner) {
            return response()->json([
                'status'  => false,
                'message' => 'Unauthenticated learner session.',
            ], 401);
        }

        $today = Carbon::today();
        $tab   = strtolower((string) $request->input('tab', $request->input('status', 'all')));
        $page  = max(1, (int) $request->input('page', 1));
        $limit = max(1, (int) $request->input('limit', $request->input('per_page', 20)));

        $branch = Branch::find($learner->branch_id);

        $details = LearnerDetail::withoutGlobalScopes()
            ->where('learner_id', $learner->id)
            ->with(['plan', 'planType'])
            ->orderBy('id', 'desc')
            ->get();

        // Color palettes for subscription cards
        $colorPalette = ['#eab308', '#3b82f6', '#10b981', '#8b5cf6', '#ec4899', '#f97316'];

        $allCards = $details->map(function ($detail, $index) use ($today, $learner, $colorPalette) {
            $startDate = !empty($detail->plan_start_date) ? Carbon::parse($detail->plan_start_date)->startOfDay() : null;
            $endDate   = !empty($detail->plan_end_date) ? Carbon::parse($detail->plan_end_date)->startOfDay() : null;

            // Determine status
            if ($startDate && $startDate->isFuture()) {
                $status = 'upcoming';
                $statusLabel = 'Upcoming';
                $statusColor = '#0ea5e9'; // cyan / light blue
            } elseif ($endDate && $endDate->lt($today)) {
                $status = 'expired';
                $statusLabel = 'Expired';
                $statusColor = '#ef4444'; // red
            } else {
                $status = 'active';
                $statusLabel = 'Active';
                $statusColor = '#22c55e'; // green
            }

            // Progress & Days Used
            $totalDays = 0;
            $usedDays  = 0;
            $daysLeft  = 0;
            $progressPercentage = 0;
            $progressLabel = 'Not start yet';

            if ($startDate && $endDate) {
                $totalDays = max(1, $startDate->diffInDays($endDate) + 1);
                if ($status === 'upcoming') {
                    $usedDays = 0;
                    $progressPercentage = 0;
                    $progressLabel = 'Not start yet';
                } elseif ($status === 'expired') {
                    $usedDays = $totalDays;
                    $progressPercentage = 100;
                    $progressLabel = "Expired ($totalDays of $totalDays days used)";
                } else {
                    $usedDays = min($totalDays, max(0, $startDate->diffInDays($today) + 1));
                    $daysLeft = max(0, $today->diffInDays($endDate, false));
                    $progressPercentage = (int) min(100, round(($usedDays / $totalDays) * 100));
                    $progressLabel = "{$usedDays} of {$totalDays} days used";
                }
            }

            // Transaction associated
            $txn = LearnerTransaction::withoutGlobalScopes()
                ->where('learner_detail_id', $detail->id)
                ->latest('id')
                ->first();

            $amtPaid = (float) ($txn->paid_amount ?? ($detail->plan_price_id ?? 0));
            $downloadReceiptUrl = '';
            if ($txn && (int) ($txn->is_paid ?? 0) === 1) {
                try {
                    $downloadReceiptUrl = app(\App\Services\ReceiptService::class)->receiptOpenLink((int) $txn->id);
                } catch (\Throwable $e) {}
            }

            $planColor = $colorPalette[$index % count($colorPalette)];

            return [
                'id'                   => (int) $detail->id,
                'plan_id'              => $detail->plan_id,
                'plan_type_id'         => $detail->plan_type_id,
                'plan_name'            => $detail->plan?->name ?? 'Membership Plan',
                'plan_type'            => $detail->planType?->name ?? ($detail->plan?->monthdays ?? 'Monthly'),
                'plan_color'           => $planColor,
                'status'               => $status,
                'status_label'         => $statusLabel,
                'status_color'         => $statusColor,
                'start_date'           => $detail->plan_start_date ?? '',
                'end_date'             => $detail->plan_end_date ?? '',
                'formatted_start_date' => $startDate ? $startDate->format('d M Y') : '',
                'formatted_end_date'   => $endDate ? $endDate->format('d M Y') : '',
                'amount_paid'          => $amtPaid,
                'formatted_amount_paid'=> '₹' . number_format($amtPaid, 0),
                'total_days'           => $totalDays,
                'used_days'            => $usedDays,
                'days_left'            => $daysLeft,
                'progress_percentage'  => $progressPercentage,
                'progress_label'       => $progressLabel,
                'can_renew'            => in_array($status, ['active', 'expired']),
                'download_receipt_url' => $downloadReceiptUrl,
                'seat_no'              => $learner->seat_no ? (string) getSeatDisplayShortFloorName($learner->seat_no) : 'GEN',
            ];
        });

        // Counts
        $counts = [
            'all'      => $allCards->count(),
            'active'   => $allCards->where('status', 'active')->count(),
            'upcoming' => $allCards->where('status', 'upcoming')->count(),
            'expired'  => $allCards->where('status', 'expired')->count(),
        ];

        // Filter by tab
        if ($tab === 'active') {
            $filtered = $allCards->where('status', 'active')->values();
        } elseif ($tab === 'upcoming') {
            $filtered = $allCards->where('status', 'upcoming')->values();
        } elseif ($tab === 'expired') {
            $filtered = $allCards->where('status', 'expired')->values();
        } else {
            $filtered = $allCards->values();
        }

        $paginated = $filtered->slice(($page - 1) * $limit, $limit)->values();

        return response()->json([
            'status'  => true,
            'message' => 'Subscriptions fetched successfully.',
            'data'    => [
                'current_tab'   => $tab,
                'counts'        => $counts,
                'subscriptions' => $paginated,
                'subscribe_cta' => [
                    'branch_uuid' => $branch?->uuid ?? '',
                    'button_text' => 'Subscribe',
                ],
                'pagination'    => [
                    'current_page' => $page,
                    'per_page'     => $limit,
                    'total'        => $filtered->count(),
                    'last_page'    => (int) ceil($filtered->count() / $limit),
                    'has_more'     => ($page * $limit) < $filtered->count(),
                ],
            ],
        ], 200);
    }

    /**
     * Learner self-service My Transactions list matching the mobile payment transaction history screen.
     */
    public function transactions(Request $request)
    {
        $learner = auth('learner_api')->user();

        if (!$learner) {
            return response()->json([
                'status'  => false,
                'message' => 'Unauthenticated learner session.',
            ], 401);
        }

        $page  = max(1, (int) $request->input('page', 1));
        $limit = max(1, (int) $request->input('limit', $request->input('per_page', 20)));

        $txns = LearnerTransaction::withoutGlobalScopes()
            ->where('learner_id', $learner->id)
            ->with(['learnerDetail.plan', 'learnerDetail.planType'])
            ->orderBy('id', 'desc')
            ->get();

        $receiptService = app(\App\Services\ReceiptService::class);

        $items = $txns->map(function ($tx) use ($receiptService) {
            $isPaid = (int) ($tx->is_paid ?? 1) === 1 && (float) ($tx->paid_amount ?? 0) > 0;
            $hasPending = (float) ($tx->pending_amount ?? 0) > 0;

            if ($isPaid) {
                $status = 'SUCCESS';
                $statusColor = '#22c55e'; // green
            } elseif ($hasPending) {
                $status = 'PENDING';
                $statusColor = '#f59e0b'; // orange
            } else {
                $status = 'FAILED';
                $statusColor = '#ef4444'; // red
            }

            $paidDate = !empty($tx->paid_date) ? Carbon::parse($tx->paid_date)->format('d/m/Y') : ($tx->created_at ? Carbon::parse($tx->created_at)->format('d/m/Y') : '');
            $amountPaid = (float) ($tx->paid_amount ?? 0);

            $paymentModeCode = (int) ($tx->payment_mode ?? 0);
            if ($paymentModeCode === 1) {
                $paymentMode = 'Online';
            } elseif ($paymentModeCode === 2) {
                $paymentMode = 'Offline';
            } elseif ($paymentModeCode === 3) {
                $paymentMode = 'Paylater';
            } else {
                $paymentMode = !empty($tx->payment_mode) ? (string) $tx->payment_mode : 'Online';
            }

            $trxnDisplayId = !empty($tx->transaction_id) ? (string) $tx->transaction_id : (string) $tx->id;

            $downloadReceiptUrl = '';
            if ($isPaid) {
                try {
                    $downloadReceiptUrl = $receiptService->receiptOpenLink((int) $tx->id);
                } catch (\Throwable $e) {}
            }

            return [
                'id'                    => (int) $tx->id,
                'trxn_id'               => $trxnDisplayId,
                'transaction_id'        => $trxnDisplayId,
                'amt_paid'              => $amountPaid,
                'amount_paid'           => $amountPaid,
                'formatted_amount_paid' => '₹' . number_format($amountPaid, 0),
                'payment_mode'          => $paymentMode,
                'trxn_date'             => $paidDate,
                'transaction_date'      => $paidDate,
                'status'                => $status,
                'status_color'          => $statusColor,
                'plan_name'             => $tx->learnerDetail?->plan?->name ?? 'Membership Plan',
                'plan_type'             => $tx->learnerDetail?->planType?->name ?? '',
                'total_amount'          => (float) ($tx->total_amount ?? $amountPaid),
                'pending_amount'        => (float) ($tx->pending_amount ?? 0),
                'download_receipt_url'  => $downloadReceiptUrl,
                'created_at'            => $tx->created_at ? Carbon::parse($tx->created_at)->toISOString() : null,
            ];
        });

        $paginated = $items->slice(($page - 1) * $limit, $limit)->values();

        return response()->json([
            'status'  => true,
            'message' => 'Transactions fetched successfully.',
            'data'    => [
                'transactions' => $paginated,
                'pagination'   => [
                    'current_page' => $page,
                    'per_page'     => $limit,
                    'total'        => $items->count(),
                    'last_page'    => (int) ceil($items->count() / $limit),
                    'has_more'     => ($page * $limit) < $items->count(),
                ],
            ],
        ], 200);
    }
}
