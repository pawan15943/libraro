<?php

namespace App\Helpers;

use Illuminate\Support\Str;

class ReferralHelper
{
    public static function generateLibraryReferralCode($libraryId)
    {
        return 'LIB-' . $libraryId . '-' . strtoupper(Str::random(4));
    }
}
