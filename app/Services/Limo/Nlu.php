<?php

namespace App\Services\Limo;

use Carbon\Carbon;

/**
 * Limo's language layer. Turns a question in English, Hindi (Devanagari) or Hinglish into a small set of
 * canonical words plus entities (period, number, name, mobile, seat). It is plain PHP: no network, no model.
 */
class Nlu
{
    /** canonical word => synonyms (Latin and Devanagari) */
    private const LEX = [
        'today' => ['today', 'aaj', 'aj', 'आज'],
        'yesterday' => ['yesterday', 'beeta', 'बीता'],
        'tomorrow' => ['tomorrow', 'tmrw', 'tommorow'],
        'kal' => ['kal', 'कल'],
        'week' => ['week', 'weeks', 'hafta', 'hafte', 'haftey', 'saptah', 'हफ्ते', 'हफ्ता', 'हफ़्ते', 'सप्ताह'],
        'month' => ['month', 'months', 'mahina', 'mahine', 'mahinay', 'maah', 'महीने', 'महीना', 'माह', 'मास'],
        'year' => ['year', 'years', 'saal', 'sal', 'varsh', 'साल', 'वर्ष'],
        'day' => ['day', 'days', 'din', 'dino', 'dinon', 'दिन', 'दिनों'],
        'dues' => ['due', 'dues', 'pending', 'baki', 'baaki', 'bakaya', 'bakiya', 'udhaar', 'udhar', 'outstanding', 'unpaid', 'बकाया', 'बाकी', 'उधार', 'पेंडिंग', 'ड्यू', 'डयू'],
        'collection' => ['collection', 'collections', 'collect', 'revenue', 'income', 'earning', 'earnings', 'kamai', 'kamayi', 'vasooli', 'wasooli', 'amdani', 'कलेक्शन', 'वसूली', 'कमाई', 'आय', 'रेवेन्यू', 'आमदनी'],
        'student' => ['student', 'students', 'learner', 'learners', 'member', 'members', 'vidyarthi', 'chhatra', 'chatra', 'bacche', 'bachche', 'छात्र', 'छात्रों', 'छात्रा', 'सदस्य', 'सदस्यों', 'विद्यार्थी', 'स्टूडेंट', 'स्टूडेंट्स', 'लर्नर', 'मेंबर'],
        'seat' => ['seat', 'seats', 'seet', 'सीट', 'सीटें', 'सीटों'],
        'vacant' => ['vacant', 'khali', 'khaali', 'empty', 'free', 'available', 'availability', 'remaining', 'खाली', 'रिक्त', 'उपलब्ध'],
        'expire' => ['expire', 'expires', 'expiring', 'expired', 'expiry', 'khatam', 'khtm', 'renew', 'renewal', 'renewals', 'ending', 'ends', 'samapt', 'एक्सपायर', 'समाप्त', 'खत्म', 'ख़त्म', 'रिन्यू'],
        'attendance' => ['attendance', 'hajri', 'hazri', 'haziri', 'उपस्थिति', 'हाज़िरी', 'हाजिरी', 'अटेंडेंस'],
        'present' => ['present', 'aaya', 'aaye', 'aayi', 'उपस्थित'],
        'absent' => ['absent', 'gairhazir', 'anupasthit', 'अनुपस्थित', 'गैरहाज़िर'],
        'shift' => ['shift', 'shifts', 'slot', 'slots', 'timing', 'timings', 'शिफ्ट', 'समय', 'टाइमिंग'],
        'plan' => ['plan', 'plans', 'प्लान'],
        'new' => ['new', 'naya', 'naye', 'nayi', 'admission', 'admissions', 'registration', 'registrations', 'joined', 'joining', 'नए', 'नया', 'नई', 'एडमिशन', 'रजिस्ट्रेशन', 'प्रवेश'],
        'list' => ['list', 'names', 'naam', 'naamon', 'namelist', 'सूची', 'लिस्ट', 'नाम', 'नामों'],
        'count' => ['kitna', 'kitne', 'kitni', 'total', 'count', 'sankhya', 'कितना', 'कितने', 'कितनी', 'कुल', 'संख्या'],
        'contact' => ['mobile', 'phone', 'contact', 'whatsapp', 'मोबाइल', 'फोन', 'फ़ोन', 'संपर्क'],
        'subscription' => ['subscription', 'subscribe', 'सब्सक्रिप्शन'],
        'complaint' => ['complaint', 'complaints', 'shikayat', 'shikayatein', 'शिकायत', 'शिकायतें'],
        'birthday' => ['birthday', 'birthdays', 'bday', 'janmdin', 'जन्मदिन'],
        'howto' => ['kaise', 'kese', 'kaisey', 'steps', 'guide', 'tarika', 'tareeka', 'procedure', 'कैसे', 'तरीका', 'विधि'],
        'hello' => ['hi', 'hello', 'hey', 'hii', 'helo', 'namaste', 'namaskar', 'pranam', 'नमस्ते', 'हेलो', 'हाय', 'प्रणाम'],
        'thanks' => ['thanks', 'thank', 'thankyou', 'thx', 'dhanyawad', 'dhanyavad', 'shukriya', 'धन्यवाद', 'शुक्रिया', 'थैंक्स'],
        'who' => ['who', 'kaun', 'kon', 'कौन'],
        'help' => ['help', 'madad', 'sahayata', 'features', 'मदद', 'सहायता', 'हेल्प'],
        'summary' => ['summary', 'overview', 'report', 'status', 'haal', 'update', 'dashboard', 'snapshot', 'सारांश', 'समरी', 'रिपोर्ट', 'विवरण', 'हाल'],
        'occupancy' => ['occupancy', 'occupied', 'percent', 'percentage', 'houseful', 'ऑक्यूपेंसी'],
        'expense' => ['expense', 'expenses', 'kharcha', 'kharch', 'kharche', 'spending', 'खर्च', 'खर्चा', 'व्यय'],
        'enquiry' => ['enquiry', 'inquiry', 'enquiries', 'inquiries', 'puchtach', 'पूछताछ', 'इन्क्वायरी'],
        'exam' => ['exam', 'exams', 'preparing', 'preparation', 'taiyari', 'tayari', 'परीक्षा', 'एग्जाम', 'तैयारी'],
        'floor' => ['floor', 'floors', 'manzil', 'फ्लोर', 'मंज़िल', 'मंजिल'],
        'details' => ['detail', 'details', 'profile', 'info', 'information', 'jankari', 'history', 'जानकारी', 'डिटेल', 'प्रोफाइल'],
        'top' => ['top', 'highest', 'most', 'sabse', 'सबसे'],
        'payment' => ['fee', 'fees', 'payment', 'payments', 'paid', 'jama', 'deposit', 'फीस', 'भुगतान', 'पेमेंट', 'जमा'],
        'frozen' => ['freeze', 'frozen', 'hold', 'paused', 'फ्रीज'],
        'show' => ['show', 'tell', 'give', 'batao', 'bata', 'batayein', 'batayiye', 'bataiye', 'btao', 'dikhao', 'dikha', 'dikhaiye', 'dekho', 'display', 'get', 'check', 'find', 'search', 'बताओ', 'बताइए', 'बताइये', 'बताएं', 'दिखाओ', 'दिखाइए', 'दिखाएं', 'देखो'],
        'last' => ['last', 'previous', 'prev', 'pichhle', 'pichle', 'pichhla', 'pichla', 'past', 'पिछले', 'पिछला', 'बीते', 'गत'],
        'next' => ['next', 'agle', 'agla', 'aane', 'upcoming', 'अगले', 'अगला', 'आगामी'],
        'pronoun' => ['unka', 'unki', 'unke', 'iska', 'iski', 'uska', 'uski', 'wo', 'woh', 'uska', 'him', 'her', 'his', 'them', 'उनका', 'उनकी', 'इसका', 'उसका', 'वो'],
    ];

    /** words that carry no meaning for us (also keeps them out of the "name" guess) */
    private const STOP = ['limo', 'bhai', 'sir', 'madam', 'please', 'pls', 'plz', 'ji', 'yaar', 'ek', 'ye', 'yeh', 'library', 'libraro', 'hamari', 'hamara', 'aap', 'tum', 'tu', 'me', 'mein', 'mai', 'main', 'hum',
        'ko', 'kar', 'kr', 'ke', 'ka', 'ki', 'se', 'par', 'pe', 'ho', 'hai', 'hain', 'tha', 'thi', 'the', 'hoga', 'hogi', 'hoge', 'hue', 'hua', 'hui', 'wale', 'wala', 'wali', 'aur', 'and', 'or', 'for', 'to',
        'a', 'an', 'of', 'in', 'on', 'at', 'is', 'are', 'was', 'were', 'be', 'can', 'could', 'would', 'will', 'do', 'does', 'did', 'i', 'you', 'we', 'what', 'which', 'whose', 'how', 'many', 'much', 'me', 'my',
        'our', 'your', 'all', 'any', 'some', 'only', 'just', 'the', 'this', 'that', 'it', 'with', 'about', 'kya', 'kyu', 'kyun', 'kab', 'kahan', 'kitna', 'iss', 'is', 'mera', 'meri', 'mere', 'apna', 'apni', 'karo', 'kare', 'karein', 'karna',
        'abhi', 'now', 'there', 'here', 'liye', 'lie', 'bhi', 'toh', 'to', 'na', 'nahi', 'koi', 'sab', 'sabhi', 'kisko', 'kisi', 'kis', 'dena', 'do', 'de', 'dijiye', 'jara', 'zara', 'thoda', 'batado', 'bataye',
        'में', 'का', 'की', 'के', 'है', 'हैं', 'हो', 'से', 'को', 'और', 'कर', 'करें', 'करो', 'क्या', 'मुझे', 'मेरी', 'मेरा', 'हमारी', 'हमारे', 'अभी', 'यहाँ', 'पर', 'था', 'थी', 'वाले', 'वाला', 'कृपया', 'जी', 'भाई'];

    private const MONTHS = ['jan' => 1, 'january' => 1, 'feb' => 2, 'february' => 2, 'mar' => 3, 'march' => 3, 'apr' => 4, 'april' => 4, 'may' => 5, 'jun' => 6, 'june' => 6, 'jul' => 7, 'july' => 7,
        'aug' => 8, 'august' => 8, 'sep' => 9, 'sept' => 9, 'september' => 9, 'oct' => 10, 'october' => 10, 'nov' => 11, 'november' => 11, 'dec' => 12, 'december' => 12];

    /** Latin words that tell us a Latin-script question is Hinglish */
    private const HINGLISH = ['kitna', 'kitne', 'kitni', 'hai', 'hain', 'aaj', 'kal', 'batao', 'bata', 'dikhao', 'ka', 'ki', 'ke', 'mein', 'me', 'kya', 'nahi', 'kaise', 'kaun', 'bakaya', 'baki', 'seet', 'khali', 'naya', 'naye', 'mahine', 'mahina',
        'hafte', 'din', 'dino', 'kamai', 'wala', 'wale', 'karo', 'karein', 'ho', 'hoga', 'abhi', 'sabse', 'pichhle', 'pichle', 'agle', 'liye', 'aur', 'mujhe', 'mera', 'meri'];

    private static ?array $inverse = null;

    private static function inverse(): array
    {
        if (self::$inverse === null) {
            self::$inverse = [];
            foreach (self::LEX as $canon => $words) {
                foreach ($words as $w) {
                    self::$inverse[mb_strtolower($w)] = $canon;
                }
            }
        }

        return self::$inverse;
    }

    /** A token to its canonical word, allowing one or two typos for longer words */
    private static function canon(string $tok): ?string
    {
        $inv = self::inverse();
        if (isset($inv[$tok])) {
            return $inv[$tok];
        }
        $len = mb_strlen($tok);
        if ($len < 5 || preg_match('/[\x{0900}-\x{097F}]/u', $tok) || ctype_digit($tok)) {
            return null;
        }
        $max = $len >= 9 ? 2 : 1;
        $best = null;
        $bestD = $max + 1;
        foreach ($inv as $word => $canon) {
            if (strlen($word) < 5 || abs(strlen($word) - strlen($tok)) > $max || preg_match('/[^\x00-\x7F]/', $word)) {
                continue;
            }
            $d = levenshtein($tok, $word);
            if ($d < $bestD) {
                $best = $canon;
                $bestD = $d;
            }
        }

        return $bestD <= $max ? $best : null;
    }

    public static function analyze(string $raw, ?Carbon $now = null): array
    {
        $now = $now ?: Carbon::now();
        $text = mb_strtolower(trim($raw));
        $text = preg_replace('/\s+/u', ' ', $text);
        $isHindi = (bool) preg_match('/[\x{0900}-\x{097F}]/u', $text);

        preg_match_all('/[\p{L}\p{M}\p{N}]+/u', $text, $m);
        $words = $m[0];

        $canon = [];
        $leftover = [];
        foreach ($words as $w) {
            $c = self::canon($w);
            if ($c) {
                $canon[$c] = true;
            } elseif (! in_array($w, self::STOP, true) && ! ctype_digit($w) && mb_strlen($w) >= 2 && ! isset(self::MONTHS[$w])) {
                $leftover[] = $w;
            }
        }
        // "how to" / "how do I" is a how-to question; "how many / how much" is a count
        if (preg_match('/\bhow (to|do|can|should)\b/u', $text)) {
            $canon['howto'] = true;
        }
        if (preg_match('/\bhow (many|much)\b/u', $text)) {
            $canon['count'] = true;
        }
        if (preg_match('/\bwhat can you do\b|\byou (can )?do\b|kya kar sakte/u', $text)) {
            $canon['help'] = true;
        }

        $hinglish = ! $isHindi && count(array_intersect($words, self::HINGLISH)) > 0;
        $lang = $isHindi ? 'hi' : ($hinglish ? 'hl' : 'en');

        return [
            'raw' => $raw,
            'text' => $text,
            'lang' => $lang,
            'c' => $canon,
            'left' => $leftover,
            'name' => trim(implode(' ', $leftover)),
            'n' => self::number($text),
            'period' => self::period($text, $canon, $now),
            'mobile' => preg_match('/(?<!\d)([6-9]\d{9})(?!\d)/', $text, $mm) ? $mm[1] : null,
            'learner_no' => preg_match('/\b(ln\d{3,})\b/i', $text, $ml) ? strtoupper($ml[1]) : null,
            'seat' => preg_match('/(?:seat|सीट)\s*(?:no\.?|number|#|नंबर)?\s*[:\-]?\s*(\d{1,4})\b/u', $text, $ms) ? (int) $ms[1] : null,
        ];
    }

    private static function number(string $text): ?int
    {
        $t = preg_replace('/(?<!\d)[6-9]\d{9}(?!\d)/', ' ', $text);       // mobile numbers
        $t = preg_replace('/\b\d{1,2}[\/\-.]\d{1,2}([\/\-.]\d{2,4})?\b/', ' ', $t); // dates
        $t = preg_replace('/\bln\d+\b/i', ' ', $t);

        return preg_match('/(?<!\d)(\d{1,4})(?!\d)/', $t, $m) ? (int) $m[1] : null;
    }

    /** @return array{key:string, from:Carbon, to:Carbon, label:string, explicit:bool}|null */
    private static function period(string $t, array $c, Carbon $now): ?array
    {
        $day = fn (Carbon $d, string $key, string $label) => ['key' => $key, 'from' => $d->copy()->startOfDay(), 'to' => $d->copy()->endOfDay(), 'label' => $label, 'explicit' => true];
        $range = fn (Carbon $a, Carbon $b, string $key, string $label) => ['key' => $key, 'from' => $a->copy()->startOfDay(), 'to' => $b->copy()->endOfDay(), 'label' => $label, 'explicit' => true];

        $unitMonth = '(?:month|mahine|mahina|महीने|महीना|माह)';
        $unitWeek = '(?:week|hafte|hafta|haftey|हफ्ते|हफ्ता|सप्ताह)';
        $unitDay = '(?:days?|din|dino|dinon|दिन|दिनों)';
        $prev = '(?:last|previous|pichhle|pichle|pichhla|pichla|पिछले|पिछला|beete|बीते)';
        $cur = '(?:this|is|iss|current|इस)';
        $nxt = '(?:next|agle|agla|aane wale|अगले|अगला)';

        if (preg_match("/$prev\s*(\d{1,3})\s*$unitDay/u", $t, $m)) {
            return $range($now->copy()->subDays((int) $m[1] - 1), $now, 'last_n', 'last ' . $m[1] . ' days');
        }
        if (preg_match("/$nxt\s*(\d{1,3})\s*$unitDay/u", $t, $m) || preg_match("/(?<![\d\/.\-])(\d{1,3})\s*$unitDay\b/u", $t, $m)) {
            return $range($now, $now->copy()->addDays((int) $m[1]), 'next_n', 'next ' . $m[1] . ' days');
        }
        if (preg_match("/$prev\s*$unitMonth/u", $t)) {
            $a = $now->copy()->subMonthNoOverflow()->startOfMonth();

            return $range($a, $a->copy()->endOfMonth(), 'last_month', $a->format('F Y'));
        }
        if (preg_match("/$cur\s*$unitMonth/u", $t) || (isset($c['month']) && ! isset($c['last']) && ! isset($c['next']))) {
            return $range($now->copy()->startOfMonth(), $now->copy()->endOfMonth(), 'this_month', $now->format('F Y'));
        }
        if (preg_match("/$prev\s*$unitWeek/u", $t)) {
            $a = $now->copy()->subWeek()->startOfWeek();

            return $range($a, $a->copy()->endOfWeek(), 'last_week', 'last week');
        }
        if (preg_match("/$cur\s*$unitWeek/u", $t) || (isset($c['week']) && ! isset($c['next']))) {
            return $range($now->copy()->startOfWeek(), $now->copy()->endOfWeek(), 'this_week', 'this week');
        }
        if (preg_match("/$nxt\s*$unitWeek/u", $t)) {
            return $range($now, $now->copy()->addDays(7), 'next_n', 'next 7 days');
        }
        if (preg_match('/\b(\d{1,2})\s*(jan|january|feb|february|mar|march|apr|april|may|jun|june|jul|july|aug|august|sep|sept|september|oct|october|nov|november|dec|december)\b(?:\s*(\d{4}))?/u', $t, $m)) {
            $d = Carbon::create(isset($m[3]) ? (int) $m[3] : $now->year, self::MONTHS[$m[2]], min(28, max(1, (int) $m[1])));
            $d->day = min((int) $m[1], $d->daysInMonth);

            return $day($d, 'date', $d->format('j M Y'));
        }
        if (preg_match('/\b(jan|january|feb|february|mar|march|apr|april|may|jun|june|jul|july|aug|august|sep|sept|september|oct|october|nov|november|dec|december)\b(?:\s*(\d{4}))?/u', $t, $m)) {
            $a = Carbon::create(isset($m[2]) ? (int) $m[2] : $now->year, self::MONTHS[$m[1]], 1)->startOfMonth();

            return $range($a, $a->copy()->endOfMonth(), 'month_name', $a->format('F Y'));
        }
        if (preg_match('/(?<!\d)(\d{1,2})[\/\-.](\d{1,2})(?:[\/\-.](\d{2,4}))?(?!\d)/', $t, $m)) {
            $y = isset($m[3]) ? (int) $m[3] : $now->year;
            $y = $y < 100 ? 2000 + $y : $y;
            if ((int) $m[2] >= 1 && (int) $m[2] <= 12 && (int) $m[1] >= 1 && (int) $m[1] <= 31) {
                $d = Carbon::create($y, (int) $m[2], 1);
                $d->day = min((int) $m[1], $d->daysInMonth);

                return $day($d, 'date', $d->format('j M Y'));
            }
        }
        if (isset($c['yesterday']) || preg_match('/\b(beete kal|bite kal)\b|बीते कल|पिछले कल/u', $t)) {
            return $day($now->copy()->subDay(), 'yesterday', 'yesterday');
        }
        if (isset($c['tomorrow'])) {
            return $day($now->copy()->addDay(), 'tomorrow', 'tomorrow');
        }
        if (isset($c['today'])) {
            return $day($now, 'today', 'today');
        }
        if (isset($c['kal'])) { // Hindi "kal" is yesterday or tomorrow: the skill decides
            return ['key' => 'kal', 'from' => $now->copy()->subDay()->startOfDay(), 'to' => $now->copy()->subDay()->endOfDay(), 'label' => 'kal', 'explicit' => true];
        }

        return null;
    }
}
