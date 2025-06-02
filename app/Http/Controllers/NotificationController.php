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
    public function create(){
        $notifications =DB::table('notifications')
        ->select(
            'batch_id',
            'guard',
            'data',
            DB::raw('MIN(start_date) as start_date'),
            DB::raw('MAX(end_date) as end_date'),
            'created_at'
        )
        ->groupBy('batch_id', 'guard', 'data', 'created_at')
        ->get();
        $notificat=null;
        return view('notification.custom_notification',compact('notifications','notificat'));
    }
    public function edit($id){
        
        $notificat=DB::table('notifications')->where('batch_id',$id)->first();
       
        $notifications =DB::table('notifications')
        ->select(
            'batch_id',
            'guard',
            'data',
            DB::raw('MIN(start_date) as start_date'),
            DB::raw('MAX(end_date) as end_date'),
            'created_at'
        )
        ->groupBy('batch_id', 'guard', 'data', 'created_at')
        ->get();
        return view('notification.custom_notification',compact('notifications','notificat'));
    }
    

    public function send(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'link' => 'nullable|url',
            'image' => 'nullable|url',
            'guard' => 'required|string|in:web,library,learner',
            'start_date' => 'required',
            'end_date' => 'required',
        ]);
        // Generate a unique batch_id for this notification
        $batchId = random_int(100000, 999999); // Generate a 6-digit random integer

        $data = [
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
                'batch_id' => $batchId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return redirect()->back()->with('success', 'Notification sent successfully!');
    }

    public function update(Request $request)
    {
        $request->validate([
            'batch_id' => 'required',
            'title' => 'required',
            'description' => 'required',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);

        // Update all notifications in the batch
        DB::table('notifications')
            ->where('batch_id', $request->batch_id)
            ->update([
                'data' => json_encode([
                    'title' => $request->title,
                    'description' => $request->description,
                ]),
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ]);

        return redirect()->back()->with('success', 'Notification updated successfully!');
    }

    public function show(Request $request){
        
        $guard = Auth::guard()->getName(); 
        $parts = explode('_', $guard);
        $guard_name = $parts[1]; 
      
        $notifications = DB::table('notifications')
        ->select(
            'batch_id',
            'guard',
            'data',
            DB::raw('MIN(start_date) as start_date'),
            DB::raw('MAX(end_date) as end_date'),
            'created_at'
        )
        ->where('notifiable_id', Auth::user()->id)
        ->where('guard', $guard_name)
        ->groupBy('batch_id', 'guard', 'data', 'created_at') // Add non-aggregated columns here
        ->get();
    
        return view('notification.show',compact('notifications'));
    }

    public function markAsRead(Request $request)
    {
        $notificationId = $request->notification_id;

        // Find the notification for the authenticated user
        $notification = Auth::user()->notifications()->find($notificationId);

        if ($notification && !$notification->read_at) {
            $notification->markAsRead(); // Use Laravel's built-in method to update the `read_at` column
        }

        return response()->json(['success' => true]);
    }

}
