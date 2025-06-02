<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Crypt;

class LicenseCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        
        $filePath = storage_path('framework/cache/.hidden_license_key');

        if (!File::exists($filePath)) {
            abort(403, 'License key not found');
        }

        $storedData = File::get($filePath);
        // $storedKey = Crypt::decryptString($storedKeyEncrypted);
        $currentMacAddress = $this->getMacAddress();
        $generatedKey = $this->generateLicenseKey($currentMacAddress);
 
        
       
        if ($storedData !== $generatedKey) {
            abort(403, 'Invalid license key or MAC address');
        }

        return $next($request);
    }

    private function getMacAddress()
    {
        $output = exec('getmac'); // For Windows
        $macAddress = substr($output, 0, 17); // Extract the first 17 characters
        return $macAddress;
    }

    private function generateLicenseKey(string $macAddress,$key='125352-ABXG56-H7Y5F5-45IJNN')
    {
        return hash('sha256', $macAddress . $key);
    }
}
