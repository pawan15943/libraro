<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\NotificationChannelSetting;
use App\Models\NotificationTemplate;
use App\Jobs\SendNotificationJob;
use App\Models\Branch;
use App\Models\Learner;
use App\Models\NotificationLog;
use App\Models\NotificationSubscription;
use Illuminate\Support\Str;
use DB;
use Auth;
use Illuminate\Support\Facades\Http;
use Razorpay\Api\Api;
use Twilio\Rest\Client;

class NotificationSentController extends Controller
{
    public function index()
    {

        $planswaba = DB::table('notification_subscription_plans')
            ->orderBy('channel')
            ->orderBy('amount')
            ->get()
            ->groupBy('channel');

        // existing subscription for this library (optional)
        $libraryId = getLibraryId() ?? null;
        $subscription = null;
        if ($libraryId) {
            $subscription = NotificationSubscription::where('library_id', $libraryId)->first();
        }

        return view('notification.subscription', compact('planswaba', 'subscription'));
    }

    /**
     * Handle purchase request (simulate payment)
     */
    public function purchase(Request $request)
    {
        $libraryId = getLibraryId();

        // ---------------------------
        // FETCH CHANNELS DYNAMICALLY
        // ---------------------------
        $channels = DB::table('notification_subscription_plans')
            ->select('channel')
            ->distinct()
            ->pluck('channel')
            ->toArray();

        // ---------------------------
        // VALIDATION
        // ---------------------------
        $rules = [];
        foreach ($channels as $ch) {
            $rules["{$ch}_plan_id"] = "nullable|integer|exists:notification_subscription_plans,id";
        }

        $request->validate($rules);

        // ---------------------------
        // FETCH SELECTED PLANS
        // ---------------------------
        $selected = [];

        foreach ($channels as $ch) {

            $planId = $request->get("{$ch}_plan_id");

            if ($planId) {
                $plan = DB::table('notification_subscription_plans')->where('id', $planId)->first();

                if ($plan) {
                    $selected[$ch] = [
                        'id'     => $plan->id,
                        'amount' => $plan->amount,
                        'type'   => $plan->type,
                        // 'count'   => $plan->message_count,
                    ];
                }
            }
        }


        if (empty($selected)) {
            return back()->with('error', 'Please select at least one plan.');
        }

        $totalAmount = array_sum(array_column($selected, 'amount'));



        $amountInPaise = intval($totalAmount * 100);

        $api = new Api(env('RAZORPAY_KEY'), env('RAZORPAY_SECRET'));

        $razorOrder = $api->order->create([
            'receipt'  => 'LIB_NOTIFY_' . time(),
            'amount'   => $amountInPaise,
            'currency' => 'INR'
        ]);
        $channels = ['waba', 'text', 'email'];

        $formattedPlans = [];

        foreach ($channels as $ch) {
            $formattedPlans[$ch] = $selected[$ch]['id'] ?? null;
        }

        // Save order in DB
        $orderId = DB::table('notification_orders')->insertGetId([
            'library_id'     => $libraryId,
            'razorpay_order_id' => $razorOrder['id'],
            'selected_plans' => json_encode($formattedPlans),
            'amount'         => $totalAmount,
            'status'         => 'pending',
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        return view('notification.razorpay_checkout', [
            'key'      => env('RAZORPAY_KEY'),
            'amount'   => $totalAmount,
            'orderId'  => $razorOrder['id'],
            'dbOrderId' => $orderId
        ]);
    }
    public function verifyPayment(Request $request)
    {
        \Log::channel('razorpay')->info('Verify Callback Received', $request->all());
        $api = new Api(env('RAZORPAY_KEY'), env('RAZORPAY_SECRET'));

        try {
            $attributes = [
                'razorpay_order_id'   => (string) $request->razorpay_order_id,
                'razorpay_payment_id' => (string) $request->razorpay_payment_id,
                'razorpay_signature'  => (string) $request->razorpay_signature,
            ];

            $api->utility->verifyPaymentSignature($attributes);
            \Log::channel('razorpay')->info("Signature Verified", $attributes);
            // Update DB order
            DB::table('notification_orders')
                ->where('id', $request->db_order_id)
                ->update([
                    'status' => 'paid',
                    'razorpay_payment_id' => $request->razorpay_payment_id,
                    'razorpay_signature'  => $request->razorpay_signature,
                    'updated_at' => now()
                ]);

            // Now activate credits
            $this->activateSubscription($request->db_order_id);

            return redirect()
                ->route('notifications.settings')
                ->with('success', 'Payment Successful — Configure your channels now');
        } catch (\Exception $e) {

            DB::table('notification_orders')
                ->where('id', $request->db_order_id)
                ->update(['status' => 'failed']);

            \Log::channel('razorpay')->error("Signature Verify Failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'callback' => $request->all()
            ]);

            return redirect()
                ->route('notifications.subscription')
                ->with('error', 'Payment Verification Failed');
        }
    }
    public function activateSubscription($orderId)
    {
        $order = DB::table('notification_orders')->where('id', $orderId)->first();

        $selected = json_decode($order->selected_plans, true);
        $libraryId = $order->library_id;

        NotificationSubscription::create([
            'library_id'     => $libraryId,
            'waba'           => $selected['waba'] ?? null,
            'text'           => $selected['text'] ?? null,
            'email'          => $selected['email'] ?? null,
            'total_paid_amount' => $order->amount,
            'order_id'       => $order->razorpay_order_id,
        ]);

        foreach ($selected as $ch => $planId) {

            if (!$planId) continue;

            $plan = DB::table('notification_subscription_plans')
                ->where('id', $planId)
                ->first();

            DB::table('notification_credits_usage')->insert([
                'library_id' => $libraryId,
                'channel'    => $ch,
                'date'       => date('Y-m-d'),
                'used'       => 0,
                'remaining'  => $plan->message_count,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }



    public function settingsForm(Request $request)
    {
        if (!notificationActive()) {
            return redirect()->route('notifications.subscription');
        }
        $libraryId = getLibraryId();

        // 1. All branches of the library
        $branches = Branch::where('library_id', $libraryId)
            ->select('id', 'name')
            ->get();

        // 2. Load operations + their templates (same as your working code)
        $operations = DB::table('operations as o')
            ->leftJoin('notification_templates as nt', 'o.id', '=', 'nt.operation_id')
            ->select(
                'o.id as operation_id',
                'o.name as operation_name',
                'nt.id as template_id',
                'nt.type',
                'nt.template_message',
                'nt.template_name',
                'nt.template_code'
            )
            ->orderBy('o.id')
            ->where('nt.is_custom', 0)
            ->get()

            ->groupBy('operation_name');

        // 3. EDIT MODE → Load existing settings (first branch or requested branch)
        // If user selects branch first, use request branch, else take first branch for auto edit load.

        $selectedBranchId = $request->branch_id ?? getCurrentBranch();
        $existing = DB::table('notification_channel_settings')
            ->where('branch_id', $selectedBranchId)
            ->first();



        // If no existing settings → empty arrays (ADD mode)
        $oldText  = $existing ? json_decode($existing->text_template_id, true)  : [];
        $oldWaba  = $existing ? json_decode($existing->waba_template_id, true)  : [];
        $oldEmail = $existing ? json_decode($existing->email_template_id, true) : [];
        $oldMessageTime = json_decode($existing->message_time ?? '[]', true);


        return view(
            'notification.settings',
            compact('branches', 'operations', 'oldText', 'oldWaba', 'oldEmail', 'selectedBranchId', 'oldMessageTime')
        );
    }

    public function settingStore(Request $request)
    {
        $validated = $request->validate([
            'branch_ids' => 'required|integer|exists:branches,id',

            'settings' => 'required|array',

            'settings.*.text_template_id' => 'nullable|integer|exists:notification_templates,id',
            'settings.*.waba_template_id' => 'nullable|integer|exists:notification_templates,id',
        ], [], [
            'settings.*.text_template_id' => 'SMS template',
            'settings.*.waba_template_id' => 'WABA template',
        ]);


        $branchId = $request->branch_ids;
        $settings  = $request->settings;
        $messageTime = $request->message_time ?? [];
        $messageTimeJson = empty($messageTime) ? null : json_encode($messageTime);



        $textArr = [];
        $wabaArr = [];
        $emailArr = [];

        foreach ($settings as $opId => $row) {

            if (!empty($row['text_template_id'])) {
                $textArr[] = (int)$row['text_template_id'];
            }

            if (!empty($row['waba_template_id'])) {
                $wabaArr[] = (int)$row['waba_template_id'];
            }

            if (!empty($row['email_template_id'] ?? null)) {
                $emailArr[] = (int)$row['email_template_id'];
            }
        }

        DB::table('notification_channel_settings')
            ->updateOrInsert(
                ['branch_id' => $branchId],
                [
                    'text_template_id'  => empty($textArr)  ? null : json_encode($textArr),
                    'waba_template_id'  => empty($wabaArr)  ? null : json_encode($wabaArr),
                    'email_template_id' => empty($emailArr) ? null : json_encode($emailArr),
                    'message_time'      => $messageTimeJson,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );


        return redirect()
            ->route('notification.dashboard')
            ->with('success', 'Settings saved successfully.');
    }



    public function dashboard()
    {
        if (!notificationActive()) {
            return redirect()->route('notifications.subscription');
        } else {
            $libraryId = getLibraryId();
            $branchId  = getCurrentBranch();

            // Fetch Logs With Operation Name
            $logs = DB::table('notification_logs as nl')
                ->leftJoin('operations as o', 'o.id', '=', 'nl.operation_id')
                ->where('nl.library_id', $libraryId)
                ->where('nl.branch_id', $branchId)
                ->where('nl.channel', 'waba')
                ->orderBy('nl.created_at', 'desc')
                ->select(
                    'nl.unique_id',
                    'nl.message_status',
                    'nl.message_content',
                    'nl.created_at',
                    'nl.delivery_status',
                    'nl.seat_no',
                    'o.name as operation_name'
                )
                ->get()
                ->map(function ($row) {

                    // Handle JSON delivery status
                    $delivery = json_decode($row->delivery_status, true);
                    $row->delivery_label = isset($delivery['status'])
                        ? ucfirst($delivery['status'])
                        : '-';

                    return $row;
                });


            // Fetch SMS logs
            $textlogs = DB::table('notification_logs as nl')
                ->leftJoin('operations as o', 'o.id', '=', 'nl.operation_id')
                ->where('nl.library_id', $libraryId)
                ->where('nl.branch_id', $branchId)
                ->where('nl.channel', 'text') // Only SMS
                ->orderBy('nl.created_at', 'desc')
                ->select(
                    'nl.unique_id',
                    'nl.message_status',
                    'nl.message_content',
                    'nl.created_at',
                    'nl.delivery_status',
                    'nl.seat_no',
                    'o.name as operation_name'
                )
                ->get()
                ->map(function ($row) {

                    // Delivery JSON conversion
                    $delivery = json_decode($row->delivery_status, true);

                    $row->delivery_label = isset($delivery['status'])
                        ? ucfirst($delivery['status'])
                        : '-';

                    return $row;
                });
            $wabaRemaining = DB::table('notification_credits_usage')
                ->where('library_id', getLibraryId())
                ->where('channel', 'waba')
                ->sum('remaining');

            $wabaUsed = DB::table('notification_credits_usage')
                ->where('library_id', getLibraryId())
                ->where('channel', 'waba')
                ->sum('used');
            $textRemaining = DB::table('notification_credits_usage')
                ->where('library_id', getLibraryId())
                ->where('channel', 'text')
                ->sum('remaining');

            $textUsed = DB::table('notification_credits_usage')
                ->where('library_id', getLibraryId())
                ->where('channel', 'text')
                ->sum('used');
            return view('notification.dashboard', compact('logs', 'textlogs', 'wabaRemaining', 'wabaUsed', 'textRemaining', 'textUsed'));
        }
    }
    public function renderMessage(Request $request)
    {
        $request->validate([
            'template_id' => 'required|integer|exists:notification_templates,id',
            'learner_id'  => 'required|integer|exists:learners,id'
        ]);

        // 1. Fetch Template
        $template = NotificationTemplate::find($request->template_id);

        // 2. Fetch learner
        $student = Learner::where('id', $request->learner_id)->first();

        if (!$student) {
            return response()->json(['message' => 'Learner not found'], 404);
        }

        // 3. Prepare dynamic values
        $replace = [
            '{{learner_name}}' => $student->name,
            '{{seat_no}}'     => $student->seat_no,
            '{{library_name}}' => getLibrary()->library_name,
            '{{mobile}}'      => $student->mobile,
        ];

        // 4. Replace dynamic values
        $finalMessage = str_replace(array_keys($replace), array_values($replace), $template->template_message);

        // 5. Send back
        return response()->json([
            'message' => $finalMessage
        ]);
    }
    public function getLearnerMobiles(Request $request)
    {
        $learner = Learner::select('mobile', 'alternate_mobile')->where('id', $request->learner_id)->first();

        $mobiles = [];

        if ($learner->mobile) {
            $mobiles[] = $learner->mobile;
        }

        if ($learner->alternate_mobile) {
            $mobiles[] = $learner->alternate_mobile;
        }

        return response()->json([
            'mobiles' => $mobiles
        ]);
    }

    public function autoMessage($learner_id, $type, $template_code)
    {

        \LOG::info('aumessage start', ['learner_id' => $learner_id, 'type' => $type, 'template_code' => $template_code]);
        // 1. Template
        $template = NotificationTemplate::where('type', $type)
            ->where('template_code', $template_code)
            ->select('id as template_id', 'template_message as message')
            ->first();

        if (!$template) {
            return;
        }

        // 2. Learner
        $learner = Learner::withTrashed()->where('id', $learner_id)
            ->select('sended_message_type', 'mobile', 'name')
            ->first();

        if (!$learner || empty($learner->mobile)) {
            return;
        }

        // 3. Prepare dynamic values
        $replace = [
            '{{learner_name}}' => $learner->name,
            '{{seat_no}}'     => $learner->seat_no,
            '{{mobile}}'      => $learner->mobile,
            '{{library_name}}' => getLibrary()->library_name
        ];
        \LOG::info('automessage', ['template' => $template, 'learner' => $learner]);
        // 4. Replace dynamic values
        $finalMessage = str_replace(array_keys($replace), array_values($replace), $template->message);

        // 3. Payload
        $data = [
            'learner_id'  => $learner_id,
            'template_id' => $template->template_id,
            'message'     => $finalMessage,
            'mobileNo'    => $learner->mobile
        ];
        // 4. Channel settings
        $wabaSetting = NotificationChannelSetting::where('branch_id', getCurrentBranch())
            ->whereJsonContains('waba_template_id', $template->template_id)
            ->exists();

        $textSetting = NotificationChannelSetting::where('branch_id', getCurrentBranch())
            ->whereJsonContains('text_template_id', $template->template_id)
            ->exists();
        \LOG::info('automessage', ['data' => $data, 'type' => $type, 'wabasetting' => $wabaSetting, 'ismobile' => in_array($learner->sended_message_type, ['whatsapp', 'both'])]);

        // 5. Send WABA
        if (
            $type == 'waba' &&
            $wabaSetting &&
            in_array($learner->sended_message_type, ['whatsapp', 'both'])
        ) {
            \Log::info('autoMessage sendmessage call');
            $this->sendMessage(new \Illuminate\Http\Request($data));
            return;
        }

        // 6. Send SMS
        if (
            $type == 'text' &&
            $textSetting &&
            in_array($learner->sended_message_type, ['text', 'both'])
        ) {
            $this->sendMessage(new \Illuminate\Http\Request($data));
            return;
        }
    }

    public function sendMessage(Request $request)
    {

        $request->validate([
            'learner_id'  => 'required|integer',
            'template_id' => 'required|integer',
            'message'     => 'required|string',
            'mobileNo' => 'required'
        ]);

        $student = Learner::withTrashed()->findOrFail($request->learner_id);

        $message = $request->message;

        // Detect channel based on template type
        $template = NotificationTemplate::find($request->template_id);

        $channel = $template->type; // text / waba / email

        \Log::info('sendMessage', ['learner' => $request->learner_id, 'channel' => $channel, 'template_name' => $request->template_id]);


        // 2. Send Message via APIs

        $apiResponse = null;
        $status = 'queued';
        $deliveryStatus = null;
        $error = null;
        $cost = 1.00; // optional (update based on your pricing)

        try {
            if ($channel === 'waba') {


                // $apiResponse = $this->sendWaba($student->mobile, $message);
                $apiResponse = $this->sendWaba("8114479678", $message);
                $status = 'sent';
                $deliveryStatus = json_encode($apiResponse);
            }

            if ($channel === 'text') {

                $apiResponse = $this->sendSMS("8114479678", $message);
                // $apiResponse = $this->sendSMS($student->mobile, $message);
                $status = 'sent';
                $deliveryStatus = json_encode($apiResponse);
            }
        } catch (\Exception $e) {
            $status = 'failed';
            $error = $e->getMessage();
        }

        $delivery = json_decode($deliveryStatus, true);
        \LOG::info(['delivery status' => $delivery['status']]);

        if (!empty($delivery['status']) && $delivery['status'] === 'success') {

            DB::table('notification_credits_usage')
                ->where('library_id', getLibraryId())
                ->where('channel', $channel)
                ->orderBy('id', 'DESC')
                ->limit(1)
                ->decrement('remaining', 1);

            DB::table('notification_credits_usage')
                ->where('library_id', getLibraryId())
                ->where('channel', $channel)
                ->orderBy('id', 'DESC')
                ->limit(1)
                ->increment('used', 1);
        }


        NotificationLog::create([
            'unique_id'        => Str::uuid(),
            'library_id'       => getLibraryId(),
            'branch_id'        => getCurrentBranch(),
            'learner_id'       => $student->id,
            'seat_no'          => $student->seat_no,
            'operation_id'     => $template->operation_id,
            'channel'          => $channel,
            'template_id'      => $template->id,
            'message_content'  => $message,
            'message_status'   => $delivery['status'] ?? $status,
            'delivery_status'  => $deliveryStatus,
            'error_message'    => $error,
            'cost'             => $cost,
        ]);

        return response()->json(['success' => 'Message sent successfully!']);
    }


    // waba API Code 
    public function sendWaba($mobile, $message)
    {

        $sid    = env('TWILIO_SID');
        $token  = env('TWILIO_AUTH_TOKEN');
        \Log::info('TWILIO_SID', ['TWILIO_SID' => $sid, 'TWILIO_AUTH_TOKEN' => $token]);
        if (!$sid || !$token) {
             \Log::info('Twilio credentials missing in .env');
            return [
                'status' => 'error',
                'error' => 'Twilio credentials missing in .env'
            ];
        }

        try {
            $twilio = new Client($sid, $token);
            $response = $twilio->messages->create(
                "whatsapp:+91" . $mobile,
                [
                    "from" => env("TWILIO_WHATSAPP_FROM"),
                    "body" => $message
                ]
            );

            return [
                'status' => 'success',
                'sid'    => $response->sid,
            ];
        } catch (\Exception $e) {

            return [
                'status' => 'error',
                'error'  => $e->getMessage(),
            ];
        }
    }

    public function sendSMS($mobile, $message)
    {
        $sid    = env('TWILIO_SID');
        $token  = env('TWILIO_AUTH_TOKEN');
        $from   = env('TWILIO_SMS_FROM');   // your Twilio SMS number

        try {
            $twilio = new \Twilio\Rest\Client($sid, $token);

            $sms = $twilio->messages->create(
                "+91" . $mobile,
                [
                    "from" => $from,
                    "body" => $message
                ]
            );

            return [
                "status" => "success",
                "sid" => $sms->sid
            ];
        } catch (\Exception $e) {
            return [
                "status" => "error",
                "error" => $e->getMessage()
            ];
        }
    }
}
