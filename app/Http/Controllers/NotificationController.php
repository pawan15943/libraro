<?php

namespace App\Http\Controllers;

use App\Models\Learner;
use App\Models\Library;
use App\Models\User;
use App\Notifications\CustomNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Auth ;
class NotificationController extends Controller
{
    public function index()
    {
        $notifications = DB::table('notifications')
            ->select(
                'batch_id',
                'guard',
                'data',
                DB::raw('MIN(status) as status'),
                DB::raw('MIN(start_date) as start_date'),
                DB::raw('MAX(end_date) as end_date'),
                DB::raw('COUNT(*) as total_recipients'),
                DB::raw('COUNT(read_at) as read_count'),
                DB::raw('MIN(created_at) as created_at')
            )
            ->groupBy('batch_id', 'guard', 'data')
            ->orderByDesc('created_at')
            ->get();

        return view('notification.index', compact('notifications'));
    }

    public function create()
    {
        $notificat = null;
        return view('notification.form', compact('notificat'));
    }

    public function edit($id)
    {
        $notificat = DB::table('notifications')->where('batch_id', $id)->first();
        if (!$notificat) {
            return redirect()->route('admin.notifications.index')->with('error', 'Notification not found.');
        }

        return view('notification.form', compact('notificat'));
    }

    public function send(Request $request)
    {
        $request->validate([
            'notification_type' => 'required|string|in:important,wishes,maintenance,offers',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'link' => 'nullable|url',
            'image' => 'nullable|url',
            'guard' => 'required|string|in:web,library,learner',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'status' => 'required|in:0,1',
        ]);

        // Generate a unique batch_id for this notification
        $batchId = random_int(100000, 999999);

        $data = [
            'notification_type' => $request->notification_type,
            'title' => $request->title,
            'description' => $request->description,
            'link' => $request->link,
            'image' => $request->image,
            'guard' => $request->guard,
        ];

        // Determine the guard and notify users
        $users = match ($request->guard) {
            'web' => User::all(),
            'library' => Library::all(),
            'learner' => Learner::all(),
        };

        // Manually insert each notification for users
        foreach ($users as $user) {
            DB::table('notifications')->insert([
                'id' => Str::uuid()->toString(),
                'type' => CustomNotification::class,
                'notifiable_type' => get_class($user),
                'notifiable_id' => $user->id,
                'data' => json_encode($data),
                'guard' => $request->guard,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'status' => (int)$request->status,
                'batch_id' => $batchId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return redirect()->route('admin.notifications.index')->with('success', 'Notification sent successfully!');
    }

    public function update(Request $request)
    {
        $request->validate([
            'batch_id' => 'required',
            'notification_type' => 'required|string|in:important,wishes,maintenance,offers',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'link' => 'nullable|url',
            'image' => 'nullable|url',
            'guard' => 'required|string|in:web,library,learner',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'status' => 'required|in:0,1',
        ]);

        $startDate = $request->start_date;
        $endDate = $request->end_date;

        // If activating an expired notification without extending dates, ensure it runs from today for 7 days
        if ((int)$request->status === 1 && $endDate < now()->toDateString()) {
            $startDate = now()->toDateString();
            $endDate = now()->addDays(7)->toDateString();
        }

        // Update all notifications in the batch
        DB::table('notifications')
            ->where('batch_id', $request->batch_id)
            ->update([
                'data' => json_encode([
                    'notification_type' => $request->notification_type,
                    'title' => $request->title,
                    'description' => $request->description,
                    'link' => $request->link,
                    'image' => $request->image,
                    'guard' => $request->guard,
                ]),
                'guard' => $request->guard,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => (int)$request->status,
                'updated_at' => now(),
            ]);

        return redirect()->route('admin.notifications.index')->with('success', 'Notification updated successfully!');
    }

    public function destroy($batchId)
    {
        DB::table('notifications')->where('batch_id', $batchId)->delete();
        return redirect()->route('admin.notifications.index')->with('success', 'Notification deleted successfully!');
    }

    public function show(Request $request)
    {
        $user = Auth::guard('library')->user() ?? Auth::guard('web')->user() ?? Auth::user() ?? getAuthenticatedUser();
        if (!$user) {
            return redirect()->route('login');
        }

        $filter = $request->get('filter', 'all');
        $libraryId = function_exists('getLibraryId') ? getLibraryId() : null;

        $query = DB::table('notifications')
            ->where(function ($q) {
                $q->where('status', 1)->orWhereNull('status');
            })
            ->where(function ($q) use ($user, $libraryId) {
                $q->where('notifiable_id', $user->id);
                if ($libraryId) {
                    $q->orWhere('notifiable_id', $libraryId);
                }
            });

        $totalCount = (clone $query)->count();
        $unreadCount = (clone $query)->whereNull('read_at')->count();
        $readCount = (clone $query)->whereNotNull('read_at')->count();

        if ($filter === 'unread') {
            $query->whereNull('read_at');
        } elseif ($filter === 'read') {
            $query->whereNotNull('read_at');
        }

        $notifications = $query->orderBy('created_at', 'desc')->get();

        return view('notification.show', compact('notifications', 'filter', 'totalCount', 'unreadCount', 'readCount'));
    }

    public function markAsRead(Request $request)
    {
        $notificationId = $request->notification_id;
        $user = Auth::user() ?? getAuthenticatedUser();

        if ($user && $notificationId) {
            DB::table('notifications')
                ->where('id', $notificationId)
                ->where('notifiable_id', $user->id)
                ->update(['read_at' => now(), 'updated_at' => now()]);
        }

        return response()->json(['success' => true]);
    }

    public function markAsUnread(Request $request)
    {
        $notificationId = $request->notification_id;
        $user = Auth::user() ?? getAuthenticatedUser();

        if ($user && $notificationId) {
            DB::table('notifications')
                ->where('id', $notificationId)
                ->where('notifiable_id', $user->id)
                ->update(['read_at' => null, 'updated_at' => now()]);
        }

        return response()->json(['success' => true]);
    }

    public function markAllAsRead(Request $request)
    {
        $user = Auth::user() ?? getAuthenticatedUser();

        if ($user) {
            DB::table('notifications')
                ->where('notifiable_id', $user->id)
                ->whereNull('read_at')
                ->update(['read_at' => now(), 'updated_at' => now()]);
        }

        return redirect()->back()->with('success', 'All notifications marked as read!');
    }

}
