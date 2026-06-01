<?php

namespace App\Actions;

use App\Events\LibraryRegistered;
use App\Models\Library;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Helpers\ReferralHelper;
use App\Models\LibraryReferral;
use App\Services\ReferralRewardService;
use Illuminate\Validation\ValidationException;

class RegisterLibrary
{
    public function __construct(private ReferralRewardService $rewardService)
    {
    }

    public function handle(array $data): Library
    {
       
        return DB::transaction(function () use ($data) {

          
            $password = trim($data['password']);
            $data['original_password'] = $password;
            $data['password'] = bcrypt($password);
            
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

                if (! $referrer) {
                    throw ValidationException::withMessages([
                        'referral_code' => ['Invalid referral code.'],
                    ]);
                }

                if ((int) $referrer->id === (int) $library->id) {
                    throw ValidationException::withMessages([
                        'referral_code' => ['Self referral is not allowed.'],
                    ]);
                }

                $library->referred_by = $referrer->id;
                $library->save();

                $referral = LibraryReferral::create([
                    'referrer_library_id' => $referrer->id,
                    'referred_library_id' => $library->id,
                    'referral_code' => $referral_code,
                    'referral_type' => $referral_type ?? 'code',
                    'status' => 'completed'
                ]);

                $this->rewardService->processReferralReward((int) $referral->id);
            }
        
            // Fire Event
            event(new LibraryRegistered($library));

            return $library;
        });
    }
}
