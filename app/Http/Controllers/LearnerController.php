<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Hour;
use App\Models\Learner;
use App\Models\LearnerDetail;
use App\Models\LearnerTransaction;
use App\Models\Plan;
use App\Models\Library;
use App\Models\Blog;
use App\Models\Branch;
use App\Models\PlanPrice;
use App\Models\PlanType;
use App\Models\Seat;
use App\Models\Suggestion;
use App\Models\LearnerFeedback;
use App\Models\Complaint;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use DB;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use App\Services\LearnerService;
use Exception;
use App\Traits\LearnerQueryTrait;
use Illuminate\Support\Facades\Auth;
use Log;
use Barryvdh\DomPDF\Facade\Pdf;


class LearnerController extends Controller
{
    use LearnerQueryTrait;
    protected $learnerService;

    public function __construct(LearnerService $learnerService)
    {
        $this->learnerService = $learnerService;
    }

    protected function validateCustomer(Request $request, array $additionalRules = [])
    {

        $baseRules = [

            'email' => [
                'required',
                'email',
                Rule::unique('learners')->where(function ($query) use ($request) {
                    return $query->where('branch_id', getCurrentBranch());
                }),
            ],
            'name' => 'required',
            'id_proof_file' => 'nullable|file|mimes:jpg,png,jpeg,webp|max:200',
            'mobile' => 'required|digits:10',
            'dob' => 'nullable|date',

            'plan_id' => 'required',
            'plan_type_id' => 'required',
            'plan_price_id' => 'required|numeric|min:0',

            'discount_amount' => 'nullable|numeric|min:0',
            'locker_amount' => [
                'nullable',
                'required_if:toggleFieldCheckbox,yes',
                'numeric'
            ],


            'due_date' => [
                'nullable',
                'date',
                function ($attribute, $value, $fail) use ($request) {
                    $planPrice = (float) $request->input('plan_price_id', 0);
                    $paid = (float) $request->input('paid_amount', 0);
                    $locker = (float) $request->input('locker_amount', 0);
                    $discount = (float) $request->input('discount_amount', 0);

                    $effectivePaid = $planPrice + $locker - $discount;
                    $pending =  $effectivePaid - $paid;

                    if ($pending > 0 && empty($value)) {
                        $fail('Due date is required if there is any pending amount.');
                    }
                }
            ],


        ];


        $rules = array_merge($baseRules, $additionalRules);

        return Validator::make($request->all(), $rules);
    }
    protected function dataUpdate()
    {
        Log::info('Starting dataUpdate function');

        $userUpdates = Learner::where('status', 1)->get();

        foreach ($userUpdates as $userUpdate) {
            $today = date('Y-m-d');

            \Log::info("Processing Learner ID: {$userUpdate->id} for status update.");

            $customerdatas = LearnerDetail::where('learner_id', $userUpdate->id)->where('status', 1)->get();

            \Log::info("Customer Details for Learner ID {$userUpdate->id}: ", $customerdatas->toArray());


            $extend_day = getExtendDays();
            \Log::info("Extend days: {$extend_day} for Library ID: " . getLibraryId());


            foreach ($customerdatas as $customerdata) {
                $planEndDateWithExtension = Carbon::parse($customerdata->plan_end_date)->addDays($extend_day);
                \Log::info("Learner ID {$userUpdate->id} Plan End Date with Extension: {$planEndDateWithExtension}. Today's Date: {$today}");

                // Check if learner has a future plan_end_date and a past plan_end_date
                $current_date = Carbon::today();
                $hasFuturePlan = LearnerDetail::where('learner_id', $userUpdate->id)
                    ->where('plan_end_date', '>', $current_date->copy()->addDays(5))->where('status', 0)
                    ->exists();
                $hasPastPlan = LearnerDetail::where('learner_id', $userUpdate->id)
                    ->where('plan_end_date', '<=', $current_date->copy()->addDays(5))
                    ->exists();

                $isRenewed = $hasFuturePlan && $hasPastPlan;

                // Log the renewal status
                \Log::info("Renewal Status for Learner ID {$userUpdate->id}: " . ($isRenewed ? 'Renewed' : 'Not Renewed'));


                if ($planEndDateWithExtension->lte($today)) {
                    $userUpdate->update(['status' => 0]);
                    $customerdata->update(['status' => 0]);
                    \Log::info("Updated Learner ID {$userUpdate->id} and Customer Data ID {$customerdata->id} to status 0.");
                } elseif ($isRenewed) {
                    LearnerDetail::where('learner_id', $userUpdate->id)->where('plan_start_date', '<=', $today)->where('plan_end_date', '>', $current_date->copy()->addDays(5))->update(['status' => 1]);
                    LearnerDetail::where('learner_id', $userUpdate->id)->where('plan_end_date', '<', $today)->update(['status' => 0]);
                } else {
                    $userUpdate->update(['status' => 1]);
                    LearnerDetail::where('learner_id', $userUpdate->learner_id)
                        ->where('status', 0)
                        ->where('plan_start_date', '<=', $today)
                        ->where('plan_end_date', '>', $today)
                        ->update(['status' => 1]);
                    \Log::info("Updated Learner ID {$userUpdate->id} to status 1.");
                }
            }
        }
    }



    protected function seat_availablity(Request $request)
    {

        $plan_type_id = $request->plan_type_id;
        $seat_id = $request->seat_id;

        if (!$seat_id) {
            $learnerData = Learner::where('id', $request->user_id)->first();
            $library_id = $learnerData->library_id;
            $seat = Seat::where('library_id', $library_id)->where('seat_no', $request->seat_no)->first();
            $seat_id = $seat->id;
        }


        $this->seat_availablity_update($seat_id, $plan_type_id);
    }
    protected function seat_availablity_update($seat_id, $plan_type_id)
    {
        $seat = Seat::where('id', $seat_id)->first();

        $available = 5;
        $day_type_id = PlanType::where('id', $plan_type_id)->select('day_type_id')->first();

        if ($seat->is_available == 1 && $day_type_id->day_type_id == 1) {

            $available = 5;
        } elseif ($seat->is_available == 1 && $day_type_id->day_type_id == 2) {

            $available = 2;
        } elseif ($seat->is_available == 1 && $day_type_id->day_type_id == 3) {

            $available = 3;
        } elseif ($seat->is_available == 1 && ($day_type_id->day_type_id == 4 || $day_type_id->day_type_id == 5 || $day_type_id->day_type_id == 6 || $day_type_id->day_type_id == 7)) {

            $available = 4;
        } elseif ($seat->is_available == 2 && $day_type_id->day_type_id == 3) {

            $available = 5;
        } elseif ($seat->is_available == 2 && ($day_type_id->day_type_id == 6 || $day_type_id->day_type_id == 7)) {

            $available = 4;
        } elseif ($seat->is_available == 3 && ($day_type_id->day_type_id == 4 || $day_type_id->day_type_id == 5)) {

            $available = 4;
        } elseif ($seat->is_available == 3 && $day_type_id->day_type_id == 2) {

            $available = 5;
        } elseif ($seat->is_available == 4 && ($day_type_id->day_type_id == 2 || $day_type_id->day_type_id == 3 || $day_type_id->day_type_id == 4 || $day_type_id->day_type_id == 5 || $day_type_id->day_type_id == 6 || $day_type_id->day_type_id == 5)) {
            $available = 4;
        }

        // Update seat availability
        $update = Seat::where('id', $seat_id)->update(['is_available' => $available]);
    }


    public function index()
    {
        $this->dataUpdate();
        $users = $this->getLearnersByLibrary()->where('learners.status', 1)->where('learner_detail.library_id', getLibraryId());

        $count_fullday = $this->getLearnersByLibrary()->leftJoin('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')->where('learner_detail.library_id', getLibraryId())->where('plan_types.day_type_id', 1)->where('learners.status', 1)->count();
        $count_firstH = $this->getLearnersByLibrary()->leftJoin('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')->where('learner_detail.library_id', getLibraryId())->where('plan_types.day_type_id', 2)->where('learners.status', 1)->count();
        $count_secondH = $this->getLearnersByLibrary()->leftJoin('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')->where('learner_detail.library_id', getLibraryId())->where('plan_types.day_type_id', 3)->where('learners.status', 1)->count();
        $count_hourly = $this->getLearnersByLibrary()->leftJoin('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')->where('learner_detail.library_id', getLibraryId())->whereIn('plan_types.day_type_id', [4, 5, 6, 7])->where('learners.status', 1)->count();

        $not_available = getUnavailableSeatCount();
        $available = getAvailableSeatCount();
        $availableseats = $this->learnerService->getAvailableSeats();

        return view('learner.seat', compact('availableseats', 'users',  'count_fullday', 'count_firstH', 'count_secondH', 'available', 'not_available', 'count_hourly'));
    }
    //learner store seat and without seat
    public function learnerStore(Request $request)
    {
        $additionalRules = [
            'payment_mode'     => 'required',
            'plan_start_date'  => 'required|date',
            'paid_amount'      => 'required',

        ];

        if ($request->general_seat != 'yes') {
            $additionalRules['seat_no'] = 'required|integer';
        }

        $validator = $this->validateCustomer($request, $additionalRules);

        $exists = Learner::where('branch_id', getCurrentBranch())
            ->get()
            ->filter(function ($learner) use ($request) {
                return $learner->email === strtolower(trim($request->input('email')));
            })
            ->isNotEmpty();

        if ($exists) {
            return response()->json([
                'error' => true,
                'message' => 'The email has already been taken.'
            ], 422);
            die;
        }


        $planPrice = (float) $request->input('plan_price_id', 0);

        $paid_amount = (float) $request->input('paid_amount', 0);
        $locker = (float) $request->input('locker_amount', 0);
        $discount = (float) $request->input('discount_amount', 0);

        $effectivePaid = $planPrice + $locker - $discount;
        $pending_amount =  $effectivePaid - $paid_amount;




        $first_record = Hour::first();

        $total_hour = $first_record ? $first_record->hour : null;
        if ($request->seat_no) {

            if ($this->getLearnersByLibrary()->where('learners.seat_no', $request->seat_no)->where('plan_type_id', $request->plan_type_id)->where('learners.status', 1)->count() > 0) {

                return response()->json([
                    'error' => true,
                    'message' => 'This Plan Type Seat already booked'
                ], 422);
                die;
            }
        }
        if ($validator->fails()) {

            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
            die;
        }

        if (($paid_amount > $effectivePaid) || ($paid_amount == 0)) {
            return response()->json([
                'error' => true,
                'message' => 'Paid amount is not valid',
            ], 422);
            die;
        }


        if ($request->hasFile('id_proof_file')) {
            $this->validate($request, ['id_proof_file' => 'mimes:webp,png,jpg,jpeg|max:200']);
            $id_proof_file = $request->id_proof_file;
            $id_proof_fileNewName = "id_proof_file" . time() . $id_proof_file->getClientOriginalName();
            $id_proof_file->move('public/uploade/', $id_proof_fileNewName);
            $id_proof_file = 'public/uploade/' . $id_proof_fileNewName;
        } else {
            $id_proof_file = null;
        }



        if (PlanType::where('id', $request->plan_type_id)->count() > 0) {

            $hours = PlanType::where('id', $request->plan_type_id)->value('slot_hours');
        }


        if ($request->seat_no) {
            if (($this->getLearnersByLibrary()->where('learners.seat_no', $request->seat_no)->where('learner_detail.status', 1)->sum('hours') + $hours) > $total_hour) {

                return response()->json([
                    'error' => true,
                    'message' => 'You cannot select this plan because it conflicts with an existing booking. The seat is already reserved for the full library hours on the selected day, so we are unable to process this booking.'
                ], 422);
                die;
            }
        }


        $plan_id = $request->input('plan_id');
        $duration = Plan::where('id', $plan_id)->value('plan_id'); // This is the number of units
        $type = Plan::where('id', $plan_id)->value('type');        // This is the unit type: DAY, MONTH, YEAR, WEEK

        $start_date = Carbon::parse($request->input('plan_start_date'));

        switch (strtoupper($type)) {
            case 'DAY':
                $endDate = $start_date->copy()->addDays($duration);
                break;
            case 'WEEK':
                $endDate = $start_date->copy()->addWeeks($duration);
                break;
            case 'MONTH':
                $endDate = $start_date->copy()->addMonths($duration);
                break;
            case 'YEAR':
                $endDate = $start_date->copy()->addYears($duration);
                break;
            default:
                $endDate = $start_date; // fallback to start date if type is unknown
                break;
        }

        if ($request->payment_mode == 1 || $request->payment_mode == 2) {
            $is_paid = 1;
        } else {
            $is_paid = 0;
        }


        $extendDay = getExtendDays();

        $inextendDate = Carbon::parse($endDate)->addDays($extendDay);
        $status = $inextendDate > Carbon::today() ? 1 : 0;

        if ($request->seat_no) {
            $seat_no = $request->input('seat_no');
        } else {
            $seat_no = null;
        }
        if ($request->input('payment_mode') == 3) {

            $pending_amount = $paid_amount;
            $paid_amount = 0;
        }

        $customer = Learner::create([
            'seat_no' => $seat_no,
            'name' => $request->input('name'),
            'mobile' => encryptData($request->input('mobile')),
            'email' => encryptData($request->input('email')),
            'dob' => $request->input('dob'),
            'id_proof_name' => $request->input('id_proof_name'),
            'id_proof_file' => $id_proof_file,
            'hours' => $hours,
            'status' => $status,
            'library_id' => getLibraryId(),
            'password' => bcrypt($request->mobile),
            'branch_id' => getCurrentBranch(),

        ]);

        $learner_detail = LearnerDetail::create([
            'learner_id' => $customer->id,
            'plan_id' => $plan_id,
            'plan_type_id' => $request->input('plan_type_id'),
            'plan_price_id' => $request->input('plan_price_id'),
            'plan_start_date' => $start_date->format('Y-m-d'),
            'plan_end_date' => $endDate->format('Y-m-d'),
            'join_date' =>  $start_date->format('Y-m-d'),
            'hour' => $hours,
            'library_id' => getLibraryId(),
            'is_paid' => 1,
            'status' => $status,
            'payment_mode' => $request->input('payment_mode'),
            'seat_no' => $seat_no,
            'branch_id' => getCurrentBranch(),
            'exam_id' => $request->input('exam_id') ?? null,
        ]);


        LearnerTransaction::create([
            'learner_id' => $customer->id,
            'library_id' => getLibraryId(),
            'learner_detail_id' => $learner_detail->id,
            'total_amount' => $effectivePaid,
            'paid_amount' => $paid_amount,
            'pending_amount' => $pending_amount,
            'locker_amount' => $locker,
            'discount_amount' => $discount,
            'paid_date' => $start_date->format('Y-m-d') ?? date('Y-m-d'),
            'is_paid' => $is_paid,
            'branch_id' => getCurrentBranch()
        ]);

        if ($pending_amount &&  $request->due_date) {
            $tran = [
                'learner_id' => $customer->id,
                'due_date' => $request->due_date,
                'pending_amount' => $pending_amount,
                'created_at' => now(),
            ];
            DB::table('learner_pending_transaction')->insert($tran);
        }
        if ($status == 1) {

            $this->dataUpdate();
        }



        return response()->json([
            'success' => true,
            'message' => 'Learner created successfully!',
        ], 201);
    }


    public function getPlanType(Request $request)
    {

        $seatNo = $request->seat_no;


        if ($request->learner_detail_id) {
            $customer_plan = LearnerDetail::where('id', $request->learner_detail_id)
                ->pluck('plan_type_id');
            $selectedPlan = LearnerDetail::where('id', $request->learner_detail_id)
                ->pluck('plan_id');
        } else {
            $customer_plan = LearnerDetail::where('seat_no', $seatNo)->where('learner_id', $request->user_id)
                ->pluck('plan_type_id');
            $selectedPlan = $this->getLearnersByLibrary()->where('learner_detail.seat_no', $seatNo)->where('learners.id', $request->user_id)
                ->pluck('plan_id');
        }


        // Step 1: Retrieve the plan_type_ids from learners for the given seat
        $filteredPlanTypes = PlanType::where('id', $customer_plan)->pluck('name', 'id');

        $planTypesRemovals = $this->getLearnersByLibrary()->where('learner_detail.seat_no', $seatNo)
            ->pluck('plan_type_id')
            ->toArray();


        // Step 2: Retrieve all plan_types as an associative array
        $planTypes = PlanType::pluck('name', 'id');



        // Step 3: Filter out the plan_types that match the retrieved plan_type_ids
        if (!$planTypesRemovals) {
            $filteredPlanTypes = $planTypes->reject(function ($name, $id) use ($planTypesRemovals) {
                return in_array($id, $planTypesRemovals);
            });
        }


        $selectedPlanName = Plan::where('id', $selectedPlan)->pluck('name', 'id');

        // Return the filtered plan types as JSON
        $selectedbothId = LearnerDetail::where('id', $request->learner_detail_id)->select('plan_id', 'plan_type_id', 'plan_price_id')->first();
        $transaction = LearnerTransaction::where('learner_detail_id', $request->learner_detail_id)->select('total_amount', 'locker_amount', 'discount_amount', 'paid_amount')->first();

        return response()->json([$filteredPlanTypes, $selectedPlanName, $selectedbothId, $transaction]);
    }
    public function getPrice(Request $request)
    {

        $plan_type_id = $request->plan_type_id;
        $plan_id = $request->plan_id;
        if ($request->plan_type_id && $request->plan_id) {
            $PlanpPrice = getPlanPrice($plan_id, $plan_type_id);
            
            return response()->json($PlanpPrice);
        }
    }
    //learner  change plan 
    public function changePlanUpdate(Request $request, $id = null)
    {

        $request->validate([
            'plan_type_id' => 'required|exists:plan_types,id',
            'plan_price_id' => 'required',
            'new_plan_price' => 'required',
            'payment_mode' => 'required',
            'learner_detail_id' => 'required|exists:learner_detail,id',
        ]);


        $hours = PlanType::where('id', $request->plan_type_id)->value('slot_hours');

        $customer = Learner::findOrFail($id);
        $customer->hours = $hours;
        $customer->save();

        $LearnerDetail = LearnerDetail::where('learner_id', $customer->id)->first();
        if ($LearnerDetail) {


            $LearnerDetail->plan_type_id = $request->input('plan_type_id');
            $LearnerDetail->plan_price_id = $request->input('plan_price_id');
            $LearnerDetail->payment_mode = $request->input('payment_mode');
            $LearnerDetail->hour = $hours;
            $LearnerDetail->save();
        }

        $learnerTransaction = LearnerTransaction::where('learner_detail_id', $request->learner_detail_id)->first();
        if ($learnerTransaction) {
            if ($request->locker == 'yes') {
                $learnerTransaction->locker_amount = $request->input('locker_amount');
            }

            $learnerTransaction->total_amount = $request->input('new_plan_price');
            $learnerTransaction->paid_amount = $request->input('new_plan_price');
            $learnerTransaction->pending_amount = 0;
            $learnerTransaction->save();
        }


        $this->dataUpdate();
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Learner updated successfully!',
            ], 200);
        } else {
            return redirect()->route('learners')->with('success', 'Learner updated successfully.');
        }
    }
    public function getPricePlanwiseUpgrade(Request $request)
    {
        // if ($request->update_plan_type_id && $request->update_plan_id) {

        //     $planId = $request->update_plan_type_id;

        //     $PlanpPrice = PlanPrice::where('plan_type_id', $planId)->where('plan_id', $request->update_plan_id)->pluck('price', 'id');

        //     return response()->json($PlanpPrice);
        // }
    }

    public function getPlanTypeSeatWise(Request $request)
    {

        $seatNo = $request->seatNo;
        if ($seatNo) {


            // Step 1: Retrieve all bookings for the given seat
            $bookings = $this->getLearnersByLibrary()
                ->join('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')
                ->where('learner_detail.seat_no', $seatNo)
                ->where('learners.status', 1)
                ->where('learner_detail.status', 1)
                ->where('learners.branch_id', getCurrentBranch())
                ->where('learner_detail.branch_id', getCurrentBranch())
                ->get(['learner_detail.plan_type_id', 'plan_types.start_time', 'plan_types.end_time', 'plan_types.slot_hours']);

            // Step 2: Retrieve all plan types
            $planTypes = PlanType::get();

            // Step 3: Initialize an array to store the plan_type_ids to be removed
            $planTypesRemovals = [];

            // Step 4: Calculate total booked hours for the seat
            $totalBookedHours = $bookings->sum('slot_hours');

            $nightseatBooked = LearnerDetail::join('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')->where('learner_detail.seat_no', $seatNo)->where('learner_detail.status', 1)->where('plan_types.day_type_id', 9)->exists();

            // Step 5: Determine conflicts based on plan_type_id and hours
            $planTypeId = null;
            if ($totalBookedHours < 24) {

                foreach ($bookings as $booking) {
                    foreach ($planTypes as $planType) {
                        if ($booking->start_time < $planType->end_time && $booking->end_time > $planType->start_time) {
                            $planTypesRemovals[] = $planType->id;
                        }
                    }
                }
            }
            if ($totalBookedHours > 1) {
                $planTypeId = PlanType::where('day_type_id', 8)->value('id') ?? 0;
            }

            if (!is_null($planTypeId)) {
                $planTypesRemovals[] = $planTypeId;
            }
            if ($nightseatBooked) {
                $planTypeid = LearnerDetail::join('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')->where('learner_detail.seat_no', $seatNo)->where('learner_detail.status', 1)->where('plan_types.day_type_id', 9)->value('plan_types.id') ?? 0;
                $planTypesRemovals[] = $planTypeid;
            }
            // Remove duplicate entries in planTypesRemovals
            $planTypesRemovals = array_unique($planTypesRemovals);

            // If total booked hours >= 16, all plan types should be removed
            $first_record = Hour::where('branch_id', getCurrentBranch())->first();
            $total_hour = $first_record ? $first_record->hour : null;

            if ($totalBookedHours >= $total_hour) {
                $planTypesRemovals = $planTypes->pluck('id')->toArray();
            }
            // ✅ Remove day_type_id 8 and 9 if total allowed hours < 24
            if ($total_hour < 24) {
                $dayTypePlanIds = PlanType::whereIn('day_type_id', [8, 9])->pluck('id')->toArray();
                $planTypesRemovals = array_merge($planTypesRemovals, $dayTypePlanIds);
            }
            // Step 6: Filter out the plan_types that match the retrieved plan_type_ids
            $filteredPlanTypes = $planTypes->filter(function ($planType) use ($planTypesRemovals) {
                return !in_array($planType->id, $planTypesRemovals);
            })->map(function ($planType) {
                return ['id' => $planType->id, 'name' => $planType->name];
            })->values(); // Ensure the keys are reset to a continuous numerical index
        } else {

            $first_record = Hour::where('branch_id', getCurrentBranch())->first();
            $total_hour = $first_record ? $first_record->hour : null;

            if ($total_hour < 24) {
                $filteredPlanTypes = PlanType::whereNotIn('day_type_id', [8, 9])
                    ->select('id', 'name')
                    ->get();
            } else {
                $filteredPlanTypes = PlanType::select('id', 'name')->get();
            }

        }

        // Return the filtered plan types as JSON
        return response()->json($filteredPlanTypes);
    }


    public function fetchCustomerData($customerId = null, $isRenew = false, $status, $detailStatus, $filters = [])
    {

        $query = Learner::leftJoin('learner_detail', 'learner_detail.learner_id', '=', 'learners.id')
            ->leftJoin('plans', 'learner_detail.plan_id', '=', 'plans.id')
            ->leftJoin('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id');

        if (getCurrentBranch() == 0) {
            $query->where('learners.library_id', getLibraryId())
                ->where('learner_detail.library_id', getLibraryId());
        } else {
            $query->where('learners.branch_id', getCurrentBranch())
                ->where('learner_detail.branch_id', getCurrentBranch())
                ->where('learners.library_id', getLibraryId())
                ->where('learner_detail.library_id', getLibraryId());
        }

        $query->select(
            'plan_types.name as plan_type_name',
            'plans.name as plan_name',
            'learner_detail.seat_no',
            'learners.*',
            'plan_types.start_time',
            'plan_types.end_time',
            'learner_detail.plan_start_date',
            'learner_detail.plan_end_date',
            'learner_detail.plan_type_id',
            'learner_detail.plan_id',
            'learner_detail.plan_price_id',
            'learner_detail.status as learner_detail_status',
            'plan_types.image',
            'learner_detail.is_paid',
            'learner_detail.payment_mode',
            'learner_detail.id as learner_detail_id'
        );


        //  Apply dynamic filters if provided
        if (!empty($filters)) {

            // Filter by Plan ID
            if (!empty($filters['plan_id'])) {
                $query->where('learner_detail.plan_id', $filters['plan_id']);
            }

            // Filter by Payment Status

            if (isset($filters['is_paid'])) {
                $query->where('learner_detail.is_paid', $filters['is_paid']);
            }

            // If a status filter is provided, apply it and skip the default status conditions
            if (isset($filters['status'])) {
                if ($filters['status'] === 'active') {
                    // Only select active learners and details
                    $query->where('learners.status', 1)
                        ->where('learner_detail.status', 1);
                } elseif ($filters['status'] === 'expired') {
                    // Only select expired learners or details
                    $query->where(function ($q) {
                        $q->where('learner_detail.status', 0);
                    });
                }
            } else {
                // Apply default status conditions if no status filter is provided
                $query->where('learners.status', $status)
                    ->where('learner_detail.status', $detailStatus);
            }
            if (!empty($filters['seat_no'])) {

                $query->where('learner_detail.seat_no', $filters['seat_no']);
            }
            // Search by Name, Mobile, or Email
            if (!empty($filters['search'])) {
                $search = $filters['search'];
                $encryptdata = encryptData($search);
                $query->where(function ($q) use ($search, $encryptdata) {
                    $q->where('learners.name', 'LIKE', "%{$search}%")
                        ->orWhere('learners.mobile', 'LIKE', "%{$encryptdata}%")
                        ->orWhere('learners.seat_no', 'LIKE', "%{$search}%")
                        ->orWhere('learners.email', $encryptdata); // 🔍 Exact match for encrypted email
                });
            }
        } else {
            // Apply default status conditions if no filters are provided
            $query->where('learners.status', $status)
                ->where('learner_detail.status', $detailStatus);
        }

        // If fetching a specific customer
        if ($customerId) {
            $query->where('learners.id', $customerId);

            // Handle renew cases
            if ($isRenew) {
                $query->selectRaw('learner_detail.learner_id, learner_detail.plan_start_date, learner_detail.join_date, learner_detail.plan_end_date, learner_detail.plan_type_id, learner_detail.plan_id, learner_detail.plan_price_id, learner_detail.status, 1 as is_renew');
            } else {
                $query->selectRaw('learner_detail.learner_id, learner_detail.plan_start_date, learner_detail.join_date, learner_detail.plan_end_date, learner_detail.plan_type_id, learner_detail.plan_id, learner_detail.plan_price_id, learner_detail.status, 0 as is_renew');
            }

            $customer = $query->firstOrFail();

            if ($customer) {
                // Format start and end time
                $customer->start_time = Carbon::parse($customer->start_time)->format('g:i A');
                $customer->end_time = Carbon::parse($customer->end_time)->format('g:i A');
            }

            return $customer;
        }

        $query = $query->get(); // ✅ Fetch data as a collection

        return $query; // ✅ Return the modified collection


    }

    public function fetchLearnerData($customerId = null, $isRenew = false, $status, $detailStatus, $filters = [])
    {

        $query = Learner::leftJoin('learner_detail', 'learner_detail.learner_id', '=', 'learners.id')
            ->leftJoin('plans', 'learner_detail.plan_id', '=', 'plans.id')
            ->leftJoin('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id');

        if (getCurrentBranch() == 0) {
            $query->where('learners.library_id', getLibraryId())
                ->where('learner_detail.library_id', getLibraryId());
        } else {
            $query->where('learners.branch_id', getCurrentBranch())
                ->where('learner_detail.branch_id', getCurrentBranch())
                ->where('learners.library_id', getLibraryId())
                ->where('learner_detail.library_id', getLibraryId());
        }

        $query->select(
            'plan_types.name as plan_type_name',
            'plans.name as plan_name',
            'learner_detail.seat_no',
            'learners.*',
            'plan_types.start_time',
            'plan_types.end_time',
            'learner_detail.plan_start_date',
            'learner_detail.plan_end_date',
            'learner_detail.plan_type_id',
            'learner_detail.plan_id',
            'learner_detail.plan_price_id',
            'learner_detail.status as learner_detail_status',
            'plan_types.image',
            'learner_detail.is_paid',
            'learner_detail.payment_mode',
            'learner_detail.id as learner_detail_id'
        );


        $filters = array_filter($filters ?? []);

        if (!empty($filters)) {

            // Filter by Plan ID
            if (!empty($filters['plan_id'])) {

                $query->where('learner_detail.plan_id', $filters['plan_id']);
            }

            // Filter by Payment Status

            if (isset($filters['is_paid'])) {

                $query->where('learner_detail.is_paid', $filters['is_paid']);
            }


            if (!empty($filters['seat_no'])) {

                $query->where('learner_detail.seat_no', $filters['seat_no']);
            }
            // Search by Name, Mobile, or Email
            if (!empty($filters['search'])) {

                $search = $filters['search'];
                $encryptdata = encryptData($search);
                $query->where(function ($q) use ($search, $encryptdata) {
                    $q->where('learners.name', 'LIKE', "%{$search}%")
                        ->orWhere('learners.mobile', 'LIKE', "%{$encryptdata}%")
                        ->orWhere('learners.seat_no', 'LIKE', "%{$search}%")
                        ->orWhere('learners.email', $encryptdata);
                });
            }


            $query = $query->get();
            return $query;
        }
    }
    public function learnerList(Request $request)
    {

        $filters = [
            'plan_id' => $request->get('plan_id'),
            'is_paid' => $request->get('is_paid'),
            'status'  => $request->get('status'),
            'search'  => $request->get('search'),
            'seat_no'  => $request->get('seat_no'),
        ];

        $learners = $this->fetchCustomerData(null, false, 1, 1, $filters);



        return view('learner.learner', compact('learners'));
    }
    public function learnerSearch(Request $request)
    {

        $filters = [

            'search'  => $request->get('search'),

        ];

        $learners = $this->fetchLearnerData(null, false, 1, 1, $filters);



        return view('learner.learner-search', compact('learners'));
    }
    public function learnerHistory(Request $request)
    {
        $filters = [
            'plan_id' => $request->get('plan_id'),
            'is_paid' => $request->get('is_paid'),
            'status'  => $request->get('status'),
            'search'  => $request->get('search'),
        ];



        $learnerHistory = $this->fetchCustomerData(null, null, $status = 0, $detailStatus = 0, $filters);

        return view('learner.learnerHistory', compact('learnerHistory'));
    }

    //learner  Upgrade 
    public function userUpdate(Request $request, $id = null)
    {

        $learner = Learner::find($id);

        $validator = $this->validateCustomer($request);

        $validator = Validator::make($request->all(), array_merge($validator->getRules(), [
            'email' => [
                'required',
                'email',
                Rule::unique('learners')->where(function ($query) use ($request) {
                    return $query->where('library_id', getLibraryId());
                })->ignore($learner->id),
            ],

        ]));

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            } else {
                return redirect()->back()->withErrors($validator)->withInput();
            }
        }

        $user_id = $id ?: $request->input('user_id');
        $customer = Learner::findOrFail($user_id);
        if ($customer->seat_no) {
            // Fetch existing bookings for the same seat
            $existingBookings = $this->getLearnersByLibrary()->where('seat_no', $customer->seat_no)
                ->where('learners.id', '!=', $customer->id) // Exclude the current booking
                ->where('learner_detail.status', 1)
                ->get();

            // Determine hours based on plan_type_id

            $planType = PlanType::find($request->plan_type_id);
            $startTime = $planType->start_time;
            $endTime = $planType->end_time;
            $hours = $planType->slot_hours;

            // Check for overlaps with existing bookings
            foreach ($existingBookings as $booking) {
                $bookingPlanType = PlanType::find($booking->plan_type_id);

                if ($bookingPlanType) {
                    $bookingStartTime = $bookingPlanType->start_time;
                    $bookingEndTime = $bookingPlanType->end_time;


                    if (
                        ($startTime < $bookingEndTime && $endTime > $bookingStartTime) ||
                        ($endTime > $bookingStartTime && $startTime < $bookingEndTime)
                    ) {
                        return redirect()->back()->with('error', 'The selected plan type overlaps with an existing booking.');
                    }
                }
            }


            $first_record = Hour::first();
            $total_hour = $first_record ? $first_record->hour : 0;

            if ($total_hour === 0) {
                return redirect()->back()->with('error', 'Total available hours not set.');
            }

            // Calculate total hours booked on this seat
            $total_cust_hour = Learner::where('library_id', getLibraryId())->where('seat_no', $customer->seat_no)->where('status', 1)->sum('hours');

            // Check if the selected plan type exceeds available hours
            if ($hours > ($total_hour - ($total_cust_hour - $customer->hours))) {
                return redirect()->back()->with('error', 'You cannot select this plan type as it exceeds the available hours.');
            } else {
                $plan_type = $request->plan_type_id;
            }
        }
        // Calculate new plan_end_date by adding duration to the current plan_end_date
        $months = Plan::where('id', $request->plan_id)->value('plan_id');
        $duration = $months ?? 0;
        $currentEndDate = Carbon::parse($customer->plan_end_date);
        $start_date = Carbon::parse($request->input('plan_start_date'));
        if ($request->input('plan_end_date')) {
            $newEndDate = Carbon::parse($request->input('plan_end_date'));
        } elseif ($request->input('plan_start_date')) {
            $start_date = Carbon::parse($request->input('plan_start_date'));
            $newEndDate = $start_date->copy()->addMonths($duration);
        } else {

            $newEndDate = $currentEndDate->addMonths($duration);
        }
        // Handle the file upload
        if ($request->hasFile('id_proof_file')) {
            $id_proof_file = $request->file('id_proof_file');
            $id_proof_fileNewName = "id_proof_file_" . time() . "_" . $id_proof_file->getClientOriginalName();

            $id_proof_file->move(public_path('uploads'), $id_proof_fileNewName);
            $id_proof_filePath = 'uploads/' . $id_proof_fileNewName;

            $customer->id_proof_file = $id_proof_filePath;
        }

        // Update customer details only if the field is provided
        $customer->name = $request->input('name', $customer->name);
        $customer->mobile = $request->input('mobile', $customer->mobile);
        $customer->email = $request->input('email', $customer->email);
        $customer->dob = $request->input('dob', $customer->dob);

        $customer->id_proof_name = $request->input('id_proof_name', $customer->id_proof_name);
        $customer->hours = $hours;
        $customer->save();

        $LearnerDetail = LearnerDetail::where('learner_id', $customer->id)->first();
        if ($LearnerDetail) {
            if ($request->input('plan_start_date')) {
                $LearnerDetail->plan_start_date = $start_date;
            }
            $LearnerDetail->plan_id = $request->input('plan_id');
            $LearnerDetail->plan_type_id = $plan_type;
            $LearnerDetail->plan_price_id = $request->input('plan_price_id');
            $LearnerDetail->plan_end_date = $newEndDate->toDateString();
            $LearnerDetail->payment_mode = $request->input('payment_mode');
            $LearnerDetail->save();
        }
        $learnerTransaction = LearnerTransaction::where('learner_detail_id', $request->learner_detail_id)->first();
        if ($learnerTransaction) {
            $learnerTransaction->total_amount = $request->input('plan_price_id');
            $learnerTransaction->paid_amount = $request->input('plan_price_id');
            $learnerTransaction->pending_amount = 0;
        }


        $this->dataUpdate();
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Learner updated successfully!',
            ], 200);
        } else {
            return redirect()->route('learners')->with('success', 'Learner updated successfully.');
        }
    }
    //learner  Upgrade and renew store
    public function learnerUpgradeRenew(Request $request)
    {

        $rules = [

            'plan_id' => 'required',
            'plan_type_id' => 'required',
            'plan_price_id' => 'required',
            'user_id' => 'required',
            'discountType' => 'nullable',
            'discount_amount' => [
                'nullable',
                function ($attribute, $value, $fail) use ($request) {
                    if (!in_array($request->discountType, ['amount', 'percentage']) && $value) {
                        $fail('Discount type must be selected when providing a discount amount.');
                    }
                    if (in_array($request->discountType, ['amount', 'percentage']) && !$value) {
                        $fail('Discount amount is required when a discount type is selected.');
                    }
                }
            ]

        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        if (!Auth::user()->can('has-permission', 'Renew Seat')) {
            return redirect()->back()->with('error', 'You do not have permission to renew the seat.');
        }

        $currentDate = date('Y-m-d');
        DB::beginTransaction();

        try {
            $customer = Learner::findOrFail($request->user_id);
            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Learner not found.'
                ], 404);
            }

            $months = Plan::where('id', $request->plan_id)->value('plan_id');
            $duration = $months ?? 0;
            if ($request->learner_detail) {
                $learner_detail = LearnerDetail::where('id', $request->learner_detail)->first();
            } else {
                $learner_detail = LearnerDetail::where('learner_id', $request->user_id)->orderBy('id', 'DESC')->first();
            }
            // Determine hours based on plan_type_id

            $planType = PlanType::find($request->plan_type_id);
            $startTime = $planType->start_time;
            $endTime = $planType->end_time;
            $hours = $planType->slot_hours;
            if ($customer->seat_no) {


                // Fetch existing bookings for the same seat
                $existingBookings = $this->getLearnersByLibrary()->where('learner_detail.seat_no', $customer->seat_no)
                    ->where('learners.id', '!=', $customer->id) // Exclude the current booking
                    ->where('learner_detail.status', 1)
                    ->get();



                // Check for overlaps with existing bookings
                foreach ($existingBookings as $booking) {
                    $bookingPlanType = PlanType::find($booking->plan_type_id);

                    if ($bookingPlanType) {
                        $bookingStartTime = $bookingPlanType->start_time;
                        $bookingEndTime = $bookingPlanType->end_time;


                        if (
                            ($startTime < $bookingEndTime && $endTime > $bookingStartTime) ||
                            ($endTime > $bookingStartTime && $startTime < $bookingEndTime)
                        ) {
                            return redirect()->back()->with('error', 'The selected plan type overlaps with an existing booking.');
                        }
                    }
                }

                $first_record = Hour::first();
                $total_hour = $first_record ? $first_record->hour : 0;

                if ($total_hour === 0) {
                    return redirect()->back()->with('error', 'Total available hours not set.');
                }

                $total_cust_hour = Learner::where('seat_no', $customer->seat_no)->where('status', 1)->sum('hours');
                // Check if the selected plan type exceeds available hours
                if ($hours > ($total_hour - ($total_cust_hour - $customer->hours))) {
                    return redirect()->back()->with('error', 'You cannot select this plan type as it exceeds the available hours.');
                }
            }


            $plan_type = $request->plan_type_id;
            $start_date = Carbon::parse($learner_detail->plan_end_date)->addDay();

            $endDate = $start_date->copy()->addMonths($duration);
            if ($request->payment_mode == 1 || $request->payment_mode == 2) {
                $is_paid = 1;
                $payment_mode = $request->payment_mode;
            } else {
                $is_paid = 0;
                $payment_mode = 3;
            }

            if ($learner_detail->plan_end_date < $currentDate && $endDate->format('Y-m-d') > $currentDate  && $is_paid == 1) {

                $status = 1;
            } else {

                $status = 0;
            }

            if ($request->paid_date) {
                $transaction_date = $request->paid_date;
            } elseif ($start_date->format('Y-m-d')) {
                $transaction_date = $start_date->format('Y-m-d');
            } else {
                $transaction_date = date('Y-m-d');
            }
            if ($request->locker_amount) {
                $lockeramt = $request->locker_amount;
            } else {
                $lockeramt = 0;
            }
            if ($request->discountType == 'amount') {
                $discount = $request->discount_amount;
            } elseif ($request->discountType == 'percentage') {
                $total = $request->input('plan_price_id') + $lockeramt;
                $discount = ($total * $request->discount_amount) / 100;
            } else {
                $discount = 0;
            }

            $total_amount = $request->input('plan_price_id') + $lockeramt - $discount;
            if ($total_amount != $request->total_amount) {
                return redirect()->back()->with('error', 'Something went wrong');
            }
            $learner_detail = LearnerDetail::create([
                'library_id' => $customer->library_id,
                'branch_id' => getCurrentBranch(),
                'learner_id' => $customer->id,
                'plan_id' => $request->input('plan_id'),
                'plan_type_id' => $request->input('plan_type_id'),
                'plan_price_id' => $request->input('plan_price_id'),
                'plan_start_date' => $start_date->format('Y-m-d'),
                'plan_end_date' => $endDate->format('Y-m-d'),
                'join_date' => $learner_detail->join_date,
                'hour' => $hours,
                'seat_no' => $learner_detail->seat_no,
                'status' => $status,
                'is_paid' => $is_paid,
                'payment_mode' => $payment_mode,
            ]);

            if ($payment_mode == 1 || $payment_mode == 2) {
                LearnerTransaction::create([
                    'learner_id' => $customer->id,
                    'library_id' => getLibraryId(),
                    'branch_id' => getCurrentBranch(),
                    'learner_detail_id' => $learner_detail->id,
                    'total_amount' => $total_amount,
                    'paid_amount' => $total_amount,
                    'pending_amount' => 0,
                    'paid_date' => $transaction_date,
                    'locker_amount' => $lockeramt,
                    'discount_amount' => $discount ?? 0,
                    'is_paid' => 1,
                ]);
            }
            if ($status == 1) {
                $customer->hours = $hours;
            }
            $customer->save();
            DB::commit();
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Learner Renew successfully!',
                ], 200);
            } else {
                return redirect()->back()->with('success', 'Learner updated successfully!');
            }
        } catch (\Exception $e) {
            DB::rollBack(); // Something went wrong, rollback

            return redirect()->back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }
    public function getUser(Request $request, $id = null)
    {

        $customerId = $request->id ?? $id;
        $is_renew = $this->learnerService->getRenewalStatus($customerId);


        $available_seat = $this->learnerService->getAvailableSeats();

        $customer = $this->fetchCustomerData($customerId, $is_renew, $status = 1, $detailStatus = 1);

        if ($request->expectsJson() || $request->has('id')) {
            return response()->json($customer);
        } else {
            return view('learner.learnerEdit', compact('customer', 'available_seat'));
        }
    }
    public function showLearner(Request $request, $id = null)
    {

        $customerId = $request->id ?? $id;
        $is_renew = $this->learnerService->getRenewalStatus($customerId);

        $today = Carbon::today();
        $hasFuturePlan = LearnerDetail::where('learner_id', $customerId)
            ->where('plan_end_date', '>', $today->copy()->addDays(5))->where('status', 0)
            ->exists();
        $hasPastPlan = LearnerDetail::where('learner_id', $customerId)
            ->where('plan_end_date', '<=', $today->copy()->addDays(5))
            ->exists();

        $is_renew_update = $hasFuturePlan && $hasPastPlan;



        $available_seat = $this->learnerService->getAvailableSeats();
        $customer_status = learner::where('id', $customerId)->first();
        $status = $customer_status->status;
        $detailStatus = $customer_status->status;
        $customer = $this->fetchCustomerData($customerId, $is_renew, $status, $detailStatus);

        //renew History
        $renew_detail = LearnerDetail::where('learner_detail.learner_id', $customerId)
            ->with(['plan', 'planType'])
            ->get();
            
        //seat history

        if($customer->seat_no){
            $seat_history = $this->getAllLearnersByLibrary()
            ->where('seat_no', $customer->seat_no)
            ->where('id', '!=', $customerId)
            ->get();
        }else{
            $seat_history=null;
        }
       

        $transaction = LearnerTransaction::where('learner_id', $customerId)->where('learner_detail_id', $customer->learner_detail_id)
            ->orderBy('id', 'DESC')
            ->first();



        $all_transactions = LearnerTransaction::where('learner_id', $customerId)->where('is_paid', 1)->get();


        $customer['renew_update'] = $is_renew_update;
        if (isset($transaction) && $transaction->pending_amount > 0 &&  overdue($customerId, $transaction->pending_amount)) {
            $customer['pending'] = $transaction->pending_amount;
            $customer['overdue'] = "Overdue";
        } elseif (isset($transaction) && $transaction->pending_amount > 0) {
            $customer['pending'] = $transaction->pending_amount;
            $customer['overdue'] = '';
        } else {
            $customer['pending'] = '';
            $customer['overdue'] = '';
        }

        $learner_request = DB::table('learner_request')->where('learner_id', $customerId)->get();

        $learnerlog = DB::table('learner_operations_log')
            ->select('learner_id', 'created_at', DB::raw('MAX(operation) as operation'))
            ->where('learner_id', $customerId)
            ->groupBy('learner_id', 'created_at')
            ->get();

        if ($request->expectsJson() || $request->has('id')) {
            return response()->json($customer);
        } else {
            return view('learner.learnershow', compact('customer', 'available_seat', 'renew_detail', 'seat_history', 'transaction', 'all_transactions', 'learner_request', 'learnerlog'));
        }
    }
    //upgrade form view
    public function getLearner(Request $request, $id = null)
    {
        $routeName = $request->route()->getName();
        $today = Carbon::today()->format('Y-m-d');
        $customerId = $request->id ?? $id;
        $hasFuturePlan = LearnerDetail::where('learner_id', $customerId)
            ->where('plan_end_date', '>', $today)
            ->exists();
        $hasPastPlan = LearnerDetail::where('learner_id', $customerId)
            ->where('plan_end_date', '<=', $today)
            ->exists();
        $isalreadyRenew = LearnerDetail::where('learner_id', $customerId)
            ->where('plan_end_date', '>', $today)
            ->where('status', 0)
            ->exists();

        $is_renew = $hasFuturePlan && $hasPastPlan;


        $available_seat = $this->learnerService->getAvailableSeats();

        $customer = $this->fetchCustomerData($customerId, $is_renew, $status = 1, $detailStatus = 1);
        $customer_detail = LearnerDetail::where('learner_id', $customerId)->orderBy('id', 'Desc')->first();

        $oneWeekLater = Carbon::parse($customer->plan_start_date)->addWeek();
        $showButton = Carbon::now()->greaterThanOrEqualTo($oneWeekLater);
        $plantype = PlanType::get();
        $otherPlantype = LearnerDetail::where('learner_id', '!=', $customerId)
            ->where('seat_no', $customer_detail->seat_no)
            ->where('status', 1)
            ->pluck('plan_type_id');

        if ($customer_detail->seat_no) {
           $filteredPlanTypes =filterPlantypeFromseat($customer_detail->seat_no,$customerId);
        } else {
            $filteredPlanTypes = PlanType::select('id', 'name')->get();
        }

        // $bookings = LearnerDetail::join('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')->where('learner_id', '!=', $customerId)
        //     ->where('seat_no', $customer_detail->seat_no)
        //     ->where('learner_detail.status',1)
        //    ->get(['learner_detail.plan_type_id', 'plan_types.start_time', 'plan_types.end_time', 'plan_types.slot_hours']);
        // $totalBookedHours = $bookings->sum('slot_hours');
        // $remainingPlanTypes = $plantype->whereNotIn('id', $otherPlantype);
        // $nightseatBooked=LearnerDetail::join('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')->where('learner_id', '!=', $customerId)->where('learner_detail.seat_no',$customer_detail->seat_no)->where('learner_detail.status',1)->where('plan_types.day_type_id',9)->exists();
        //  $planTypesRemovals = [];

        //  $planTypeId = null;
        //     if($totalBookedHours < 24){

        //         foreach ($bookings as $booking) {
        //             foreach ($remainingPlanTypes as $planType) {
        //                 if ($booking->start_time < $planType->end_time && $booking->end_time > $planType->start_time) {
        //                     $planTypesRemovals[] = $planType->id;
        //                 }
        //             }
        //         }
        //     }
        //     if($totalBookedHours > 1){
        //         $planTypeId = PlanType::where('day_type_id', 8)->value('id') ?? 0;

        //     }

        //     if (!is_null($planTypeId)) {
        //         $planTypesRemovals[] = $planTypeId;

        //     }
        //     if($nightseatBooked){
        //         $planTypeid=LearnerDetail::join('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')->where('learner_detail.seat_no',$customer_detail->seat_no)->where('learner_detail.status',1)->where('plan_types.day_type_id',9)->value('plan_types.id') ?? 0;
        //         $planTypesRemovals[] = $planTypeid;
        //     }
        //     // Remove duplicate entries in planTypesRemovals
        //     $planTypesRemovals = array_unique($planTypesRemovals);
        //      $first_record = Hour::first();
        //     $total_hour = $first_record ? $first_record->hour : null;

        //     if ($totalBookedHours >= $total_hour) {
        //         $planTypesRemovals = $remainingPlanTypes->pluck('id')->toArray();
        //     }

        //     // Step 6: Filter out the plan_types that match the retrieved plan_type_ids
        //     $filteredPlanTypes = $remainingPlanTypes->filter(function ($planType) use ($planTypesRemovals) {
        //         return !in_array($planType->id, $planTypesRemovals);
        //     })->map(function ($planType) {
        //         return ['id' => $planType->id, 'name' => $planType->name];
        //     })->values();

        if ($routeName == 'learners.upgrade.renew' || $routeName == 'learner.renew.plan') {

            return view('learner.renewUpgrade', compact('customer',  'available_seat', 'showButton', 'is_renew', 'filteredPlanTypes', 'isalreadyRenew'));
        } else {

            return view('learner.upgradePlantype', compact('customer',  'available_seat', 'showButton', 'filteredPlanTypes'));
        }
    }
    public function getSwapUser($id)
    {

        $customerId = $id;
        $firstRecord = Hour::first();
        $totalHour = $firstRecord ? $firstRecord->hour : null;

        $customer = $this->fetchCustomerData($customerId, false, $status = 1, $detailStatus = 1);

        return view('learner.swap', compact('customer'));
    }

    public function seatHistory()
    {

        $learners_seats =  Learner::leftJoin('learner_detail', 'learner_detail.learner_id', '=', 'learners.id')
            ->where('learners.branch_id', getCurrentBranch())->get();


        $today = Carbon::today();
        $first_record = Hour::where('branch_id', getCurrentBranch())->first();
        $total_seats = $first_record ? $first_record->seats : 0;

        $seats = [];

        for ($seatNo = 1; $seatNo <= $total_seats; $seatNo++) {
            // Fetch learners for each seat number
            $learners = Learner::leftJoin('learner_detail', 'learner_detail.learner_id', '=', 'learners.id')
                ->where('learners.branch_id', getCurrentBranch())
                ->where('learner_detail.seat_no', $seatNo)
                ->select('learners.*', 'learner_detail.*')
                ->get();

            // Separate active and expired learners
            $activeLearners = $learners->where('status', 1);
            $expiredLearners = $learners->where('status', 0);

            // Initialize seat info
            $seat = new \stdClass();
            $seat->seat_no = $seatNo;


            // Determine seat status
            if ($activeLearners->isNotEmpty()) {
                $seat->status = 'booked';
            } elseif ($expiredLearners->isNotEmpty()) {
                $seat->status = 'expired';
            } else {
                $seat->status = 'available';
            }

            $seats[] = $seat;
        }


        return view('learner.seatHistory', ['learners_seats' => $learners_seats->toArray(), 'seats' => $seats]);
    }
    public function history($id)
    {
        // Get the learners with their details, plans, and seat information
        $learners = Learner::where('library_id', getLibraryId())
            ->with([
                'learnerDetails' => function ($query) {
                    $query->with(['plan', 'planType']);
                }
            ])
            ->whereHas('learnerDetails', function ($query) use ($id) {
                $query->where('learner_id', $id)->where('learner_detail.status', 0);
            })

            ->get();

        return view('learner.seatHistoryView', compact('learners'));
    }

    public function reactiveUser(Request $request, $id = null)
    {

        $customerId = $request->id ?? $id;
        $is_renew = $this->learnerService->getRenewalStatus($customerId);
        $available_seat = $this->learnerService->getAvailableSeats();
      
        $customer = $this->fetchCustomerData($customerId, false, $status = 0, $detailStatus = 0);
        $customer_detail = LearnerDetail::where('learner_id', $customerId)->orderBy('id', 'Desc')->first();
     
        if ($request->expectsJson() || $request->has('id')) {

            return response()->json($customer);
        } else {

            return view('learner.learnerEdit', compact('customer', 'available_seat'));
        }
    }

    public function swapSeat(Request $request)
    {
        try {
            DB::transaction(function () use ($request) {

                $customer = $this->getLearnersByLibrary()->where('learners.id', $request->learner_id)->select('learners.id as id', 'learners.*', 'learner_detail.plan_type_id', 'learner_detail.seat_no')->first();

                $newSeatId = $request->seat_id;

                $first_record = Hour::first();
                $total_hour = $first_record ? $first_record->hour : null;

                $newSeatNo = $request->seat_id;

                $total_cust_hour = Learner::where('library_id', getLibraryId())->where('seat_no', $newSeatNo)->sum('hours');
                $new_seat_remainig = $total_hour - $total_cust_hour;

                if ($request->seat_id && ($customer->hours > $new_seat_remainig)) {
                    throw new Exception('Not available according to your hours.');
                } elseif (
                    $this->getLearnersByLibrary()->where('learner_detail.seat_no', $newSeatNo)
                    ->where('plan_type_id', $customer->plan_type_id)
                    ->where('learners.status', 1)
                    ->where('learner_detail.status', 1)
                    ->count() > 0 && $request->seat_id
                ) {
                    throw new Exception('The new seat is not available for your plan type.');
                } else {

                    // Update the learner's seat_id and seat_no
                    $data = Learner::findOrFail($request->learner_id);
                    $data->seat_no = $newSeatNo;
                    $data->save();
                    $learner_detail = LearnerDetail::where('learner_id', $request->learner_id)->update([
                        'seat_no' => $newSeatId,
                    ]);
                }
            });


            return redirect()->route('learners')->with('success', 'Seat swapped successfully!');
        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Seat swap failed: ' . $e->getMessage());
        }
    }
    public function reactiveLearner(Request $request, $id)
    {

         $rules = [

            'plan_id' => 'required',
            'seat_no' => 'nullable',
            'plan_type_id' => 'required',
            'plan_price_id' => 'required',
            'plan_start_date' => 'required',
            'user_id' => 'required',
            'learner_detail' => 'required',
            'payment_mode' => 'required',
            'discountType' => 'nullable',
            'discount_amount' => [
                'nullable',
                function ($attribute, $value, $fail) use ($request) {
                    if (!in_array($request->discountType, ['amount', 'percentage']) && $value) {
                        $fail('Discount type must be selected when providing a discount amount.');
                    }
                    if (in_array($request->discountType, ['amount', 'percentage']) && !$value) {
                        $fail('Discount amount is required when a discount type is selected.');
                    }
                }
            ]

        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        if (!Auth::user()->can('has-permission', 'Renew Seat')) {
            return redirect()->back()->with('error', 'You do not have permission to renew the seat.');
        }


        DB::beginTransaction();

        try {
            // for log value

            $old_value = LearnerDetail::where('id', $request->learner_detail)->first();

            // for log value
            $customer = Learner::findOrFail($request->user_id);
            $start_date = Carbon::parse($request->input('plan_start_date'));
            $plan_id = $request->input('plan_id');
            $duration = Plan::where('id', $plan_id)->value('plan_id'); 
            $type = Plan::where('id', $plan_id)->value('type'); 

            switch (strtoupper($type)) {
                case 'DAY':
                    $endDate = $start_date->copy()->addDays($duration);
                    break;
                case 'WEEK':
                    $endDate = $start_date->copy()->addWeeks($duration);
                    break;
                case 'MONTH':
                    $endDate = $start_date->copy()->addMonths($duration);
                    break;
                case 'YEAR':
                    $endDate = $start_date->copy()->addYears($duration);
                    break;
                default:
                    $endDate = $start_date; 
                    break;
            }
            
        $existingBookings = $this->getLearnersByLibrary()
            ->where('learner_detail.seat_no', '=', $request->seat_no)
            ->where('learner_detail.plan_type_id', '=', $request->plan_type_id)
            ->where('learners.status', 1)
            ->where('learner_detail.status', 1)
            ->where(function ($q) use ($start_date, $endDate) {
                $q->where('learner_detail.plan_start_date', '<=', $endDate)
                ->where('learner_detail.plan_end_date', '>=', $start_date);
            })
          
            ->exists();

     
           if($existingBookings){
            return redirect()->back()->with('error', 'You can not select this plan type it is already booked for this Seat.');
           }
            $planType = PlanType::find($request->plan_type_id);
            $startTime = $planType->start_time;
            $endTime = $planType->end_time;
            $hours = $planType->slot_hours;
           
            $first_record = Hour::first();
            $total_hour = $first_record ? $first_record->hour : 0;

            if ($total_hour === 0) {
                return redirect()->back()->with('error', 'Total available hours not set.');
            }

            $total_cust_hour = Learner::where('seat_no', $request->seat_no)->where('status', 1)->sum('hours');
           

            if ($hours > ($total_hour - $total_cust_hour)) {

                return redirect()->back()->with('error', 'You cannot select this plan type as it exceeds the available hours.');
            }

            $months = Plan::where('id', $request->plan_id)->value('plan_id');
            $duration = $months ?? 0;
            $start_date = Carbon::parse($request->input('plan_start_date'));
            $endDate = $start_date->copy()->addMonths($duration);

            $customer->seat_no = $request->seat_no;
            $customer->hours = $hours;
            $customer->status = 1;
            if (!$customer->save()) {
                throw new \Exception('Failed to update customer');
            }
            if ($request->payment_mode == 1 || $request->payment_mode == 2) {
                $is_paid = 1;
                $payment_mode = $request->payment_mode;
            } else {
                $is_paid = 0;
                $payment_mode = 3;
            }
            $learner_detail = LearnerDetail::create([
                'library_id' => $customer->library_id,
                'learner_id' => $customer->id,
                'plan_id' => $request->input('plan_id'),
                'plan_type_id' => $request->input('plan_type_id'),
                'plan_price_id' => $request->input('plan_price_id'),
                'plan_start_date' => $start_date->format('Y-m-d'),
                'plan_end_date' => $endDate->format('Y-m-d'),
                'join_date' => $start_date->format('Y-m-d'),
                'hour' => $hours,
                'seat_no' => $request->seat_no,
                'payment_mode' => $payment_mode,
                'is_paid' =>1
            ]);
            
                LearnerTransaction::create([
                    'learner_id' => $customer->id,
                    'library_id' => $customer->library_id,
                    'learner_detail_id' => $learner_detail->id,
                    'total_amount' => $request->input('plan_price_id'),
                    'paid_amount' => $request->input('plan_price_id'),
                    'pending_amount' => 0,
                    'paid_date' => $start_date->format('Y-m-d') ?? date('Y-m-d'),
                    'is_paid' => $is_paid
                ]);
           
            

            // $this->seat_availablity($request);
            // learner log table update
            DB::table('learner_operations_log')->insert([
                'learner_id' => $customer->id,
                'learner_detail_id' => $learner_detail->id,
                'library_id' => $customer->library_id,
                'field_updated' => 'seat_no',
                'old_value' => $old_value->seat_no,
                'new_value' => $request->seat_no,
                'updated_by' => getLibraryId(),
                'branch_id' =>  getCurrentBranch(),
                'operation' => 'reactive',
                'created_at' => now(),
            ]);
            DB::commit();

            return redirect()->route('learnerHistory')->with('success', 'Learner updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    public function learnerRenew(Request $request)
    {

        $rules = [

            'plan_id' => 'required',
            'plan_type_id' => 'required',
            'plan_price_id' => 'required',
            'user_id' => 'required',
            // 'payment_mode' => 'required',

        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        $currentDate = date('Y-m-d');
        // Find the customer by user_id
        $customer = Learner::findOrFail($request->user_id);
        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Learner not found.'
            ], 404);
        }

        $months = Plan::where('id', $request->plan_id)->value('plan_id');
        $duration = $months ?? 0;
        $learner_detail = LearnerDetail::where('learner_id', $customer->id)->where('status', 1)->first();


        if (!$learner_detail) {
            return response()->json([
                'success' => false,
                'message' => 'Learner detail not found or inactive.'
            ], 404);
        }
        $start_date = Carbon::parse($learner_detail->plan_end_date)->addDay();

        $endDate = $start_date->copy()->addMonths($duration);
        if ($request->payment_mode == 1 || $request->payment_mode == 2) {
            $is_paid = 1;
            $payment_mode = $request->payment_mode;
        } else {
            $is_paid = 0;
            $payment_mode = 3;
        }

        if ($learner_detail->plan_end_date < $currentDate && $endDate->format('Y-m-d') > $currentDate  && $is_paid == 1) {

            $status = 1;
        } else {

            $status = 0;
        }

     
        $learner_detail = LearnerDetail::create([
            'library_id' => $customer->library_id,
            'learner_id' => $customer->id,
            'plan_id' => $request->input('plan_id'),
            'plan_type_id' => $request->input('plan_type_id'),
            'plan_price_id' => $request->input('plan_price_id'),
            'plan_start_date' => $start_date->format('Y-m-d'),
            'plan_end_date' => $endDate->format('Y-m-d'),
            'join_date' => $learner_detail->join_date,
            'hour' => $learner_detail->hour,
            'seat_no' => $learner_detail->seat_no,
            'status' => $status,
            'is_paid' => $is_paid,
            'payment_mode' => $payment_mode,
        ]);
       
            LearnerTransaction::create([
                'learner_id' => $customer->id,
                'library_id' => getLibraryId(),
                'learner_detail_id' => $learner_detail->id,
                'total_amount' => $request->input('plan_price_id'),
                'paid_amount' => $request->input('plan_price_id'),
                'pending_amount' => 0,
                'paid_date' => $start_date->format('Y-m-d') ?? date('Y-m-d'),
                'is_paid' => 1
            ]);
    
        $learnerStatus = LearnerDetail::where('learner_id', $customer->id)
            ->where('is_paid', 1)
            ->where('plan_end_date', '<', $currentDate) // Corrected comparison syntax
            ->where('status', 1)
            ->get();

        if (!$learnerStatus->isEmpty()) {

            foreach ($learnerStatus as $data) {
                if ($data->plan_end_date < $currentDate) {

                    $data->status = 0;  // Expired
                } elseif ($data->plan_end_date > $currentDate) {

                    $data->status = 1;  // Active
                }

                $data->save();
            }
        }


        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Learner updated successfully!',
            ], 200);
        } else {
            return redirect()->back()->with('success', 'Learner updated successfully!');
        }
    }
    public function getSeatStatus(Request $request)
    {

        $count = $this->getLearnersByLibrary()
            ->where('learner_detail.seat_no', $request->new_seat_id)
            ->where('learners.status', 1)
            ->where('learner_detail.status', 1)
            ->where('learner_detail.plan_type_id', $request->plan_type_id)
            ->count();

        $customer = Learner::where('id', $request->user_id)
            ->where('status', 1)
            ->first();

        $first_record = Hour::first();
        $total_hour = $first_record ? $first_record->hour : null;

        $total_cust_hour = Learner::where('library_id', getLibraryId())->where('seat_no', $request->new_seat_id)->sum('hours');
        $new_seat_remaining = $total_hour - $total_cust_hour;


        $bookings = $this->getLearnersByLibrary()
            ->join('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')
            ->where('learner_detail.seat_no', $request->new_seat_id)
            ->where('learners.status', 1)
            ->where('learner_detail.status', 1)
            ->get(['learner_detail.plan_type_id', 'plan_types.start_time', 'plan_types.end_time', 'plan_types.slot_hours']);

        $planType = PlanType::where('id', $request->plan_type_id)->first();

        $status_array = [];

        foreach ($bookings as $booking) {

            if ($booking->start_time < $planType->end_time && $booking->end_time > $planType->start_time) {
                $status_array[] = 0;
            } else {
                $status_array[] = 1;
            }
        }

        if ($customer->hours > $new_seat_remaining) {
            $status = 0;
        } elseif ($count == 1) {
            $status = 0;
        } elseif (in_array(0, $status_array)) {
            $status = 0;
        } elseif ($count == 0) {
            $status = 1;
        } else {
            $status = 1;
        }

        return response()->json($status);
    }


    public function destroy($id)
    {

        try {
            DB::transaction(function () use ($id) {

                $customer = Learner::findOrFail($id);


                $lastLearnerDetail = LearnerDetail::where('learner_id', $customer->id)
                    ->orderBy('id', 'desc')
                    ->first();

                if ($lastLearnerDetail) {
                    // Delete associated LearnerTransaction records
                    LearnerTransaction::where('learner_detail_id', $lastLearnerDetail->id)->delete();

                    $lastLearnerDetail->delete();
                    $customer->delete();
                } else {

                    throw new Exception("No LearnerDetail found for learner ID: {$customer->id}");
                }
            });


            return response()->json(['success' => 'Learner and related details deleted successfully.']);
        } catch (\Exception $e) {

            return response()->json(['error' => 'An error occurred while deleting the customer: ' . $e->getMessage()], 500);
        }

        return response()->json(['success' => 'Learner deleted successfully.']);
    }
    public function userclose(Request $request)
    {

        $customer = Learner::findOrFail($request->learner_id);

        $today = date('Y-m-d');
        LearnerDetail::where('learner_id', $customer->id)->where('status', 1)->update(['plan_end_date' => $today, 'status' => 0]);
        $customer->status = 0;
        $customer->save();
        return response()->json(['message' => 'Learner closed successfully.']);
    }

    public function makePayment(Request $request)
    {

        $customer_detail_id = $request->id;
        $customer_detail = LearnerDetail::where('id', $customer_detail_id)->first();
        $customerId = $customer_detail->learner_id;
        if ($customer_detail->status == 0 && $customer_detail->is_paid == 0 && $customer_detail->plan_end_date >= date('Y-m-d')) {
            $isRenew = true;
        } else {
            $isRenew = false;
        }

        $learner = Learner::where('id', $customerId)->first();
        $status = $learner->status;
        $detailStatus = $customer_detail->status;

        $customer = LearnerDetail::where('id', $customer_detail_id)->with('learner', 'plan', 'plantype')->first();
        $is_payment_pending = LearnerTransaction::where('learner_detail_id', $customer_detail_id)
            ->where('pending_amount', '!=', 0)
            ->exists();
        $pending_payment = LearnerTransaction::where('learner_detail_id', $customer_detail_id)
            ->where('pending_amount', '!=', 0)->select('pending_amount', 'id')->first();

        return view('learner.payment', compact('customer',  'isRenew', 'is_payment_pending', 'pending_payment'));
    }

    public function paymentStore(Request $request)
    {

        $this->validate($request, [
            'learner_id' => 'required|exists:learners,id',
            'paid_amount' => 'required|numeric',

            'payment_mode' => 'required'
        ]);



        if ($request->learner_transaction_id && LearnerTransaction::where('id', $request->learner_transaction_id)->exists()) {

            $tranDetail = LearnerTransaction::where('id', $request->learner_transaction_id)->first();

            $data['pending_amount'] = $tranDetail->pending_amount - $request->paid_amount;
            if ($data['pending_amount'] == 0) {
                $data['is_paid'] = 1;
            } else {
                $data['is_paid'] = 0;
            }
            $data['paid_amount'] = $tranDetail->paid_amount + $request->paid_amount;
            $data['paid_date'] = date('Y-m-d');
            $traupdate = LearnerTransaction::where('id', $tranDetail->id)->update($data);
            if ($traupdate) {
                if (DB::table('learner_pending_transaction')->where('learner_id', $request->learner_id)->exists()) {
                    DB::table('learner_pending_transaction')->where('learner_id', $request->learner_id)->where('pending_amount', '>=', $request->paid_amount)
                        ->update([
                            'pending_amount' => $data['pending_amount'],
                            'paid_date' => date('Y-m-d'),
                            'status' => $data['is_paid']
                        ]);
                } elseif ($data['pending_amount'] > 0) {
                    DB::table('learner_pending_transaction')->insert(
                        [
                            'learner_id' => $request->learner_id,
                            'due_date' => date("Y-m-d"),
                            'pending_amount' => $data['pending_amount'],
                            'status' => $data['is_paid']
                        ]
                    );
                }
            }
            try {

                return redirect()->route('learners')->with('success', 'Payment successfully recorded.');
            } catch (\Exception $e) {
                \Log::error('Payment Error: ' . $e->getMessage());
                return redirect()->route('learners')->withErrors(['error' => 'An error occurred while processing the payment.']);
            }
        }

        return redirect()->route('learners')->with('error', 'Something went wrong');
    }

    public function learnerExpire(Request $request, $id = null)
    {

        $customerId = $request->id ?? $id;
        $is_renew = $this->learnerService->getRenewalStatus($customerId);

        $available_seat = $this->learnerService->getAvailableSeats();

        $customer = $this->fetchCustomerData($customerId, $is_renew, $status = 1, $detailStatus = 1);

        return view('learner.expire', compact('customer',  'available_seat'));
    }
    public function editLearnerExpire(Request $request)
    {
        $user_id = $request->input('user_id');

        $customer = Learner::findOrFail($user_id);
        $start_date = Carbon::parse($request->input('plan_start_date'));
        if ($request->input('plan_end_date')) {
            $newEndDate = Carbon::parse($request->input('plan_end_date'));
        }

        $LearnerDetail = LearnerDetail::where('learner_id', $customer->id)->first();
        if ($request->input('plan_start_date')) {
            $LearnerDetail->plan_start_date = $start_date->toDateString();
            $LearnerDetail->join_date = $start_date->toDateString();
        }

        $LearnerDetail->plan_end_date = $newEndDate->toDateString();
        $LearnerDetail->save();
        $LearnerDetail = LearnerDetail::where('learner_id', $customer->id)->first();
        $LearnerDetail->save();
        // $this->seat_availablity($request);

        $this->dataUpdate();
        return redirect()->route('learners')->with('success', 'Learner updated successfully.');
    }

    public function learnerLog(Request $request)
    {
        // Log the incoming request data
        Log::info('Incoming Request Data:', $request->all());

        // Validate the request and catch any validation errors
        try {
            $validatedData = $request->validate([
                'learner_id' => 'required|integer',
                'field_updated' => 'required|string',
                'old_value' => 'nullable|string',
                'new_value' => 'nullable|string',
                'updated_by' => 'required|integer',

                'operation' => 'required',
            ]);

            Log::info('Validation Successful:', $validatedData);

            $updated_user = $validatedData['updated_by'] ?? getLibraryId();
            $old_value = $validatedData['old_value'] ? $validatedData['old_value'] : $validatedData['operation'];
            if ($validatedData['operation'] == 'renewSeat' || $validatedData['operation'] == 'reactive' || $validatedData['operation'] == 'learnerUpgrade' || $validatedData['operation'] == 'swapseat' || $validatedData['operation'] == 'changePlan') {
                $learner_detail_id = LearnerDetail::where('learner_id', $validatedData['learner_id'])
                    ->orderBy('id', 'DESC')
                    ->value('id');
            } elseif ($validatedData['operation'] == 'closeSeat' || $validatedData['operation'] == 'deleteSeat') {
                $learner_detail_id = LearnerDetail::where('learner_id', $validatedData['learner_id'])
                    ->whereNull('deleted_at')
                    ->orderBy('id', 'DESC')
                    ->value('id');
            } else {
                $learner_detail_id = null;
            }

            /*ALTER TABLE learner_operations_log
            ADD UNIQUE INDEX unique_learner_operation_created_at (learner_id, operation, created_at);
            */
            if ($old_value == 'swapseat') {
                $old_value = "General";
            }

            DB::table('learner_operations_log')->insert([
                'learner_id' => $validatedData['learner_id'],
                'learner_detail_id' => $learner_detail_id,
                'library_id' => getLibraryId(),
                'field_updated' => $validatedData['field_updated'],
                'old_value' => $old_value,
                'new_value' => $validatedData['new_value'],
                'updated_by' => $updated_user,
                'operation' => $validatedData['operation'],
                'branch_id' =>  getCurrentBranch(),
                'created_at' => now(),
            ]);

            Log::info('Data inserted successfully');
            return response()->json(['success' => true, 'message' => 'Change logged successfully']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation Error:', $e->errors());
            return response()->json(['success' => false, 'message' => 'Validation error', 'errors' => $e->errors()]);
        } catch (\Exception $e) {
            Log::error('Insertion Error:', ['message' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Database insertion error']);
        }
    }


    public function incrementMessageCount(Request $request)
    {

        $id = $request->input('id');
        $type = $request->input('type');
        $message = $request->input('message');

        // Find the learner record
        $learner = Learner::find($id);

        if ($learner) {

            $detailCount = DB::table('email_message')->where('learner_id', $learner->id)->first();

            if ($detailCount) {
                if ($type === 'whatsapp') {

                    DB::table('email_message')->where('learner_id', $learner->id)->create([
                        'learner_message' => $message,
                        'created_at' => now(),
                    ]);
                } elseif ($type === 'email') {

                    DB::table('email_message')->where('learner_id', $learner->id)->create([
                        'learner_email' => $message,
                        'created_at' => now(),
                    ]);
                }

                return response()->json(['success' => true]);
            }
        }

        return response()->json(['success' => false], 404);
    }
    //learner  update
    public function learnerUpdate(Request $request, $id = null)
    {

        $learner = Learner::find($id);

        // Call validateCustomer method to apply default validation
        $validator = $this->validateCustomer($request);

        $validator = Validator::make($request->all(), array_merge($validator->getRules(), [
            'email' => [
                'required',
                'email',
                Rule::unique('learners')->where(function ($query) use ($request) {
                    return $query->where('library_id', getLibraryId());
                })->ignore($learner->id), // Ignore current learner's email
            ],
        ]));

        if ($validator->fails()) {

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            } else {
                return redirect()->back()->withErrors($validator)->withInput();
            }
        }

        // Determine user_id based on $id or request input
        $user_id = $id ?: $request->input('user_id');


        $customer = Learner::findOrFail($user_id);
        // Handle the file upload
        if ($request->hasFile('id_proof_file')) {
            $id_proof_file = $request->file('id_proof_file');
            $id_proof_fileNewName = "id_proof_file_" . time() . "_" . $id_proof_file->getClientOriginalName();

            // Store the file in the 'public/uploads' directory
            $id_proof_file->move(public_path('uploads'), $id_proof_fileNewName);
            $id_proof_filePath = 'uploads/' . $id_proof_fileNewName;

            // Set the path in the customer model
            $customer->id_proof_file = $id_proof_filePath;
        }

        // Update customer details only if the field is provided
        $customer->name = $request->input('name', $customer->name);
        $customer->mobile = encryptData($request->input('mobile', $customer->mobile));

        $customer->dob = $request->input('dob', $customer->dob);

        $customer->id_proof_name = $request->input('id_proof_name', $customer->id_proof_name);

        // Save the customer details
        $customer->save();
        return redirect()->route('learners')->with('success', 'Learner updated successfully.');
    }

    public function generateIdCard(Request $request)
    {

        $learner_detail = LearnerDetail::where('id', $request->detail_id)->with(['plan', 'planType', 'learner'])->first();
        $learner = Learner::where('id', $request->learner_id)->first();
        // Generate the ID Card PDF
        $pdf = PDF::loadView('learner.id_card_template', compact('learner_detail', 'learner'));
        $filePath = storage_path('app/public/id_cards/' . $learner_detail->id . '_id_card.pdf');
        $pdf->save($filePath);

        // Send via WhatsApp
        // $whatsappService = new WhatsAppService();
        // $whatsappService->sendMessageWithAttachment($user->phone, $filePath);
        return redirect()->back()->with('success', 'Learner Id Card Generate successfully!');
    }


    public function learnerProfile()
    {
        $learner = LearnerDetail::withoutGlobalScopes()->where('learner_id', getLibraryId())->where('learner_detail.status', 1)->leftJoin('plans', 'learner_detail.plan_id', '=', 'plans.id')->leftJoin('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')->select('learner_detail.*', 'plan_types.name as plan_type_name', 'plans.name as plan_name', 'plan_types.start_time', 'plan_types.end_time')->first();

        return view('learner.profile', compact('learner'));
    }

    public function learnerRequest()
    {
        $learner_request = DB::table('learner_request')->where('learner_id', getLibraryId())->get();
        return view('learner.request', compact('learner_request'));
    }

    public function learnerRequestCreate(Request $request)
    {
        $request->validate([
            'request_name' => 'required|string|max:255',
            'description' => 'required',
        ]);

        DB::table('learner_request')->insert([
            'learner_id' => Auth::id(),
            'request_name' => $request->request_name,
            'description' => $request->description,
            'request_date' => Carbon::now()->toDateString(),

            'created_at' => now(),

        ]);

        return redirect('learner/request')->with('success', 'Request submitted successfully.');
    }

    public function learnerAttendence(Request $request)
    {
        if ($request->has('date')) {
            $learners =  Learner::leftJoin('learner_detail', 'learner_detail.learner_id', '=', 'learners.id')
                ->where('learners.library_id', getLibraryId())
                ->leftJoin('attendances', function ($join) use ($request) {
                    $join->on('learners.id', '=', 'attendances.learner_id')
                        ->whereDate('attendances.date', '=', $request->date);
                })
                ->where('learners.status', 1)
                ->select('learners.*', 'learner_detail.*', DB::raw('COALESCE(attendances.attendance, 2) as attendance'))
                ->get();
        } else {
            $learners = null;
        }


        return view('learner.attendance', compact('learners'));
    }

    public function updateAttendance(Request $request)
    {

        $request->validate([
            'learner_id' => 'required|integer',
            'attendance' => 'required|integer',
            'date' => 'required|date',
            'time' => 'required|in:in,out',
        ]);

        // Extract variables from the request
        $learnerId = $request->learner_id;
        $attendance = $request->attendance;
        $date = $request->date;
        $currentTime = now();

        $existingAttendance = Attendance::where('learner_id', $learnerId)
            ->where('date', $date)
            ->first();

        if ($existingAttendance) {

            if ($request->time == 'in') {
                $existingAttendance->in_time = $currentTime;
            } elseif ($request->time == 'out') {
                $existingAttendance->out_time = $currentTime;
            }

            // Update attendance status
            $existingAttendance->attendance = $attendance;
            $existingAttendance->save();
        } else {

            Attendance::create([
                'learner_id' => $learnerId,
                'attendance' => $attendance,
                'date' => $date,
                'in_time' => $request->time == 'in' ? $currentTime : null,
                'out_time' => $request->time == 'out' ? $currentTime : null,
                'library_id' => getLibraryId(),
                'branch_id' => getCurrentBranch(),
            ]);
        }
        $learner = Learner::where('id', $learnerId)->select('name')->first();

        if ($attendance == 1) {
            $message = 'Attendance of ' . $learner->name . ' has been marked Present!';
            return response()->json(['present' => true, 'message' => $message]);
        } else {
            $message = '';
            $message = 'Attendance of ' . $learner->name . ' has been marked Absent!';
            return response()->json(['absent' => true, 'message' => $message]);
        }
    }

    public function getLearnerAttendence(Request $request)
    {
        $data = Learner::where('library_id', getLibraryId())
            ->where('status', 1)
            ->pluck('name', 'id');

        // Base Query
        $learners = Learner::leftJoin('learner_detail', 'learner_detail.learner_id', '=', 'learners.id')
            ->leftJoin('attendances', 'learners.id', '=', 'attendances.learner_id')
            ->leftJoin('plans', 'learner_detail.plan_id', '=', 'plans.id')
            ->leftJoin('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')
            ->where('learners.library_id', getLibraryId())
            ->where('learners.status', 1);

        // Apply Filters Dynamically
        if ($request->has('date')) {

            $learners->whereDate('attendances.date', '=', $request->date);
        }

        if ($request->has('learner_id')) {

            $learners->where('learners.id', '=', $request->learner_id);
        }

        // Select required columns
        $learners = $learners->select(
            'learners.id as learner_id',
            'learners.name as name',
            'learners.email as email',
            'learners.dob as dob',
            'learners.mobile',
            'learners.seat_no',
            'learner_detail.plan_start_date',
            'learner_detail.plan_end_date',
            'learners.library_id',
            'learners.status',
            'plans.name as plan_name',
            'plan_types.name as plan_type_name',
            'attendances.in_time',
            'attendances.out_time',
            'attendances.attendance',
            'attendances.date'
        )->get();

        return view('library.learner-attendance', compact('learners', 'data'));
    }


    /** Learner Guard and in front learner related function**/

    public function IdCard()
    {
        $data = LearnerDetail::withoutGlobalScopes()->where('learner_id', Auth::user()->id)->where('learner_detail.status', 1)->leftJoin('plans', 'learner_detail.plan_id', '=', 'plans.id')->leftJoin('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')->select('learner_detail.*', 'plan_types.name as plan_type_name', 'plans.name as plan_name', 'plan_types.start_time', 'plan_types.end_time')->first();

        $library_name = Branch::where('id', Auth::user()->branch_id)->select('name as library_name', 'features')->first();

        return view('learner.idCard', compact('library_name', 'data'));
    }
    public function support()
    {
        $library_name = Library::where('id', Auth::user()->library_id)->first();

        return view('learner.support', compact('library_name'));
    }
    public function blog()
    {
        $data = Blog::get();
        return view('learner.blog', compact('data'));
    }
    public function feadback()
    {
        $is_feedback = LearnerFeedback::where('learner_id', Auth::user()->id)->exists();
        return view('learner.feadback', compact('is_feedback'));
    }
    public function suggestions()
    {
        $data = Suggestion::where('learner_id', Auth::user()->id)->get();
        return view('learner.suggestions', compact('data'));
    }
    public function attendance(Request $request)
    {
        $dates = LearnerDetail::withoutGlobalScopes()->where('learner_id', Auth::user()->id)->select('plan_start_date', 'plan_end_date')->get();
        $data = LearnerDetail::withoutGlobalScopes()->where('learner_id', Auth::user()->id)->where('learner_detail.status', 1)->leftJoin('plans', 'learner_detail.plan_id', '=', 'plans.id')->leftJoin('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')->select('learner_detail.*', 'plan_types.name as plan_type_name', 'plans.name as plan_name', 'plan_types.start_time', 'plan_types.end_time')->first();
        $my_attandance = Attendance::where('learner_id', Auth::user()->id)->get();

        if ($request->has('request_name') && !empty($request->request_name)) {
            $year = Carbon::parse($request->request_name)->year;
            $month = Carbon::parse($request->request_name)->month;

            $my_attandance = Attendance::where('learner_id', Auth::user()->id)
                ->whereYear('date', $year)
                ->whereMonth('date', $month)
                ->get();
        }

        $months = [];

        foreach ($dates as $date) {
            $start = Carbon::parse($date->plan_start_date)->startOfMonth();
            $end = Carbon::parse($date->plan_end_date)->startOfMonth();

            // Loop through the months within the start and end date range
            while ($start <= $end) {
                $year = $start->year;
                $monthNumber = $start->month;
                $monthName = $start->format('F');
                $year_month = $start->format('Y-m');

                $months[] = [
                    'year' => $year,
                    'month' => $monthNumber,
                    'month_name' => $monthName,
                    'year_month' => $year_month
                ];

                $start->addMonth();
            }
        }
        return view('learner.my-attendance', compact('months', 'data', 'my_attandance'));
    }
    public function complaints()
    {
        $data = Complaint::where('learner_id', Auth::user()->id)->get();
        return view('learner.complaints', compact('data'));
    }
    public function transactions()
    {
        $transaction = LearnerTransaction::withoutGlobalScopes()->where('learner_transactions.learner_id', Auth::user()->id)->leftJoin('learner_detail', 'learner_transactions.learner_detail_id', '=', 'learner_detail.id')->select('learner_transactions.*', 'learner_detail.plan_type_id', 'learner_detail.plan_id')->get();

        return view('learner.transactions', compact('transaction'));
    }
    public function booksLibrary()
    {
        return view('learner.booksLibrary');
    }

    public function suggestionsStore(Request $request)
    {

        $data = $request->validate([
            'title' => 'required',
            'description' => 'required',

        ]);

        if ($request->hasFile('attachment')) {
            $this->validate($request, ['attachment' => 'mimes:webp,png,jpg,jpeg|max:200']);
            $attachment = $request->attachment;
            $id_proof_fileNewName = "suggestion" . time() . $attachment->getClientOriginalName();
            $attachment->move('public/uploade/', $id_proof_fileNewName);
            $attachment = 'public/uploade/' . $id_proof_fileNewName;
        } else {
            $attachment = null;
        }
        $data['attachment'] = $attachment;
        $data['learner_id'] = Auth::user()->id;
        $data['library_id'] = Auth::user()->library_id;
        Suggestion::create($data);

        return redirect()->route('learner.suggestions')->with('success', 'Data created Successfully');
    }

    public function complaintsStore(Request $request)
    {

        $data = $request->validate([
            'title' => 'required',
            'description' => 'required',

        ]);

        if ($request->hasFile('attachment')) {
            $this->validate($request, ['attachment' => 'mimes:webp,png,jpg,jpeg|max:200']);
            $attachment = $request->attachment;
            $id_proof_fileNewName = "suggestion" . time() . $attachment->getClientOriginalName();
            $attachment->move('public/uploade/', $id_proof_fileNewName);
            $attachment = 'public/uploade/' . $id_proof_fileNewName;
        } else {
            $attachment = null;
        }
        $data['attachment'] = $attachment;
        $data['learner_id'] = Auth::user()->id;
        $data['library_id'] = Auth::user()->library_id;
        Complaint::create($data);

        return redirect()->route('complaints')->with('success', 'Data created Successfully');
    }


    public function feadbackStore(Request $request)
    {
        $data = $request->validate([
            'frequency' => 'required|integer|in:1,2,3,4', // Must be 1, 2, 3, or 4
            'purpose' => 'required|string|max:255', // Required, max 255 chars
            'resources' => 'required|integer|in:1,2', // Must be 1 (Yes) or 2 (No) [change if needed]
            'resource_suggestions' => 'nullable|string|max:500', // Optional, max 500 chars
            'rating' => 'required|integer|min:1|max:5', // Rating between 1-5
            'staff' => 'required|integer|in:1,2', // Must be 1 (Yes) or 2 (No) [change if needed]
            'comments' => 'nullable|string|max:500', // Optional, max 500 chars
        ]);


        $data['learner_id'] = Auth::user()->id;
        $data['library_id'] = Auth::user()->library_id;
        if (LearnerFeedback::where('learner_id', Auth::user()->id)->exists()) {
            return redirect()->route('learner.feadback')->with('error', ' Your feedback already uploaded');
        }
        LearnerFeedback::create($data);
        return redirect()->route('learner.feadback')->with('success', 'Thank you for your feedback!');
    }


    public function blogDetailShow($slug)
    {

        $data = Blog::where('page_slug', $slug)->first();
        return view('site.blog-details', compact('data'));
    }

    public function pendingPayment(Request $request)
    {
        $learner_id = $request->id;
        $customer = LearnerDetail::where('learner_id', $learner_id)
            ->with('learner', 'plan', 'plantype')
            ->orderBy('id', 'DESC')
            ->first();
        $pendingPayment = DB::table('learner_pending_transaction')->where('learner_id', $learner_id)
            ->where('learner_id', $learner_id)
            ->first();
        // $pendingPayment = LearnerTransaction::where('learner_id', $learner_id)
        // ->where('pending_amount', '!=', 0)
        // ->whereNotNull('pending_amount')
        // ->first();
        return view('learner.pending-payment', compact('customer', 'pendingPayment'));
    }

    public function getTransactionDetail(Request $request)
    {
        $transaction_id = $request->transaction_id;
        $transaction = LearnerTransaction::find($transaction_id);

        if (!$transaction) {
            return response()->json(['error' => 'Transaction not found'], 404);
        }

        $learnerDetail = $transaction->learner_detail_id;
        $data = LearnerDetail::where('id', $learnerDetail)
            ->with('learner', 'plan', 'plantype')
            ->first();

        return response()->json($data);
    }


    public function pendingPaymentStore(Request $request)
    {

        $this->validate($request, [
            // 'transaction_image' => 'nullable|mimes:webp,png,jpg,jpeg|max:200',
            'transaction_id' => 'required|exists:learner_pending_transaction,id',
            'pending_amount' => 'required',
            'payment_mode' => 'required',

        ]);
        $pendingTransaction = DB::table('learner_pending_transaction')
            ->where('id', $request->transaction_id)
            ->orderBy('id', 'desc')
            ->first();
        $transaction = LearnerTransaction::where('learner_id', $pendingTransaction->learner_id)->where('pending_amount', $request->pending_amount)->first();
        if (!$transaction) {
            return redirect()->route('learners')->withErrors(['error' => 'Transaction not found.']);
        }

        $total_amount = $transaction->total_amount;
        $total_paid_amount = $transaction->paid_amount + $transaction->pending_amount;
        $new_pending_amount = $total_amount - $total_paid_amount;

        // if ($request->hasFile('transaction_image')) {
        //     $transaction_image = $request->file('transaction_image');
        //     $transaction_imageNewName = 'transaction_image_' . time() . '_' . $transaction_image->getClientOriginalName();
        //     $transaction_image->move(public_path('uploads'), $transaction_imageNewName);
        //     $transaction_image = 'uploads/' . $transaction_imageNewName;
        // } else {
        //     $transaction_image = null;
        // }

        try {

            $transaction->pending_amount = $new_pending_amount;
            $transaction->paid_amount = $total_paid_amount;
            $transaction->save();

            if ($pendingTransaction) {
                DB::table('learner_pending_transaction')
                    ->where('id', $pendingTransaction->id)
                    ->update([

                        'status' => 1,
                        'payment_mode' => $request->payment_mode ?? null,
                    ]);
            }

            return redirect()->route('learners')->with('success', 'Payment successfully recorded.');
        } catch (\Exception $e) {
            \Log::error('Payment Error: ' . $e->getMessage());
            return redirect()->route('learners')->withErrors(['error' => 'An error occurred while processing the payment.']);
        }
    }
}
