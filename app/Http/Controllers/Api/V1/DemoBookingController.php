<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\BookingEnquiryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class DemoBookingController extends Controller
{
    public function store(Request $request, BookingEnquiryService $bookingEnquiryService)
    {
       
        $branch_id=getCurrentBranch();
        try {
            $booking = $bookingEnquiryService->createDemoBooking($request, (int) $branch_id);

            return response()->json([
                'status' => true,
                'message' => 'Booking enquiry created successfully.',
                
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('API DEMO BOOKING STORE CRASH', [
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
