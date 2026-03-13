<?php

namespace App\Actions;

use App\Events\LibraryRegistered;
use App\Models\Library;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Helpers\ReferralHelper;
use App\Models\LibraryReferral;

class RegisterLibrary
{
    public function handle(array $data): Library
    {
       
        return DB::transaction(function () use ($data) {

          
            $data['original_password'] = $data['password'];
              
            $data['password'] = bcrypt($data['password']);
            
            $data['slug'] = Str::slug($data['library_name']) . '-' . Str::lower(Str::random(6));

            $deviceType = request()->header('device-type'); 
            $deviceId   = request()->header('device-id');   

            $referral_code   = $data['referral_code'] ?? null;
            $referral_type   = $data['referral_type'] ?? null;

            unset($data['referral_code'],$data['referral_type']);
            // Create Library
           
            $library = Library::create($data);
           
           
          
            // Generate OTP & Referral Code
            // $library->email_otp = rand(100000, 999999);
            $library->email_otp = 123456;
            $library->referral_code = ReferralHelper::generateLibraryReferralCode($library->id);
            $library->save();
            
             // Save Device
           if (!empty($deviceType) && !empty($deviceId)) {
           
                $library->devices()->updateOrCreate(
                    ['device_id' => $deviceId],
                    [
                        'device_type' => $deviceType,
                        'guard_name'  => 'library'
                    ]
                );
            }
            // Handle Referral
            if (!empty($referral_code)) {

                $referrer = Library::where('referral_code', $referral_code)->first();

                if ($referrer && $referrer->id !== $library->id) {

                    $library->referred_by = $referrer->id;
                    $library->save();

                    LibraryReferral::create([
                        'referrer_library_id' => $referrer->id,
                        'referred_library_id' => $library->id,
                        'referral_code' => $referral_code,
                        'referral_type' => $referral_type ?? 'code',
                        'status' => 'pending'
                    ]);
                }
            }
        
            // Fire Event
            event(new LibraryRegistered($library));

            return $library;
        });
    }
}