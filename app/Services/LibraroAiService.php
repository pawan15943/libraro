<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

/**
 * Libraro AI — a self-contained (no external API) assistant for library owners and staff.
 *
 * It understands questions in English, Hinglish and Hindi (Devanagari), works out the intent
 * (dues, collection, seats, attendance, "how to …" guides, a learner or seat lookup …), reads the
 * live data of the current branch and answers in English or Hindi.
 *
 * The answer is a structured "reply" (title, stat tiles, tables, guides, links, follow-up questions)
 * that the chat widget renders; nothing in it is HTML, so learner names etc. are always escaped
 * on the client.
 */
class LibraroAiService
{
    use Ai\SupportDesk;

    private const NON_MONEY_MODES = ['PAYLATER'];

    /** Concept => phrases (English, Hinglish, Hindi). Matched on the normalised query. */
    private const CONCEPTS = [
        'greeting' => ['hi', 'hii', 'hello', 'hey', 'namaste', 'namaskar', 'good morning', 'good evening', 'नमस्ते', 'नमस्कार', 'प्रणाम', 'हेलो', 'हाय'],
        'help' => ['help', 'madad', 'what can you do', 'kya kar sakte', 'kya puch', 'options', 'मदद', 'सहायता', 'क्या कर सकते'],
        'summary' => ['summary', 'overview', 'report card', 'status', 'haal', 'hal chal', 'dashboard data', 'snapshot', 'सारांश', 'समरी', 'हाल', 'स्थिति', 'ओवरव्यू', 'hisab', 'hisaab', 'हिसाब', 'kaisi chal rahi', 'kaisa chal raha', 'kaise chal rahi', 'how is my library', 'how is the library', 'performance', 'business kaisa', 'कैसी चल रही', 'कैसा चल रहा'],
        'collection' => ['collection', 'collections', 'collected', 'collect', 'revenue', 'income', 'earning', 'earnings', 'kamai', 'kamaai', 'aamdani', 'amdani', 'received', 'payment received', 'fees received', 'fee aayi', 'paisa aaya', 'paise aaye', 'cash', 'nakad', 'नकद', 'कैश', 'money', 'money received', 'paisa', 'paise', 'पैसा', 'पैसे', 'मिला पैसा', 'कलेक्शन', 'कमाई', 'आमदनी', 'रेवेन्यू', 'वसूली', 'जमा'],
        'due' => ['due', 'dues', 'pending', 'baki', 'baaki', 'bakaya', 'bakaaya', 'outstanding', 'unpaid', 'udhar', 'udhaar', 'remaining fee', 'has to pay', 'have to pay', 'not paid', 'dena hai', 'देना है', 'नहीं दिया', 'बकाया', 'बाकी', 'बाक़ी', 'उधार', 'पेंडिंग', 'शेष'],
        'list' => ['list', 'names', 'name', 'naam', 'who', 'whom', 'kaun', 'kon', 'kiska', 'kiske', 'kinka', 'details', 'detail', 'show', 'dikhao', 'batao', 'sabhi', 'सूची', 'लिस्ट', 'नाम', 'कौन', 'किसका', 'किसके', 'किनका', 'दिखाएं', 'दिखाओ', 'बताओ', 'बताएं', 'विवरण'],
        'seat' => ['seat', 'seats', 'kursi', 'chair', 'chairs', 'सीट', 'सीटें', 'कुर्सी'],
        'vacant' => ['vacant', 'empty', 'khali', 'khaali', 'free', 'available', 'unbooked', 'खाली', 'उपलब्ध', 'फ्री'],
        'occupancy' => ['occupancy', 'occupied', 'filled', 'booked', 'percentage', 'percent', 'bhari', 'ऑक्यूपेंसी', 'भरी', 'प्रतिशत', 'बुक'],
        'admission' => ['admission', 'admissions', 'joined', 'joining', 'new learner', 'new learners', 'new student', 'new students', 'new member', 'new members', 'naye', 'naya', 'registration', 'registrations', 'एडमिशन', 'दाखिला', 'नए', 'नया', 'नये', 'जुड़े'],
        'expire' => ['expire', 'expires', 'expiring', 'expiry', 'expired', 'ending', 'end ho', 'khatam', 'khtm', 'renewal due', 'एक्सपायर', 'समाप्त', 'खत्म', 'ख़त्म'],
        'future' => ['next', 'upcoming', 'agle', 'aane wale', 'aanewale', 'coming', 'soon', 'will', 'hone wale', 'honge', 'अगले', 'आने वाले', 'होंगे', 'होने वाले', 'जल्द'],
        'past' => ['expired', 'ho gaye', 'ho gya', 'ho gaya', 'ho chuke', 'already', 'recently', 'last', 'pichle', 'हो गए', 'हो गया', 'हो चुके', 'पिछले', 'हाल ही'],
        'active' => ['active', 'current', 'running', 'chalu', 'एक्टिव', 'सक्रिय', 'चालू'],
        'learner' => ['learner', 'learners', 'student', 'students', 'member', 'members', 'chhatra', 'chatra', 'bacche', 'bache', 'log', 'छात्र', 'छात्रों', 'सदस्य', 'सदस्यों', 'विद्यार्थी', 'बच्चे', 'लर्नर'],
        'count' => ['how many', 'how much', 'kitne', 'kitna', 'kitni', 'total', 'count', 'number of', 'kul', 'कितने', 'कितना', 'कितनी', 'कुल', 'संख्या', 'टोटल'],
        'attendance' => ['attendance', 'present', 'absent', 'hazri', 'haziri', 'hajri', 'aaye', 'aaya', 'aye', 'came', 'inside', 'andar', 'punch', 'हाजिरी', 'हाज़िरी', 'उपस्थिति', 'उपस्थित', 'अटेंडेंस', 'आए', 'आये', 'आया', 'अंदर', 'गैरहाजिर', 'अनुपस्थित'],
        'expense' => ['expense', 'expenses', 'kharcha', 'kharch', 'spent', 'spending', 'cost', 'खर्च', 'खर्चा', 'व्यय', 'एक्सपेंस'],
        'profit' => ['profit', 'munafa', 'munafaa', 'net', 'bachat', 'saving', 'loss', 'nuksan', 'मुनाफा', 'मुनाफ़ा', 'लाभ', 'प्रॉफिट', 'बचत', 'नुकसान'],
        'shift' => ['shift', 'shifts', 'timing', 'timings', 'time slot', 'plan type', 'plan types', 'samay', 'शिफ्ट', 'टाइमिंग', 'समय'],
        'floor' => ['floor', 'floors', 'manzil', 'manjil', 'मंजिल', 'मंजिलें', 'फ्लोर'],
        'subscription' => ['subscription', 'my plan', 'libraro plan', 'software plan', 'validity', 'account plan', 'सब्सक्रिप्शन', 'मेरा प्लान', 'वैधता'],
        'birthday' => ['birthday', 'birthdays', 'janamdin', 'janmdin', 'bday', 'जन्मदिन', 'बर्थडे'],
        'search' => ['search', 'find', 'lookup', 'khojo', 'dhundo', 'dhoondo', 'profile of', 'details of', 'info of', 'खोजें', 'खोजो', 'ढूंढो', 'ढूंढें'],
        'howto' => ['how to', 'how do', 'how can', 'how does', 'how should', 'kaise', 'kese', 'kaisey', 'steps', 'process', 'tarika', 'procedure', 'guide', 'tips', 'tip', 'advice', 'suggest', 'where can', 'where do', 'where is', 'kahan se', 'kaha se', 'kahan', 'what is', 'what are', 'kya hai', 'kya hota', 'meaning', 'why', 'kyu', 'kyon', 'can i', 'should i', 'कैसे', 'तरीका', 'प्रक्रिया', 'क्या है', 'क्या होता', 'कहां से', 'कहाँ से', 'कहाँ', 'कहां', 'क्यों', 'सुझाव'],
        'price' => ['price', 'prices', 'fee', 'fees', 'फीस', 'rate', 'rates', 'rate list', 'fee structure', 'fees structure', 'plan price', 'plan prices', 'monthly fee', 'monthly fees', 'plan fee', 'plan fees', 'shift fee', 'shift fees', 'charges', 'kitne ki', 'kitne ka', 'कीमत', 'रेट', 'फीस स्ट्रक्चर', 'शुल्क', 'दाम', 'मासिक फीस'],
        'refund' => ['refund', 'refunds', 'refunded', 'money back', 'paise wapas', 'paisa wapas', 'रिफंड', 'पैसे वापस'],
        'renewal' => ['renewal', 'renewals', 'renewed', 'renew hue', 'renew kiye', 'रिन्यूअल', 'रिन्यू हुए', 'रिन्यू किए'],
        'compare' => ['compare', 'comparison', 'vs', 'versus', 'growth', 'trend', 'better', 'worse', 'up or down', 'last month se', 'pichle mahine se', 'tulna', 'badha', 'ghata', 'तुलना', 'बनाम', 'ग्रोथ', 'बढ़ा', 'घटा', 'पिछले महीने से'],
        'frozen' => ['frozen', 'freezed', 'freeze wale', 'paused', 'on hold', 'फ्रीज वाले', 'फ्रीज़ वाले', 'होल्ड'],
        'locker' => ['locker', 'lockers', 'लॉकर'],
        'enquiry' => ['enquiry', 'enquiries', 'inquiry', 'inquiries', 'lead', 'leads', 'online booking', 'online bookings', 'booking request', 'booking requests', 'pending booking', 'pending bookings', 'पूछताछ', 'इन्क्वायरी', 'ऑनलाइन बुकिंग'],
        'branch' => ['branch', 'branches', 'ब्रांच', 'शाखा', 'शाखाएं'],
        'staff' => ['staff', 'employee', 'employees', 'users', 'स्टाफ', 'कर्मचारी'],
        'feedback' => ['feedback', 'rating', 'ratings', 'review', 'reviews', 'फीडबैक', 'रेटिंग', 'राय', 'रिव्यू'],
        'profile' => ['address', 'contact number', 'mobile number', 'phone number', 'upi id', 'library details', 'library info', 'library information', 'branch details', 'working days', 'extension days', 'extend days', 'पता', 'संपर्क', 'लाइब्रेरी की जानकारी', 'ब्रांच की जानकारी'],
        'mode' => ['cash', 'upi', 'online', 'payment mode', 'mode wise', 'नकद', 'कैश', 'ऑनलाइन'],
    ];

    /** "How to" synonyms, keyed by a pattern that matches the operation_name in the `how-to-use` table. */
    private const GUIDE_SYNONYMS = [
        '/book seat/i' => ['book seat', 'seat book', 'booking', 'book a seat', 'add learner', 'new admission', 'admission', 'naya admission', 'allot seat', 'assign seat', 'सीट बुक', 'बुक करें', 'बुक कैसे', 'एडमिशन', 'दाखिला', 'नया छात्र', 'सीट अलॉट'],
        '/^re-?new seat/i' => ['renew', 'renewal', 'extend plan', 'plan badhana', 'रिन्यू', 'नवीनीकरण'],
        // the library's own Libraro plan (not a learner's seat): "libraro ka plan kaise renew kare"
        // only "renew / upgrade" questions: "my libraro plan" alone is the subscription data answer
        '/^my plan, renew and upgrade libraro/i' => ['libraro plan renew', 'libraro plan upgrade', 'renew libraro', 'libraro renew', 'upgrade libraro', 'libraro upgrade', 'libraro subscription renew', 'लाइब्रेरो प्लान रिन्यू', 'Libraro प्लान रिन्यू'],
        '/swap/i' => ['swap', 'change seat', 'seat change', 'seat badal', 'seat number change', 'सीट बदल', 'स्वैप'],
        '/change plan/i' => ['change plan', 'plan change', 'change shift', 'shift change', 'plan badal', 'shift badal', 'प्लान बदल', 'शिफ्ट बदल'],
        '/^upgrade plan/i' => ['upgrade', 'अपग्रेड'],
        '/delete seat/i' => ['delete', 'delete seat', 'remove learner', 'refund', 'डिलीट', 'हटाएं', 'हटाना', 'रिफंड'],
        '/close seat/i' => ['close seat', 'closed seat', 'क्लोज'],
        '/id card/i' => ['id card', 'id कार्ड', 'आईडी', 'कार्ड', 'identity card', 'icard', 'आईडी कार्ड', 'पहचान पत्र', 'आई कार्ड'],
        '/edit learner/i' => ['edit', 'edit learner', 'update profile', 'modify', 'change name', 'change mobile', 'एडिट', 'प्रोफाइल बदल'],
        '/receipt/i' => ['receipt', 'bill', 'invoice', 'raseed', 'रसीद', 'बिल'],
        '/loca?ker/i' => ['locker', 'लॉकर'],
        '/discount/i' => ['discount', 'concession', 'chhoot', 'छूट', 'डिस्काउंट'],
        '/partial/i' => ['partial', 'part payment', 'half payment', 'aadha payment', 'आंशिक', 'आधा पेमेंट'],
        '/reactiv/i' => ['reactivate', 'activate again', 'wapas chalu', 'दोबारा चालू', 'फिर से चालू', 'रीएक्टिवेट'],
        '/take & view attendance/i' => ['attendance', 'hazri', 'mark attendance', 'हाजिरी', 'उपस्थिति', 'अटेंडेंस'],
        '/add library expenses/i' => ['add expense', 'expense', 'expenses', 'खर्च', 'खर्चा', 'expense add', 'kharcha jod', 'kharcha add', 'खर्च जोड़', 'खर्चा जोड़'],
        '/search learner/i' => ['search', 'search learner', 'find learner', 'खोज'],
        '/miscellaneous/i' => ['miscellaneous', 'misc', 'other payment', 'अन्य भुगतान'],
        '/masters in/i' => ['master', 'masters', 'मास्टर'],
        '/branch master/i' => ['branch', 'new branch', 'add branch', 'ब्रांच', 'शाखा'],
        '/floor master/i' => ['floor', 'add floor', 'फ्लोर', 'मंजिल'],
        '/^plan master/i' => ['plan master', 'create plan', 'add plan', 'monthly plan', 'प्लान बनाएं'],
        '/plan type \/ shift master/i' => ['shift', 'plan type', 'add shift', 'add a shift', 'new shift', 'create shift', 'शिफ्ट', 'शिफ्ट जोड़'],
        '/plan price/i' => ['price', 'change plan price', 'change price', 'price change', 'कीमत बदल', 'fees set', 'set fee', 'plan price', 'fee change', 'कीमत', 'फीस सेट', 'फीस बदल'],
        '/expense master/i' => ['expense name', 'expense category', 'expense master'],
        '/exam/i' => ['exam', 'परीक्षा', 'एग्जाम'],
        '/users master/i' => ['staff', 'user', 'users', 'employee', 'स्टाफ', 'कर्मचारी'],
        '/reports/i' => ['report', 'reports', 'रिपोर्ट'],
        '/gift/i' => ['gift', 'gift days', 'extra days', 'गिफ्ट'],
        '/freeze/i' => ['freeze', 'unfreeze', 'pause plan', 'फ्रीज', 'अनफ्रीज'],
        '/import learner/i' => ['import', 'upload excel', 'bulk upload', 'excel', 'इम्पोर्ट', 'एक्सेल'],
        '/csv/i' => ['csv', 'setup library', 'सीएसवी'],
        '/payment options/i' => ['payment mode', 'payment option', 'online payment', 'paylater', 'pay later', 'upi', 'पेमेंट मोड', 'ऑनलाइन पेमेंट'],
        '/custom plan/i' => ['custom', 'custom plan', 'कस्टम'],
        '/end date/i' => ['end date', 'expiry date', 'change end date', 'एंड डेट', 'समाप्ति तिथि'],
        '/text and whatsapp/i' => ['sms', 'notification', 'notifications', 'text message', 'नोटिफिकेशन', 'मैसेज'],
        '/free whatsapp/i' => ['whatsapp', 'reminder', 'reminders', 'व्हाट्सएप', 'रिमाइंडर'],
        '/security/i' => ['security', 'data safe', 'privacy', 'सुरक्षा'],
        '/seat map/i' => ['seat map', 'seat layout', 'layout', 'सीट मैप'],
        '/dashboard/i' => ['dashboard', 'डैशबोर्ड'],
        '/register/i' => ['register', 'library register', 'रजिस्टर'],
        '/future/i' => ['future booking', 'advance booking', 'एडवांस बुकिंग'],
        '/website/i' => ['website', 'seo', 'listing', 'वेबसाइट'],
        '/online seat bookings/i' => ['get online bookings', 'online seat booking', 'online booking kaise', 'ऑनलाइन बुकिंग कैसे'],
        '/video/i' => ['video', 'tutorial', 'वीडियो', 'ट्यूटोरियल'],
    ];

    /** Hinglish words that tell us to answer in Hindi when the language is "auto". */
    private const HINGLISH_MARKERS = ['kitna', 'kitne', 'kitni', 'kaise', 'kese', 'batao', 'bataiye', 'dikhao', 'hai', 'hain', 'aaj', 'kal', 'karein', 'karna', 'kare', 'kya', 'kaun', 'kon', 'kiska', 'wale', 'bakaya', 'baki', 'mahine', 'hafte', 'mera', 'meri', 'mujhe', 'khali', 'kamai', 'kharcha', 'kab', 'kahan', 'abhi', 'sabhi', 'naye', 'nahi', 'nhi', 'raha', 'rahi', 'rahe', 'gaya', 'gayi', 'kyu', 'kyun', 'mujhe', 'apna', 'apni', 'dikh', 'hua', 'karna', 'karu', 'kaise', 'wala'];

    private string $lang = 'en';

    private string $q = '';

    private int $branchId = 0;

    private int $libraryId = 0;

    /** Who is asking: "library:5" (owner) or "library_user:12" (staff). */
    private string $actor = '';

    /** Staff account id — null for the library owner (owners can see everything). */
    private ?int $staffId = null;

    /** What this answer was about, remembered for follow-up questions ("aur kal ka?"). */
    private array $newContext = [];

    /** Data answers and the permission a staff member needs to see them. '__owner__' = owner only. */
    private const INTENT_PERMISSIONS = [
        'collection' => 'Payment Collection Report',
        'refunds' => 'Payment Collection Report',
        'renewals' => 'Payment Collection Report',
        'expenses' => 'Monthly Report',
        'profit' => 'Monthly Report',
        'compareMonths' => 'Monthly Report',
        'duesTotal' => 'Pending Payment Report',
        'duesList' => 'Pending Payment Report',
        'expiring' => 'Upcoming Payment Report',
        'expired' => 'Expired Learners Report',
        'attendance' => 'Attendance Report',
        'admissions' => 'Learner List',
        'learners' => 'Learner List',
        'learnerSearch' => 'Learner List',
        'learnerCard' => 'Learner List',
        'frozenLearners' => 'Learner List',
        'lockers' => 'Learner List',
        'shiftLearners' => 'Learner List',
        'birthdays' => 'Learner List',
        'seats' => 'Seat Assignment',
        'seatLookup' => 'Seat Assignment',
        'enquiries' => 'Library Enquiry',
        'feedbackSummary' => 'Learner Feedback',
        'staffList' => 'User management',
        'subscription' => '__owner__',
        'branches' => '__owner__',
    ];

    /** Answers that can be asked again for another period / range ("aur kal ka?", "and 30 days?"). */
    private const FOLLOW_UP_INTENTS = ['collection', 'expenses', 'profit', 'admissions', 'refunds', 'renewals', 'expiring', 'expired'];

    private const PERMISSION_LABELS = [
        'Payment Collection Report' => ['collection & payments', 'कलेक्शन व भुगतान'],
        'Monthly Report' => ['expenses & profit', 'खर्च व मुनाफा'],
        'Pending Payment Report' => ['pending dues', 'बकाया फीस'],
        'Upcoming Payment Report' => ['upcoming renewals', 'आने वाले रिन्यूअल'],
        'Expired Learners Report' => ['expired learners', 'एक्सपायर छात्र'],
        'Attendance Report' => ['attendance', 'हाजिरी'],
        'Learner List' => ['learner details', 'छात्रों की जानकारी'],
        'Seat Assignment' => ['seats', 'सीटें'],
        'Library Enquiry' => ['enquiries', 'पूछताछ'],
        'Learner Feedback' => ['learner feedback', 'छात्रों का फीडबैक'],
        'User management' => ['staff accounts', 'स्टाफ अकाउंट'],
        '__owner__' => ['owner-only details', 'केवल मालिक की जानकारी'],
    ];

    public function __construct(private DashboardInsightsService $insights) {}

    /*
    |--------------------------------------------------------------------------
    | Entry point
    |--------------------------------------------------------------------------
    */

    /**
     * @param  string  $lang  'auto' | 'en' | 'hi'
     * @return array{success: bool, reply: array, message: string}
     */
    public function processQuery(string $userQuery, int $libraryId, ?int $branchId = null, string $lang = 'auto', string $mode = 'ask', ?string $shown = null): array
    {
        $shown = $shown !== null && trim($shown) !== '' ? trim($shown) : null;
        // A button label ("shown") decides the language when the query itself is a code like "#cat booking"
        $this->boot($libraryId, $branchId, $lang, $shown ?? $userQuery);
        $this->q = $this->fixTypos($this->normalise($userQuery));

        try {
            $reply = $mode === 'support' ? $this->supportAnswer($userQuery) : $this->answer($userQuery);
        } catch (\Throwable $e) {
            Log::error('Libraro AI error: '.$e->getMessage(), ['query' => $userQuery]);
            $reply = $this->reply('error', 'fa-triangle-exclamation',
                $this->t('Something went wrong', 'कुछ गड़बड़ हो गई'),
                [$this->note($this->t('I could not read your data right now. Please try again in a moment.', 'अभी आपका डेटा पढ़ नहीं पाया। कृपया थोड़ी देर बाद फिर कोशिश करें।'), 'bad')],
                [], $this->defaultSuggestions());
        }

        $reply['lang'] = $this->lang;
        if ($mode === 'support') {
            $reply['mode'] = 'support';
        }
        $reply['id'] = $this->log($shown ?? $userQuery, $reply);

        return ['success' => true, 'reply' => $reply, 'message' => $this->plainText($reply)];
    }

    private function boot(int $libraryId, ?int $branchId, string $lang, string $sample = ''): void
    {
        $this->libraryId = $libraryId;
        $this->branchId = (int) ($branchId ?: getCurrentBranch() ?: 0);
        $this->lang = $this->resolveLanguage($sample, $lang);
        [$this->actor, $this->staffId] = $this->resolveActor($libraryId);
        $this->newContext = [];

        // "Sandeep" or "seat 12" carries no language — keep the language of the conversation
        if ($lang === 'auto' && $sample !== '' && ! $this->hasLanguageSignal($sample)) {
            $prev = $this->lastContext()['lang'] ?? null;
            if (in_array($prev, ['en', 'hi'], true)) {
                $this->lang = $prev;
            }
        }
    }

    /** Devanagari, Hinglish words or plain English words tell which language to answer in. */
    private function hasLanguageSignal(string $raw): bool
    {
        if (preg_match('/[\x{0900}-\x{097F}]/u', $raw)) {
            return true;
        }
        $words = preg_split('/[^a-z]+/', strtolower($raw)) ?: [];
        $english = ['the', 'is', 'are', 'not', 'my', 'how', 'what', 'why', 'when', 'where', 'which', 'who', 'can', 'cannot', 'please', 'showing', 'show', 'working', 'still', 'it', 'this', 'to', 'of', 'for', 'and', 'do', 'does', 'did', 'today', 'list'];

        return (bool) (array_intersect($words, self::HINGLISH_MARKERS) || array_intersect($words, $english));
    }

    /** "library:5" for the owner, "library_user:12" for a staff account. */
    public function actorFor(int $libraryId): string
    {
        return $this->resolveActor($libraryId)[0];
    }

    /** Owner or staff, on the web panel or the mobile app. */
    private function resolveActor(int $libraryId): array
    {
        foreach (['library_user', 'library_user_api', 'library_api'] as $guard) {
            try {
                $u = Auth::guard($guard)->user();
            } catch (\Throwable $e) {
                $u = null;
            }
            if ($u instanceof \App\Models\LibraryUser) {
                return ['library_user:'.$u->id, (int) $u->id];
            }
        }

        return ['library:'.$libraryId, null];
    }

    /*
    |--------------------------------------------------------------------------
    | Permissions (staff accounts see only what their role allows)
    |--------------------------------------------------------------------------
    */

    private array $permissionCache = [];

    private function can(?string $permission): bool
    {
        if ($permission === null || $this->staffId === null) {
            return true; // the owner sees everything
        }
        if ($permission === '__owner__') {
            return false;
        }

        return $this->permissionCache[$permission] ??= DB::table('library_user_permissions')
            ->join('permissions', 'permissions.id', '=', 'library_user_permissions.permission_id')
            ->where('library_user_permissions.library_user_id', $this->staffId)
            ->where('permissions.name', $permission)
            ->exists();
    }

    private function canIntent(string $intent): bool
    {
        return $this->can(self::INTENT_PERMISSIONS[$intent] ?? null);
    }

    /** Runs a data answer if this user may see it, otherwise explains why not. */
    private function run(string $intent, ...$args): array
    {
        if (! $this->canIntent($intent)) {
            return $this->denied(self::INTENT_PERMISSIONS[$intent]);
        }
        if (in_array($intent, self::FOLLOW_UP_INTENTS, true)) {
            $this->newContext['intent'] = $intent;
        }

        return $this->{$intent}(...$args);
    }

    private function denied(string $permission): array
    {
        $label = self::PERMISSION_LABELS[$permission] ?? [$permission, $permission];

        return $this->reply('denied', 'fa-lock', $this->t('You don\'t have access to this', 'आपको इसकी अनुमति नहीं है'), [
            $this->note($this->t(
                'Your staff account cannot see '.$label[0].'. Please ask the library owner to give you this permission.',
                'आपका स्टाफ अकाउंट '.$label[1].' नहीं देख सकता। कृपया लाइब्रेरी मालिक से यह अनुमति मांगें।'
            ), 'warn'),
        ], [], $this->defaultSuggestions());
    }

    /*
    |--------------------------------------------------------------------------
    | Conversation memory (follow-up questions)
    |--------------------------------------------------------------------------
    */

    /** What the previous answer (same user, same branch, last 30 minutes) was about. */
    private function lastContext(): array
    {
        $row = DB::table('ai_chat_histories')
            ->where('library_id', $this->libraryId)
            ->where('branch_id', $this->branchId ?: null)
            ->where('actor', $this->actor)
            ->where('sender', 'assistant')
            ->where('created_at', '>=', now()->subMinutes(30))
            ->orderByDesc('id')
            ->value('context_data_snapshot');

        return $row ? (json_decode($row, true) ?: []) : [];
    }

    /**
     * "aur kal ka?" / "and last month?" → the previous answer for a new period;
     * "uska bakaya?" / "his plan?" → the learner from the previous answer.
     */
    private function followUp(): ?array
    {
        $last = $this->lastContext();
        if (! $last) {
            return null;
        }

        $pronoun = preg_match('/ (uska|uski|uske|iska|iski|iske|unka|unki|unke|his|her|their|him|this learner|same learner|उसका|उसकी|उसके|इसका|इसकी|इसके|उनका|उनकी|उनके) /u', $this->q);
        if ($pronoun && ! empty($last['learner_id'])) {
            return $this->run('learnerCard', (int) $last['learner_id']);
        }

        $words = count(preg_split('/\s+/u', trim($this->q)) ?: []);
        $timeOnly = $words <= 6 && preg_match('/ (aur|and|what about|or|phir|fir|और|फिर) | (today|aaj|yesterday|kal|week|hafte|month|mahine|mahina|year|saal|last|pichle|next|agle|\d+ ?(days?|din)) |(?<!\S)(आज|कल|हफ्ते|महीने|साल|पिछले|अगले|दिन)(?!\S)/u', $this->q);
        if ($timeOnly && ! empty($last['intent']) && in_array($last['intent'], self::FOLLOW_UP_INTENTS, true)) {
            [$intent] = $this->dataIntent();
            if ($intent === null || $intent === $last['intent']) {
                return $this->run($last['intent']);
            }
        }

        return null;
    }

    private function answer(string $raw): array
    {
        $has = fn (string $c) => $this->has($c);

        // 1. A seat number ("seat 12", "सीट 12 पर कौन है", "12 number seat")
        if ($seat = $this->seatNumber()) {
            return $this->run('seatLookup', $seat);
        }

        // 2. Short greetings and "what can you do"
        $words = count(preg_split('/\s+/u', trim($this->q)) ?: []);
        if ($has('greeting') && $words <= 3) {
            return $this->summary(true);
        }
        if ($has('help') && $words <= 6 && ! $has('howto')) {
            return $this->capabilities();
        }
        // "how do I use Libraro / what features does it have" — an overview, not one guide topic
        if ($this->asksAboutLibraro()) {
            return $this->libraroOverview();
        }

        // 3. Follow-ups on the previous answer ("aur kal ka?", "uska bakaya?")
        if ($follow = $this->followUp()) {
            return $follow;
        }

        // 4. A learner's name inside the question ("rahul ki fees kitni baki hai")
        if ($name = $this->nameInQuery()) {
            return $this->run('learnerSearch', $name);
        }

        // 4. "How to / what is / why …" — guides, FAQs and library tips (but not "how many / how much")
        $knowledge = $this->knowledge();
        [$intent, $score] = $this->dataIntent();
        $isHowTo = $has('howto') && ! preg_match('/\bhow (many|much)\b/u', $this->q);
        if ($isHowTo && $knowledge && $score <= $knowledge['score'] * 1.5) {
            return $knowledge['reply'];
        }

        // 4. Learner search ("find rahul", "rahul ki details")
        if ($has('search') && ($term = $this->searchTerm())) {
            return $this->run('learnerSearch', $term);
        }

        // 6. Data questions vs. knowledge — whichever matches the question better
        if ($knowledge && $knowledge['score'] >= 3 && $knowledge['score'] >= $score * 1.5) {
            return $knowledge['reply'];
        }
        if ($intent) {
            return $this->run($intent);
        }
        if ($knowledge) {
            return $knowledge['reply'];
        }

        // 7. A bare name ("rahul sharma")
        if ($words <= 3 && $this->canIntent('learnerSearch') && ($term = $this->searchTerm()) && ($found = $this->learnerSearch($term, true))) {
            return $found;
        }

        return $this->notUnderstood();
    }

    /**
     * Scores every data intent on the concepts found in the query.
     *
     * @return array{0: ?string, 1: float} [method, score]
     */
    private function dataIntent(): array
    {
        $h = fn (string $c) => $this->has($c) ? 1 : 0;

        $scores = [
            'planPrices' => $h('price') * 4 * (1 - $h('due')) * (1 - $h('collection')) * (1 - $h('refund')),
            'refunds' => $h('refund') * 5,
            'renewals' => $h('renewal') * 4,
            'compareMonths' => $h('compare') * (3 + 2 * max($h('collection'), $h('admission'), $h('profit'), $h('expense'), $h('renewal'))),
            'shiftLearners' => $h('shift') * max($h('learner'), $h('count')) * 6,
            'frozenLearners' => $h('frozen') * 5,
            'lockers' => $h('locker') * 4,
            'enquiries' => $h('enquiry') * 4,
            'branches' => $h('branch') * 3,
            'staffList' => $h('staff') * 3,
            'feedbackSummary' => $h('feedback') * 4,
            'libraryProfile' => $h('profile') * 4,
            'duesList' => $h('due') * (3 + 2 * max($h('list'), $h('learner'))) * (1 - $h('count') * 0.5),
            'duesTotal' => $h('due') * (3 + $h('count')),
            'collection' => $h('collection') * 4 + ($h('collection') ? $h('count') : 0) + ($h('mode') && ! $h('howto') ? 2 : 0),
            'profit' => $h('profit') * 5,
            'expenses' => $h('expense') * 4,
            'admissions' => $h('admission') * 4,
            'expiring' => $h('expire') * (3 + 2 * $h('future') + ($h('past') ? -2 : 0)),
            'expired' => $h('expire') * (2 + 2 * $h('past')),
            'attendance' => $h('attendance') * 4,
            'birthdays' => $h('birthday') * 5,
            'seats' => max($h('seat'), $h('vacant'), $h('occupancy')) * 3 + $h('vacant') + $h('occupancy'),
            'floors' => $h('floor') * 4,
            'shifts' => $h('shift') * 4,
            'subscription' => $h('subscription') * 5,
            'learners' => max($h('learner'), $h('active')) ? 2 + $h('active') + $h('count') : 0,
            'summaryIntent' => $h('summary') * 3 + $h('greeting'),
        ];

        // A "due" date question about plan expiry is about expiry, not fees
        if ($scores['expiring'] > 0 && ! $this->has('collection')) {
            $scores['duesList'] *= 0.5;
            $scores['duesTotal'] *= 0.5;
        }
        // "compare" is the main ask — the metric words only pick what to compare
        if ($scores['compareMonths'] > 0) {
            $scores['collection'] = $scores['admissions'] = $scores['profit'] = $scores['expenses'] = $scores['renewals'] = 0;
        }
        if ($scores['shiftLearners'] > 0) {
            $scores['shifts'] = 0;
        }
        // Concepts that are specific beat the generic "learner" one
        $specific = ['admissions', 'expiring', 'expired', 'attendance', 'duesTotal', 'birthdays', 'shiftLearners', 'frozenLearners', 'lockers', 'renewals', 'feedbackSummary', 'refunds'];
        if (max(array_map(fn ($k) => $scores[$k], $specific)) > 0) {
            $scores['learners'] = 0;
        }

        arsort($scores);
        $best = array_key_first($scores);

        return $scores[$best] > 0 ? [$best, (float) $scores[$best]] : [null, 0.0];
    }

    /*
    |--------------------------------------------------------------------------
    | Data answers
    |--------------------------------------------------------------------------
    */

    private function summaryIntent(): array
    {
        return $this->summary(false);
    }

    private function summary(bool $greet): array
    {
        $today = Carbon::today()->toDateString();

        $blocks = [];
        if ($greet) {
            $blocks[] = $this->text($this->t(
                'Namaste! I\'m Limo, your Libraro assistant. Here is how '.$this->branchName().' looks today. Ask me anything about your library — dues, seats, collection, attendance or how to use any feature.',
                'नमस्ते! मैं Limo हूँ, आपका Libraro सहायक। आज '.$this->branchName().' का हाल यह है। आप मुझसे लाइब्रेरी के बारे में कुछ भी पूछ सकते हैं — बकाया, सीटें, कलेक्शन, हाजिरी या किसी फ़ीचर का उपयोग।'
            ));
        }
        // Only the tiles this user is allowed to see (staff permissions)
        $tiles = [];
        if ($this->canIntent('collection')) {
            $collection = $this->collectionTotal($today, $today);
            $tiles[] = $this->stat($this->t("Today's collection", 'आज का कलेक्शन'), $this->inr($collection['total']), 'good', $this->t($collection['count'].' payments', $collection['count'].' भुगतान'));
        }
        if ($this->canIntent('duesTotal')) {
            $dues = $this->duesTotals();
            $tiles[] = $this->stat($this->t('Pending dues', 'कुल बकाया'), $this->inr($dues['amount']), $dues['amount'] > 0 ? 'bad' : 'good', $this->t($dues['learners'].' learners', $dues['learners'].' छात्र'));
        }
        if ($this->canIntent('learners')) {
            $tiles[] = $this->stat($this->t('Active learners', 'सक्रिय छात्र'), (string) $this->activeLearnerCount(), 'info');
        }
        if ($this->canIntent('attendance')) {
            $att = $this->attendanceCounts();
            $tiles[] = $this->stat($this->t('Present today', 'आज उपस्थित'), $att['came'].' / '.$att['all'], 'info', $this->t($att['in'].' inside now', 'अभी '.$att['in'].' अंदर'));
        }
        if ($this->canIntent('seats')) {
            $seats = $this->seatStats();
            $tiles[] = $this->stat($this->t('Vacant seats', 'खाली सीटें'), $seats['vacant'].' / '.$seats['total'], $seats['vacant'] > 0 ? 'good' : 'warn', $seats['pct'].'% '.$this->t('occupied', 'भरी'));
        }
        if ($this->canIntent('expiring')) {
            $expiring = $this->expiringQuery(7)->count();
            $tiles[] = $this->stat($this->t('Expiring in 7 days', '7 दिन में एक्सपायर'), (string) $expiring, $expiring > 0 ? 'warn' : 'good');
        }
        if ($tiles) {
            $blocks[] = $this->stats($tiles);
        }

        return $this->reply('summary', 'fa-gauge-high',
            $this->t('Today at a glance', 'आज का सारांश').' · '.$this->branchName(),
            $blocks,
            [$this->link($this->t('Open dashboard', 'डैशबोर्ड खोलें'), 'library.home')],
            [$this->t('Show due students list', 'बकाया छात्रों की सूची दिखाओ'), $this->t('Whose plan expires in next 7 days?', 'अगले 7 दिन में किसका प्लान खत्म होगा?'), $this->t('This month collection', 'इस महीने का कलेक्शन')]
        );
    }

    private function collection(): array
    {
        [$from, $to, $label] = $this->period('today');
        $c = $this->collectionTotal($from, $to);

        $modes = DB::table('learner_transaction_activity')
            ->where('branch_id', $this->branchId)->whereNull('deleted_at')
            ->where('dr_cr', 'Cr')->where('amount', '>', 0)
            ->whereNotIn('payment_mode', self::NON_MONEY_MODES)
            ->whereBetween('date', [$from, $to])
            ->selectRaw('UPPER(payment_mode) as mode, SUM(amount) as total, COUNT(*) as n')
            ->groupBy(DB::raw('UPPER(payment_mode)'))->orderByDesc('total')->get();

        $stats = [
            $this->stat($this->t('Collected', 'कुल कलेक्शन'), $this->inr($c['total']), 'good', $label),
            $this->stat($this->t('Payments', 'भुगतान'), (string) $c['count'], 'info'),
        ];
        foreach ($modes->take(4) as $m) {
            $stats[] = $this->stat(ucwords(strtolower((string) $m->mode ?: 'Other')), $this->inr((float) $m->total), 'neutral', $m->n.' ×');
        }

        $rows = DB::table('learner_transaction_activity as a')
            ->leftJoin('learners as l', 'l.id', '=', 'a.learner_id')
            ->where('a.branch_id', $this->branchId)->whereNull('a.deleted_at')
            ->where('a.dr_cr', 'Cr')->where('a.amount', '>', 0)
            ->whereNotIn('a.payment_mode', self::NON_MONEY_MODES)
            ->whereBetween('a.date', [$from, $to])
            ->orderByDesc('a.date')->orderByDesc('a.id')
            ->limit(10)
            ->get(['a.date', 'a.amount', 'a.payment_mode', 'l.name']);

        $blocks = [$this->stats($stats)];
        if ($from !== $to && $c['total'] > 0) {
            $blocks[] = $this->collectionChart($from, $to);
        }
        if ($rows->isNotEmpty()) {
            $blocks[] = $this->table(
                [$this->t('Learner', 'छात्र'), $this->t('Date', 'तारीख'), $this->t('Mode', 'मोड'), $this->t('Amount', 'राशि')],
                $rows->map(fn ($r) => [(string) ($r->name ?? 'Walk-in'), $this->date($r->date), ucwords(strtolower((string) $r->payment_mode)), $this->inr((float) $r->amount)])->all(),
                max(0, $c['count'] - 10),
                $this->t('Latest payments', 'हाल के भुगतान')
            );
        } else {
            $blocks[] = $this->note($this->t('No payment has been received in this period yet.', 'इस अवधि में अभी तक कोई भुगतान नहीं आया है।'), 'info');
        }

        return $this->reply('collection', 'fa-indian-rupee-sign',
            $this->t('Collection', 'कलेक्शन').' · '.$label, $blocks,
            [$this->link($this->t('Collection report', 'कलेक्शन रिपोर्ट'), 'payment.collection.report')],
            [$this->t("Yesterday's collection", 'कल का कलेक्शन'), $this->t('This month collection', 'इस महीने का कलेक्शन'), $this->t('Profit this month', 'इस महीने का मुनाफा')]
        );
    }

    /** Daily bars for up to two months, monthly bars beyond that. */
    private function collectionChart(string $from, string $to): ?array
    {
        $days = Carbon::parse($from)->diffInDays(Carbon::parse($to)) + 1;
        $monthly = $days > 62;
        $key = $monthly ? "DATE_FORMAT(date, '%Y-%m')" : 'date';
        $sums = DB::table('learner_transaction_activity')
            ->where('branch_id', $this->branchId)->whereNull('deleted_at')
            ->where('dr_cr', 'Cr')->where('amount', '>', 0)
            ->whereNotIn('payment_mode', self::NON_MONEY_MODES)
            ->whereBetween('date', [$from, $to])
            ->groupBy(DB::raw($key))
            ->selectRaw($key.' as k, SUM(amount) as total')
            ->pluck('total', 'k');

        $items = [];
        $cur = Carbon::parse($from);
        $end = Carbon::parse($to);
        while ($cur->lte($end)) {
            $k = $monthly ? $cur->format('Y-m') : $cur->toDateString();
            $v = (float) ($sums[$k] ?? 0);
            $items[] = [$monthly ? $cur->format('M') : $cur->format('j M'), $v, $this->inr($v)];
            $monthly ? $cur->addMonthNoOverflow()->startOfMonth() : $cur->addDay();
        }

        return $this->bars($monthly ? $this->t('Collection by month', 'महीने के हिसाब से कलेक्शन') : $this->t('Collection by day', 'दिन के हिसाब से कलेक्शन'), $items);
    }

    private function expenses(): array
    {
        [$from, $to, $label] = $this->period('month');
        $base = DB::table('learner_transaction_activity')
            ->where('branch_id', $this->branchId)->whereNull('deleted_at')
            ->where('dr_cr', 'Dr')->where('payment_type', 'EXPENSE')
            ->whereBetween('date', [$from, $to]);

        $total = (float) (clone $base)->sum('amount');
        $byHead = (clone $base)->selectRaw('particular, SUM(amount) as total, COUNT(*) as n')
            ->groupBy('particular')->orderByDesc('total')->limit(10)->get();

        $blocks = [$this->stats([
            $this->stat($this->t('Total expenses', 'कुल खर्च'), $this->inr($total), $total > 0 ? 'warn' : 'good', $label),
            $this->stat($this->t('Entries', 'एंट्री'), (string) (clone $base)->count(), 'info'),
        ])];
        if ($byHead->count() > 1) {
            $blocks[] = $this->bars($this->t('Where the money went', 'पैसा कहाँ खर्च हुआ'), $byHead->map(fn ($r) => [(string) ($r->particular ?: 'Other'), (float) $r->total, $this->inr((float) $r->total)])->all());
        }
        $blocks[] = $byHead->isNotEmpty()
            ? $this->table([$this->t('Expense', 'खर्च'), $this->t('Entries', 'एंट्री'), $this->t('Amount', 'राशि')],
                $byHead->map(fn ($r) => [(string) ($r->particular ?: 'Other'), (string) $r->n, $this->inr((float) $r->total)])->all())
            : $this->note($this->t('No expense recorded in this period.', 'इस अवधि में कोई खर्च दर्ज नहीं है।'), 'info');

        return $this->reply('expenses', 'fa-wallet', $this->t('Expenses', 'खर्च').' · '.$label, $blocks,
            [$this->link($this->t('Expense list', 'खर्च सूची'), 'add.expense.list')],
            [$this->t('Profit this month', 'इस महीने का मुनाफा'), $this->t('How to add expense?', 'खर्च कैसे जोड़ें?')]
        );
    }

    private function profit(): array
    {
        [$from, $to, $label] = $this->period('month');
        $income = $this->collectionTotal($from, $to)['total'];
        $expense = (float) DB::table('learner_transaction_activity')
            ->where('branch_id', $this->branchId)->whereNull('deleted_at')
            ->where('dr_cr', 'Dr')->where('payment_type', 'EXPENSE')
            ->whereBetween('date', [$from, $to])->sum('amount');
        $net = $income - $expense;

        return $this->reply('profit', 'fa-chart-line', $this->t('Profit', 'मुनाफा').' · '.$label, [
            $this->stats([
                $this->stat($this->t('Collection', 'कलेक्शन'), $this->inr($income), 'good'),
                $this->stat($this->t('Expenses', 'खर्च'), $this->inr($expense), 'warn'),
                $this->stat($net >= 0 ? $this->t('Net profit', 'शुद्ध मुनाफा') : $this->t('Net loss', 'शुद्ध नुकसान'), $this->inr(abs($net)), $net >= 0 ? 'good' : 'bad'),
            ]),
            $this->note($this->t('Profit = money received from learners − expenses you recorded. Pay-later amounts are not counted until paid.', 'मुनाफा = छात्रों से मिली राशि − दर्ज किए गए खर्च। Pay-later राशि भुगतान होने पर ही गिनी जाती है।'), 'info'),
        ], [$this->link($this->t('Monthly report', 'मासिक रिपोर्ट'), 'report.monthly')],
            [$this->t('Profit last month', 'पिछले महीने का मुनाफा'), $this->t('This month expenses', 'इस महीने का खर्च')]);
    }

    private function duesTotal(): array
    {
        $d = $this->duesTotals();
        $blocks = [$this->stats([
            $this->stat($this->t('Total pending', 'कुल बकाया'), $this->inr($d['amount']), $d['amount'] > 0 ? 'bad' : 'good'),
            $this->stat($this->t('Learners with dues', 'बकाया वाले छात्र'), (string) $d['learners'], 'warn'),
            $this->stat($this->t('Overdue', 'तारीख निकल गई'), $this->inr($d['overdue']), $d['overdue'] > 0 ? 'bad' : 'good', $this->t($d['overdue_learners'].' learners', $d['overdue_learners'].' छात्र')),
        ])];
        if ($d['amount'] <= 0) {
            $blocks[] = $this->note($this->t('Great — nobody has pending fees.', 'बढ़िया — किसी की फीस बाकी नहीं है।'), 'good');
        }

        return $this->reply('dues', 'fa-hand-holding-dollar', $this->t('Pending dues', 'बकाया फीस'), $blocks,
            [$this->link($this->t('Pending payment report', 'बकाया रिपोर्ट'), 'pending.payment.report')],
            [$this->t('Show due students list', 'बकाया छात्रों की सूची दिखाओ'), $this->t('How to send WhatsApp reminder?', 'व्हाट्सएप रिमाइंडर कैसे भेजें?')]);
    }

    private function duesList(): array
    {
        $today = Carbon::today()->toDateString();
        $rows = DB::table('learner_transactions as t')
            ->join('learners as l', 'l.id', '=', 't.learner_id')
            ->leftJoin('learner_detail as ld', 'ld.id', '=', 't.learner_detail_id')
            ->where('t.branch_id', $this->branchId)
            ->whereNull('t.deleted_at')->whereNull('l.deleted_at')
            ->where('t.pending_amount', '>', 0)
            ->orderByRaw('t.due_date IS NULL, t.due_date ASC')
            ->limit(50)
            ->get(['l.id', 'l.name', 'l.mobile', 't.id as tid', 'ld.seat_no', 't.pending_amount', 't.due_date']);

        $d = $this->duesTotals();
        $canCollect = $this->can('Pending Payment');
        $acts = $rows->take(25)->map(fn ($r) => [
            $this->waAction($r, 'due', ['amount' => $r->pending_amount, 'date' => $r->due_date]),
            $canCollect ? $this->linkAction($this->t('Collect', 'बकाया लें'), 'learner.pending.payment', ['id' => $r->tid], 'fa-indian-rupee-sign') : null,
        ])->all();
        if ($rows->isEmpty()) {
            return $this->reply('dues', 'fa-circle-check', $this->t('No pending dues', 'कोई बकाया नहीं'),
                [$this->note($this->t('Every learner has paid in full.', 'सभी छात्रों ने पूरी फीस जमा कर दी है।'), 'good')], [], $this->defaultSuggestions());
        }

        $table = $rows->take(25)->map(function ($r) use ($today) {
            $late = $r->due_date && $r->due_date < $today;

            return [
                ['text' => (string) $r->name, 'url' => $this->url('learners.show', $r->id)],
                $this->seatLabel($r->seat_no),
                $this->inr((float) $r->pending_amount),
                ['text' => $r->due_date ? $this->date($r->due_date) : '—', 'tone' => $late ? 'bad' : null],
            ];
        })->all();

        return $this->reply('dues', 'fa-list-check', $this->t('Students with pending dues', 'बकाया फीस वाले छात्र'), [
            $this->stats([
                $this->stat($this->t('Total pending', 'कुल बकाया'), $this->inr($d['amount']), 'bad'),
                $this->stat($this->t('Learners', 'छात्र'), (string) $d['learners'], 'warn'),
            ]),
            $this->table([$this->t('Name', 'नाम'), $this->t('Seat', 'सीट'), $this->t('Due', 'बकाया'), $this->t('Due date', 'देय तिथि')], $table, max(0, $rows->count() - 25), null, $acts),
        ], [$this->link($this->t('Pending payment report', 'बकाया रिपोर्ट'), 'pending.payment.report')],
            [$this->t('How to send WhatsApp reminder?', 'व्हाट्सएप रिमाइंडर कैसे भेजें?'), $this->t("Today's collection", 'आज का कलेक्शन')]);
    }

    private function admissions(): array
    {
        [$from, $to, $label] = $this->period('today');
        $rows = DB::table('learners as l')
            ->where('l.branch_id', $this->branchId)->whereNull('l.deleted_at')
            ->whereBetween('l.created_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->orderByDesc('l.created_at')
            ->get(['l.id', 'l.name', 'l.seat_no', 'l.created_at']);

        $blocks = [$this->stats([$this->stat($this->t('New admissions', 'नए एडमिशन'), (string) $rows->count(), $rows->count() ? 'good' : 'neutral', $label)])];
        $blocks[] = $rows->isNotEmpty()
            ? $this->table([$this->t('Name', 'नाम'), $this->t('Seat', 'सीट'), $this->t('Joined', 'जुड़े')],
                $rows->take(25)->map(fn ($r) => [['text' => (string) $r->name, 'url' => $this->url('learners.show', $r->id)], $this->seatLabel($r->seat_no), $this->date($r->created_at)])->all(),
                max(0, $rows->count() - 25))
            : $this->note($this->t('No new admission in this period.', 'इस अवधि में कोई नया एडमिशन नहीं हुआ।'), 'info');

        return $this->reply('admissions', 'fa-user-plus', $this->t('New admissions', 'नए एडमिशन').' · '.$label, $blocks,
            [$this->link($this->t('Learner report', 'छात्र रिपोर्ट'), 'learner.report')],
            [$this->t('New admissions this month', 'इस महीने कितने नए एडमिशन हुए?'), $this->t('How to book a seat?', 'सीट कैसे बुक करें?')]);
    }

    private function expiring(): array
    {
        $days = $this->numberOfDays() ?? 7;
        $rows = $this->expiringQuery($days)->orderBy('end_date')->limit(60)->get();

        $today = Carbon::today();
        $table = $rows->take(30)->map(function ($r) use ($today) {
            $left = $today->diffInDays(Carbon::parse($r->end_date), false);

            return [
                ['text' => (string) $r->name, 'url' => $this->url('learners.show', $r->id)],
                $this->seatLabel($r->seat_no),
                $this->date($r->end_date),
                ['text' => $left === 0 ? $this->t('Today', 'आज') : $this->t($left.' days', $left.' दिन'), 'tone' => $left <= 2 ? 'bad' : 'warn'],
            ];
        })->all();

        $blocks = [$this->stats([$this->stat($this->t('Plans ending', 'प्लान खत्म होंगे'), (string) $rows->count(), $rows->count() ? 'warn' : 'good', $this->t('next '.$days.' days', 'अगले '.$days.' दिन'))])];
        $blocks[] = $rows->isNotEmpty()
            ? $this->table([$this->t('Name', 'नाम'), $this->t('Seat', 'सीट'), $this->t('Ends on', 'खत्म तिथि'), $this->t('Left', 'बाकी')], $table, max(0, $rows->count() - 30), null, $this->renewActions($rows->take(30), 'expiring'))
            : $this->note($this->t('No plan is ending in this period.', 'इस अवधि में किसी का प्लान खत्म नहीं हो रहा।'), 'good');

        return $this->reply('expiring', 'fa-hourglass-half', $this->t('Plans expiring soon', 'जल्द खत्म होने वाले प्लान'), $blocks,
            [$this->link($this->t('Upcoming payment report', 'आने वाले भुगतान रिपोर्ट'), 'upcoming.payment.report')],
            [$this->t('Expiring in next 30 days', 'अगले 30 दिन में किसका प्लान खत्म होगा?'), $this->t('How to renew a seat?', 'सीट रिन्यू कैसे करें?'), $this->t('Recently expired learners', 'हाल ही में एक्सपायर हुए छात्र')]);
    }

    private function expired(): array
    {
        $days = $this->numberOfDays() ?? 30;
        $today = Carbon::today();
        $from = $today->copy()->subDays($days)->toDateString();

        $latest = DB::table('learner_detail')
            ->where('branch_id', $this->branchId)->whereNull('deleted_at')
            ->groupBy('learner_id')
            ->selectRaw('learner_id, MAX(plan_end_date) as end_date, MAX(seat_no) as seat_no');

        $rows = DB::table('learners as l')
            ->joinSub($latest, 'x', 'x.learner_id', '=', 'l.id')
            ->where('l.branch_id', $this->branchId)->whereNull('l.deleted_at')
            ->where(fn ($q) => $q->whereNull('l.no_expiry')->orWhere('l.no_expiry', '!=', 1))
            ->where('x.end_date', '<', $today->toDateString())
            ->where('x.end_date', '>=', $from)
            ->orderByDesc('x.end_date')
            ->limit(60)
            ->get(['l.id', 'l.name', 'l.mobile', 'x.seat_no', 'x.end_date']);

        $blocks = [$this->stats([$this->stat($this->t('Expired', 'एक्सपायर'), (string) $rows->count(), $rows->count() ? 'bad' : 'good', $this->t('last '.$days.' days', 'पिछले '.$days.' दिन'))])];
        $blocks[] = $rows->isNotEmpty()
            ? $this->table([$this->t('Name', 'नाम'), $this->t('Seat', 'सीट'), $this->t('Ended on', 'खत्म हुआ')],
                $rows->take(30)->map(fn ($r) => [['text' => (string) $r->name, 'url' => $this->url('learners.show', $r->id)], $this->seatLabel($r->seat_no), $this->date($r->end_date)])->all(),
                max(0, $rows->count() - 30), null, $this->renewActions($rows->take(30), 'expired'))
            : $this->note($this->t('No plan has expired in this period.', 'इस अवधि में किसी का प्लान एक्सपायर नहीं हुआ।'), 'good');

        return $this->reply('expired', 'fa-user-clock', $this->t('Recently expired learners', 'हाल ही में एक्सपायर हुए छात्र'), $blocks,
            [$this->link($this->t('Expired learner report', 'एक्सपायर छात्र रिपोर्ट'), 'expired.learner.report')],
            [$this->t('How to reactivate a learner?', 'छात्र को दोबारा चालू कैसे करें?'), $this->t('Whose plan expires in next 7 days?', 'अगले 7 दिन में किसका प्लान खत्म होगा?')]);
    }

    private function attendance(): array
    {
        $live = $this->insights->liveAttendance($this->branchId);
        $c = $live['counts'];
        $came = $c['in'] + $c['out'];
        $wantAbsent = (bool) preg_match('/absent|nahi aaye|nahi aya|not come|didn|गैरहाजिर|अनुपस्थित|नहीं आए|नहीं आये|नहीं आया/u', $this->q);

        $people = collect($live['people'])->filter(fn ($p) => $wantAbsent ? $p['state'] === 'late' : $p['state'] !== 'late')->values();
        $state = fn ($s) => $s === 'in' ? ['text' => $this->t('Inside', 'अंदर'), 'tone' => 'good'] : ($s === 'out' ? ['text' => $this->t('Left', 'चले गए'), 'tone' => null] : ['text' => $this->t('Not come', 'नहीं आए'), 'tone' => 'bad']);

        $blocks = [$this->stats([
            $this->stat($this->t('Came today', 'आज आए'), $came.' / '.$c['all'], 'good'),
            $this->stat($this->t('Inside now', 'अभी अंदर'), (string) $c['in'], 'info'),
            $this->stat($this->t('Not come yet', 'अभी तक नहीं आए'), (string) $c['late'], $c['late'] ? 'warn' : 'good'),
        ])];
        if ($people->isNotEmpty()) {
            $blocks[] = $this->table([$this->t('Name', 'नाम'), $this->t('Seat', 'सीट'), $this->t('Status', 'स्थिति')],
                $people->take(30)->map(fn ($p) => [['text' => $p['name'], 'url' => $p['url']], $p['seat'], $state($p['state'])])->all(),
                max(0, $people->count() - 30),
                $wantAbsent ? $this->t('Not come today', 'आज नहीं आए') : $this->t('Came today', 'आज आए'));
        }

        return $this->reply('attendance', 'fa-user-check', $this->t("Today's attendance", 'आज की हाजिरी'), $blocks,
            [$this->link($this->t('Attendance report', 'हाजिरी रिपोर्ट'), 'attendance.report')],
            [$wantAbsent ? $this->t('Who came today?', 'आज कौन आया?') : $this->t('Who is absent today?', 'आज कौन नहीं आया?'), $this->t('How to take attendance?', 'हाजिरी कैसे लें?')]);
    }

    private function birthdays(): array
    {
        $h = $this->insights->highlights($this->branchId);
        $list = $h['birthdays'] ?? [];

        return $this->reply('birthdays', 'fa-cake-candles', $this->t("Today's birthdays", 'आज के जन्मदिन'), [
            $list
                ? $this->table([$this->t('Name', 'नाम'), $this->t('Seat', 'सीट')], array_map(fn ($b) => [$b['name'], $b['seat']], $list))
                : $this->note($this->t('No learner has a birthday today.', 'आज किसी छात्र का जन्मदिन नहीं है।'), 'info'),
        ], [], $this->defaultSuggestions());
    }

    private function seats(): array
    {
        $s = $this->seatStats();
        $blocks = [$this->stats([
            $this->stat($this->t('Total seats', 'कुल सीटें'), (string) $s['total'], 'info'),
            $this->stat($this->t('Booked', 'बुक'), (string) $s['occupied'], 'warn'),
            $this->stat($this->t('Vacant', 'खाली'), (string) $s['vacant'], $s['vacant'] ? 'good' : 'bad'),
            $this->stat($this->t('Occupancy', 'ऑक्यूपेंसी'), $s['pct'].'%', 'neutral'),
            $this->stat($this->t('General seat learners', 'जनरल सीट छात्र'), (string) $s['general'], 'neutral'),
        ])];
        $this->seatLabel(1); // loads the floor map
        if ($this->seatFloors && $s['total']) {
            // Floor-wise: booked / vacant, and vacant seat numbers as printed on each floor
            $perFloor = [];
            for ($m = 1; $m <= $s['total']; $m++) {
                $f = $this->seatFloors[$m] ?? [$m, $this->t('Other', 'अन्य')];
                $perFloor[$f[1]] ??= ['total' => 0, 'vacant' => []];
                $perFloor[$f[1]]['total']++;
                if (in_array((string) $m, $s['vacantList'], true)) {
                    $perFloor[$f[1]]['vacant'][] = (string) $f[0];
                }
            }
            $blocks[] = $this->table([$this->t('Floor', 'फ्लोर'), $this->t('Seats', 'सीटें'), $this->t('Booked', 'बुक'), $this->t('Vacant', 'खाली')],
                array_map(fn ($name, $f) => [$name, (string) $f['total'], (string) ($f['total'] - count($f['vacant'])), ['text' => (string) count($f['vacant']), 'tone' => count($f['vacant']) ? 'good' : 'bad']], array_keys($perFloor), $perFloor));
            foreach ($perFloor as $name => $f) {
                if ($f['vacant']) {
                    $shown = array_slice($f['vacant'], 0, 40);
                    $blocks[] = $this->chips($this->t('Vacant seats · ', 'खाली सीटें · ').$name, $shown, count($f['vacant']) - count($shown));
                }
            }
        } elseif ($s['vacantList']) {
            $shown = array_slice($s['vacantList'], 0, 60);
            $blocks[] = $this->chips($this->t('Vacant seat numbers', 'खाली सीट नंबर'), $shown, count($s['vacantList']) - count($shown));
        }
        $blocks[] = $this->note($this->t(
            'A seat counts as booked when any active learner holds it, in any shift. A seat can still be free in another shift — check the seat map for shift-wise availability.',
            'कोई भी सक्रिय छात्र किसी भी शिफ्ट में सीट रखता है तो वह बुक गिनी जाती है। दूसरी शिफ्ट में वह सीट खाली हो सकती है — शिफ्ट-वार जानकारी सीट मैप में देखें।'
        ), 'info');

        return $this->reply('seats', 'fa-chair', $this->t('Seats & occupancy', 'सीटें व ऑक्यूपेंसी'), $blocks,
            [$this->link($this->t('Open seat map', 'सीट मैप खोलें'), 'seats'), $this->link($this->t('Occupancy report', 'ऑक्यूपेंसी रिपोर्ट'), 'reports.occupancy')],
            [$this->t('Who is on seat 1?', 'सीट 1 पर कौन है?'), $this->t('How to book a seat?', 'सीट कैसे बुक करें?'), $this->t('Show floors', 'फ्लोर दिखाओ')]);
    }

    private function seatLookup(int $seat): array
    {
        $today = Carbon::today()->toDateString();
        $rows = DB::table('learner_detail as ld')
            ->join('learners as l', 'l.id', '=', 'ld.learner_id')
            ->leftJoin('plan_types as pt', 'pt.id', '=', 'ld.plan_type_id')
            ->where('ld.branch_id', $this->branchId)
            ->whereNull('ld.deleted_at')->whereNull('l.deleted_at')
            ->where('ld.status', 1)
            ->where('ld.seat_no', $seat)
            ->where(fn ($q) => $q->where('ld.plan_end_date', '>=', $today)->orWhere('l.no_expiry', 1))
            ->orderBy('pt.start_time')
            ->get(['l.id', 'l.name', 'pt.name as shift', 'pt.start_time', 'pt.end_time', 'ld.plan_start_date', 'ld.plan_end_date']);

        $total = $this->seatStats()['total'];
        if ($total && $seat > $total) {
            return $this->reply('seat', 'fa-chair', $this->t('Seat '.$seat, 'सीट '.$seat),
                [$this->note($this->t('Your branch has only '.$total.' seats.', 'आपकी ब्रांच में केवल '.$total.' सीटें हैं।'), 'warn')],
                [], [$this->t('Show vacant seats', 'खाली सीटें दिखाओ')]);
        }

        if ($rows->isEmpty()) {
            return $this->reply('seat', 'fa-chair', $this->t('Seat '.$seat.' is vacant', 'सीट '.$seat.' खाली है'),
                [$this->note($this->t('No active learner holds this seat right now. You can book it from the seat map.', 'अभी कोई सक्रिय छात्र इस सीट पर नहीं है। आप इसे सीट मैप से बुक कर सकते हैं।'), 'good')],
                [$this->link($this->t('Open seat map', 'सीट मैप खोलें'), 'seats')],
                [$this->t('How to book a seat?', 'सीट कैसे बुक करें?'), $this->t('Show vacant seats', 'खाली सीटें दिखाओ')]);
        }

        return $this->reply('seat', 'fa-chair', $this->t('Seat '.$seat, 'सीट '.$seat), [
            $this->table([$this->t('Learner', 'छात्र'), $this->t('Shift', 'शिफ्ट'), $this->t('Plan ends', 'प्लान खत्म')],
                $rows->map(fn ($r) => [
                    ['text' => (string) $r->name, 'url' => $this->url('learners.show', $r->id)],
                    trim(($r->shift ?? '').' '.$this->timeRange($r->start_time, $r->end_time)) ?: '—',
                    $r->plan_end_date ? $this->date($r->plan_end_date) : '—',
                ])->all()),
        ], [$this->link($this->t('Open seat map', 'सीट मैप खोलें'), 'seats')],
            [$this->t('How to swap seat?', 'सीट कैसे बदलें?'), $this->t('Show vacant seats', 'खाली सीटें दिखाओ')]);
    }

    private function floors(): array
    {
        $floors = DB::table('floors')->where('branch_id', $this->branchId)->whereNull('deleted_at')
            ->orderBy('floor_no')->get(['name', 'from_seat', 'to_seat', 'total_seats']);

        return $this->reply('floors', 'fa-layer-group', $this->t('Floors', 'फ्लोर (मंजिलें)'), [
            $floors->isNotEmpty()
                ? $this->table([$this->t('Floor', 'फ्लोर'), $this->t('Seats', 'सीटें'), $this->t('Seat range', 'सीट रेंज')],
                    $floors->map(fn ($f) => [(string) $f->name, (string) ($f->total_seats ?? max(0, (int) $f->to_seat - (int) $f->from_seat + 1)), $f->from_seat.' – '.$f->to_seat])->all())
                : $this->note($this->t('No floors are set up yet. All seats are on one floor.', 'अभी कोई फ्लोर सेट नहीं है। सभी सीटें एक ही फ्लोर पर हैं।'), 'info'),
        ], [], [$this->t('How to add a floor?', 'फ्लोर कैसे जोड़ें?'), $this->t('Show vacant seats', 'खाली सीटें दिखाओ')]);
    }

    private function shifts(): array
    {
        $rows = DB::table('plan_types')->where('branch_id', $this->branchId)->whereNull('deleted_at')
            ->orderBy('start_time')->get(['name', 'start_time', 'end_time', 'start_time_2', 'end_time_2', 'slot_hours']);

        return $this->reply('shifts', 'fa-clock', $this->t('Shifts & timings', 'शिफ्ट व समय'), [
            $rows->isNotEmpty()
                ? $this->table([$this->t('Shift', 'शिफ्ट'), $this->t('Timing', 'समय'), $this->t('Hours', 'घंटे')],
                    $rows->map(fn ($r) => [
                        (string) $r->name,
                        trim($this->timeRange($r->start_time, $r->end_time).($r->start_time_2 ? ', '.$this->timeRange($r->start_time_2, $r->end_time_2) : '')) ?: '—',
                        $r->slot_hours ? (string) $r->slot_hours : '—',
                    ])->all())
                : $this->note($this->t('No shift is set up yet.', 'अभी कोई शिफ्ट सेट नहीं है।'), 'warn'),
        ], [], [$this->t('How to add a shift?', 'शिफ्ट कैसे जोड़ें?'), $this->t('How to change plan or shift?', 'प्लान या शिफ्ट कैसे बदलें?')]);
    }

    private function subscription(): array
    {
        $lib = DB::table('libraries')->where('id', $this->libraryId)->first();
        $plan = $lib && ! empty($lib->library_type) ? DB::table('subscriptions')->where('id', $lib->library_type)->value('name') : null;
        $end = $lib->plan_end_date ?? null;
        $left = $end ? Carbon::today()->diffInDays(Carbon::parse($end), false) : null;

        return $this->reply('subscription', 'fa-crown', $this->t('Your Libraro plan', 'आपका Libraro प्लान'), [
            $this->stats([
                $this->stat($this->t('Plan', 'प्लान'), (string) ($plan ?: '—'), 'info'),
                $this->stat($this->t('Valid till', 'वैधता'), $end ? $this->date($end) : '—', $left !== null && $left < 7 ? 'bad' : 'good',
                    $left === null ? null : ($left >= 0 ? $this->t($left.' days left', $left.' दिन बाकी') : $this->t('Expired', 'समाप्त'))),
            ]),
        ], [$this->link($this->t('Renew / upgrade', 'रिन्यू / अपग्रेड'), 'subscriptions.choosePlan')], $this->defaultSuggestions());
    }

    private function learners(): array
    {
        $active = $this->activeLearnerCount();
        $total = DB::table('learners')->where('branch_id', $this->branchId)->whereNull('deleted_at')->count();
        $month = DB::table('learners')->where('branch_id', $this->branchId)->whereNull('deleted_at')
            ->where('created_at', '>=', Carbon::today()->startOfMonth())->count();

        return $this->reply('learners', 'fa-users', $this->t('Learners', 'छात्र'), [
            $this->stats([
                $this->stat($this->t('Active now', 'अभी सक्रिय'), (string) $active, 'good'),
                $this->stat($this->t('All learners', 'कुल छात्र'), (string) $total, 'info'),
                $this->stat($this->t('Joined this month', 'इस महीने जुड़े'), (string) $month, 'neutral'),
                $this->stat($this->t('Expiring in 7 days', '7 दिन में एक्सपायर'), (string) $this->expiringQuery(7)->count(), 'warn'),
            ]),
        ], [$this->link($this->t('Learner report', 'छात्र रिपोर्ट'), 'learner.report')],
            [$this->t('Whose plan expires in next 7 days?', 'अगले 7 दिन में किसका प्लान खत्म होगा?'), $this->t('Recently expired learners', 'हाल ही में एक्सपायर हुए छात्र')]);
    }

    private function planPrices(): array
    {
        $rows = DB::table('plan_prices as pp')
            ->join('plans as p', 'p.id', '=', 'pp.plan_id')
            ->join('plan_types as t', 't.id', '=', 'pp.plan_type_id')
            ->where('pp.branch_id', $this->branchId)
            ->whereNull('pp.deleted_at')->whereNull('p.deleted_at')->whereNull('t.deleted_at')
            ->orderBy('t.start_time')->orderBy('pp.price')
            ->get(['p.name as plan', 't.name as shift', 't.start_time', 't.end_time', 'pp.price']);
        $branch = DB::table('branches')->where('id', $this->branchId)->first(['locker_amount', 'token_money']);

        $blocks = [];
        $blocks[] = $rows->isNotEmpty()
            ? $this->table([$this->t('Shift', 'शिफ्ट'), $this->t('Plan', 'प्लान'), $this->t('Fee', 'फीस')],
                $rows->map(fn ($r) => [
                    trim($r->shift.($this->timeRange($r->start_time, $r->end_time) ? ' · '.$this->timeRange($r->start_time, $r->end_time) : '')),
                    (string) $r->plan,
                    $this->inr((float) $r->price),
                ])->all())
            : $this->note($this->t('No plan prices are set for this branch yet.', 'इस ब्रांच के लिए अभी कोई प्लान कीमत सेट नहीं है।'), 'warn');

        $extra = [];
        if ($branch && (float) $branch->locker_amount > 0) {
            $extra[] = $this->stat($this->t('Locker charge', 'लॉकर शुल्क'), $this->inr((float) $branch->locker_amount), 'neutral');
        }
        if ($branch && (float) $branch->token_money > 0) {
            $extra[] = $this->stat($this->t('Token money', 'टोकन मनी'), $this->inr((float) $branch->token_money), 'neutral');
        }
        if ($extra) {
            $blocks[] = $this->stats($extra);
        }

        return $this->reply('prices', 'fa-tags', $this->t('Plan fees', 'प्लान की फीस'), $blocks, [],
            [$this->t('How to change plan price?', 'प्लान की कीमत कैसे बदलें?'), $this->t('How should I decide fees?', 'फीस कितनी रखें?'), $this->t('Shift wise learners', 'शिफ्ट वाइज छात्र')]);
    }

    private function refunds(): array
    {
        [$from, $to, $label] = $this->period('month');
        $rows = DB::table('learner_transaction_activity as a')
            ->leftJoin('learners as l', 'l.id', '=', 'a.learner_id')
            ->where('a.branch_id', $this->branchId)->whereNull('a.deleted_at')
            ->where('a.dr_cr', 'Dr')->where('a.payment_type', 'REFUND')
            ->whereBetween('a.date', [$from, $to])
            ->orderByDesc('a.date')
            ->get(['a.date', 'a.amount', 'l.id', 'l.name']);

        $blocks = [$this->stats([
            $this->stat($this->t('Refunded', 'रिफंड'), $this->inr((float) $rows->sum('amount')), $rows->count() ? 'warn' : 'good', $label),
            $this->stat($this->t('Refunds', 'रिफंड संख्या'), (string) $rows->count(), 'neutral'),
        ])];
        if ($rows->isNotEmpty()) {
            $blocks[] = $this->table([$this->t('Learner', 'छात्र'), $this->t('Date', 'तारीख'), $this->t('Amount', 'राशि')],
                $rows->take(25)->map(fn ($r) => [['text' => (string) ($r->name ?? '—'), 'url' => $r->id ? $this->url('learners.show', $r->id) : null], $this->date($r->date), $this->inr((float) $r->amount)])->all(),
                max(0, $rows->count() - 25));
        }

        return $this->reply('refunds', 'fa-rotate-left', $this->t('Refunds', 'रिफंड').' · '.$label, $blocks, [],
            [$this->t('Profit this month', 'इस महीने का मुनाफा'), $this->t('How to delete seat with refund?', 'रिफंड के साथ सीट कैसे डिलीट करें?')]);
    }

    private function renewals(): array
    {
        [$from, $to, $label] = $this->period('month');
        $rows = DB::table('learner_transaction_activity as a')
            ->leftJoin('learners as l', 'l.id', '=', 'a.learner_id')
            ->where('a.branch_id', $this->branchId)->whereNull('a.deleted_at')
            ->where('a.dr_cr', 'Cr')->whereIn('a.payment_type', ['RENEW', 'REACTIVE', 'UPGRADE'])
            ->whereBetween('a.date', [$from, $to])
            ->orderByDesc('a.date')
            ->get(['a.date', 'a.amount', 'a.payment_type', 'l.id', 'l.name']);
        $type = fn ($t) => ['RENEW' => $this->t('Renew', 'रिन्यू'), 'REACTIVE' => $this->t('Reactivate', 'दोबारा चालू'), 'UPGRADE' => $this->t('Upgrade', 'अपग्रेड')][$t] ?? $t;

        $blocks = [$this->stats([
            $this->stat($this->t('Renewals', 'रिन्यूअल'), (string) $rows->where('payment_type', 'RENEW')->count(), 'good', $label),
            $this->stat($this->t('Reactivated', 'दोबारा चालू'), (string) $rows->where('payment_type', 'REACTIVE')->count(), 'info'),
            $this->stat($this->t('Upgrades', 'अपग्रेड'), (string) $rows->where('payment_type', 'UPGRADE')->count(), 'neutral'),
            $this->stat($this->t('Amount', 'राशि'), $this->inr((float) $rows->sum('amount')), 'good'),
        ])];
        $blocks[] = $rows->isNotEmpty()
            ? $this->table([$this->t('Learner', 'छात्र'), $this->t('Type', 'प्रकार'), $this->t('Date', 'तारीख'), $this->t('Amount', 'राशि')],
                $rows->take(25)->map(fn ($r) => [['text' => (string) ($r->name ?? '—'), 'url' => $r->id ? $this->url('learners.show', $r->id) : null], $type($r->payment_type), $this->date($r->date), $this->inr((float) $r->amount)])->all(),
                max(0, $rows->count() - 25))
            : $this->note($this->t('No renewal in this period.', 'इस अवधि में कोई रिन्यूअल नहीं हुआ।'), 'info');

        return $this->reply('renewals', 'fa-arrows-rotate', $this->t('Renewals', 'रिन्यूअल').' · '.$label, $blocks, [],
            [$this->t('Whose plan expires in next 7 days?', 'अगले 7 दिन में किसका प्लान खत्म होगा?'), $this->t('How to keep learners renewing?', 'रिन्यूअल कैसे बढ़ाएं?')]);
    }

    private function compareMonths(): array
    {
        $t = Carbon::today();
        $thisFrom = $t->copy()->startOfMonth();
        $lastFrom = $t->copy()->subMonthNoOverflow()->startOfMonth();
        $lastTo = $lastFrom->copy()->addDays(min($t->day, $lastFrom->daysInMonth) - 1);

        $metric = function (string $from, string $to): array {
            $exp = (float) DB::table('learner_transaction_activity')->where('branch_id', $this->branchId)->whereNull('deleted_at')
                ->where('dr_cr', 'Dr')->where('payment_type', 'EXPENSE')->whereBetween('date', [$from, $to])->sum('amount');
            $col = $this->collectionTotal($from, $to)['total'];

            return [
                'collection' => $col,
                'admissions' => DB::table('learners')->where('branch_id', $this->branchId)->whereNull('deleted_at')
                    ->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59'])->count(),
                'renewals' => DB::table('learner_transaction_activity')->where('branch_id', $this->branchId)->whereNull('deleted_at')
                    ->where('dr_cr', 'Cr')->where('payment_type', 'RENEW')->whereBetween('date', [$from, $to])->count(),
                'expenses' => $exp,
                'profit' => $col - $exp,
            ];
        };
        $now = $metric($thisFrom->toDateString(), $t->toDateString());
        $before = $metric($lastFrom->toDateString(), $lastTo->toDateString());

        $change = function ($a, $b, bool $lowerIsBetter = false) {
            if ((float) $b == 0.0) {
                return ['text' => (float) $a == 0.0 ? '—' : $this->t('new', 'नया'), 'tone' => null];
            }
            $pct = round(($a - $b) / abs($b) * 100);
            $good = $lowerIsBetter ? $pct <= 0 : $pct >= 0;

            return ['text' => ($pct > 0 ? '▲ ' : ($pct < 0 ? '▼ ' : '')).abs($pct).'%', 'tone' => $pct == 0 ? null : ($good ? 'good' : 'bad')];
        };

        $rows = [
            [$this->t('Collection', 'कलेक्शन'), $this->inr($now['collection']), $this->inr($before['collection']), $change($now['collection'], $before['collection'])],
            [$this->t('New admissions', 'नए एडमिशन'), (string) $now['admissions'], (string) $before['admissions'], $change($now['admissions'], $before['admissions'])],
            [$this->t('Renewals', 'रिन्यूअल'), (string) $now['renewals'], (string) $before['renewals'], $change($now['renewals'], $before['renewals'])],
            [$this->t('Expenses', 'खर्च'), $this->inr($now['expenses']), $this->inr($before['expenses']), $change($now['expenses'], $before['expenses'], true)],
            [$this->t('Profit', 'मुनाफा'), $this->inr($now['profit']), $this->inr($before['profit']), $change($now['profit'], $before['profit'])],
        ];

        return $this->reply('compare', 'fa-chart-column', $this->t('This month vs last month', 'इस महीने बनाम पिछला महीना'), [
            ($now['collection'] + $before['collection']) > 0 ? $this->bars($this->t('Collection', 'कलेक्शन'), [
                [$lastFrom->format('M').' 1–'.$lastTo->day, $before['collection'], $this->inr($before['collection'])],
                [$t->format('M').' 1–'.$t->day, $now['collection'], $this->inr($now['collection'])],
            ]) : null,
            $this->table([$this->t('Metric', 'माप'), $t->format('M').' 1–'.$t->day, $lastFrom->format('M').' 1–'.$lastTo->day, $this->t('Change', 'बदलाव')], $rows),
            $this->note($this->t('Both months are compared for the same number of days, so the numbers are fair.', 'दोनों महीनों की तुलना बराबर दिनों के लिए की गई है, ताकि आंकड़े सही रहें।'), 'info'),
        ], [$this->link($this->t('Growth report', 'ग्रोथ रिपोर्ट'), 'reports.growth')],
            [$this->t('How to get more admissions?', 'एडमिशन कैसे बढ़ाएं?'), $this->t('This month expenses', 'इस महीने का खर्च')]);
    }

    private function shiftLearners(): array
    {
        $today = Carbon::today()->toDateString();
        $rows = DB::table('learner_detail as ld')
            ->join('learners as l', 'l.id', '=', 'ld.learner_id')
            ->leftJoin('plan_types as pt', 'pt.id', '=', 'ld.plan_type_id')
            ->where('ld.branch_id', $this->branchId)
            ->whereNull('ld.deleted_at')->whereNull('l.deleted_at')
            ->where('ld.status', 1)
            ->where('ld.plan_start_date', '<=', $today)
            ->where(fn ($q) => $q->where('ld.plan_end_date', '>=', $today)->orWhere('l.no_expiry', 1))
            ->groupBy('pt.id', 'pt.name', 'pt.start_time', 'pt.end_time')
            ->orderBy('pt.start_time')
            ->selectRaw('pt.name, pt.start_time, pt.end_time, COUNT(DISTINCT ld.learner_id) as n')
            ->get();

        return $this->reply('shiftLearners', 'fa-users-rectangle', $this->t('Active learners by shift', 'शिफ्ट के हिसाब से सक्रिय छात्र'), [
            $this->bars($this->t('Learners per shift', 'हर शिफ्ट में छात्र'), $rows->map(fn ($r) => [(string) ($r->name ?: '—'), (int) $r->n, (string) $r->n])->all()),
            $rows->isNotEmpty()
                ? $this->table([$this->t('Shift', 'शिफ्ट'), $this->t('Timing', 'समय'), $this->t('Learners', 'छात्र')],
                    $rows->map(fn ($r) => [(string) ($r->name ?: $this->t('No shift', 'बिना शिफ्ट')), $this->timeRange($r->start_time, $r->end_time) ?: '—', (string) $r->n])->all())
                : $this->note($this->t('No active learner right now.', 'अभी कोई सक्रिय छात्र नहीं है।'), 'info'),
        ], [], [$this->t('How to fill empty seats?', 'खाली सीटें कैसे भरें?'), $this->t('Plan fees', 'प्लान की फीस')]);
    }

    private function frozenLearners(): array
    {
        $rows = DB::table('learners')->where('branch_id', $this->branchId)->whereNull('deleted_at')
            ->where('frozen_status', 1)->orderBy('name')->get(['id', 'name', 'seat_no']);

        return $this->reply('frozen', 'fa-snowflake', $this->t('Frozen plans', 'फ्रीज़ किए गए प्लान'), [
            $this->stats([$this->stat($this->t('Frozen learners', 'फ्रीज़ छात्र'), (string) $rows->count(), 'info')]),
            $rows->isNotEmpty()
                ? $this->table([$this->t('Name', 'नाम'), $this->t('Seat', 'सीट')], $rows->take(30)->map(fn ($r) => [['text' => (string) $r->name, 'url' => $this->url('learners.show', $r->id)], $this->seatLabel($r->seat_no)])->all(), max(0, $rows->count() - 30))
                : $this->note($this->t('No plan is frozen right now.', 'अभी कोई प्लान फ्रीज़ नहीं है।'), 'good'),
        ], [], [$this->t('How to freeze a plan?', 'प्लान फ्रीज कैसे करें?')]);
    }

    private function lockers(): array
    {
        $rows = DB::table('learners')->where('branch_id', $this->branchId)->whereNull('deleted_at')->where('status', 1)
            ->whereNotNull('locker_no')->where('locker_no', '!=', '')->where('locker_no', '!=', '0')
            ->orderByRaw('CAST(locker_no AS UNSIGNED)')->get(['id', 'name', 'locker_no', 'seat_no']);
        $charge = (float) DB::table('branches')->where('id', $this->branchId)->value('locker_amount');

        $stats = [$this->stat($this->t('Lockers in use', 'उपयोग में लॉकर'), (string) $rows->count(), 'info')];
        if ($charge > 0) {
            $stats[] = $this->stat($this->t('Locker charge', 'लॉकर शुल्क'), $this->inr($charge), 'neutral');
        }

        return $this->reply('lockers', 'fa-lock', $this->t('Lockers', 'लॉकर'), [
            $this->stats($stats),
            $rows->isNotEmpty()
                ? $this->table([$this->t('Locker', 'लॉकर'), $this->t('Learner', 'छात्र'), $this->t('Seat', 'सीट')],
                    $rows->take(40)->map(fn ($r) => [(string) $r->locker_no, ['text' => (string) $r->name, 'url' => $this->url('learners.show', $r->id)], $this->seatLabel($r->seat_no)])->all(), max(0, $rows->count() - 40))
                : $this->note($this->t('No locker is assigned right now.', 'अभी कोई लॉकर किसी को नहीं दिया गया है।'), 'info'),
        ], [], [$this->t('How to assign a locker?', 'लॉकर कैसे अलॉट करें?')]);
    }

    private function enquiries(): array
    {
        $from = Carbon::today()->subDays(30);
        $enq = DB::table('library_enquiry')->where('branch_id', $this->branchId)->whereNull('deleted_at')
            ->where('created_at', '>=', $from)->orderByDesc('created_at')->get(['name', 'shift_time', 'enquiry', 'created_at']);
        $pending = DB::table('bookings')->where('branch_id', $this->branchId)->where('status', 'pending')
            ->selectRaw('type, COUNT(*) as n')->groupBy('type')->pluck('n', 'type');
        $typeLabel = ['qr_seat_book' => $this->t('Online seat bookings', 'ऑनलाइन सीट बुकिंग'), 'qr_renew' => $this->t('Online renewals', 'ऑनलाइन रिन्यूअल'), 'demo-bookings' => $this->t('Demo bookings', 'डेमो बुकिंग')];

        $stats = [$this->stat($this->t('Enquiries (30 days)', 'पूछताछ (30 दिन)'), (string) $enq->count(), 'info')];
        foreach ($pending as $type => $n) {
            $stats[] = $this->stat(($typeLabel[$type] ?? ucwords(str_replace(['_', '-'], ' ', (string) $type))).' · '.$this->t('pending', 'बाकी'), (string) $n, 'warn');
        }

        $blocks = [$this->stats($stats)];
        if ($enq->isNotEmpty()) {
            $blocks[] = $this->table([$this->t('Name', 'नाम'), $this->t('Shift', 'शिफ्ट'), $this->t('Date', 'तारीख')],
                $enq->take(15)->map(fn ($e) => [(string) $e->name, (string) ($e->shift_time ?: '—'), $this->date($e->created_at)])->all(), max(0, $enq->count() - 15));
        }

        return $this->reply('enquiries', 'fa-inbox', $this->t('Enquiries & online bookings', 'पूछताछ व ऑनलाइन बुकिंग'), $blocks,
            [$this->link($this->t('Open enquiries', 'पूछताछ देखें'), 'library.enquiry')],
            [$this->t('How to get more admissions?', 'एडमिशन कैसे बढ़ाएं?'), $this->t('Show vacant seats', 'खाली सीटें दिखाओ')]);
    }

    private function branches(): array
    {
        $today = Carbon::today()->toDateString();
        $rows = DB::table('branches')->where('library_id', $this->libraryId)->whereNull('deleted_at')->orderBy('id')->get(['id', 'name', 'display_name']);
        $active = DB::table('learner_detail as ld')->join('learners as l', 'l.id', '=', 'ld.learner_id')
            ->whereIn('ld.branch_id', $rows->pluck('id'))->whereNull('ld.deleted_at')->whereNull('l.deleted_at')
            ->where('ld.status', 1)->where('ld.plan_start_date', '<=', $today)
            ->where(fn ($q) => $q->where('ld.plan_end_date', '>=', $today)->orWhere('l.no_expiry', 1))
            ->groupBy('ld.branch_id')->selectRaw('ld.branch_id, COUNT(DISTINCT ld.learner_id) as n')->pluck('n', 'branch_id');
        $seats = DB::table('hour')->whereIn('branch_id', $rows->pluck('id'))->pluck('seats', 'branch_id');

        return $this->reply('branches', 'fa-code-branch', $this->t('Your branches', 'आपकी ब्रांच'), [
            $this->table([$this->t('Branch', 'ब्रांच'), $this->t('Seats', 'सीटें'), $this->t('Active learners', 'सक्रिय छात्र')],
                $rows->map(fn ($b) => [
                    ['text' => (string) ($b->display_name ?: $b->name).((int) $b->id === $this->branchId ? $this->t(' (current)', ' (अभी चुनी)') : ''), 'tone' => (int) $b->id === $this->branchId ? 'good' : null],
                    (string) ($seats[$b->id] ?? 0),
                    (string) ($active[$b->id] ?? 0),
                ])->all()),
            $this->note($this->t('My other answers use the branch selected at the top of the panel.', 'मेरे बाकी जवाब पैनल के ऊपर चुनी गई ब्रांच के होते हैं।'), 'info'),
        ], [], [$this->t('How to switch branch?', 'ब्रांच कैसे बदलें?'), $this->t('How to add a branch?', 'ब्रांच कैसे जोड़ें?')]);
    }

    private function staffList(): array
    {
        $rows = DB::table('library_users as u')->leftJoin('branches as b', 'b.id', '=', 'u.branch_id')
            ->where('u.library_id', $this->libraryId)->whereNull('u.deleted_at')->orderBy('u.name')
            ->get(['u.name', 'u.status', 'b.name as branch', 'b.display_name']);

        return $this->reply('staff', 'fa-id-badge', $this->t('Staff accounts', 'स्टाफ अकाउंट'), [
            $this->stats([$this->stat($this->t('Staff', 'स्टाफ'), (string) $rows->count(), 'info'), $this->stat($this->t('Active', 'सक्रिय'), (string) $rows->where('status', 1)->count(), 'good')]),
            $rows->isNotEmpty()
                ? $this->table([$this->t('Name', 'नाम'), $this->t('Branch', 'ब्रांच'), $this->t('Status', 'स्थिति')],
                    $rows->map(fn ($u) => [(string) $u->name, (string) ($u->display_name ?: $u->branch ?: '—'), (int) $u->status === 1 ? ['text' => $this->t('Active', 'सक्रिय'), 'tone' => 'good'] : ['text' => $this->t('Inactive', 'बंद'), 'tone' => 'bad']])->all())
                : $this->note($this->t('No staff account yet.', 'अभी कोई स्टाफ अकाउंट नहीं है।'), 'info'),
        ], [$this->link($this->t('Manage staff', 'स्टाफ मैनेज करें'), 'library-users.index')], [$this->t('How to manage staff?', 'स्टाफ कैसे संभालें?')]);
    }

    private function feedbackSummary(): array
    {
        $rows = DB::table('learner_feedback')->where('library_id', $this->libraryId)->whereNull('deleted_at')->orderByDesc('created_at')
            ->get(['name', 'rating', 'comments', 'created_at']);
        $avg = $rows->count() ? round((float) $rows->avg(fn ($r) => (float) $r->rating), 1) : 0;

        $blocks = [$this->stats([
            $this->stat($this->t('Feedback received', 'मिले फीडबैक'), (string) $rows->count(), 'info'),
            $this->stat($this->t('Average rating', 'औसत रेटिंग'), $rows->count() ? $avg.' / 5' : '—', $avg >= 4 ? 'good' : ($avg >= 3 ? 'warn' : 'neutral')),
        ])];
        if ($rows->isNotEmpty()) {
            $blocks[] = $this->table([$this->t('Learner', 'छात्र'), $this->t('Rating', 'रेटिंग'), $this->t('Comment', 'टिप्पणी')],
                $rows->take(8)->map(fn ($r) => [(string) $r->name, (string) $r->rating, mb_strimwidth((string) ($r->comments ?: '—'), 0, 80, '…')])->all());
        }

        return $this->reply('feedback', 'fa-star', $this->t('Learner feedback', 'छात्रों का फीडबैक'), $blocks,
            [$this->link($this->t('All feedback', 'सभी फीडबैक'), 'library.learner.feedback')],
            [$this->t('How to keep learners renewing?', 'रिन्यूअल कैसे बढ़ाएं?')]);
    }

    private function libraryProfile(): array
    {
        $b = DB::table('branches')->where('id', $this->branchId)->first();
        if (! $b) {
            return $this->notUnderstood();
        }
        $rows = array_filter([
            [$this->t('Name', 'नाम'), (string) ($b->display_name ?: $b->name)],
            [$this->t('Address', 'पता'), trim((string) $b->library_address.($b->library_zip ? ' - '.$b->library_zip : ''))],
            [$this->t('Mobile', 'मोबाइल'), (string) $b->mobile],
            [$this->t('Email', 'ईमेल'), (string) $b->email],
            [$this->t('UPI ID', 'UPI ID'), (string) $b->upi_id],
            [$this->t('Working days', 'कार्य दिवस'), is_string($b->working_days) ? implode(', ', (array) (json_decode($b->working_days, true) ?: [$b->working_days])) : ''],
            [$this->t('Extension days after expiry', 'एक्सपायरी के बाद extension दिन'), (string) $b->extend_days],
            [$this->t('Locker charge', 'लॉकर शुल्क'), (float) $b->locker_amount > 0 ? $this->inr((float) $b->locker_amount) : ''],
        ], fn ($r) => trim((string) $r[1]) !== '');

        return $this->reply('profile', 'fa-building', $this->t('Branch details', 'ब्रांच की जानकारी'), [
            $this->table([$this->t('Detail', 'जानकारी'), $this->t('Value', 'वैल्यू')], array_values($rows)),
        ], [$this->link($this->t('Edit profile', 'प्रोफाइल बदलें'), 'profile')], [$this->t('Plan fees', 'प्लान की फीस'), $this->t('Shift timings', 'शिफ्ट का समय बताओ')]);
    }

    private function learnerSearch(string $term, bool $silent = false): ?array
    {
        $rows = DB::table('learners as l')
            ->where('l.branch_id', $this->branchId)->whereNull('l.deleted_at')
            ->where('l.name', 'like', '%'.addcslashes($term, '%_\\').'%')
            ->orderByDesc('l.status')->orderBy('l.name')
            ->limit(10)
            ->get(['l.id', 'l.name', 'l.status']);

        if ($rows->isEmpty()) {
            if ($silent) {
                return null;
            }

            return $this->reply('search', 'fa-magnifying-glass', $this->t('No learner found', 'कोई छात्र नहीं मिला'),
                [$this->note($this->t('No learner named “'.$term.'” in this branch.', 'इस ब्रांच में “'.$term.'” नाम का कोई छात्र नहीं है।'), 'warn')],
                [], $this->defaultSuggestions());
        }

        $ids = $rows->pluck('id')->all();
        $details = DB::table('learner_detail as ld')
            ->leftJoin('plan_types as pt', 'pt.id', '=', 'ld.plan_type_id')
            ->whereIn('ld.learner_id', $ids)->whereNull('ld.deleted_at')
            ->orderByDesc('ld.plan_end_date')
            ->get(['ld.learner_id', 'ld.seat_no', 'ld.plan_end_date', 'pt.name as shift'])
            ->unique('learner_id')->keyBy('learner_id');
        $dues = DB::table('learner_transactions')->whereIn('learner_id', $ids)->whereNull('deleted_at')
            ->groupBy('learner_id')->selectRaw('learner_id, SUM(pending_amount) as due')->pluck('due', 'learner_id');

        $today = Carbon::today()->toDateString();
        if ($rows->count() === 1) {
            return $this->learnerCard((int) $rows->first()->id);
        }
        $table = $rows->map(function ($r) use ($details, $dues, $today) {
            $d = $details->get($r->id);
            $ended = $d && $d->plan_end_date && $d->plan_end_date < $today;
            $due = (float) ($dues[$r->id] ?? 0);

            return [
                ['text' => (string) $r->name, 'url' => $this->url('learners.show', $r->id)],
                $d ? $this->seatLabel($d->seat_no) : '—',
                $d ? (string) ($d->shift ?? '—') : '—',
                ['text' => $d && $d->plan_end_date ? $this->date($d->plan_end_date) : '—', 'tone' => $ended ? 'bad' : null],
                ['text' => $due > 0 ? $this->inr($due) : $this->t('Paid', 'जमा'), 'tone' => $due > 0 ? 'bad' : 'good'],
            ];
        })->all();

        return $this->reply('search', 'fa-magnifying-glass', $this->t('Learners matching “'.$term.'”', '“'.$term.'” से मिलते छात्र'), [
            $this->table([$this->t('Name', 'नाम'), $this->t('Seat', 'सीट'), $this->t('Shift', 'शिफ्ट'), $this->t('Plan ends', 'प्लान खत्म'), $this->t('Dues', 'बकाया')], $table),
        ], [], [$this->t('How to edit learner profile?', 'छात्र की प्रोफाइल कैसे एडिट करें?'), $this->t('How to renew a seat?', 'सीट रिन्यू कैसे करें?')]);
    }

    /** Everything about one learner in one card. */
    private function learnerCard(int $id): ?array
    {
        $l = DB::table('learners')->where('id', $id)->where('branch_id', $this->branchId)->whereNull('deleted_at')->first();
        if (! $l) {
            return $this->notUnderstood();
        }
        $this->newContext['learner_id'] = $id;
        $detail = DB::table('learner_detail as ld')
            ->leftJoin('plan_types as pt', 'pt.id', '=', 'ld.plan_type_id')
            ->where('ld.learner_id', $id)->whereNull('ld.deleted_at')
            ->orderByDesc('ld.plan_end_date')
            ->first(['ld.seat_no', 'ld.plan_end_date', 'pt.name as shift']);
        $dueRow = DB::table('learner_transactions')->where('learner_id', $id)->whereNull('deleted_at')->where('pending_amount', '>', 0)
            ->orderBy('due_date')->first(['id', 'due_date']);
        $due = (float) DB::table('learner_transactions')->where('learner_id', $id)->whereNull('deleted_at')->sum('pending_amount');
        $today = Carbon::today();
        $end = $detail && $detail->plan_end_date ? Carbon::parse($detail->plan_end_date) : null;
        $left = $end ? $today->diffInDays($end, false) : null;

        $lastPunch = DB::table('learner_attendance_logs')->where('learner_id', $id)->max('punch_datetime')
            ?: DB::table('attendances')->where('learner_id', $id)->where('attendance', 1)->max('date');

        $mobile = '';
        try {
            $mobile = function_exists('display_learner_mobile') ? display_learner_mobile(decryptData($l->mobile)) : '';
        } catch (\Throwable $e) {
        }

        $planTone = $left === null ? 'neutral' : ($left < 0 ? 'bad' : ($left <= 5 ? 'warn' : 'good'));
        $planSub = $left === null ? null : ($left < 0 ? $this->t('Expired '.abs($left).' days ago', abs($left).' दिन पहले खत्म') : ($left === 0 ? $this->t('Ends today', 'आज खत्म') : $this->t($left.' days left', $left.' दिन बाकी')));
        if ((int) ($l->no_expiry ?? 0) === 1) {
            [$planTone, $planSub] = ['good', $this->t('No expiry', 'कभी खत्म नहीं')];
        }

        $stats = [
            $this->stat($this->t('Seat', 'सीट'), $detail ? $this->seatLabel($detail->seat_no) : '—', 'info', $detail ? (string) ($detail->shift ?? '') : null),
            $this->stat($this->t('Plan ends', 'प्लान खत्म'), $end ? $this->date($end) : '—', $planTone, $planSub),
            $this->stat($this->t('Dues', 'बकाया'), $due > 0 ? $this->inr($due) : $this->t('Paid', 'जमा'), $due > 0 ? 'bad' : 'good'),
            $this->stat($this->t('Last attendance', 'आखिरी हाजिरी'), $lastPunch ? $this->date($lastPunch) : '—', 'neutral'),
        ];
        if (! empty($l->locker_no)) {
            $stats[] = $this->stat($this->t('Locker', 'लॉकर'), (string) $l->locker_no, 'neutral');
        }
        if ((int) ($l->frozen_status ?? 0) === 1) {
            $stats[] = $this->stat($this->t('Status', 'स्थिति'), $this->t('Frozen', 'फ्रीज़'), 'warn');
        }
        if ($mobile) {
            $stats[] = $this->stat($this->t('Mobile', 'मोबाइल'), $mobile, 'neutral');
        }

        $next = [];
        if ($left !== null && $left <= 5) {
            $next[] = $this->t('How to renew a seat?', 'सीट रिन्यू कैसे करें?');
        }
        if ($due > 0) {
            $next[] = $this->t('How to send WhatsApp reminder?', 'व्हाट्सएप रिमाइंडर कैसे भेजें?');
        }
        $next[] = $this->t('How to generate ID card?', 'ID कार्ड कैसे बनाएं?');

        // One-tap actions on this learner (each also checks the staff permission)
        $actions = [];
        if ($due > 0) {
            $actions[] = $this->waAction($l, 'due', ['amount' => $due, 'date' => $dueRow->due_date ?? null]);
            if ($dueRow && $this->can('Pending Payment')) {
                $actions[] = $this->linkAction($this->t('Collect dues', 'बकाया लें'), 'learner.pending.payment', ['id' => $dueRow->id], 'fa-indian-rupee-sign');
            }
        } elseif ($left !== null && $left <= 5 && (int) ($l->no_expiry ?? 0) !== 1) {
            $actions[] = $this->waAction($l, $left < 0 ? 'expired' : 'expiring', ['date' => $detail->plan_end_date ?? null]);
        }
        if ($left !== null && $left <= 5 && $this->can('Renew Seat')) {
            $actions[] = $this->linkAction($this->t('Renew', 'रिन्यू करें'), 'learner.renew.plan', ['id' => $id], 'fa-arrows-rotate');
        }
        $blocks = [$this->stats($stats)];
        if ($actions = array_values(array_filter($actions))) {
            $blocks[] = ['type' => 'buttons', 'items' => $actions];
        }

        return $this->reply('learner', 'fa-user', (string) $l->name, $blocks,
            [$this->link($this->t('Open profile', 'प्रोफाइल खोलें'), 'learners.show', $id), $this->link($this->t('Attendance', 'हाजिरी'), 'attendance.summary', $id)],
            $next);
    }

    /*
    |--------------------------------------------------------------------------
    | How-to guides (from the `how-to-use` table)
    |--------------------------------------------------------------------------
    */

    /**
     * Finds the best answer among three sources — the `how-to-use` guides, the `guidelines` FAQ
     * and the built-in library tips — and returns it with its match score.
     *
     * @return array{score: float, reply: array}|null
     */
    private function knowledge(): ?array
    {
        $candidates = array_merge($this->howToCandidates(), $this->guidelineCandidates(), $this->kbCandidates());
        $candidates = array_values(array_filter($candidates, fn ($c) => $c['score'] >= 2 && ($c['en'] || $c['hi'])));
        if (! $candidates) {
            return null;
        }
        usort($candidates, fn ($a, $b) => $b['score'] <=> $a['score']);
        $best = $candidates[0];

        $related = [];
        foreach (array_slice($candidates, 1) as $c) {
            $q = $this->lang === 'hi' ? $c['ask_hi'] : $c['ask_en'];
            if ($q && ! in_array($q, $related, true) && $c['title_en'] !== $best['title_en']) {
                $related[] = $q;
            }
            if (count($related) === 3) {
                break;
            }
        }

        $links = array_map(fn ($l) => $this->link($this->t($l[1], $l[2]), $l[0]), $best['links']);
        if ($best['source'] === 'howto') {
            $links[] = $this->link($this->t('Watch video tutorials', 'वीडियो ट्यूटोरियल देखें'), 'library.video-training');
        }

        $icon = ['howto' => 'fa-book-open', 'faq' => 'fa-circle-question', 'kb' => 'fa-lightbulb'][$best['source']];

        return [
            'score' => (float) $best['score'],
            'reply' => $this->reply($best['source'] === 'kb' ? 'tips' : 'guide', $icon,
                $this->lang === 'hi' ? ($best['title_hi'] ?: $best['title_en']) : ($best['title_en'] ?: $best['title_hi']),
                [['type' => 'guide', 'en' => $best['en'], 'hi' => $best['hi']]],
                $links, $related ?: $this->defaultSuggestions()),
        ];
    }

    private function phraseScore(array $phrases): int
    {
        $score = 0;
        foreach ($phrases as $p) {
            $n = trim($this->normalise($p));
            if ($n === '') {
                continue;
            }
            $words = explode(' ', $n);
            if ($this->contains($n)) {
                $score += 2 * count($words);
            } elseif (count($words) > 1 && count(array_filter($words, fn ($w) => $this->contains($w))) === count($words)) {
                // all words present but not side by side ("सीट कैसे बुक करें")
                $score += count($words);
            }
        }

        return $score;
    }

    private function howToCandidates(): array
    {
        if (! Schema::hasTable('how-to-use')) {
            return [];
        }
        $out = [];
        // "how to / kaise" asks for steps — prefer the step-by-step guides over FAQs
        $bonus = $this->howBonus();
        foreach (DB::table('how-to-use')->get() as $op) {
            $name = (string) $op->operation_name;
            $score = 0;
            foreach (self::GUIDE_SYNONYMS as $pattern => $syns) {
                if (preg_match($pattern, $name)) {
                    $score += $this->phraseScore($syns);
                }
            }
            $clean = trim(preg_replace('/^(operation|masters|utility|premium operation|analytics)\s*:\s*/i', '', $name));
            $clean = str_ireplace(['Saet', 'Locaker', 'Reactive', 'Leaner'], ['Seat', 'Locker', 'Reactivate', 'Learner'], $clean);
            foreach (preg_split('/[^a-z]+/', strtolower($clean)) as $tok) {
                // "libraro" is in many titles ("What are masters in Libraro?") and says nothing about the topic
                if (strlen($tok) > 3 && ! in_array($tok, ['with', 'without', 'learner', 'option', 'library', 'libraro', 'view', 'seat', 'plan', 'what', 'your', 'from'], true) && $this->contains($tok)) {
                    $score += 1;
                }
            }
            $out[] = [
                'source' => 'howto', 'score' => $score ? $score + $bonus : 0, 'links' => [],
                'title_en' => $clean, 'title_hi' => $clean,
                'ask_en' => 'How to: '.$clean, 'ask_hi' => $clean.' कैसे करें?',
                'en' => $this->steps((string) $op->usage_english), 'hi' => $this->steps((string) $op->usage_hindi),
            ];
        }

        return $out;
    }

    private function guidelineCandidates(): array
    {
        if (! Schema::hasTable('guidelines')) {
            return [];
        }
        $stop = ['how', 'the', 'can', 'and', 'for', 'you', 'why', 'was', 'are', 'its', 'not', 'see', 'get', 'use', 'all', 'any', 'has', 'who', 'did', 'now', 'out', 'day', 'one', 'what', 'when', 'where', 'which', 'does', 'that', 'this', 'with', 'from', 'student', 'students', 'learner', 'there', 'about', 'many', 'much', 'plan', 'change', 'option', 'can\'t', 'cant', 'everything', 'particular',
            'क्या', 'कैसे', 'कहाँ', 'कहां', 'स्टूडेंट', 'सकते', 'हैं', 'नहीं', 'रहा', 'करें', 'मिलेगा', 'होता', 'लिखा', 'देख'];
        $out = [];
        foreach (DB::table('guidelines')->get() as $g) {
            $score = $this->phraseScore([(string) $g->guideline_type]);
            $overlap = 0;
            foreach ([(string) $g->question, (string) $g->hindi_question] as $text) {
                foreach (array_unique(explode(' ', trim($this->normalise($text)))) as $w) {
                    if (mb_strlen($w) >= 3 && ! in_array($w, $stop, true) && $this->contains($w)) {
                        $overlap++;
                    }
                }
            }
            $score += $overlap + ($overlap >= 2 ? 1 : 0);
            $en = trim(strip_tags((string) $g->answer));
            $hi = trim(strip_tags((string) ($g->hindi_answer ?? '')));
            $out[] = [
                'source' => 'faq', 'score' => $score, 'links' => [],
                'title_en' => (string) $g->question, 'title_hi' => (string) ($g->hindi_question ?: $g->question),
                'ask_en' => (string) $g->question, 'ask_hi' => (string) ($g->hindi_question ?: $g->question),
                'en' => $en !== '' ? $this->steps($en) : [], 'hi' => $hi !== '' ? $this->steps($hi) : [],
            ];
        }

        return $out;
    }

    /** "how to / kaise" asks for steps — step-by-step guides and tips rank above FAQs. */
    private function howBonus(): int
    {
        return preg_match('/ how (to|do|can|should) | kaise | kese | कैसे | steps /u', $this->q) ? 3 : 0;
    }

    /**
     * Tips / FAQ entries: the admin-editable `ai_knowledge_entries` table, or the built-in list
     * when the table is not there yet.
     */
    private ?array $kbCache = null;

    private function kbEntries(): array
    {
        if ($this->kbCache !== null) {
            return $this->kbCache;
        }
        try {
            if (Schema::hasTable('ai_knowledge_entries')) {
                $split = fn ($t) => array_values(array_filter(array_map('trim', preg_split('/\r?\n/', (string) $t))));

                return $this->kbCache = DB::table('ai_knowledge_entries')->where('status', 1)->orderBy('id')->get()
                    ->map(fn ($e) => [
                        'keys' => $split($e->keys),
                        'en' => [$e->title_en, $split($e->steps_en)],
                        'hi' => [$e->title_hi, $split($e->steps_hi)],
                        'links' => json_decode((string) $e->links, true) ?: [],
                    ])->all();
            }
        } catch (\Throwable $e) {
        }

        return $this->kbCache = Ai\LibraroKnowledgeBase::entries();
    }

    private function kbCandidates(): array
    {
        $out = [];
        foreach ($this->kbEntries() as $e) {
            $out[] = [
                'source' => 'kb', 'score' => ($k = $this->phraseScore($e['keys'])) ? $k + $this->howBonus() : 0, 'links' => $e['links'] ?? [],
                'title_en' => $e['en'][0], 'title_hi' => $e['hi'][0],
                'ask_en' => $e['en'][0], 'ask_hi' => $e['hi'][0],
                'en' => $e['en'][1], 'hi' => $e['hi'][1],
            ];
        }

        return $out;
    }

    /** Splits a guide text into readable steps. */
    private function steps(string $text): array
    {
        $text = trim(str_replace("\r", '', $text));
        if ($text === '') {
            return [];
        }
        $parts = preg_split('/\n+|(?<=[.।!?])\s+(?=[A-Z0-9\x{0900}-\x{097F}])/u', $text) ?: [$text];
        $parts = array_values(array_filter(array_map(fn ($p) => trim(preg_replace('/^\s*(\d+[.)]|[-•*])\s*/u', '', $p)), $parts), fn ($p) => mb_strlen($p) > 1));

        return $parts;
    }

    /*
    |--------------------------------------------------------------------------
    | Fallbacks
    |--------------------------------------------------------------------------
    */

    private function capabilities(): array
    {
        return $this->reply('help', 'fa-wand-magic-sparkles', $this->t('What Limo can do', 'Limo क्या कर सकता है'), [
            $this->text($this->t(
                'I read your branch data live and answer in English or Hindi. Try asking:',
                'मैं आपकी ब्रांच का लाइव डेटा पढ़कर हिंदी या English में जवाब देता हूँ। ऐसे पूछें:'
            )),
            ['type' => 'bullets', 'items' => [
                $this->t('Money — collection (cash / UPI), dues, expenses, refunds, profit, this month vs last month', 'पैसा — कलेक्शन (कैश / UPI), बकाया, खर्च, रिफंड, मुनाफा, इस महीने बनाम पिछला महीना'),
                $this->t('Learners — admissions, renewals, expiring and expired plans, frozen, lockers, birthdays, feedback, any learner by name', 'छात्र — एडमिशन, रिन्यूअल, खत्म होने वाले व एक्सपायर प्लान, फ्रीज़, लॉकर, जन्मदिन, फीडबैक, नाम से कोई भी छात्र'),
                $this->t('Seats — vacant seats floor-wise, occupancy, who is on seat 12, shifts, plan fees', 'सीटें — फ्लोर-वार खाली सीटें, ऑक्यूपेंसी, सीट 12 पर कौन है, शिफ्ट, प्लान फीस'),
                $this->t("Attendance — who came today, who hasn't come", 'हाजिरी — आज कौन आया, कौन नहीं आया'),
                $this->t('Library — branches, staff, enquiries & online bookings, branch details, your Libraro plan', 'लाइब्रेरी — ब्रांच, स्टाफ, पूछताछ व ऑनलाइन बुकिंग, ब्रांच की जानकारी, आपका Libraro प्लान'),
                $this->t('How to use Libraro — every feature, step by step, plus FAQs', 'Libraro कैसे चलाएं — हर फ़ीचर स्टेप-बाय-स्टेप, और आम सवाल'),
                $this->t('Running a library — more admissions, collecting fees on time, pricing, rules, facilities, starting a new library', 'लाइब्रेरी चलाना — एडमिशन बढ़ाना, समय पर फीस लेना, फीस तय करना, नियम, सुविधाएं, नई लाइब्रेरी शुरू करना'),
            ]],
        ], [], $this->defaultSuggestions());
    }

    /**
     * "Libraro ko use kaise karu, isme kya kya features hai", "how to use libraro", "what features
     * does the app have": the question is about Libraro as a whole, not one feature.
     */
    private function asksAboutLibraro(): bool
    {
        $q = $this->q;
        $subject = '(libraro|लाइब्रेरो|लाइब्रारो|software|सॉफ्टवेयर|app|ऐप|application|portal|panel|isko|ise|isse|इसको|इसे|इसमें)';
        $useIt = (bool) preg_match('/ '.$subject.' (ko |का |को )?(kaise |kese |kaisey |कैसे )?(use|istemal|istemaal|chala\S*|operate|इस्तेमाल|उपयोग|चला\S*) /u', $q)
            || (bool) preg_match('/ how (to|do i|can i|should i) (use|start using|operate) (libraro|this app|the app|this software|the software|it) /u', $q);
        $features = (bool) preg_match('/ (features?|feature list|फीचर\S*|फ़ीचर\S*) /u', $q)
            || (bool) preg_match('/ kya kya (h|he|hai|hain|milta|milega|hota|kar|kr|sakte|sakta) /u', $q);

        return $useIt || ($features && (bool) preg_match('/ '.$subject.' | isme | ismein | इसमें /u', $q)) || ($features && count(preg_split('/\s+/u', trim($q)) ?: []) <= 4);
    }

    private function libraroOverview(): array
    {
        $links = array_values(array_filter([
            $this->link($this->t('Seat map', 'सीट मैप'), 'seats'),
            $this->link($this->t('Learners', 'छात्र'), 'learners'),
            $this->link($this->t('Reports', 'रिपोर्ट'), 'reports.hub'),
            $this->link($this->t('Watch video tutorials', 'वीडियो ट्यूटोरियल देखें'), 'library.video-training'),
        ]));

        return $this->reply('guide', 'fa-book-open', $this->t('How to use Libraro — main features', 'Libraro कैसे चलाएं — मुख्य फ़ीचर'), [
            $this->text($this->t(
                'Libraro runs your whole library from one panel. Start in this order, then use the rest every day:',
                'Libraro से पूरी लाइब्रेरी एक ही पैनल से चलती है। इस क्रम में शुरू करें, फिर बाकी चीज़ें रोज़ काम आएंगी:'
            )),
            ['type' => 'bullets', 'items' => [
                $this->t('Setup — branch, shifts & fees, floors, plans and plan prices (Setup menu)', 'सेटअप — ब्रांच, शिफ्ट व फीस, फ्लोर, प्लान और प्लान की कीमत (Setup मेन्यू)'),
                $this->t('Seat booking — book from the Seat Map, numbered or general seats, extra shifts, custom dates, locker and discount', 'सीट बुकिंग — Seat Map से बुक करें, नंबर वाली या General सीट, अतिरिक्त शिफ्ट, कस्टम तारीख, लॉकर और छूट'),
                $this->t('Learners — renew, upgrade, change plan, swap seat, freeze, gift days, close, reactivate, ID cards and receipts', 'छात्र — रिन्यू, अपग्रेड, प्लान बदलना, सीट बदलना, फ्रीज़, गिफ्ट डेज़, क्लोज़, दोबारा चालू, ID कार्ड और रसीद'),
                $this->t('Fees — part payments, dues to collect, pay later, other payments, expenses and WhatsApp / SMS reminders', 'फीस — आंशिक भुगतान, बकाया वसूली, पे-लेटर, अन्य भुगतान, खर्च और WhatsApp / SMS रिमाइंडर'),
                $this->t('Attendance — QR scan at the desk, manual marking, monthly summary', 'हाजिरी — डेस्क पर QR स्कैन, हाथ से हाजिरी, मासिक सारांश'),
                $this->t('Online — QR seat booking, your library website page, enquiries and demo bookings', 'ऑनलाइन — QR से सीट बुकिंग, लाइब्रेरी का वेबसाइट पेज, पूछताछ और डेमो बुकिंग'),
                $this->t('Reports — collection, cash book, profit & loss, renewals, occupancy, growth, attendance', 'रिपोर्ट — कलेक्शन, कैश बुक, मुनाफा-नुकसान, रिन्यूअल, ऑक्यूपेंसी, ग्रोथ, हाजिरी'),
                $this->t('Students — their own login, end-to-end encrypted chat with you, quizzes and study tools', 'छात्र पोर्टल — उनका अपना लॉगिन, आपसे end-to-end encrypted चैट, क्विज़ और पढ़ाई के टूल'),
                $this->t('Staff — separate logins with only the permissions you give', 'स्टाफ — अलग लॉगिन, सिर्फ उतनी अनुमति जितनी आप दें'),
                $this->t('Limo (me) — ask about dues, seats, collection or how to do anything', 'Limo (मैं) — बकाया, सीटें, कलेक्शन या कोई भी काम कैसे करें, पूछें'),
            ]],
        ], $links, [
            $this->t('How to book a seat?', 'सीट कैसे बुक करें?'),
            $this->t('First-time setup of your library', 'लाइब्रेरी का पहली बार सेटअप'),
            $this->t('How to renew a seat?', 'सीट कैसे रिन्यू करें?'),
            $this->t("Today's summary", 'आज का सारांश'),
        ]);
    }

    private function notUnderstood(): array
    {
        $r = $this->reply('unknown', 'fa-circle-question', $this->t('I did not get that', 'मैं समझ नहीं पाया'), [
            $this->note($this->t(
                'I only answer questions about your library data and how to use Libraro. Try one of these, or rephrase with words like dues, seats, collection, attendance, renew … Facing a problem in the panel? Open Limo Support.',
                'मैं केवल आपकी लाइब्रेरी के डेटा और Libraro के उपयोग से जुड़े सवालों के जवाब देता हूँ। नीचे से कोई सवाल चुनें, या बकाया, सीट, कलेक्शन, हाजिरी, रिन्यू जैसे शब्दों से पूछें। पैनल में कोई समस्या है? Limo सपोर्ट खोलें।'
            ), 'info'),
        ], [], $this->defaultSuggestions());
        // Problems are solved step by step in Support mode
        $r['choices'] = [$this->choice($this->t('Open Limo Support', 'Limo सपोर्ट खोलें'), '#home', 'violet', 'fa-life-ring')];

        return $r;
    }

    private function defaultSuggestions(): array
    {
        return [
            $this->t("Today's summary", 'आज का सारांश'),
            $this->t('Show due students list', 'बकाया छात्रों की सूची दिखाओ'),
            $this->t('Show vacant seats', 'खाली सीटें दिखाओ'),
            $this->t('How to book a seat?', 'सीट कैसे बुक करें?'),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Shared queries
    |--------------------------------------------------------------------------
    */

    private function collectionTotal(string $from, string $to): array
    {
        $q = DB::table('learner_transaction_activity')
            ->where('branch_id', $this->branchId)->whereNull('deleted_at')
            ->where('dr_cr', 'Cr')->where('amount', '>', 0)
            ->whereNotIn('payment_mode', self::NON_MONEY_MODES)
            ->whereBetween('date', [$from, $to]);

        return ['total' => (float) (clone $q)->sum('amount'), 'count' => (clone $q)->count()];
    }

    private function duesTotals(): array
    {
        $today = Carbon::today()->toDateString();
        $base = DB::table('learner_transactions as t')
            ->join('learners as l', 'l.id', '=', 't.learner_id')
            ->where('t.branch_id', $this->branchId)
            ->whereNull('t.deleted_at')->whereNull('l.deleted_at')
            ->where('t.pending_amount', '>', 0);
        $over = (clone $base)->where(fn ($q) => $q->whereNull('t.due_date')->orWhere('t.due_date', '<', $today));

        return [
            'amount' => (float) (clone $base)->sum('t.pending_amount'),
            'learners' => (clone $base)->distinct()->count('t.learner_id'),
            'overdue' => (float) (clone $over)->sum('t.pending_amount'),
            'overdue_learners' => (clone $over)->distinct()->count('t.learner_id'),
        ];
    }

    /** WhatsApp reminder + Renew for each learner row. */
    private function renewActions($rows, string $kind): array
    {
        $canRenew = $this->can('Renew Seat');

        return $rows->map(fn ($r) => [
            $this->waAction($r, $kind, ['date' => $r->end_date]),
            $canRenew ? $this->linkAction($this->t('Renew', 'रिन्यू'), 'learner.renew.plan', ['id' => $r->id], 'fa-arrows-rotate') : null,
        ])->values()->all();
    }

    private function expiringQuery(int $days)
    {
        $today = Carbon::today();
        $latest = DB::table('learner_detail')
            ->where('branch_id', $this->branchId)->whereNull('deleted_at')->where('status', 1)
            ->groupBy('learner_id')
            ->selectRaw('learner_id, MAX(plan_end_date) as end_date, MAX(seat_no) as seat_no');

        return DB::table('learners as l')
            ->joinSub($latest, 'x', 'x.learner_id', '=', 'l.id')
            ->where('l.branch_id', $this->branchId)->whereNull('l.deleted_at')
            ->where(fn ($q) => $q->whereNull('l.no_expiry')->orWhere('l.no_expiry', '!=', 1))
            ->whereBetween('x.end_date', [$today->toDateString(), $today->copy()->addDays($days)->toDateString()])
            ->select('l.id', 'l.name', 'l.mobile', 'x.seat_no', 'x.end_date');
    }

    private function activeLearnerCount(): int
    {
        return (int) ($this->insights->memberKpis($this->branchId)['active'] ?? 0);
    }

    private function attendanceCounts(): array
    {
        try {
            $c = $this->insights->liveAttendance($this->branchId)['counts'];

            return ['all' => $c['all'], 'in' => $c['in'], 'came' => $c['in'] + $c['out']];
        } catch (\Throwable $e) {
            return ['all' => 0, 'in' => 0, 'came' => 0];
        }
    }

    private array $seatCache = [];

    private function seatStats(): array
    {
        if ($this->seatCache) {
            return $this->seatCache;
        }
        $today = Carbon::today()->toDateString();
        $total = (int) DB::table('hour')->where('branch_id', $this->branchId)->value('seats');

        $active = DB::table('learner_detail as ld')
            ->join('learners as l', 'l.id', '=', 'ld.learner_id')
            ->where('ld.branch_id', $this->branchId)
            ->whereNull('ld.deleted_at')->whereNull('l.deleted_at')
            ->where('ld.status', 1)
            ->where('ld.plan_start_date', '<=', $today)
            ->where(fn ($q) => $q->where('ld.plan_end_date', '>=', $today)->orWhere('l.no_expiry', 1));

        $taken = (clone $active)->where('ld.seat_no', '>', 0)->distinct()->pluck('ld.seat_no')->map(fn ($s) => (int) $s)->all();
        $general = (clone $active)->where(fn ($q) => $q->whereNull('ld.seat_no')->orWhere('ld.seat_no', 0))->distinct()->count('ld.learner_id');

        $taken = array_values(array_filter(array_unique($taken), fn ($s) => $s <= $total || ! $total));
        $occupied = count($taken);
        $vacantList = $total ? array_values(array_diff(range(1, $total), $taken)) : [];

        return $this->seatCache = [
            'total' => $total,
            'occupied' => $occupied,
            'vacant' => max(0, $total - $occupied),
            'pct' => $total ? round($occupied / $total * 100, 1) : 0,
            'general' => $general,
            'vacantList' => array_map('strval', $vacantList),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Language & parsing
    |--------------------------------------------------------------------------
    */

    private function resolveLanguage(string $raw, string $lang): string
    {
        if (in_array($lang, ['en', 'hi'], true)) {
            return $lang;
        }
        if (preg_match('/[\x{0900}-\x{097F}]/u', $raw)) {
            return 'hi';
        }
        $words = preg_split('/[^a-z]+/', strtolower($raw)) ?: [];
        $hits = count(array_intersect($words, self::HINGLISH_MARKERS));

        return $hits >= 1 && $hits >= count(array_filter($words)) / 6 ? 'hi' : 'en';
    }

    private function normalise(string $s): string
    {
        $s = mb_strtolower($s, 'UTF-8');
        // Devanagari nukta forms -> plain letters, so "ज़" and "ज" match the same
        $s = str_replace(['ज़', 'फ़', 'क़', 'ख़', 'ग़', 'ड़', 'ढ़'], ['ज', 'फ', 'क', 'ख', 'ग', 'ड', 'ढ'], $s);
        $s = preg_replace('/[^\p{L}\p{M}\p{N}\s]+/u', ' ', $s);

        return ' '.trim(preg_replace('/\s+/u', ' ', $s)).' ';
    }

    /**
     * Corrects small spelling mistakes in Latin-script words ("colection", "vaccant", "bakya")
     * against the words the assistant knows. Names and numbers are left alone.
     */
    /** Every Latin word the assistant knows (concepts, guide synonyms, tips). */
    private function vocab(): array
    {
        static $vocab = null;
        if ($vocab === null) {
            $phrases = array_merge(...array_values(self::CONCEPTS), ...array_values(self::GUIDE_SYNONYMS));
            foreach ($this->kbEntries() as $e) {
                $phrases = array_merge($phrases, $e['keys']);
            }
            try {
                $phrases = array_merge($phrases,
                    DB::table('guidelines')->pluck('guideline_type')->all(),
                    DB::table('how-to-use')->pluck('operation_name')->map(fn ($n) => preg_replace('/[^a-z ]+/i', ' ', (string) $n))->all());
            } catch (\Throwable $e) {
            }
            $vocab = [];
            foreach ($phrases as $p) {
                foreach (preg_split('/\s+/', strtolower($p)) as $w) {
                    if (preg_match('/^[a-z]{3,}$/', $w)) {
                        $vocab[$w] = true;
                    }
                }
            }
            $vocab = array_keys($vocab);
        }

        return $vocab;
    }

    private function fixTypos(string $q): string
    {
        $vocab = $this->vocab();
        $words = explode(' ', trim($q));
        foreach ($words as $i => $w) {
            if (! preg_match('/^[a-z]{4,}$/', $w) || in_array($w, $vocab, true)) {
                continue;
            }
            // "deleted", "renewing", "seats" are real words, not typos
            $stem = preg_replace('/(ing|ed|es|s)$/', '', $w);
            if ($stem !== $w && (in_array($stem, $vocab, true) || in_array($stem.'e', $vocab, true))) {
                continue;
            }
            $max = strlen($w) <= 5 ? 1 : 2;
            $best = null;
            $bestD = $max + 1;
            foreach ($vocab as $v) {
                if (abs(strlen($v) - strlen($w)) > $max || $v[0] !== $w[0]) {
                    continue;
                }
                $d = levenshtein($w, $v);
                if ($d < $bestD) {
                    [$best, $bestD] = [$v, $d];
                }
            }
            if ($best !== null) {
                $words[$i] = $best;
            }
        }

        return ' '.implode(' ', $words).' ';
    }

    private function contains(string $phrase): bool
    {
        $phrase = trim($phrase);
        if ($phrase === '') {
            return false;
        }

        // Whole-word match (spaces around), works for Latin and Devanagari alike
        return mb_strpos($this->q, ' '.$phrase.' ') !== false
            // Hindi words often carry a suffix (छात्रों, सीटें) — allow prefix match for Devanagari
            || (preg_match('/[\x{0900}-\x{097F}]/u', $phrase) && mb_strpos($this->q, ' '.$phrase) !== false);
    }

    private function has(string $concept): bool
    {
        foreach (self::CONCEPTS[$concept] ?? [] as $p) {
            if ($this->contains($this->normalise($p))) {
                return true;
            }
        }
        // The branch's own shift names ("full day", "morning") also mean "shift"
        if ($concept === 'shift') {
            foreach ($this->shiftNames() as $name) {
                if ($this->contains($name)) {
                    return true;
                }
            }
        }

        return false;
    }

    private ?array $shiftNameCache = null;

    private function shiftNames(): array
    {
        return $this->shiftNameCache ??= DB::table('plan_types')->where('branch_id', $this->branchId)->whereNull('deleted_at')
            ->pluck('name')
            ->map(fn ($n) => trim($this->normalise((string) $n)))
            ->filter(fn ($n) => mb_strlen($n) >= 3)
            ->unique()->values()->all();
    }

    private function seatNumber(): ?int
    {
        if (preg_match('/(?:seat|सीट|kursi|कुर्सी)\s*(?:no|number|नंबर|num|#)?\s*(\d{1,4})\b/u', $this->q, $m)
            || preg_match('/\b(\d{1,4})\s*(?:no|number|नंबर)?\s*(?:seat|सीट)(?=\s|$)/u', $this->q, $m)) {
            // "7 days" / "30 din" are periods, not seats
            if (preg_match('/\d+\s*(days?|din|दिन|month|mahine)/u', $this->q)) {
                return null;
            }

            return (int) $m[1] ?: null;
        }

        return null;
    }

    private function numberOfDays(): ?int
    {
        if (preg_match('/(\d{1,3})\s*(days?|din|दिन)/u', $this->q, $m)) {
            return max(1, min(365, (int) $m[1]));
        }
        if (preg_match('/\b(week|hafte|hafta|सप्ताह|हफ्ते)\b/u', $this->q)) {
            return 7;
        }
        if (preg_match('/\b(month|mahine|mahina|महीने|महीना)\b/u', $this->q)) {
            return 30;
        }
        if (preg_match('/\b(tomorrow|kal)\b|(?<!\S)कल(?!\S)/u', $this->q)) {
            return 1;
        }
        if (preg_match('/\b(today|aaj)\b|(?<!\S)आज(?!\S)/u', $this->q)) {
            return 0;
        }

        return null;
    }

    /** Works out a past date range from the query. */
    private function period(string $default): array
    {
        $t = Carbon::today();
        $q = $this->q;
        $m = [];

        if (preg_match('/(last|pichle|पिछले)\s*(month|mahine|महीने)/u', $q)) {
            $s = $t->copy()->subMonthNoOverflow()->startOfMonth();

            return [$s->toDateString(), $s->copy()->endOfMonth()->toDateString(), $s->format('F Y')];
        }
        if (preg_match('/(\d{1,3})\s*(days?|din|दिन)/u', $q, $m)) {
            $n = max(1, min(365, (int) $m[1]));

            return [$t->copy()->subDays($n - 1)->toDateString(), $t->toDateString(), $this->t('Last '.$n.' days', 'पिछले '.$n.' दिन')];
        }
        if (preg_match('/\b(yesterday|kal)\b|(?<!\S)कल(?!\S)/u', $q)) {
            $y = $t->copy()->subDay();

            return [$y->toDateString(), $y->toDateString(), $this->t('Yesterday', 'कल').' · '.$y->format('j M')];
        }
        if (preg_match('/\b(week|hafte|hafta|weekly)\b|सप्ताह|हफ्ते/u', $q)) {
            return [$t->copy()->subDays(6)->toDateString(), $t->toDateString(), $this->t('Last 7 days', 'पिछले 7 दिन')];
        }
        if (preg_match('/\b(year|saal|yearly)\b|साल|वर्ष/u', $q)) {
            return [$t->copy()->startOfYear()->toDateString(), $t->toDateString(), $this->t('This year', 'इस साल')];
        }
        if (preg_match('/\b(month|mahine|mahina|monthly)\b|महीने|महीना/u', $q) || $default === 'month') {
            if (! preg_match('/\b(today|aaj)\b|(?<!\S)आज(?!\S)/u', $q) || $default === 'month') {
                return [$t->copy()->startOfMonth()->toDateString(), $t->toDateString(), $t->format('F Y')];
            }
        }

        return [$t->toDateString(), $t->toDateString(), $this->t('Today', 'आज').' · '.$t->format('j M')];
    }

    /**
     * Words of the query that are not part of any known concept and match the start of a
     * learner's name in this branch — e.g. "rahul" in "rahul ki fees kitni baki hai".
     */
    private function nameInQuery(): ?string
    {
        $q = $this->q;
        foreach (self::CONCEPTS as $phrases) {
            foreach ($phrases as $p) {
                $q = str_replace(' '.trim($this->normalise($p)).' ', ' ', $q);
            }
        }
        $stop = ['fee', 'fees', 'fess', 'payment', 'plan', 'plans', 'seat', 'this', 'that', 'month', 'today', 'yesterday', 'tomorrow', 'week', 'year', 'days', 'day', 'last', 'next',
            'what', 'when', 'where', 'which', 'why', 'with', 'from', 'about', 'there', 'their', 'have', 'has', 'are', 'was', 'were', 'the', 'and', 'for', 'all', 'any', 'can', 'you', 'your', 'please', 'tell', 'give', 'show', 'library',
            'aaj', 'kal', 'abhi', 'mahine', 'mahina', 'hafte', 'saal', 'din', 'hai', 'hain', 'tha', 'the', 'kya', 'kab', 'kitna', 'ki', 'ka', 'ke', 'ko', 'se', 'me', 'mein', 'par', 'pe', 'aur', 'bhi', 'wala', 'wale', 'wali', 'mera', 'meri', 'mere', 'apna', 'apni', 'karo', 'kare', 'karein', 'chahiye', 'tak', 'liye',
            'है', 'हैं', 'की', 'का', 'के', 'को', 'से', 'में', 'पर', 'और', 'भी', 'वाले', 'वाला', 'मेरा', 'मेरी', 'इस', 'उस', 'आज', 'कल', 'महीने', 'दिन', 'फीस', 'प्लान', 'क्या', 'कब', 'तक', 'लिए', 'करें', 'करो'];

        $known = $this->vocab();
        $words = array_values(array_filter(explode(' ', trim($q)), fn ($w) => mb_strlen($w) >= 3 && ! in_array($w, $stop, true) && ! in_array($w, $known, true) && ! preg_match('/\d/', $w)));
        if (! $words || count($words) > 3) {
            return null;
        }

        $found = [];
        foreach ($words as $w) {
            $like = addcslashes($w, '%_\\');
            $hit = DB::table('learners')->where('branch_id', $this->branchId)->whereNull('deleted_at')
                ->where(fn ($x) => $x->where('name', 'like', $like.'%')->orWhere('name', 'like', '% '.$like.'%'))
                ->exists();
            if ($hit) {
                $found[] = $w;
            }
        }

        return $found ? implode(' ', $found) : null;
    }

    /** What is left of the query once known words are removed — used as a learner name. */
    private function searchTerm(): ?string
    {
        $q = $this->q;
        $stop = ['search', 'find', 'lookup', 'khojo', 'dhundo', 'dhoondo', 'profile', 'details', 'detail', 'info', 'of', 'the', 'learner', 'student', 'member', 'show', 'me', 'please', 'ki', 'ka', 'ke', 'ko', 'naam', 'name', 'named', 'batao', 'dikhao', 'kaun', 'hai', 'about', 'for', 'खोजें', 'खोजो', 'ढूंढो', 'ढूंढें', 'की', 'का', 'के', 'को', 'छात्र', 'नाम', 'बताओ', 'दिखाओ', 'विवरण', 'जानकारी', 'है', 'प्रोफाइल'];
        $words = array_filter(explode(' ', trim($q)), fn ($w) => $w !== '' && ! in_array($w, $stop, true));
        $term = trim(implode(' ', $words));

        return mb_strlen($term) >= 3 && mb_strlen($term) <= 40 ? $term : null;
    }

    /*
    |--------------------------------------------------------------------------
    | Reply building
    |--------------------------------------------------------------------------
    */

    private function t(string $en, string $hi): string
    {
        return $this->lang === 'hi' ? $hi : $en;
    }

    private function reply(string $intent, string $icon, string $title, array $blocks, array $actions = [], array $suggestions = []): array
    {
        return [
            'intent' => $intent,
            'icon' => $icon,
            'title' => $title,
            'blocks' => array_values(array_filter($blocks)),
            'actions' => array_values(array_filter($actions)),
            'suggestions' => array_values(array_unique($suggestions)),
        ];
    }

    private function stats(array $items): array
    {
        return ['type' => 'stats', 'items' => $items];
    }

    private function stat(string $label, string $value, string $tone = 'neutral', ?string $sub = null): array
    {
        return ['label' => $label, 'value' => $value, 'tone' => $tone, 'sub' => $sub];
    }

    /** @param  array  $rowActions  per row: a list of actions (waAction / linkAction) */
    private function table(array $columns, array $rows, int $more = 0, ?string $caption = null, array $rowActions = []): array
    {
        $rowActions = array_map(fn ($a) => array_values(array_filter($a)), $rowActions);

        return ['type' => 'table', 'columns' => $columns, 'rows' => $rows, 'more' => $more,
            'moreLabel' => $more ? $this->t('+ '.$more.' more — open the report for the full list', '+ '.$more.' और — पूरी सूची रिपोर्ट में देखें') : null,
            'caption' => $caption,
            'rowActions' => array_filter($rowActions) ? $rowActions : null];
    }

    /** Bar chart: items = [[label, value, display], ...] */
    private function bars(string $caption, array $items): ?array
    {
        $items = array_values(array_filter($items, fn ($i) => is_numeric($i[1])));
        if (count($items) < 2) {
            return null;
        }

        return ['type' => 'bars', 'caption' => $caption,
            'items' => array_map(fn ($i) => ['label' => (string) $i[0], 'value' => (float) $i[1], 'display' => (string) ($i[2] ?? $i[1])], $items)];
    }

    /**
     * Opens WhatsApp with a ready reminder for the learner (the owner still presses send there).
     *
     * @param  object  $l  a learners row (name, mobile)
     * @param  string  $kind  due | expiring | expired
     */
    private function waAction(object $l, string $kind, array $d = []): ?array
    {
        try {
            $phone = preg_replace('/\D/', '', (string) decryptData((string) ($l->mobile ?? '')));
        } catch (\Throwable $e) {
            return null;
        }
        if (strlen($phone) > 10 && str_starts_with($phone, '91')) {
            $phone = substr($phone, 2);
        }
        if (strlen($phone) !== 10) {
            return null;
        }

        $name = (string) $l->name;
        $first = trim(explode(' ', trim($name))[0]) ?: $name;
        $branch = $this->branchName();
        $date = ! empty($d['date']) ? $this->date($d['date']) : null;
        $amount = isset($d['amount']) ? $this->inr((float) $d['amount']) : null;

        $msg = match ($kind) {
            'due' => $this->t(
                "Hello {$first}, your fee of {$amount} at {$branch} is pending".($date ? " (due on {$date})" : '').'. Please pay at the earliest. Thank you!',
                "नमस्ते {$first}, {$branch} में आपकी {$amount} फीस बाकी है".($date ? " (देय तिथि {$date})" : '').'। कृपया जल्द जमा करें। धन्यवाद!'
            ),
            'expiring' => $this->t(
                "Hello {$first}, your plan at {$branch} ends on {$date}. Please renew on time to keep your seat. Thank you!",
                "नमस्ते {$first}, {$branch} में आपका प्लान {$date} को खत्म हो रहा है। अपनी सीट बनाए रखने के लिए समय पर रिन्यू करें। धन्यवाद!"
            ),
            default => $this->t(
                "Hello {$first}, your plan at {$branch} ended on {$date}. Renew now to get your seat back. We would love to see you again!",
                "नमस्ते {$first}, {$branch} में आपका प्लान {$date} को खत्म हो गया। अपनी सीट वापस पाने के लिए अभी रिन्यू करें। हमें आपका इंतज़ार है!"
            ),
        };

        return [
            'kind' => 'whatsapp',
            'label' => $this->t('WhatsApp reminder', 'व्हाट्सएप रिमाइंडर'),
            'icon' => 'fa-brands fa-whatsapp',
            'url' => 'https://wa.me/91'.$phone.'?text='.rawurlencode($msg),
            'confirm' => $this->t('Open WhatsApp with a reminder for '.$name.'?', $name.' के लिए WhatsApp पर रिमाइंडर खोलें?'),
            'preview' => $msg,
        ];
    }

    private function linkAction(string $label, string $route, array $params, string $icon): ?array
    {
        $url = $this->url($route, $params);

        return $url ? ['kind' => 'link', 'label' => $label, 'icon' => $icon, 'url' => $url] : null;
    }

    private function chips(string $caption, array $items, int $more = 0): array
    {
        return ['type' => 'chips', 'caption' => $caption, 'items' => $items, 'more' => $more > 0 ? $this->t('+'.$more.' more', '+'.$more.' और') : null];
    }

    private function note(string $text, string $tone = 'info'): array
    {
        return ['type' => 'note', 'text' => $text, 'tone' => $tone];
    }

    private function text(string $text): array
    {
        return ['type' => 'text', 'text' => $text];
    }

    private function link(string $label, string $route, $param = null): ?array
    {
        $url = $this->url($route, $param);

        return $url ? ['label' => $label, 'url' => $url] : null;
    }

    private function url(string $route, $param = null): ?string
    {
        if (! Route::has($route)) {
            return null;
        }
        try {
            return $param === null ? route($route) : route($route, $param);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function branchName(): string
    {
        static $name = null;

        return $name ??= (string) (DB::table('branches')->where('id', $this->branchId)->value(DB::raw('COALESCE(display_name, name)'))
            ?: DB::table('libraries')->where('id', $this->libraryId)->value('library_name') ?: 'Library');
    }

    private ?array $seatFloors = null;

    /** "Seat 12 · Hall A" — floors renumber seats, so the floor name makes the seat findable. */
    private function seatLabel($no): string
    {
        if ($no === null || $no === '' || (int) $no === 0) {
            return $this->t('General', 'जनरल');
        }
        if ($this->seatFloors === null) {
            $this->seatFloors = [];
            try {
                foreach (generateSeatNumbers2($this->branchId) as $s) {
                    if (! empty($s['floor_name'])) {
                        $this->seatFloors[(int) $s['main']] = [$s['floor'] ?? $s['main'], $s['floor_name']];
                    }
                }
            } catch (\Throwable $e) {
            }
        }
        $f = $this->seatFloors[(int) $no] ?? null;

        return $this->t('Seat ', 'सीट ').($f ? $f[0].' · '.$f[1] : (int) $no);
    }

    private function inr(float $n): string
    {
        $neg = $n < 0;
        $n = abs($n);
        $dec = fmod($n, 1) > 0.004 ? '.'.str_pad((string) round(fmod($n, 1) * 100), 2, '0', STR_PAD_LEFT) : '';
        $int = (string) (int) floor($n);
        if (strlen($int) > 3) {
            $last = substr($int, -3);
            $rest = substr($int, 0, -3);
            $int = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest).','.$last;
        }

        return ($neg ? '−' : '').'₹'.$int.$dec;
    }

    private function date($d): string
    {
        try {
            return Carbon::parse($d)->format('j M Y');
        } catch (\Throwable $e) {
            return (string) $d;
        }
    }

    private function timeRange(?string $start, ?string $end): string
    {
        $f = function ($t) {
            if (! $t) {
                return '';
            }
            try {
                $c = Carbon::parse($t);

                return $c->format('i') === '00' ? $c->format('g A') : $c->format('g:i A');
            } catch (\Throwable $e) {
                return (string) $t;
            }
        };

        if (! $start) {
            return '';
        }
        // Same start and end = a flexible / round-the-clock slot
        if ($end && $f($start) === $f($end)) {
            return $this->t('Any time', 'कभी भी');
        }

        return $f($start).($end ? ' – '.$f($end) : '');
    }

    /** Plain-text version of a reply (for logs and any client that cannot render blocks). */
    private function plainText(array $reply): string
    {
        $out = [$reply['title']];
        foreach ($reply['blocks'] as $b) {
            switch ($b['type']) {
                case 'stats':
                    foreach ($b['items'] as $i) {
                        $out[] = '• '.$i['label'].': '.$i['value'];
                    }
                    break;
                case 'table':
                    foreach ($b['rows'] as $r) {
                        $out[] = '• '.implode(' | ', array_map(fn ($c) => is_array($c) ? $c['text'] : $c, $r));
                    }
                    break;
                case 'guide':
                    foreach ($b[$this->lang] ?: $b['en'] as $s) {
                        $out[] = '• '.$s;
                    }
                    break;
                case 'bullets':
                case 'chips':
                    $out[] = implode(', ', $b['items']);
                    break;
                default:
                    $out[] = $b['text'] ?? '';
            }
        }

        return implode("\n", array_filter($out));
    }

    /** Saves the question and the answer; returns the answer's id (used for 👍/👎 and PDF export). */
    private function log(string $query, array $reply): ?int
    {
        try {
            $now = now();
            $base = ['library_id' => $this->libraryId, 'branch_id' => $this->branchId ?: null, 'actor' => $this->actor, 'created_at' => $now, 'updated_at' => $now];
            DB::table('ai_chat_histories')->insert($base + ['sender' => 'user', 'message' => mb_substr($query, 0, 1000), 'context_data_snapshot' => null]);

            return (int) DB::table('ai_chat_histories')->insertGetId($base + [
                'sender' => 'assistant',
                'intent' => mb_substr((string) ($reply['intent'] ?? ''), 0, 40),
                'question' => mb_substr($query, 0, 500),
                'message' => json_encode($reply, JSON_UNESCAPED_UNICODE),
                'context_data_snapshot' => json_encode($this->newContext + ['lang' => $this->lang]),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Libraro AI history not saved: '.$e->getMessage());

            return null;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Feedback, alerts and export
    |--------------------------------------------------------------------------
    */

    /** 👍 (1) / 👎 (-1) on an answer of this library; 0 removes the vote. */
    public function feedback(int $libraryId, int $messageId, int $value): bool
    {
        return DB::table('ai_chat_histories')
            ->where('id', $messageId)->where('library_id', $libraryId)->where('sender', 'assistant')
            ->update(['feedback' => $value === 0 ? null : ($value > 0 ? 1 : -1), 'updated_at' => now()]) > 0;
    }

    /** A stored answer of this library (for PDF / Excel export). */
    public function storedReply(int $libraryId, int $messageId): ?array
    {
        $m = DB::table('ai_chat_histories')->where('id', $messageId)->where('library_id', $libraryId)->where('sender', 'assistant')->value('message');
        $reply = $m ? json_decode($m, true) : null;

        return is_array($reply) ? $reply : null;
    }

    /**
     * Things that need attention today, shown when the chat opens — each one is a question
     * Limo can answer in detail. Only what this user is allowed to see.
     *
     * @return array<int, array{tone: string, icon: string, text: string, query: string}>
     */
    public function alerts(int $libraryId, ?int $branchId = null, string $lang = 'en'): array
    {
        $this->boot($libraryId, $branchId, $lang === 'hi' ? 'hi' : 'en');
        $out = [];

        try {
            if ($this->canIntent('expiring')) {
                $today = $this->expiringQuery(0)->count();
                $week = $this->expiringQuery(3)->count();
                if ($today > 0) {
                    $out[] = ['tone' => 'bad', 'icon' => 'fa-hourglass-end', 'text' => $this->t($today.' plan'.($today > 1 ? 's end' : ' ends').' today', 'आज '.$today.' प्लान खत्म हो रहे हैं'), 'query' => $this->t('Whose plan expires in next 3 days?', 'अगले 3 दिन में किसका प्लान खत्म होगा?')];
                } elseif ($week > 0) {
                    $out[] = ['tone' => 'warn', 'icon' => 'fa-hourglass-half', 'text' => $this->t($week.' plan'.($week > 1 ? 's end' : ' ends').' in the next 3 days', 'अगले 3 दिन में '.$week.' प्लान खत्म होंगे'), 'query' => $this->t('Whose plan expires in next 3 days?', 'अगले 3 दिन में किसका प्लान खत्म होगा?')];
                }
            }
            if ($this->canIntent('duesList')) {
                $d = $this->duesTotals();
                if ($d['overdue_learners'] > 0) {
                    $out[] = ['tone' => 'bad', 'icon' => 'fa-hand-holding-dollar', 'text' => $this->t($d['overdue_learners'].' learner'.($d['overdue_learners'] > 1 ? 's have' : ' has').' overdue fees · '.$this->inr($d['overdue']), $d['overdue_learners'].' छात्रों की फीस की तारीख निकल गई · '.$this->inr($d['overdue'])), 'query' => $this->t('Show due students list', 'बकाया छात्रों की सूची दिखाओ')];
                }
            }
            if ($this->canIntent('enquiries')) {
                $pending = DB::table('bookings')->where('branch_id', $this->branchId)->where('status', 'pending')->whereIn('type', ['qr_seat_book', 'qr_renew'])->count();
                if ($pending > 0) {
                    $out[] = ['tone' => 'info', 'icon' => 'fa-inbox', 'text' => $this->t($pending.' online booking'.($pending > 1 ? 's wait' : ' waits').' for your approval', $pending.' ऑनलाइन बुकिंग आपकी मंज़ूरी का इंतज़ार कर रही हैं'), 'query' => $this->t('Enquiries and online bookings', 'पूछताछ और ऑनलाइन बुकिंग')];
                }
            }
            if ($this->canIntent('birthdays')) {
                $b = count($this->insights->highlights($this->branchId)['birthdays'] ?? []);
                if ($b > 0) {
                    $out[] = ['tone' => 'good', 'icon' => 'fa-cake-candles', 'text' => $this->t($b.' learner'.($b > 1 ? 's have' : ' has').' a birthday today', 'आज '.$b.' छात्रों का जन्मदिन है'), 'query' => $this->t("Today's birthdays", 'आज किसका जन्मदिन है?')];
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Limo alerts failed: '.$e->getMessage());
        }

        return array_slice($out, 0, 3);
    }
}
