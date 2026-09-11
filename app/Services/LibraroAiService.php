<?php

namespace App\Services;

use App\Models\Learner;
use App\Models\LearnerDetail;
use App\Models\LearnerTransaction;
use App\Models\Seat;
use App\Models\Library;
use App\Models\UserSubscription;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LibraroAiService
{
    /**
     * Build aggregated, multi-tenant scoped context data for a specific library owner.
     *
     * @param int $libraryId
     * @param int|null $branchId
     * @return array
     */
    public function buildLibraryContextData($libraryId, $branchId = null)
    {
        $activeBranchId = $branchId ?? getCurrentBranch() ?? $libraryId;

        // 1. Library & Branch Profile
        $library = Library::find($libraryId);
        $libraryName = $library ? $library->library_name : 'Library';

        // 2. Seat Metrics
        $totalSeats = function_exists('totalSeat') ? totalSeat() : ($library->total_seats ?? 0);

        // Occupied seats
        $occupiedSeats = LearnerDetail::where('branch_id', $activeBranchId)
            ->where('status', 1)
            ->whereNotNull('seat_no')
            ->where('seat_no', '!=', '')
            ->distinct('seat_no')
            ->count('seat_no');

        $vacantSeats = max(0, $totalSeats - $occupiedSeats);

        // 3. Learner Metrics
        $totalLearners = LearnerDetail::where('branch_id', $activeBranchId)->count();
        $activeLearners = LearnerDetail::where('branch_id', $activeBranchId)->where('status', 1)->count();
        $expiredLearners = LearnerDetail::where('branch_id', $activeBranchId)->where('status', 0)->count();

        $today = date('Y-m-d');
        $sevenDaysLater = date('Y-m-d', strtotime('+7 days'));

        $expiringIn7Days = LearnerDetail::where('branch_id', $activeBranchId)
            ->where('status', 1)
            ->whereBetween('plan_end_date', [$today, $sevenDaysLater])
            ->count();

        $todayRegistrations = LearnerDetail::where('branch_id', $activeBranchId)
            ->whereDate('created_at', $today)
            ->count();

        // 4. Financial Metrics (Pending Dues & Today's Collection)
        $totalPendingDues = LearnerTransaction::where('branch_id', $activeBranchId)
            ->where('pending_amount', '>', 0)
            ->sum('pending_amount');

        $pendingLearnerCount = LearnerTransaction::where('branch_id', $activeBranchId)
            ->where('pending_amount', '>', 0)
            ->distinct('learner_id')
            ->count('learner_id');

        $todayCollection = LearnerTransaction::where('branch_id', $activeBranchId)
            ->whereDate('paid_date', $today)
            ->sum('paid_amount');

        // 5. Floors
        $floorsList = [];
        if (DB::getSchemaBuilder()->hasTable('floors')) {
            $floorsList = DB::table('floors')->where('branch_id', $activeBranchId)->pluck('name')->toArray();
        }

        // 6. Plan Types / Shifts
        $shiftsList = [];
        if (DB::getSchemaBuilder()->hasTable('plan_types')) {
            $shifts = DB::table('plan_types')->where('branch_id', $activeBranchId)->get(['name', 'start_time', 'end_time']);
            foreach ($shifts as $s) {
                $shiftsList[] = [
                    'name' => $s->name ?? 'Shift',
                    'time' => ($s->start_time ?? '') . ' - ' . ($s->end_time ?? '')
                ];
            }
        }

        // 7. Due Students List (Name, Seat No, Pending Amount, Due Date)
        $dueStudentsList = [];
        try {
            $dueQuery = DB::table('learner_transactions')
                ->join('learners', 'learner_transactions.learner_id', '=', 'learners.id')
                ->leftJoin('learner_detail', 'learner_transactions.learner_detail_id', '=', 'learner_detail.id')
                ->where('learner_transactions.branch_id', $activeBranchId)
                ->where('learner_transactions.pending_amount', '>', 0)
                ->select(
                    'learners.name',
                    'learner_detail.seat_no',
                    'learner_transactions.pending_amount',
                    'learner_transactions.due_date'
                )
                ->take(15)
                ->get();

            foreach ($dueQuery as $st) {
                $dueStudentsList[] = [
                    'name' => $st->name,
                    'seat_no' => !empty($st->seat_no) ? $st->seat_no : 'General Seat',
                    'pending_amount' => number_format($st->pending_amount, 2),
                    'due_date' => $st->due_date ?? 'N/A'
                ];
            }
        } catch (\Exception $e) {
            Log::error('Error fetching due students: ' . $e->getMessage());
        }

        // 8. Subscription Plan Details
        $subName = 'Standard Plan';
        $subEndDate = 'N/A';
        if ($library && $library->library_type) {
            $sub = Subscription::find($library->library_type);
            if ($sub) {
                $subName = $sub->name;
            }
        }
        if ($library && !empty($library->plan_end_date)) {
            $subEndDate = $library->plan_end_date;
        }

        $occupancyPct = $totalSeats > 0 ? round(($occupiedSeats / $totalSeats) * 100, 1) : 0;

        return [
            'library_name' => $libraryName,
            'branch_id' => $activeBranchId,
            'total_seats' => $totalSeats,
            'occupied_seats' => $occupiedSeats,
            'vacant_seats' => $vacantSeats,
            'occupancy_percentage' => $occupancyPct,
            'total_learners' => $totalLearners,
            'active_learners' => $activeLearners,
            'expired_learners' => $expiredLearners,
            'expiring_in_7_days' => $expiringIn7Days,
            'today_registrations' => $todayRegistrations,
            'total_pending_dues' => number_format($totalPendingDues, 2),
            'pending_learner_count' => $pendingLearnerCount,
            'today_collection' => number_format($todayCollection, 2),
            'subscription_plan' => $subName,
            'subscription_end_date' => $subEndDate,
            'floors' => $floorsList,
            'shifts' => $shiftsList,
            'due_students' => $dueStudentsList,
            'generated_at' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Process a query from a Library Owner and return an AI response.
     *
     * @param string $userQuery
     * @param int $libraryId
     * @param int|null $branchId
     * @return array
     */
    public function processQuery($userQuery, $libraryId, $branchId = null)
    {
        $activeBranchId = $branchId ?? getCurrentBranch() ?? $libraryId;
        $context = $this->buildLibraryContextData($libraryId, $activeBranchId);

        // Check if external Gemini / OpenAI API key is configured
        $geminiApiKey = config('services.gemini.api_key') ?? env('GEMINI_API_KEY');

        if (!empty($geminiApiKey)) {
            $aiMessage = $this->callGeminiApi($userQuery, $context, $geminiApiKey);
        } else {
            // Intelligent Rule-based NLP fallback engine for instant response
            $aiMessage = $this->generateLocalNlpResponse($userQuery, $context);
        }

        // Log user query to database
        DB::table('ai_chat_histories')->insert([
            'library_id' => $libraryId,
            'branch_id' => $activeBranchId,
            'sender' => 'user',
            'message' => $userQuery,
            'context_data_snapshot' => json_encode($context),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Log AI response to database
        DB::table('ai_chat_histories')->insert([
            'library_id' => $libraryId,
            'branch_id' => $activeBranchId,
            'sender' => 'assistant',
            'message' => $aiMessage,
            'context_data_snapshot' => json_encode($context),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'success' => true,
            'message' => $aiMessage,
            'context' => $context,
        ];
    }

    /**
     * Call Gemini AI API with system instructions and context JSON.
     */
    /**
     * Call Gemini AI API with system instructions and context JSON.
     */
    /**
     * Call Gemini AI API with system instructions and context JSON.
     */
    protected function callGeminiApi($userQuery, array $context, $apiKey)
    {
        $dbOperationsManual = $this->buildDatabaseOperationsManual();

        $systemPrompt = "You are Libraro AI, an intelligent assistant exclusively dedicated to Library Owners.

MULTILINGUAL LANGUAGE DIRECTIVE:
1. IF the user asks in Pure Hindi / Devanagari script (e.g., 'नया एडमिशन कैसे करें?', 'सीट कैसे बदलें?', 'बकाया फीस कितनी है?'):
   RESPOND STRICTLY IN ELEGANT PURE HINDI (शुद्ध हिन्दी भाषा, देवनागरी लिपि में).
2. IF the user asks in Hinglish (e.g., 'Admission kaise karein?'):
   RESPOND IN POLITE HINGLISH.
3. IF the user asks in English:
   RESPOND IN CLEAR CRISP ENGLISH.

STRICT DATA PRIVACY & RELEVANCE BOUNDARY:
- You are ONLY allowed to provide information about the logged-in user's specific library data (Members, Seats, Fee dues, Collections, Attendance, Subscription) AND explain how to use Libraro software operations.
- IF the user asks ANY question that is general knowledge, trivia, joke, weather, coding, recipe, math, personal, or completely irrelevant to Libraro & library operations:
  YOU MUST STRICTLY REFUSE TO ANSWER and respond ONLY with (in user's language):
  'I am not allowed to share external or general information. I can only provide information about your library data and how to use Libraro software operations.'

" . $dbOperationsManual . "

LIBRARY DATA CONTEXT:
" . json_encode($context, JSON_PRETTY_PRINT);

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$apiKey}", [
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [
                            ['text' => $systemPrompt . "\n\nUser Question: " . $userQuery]
                        ]
                    ]
                ]
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['candidates'][0]['content']['parts'][0]['text'] ?? $this->generateLocalNlpResponse($userQuery, $context);
            }
        } catch (\Exception $e) {
            Log::error('Gemini API call error: ' . $e->getMessage());
        }

        return $this->generateLocalNlpResponse($userQuery, $context);
    }

    /**
     * Build dynamic database-driven operations manual text from `how-to-use` table.
     */
    protected function buildDatabaseOperationsManual()
    {
        try {
            $records = DB::table('how-to-use')->get();
            if ($records->isEmpty()) {
                return '';
            }

            $manual = "OFFICIAL LIBRARO OPERATIONS MANUAL (FROM DATABASE `how-to-use` TABLE):\n";
            foreach ($records as $rec) {
                $manual .= "• Operation: {$rec->operation_name}\n";
                if (!empty($rec->hindi_question)) {
                    $manual .= "  Hindi Question: {$rec->hindi_question}\n";
                }
                if (!empty($rec->usage_english)) {
                    $manual .= "  Usage (English): " . trim($rec->usage_english) . "\n";
                }
                if (!empty($rec->usage_hindi)) {
                    $manual .= "  Usage (Hindi): " . trim($rec->usage_hindi) . "\n";
                }
                $manual .= "\n";
            }
            return $manual;
        } catch (\Exception $e) {
            Log::error('Error reading how-to-use table: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * High-speed, multilingual Local NLP Rule Engine for instant offline answers.
     */
    protected function generateLocalNlpResponse($userQuery, array $context)
    {
        $q = strtolower(trim($userQuery));
        $isHindiDevanagari = preg_match('/[\x{0900}-\x{097F}]/u', $userQuery);

        // 1. Greetings & Overview Summary
        if (in_array($q, ['hi', 'hello', 'namaste', 'hey', 'नमस्ते', 'प्रणाम']) || str_contains($q, 'summary') || str_contains($q, 'overview') || str_contains($q, 'aaj ka summary') || str_contains($q, 'आज का समरी')) {
            if ($isHindiDevanagari) {
                return "👋 **नमस्ते! मैं आपका लिबरारो AI असिस्टेंट हूँ।**\n\n" .
                       "आपकी लाइब्रेरी **{$context['library_name']}** का लाइव विवरण:\n\n" .
                       "• 🪑 **खाली सीटें**: {$context['vacant_seats']} / {$context['total_seats']} ({$context['occupancy_percentage']}% ऑक्यूपेंसी)\n" .
                       "• 👥 **सक्रिय सदस्य (Active)**: {$context['active_learners']}\n" .
                       "• 💰 **कुल बकाया फीस**: ₹{$context['total_pending_dues']}\n" .
                       "• 💵 **आज का कुल कलेक्शन**: ₹{$context['today_collection']}\n" .
                       "• ⏳ **7 दिनों में एक्सपायर होने वाले**: {$context['expiring_in_7_days']} सदस्य\n\n" .
                       "आप मुझसे अपनी लाइब्रेरी या लिबरारो सॉफ्टवेयर ऑपरेशन्स के बारे में कोई भी प्रश्न पूछ सकते हैं।";
            }
            return "👋 **Namaste! Main aapka Libraro AI Assistant hoon.**\n\n" .
                   "Aapki library **{$context['library_name']}** ka live summary:\n\n" .
                   "• 🪑 **Vacant Seats**: {$context['vacant_seats']} / {$context['total_seats']} ({$context['occupancy_percentage']}% Occupied)\n" .
                   "• 👥 **Active Members**: {$context['active_learners']}\n" .
                   "• 💰 **Total Pending Dues**: ₹{$context['total_pending_dues']}\n" .
                   "• 💵 **Today's Collection**: ₹{$context['today_collection']}\n" .
                   "• ⏳ **Expiring in 7 Days**: {$context['expiring_in_7_days']} members\n\n" .
                   "Aap mujhse apni library se jude sawal pooch sakte hain (e.g. *'Pending dues kitna hai?'* ya *'Vacant seats kitni hain?'*).";
        }

        // 2. Today's Total Revenue & Collection
        if (str_contains($q, 'revenue') || str_contains($q, 'today collection') || str_contains($q, 'aaj ka collection') || str_contains($q, 'aaj ki kamai') || str_contains($q, 'कलेक्शन') || str_contains($q, 'रेवेन्यू') || str_contains($q, 'आज की कमाई')) {
            if ($isHindiDevanagari) {
                return "💵 **आज का कुल कलेक्शन व रेवेन्यू ({$context['library_name']}):**\n\n" .
                       "• 💰 **आज का कुल कलेक्शन**: ₹{$context['today_collection']}\n" .
                       "• 📝 **आज के नए एडमिशन**: {$context['today_registrations']} छात्र\n" .
                       "• 📊 **कुल बकाया फीस**: ₹{$context['total_pending_dues']} ({$context['pending_learner_count']} छात्र)";
            }
            return "💵 **Today's Revenue & Collection Summary ({$context['library_name']}):**\n\n" .
                   "• 💰 **Today's Total Collection**: ₹{$context['today_collection']}\n" .
                   "• 📝 **Today's New Admissions**: {$context['today_registrations']} learners\n" .
                   "• 📊 **Total Pending Dues**: ₹{$context['total_pending_dues']} ({$context['pending_learner_count']} learners)";
        }

        // 3. Due Students List (Names, Seat Numbers, Amounts & Dates)
        if (str_contains($q, 'due student') || str_contains($q, 'bakaya student') || str_contains($q, 'due list') || str_contains($q, 'baki student') || str_contains($q, 'due name') || str_contains($q, 'बकाया छात्र') || str_contains($q, 'बकाया सूची') || str_contains($q, 'बकाया नाम')) {
            if (!empty($context['due_students'])) {
                if ($isHindiDevanagari) {
                    $out = "📋 **बकाया फीस वाले छात्र व सीट विवरण ({$context['library_name']}):**\n\n";
                    foreach ($context['due_students'] as $st) {
                        $out .= "• **{$st['name']}** | सीट नंबर: **{$st['seat_no']}** | बकाया राशि: **₹{$st['pending_amount']}** | देय तिथि: {$st['due_date']}\n";
                    }
                    return $out;
                }
                $out = "📋 **Pending Dues Student List ({$context['library_name']}):**\n\n";
                foreach ($context['due_students'] as $st) {
                    $out .= "• **{$st['name']}** | Seat No: **{$st['seat_no']}** | Dues: **₹{$st['pending_amount']}** | Due Date: {$st['due_date']}\n";
                }
                return $out;
            } else {
                return $isHindiDevanagari 
                    ? "✅ **कोई बकाया फीस नहीं!** आपकी लाइब्रेरी में सभी छात्रों की फीस जमा है।"
                    : "✅ **No Pending Dues!** All learners have cleared their payments in {$context['library_name']}.";
            }
        }

        // 4. Total Pending Dues Summary
        if (str_contains($q, 'pending') || str_contains($q, 'due') || str_contains($q, 'baki') || str_contains($q, 'bakaya') || str_contains($q, 'बकाया') || str_contains($q, 'बाकी')) {
            if ($isHindiDevanagari) {
                return "📊 **कुल बकाया फीस विवरण ({$context['library_name']}):**\n\n" .
                       "• **कुल बकाया राशि**: ₹{$context['total_pending_dues']}\n" .
                       "• **बकाया छात्र संख्या**: {$context['pending_learner_count']} छात्र\n" .
                       "• **आज का कुल कलेक्शन**: ₹{$context['today_collection']}\n\n" .
                       "💡 *टिप: आप AI से 'बकाया छात्र की सूची' पूछकर नाम व सीट नंबर देख सकते हैं।*";
            }
            return "📊 **Total Pending Dues Summary ({$context['library_name']}):**\n\n" .
                   "• **Total Pending Dues**: ₹{$context['total_pending_dues']}\n" .
                   "• **Pending Learners**: {$context['pending_learner_count']} learners\n" .
                   "• **Today's Collection**: ₹{$context['today_collection']}\n\n" .
                   "💡 *Tip: Ask 'due student list' to see all student names and seat numbers!*";
        }

        // 5. Library Occupancy & Summary
        if (str_contains($q, 'occupancy') || str_contains($q, 'percent') || str_contains($q, 'ऑक्यूपेंसी')) {
            if ($isHindiDevanagari) {
                return "📊 **लाइब्रेरी ऑक्यूपेंसी व सीट विवरण ({$context['library_name']}):**\n\n" .
                       "• **ऑक्यूपेंसी दर**: **{$context['occupancy_percentage']}%**\n" .
                       "• **कुल सीटें**: {$context['total_seats']} सीटें\n" .
                       "• **अलॉट की गई सीटें**: {$context['occupied_seats']} सीटें\n" .
                       "• **खाली सीटें**: {$context['vacant_seats']} सीटें";
            }
            return "📊 **Library Occupancy Rate & Summary ({$context['library_name']}):**\n\n" .
                   "• **Occupancy Rate**: **{$context['occupancy_percentage']}% Occupied**\n" .
                   "• **Total Capacity**: {$context['total_seats']} seats\n" .
                   "• **Occupied Seats**: {$context['occupied_seats']} seats\n" .
                   "• **Vacant Seats**: {$context['vacant_seats']} seats";
        }

        // 6. Total Seats & Floors Configured
        if (str_contains($q, 'floor') || str_contains($q, 'मंज़िल') || str_contains($q, 'मंजिल')) {
            $floorsStr = !empty($context['floors']) ? implode(', ', $context['floors']) : 'Main Floor';
            if ($isHindiDevanagari) {
                return "🏢 **लाइब्रेरी फ्लोर्स (मंज़िलें) व सीट सेटअप ({$context['library_name']}):**\n\n" .
                       "• **कुल सीटें**: {$context['total_seats']} सीटें\n" .
                       "• **कुल मंज़िलें (Floors)**: " . (count($context['floors']) > 0 ? count($context['floors']) : 1) . "\n" .
                       "• **फ्लोर सूची**: {$floorsStr}\n" .
                       "• **उपलब्ध खाली सीटें**: {$context['vacant_seats']} सीटें";
            }
            return "🏢 **Library Floor & Seat Setup ({$context['library_name']}):**\n\n" .
                   "• **Total Seats**: {$context['total_seats']} seats\n" .
                   "• **Total Floors**: " . (count($context['floors']) > 0 ? count($context['floors']) : 1) . "\n" .
                   "• **Floors List**: {$floorsStr}\n" .
                   "• **Available Vacant Seats**: {$context['vacant_seats']} seats";
        }

        // 7. Shifts & Plan Timings
        if ((str_contains($q, 'shift') || str_contains($q, 'timing') || str_contains($q, 'शिफ्ट')) && !str_contains($q, 'configure') && !str_contains($q, 'kaise')) {
            if (!empty($context['shifts'])) {
                if ($isHindiDevanagari) {
                    $out = "⏰ **लाइब्रेरी शिफ्ट टाइमिंग व प्लान फीस ({$context['library_name']}):**\n\n";
                    foreach ($context['shifts'] as $sh) {
                        $out .= "• **{$sh['name']}**: {$sh['time']}\n";
                    }
                    return $out;
                }
                $out = "⏰ **Configured Shifts & Plan Timings ({$context['library_name']}):**\n\n";
                foreach ($context['shifts'] as $sh) {
                    $out .= "• **{$sh['name']}**: {$sh['time']}\n";
                }
                return $out;
            }
        }

        // 8. Subscription Information
        if (str_contains($q, 'subscription') || str_contains($q, 'sub plan') || str_contains($q, 'सब्सक्रिप्शन')) {
            if ($isHindiDevanagari) {
                return "💳 **सब्सक्रिप्शन प्लान विवरण ({$context['library_name']}):**\n\n" .
                       "• **वर्तमान प्लान**: {$context['subscription_plan']}\n" .
                       "• **वैधता अंतिम तिथि (Plan End Date)**: {$context['subscription_end_date']}\n" .
                       "• **कुल सीट क्षमता**: {$context['total_seats']} सीटें";
            }
            return "💳 **Subscription Plan Details ({$context['library_name']}):**\n\n" .
                   "• **Active Plan**: {$context['subscription_plan']}\n" .
                   "• **Plan End Date**: {$context['subscription_end_date']}\n" .
                   "• **Total Seat Capacity**: {$context['total_seats']} seats";
        }

        // 9. Dynamic Search in `how-to-use` database table
        try {
            $dbOps = DB::table('how-to-use')->get();
            foreach ($dbOps as $op) {
                $opName = strtolower($op->operation_name ?? '');
                $hindiQ = mb_strtolower($op->hindi_question ?? '');
                $engUsage = $op->usage_english ?? '';
                $hinUsage = $op->usage_hindi ?? '';

                // Extract core key terms from operation_name
                $cleanName = preg_replace('/^(operation|masters|utility|premium operation)\s*:\s*/i', '', $opName);
                $cleanName = trim(explode('(', $cleanName)[0]);
                $tokens = array_filter(explode(' ', strtolower($cleanName)), fn($t) => strlen($t) > 3);

                $matchFound = false;
                foreach ($tokens as $t) {
                    if (str_contains($q, $t)) {
                        $matchFound = true;
                        break;
                    }
                }

                if (!$matchFound && $hindiQ && (str_contains($q, mb_substr($hindiQ, 0, 8)) || str_contains($hindiQ, mb_substr($q, 0, 8)))) {
                    $matchFound = true;
                }

                if ($matchFound) {
                    $out = "<div class=\"ai-op-card\">\n";
                    $out .= "  <div class=\"ai-op-title\"><i class=\"fa-solid fa-book-open me-2\"></i>" . htmlspecialchars($op->operation_name) . "</div>\n";
                    
                    if (!empty($hinUsage)) {
                        $out .= "  <div class=\"ai-lang-box hin-box\">\n";
                        $out .= "    <div class=\"ai-lang-title\"><span class=\"lang-pill hin\">🇮🇳 हिन्दी निर्देश</span></div>\n";
                        $out .= "    <div class=\"ai-lang-content\">" . nl2br(htmlspecialchars(trim($hinUsage))) . "</div>\n";
                        $out .= "  </div>\n";
                    }
                    
                    if (!empty($engUsage)) {
                        $out .= "  <div class=\"ai-lang-box eng-box\">\n";
                        $out .= "    <div class=\"ai-lang-title\"><span class=\"lang-pill eng\">🇬🇧 English Guide</span></div>\n";
                        $out .= "    <div class=\"ai-lang-content\">" . nl2br(htmlspecialchars(trim($engUsage))) . "</div>\n";
                        $out .= "  </div>\n";
                    }
                    
                    $out .= "</div>";
                    return $out;
                }
            }
        } catch (\Exception $e) {
            Log::error('how-to-use DB lookup error: ' . $e->getMessage());
        }

        // Due Students List Query
        if (str_contains($q, 'due student') || str_contains($q, 'bakaya student') || str_contains($q, 'due list') || str_contains($q, 'baki student') || str_contains($q, 'बकाया छात्र') || str_contains($q, 'बकाया सूची')) {
            if (!empty($context['due_students'])) {
                $out = "📋 **Pending Dues Student List ({$context['library_name']}):**\n\n";
                foreach ($context['due_students'] as $st) {
                    $out .= "• **{$st['name']}** | Seat: **{$st['seat_no']}** | Dues: **₹{$st['pending_amount']}** | Due Date: {$st['due_date']}\n";
                }
                return $out;
            } else {
                return "✅ **No Pending Dues!** All learners have cleared their payments in {$context['library_name']}.";
            }
        }

        // Floors & Seats Query
        if (str_contains($q, 'floor') || str_contains($q, 'मंज़िल') || str_contains($q, 'मंजिल')) {
            $floorsStr = !empty($context['floors']) ? implode(', ', $context['floors']) : 'Main Floor';
            return "🏢 **Library Floor & Seat Setup ({$context['library_name']})**\n\n" .
                   "• **Total Seats**: {$context['total_seats']}\n" .
                   "• **Total Floors**: " . (count($context['floors']) > 0 ? count($context['floors']) : 1) . "\n" .
                   "• **Floors List**: {$floorsStr}\n" .
                   "• **Vacant Seats**: {$context['vacant_seats']} seats available";
        }

        // Occupancy Percentage Query
        if (str_contains($q, 'occupancy') || str_contains($q, 'percent') || str_contains($q, 'ऑक्यूपेंसी')) {
            return "📊 **Seat Occupancy Rate ({$context['library_name']})**\n\n" .
                   "• **Occupancy Rate**: **{$context['occupancy_percentage']}%**\n" .
                   "• **Occupied Seats**: {$context['occupied_seats']}\n" .
                   "• **Vacant Seats**: {$context['vacant_seats']}\n" .
                   "• **Total Capacity**: {$context['total_seats']} seats";
        }

        // Shifts & Timings Query
        if ((str_contains($q, 'shift') || str_contains($q, 'timing') || str_contains($q, 'शिफ्ट')) && !str_contains($q, 'configure') && !str_contains($q, 'kaise')) {
            if (!empty($context['shifts'])) {
                $out = "⏰ **Configured Shifts & Timings ({$context['library_name']}):**\n\n";
                foreach ($context['shifts'] as $sh) {
                    $out .= "• **{$sh['name']}**: {$sh['time']}\n";
                }
                return $out;
            }
        }

        // Greetings & Summary queries
        if (in_array($q, ['hi', 'hello', 'namaste', 'hey', 'नमस्ते', 'प्रणाम']) || str_contains($q, 'summary') || str_contains($q, 'overview') || str_contains($q, 'aaj ka summary') || str_contains($q, 'आज का समरी')) {
            if ($isHindiDevanagari) {
                return "👋 **नमस्ते! मैं आपका लिबरारो AI असिस्टेंट हूँ।**\n\n" .
                       "आपकी लाइब्रेरी **{$context['library_name']}** का लाइव विवरण:\n\n" .
                       "• 🪑 **खाली सीटें**: {$context['vacant_seats']} / {$context['total_seats']} ({$context['occupancy_percentage']}% ऑक्यूपेंसी)\n" .
                       "• 👥 **सक्रिय सदस्य (Active)**: {$context['active_learners']}\n" .
                       "• 💰 **कुल बकाया फीस**: ₹{$context['total_pending_dues']}\n" .
                       "• 💵 **आज का कुल कलेक्शन**: ₹{$context['today_collection']}\n" .
                       "• ⏳ **7 दिनों में एक्सपायर होने वाले**: {$context['expiring_in_7_days']} सदस्य\n\n" .
                       "आप मुझसे अपनी लाइब्रेरी या लिबरारो सॉफ्टवेयर ऑपरेशन्स के बारे में कोई भी प्रश्न पूछ सकते हैं।";
            }
            return "👋 **Namaste! Main aapka Libraro AI Assistant hoon.**\n\n" .
                   "Aapki library **{$context['library_name']}** ka live summary:\n\n" .
                   "• 🪑 **Vacant Seats**: {$context['vacant_seats']} / {$context['total_seats']} ({$context['occupancy_percentage']}% Occupied)\n" .
                   "• 👥 **Active Members**: {$context['active_learners']}\n" .
                   "• 💰 **Total Pending Dues**: ₹{$context['total_pending_dues']}\n" .
                   "• 💵 **Today's Collection**: ₹{$context['today_collection']}\n" .
                   "• ⏳ **Expiring in 7 Days**: {$context['expiring_in_7_days']} members\n\n" .
                   "Aap mujhse apni library se jude sawal pooch sakte hain (e.g. *'Pending dues kitna hai?'* ya *'Vacant seats kitni hain?'*).";
        }

        // How to Add Learner / Admission guide
        if (str_contains($q, 'add learner') || str_contains($q, 'new learner') || str_contains($q, 'admission kaise') || str_contains($q, 'book learner') || str_contains($q, 'एडमिशन') || str_contains($q, 'नया छात्र')) {
            if ($isHindiDevanagari) {
                return "📖 **लिबरारो में नया एडमिशन (Add Learner) कैसे करें:**\n\n" .
                       "1. **Learners** मेन्यू में जाएं और **'+ Add Learner'** बटन पर क्लिक करें।\n" .
                       "2. **Assign Seat No ?** चुनें:\n" .
                       "   • **Yes, Allot a Seat No.** = फिक्स्ड सीट नंबर (जैसे सीट #12)\n" .
                       "   • **No** = जनरल सीट (अन-रिजर्व्ड सीट)\n" .
                       "3. छात्र का नाम, मोबाइल नंबर, शिफ्ट और एडमिशन की तारीख भरें।\n" .
                       "4. पेमेंट विवरण (कैश, ऑनलाइन या पे-लेटर) दर्ज करें।\n" .
                       "5. **Save** पर क्लिक करके एडमिशन पूरा करें!";
            }
            return "📖 **How to Add a New Learner in Libraro:**\n\n" .
                   "1. Go to **Learners** menu and click **'+ Add Learner'** button.\n" .
                   "2. Select **Assign Seat No ?**:\n" .
                   "   • **Yes, Allot a Seat No.** = Specific Seat Number (e.g. #12)\n" .
                   "   • **No** = General / Unassigned Seat\n" .
                   "3. Fill in Learner Name, Mobile Number, Shift, and Start Date.\n" .
                   "4. Enter Payment details (Cash, Online, or Pay Later).\n" .
                   "5. Click **Save** to complete admission!";
        }

        // How to Swap / Change Seat guide
        if (str_contains($q, 'swap') || str_contains($q, 'change seat') || str_contains($q, 'seat badal') || str_contains($q, 'सीट बदल') || str_contains($q, 'सीट स्वैप')) {
            if ($isHindiDevanagari) {
                return "🔄 **सीट कैसे बदलें (Seat Swap Operations):**\n\n" .
                       "1. **Learners List** खोलें।\n" .
                       "2. संबंधित छात्र की पंक्ति में **Action Menu** खोलें।\n" .
                       "3. **'Swap Seat'** आइकन पर क्लिक करें।\n" .
                       "4. सीट मैट्रिक्स में से नई खाली सीट चुनें।\n" .
                       "5. **Confirm Swap** पर क्लिक करके सीट तुरंत ट्रांसफर करें!";
            }
            return "🔄 **How to Swap / Change a Seat:**\n\n" .
                   "1. Go to **Learners List**.\n" .
                   "2. Locate the learner and click **Action Menu**.\n" .
                   "3. Click **'Swap Seat'** icon.\n" .
                   "4. Select the new vacant seat from the matrix.\n" .
                   "5. Click **Confirm Swap** to transfer the seat instantly!";
        }

        // How to Send Reminders guide
        if (str_contains($q, 'whatsapp') || str_contains($q, 'reminder') || str_contains($q, 'notice') || str_contains($q, 'रिमाइंडर') || str_contains($q, 'व्हाट्सएप')) {
            if ($isHindiDevanagari) {
                return "📲 **पेमेंट और एक्सपायरी रिमाइंडर कैसे भेजें:**\n\n" .
                       "1. **Pending Dues** या **Learners List** पर जाएं।\n" .
                       "2. छात्र के नाम के आगे **WhatsApp / SMS आइकन** पर क्लिक करें।\n" .
                       "3. मैसेज टेम्पलेट (बकाया फीस / एक्सपायरी नोटिस / वेलकम) चुनें।\n" .
                       "4. **Send** पर क्लिक करके तुरंत रिमाइंडर भेजें!";
            }
            return "📲 **How to Send Payment / Expiry Reminders:**\n\n" .
                   "1. Go to **Pending Dues** or **Learners List**.\n" .
                   "2. Click the **WhatsApp / Text icon** next to the member's name.\n" .
                   "3. Select pre-made message template (Due Fee / Expiry Warning / Welcome).\n" .
                   "4. Click **Send** to send instant reminder!";
        }

        // How to Configure Shifts & Pricing guide
        if (str_contains($q, 'shift') || str_contains($q, 'timing') || str_contains($q, 'price configure') || str_contains($q, 'शिफ्ट') || str_contains($q, 'समय सेट')) {
            if ($isHindiDevanagari) {
                return "⚙️ **शिफ्ट और प्लान फीस कैसे सेट करें:**\n\n" .
                       "1. **Library Master -> Plan Types** में जाएं।\n" .
                       "2. **'Add Plan Type'** बटन पर क्लिक करें।\n" .
                       "3. शिफ्ट का नाम (सुबह / शाम / फुल डे), समय (Timings) और मासिक फीस दर्ज करें।\n" .
                       "4. **Save** पर क्लिक करें!";
            }
            return "⚙️ **How to Configure Shifts & Plan Types:**\n\n" .
                   "1. Go to **Library Master -> Plan Types**.\n" .
                   "2. Click **'Add Plan Type'**.\n" .
                   "3. Define Shift Name (Morning/Evening/Full Day), Timings, and Price.\n" .
                   "4. Click **Save** to make shift active for new bookings!";
        }

        // General Seat vs Fixed Seat guide
        if (str_contains($q, 'general seat') || str_contains($q, 'fixed seat') || str_contains($q, 'difference') || str_contains($q, 'जनरल सीट') || str_contains($q, 'फिक्स्ड सीट')) {
            if ($isHindiDevanagari) {
                return "🪑 **जनरल सीट बनाम फिक्स्ड सीट (अंतर):**\n\n" .
                       "• **फिक्स्ड सीट (Fixed Seat)**: छात्र को एक निश्चित सीट नंबर (उदा. सीट #10) अलॉट की जाती है।\n" .
                       "• **जनरल सीट (General Seat)**: छात्र किसी भी उपलब्ध अन-रिजर्व्ड कुर्सी पर बैठ सकता है।";
            }
            return "🪑 **General Seat vs Fixed Seat:**\n\n" .
                   "• **Fixed Seat**: Allots a specific numbered seat (e.g. Seat #10) exclusively to the member.\n" .
                   "• **General Seat**: Allows member to sit on any open non-reserved seat without reserving a specific number.";
        }

        // Dues / Pending / Payment / Fee queries
        if (str_contains($q, 'pending') || str_contains($q, 'due') || str_contains($q, 'baki') || str_contains($q, 'bakaya') || str_contains($q, 'payment') || str_contains($q, 'fees') || str_contains($q, 'fee') || str_contains($q, 'बकाया') || str_contains($q, 'फीस')) {
            if ($isHindiDevanagari) {
                return "📊 **बकाया फीस विवरण ({$context['library_name']}):**\n\n" .
                       "• **कुल बकाया राशि**: ₹{$context['total_pending_dues']}\n" .
                       "• **बकाया छात्र संख्या**: {$context['pending_learner_count']} छात्र\n" .
                       "• **आज का कुल कलेक्शन**: ₹{$context['today_collection']}\n\n" .
                       "💡 *टिप: आप 'Pending Dues' सेक्शन से व्हाट्सएप रिमाइंडर भेज सकते हैं।*";
            }
            return "📊 **Pending Dues Summary ({$context['library_name']})**\n\n" .
                   "• **Total Pending Dues**: ₹{$context['total_pending_dues']}\n" .
                   "• **Pending Learners**: {$context['pending_learner_count']} learners\n" .
                   "• **Today's Collection**: ₹{$context['today_collection']}\n\n" .
                   "💡 *Tip: Aap 'Pending Dues' report section se payment reminders WhatsApp par bhej sakte hain!*";
        }

        // Seats / Occupancy / Vacant queries
        if (str_contains($q, 'seat') || str_contains($q, 'vacant') || str_contains($q, 'empty') || str_contains($q, 'khali') || str_contains($q, 'capacity') || str_contains($q, 'occupied') || str_contains($q, 'सीट') || str_contains($q, 'खाली')) {
            if ($isHindiDevanagari) {
                return "🪑 **सीट उपलब्धता विवरण:**\n\n" .
                       "• **कुल सीटें**: {$context['total_seats']}\n" .
                       "• **बुक सीटें**: {$context['occupied_seats']}\n" .
                       "• **खाली (उपलब्ध) सीटें**: **{$context['vacant_seats']} सीटें**\n\n" .
                       "📌 *आप डैशबोर्ड सीट लेआउट से खाली सीट अलॉट कर सकते हैं।*";
            }
            return "🪑 **Seat Occupancy Overview**\n\n" .
                   "• **Total Seats**: {$context['total_seats']}\n" .
                   "• **Occupied Seats**: {$context['occupied_seats']}\n" .
                   "• **Vacant (Available) Seats**: **{$context['vacant_seats']} seats**\n\n" .
                   "📌 *Aap Dashboard 'Seat Layout' se vacant seats ko directly allot kar sakte hain.*";
        }

        // Learner count / Active / Expired / Expiry queries
        if (str_contains($q, 'learner') || str_contains($q, 'student') || str_contains($q, 'active') || str_contains($q, 'expire') || str_contains($q, 'admissions') || str_contains($q, 'admission') || str_contains($q, 'total') || str_contains($q, 'member') || str_contains($q, 'छात्र') || str_contains($q, 'सदस्य')) {
            if ($isHindiDevanagari) {
                return "👥 **छात्र और सदस्य विवरण:**\n\n" .
                       "• **कुल पंजीकृत छात्र**: {$context['total_learners']}\n" .
                       "• **सक्रिय छात्र (Active)**: {$context['active_learners']}\n" .
                       "• **एक्सपायर छात्र**: {$context['expired_learners']}\n" .
                       "• **7 दिनों में एक्सपायर होने वाले**: {$context['expiring_in_7_days']} छात्र\n" .
                       "• **आज के नए एडमिशन**: {$context['today_registrations']}";
            }
            return "👥 **Learners Analytics**\n\n" .
                   "• **Total Registered Learners**: {$context['total_learners']}\n" .
                   "• **Active Members**: {$context['active_learners']}\n" .
                   "• **Expired Members**: {$context['expired_learners']}\n" .
                   "• **Expiring in Next 7 Days**: {$context['expiring_in_7_days']} learners\n" .
                   "• **Today's New Admissions**: {$context['today_registrations']}\n\n" .
                   "⚡ *Expired members ko 'Reactivate' karne ke liye Learner List inspect karein.*";
        }

        // Plan / Subscription / Expiry queries
        if (str_contains($q, 'plan') || str_contains($q, 'subscription') || str_contains($q, 'validity') || str_contains($q, 'renew') || str_contains($q, 'प्लान') || str_contains($q, 'सब्सक्रिप्शन')) {
            if ($isHindiDevanagari) {
                return "💳 **वर्तमान सब्सक्रिप्शन प्लान विवरण:**\n\n" .
                       "• **एक्टिव प्लान**: {$context['subscription_plan']}\n" .
                       "• **वैधता अंतिम तिथि**: {$context['subscription_end_date']}\n" .
                       "• **लाइब्रेरी का नाम**: {$context['library_name']}";
            }
            return "💳 **Current Subscription Plan**\n\n" .
                   "• **Active Plan**: {$context['subscription_plan']}\n" .
                   "• **Plan End Date**: {$context['subscription_end_date']}\n" .
                   "• **Library Name**: {$context['library_name']}\n\n" .
                   "✨ *Plan upgrade ya seat limit badhane ke liye Subscriptions tab visit karein.*";
        }

        // Strict Refusal for any Irrelevant / Off-Topic / External Questions
        if ($isHindiDevanagari) {
            return "⚠️ **सूचना**: मुझे केवल आपकी लाइब्रेरी के आंकड़ों (जैसे छात्र, सीटें, बकाया फीस, कलेक्शन) और लिबरारो सॉफ्टवेयर ऑपरेशन्स की जानकारी देने की अनुमति है।\n\n*कृपया केवल लाइब्रेरी से संबंधित प्रश्न ही पूछें (जैसे 'एडमिशन कैसे करें?', 'सीट कैसे बदलें?', या 'बकाया फीस कितनी है?|)*";
        }
        return "⚠️ **Notice**: I am not allowed to share external or general information. I can only provide information about your library data (such as Members, Seats, Dues, Collections, and Subscription details) and explain how to use Libraro software features.\n\n*Kripya apni library ya Libraro software se jude sawal hi poochhein (jaise 'Admission kaise karein?', 'Seat swap kaise karein?', ya 'Pending dues kitna hai?').*";
    }
}
