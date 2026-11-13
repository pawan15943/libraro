<?php

namespace App\Services\Ai;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Limo Support mode — solves a library owner's problem step by step.
 *
 *  1. understands the problem (free text in English / Hinglish / Hindi, or a topic button);
 *  2. checks the library's own data for the real cause (e.g. "Rahul's plan ends on 20 Nov,
 *     Renew shows from 15 Nov") — see SupportPlaybooks for the rules of every operation;
 *  3. gives the fix as numbered steps, with links to the right page;
 *  4. "Still not working?" → deeper checks → hands over to the Libraro team with a ready summary.
 *
 * Used by LibraroAiService (shares its language, matching, data and reply helpers).
 * Buttons send short codes: "#cat <topic>", "#pb <playbook>", "#solved", "#more", "#human", "#home".
 */
trait SupportDesk
{
    use SupportChecks;

    private const SUPPORT_PHONE = '918114479678';

    private const SUPPORT_PHONE_LABEL = '+91 81144 79678';

    private function supportAnswer(string $raw): array
    {
        $code = trim(mb_strtolower($raw));
        $ctx = $this->lastContext()['support'] ?? null;

        if (preg_match('/^#cat\s+([a-z_]+)/', $code, $m)) {
            return $this->supportCategory($m[1]);
        }
        if (preg_match('/^#pb\s+([a-z_]+)/', $code, $m)) {
            return $this->supportPlaybook($m[1], $ctx['learner_id'] ?? null, 0, $ctx['q'] ?? null);
        }
        if ($code === '#home') {
            return $this->supportHome();
        }
        if ($code === '#human') {
            return $this->supportEscalate($ctx);
        }
        if ($code === '#solved') {
            return $this->supportSolved($ctx);
        }
        if ($code === '#more') {
            return ! empty($ctx['id'])
                ? $this->supportPlaybook($ctx['id'], $ctx['learner_id'] ?? null, ($ctx['stage'] ?? 0) + 1, $ctx['q'] ?? null)
                : $this->supportHome();
        }

        // A new problem described in words always starts fresh — even if it contains
        // "nahi ho raha" or "ho gaya" ("galti se delete ho gaya" is a problem, not a thank-you)
        [$id, $score] = $this->bestPlaybook();
        $words = count(preg_split('/\s+/u', trim($this->q)) ?: []);
        $sameProblem = $id && ! empty($ctx['id']) && $id === $ctx['id'];
        if ($id && $score >= 3 && ! ($sameProblem && $this->saysNotSolved() && ! $this->supportFindLearner())) {
            return $this->supportPlaybook($id, $this->supportFindLearner() ?? ($sameProblem ? ($ctx['learner_id'] ?? null) : null), 0, $raw);
        }

        // Short replies to the previous step: "ho gaya" / "abhi bhi nahi hua"
        if (! empty($ctx['id']) && $words <= 6) {
            if ($this->saysNotSolved()) {
                return $this->supportPlaybook($ctx['id'], $ctx['learner_id'] ?? null, ($ctx['stage'] ?? 0) + 1, $ctx['q'] ?? null);
            }
            if ($this->saysSolved()) {
                return $this->supportSolved($ctx);
            }
        }

        // A learner's name right after Limo asked "which learner?"
        if ($ctx && ($ctx['await'] ?? null) === 'learner' && ($learnerId = $this->supportFindLearner())) {
            return $this->supportPlaybook($ctx['id'], $learnerId, 0, $ctx['q'] ?? $raw);
        }

        // Not a known problem — answer like Ask mode when Limo can, inside the support flow
        $answer = $this->answer($raw);
        if (! in_array($answer['intent'], ['unknown', 'help', 'denied', 'search', 'error'], true) && $this->looksLikeQuestion()) {
            $answer['choices'] = [
                $this->choice($this->t('That helped', 'इससे मदद मिली'), '#solved', 'good', 'fa-circle-check'),
                $this->choice($this->t('Choose a problem topic', 'समस्या का विषय चुनें'), '#home', 'violet', 'fa-list'),
                $this->choice($this->t('Talk to Libraro support', 'Libraro सपोर्ट से बात करें'), '#human', 'neutral', 'fa-headset'),
            ];

            return $answer;
        }

        return $this->supportHome(true, $raw);
    }

    /*
    |--------------------------------------------------------------------------
    | Understanding the problem
    |--------------------------------------------------------------------------
    */

    /** Best matching playbook for the question: [id, score]. */
    private function bestPlaybook(): array
    {
        $best = [null, 0];
        foreach (SupportPlaybooks::all() as $id => $pb) {
            $score = $this->phraseScore($pb['keys']);
            if ($score > 0 && $this->has('howto')) {
                $score += 0.5; // "kaise / why / kyu" fits a problem description
            }
            if ($score > $best[1]) {
                $best = [$id, $score];
            }
        }

        return $best;
    }

    /** Ask-mode answers are offered in Support only for questions that name a library thing. */
    private function looksLikeQuestion(): bool
    {
        foreach (['collection', 'due', 'seat', 'vacant', 'admission', 'expire', 'attendance', 'expense', 'profit', 'shift', 'floor', 'subscription', 'price', 'refund', 'renewal', 'learner', 'locker', 'enquiry', 'staff', 'howto'] as $c) {
            if ($this->has($c)) {
                return true;
            }
        }

        return false;
    }

    private function saysSolved(): bool
    {
        return (bool) preg_match('/ (solved|fixed|resolved|worked|working now|it works|done|thanks|thank you|ho gaya|ho gya|hogaya|sahi ho gaya|chal gaya|chal raha|theek ho gaya|thik ho gaya|ठीक हो गया|हो गया|चल गया|हल हो गया|धन्यवाद) /u', $this->q)
            && ! $this->saysNotSolved();
    }

    private function saysNotSolved(): bool
    {
        return (bool) preg_match('/ (not working|still|nahi hua|nahi ho raha|nhi hua|abhi bhi|ab bhi|same problem|didn t work|doesn t work|no luck|नहीं हुआ|नहीं हो रहा|अभी भी|अब भी) /u', $this->q);
    }

    /**
     * A learner of this branch named in the problem ("ANUKUL Maurya ka renew button nahi dikh raha").
     * Problem words are removed first; a two-word name is tried before single words.
     */
    private function supportFindLearner(): ?int
    {
        static $known = null;
        if ($known === null) {
            $known = array_flip($this->vocab());
            foreach (SupportPlaybooks::all() as $pb) {
                foreach ($pb['keys'] as $k) {
                    foreach (explode(' ', trim($this->normalise($k))) as $w) {
                        $known[$w] = true;
                    }
                }
            }
            foreach (['ka', 'ki', 'ke', 'ko', 'se', 'me', 'mein', 'par', 'pe', 'hai', 'hain', 'tha', 'thi', 'nahi', 'nhi', 'raha', 'rahi', 'rahe', 'gaya', 'gayi', 'gya', 'kar', 'karna', 'kya', 'kyu', 'kyun', 'aur', 'bhi', 'wala', 'wali', 'koi', 'kuch', 'sab', 'abhi', 'please', 'the', 'my', 'is', 'not', 'for', 'of', 'his', 'her', 'student', 'learner', 'button', 'option', 'icon', 'showing', 'working', 'की', 'का', 'के', 'को', 'से', 'में', 'पर', 'है', 'हैं', 'नहीं', 'रहा', 'रही', 'गया', 'गई', 'छात्र', 'बटन'] as $w) {
                $known[$w] = true;
            }
        }

        $words = array_values(array_filter(explode(' ', trim($this->q)), fn ($w) => mb_strlen($w) >= 3 && ! isset($known[$w]) && ! preg_match('/\d/', $w)));
        if (! $words) {
            return null;
        }
        $candidates = [];
        for ($i = 0; $i < count($words) - 1; $i++) {
            $candidates[] = $words[$i].' '.$words[$i + 1];
        }
        $candidates = array_merge($candidates, $words);

        foreach ([true, false] as $onlyLive) { // prefer learners that are not deleted
            foreach ($candidates as $c) {
                $like = addcslashes($c, '%_\\');
                $id = DB::table('learners')->where('branch_id', $this->branchId)
                    ->when($onlyLive, fn ($q) => $q->whereNull('deleted_at'))
                    ->where(fn ($q) => $q->where('name', 'like', $like.'%')->orWhere('name', 'like', '% '.$like.'%'))
                    ->orderByDesc('status')->orderByDesc('id')
                    ->value('id');
                if ($id) {
                    return (int) $id;
                }
            }
        }

        return null;
    }
    /*
    |--------------------------------------------------------------------------
    | Replies
    |--------------------------------------------------------------------------
    */

    private function choice(string $label, string $query, string $tone = 'neutral', ?string $icon = null): array
    {
        return ['label' => $label, 'query' => $query, 'tone' => $tone, 'icon' => $icon];
    }

    private function choiceLink(string $label, string $url, string $tone = 'neutral', ?string $icon = null): array
    {
        return ['label' => $label, 'url' => $url, 'tone' => $tone, 'icon' => $icon];
    }

    /** Topics to choose from (also shown when Limo did not understand the problem). */
    private function supportHome(bool $notUnderstood = false, ?string $raw = null): array
    {
        $blocks = [];
        if ($notUnderstood) {
            $blocks[] = $this->note($this->t(
                'I could not match this to a known problem yet. Pick the topic closest to it, or describe it with the page / button name — for example “renew button not showing for Rahul”.',
                'मैं इसे किसी जानी हुई समस्या से नहीं मिला पाया। सबसे मिलता-जुलता विषय चुनें, या पेज / बटन के नाम के साथ बताएं — जैसे “राहुल का रिन्यू बटन नहीं दिख रहा”।'
            ), 'info');
        } else {
            $blocks[] = $this->text($this->t('Which part of Libraro is the problem in?', 'समस्या Libraro के किस हिस्से में है?'));
        }

        $choices = [];
        foreach (SupportPlaybooks::categories() as $key => $c) {
            $choices[] = $this->choice($this->t($c[1], $c[2]), '#cat '.$key, 'neutral', $c[0]);
        }
        $choices[] = $this->choice($this->t('Talk to Libraro support', 'Libraro सपोर्ट से बात करें'), '#human', 'violet', 'fa-headset');

        if ($raw) {
            $this->newContext['support'] = ['id' => null, 'stage' => 0, 'q' => mb_substr($raw, 0, 200)];
        }

        $r = $this->reply($notUnderstood ? 'support_unknown' : 'support_home', 'fa-life-ring',
            $notUnderstood ? $this->t('Let\'s find the problem', 'चलिए समस्या ढूंढते हैं') : $this->t('Limo Support', 'Limo सपोर्ट'), $blocks);
        $r['choices'] = $choices;

        return $r;
    }

    private function supportCategory(string $key): array
    {
        $cats = SupportPlaybooks::categories();
        if (! isset($cats[$key])) {
            return $this->supportHome();
        }
        $choices = [];
        foreach (SupportPlaybooks::all() as $id => $pb) {
            if ($pb['cat'] === $key) {
                $choices[] = $this->choice($this->t($pb['title'][0], $pb['title'][1]), '#pb '.$id, 'neutral', 'fa-circle-question');
            }
        }
        $choices[] = $this->choice($this->t('Something else', 'कुछ और'), '#human', 'violet', 'fa-headset');

        $r = $this->reply('support_topic', $cats[$key][0], $this->t($cats[$key][1], $cats[$key][2]), [
            $this->text($this->t('What exactly is happening? Pick one — or type it in your own words.', 'असल में क्या हो रहा है? एक चुनें — या अपने शब्दों में लिखें।')),
        ]);
        $r['choices'] = $choices;

        return $r;
    }

    /**
     * One problem: what Limo found in the data, then the steps.
     * Stage 0 = main fix, 1 = deeper checks, 2+ = hand over to the Libraro team.
     */
    private function supportPlaybook(?string $id, ?int $learnerId, int $stage, ?string $question): array
    {
        $pb = $id ? (SupportPlaybooks::all()[$id] ?? null) : null;
        if (! $pb) {
            return $this->supportHome();
        }
        if ($stage >= 2 || ($stage === 1 && empty($pb['more']))) {
            return $this->supportEscalate(['id' => $id, 'learner_id' => $learnerId, 'q' => $question, 'stage' => $stage]);
        }

        $title = $this->t($pb['title'][0], $pb['title'][1]);
        $blocks = [];
        $await = null;

        if ($stage === 0) {
            // 1) Check the data for the real cause
            $learner = $learnerId ? $this->supportLearnerFacts($learnerId) : null;
            if (($pb['needs'] ?? null) === 'learner' && ! $learner) {
                $await = 'learner';
            }
            $checks = [];
            if (! empty($pb['check']) && method_exists($this, $pb['check'])) {
                $checks = $this->{$pb['check']}($learner) ?: [];
            }
            // Libraro plan / staff permission / branch "Hide …" toggle can hide the option on their own
            $checks = array_merge($this->permissionChecks($pb['perm'] ?? null, $pb['toggle'] ?? null), $checks);
            if ($checks) {
                $blocks[] = ['type' => 'checks', 'caption' => $learner
                    ? $this->t('What I found for '.$learner['name'], $learner['name'].' के लिए मुझे यह मिला')
                    : $this->t('What I checked', 'मैंने यह जांचा'), 'items' => $checks];
            }
            if ($await) {
                $blocks[] = $this->note($this->t(
                    'Tell me the learner\'s name and I will check their record and tell you the exact reason.',
                    'छात्र का नाम बताइए — मैं उनका रिकॉर्ड देखकर सही वजह बताऊंगा।'
                ), 'info');
            }

            // 2) The fix
            $blocks[] = ['type' => 'guide', 'caption' => null,
                'en' => array_column($pb['steps'], 0), 'hi' => array_column($pb['steps'], 1)];
            if (! empty($pb['why'])) {
                $blocks[] = $this->note($this->t($pb['why'][0], $pb['why'][1]), 'info');
            }
        } else {
            $blocks[] = $this->text($this->t('Let\'s check a few more things:', 'चलिए कुछ और चीज़ें जांचते हैं:'));
            $blocks[] = ['type' => 'guide', 'en' => array_column($pb['more'], 0), 'hi' => array_column($pb['more'], 1)];
        }

        $actions = [];
        foreach ($pb['links'] ?? [] as $l) {
            $actions[] = $this->link($this->t($l[1], $l[2]), $l[0], ($l[3] ?? null) === 'learner' && $learnerId ? ['id' => $learnerId] : null);
        }

        $choices = [
            $this->choice($this->t('Solved', 'हो गया'), '#solved', 'good', 'fa-circle-check'),
            $this->choice($stage === 0 ? $this->t('Still not working', 'अभी भी नहीं हो रहा') : $this->t('Still not solved', 'अभी भी हल नहीं हुआ'), '#more', 'bad', 'fa-circle-xmark'),
        ];
        if ($stage >= 1) {
            $choices[] = $this->choice($this->t('Talk to Libraro support', 'Libraro सपोर्ट से बात करें'), '#human', 'violet', 'fa-headset');
        }

        $this->newContext['support'] = ['id' => $id, 'stage' => $stage, 'learner_id' => $learnerId, 'await' => $await, 'q' => $question ? mb_substr($question, 0, 200) : null];
        if ($learnerId) {
            $this->newContext['learner_id'] = $learnerId;
        }

        $r = $this->reply('support', $pb['icon'] ?? 'fa-life-ring', ($stage ? $this->t('More checks · ', 'और जांच · ') : '').$title, $blocks, $actions,
            array_map(fn ($rid) => $this->t(SupportPlaybooks::all()[$rid]['title'][0], SupportPlaybooks::all()[$rid]['title'][1]), array_slice($pb['related'] ?? [], 0, 3)));
        $r['choices'] = $choices;

        return $r;
    }

    private function supportSolved(?array $ctx): array
    {
        $r = $this->reply('support_solved', 'fa-face-smile', $this->t('Great — glad it is solved!', 'बढ़िया — समस्या हल हो गई!'), [
            $this->text($this->t(
                'If this answer helped, tap 👍 below — it helps Limo get better. Anything else I can fix?',
                'अगर इस जवाब से मदद मिली तो नीचे 👍 दबाएं — इससे Limo और बेहतर बनता है। और कोई समस्या?'
            )),
        ]);
        $r['choices'] = [$this->choice($this->t('Another problem', 'दूसरी समस्या'), '#home', 'violet', 'fa-life-ring')];
        $this->newContext['support'] = null;

        return $r;
    }

    /** Hand over to the Libraro team with everything they need, ready to send. */
    private function supportEscalate(?array $ctx): array
    {
        $pb = ! empty($ctx['id']) ? (SupportPlaybooks::all()[$ctx['id']] ?? null) : null;
        $learner = ! empty($ctx['learner_id']) ? $this->supportLearnerFacts((int) $ctx['learner_id']) : null;
        $library = DB::table('libraries')->where('id', $this->libraryId)->first(['library_name', 'library_no']);

        $lines = array_filter([
            'Hello Libraro team, I need help.',
            'Library: '.($library->library_name ?? '—').($library?->library_no ? ' ('.$library->library_no.')' : ''),
            'Branch: '.$this->branchName(),
            $pb ? 'Problem: '.$pb['title'][0] : null,
            ! empty($ctx['q']) ? 'In my words: '.$ctx['q'] : null,
            $learner ? 'Learner: '.$learner['name'].' (ID '.$learner['id'].')' : null,
            $pb ? 'Tried with Limo: the suggested steps did not solve it.' : null,
        ]);
        $summary = implode("\n", $lines);

        $r = $this->reply('support_human', 'fa-headset', $this->t('Let the Libraro team fix it', 'Libraro टीम इसे ठीक करेगी'), [
            $this->text($this->t(
                'Sorry this is still not solved. Send this summary to the Libraro support team on WhatsApp — they will see exactly what is wrong, no need to explain again.',
                'माफ़ कीजिए, समस्या अभी भी हल नहीं हुई। यह सारांश WhatsApp पर Libraro सपोर्ट टीम को भेजें — उन्हें पूरी बात पता चल जाएगी, दोबारा समझाने की ज़रूरत नहीं।'
            )),
            ['type' => 'checks', 'caption' => $this->t('Summary that will be sent', 'भेजा जाने वाला सारांश'), 'items' => array_map(fn ($l) => ['tone' => 'info', 'text' => $l], array_values($lines))],
            $this->note($this->t('Support phone: '.self::SUPPORT_PHONE_LABEL, 'सपोर्ट फोन: '.self::SUPPORT_PHONE_LABEL), 'info'),
        ], array_filter([
            $this->link($this->t('Send feedback in the panel', 'पैनल से फीडबैक भेजें'), 'library.feedback'),
            $this->link($this->t('Video tutorials', 'वीडियो ट्यूटोरियल'), 'library.video-training'),
        ]));
        $r['choices'] = [
            $this->choiceLink($this->t('Send on WhatsApp', 'WhatsApp पर भेजें'), 'https://wa.me/'.self::SUPPORT_PHONE.'?text='.rawurlencode($summary), 'wa', 'fa-brands fa-whatsapp'),
            $this->choiceLink($this->t('Call support', 'सपोर्ट को कॉल करें'), 'tel:+'.self::SUPPORT_PHONE, 'neutral', 'fa-phone'),
            $this->choice($this->t('Another problem', 'दूसरी समस्या'), '#home', 'violet', 'fa-life-ring'),
        ];
        $this->newContext['support'] = null;

        return $r;
    }

    /*
    |--------------------------------------------------------------------------
    | Facts about one learner (used by the checks)
    |--------------------------------------------------------------------------
    */

    private function supportLearnerFacts(int $id): ?array
    {
        $l = DB::table('learners')->where('id', $id)->where('branch_id', $this->branchId)->first();
        if (! $l) {
            return null;
        }
        $today = Carbon::today();
        $details = DB::table('learner_detail as ld')
            ->leftJoin('plan_types as pt', 'pt.id', '=', 'ld.plan_type_id')
            ->leftJoin('plans as p', 'p.id', '=', 'ld.plan_id')
            ->where('ld.learner_id', $id)->whereNull('ld.deleted_at')
            ->orderByDesc('ld.plan_end_date')
            ->get(['ld.id', 'ld.plan_start_date', 'ld.plan_end_date', 'ld.status', 'ld.seat_no', 'ld.is_paid', 'ld.created_at', 'ld.join_date', 'pt.name as shift', 'p.name as plan']);

        $current = $details->first(fn ($d) => $d->plan_start_date <= $today->toDateString() && $d->plan_end_date >= $today->toDateString())
            ?? $details->first();
        $queued = $details->first(fn ($d) => $d->plan_start_date > $today->toDateString());
        $end = $current && $current->plan_end_date ? Carbon::parse($current->plan_end_date) : null;
        $firstBooking = $details->min('created_at');
        $extend = 0;
        try {
            $extend = (int) getExtendDays($this->branchId);
        } catch (\Throwable $e) {
        }

        $mobile = '';
        try {
            $mobile = preg_replace('/\D/', '', (string) decryptData((string) $l->mobile));
        } catch (\Throwable $e) {
        }

        return [
            'id' => (int) $l->id,
            'name' => (string) $l->name,
            'deleted' => $l->deleted_at !== null,
            'learner_status' => (int) $l->status,
            'frozen' => (int) ($l->frozen_status ?? 0) === 1,
            'no_expiry' => (int) ($l->no_expiry ?? 0) === 1,
            'mobile_ok' => strlen(preg_replace('/^91(?=\d{10}$)/', '', $mobile)) === 10,
            'has_plan' => (bool) $current,
            'detail_status' => $current ? (int) $current->status : null,
            'start' => $current?->plan_start_date ? Carbon::parse($current->plan_start_date) : null,
            'end' => $end,
            'days_left' => $end ? $today->diffInDays($end, false) : null,
            'ext_left' => $end ? $today->diffInDays($end->copy()->addDays($extend), false) : null,
            'extend_days' => $extend,
            'closed' => DB::table('learner_operations_log')->where('learner_id', $id)->where('operation', 'closeSeat')->exists(),
            'overdue' => DB::table('learner_transactions')->where('learner_id', $id)->whereNull('deleted_at')->where('pending_amount', '>', 0)->whereNotNull('due_date')->where('due_date', '<', $today->toDateString())->exists(),
            'booked_at' => $current && $current->created_at ? Carbon::parse($current->created_at) : null,
            'first_booked_at' => $firstBooking ? Carbon::parse($firstBooking) : null,
            'queued' => $queued ? ['start' => Carbon::parse($queued->plan_start_date), 'plan' => $queued->plan] : null,
            'seat' => $current->seat_no ?? null,
            'shift' => $current->shift ?? null,
            'plan' => $current->plan ?? null,
            'due' => (float) DB::table('learner_transactions')->where('learner_id', $id)->whereNull('deleted_at')->sum('pending_amount'),
        ];
    }

    private function d(?Carbon $c): string
    {
        return $c ? $c->format('j M Y') : '—';
    }
}
