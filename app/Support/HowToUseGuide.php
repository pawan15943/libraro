<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The library owner's "How Libraro works" guide (table `how-to-use`).
 *
 * The page (library.how-to-use), the mobile API (GET api/v1/library/how-to-use) and Limo all
 * read the same rows. The content itself lives in HowToUseGuideContent and is written to the
 * table by the migration 2026_11_07_000002_refresh_how_to_use_guide.
 */
class HowToUseGuide
{
    /** key => [English, Hindi], in the order the page shows them */
    public static function categories(): array
    {
        return [
            'start' => ['Getting started', 'शुरुआत'],
            'booking' => ['Seat booking', 'सीट बुकिंग'],
            'learner' => ['Learner operations', 'छात्र ऑपरेशन'],
            'payments' => ['Payments & receipts', 'भुगतान व रसीद'],
            'attendance' => ['Attendance', 'हाजिरी'],
            'messages' => ['WhatsApp & notifications', 'WhatsApp व नोटिफिकेशन'],
            'masters' => ['Setup (masters)', 'सेटअप (मास्टर)'],
            'reports' => ['Dashboard & reports', 'डैशबोर्ड व रिपोर्ट'],
            'online' => ['Online booking & website', 'ऑनलाइन बुकिंग व वेबसाइट'],
            'learners_app' => ['Learner app, chat & study', 'लर्नर ऐप, चैट व पढ़ाई'],
            'limo' => ['Limo AI assistant', 'Limo AI सहायक'],
            'account' => ['Staff, settings & account', 'स्टाफ, सेटिंग व अकाउंट'],
        ];
    }

    /** All guide rows, by topic order then position. */
    public static function rows(): Collection
    {
        $q = DB::table('how-to-use');
        if (Schema::hasColumn('how-to-use', 'sort_order')) {
            $q->orderBy('sort_order');
        }

        return $q->orderBy('id')->get();
    }
}
