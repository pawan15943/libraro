<?php

namespace App\Http\Controllers;

use App\Models\Customers;
use App\Models\Hour;
use App\Models\Learner;
use App\Models\LearnerDetail;
use App\Models\LearnerTransaction;
use App\Models\Library;
use App\Models\LibraryTransaction;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\PlanType;
use App\Models\Seat;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Throwable;
use Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Http\Middleware\LoadMenus;
use App\Models\Branch;
use App\Models\Expense;
use App\Models\Subscription;
use App\Traits\LearnerQueryTrait;
use Illuminate\Support\Str;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
    use LearnerQueryTrait;
    public function generateReceipt(Request $request)
    {
        if($request->type=='library'){
           
            $data = LibraryTransaction::where('id', $request->id)->first();
            $user = Library::where('id', $data->library_id)->where('status', 1)->first();
            $transactionDate=$data->transaction_date;
            $paymentMode=$data->payment_mode;
            $total_amount=$data->amount;
            $month=$data->month;
            $start_date=$data->start_date;
            $end_date=$data->end_date;
            $name=$user->library_owner;
            $subscription=Subscription::where('id',$user->library_type)->value('name');
            $library=$user;
        }
        if($request->type=='learner'){
           $data = LearnerTransaction::withoutGlobalScopes()->where('id', $request->id)->where('is_paid',1)->first();

            $learnerDeatail = LearnerDetail::withoutGlobalScopes()->where('id', $data->learner_detail_id)
            ->with(['plan', 'planType'])
            ->first();
          
            $user = Learner::where('id', $data->learner_id)->first();
          
            $transactionDate=$data->paid_date;
            $paymentMode='Offline';
            $total_amount=$data->total_amount;
           
        
            if ($learnerDeatail) {
                $month = $learnerDeatail->plan ? $learnerDeatail->plan->plan_id : null; // Check if 
                $start_date = $learnerDeatail->plan_start_date;
                $end_date = $learnerDeatail->plan_end_date;
                $subscription=$learnerDeatail->plantype ? $learnerDeatail->plantype->name : null;
            } else {
            
                $month = null;
                $start_date = null;
                $end_date = null;
                $subscription=null;
            }
            $name=$user->name;
           
           $library=Library::leftJoin('branches','libraries.id','=','branches.library_id')->where('libraries.id',$learnerDeatail->library_id)->select('libraries.library_name','libraries.email','libraries.library_mobile','branches.library_address')->first();
        }
       
        
        $send_data = [
            'subscription' =>$subscription ?? 'NA',
            'name' => $name ?? 'NA',
            'email' => $user->email ?? 'NA',
            'transactiondate' => $transactionDate ?? 'NA',
            'paid_amount' => $data->paid_amount ?? 'NA',
            'payment_mode' => $paymentMode ?? 'NA',
            'invoice_ref_no' => $data->transaction_id ?? 'NA',
            'total_amount' => $total_amount ?? 'NA',
            'start_date' => $start_date ?? 'NA',
            'end_date' => $end_date ?? 'NA',
            'monthly_amount' => $total_amount ?? 'NA',
            'month' => $month ?? 'NA',
            'currency' => 'Rs.',
            'library_name'=>$library->library_name,
            'library_email'=>$library->email,
            'library_mobile'=>$library->library_mobile,
            'library_address'=>$library->library_address,
        ];
        

        // Generate the PDF without saving it on the server
        $pdf = PDF::loadView('recieptPdf', $send_data);

        return $pdf->download(time() . '_receipt.pdf');
    }

    
    public function showUploadForm($id = null)
    {
        $library_id=$id;
        
        if($library_id==null){
            $library_id=getLibraryId();
        }
        
       $plans=Plan::withoutGlobalScopes()->where('library_id',$library_id)->get();
       $plan_id_one=Plan::withoutGlobalScopes()->where('library_id',$library_id)->where('plan_id',1)->value('id');
       
       if ($plan_id_one) {
            $plantypesQuery = PlanPrice::withoutGlobalScopes()
                ->leftJoin('plan_types', 'plan_prices.plan_type_id', '=', 'plan_types.id')
                ->leftJoin('plans', 'plan_prices.plan_id', '=', 'plans.id')
                ->where('plan_prices.plan_id', $plan_id_one)
                ->where('plan_prices.library_id', $library_id)
                ->select('plan_types.name as plan_type', 'plan_prices.price as plan_price','plan_types.start_time','plan_types.end_time');

            if (getCurrentBranch() != 0) {
                $plantypesQuery->where('branch_id', getCurrentBranch());
            }
            $plantypes = $plantypesQuery->get();
        } else {
            $plantypes = null;
        }

      
           return view('library.csv', compact('library_id','plans','plantypes'));
    }

   
    public function uploadCsv(Request $request)
    {
       
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt,xlsx,xls',
        ]);
        

        // Get the file and its real path
        $file = $request->file('csv_file');
        $path = $file->getRealPath();

        $csvData = [];
        $header = null;

        // Open the file and parse the CSV
        if (($handle = fopen($path, 'r')) !== false) {
            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                $row = array_map('trim', $row);

                if (!$header) {
                    $header = $row; // Set first row as header
                } else {
                    if (count($header) == count($row)) {
                        $csvData[] = array_combine($header, $row);
                    } else {
                        Log::error('CSV row does not match header format: ', $row);
                        return redirect()->back()->withErrors('CSV data does not match header format.');
                    }
                }
            }
            fclose($handle);
        }

        // Invalid and success records
        $invalidRecords = [];
        $successRecords = [];
        if($request->library_id){
            $library_id=$request->library_id;
        }elseif($request->library_import=='library_master'){
            $library_id=getLibraryId();
        }else{
            $library_id=getLibraryId(); 
        }

        if(getCurrentBranch() == 0 || getCurrentBranch()==null){
            return redirect()->back()->withErrors('Branch not selected');
        }
        
     

        DB::transaction(function () use ($csvData, &$invalidRecords, &$successRecords,$library_id) {
            foreach ($csvData as $record) {
                try {
                    $this->validateAndInsert($record, $successRecords, $invalidRecords);
                   
                    
                } catch (Throwable $e) {
                    Log::error('Error inserting record: ' . $e->getMessage(), $record);
                    $record['error_message'] = $e->getMessage();
                    $invalidRecords[] = $record;
                }
            }
        });

        if (!empty($invalidRecords)) {
        
            session(['invalidRecords' => $invalidRecords]); 

            return redirect()->route('library.upload.form')->with([
                'successCount' => count($successRecords),
                'autoExportCsv' => true,
            ]);
        }
        
        return redirect()->back()->with('successCount', count($successRecords));
       
    }
    
    public function uploadmastercsv(Request $request){
        
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt,xlsx,xls',
        ]);

        // Get the file and its real path
        $file = $request->file('csv_file');
        $path = $file->getRealPath();

        $csvData = [];
        $header = null;

        $newBranchCount = count($csvData);
        // Validate branch count limit before processing
        $validation = branchCountValidation();
        $current = $validation['branch_count'];
        $allowed = $validation['max_allowed'];

        if ($current + $newBranchCount > $allowed) {
            return redirect()->back()->withErrors([
                'csv_file' => "You can only add " . ($allowed - $current) . " more branches. Your CSV contains $newBranchCount new branches, which exceeds the limit of $allowed."
            ]);
        }


        // Open the file and parse the CSV
        if (($handle = fopen($path, 'r')) !== false) {
            
            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                $row = array_map('trim', $row);
              
                if (!$header) {
                  
                    $header = $row; // Set first row as header
                } else {
                   
                    if (count($header) == count($row)) {
                        $csvData[] = array_combine($header, $row);
                    } else {
                        Log::error('CSV row does not match header format: ', $row);
                        return redirect()->back()->withErrors('CSV data does not match header format.');
                    }
                }
            }
            fclose($handle);
        }

        // Invalid and success records
        $invalidRecords = [];
        $successRecords = [];
        if($request->library_id){
            $library_id=$request->library_id;
        }elseif($request->library_import=='library_master'){
            $library_id=Auth::user()->id;
        }else{
            $library_id=null; 
        }
       
        DB::transaction(function () use ($csvData, &$invalidRecords, &$successRecords,$library_id) {
            foreach ($csvData as $record) {
                try {
                    
                    $this->validateMasterInsert($record, $successRecords, $invalidRecords,$library_id);
                   
                    
                } catch (Throwable $e) {
                    Log::error('Error inserting record: ' . $e->getMessage(), $record);
                    $record['error_message'] = $e->getMessage();
                    $invalidRecords[] = $record;
                }
            }
        });

        if (!empty($invalidRecords)) {
        
            session(['invalidRecords' => $invalidRecords]); 

            return redirect()->back()->with([
                'successCount' => count($successRecords),
                'autoExportCsv' => true, 
            ]);
        }
        
        
        $middleware = app(LoadMenus::class);
        $middleware->updateLibraryStatus();
        return redirect()->back()->with('successCount', count($successRecords));
    }

    protected function validateAndInsert($data, &$successRecords, &$invalidRecords)
    {
    

        $validator = Validator::make($data, [
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'plan' => 'required',
            'plan_type' => 'required',
            'start_date' => 'required',
            'mobile' => 'required|max:10|min:10',
            'paid_amount'=>'required|int',
            'pending_amount'=>'required|int',
        ]);

        if ($validator->fails()) {
            $invalidRecords[] = array_merge($data, ['error' => 'Validation failed']);
            \Log::info('Validation failed');
            return;
        }

        $user = Auth::user();

        $dob = !empty($data['dob']) ? $this->parseDate(trim($data['dob'])) : now();

        

       

        if (!$dob) {
            $invalidRecords[] = array_merge($data, ['error' => 'Invalid Date of Birth Format: The date of birth (DOB) format is incorrect. Please enter it in the correct format (e.g., YYYY-MM-DD or as required).']);
            return;
        }
       
        if (!empty(trim($data['plan']))) {
            preg_match('/\d+/', trim($data['plan']), $matches);
        } else {
            $matches = [1]; // Default to 1 if empty
        }
        
        $planexplode = $matches[0] ?? 1;
      
        $plan = Plan::where('plan_id',$planexplode)->first();
        $planType = PlanType::whereRaw('LOWER(REPLACE(name, " ", "")) = ?', [strtolower(str_replace(' ', '', trim($data['plan_type'])))])->first();
      
        if (!$planType ) {
            $invalidRecords[] = array_merge($data, ['error' => 'Plan Type Not Found: The specified plan type is invalid or does not exist. Please check the plan type details and retry.']);
            return;
        }
         
       
        $planPrice =getPlanPrice($plan->id, $planType->id);
        if ((!$user->can('has-permission', 'Full Day') && $planType->day_type_id==1) || (!$user->can('has-permission', 'First Half') && $planType->day_type_id==2) || (!$user->can('has-permission', 'Second Half') && $planType->day_type_id==3) || (!$user->can('has-permission', 'Hourly Slot 1') && $planType->day_type_id==4)|| (!$user->can('has-permission', 'Hourly Slot 2') && $planType->day_type_id==5)|| (!$user->can('has-permission', 'Hourly Slot 3') && $planType->day_type_id==6)|| (!$user->can('has-permission', 'Hourly Slot 4') && $planType->day_type_id==7)){
            $invalidRecords[] = array_merge($data, ['error' => $planType->name.'Plan Type Booking Restriction: The selected plan type does not have the necessary permissions for booking. Please check the plan type settings and try again.']);
            return;
        }
        if (!$plan ) {
            $invalidRecords[] = array_merge($data, ['error' => 'Plan Not Found: The specified plan does not exist in the system. Please verify the plan name or ID and try again.']);
            return;
        }
        
        if ( !$planPrice) {
            $invalidRecords[] = array_merge($data, ['error' => 'Plan Price Not Found: The price for the selected plan is missing or not defined. Please confirm the correct pricing and re-upload the data.']);
            return;
        }
        $data['plan_price']=$planPrice;
        $paid_amount=!empty($data['paid_amount']) ? trim($data['paid_amount']) : 0;
       
      
        // if($planPrice < $paid_amount){
        //     $invalidRecords[] = array_merge($data, ['error' => 'Paid Amount Exceeds Plan Price: The entered paid amount is greater than the actual plan price. Please verify and enter the correct amount.']);
        //     return;
        // }
        if(trim($data['seat_no'])){
            $seat=trim($data['seat_no']);
        }else{
            $seat=null;
        }

        $payment_mode = !empty($data['payment_mode']) ? $this->getPaymentMode(trim($data['payment_mode'])) : 2;
        $hours = $planType->slot_hours;
        $duration = Plan::where('id', $plan->id)->value('plan_id'); 
        $type = Plan::where('id', $plan->id)->value('type'); 
         $start_date = Carbon::parse(trim($data['start_date']));
         if (!$start_date) {
            $invalidRecords[] = array_merge($data, ['error' => 'Missing Start Date: Please ensure that the start date field is filled in before proceeding with the upload.']);
            return;
        }    
        $joinDate = isset($data['join_date']) ? $this->parseDate(trim($data['join_date'])) : $start_date;
        // Here we manage end date how it calculated.
    //    $start_date = $this->parseDate(trim($data['start_date']));
      
        $duration = (int) $duration;

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
                // Log or throw error if type is invalid
                $endDate = $start_date;
                break;
        }

        
        $pending_amount =!empty($data['pending_amount']) ? trim($data['pending_amount']) : 0;
        
        $paid_date = isset($data['paid_date']) ? $this->parseDate(trim($data['paid_date'])) : $start_date;

        $extendDay = getExtendDays();
        $inextendDate = Carbon::parse($endDate)->addDays($extendDay);
        $status = $inextendDate >= Carbon::today() ? 1 : 0;
        if(empty($data['paid_amount']) || $paid_amount==0){
            $is_paid =0;
        }else{
            $is_paid =1;
        }
        // $is_paid = $pending_amount <= 0 ? 1 : 0;
        if ($status == 1) {
            \Log::info('Learner for updated', [ 'status1' => $status]);
            // Check if the learner already exists with active status
            $alreadyLearner = Learner::where('branch_id', getCurrentBranch())
                ->where('email', encryptData(trim($data['email'])))
                ->where('status', 1)
                ->exists();
            $exist_check = Learner::where('branch_id', getCurrentBranch())
            ->where('email', encryptData(trim($data['email'])))
            ->where('status', 0)
            ->exists();

            if ($alreadyLearner) {
              
                $invalidRecords[] = array_merge($data, ['error' => 'Duplicate Entry: This data already exists in the system. Please avoid duplicate entries and check the existing records before re-uploading.']);
                return;
            } else {
               
                // Check if seat is already occupied
                if (Learner::where('branch_id', getCurrentBranch())
                    ->where('seat_no', trim($data['seat_no']))
                    ->where('status', 1)->whereNotNull('seat_no')
                    ->exists()) {
                        \Log::info('Learner occupide', [ 'status1' => $status]);
                    $first_record = Hour::first();
                    $total_hour = $first_record ? $first_record->hour : null;
                    $hours = PlanType::where('id', $planType->id)->value('slot_hours');
                    $day_type_id=PlanType::where('id', $planType->id)->value('day_type_id');


                    $exists_data=Learner::leftJoin('learner_detail', 'learner_detail.learner_id', '=', 'learners.id')
                    ->where('learners.library_id', getLibraryId())
                    ->where('learner_detail.branch_id', getCurrentBranch())
                    ->where('learners.seat_no', trim($data['seat_no']))
                    ->whereNotNull('learner_detail.seat_no')
                    ->where('learners.status', 1)
                    ->where('learner_detail.status', 1)->with('planType')->get();

                    $planTypeSame=Learner::leftJoin('learner_detail', 'learner_detail.learner_id', '=', 'learners.id')
                    ->where('learners.library_id', getLibraryId())
                    ->where('learner_detail.branch_id', getCurrentBranch())
                    ->where('learners.seat_no', trim($data['seat_no']))
                    ->whereNotNull('learner_detail.seat_no')
                    ->where('learners.status', 1)
                    ->where('learner_detail.status', 1)->where('learner_detail.plan_type_id',$planType->id)->count();
                    if($planTypeSame > 0){
                        $invalidRecords[] = array_merge($data, ['error' => 'Seat Already Booked: The selected seat (Seat No: ' .$data['seat_no']. ') is already booked under another plan. Please verify the seat availability before proceeding.']);
                        return; 
                    }


                    // Day Type 1 : FD | 2 : FH | 3 : SH | 4 : H1 | 5: H2 | 6 : H3 | 7 : H4
                    // Here we check if FH is booked then H1 & H2 is Not booked and so on.
                    foreach($exists_data as $data_get){
                        if(($day_type_id==2 && ($data_get->planType->day_type_id==4 || $data_get->planType->day_type_id==5))
                         || ($day_type_id==3 && ($data_get->planType->day_type_id==6 || $data_get->planType->day_type_id==7)) 
                         || (($day_type_id==4 || $day_type_id==5) && ($data_get->planType->day_type_id==2)) 
                         || (($day_type_id==6 || $day_type_id==7) && ($data_get->planType->day_type_id==3))
                         || (($day_type_id==1 ) && ($data_get->planType->day_type_id==1 || $data_get->planType->day_type_id==2 || $data_get->planType->day_type_id==3 || $data_get->planType->day_type_id==4 ||$data_get->planType->day_type_id==5 ||$data_get->planType->day_type_id==6 ||$data_get->planType->day_type_id==7)))
                        {
                            $invalidRecords[] = array_merge($data, ['error' => 'Plan Type Already Booked: The selected plan type is already assigned to another booking. Please choose a different plan type or verify the existing booking details before proceeding.']);
                            return; 
                        }
                    }
                    // Check if total hours exceed allowed hours
                    if ((Learner::where('seat_no', trim($data['seat_no']))
                        ->where('branch_id', getCurrentBranch())
                        ->where('learners.status', 1)
                        ->sum('hours') + $hours) > $total_hour) {
                            \Log::info('plan type exceed');
                        $invalidRecords[] = array_merge($data, ['error' => 'Your plan type exceeds the library total hours']);
                        return;
                    }else {
                        // Create new learner and associated records
                        if (empty($data['name']) || empty($data['email']) || empty($data['mobile']) || empty($hours) ||  empty($start_date) ) {
                            $invalidRecords[] = array_merge($data, ['error' => 'Missing essential data for creating learner']);
                            return;
                        }
                       
                        \Log::info('last else in Learner craete', [ 'status2' => $status]);
                        $learner = $this->createLearner($data, $hours, $dob, $payment_mode, $status, $plan, $planType, $seat, $start_date, $endDate, $joinDate, $is_paid, $planPrice, $pending_amount, $paid_date);
                    }
                } elseif($exist_check){
                    \Log::info('for renew data create learner detail and update learner DB', [ 'status1' => $status]);
                    $learnerData = Learner::where('branch_id', getCurrentBranch())
                    ->where('email', encryptData(trim($data['email'])))
                    ->where('status', 0)
                    ->first();
                   
                    $this->createLearnerDetail($learnerData->id, $plan,$status, $planType, $seat, $data, $start_date, $endDate, $joinDate, $hours, $is_paid, $planPrice, $pending_amount, $paid_date,$payment_mode);
                    \Log::info('Learner detail created', [
                        'learner_id' => $learnerData->id,
                        'plan' => $plan,
                        'status' => $status,
                        'plan_type' => $planType,
                        'seat' => $seat,
                    ]);
                   
                }else {
                    \Log::info('seat is not occupied Learner create', [ 'status1' => $status]);
                    // If seat is not occupied, directly create learner
                    $learner = $this->createLearner($data, $hours, $dob, $payment_mode, $status, $plan, $planType, $seat, $start_date, $endDate, $joinDate, $is_paid, $planPrice, $pending_amount, $paid_date);
                }
            }
        } else {
            \Log::info('When Status : 0 Previously Paid Seat info : Leaner', [ 'status0' => $status]);
            // Handling non-active status (status != 1)
            $exist_check = Learner::where('branch_id', getCurrentBranch())
                ->where('email', encryptData(trim($data['email'])))
                ->exists();
        
            if (Learner::where('branch_id', getCurrentBranch())
                ->where('email', encryptData(trim($data['email'])))
                ->where('status', 1)
                ->exists()) {
                \Log::info('You are already active');
                $invalidRecords[] = array_merge($data, ['error' => 'You are already active']);
                return;
            } elseif ($exist_check) {
                // Check if learner exists and update data
                $already_data = LearnerDetail::where('plan_start_date', $start_date)->exists();
                $learnerData = Learner::where('branch_id', getCurrentBranch())
                    ->where('email', encryptData(trim($data['email'])))
                    ->first();
                if ($already_data) {
                
                    // Update existing learner and learner detail
                    $this->updateLearner($learnerData, $data, $dob, $hours, $payment_mode, $status, $plan, $planType, $seat, $start_date, $endDate, $joinDate, $is_paid);
                }
                if ($learnerData) {
                    \Log::info('Check if learner detaill exists with status 0 then update the details');
                    // Update existing learner and learner detail
                    $this->createLearnerDetail($learnerData->id, $plan,$status, $planType, $seat, $data, $start_date, $endDate, $joinDate, $hours, $is_paid, $planPrice, $pending_amount, $paid_date,$payment_mode);
                } 
            } else {
                if (empty($data['name']) || empty($data['email']) || empty($data['mobile']) || empty($hours)  || empty($start_date) || empty($planPrice)) {
                    $invalidRecords[] = array_merge($data, ['error' => 'Missing essential data for creating learner']);
                    return;
                }
                \Log::info('Insert New Learner Info if Learner is not exists in DB Previously');
                // Create a new learner if they dont exist
                $learner = $this->createLearner($data, $hours, $dob, $payment_mode, $status, $plan, $planType, $seat, $start_date, $endDate, $joinDate, $is_paid, $planPrice, $pending_amount, $paid_date);
            }
        }
   
      
        $successRecords[] = $data;
    }

    public function exportCsv()
    {
        try {
            // Retrieve invalid records from session
            $invalidRecords = session('invalidRecords', []);

            // Check if there are invalid records
            if (empty($invalidRecords)) {
                return redirect()->back()->with('error', 'No invalid records found for export.');
            }

            // Set headers for CSV
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="invalid_records.csv"',
            ];

            // Callback for streaming the CSV
            $callback = function () use ($invalidRecords) {
                $file = fopen('php://output', 'w');

                // Check if file opens successfully
                if ($file === false) {
                    throw new \Exception('Unable to open the file for writing.');
                }

                // Set CSV headers
                $headerRow = array_keys(reset($invalidRecords));
                fputcsv($file, $headerRow);

                // Write each invalid record to the CSV
                foreach ($invalidRecords as $record) {
                    fputcsv($file, $record);
                }

                fclose($file);
            };

            return new StreamedResponse($callback, 200, $headers);

        } catch (\Exception $e) {
            // Catch and handle export-related errors
            return redirect()->back()->with('error', 'Failed to export CSV: ' . $e->getMessage());
        }
    }

    function createLearner($data, $hours, $dob, $payment_mode, $status, $plan, $planType, $seat, $start_date, $endDate, $joinDate, $is_paid, $planPrice, $pending_amount, $paid_date) {
         
        $yes = trim($data['locker'] ?? '');

        if (strtolower($yes) === 'yes') {
            $locker_amount = getLockerPrice($plan->id);
        } else {
            $locker_amount = 0;
        }
        $paid_amount=!empty($data['paid_amount']) ? trim($data['paid_amount']) : $planPrice->price;
       $total=$planPrice+$locker_amount;
       $discount_amount=$total-$paid_amount-$pending_amount;
        DB::beginTransaction();
    
        try {
            // Create learner entry
            \Log::info('Learner create function start');
            $learner = Learner::create([
                'library_id' => getLibraryId(),
                'branch_id'=>getCurrentBranch(),
                'name' => trim($data['name']),
                'email' => encryptData(trim($data['email'])),
                'password' =>!empty($data['mobile']) ? bcrypt(trim($data['mobile'])) : bcrypt(trim('12345678')),
                'mobile' =>!empty($data['mobile']) ? encryptData(trim($data['mobile'])) :null,
                'dob' => $dob,
                'hours' => trim($hours),
                'seat_no' => $seat,
                'address' => !empty($data['address']) ? trim($data['address']) : null,
                'status' => $status,
            ]);
    
            // Create learner detail entry
            $learner_detail = LearnerDetail::create([
                'learner_id' => $learner->id,
                'plan_id' => $plan->id,
                'plan_type_id' => $planType->id,
                'plan_price_id' => trim($data['plan_price']),
                'plan_start_date' => $start_date,
                'plan_end_date' => $endDate,
                'join_date' => $joinDate,
                'hour' => $hours,
                'seat_no' => $seat,
               'library_id' => getLibraryId(),
                'branch_id'=>getCurrentBranch(),
                'payment_mode' => $payment_mode,
                'is_paid' => $is_paid,
                'status' => $status,
            ]);
            
            // Create learner transaction entry
           
            LearnerTransaction::create([
                'learner_id' => $learner->id,
                'library_id' => getLibraryId(),
                'branch_id'=>getCurrentBranch(),
                'learner_detail_id' => $learner_detail->id,  // Corrected column name
                'total_amount' => $total,
                'paid_amount' => $paid_amount,
                'pending_amount' => $pending_amount,
                'paid_date' => $paid_date,
                'locker_amount' => $locker_amount,
                'discount_amount' => $discount_amount,
                'is_paid' => $pending_amount >0 ? 0 : 1,
            ]);
    
            // Commit the transaction if all inserts succeed
            DB::commit();
    
            // Update seat availability and learner data
          
            $this->dataUpdateNow($learner->id);
    
        } catch (\Exception $e) {
            // Rollback transaction on failure
            DB::rollBack();
    
            // Log the error
            \Log::error('Error in createLearnerDetail: ' . $e->getMessage());
    
            // Re-throw the exception to handle it further up
            throw $e;
        }
    }
    

    function createLearnerDetail($learner_id, $plan, $status, $planType, $seat, $data, $start_date, $endDate, $joinDate, $hours, $is_paid, $planPrice, $pending_amount, $paid_date,$payment_mode)
    {

     \Log::info('Learner detail id', ['learner_id' => $learner_id]);
        DB::beginTransaction();

        try {
            \Log::info('Learner detail function start');
             // update learner  entry
            Learner::where('id', $learner_id)->update([
                'mobile' =>  !empty($data['mobile']) ? encryptData(trim($data['mobile'])) : null,
                'hours' => trim($hours),
                'seat_no' => $seat,
                'address' => !empty($data['address']) ? trim($data['address']) : null,
                'status' => $status,
            ]);
            // Create learner detail entry
            $learner_detail = LearnerDetail::create([
                'learner_id' => $learner_id,
                'plan_id' => $plan->id,
                'plan_type_id' => $planType->id,
                'plan_price_id' => trim($data['plan_price']),
                'plan_start_date' => $start_date,
                'plan_end_date' => $endDate,
                'join_date' => $joinDate,
                'hour' => $hours,
                'seat_no' => $seat,
                'library_id' => getLibraryId(),
                'branch_id'=>getCurrentBranch(),
                'payment_mode' => $payment_mode,
                'is_paid' => $is_paid,
                'status' => $status,
            ]);
            $pending_amount =!empty($data['pending_amount']) ? trim($data['pending_amount']) : 0;
            $yes = trim($data['locker'] ?? '');

            if (strtolower($yes) === 'yes') {
                $locker_amount = getLockerPrice($plan->id);
            } else {
                $locker_amount = 0;
            }
        $paid_amount=!empty($data['paid_amount']) ? trim($data['paid_amount']) : $planPrice->price;
        $total=$planPrice+$locker_amount;
        $discount_amount=$total-$paid_amount-$pending_amount;
            // Create learner transaction entry
            LearnerTransaction::create([
                'learner_id' => $learner_id,
                'library_id' => getLibraryId(),
                'branch_id'=>getCurrentBranch(),
                'learner_detail_id' => $learner_detail->id,
               'total_amount' => $total,
                'paid_amount' => $paid_amount,
                'pending_amount' => $pending_amount,
                'locker_amount' => $locker_amount,
                'discount_amount' => $discount_amount,
                'paid_date' => $paid_date,
                 'is_paid' => $pending_amount >0 ? 0 : 1,
            ]);
             

        
            DB::commit();

            // $this->seat_availablity_update_now($seat->id, $planType->id);
            $this->dataUpdateNow($learner_id);

        } catch (\Exception $e) {
         
            DB::rollBack();

            \Log::error('Error in createLearnerDetail: ' . $e->getMessage());

            throw $e;
        }
    }
    
    function updateLearner($learnerData, $data, $dob, $hours, $payment_mode, $status, $plan, $planType, $seat, $start_date, $endDate, $joinDate, $is_paid) {
        \Log::info('updTE LEARNER function start');
        Learner::where('id', $learnerData->id)->update([
            'mobile' => !empty($data['mobile']) ? encryptData(trim($data['mobile'])) : null,
            'dob' => $dob,
            'hours' => trim($hours),
            'seat_no' => $seat,
            'address' => !empty($data['address']) ? trim($data['address']) : null,
            'status' => $status,
        ]);
    
        LearnerDetail::where('learner_id', $learnerData->id)
            ->where('plan_start_date', $start_date)
            ->update([
                'plan_id' => $plan->id,
                'plan_type_id' => $planType->id,
                'plan_price_id' => trim($data['plan_price']),
                'plan_start_date' => $start_date,
                'plan_end_date' => $endDate,
                'join_date' => $joinDate,
                'hour' => $hours,
                'seat_no' => $seat,
                'is_paid' => $is_paid,
                'status' => $status,
                'payment_mode' => $payment_mode,
            ]);
            
            $this->dataUpdateNow($learnerData->id);
    }

   

    function dataUpdateNow($learner_id){
       
       
    
        $userUpdate = Learner::where('branch_id',getCurrentBranch())->where('id',$learner_id)->where('status', 1)->first();
  
       
           $today = date('Y-m-d'); 
           $customerdatas=LearnerDetail::where('learner_id',$learner_id)->where('status',1)->get();
          
           $extend_day = getExtendDays();
           foreach($customerdatas as $customerdata){
                $planEndDateWithExtension = Carbon::parse($customerdata->plan_end_date)->addDays($extend_day);
                $current_date = Carbon::today();
                $hasFuturePlan = LearnerDetail::where('learner_id', $userUpdate->id)
                ->where('plan_end_date', '>', $current_date->copy()->addDays(5))->where('status',0)
                ->exists();
                $hasPastPlan = LearnerDetail::where('learner_id', $userUpdate->id)
                    ->where('plan_end_date', '<', $current_date->copy()->addDays(5))
                    ->exists();

                $isRenewed = $hasFuturePlan && $hasPastPlan;
                if ($planEndDateWithExtension->lte($today)) {
                    $userUpdate->update(['status' => 0]);
                    $customerdata->update(['status' => 0]);
                }elseif ($isRenewed) {
                    LearnerDetail::where('learner_id', $userUpdate->id)->where('plan_start_date', '<=', $today)->where('plan_end_date', '>', $current_date->copy()->addDays(5))->update(['status'=>1]);
                    LearnerDetail::where('learner_id', $userUpdate->id)->where('plan_end_date', '<', $today)->update(['status'=>0]);
                }else{
                    $userUpdate->update(['status' => 1]);
                    LearnerDetail::where('learner_id', $userUpdate->learner_id)->where('status',0)->where('plan_start_date','<=',$today)->where('plan_end_date','>',$today)->update(['status' => 1]);
                }
           }
           
      

       
    }
    protected function parseDate($date)
    {
        $formats = ['d/m/Y', 'm/d/Y', 'Y-m-d', 'd-m-Y'];
        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, $date)->format('Y-m-d');
            } catch (\Exception $e) {
                continue;
            }
        }
        return false;
    }

    private function getPaymentMode($paymentMode)
    {
        return match ($paymentMode) {
            'Online' => 1,
            'Offline' => 2,
            'Paylater' => 3,
            default => 2,
        };
    }
    public function clearSession(Request $request)
    {
        $request->session()->forget('invalidRecords');
        
        return response()->json(['status' => 'success']);
    }
    // master create
    protected function validateMasterInsert($data, &$successRecords, &$invalidRecords, $library_id)
    {
       
      
        $validator = Validator::make($data, [
            'branch_name'=>'required',
            'Operating_hour' => 'required|integer',
            'start_time' => ['required', function($attribute, $value, $fail) {
                if (!preg_match('/^(?:[01]?\d|2[0-3]):[0-5]\d$/', $value)) {
                    $fail($attribute.' must be a valid time (HH:MM format).');
                }
            }],
            'end_time' => ['required', function($attribute, $value, $fail) {
                if (!preg_match('/^(?:[01]?\d|2[0-3]):[0-5]\d$/', $value)) {
                    $fail($attribute.' must be a valid time (HH:MM format).');
                }
            }],
            'total_seat' => 'required|integer',
            'fullday_price' => 'required|integer',
            'halfday_price' => 'required|integer',
            
            
        ]);
      
        if ($validator->fails()) {
          
            $errors = $validator->errors()->all();
        
            $errorMessages = implode(', ', $errors);
        
            $invalidRecords[] = array_merge($data, ['error' => $errorMessages]);
            return;
        }
        if (!trim($data['total_seat']) || trim($data['total_seat']) <= 0) {
            $invalidRecords[] = array_merge($data, ['error' => 'Invalid Seats']);
            return;
        }
       
        $libraryData = Library::where('id', $library_id)->first();

        if ($libraryData) {
            $seatLimit = ($libraryData->library_type == 1) ? 50 : (($libraryData->library_type == 2) ? 100 : null);
        
            if ($seatLimit !== null && trim($data['total_seat']) > $seatLimit) {
                $invalidRecords[] = array_merge($data, ['error' => 'Total seats not your Subscription according']);
                return;  
            }
        } else {
          
            $invalidRecords[] = array_merge($data, ['error' => 'Library not found']);
        }
        
        $start_time = Carbon::createFromFormat('H:i', trim($data['start_time']));
        $end_time = Carbon::createFromFormat('H:i', trim($data['end_time']));
       
        if ($end_time->lessThan($start_time)) {
            $invalidRecords[] = array_merge($data, ['error' => 'End time must be later than start time.']);
            return; 
        }
        
        $totalHours = $start_time->diffInHours($end_time);
        
        if ($totalHours != trim($data['Operating_hour'])) {
           
            $invalidRecords[] = array_merge($data, ['error' => 'Operating hour does not match the difference between start and end times.']);
            return;
        }
       
        // Using database transaction for atomic operations
        DB::transaction(function () use ($data, $library_id,$start_time, $end_time, $totalHours,&$invalidRecords,&$successRecords) {
          
              // Update or create the operating hours
                if(isset($data['allday']) && (trim($data['allday'])=='yes')){
                    $operatinghour=24;
                    $allday=true;
                }else{
                    $operatinghour=trim($data['Operating_hour']);
                    $allday=false;
                }
                $branch_name=trim($data['branch_name']);
                $slug = Str::slug($branch_name);
                $branch = Branch::updateOrCreate(
                    ['name' => $branch_name, 'library_id' => $library_id],
                    ['slug' => $slug]
                );
                            
                if (!$branch) {
                    \Log::info('Branch creation returned null');
                
                }
            
                $branch_id = $branch->id;
                
                $hourData = Hour::withoutGlobalScopes()->updateOrCreate(
                    [
                        'library_id' => $library_id,
                        'branch_id' => $branch_id
                    ],
                    [
                        'hour' => $operatinghour,
                        'seats' => trim($data['total_seat']),
                    ]
                );
            
                if (!$hourData) {
                    \Log::info('Hour creation returned null', [
                        'branch_id' => $branch_id,
                        'library_id' => $library_id,
                        'data' => $data,
                    ]);
                    
                }

               if ($branch_id && (trim($data['locker_amount']) || trim($data['extend_day']))) {
                $updateData = [];

                if (trim($data['locker_amount'])) {
                    $updateData['locker_amount'] = trim($data['locker_amount']);
                }

                if (trim($data['extend_day'])) {
                    $updateData['extend_days'] = trim($data['extend_day']);
                }

                Branch::updateOrCreate(
                    ['id' => $branch_id],
                    $updateData
                );
            }

          
            // Define slot configurations
            $slots = $this->defineSlots($start_time, $end_time, $totalHours,$allday);
           
            // Check user permissions and handle slot updates
            $this->handleSlotUpdates($slots, $library_id, $invalidRecords, $data,$successRecords);
    
            // Define plans
            $plans = [
                ['name' => '1 MONTHS', 'plan_id' => 1,'type'=>'MONTH'],
                ['name' => '3 MONTHS', 'plan_id' => 3, 'type'=>'MONTH'],
                ['name' => '6 MONTHS', 'plan_id' => 6,'type'=>'MONTH' ],
                ['name' => '12 MONTHS', 'plan_id' => 12, 'type'=>'MONTH'],
                // ['name' => '1 WEEK', 'plan_id' => 1, 'type'=>'WEEK'],
                // ['name' => '5 DAY', 'plan_id' => 5, 'type'=>'DAY'],
                
            ];
           
            // Handle plans updates
            $this->handlePlanUpdates($plans, $library_id,$invalidRecords,$successRecords);
    
            // Handle price updates
            $this->handlePlanPrices($library_id,$branch_id, trim($data['fullday_price']), trim($data['halfday_price']),trim($data['allday_price']), trim($data['fullnight_price']));
            
           
        });
        
    }
    
    // Function to define plantype
    private function defineSlots($start_time, $end_time, $totalHours, $allday)
    {
        $slots = [
            ['type_id' => 1, 'name' => 'Full Day', 'start_time' => $start_time, 'end_time' => $end_time, 'slot_hours' => $totalHours],
            ['type_id' => 2, 'name' => 'First Half', 'start_time' => $start_time, 'end_time' => $start_time->copy()->addHours($totalHours / 2), 'slot_hours' => $totalHours / 2],
            ['type_id' => 3, 'name' => 'Second Half', 'start_time' => $start_time->copy()->addHours($totalHours / 2), 'end_time' => $end_time, 'slot_hours' => $totalHours / 2],
            // ['type_id' => 4, 'name' => 'Hourly Slot 1', 'start_time' => $start_time, 'end_time' => $start_time->copy()->addHours($totalHours / 4), 'slot_hours' => $totalHours / 4],
            // ['type_id' => 5, 'name' => 'Hourly Slot 2', 'start_time' => $start_time->copy()->addHours($totalHours / 4), 'end_time' => $start_time->copy()->addHours(($totalHours / 4) * 2), 'slot_hours' => $totalHours / 4],
            // ['type_id' => 6, 'name' => 'Hourly Slot 3', 'start_time' => $start_time->copy()->addHours(($totalHours / 4) * 2), 'end_time' => $start_time->copy()->addHours(($totalHours / 4) * 3), 'slot_hours' => $totalHours / 4],
            // ['type_id' => 7, 'name' => 'Hourly Slot 4', 'start_time' => $start_time->copy()->addHours(($totalHours / 4) * 3), 'end_time' => $end_time, 'slot_hours' => $totalHours / 4],
        ];
    
        if ($allday === true) {
             
            $slots[] = ['type_id' => 8, 'name' => 'All Day', 'start_time' => $start_time, 'end_time' => $start_time, 'slot_hours' => 24];
            $slots[] = ['type_id' => 9, 'name' => 'Full Night', 'start_time' => $end_time, 'end_time' => $start_time, 'slot_hours' => 24 - $totalHours];
        }
        
        return $slots;
    }
    
    
    // Function to handle plantype updates
    private function handleSlotUpdates($slots, $library_id, &$invalidRecords, $data,&$successRecords)
    {

       
        Log::info('Starting handleSlotUpdates', ['library_id' => $library_id, 'slots' => $slots]);

        $user = Library::withoutGlobalScopes()->find($library_id);
        Log::info('User fetched', ['user' => $user]);
      
        foreach ($slots as $slot) {
            Log::info('Processing slot', ['slot' => $slot]);

            $hasPermission = true; 

            if ($slot['type_id'] == 1 && !$user->can('has-permission', 'Full Day')) {
                $hasPermission = false;
            } elseif ($slot['type_id'] == 2 && !$user->can('has-permission', 'First Half')) {
                $hasPermission = false;
            } elseif ($slot['type_id'] == 3 && !$user->can('has-permission', 'Second Half')) {
                $hasPermission = false;
            } elseif ($slot['type_id'] == 8 && !$user->can('has-permission', 'All Day')) {
                $hasPermission = false;
            }elseif ($slot['type_id'] == 9 && !$user->can('has-permission', 'Full Night')) {
                $hasPermission = false;
            }
            //  elseif ($slot['type_id'] == 4 && !$user->can('has-permission', 'Hourly Slot 1')) {
            //     $hasPermission = false;
            // } elseif ($slot['type_id'] == 5 && !$user->can('has-permission', 'Hourly Slot 2')) {
            //     $hasPermission = false;
            // } elseif ($slot['type_id'] == 6 && !$user->can('has-permission', 'Hourly Slot 3')) {
            //     $hasPermission = false;
            // } elseif ($slot['type_id'] == 7 && !$user->can('has-permission', 'Hourly Slot 4')) {
            //     $hasPermission = false;
            // }
           
            if (!$hasPermission) {
                // $invalidRecords[] = array_merge($data, ['error' => 'No permission for slot ' . $slot['type_id']]);
                continue; 
            }

            $start_time_new = Carbon::parse($slot['start_time'])->format('H:i');
            $end_time_new = Carbon::parse($slot['end_time'])->format('H:i');
            Log::info('Parsed time', ['start_time_new' => $start_time_new, 'end_time_new' => $end_time_new]);
           
            // Update or create plan type
            $planType=PlanType::withoutGlobalScopes()->updateOrCreate(
                ['library_id' => $library_id, 'day_type_id' => $slot['type_id']],
                [
                    'name' => $slot['name'],
                    'start_time' => $start_time_new,
                    'end_time' => $end_time_new,
                    'slot_hours' => $slot['slot_hours'],
                    'image'=>'public/img/booked.png',
                ]
            );
            if ($planType) {
              
                $successRecords[] = array_merge($data, ['success' => 'Plan type updated or created']);
            } else {
                $invalidRecords[] = array_merge($data, ['error' => 'Failed to update or create plan type']);
            }
    
            Log::info('Plan type updated or created', ['slot' => $slot]);
            
        }
       
        Log::info('handleSlotUpdates finished successfully.');
    }

    
    // Function to handle plan updates
    private function handlePlanUpdates($plans, $library_id, &$invalidRecords, &$successRecords)
    {
        
        foreach ($plans as $plan) {
            try {
                // Update or create a plan based on library_id and plan_id
                $updatedPlan = Plan::withoutGlobalScopes()->updateOrCreate(
                    ['library_id' => $library_id, 'plan_id' => $plan['plan_id']],
                    ['name' => $plan['name'], 'type'=>$plan['type']]
                );
    
                // If plan is successfully created or updated, add to success records
                if ($updatedPlan) {
                    
                    $successRecords[] = [
                        'library_id' => $library_id,
                        'plan_id' => $plan['plan_id'],
                        'status' => 'success',
                        'message' => 'Plan updated or created successfully'
                    ];
                } else {
                    // If something went wrong, track it as a failure
                    $invalidRecords[] = [
                        'library_id' => $library_id,
                        'plan_id' => $plan['plan_id'],
                        'status' => 'error',
                        'message' => 'Failed to update or create plan'
                    ];
                }
            } catch (\Exception $e) {
                // Handle any exceptions and log the error
                Log::error('Error updating or creating plan', ['plan' => $plan, 'error' => $e->getMessage()]);
                
                // Add to invalid records for later use
                $invalidRecords[] = [
                    'library_id' => $library_id,
                    'plan_id' => $plan['plan_id'],
                    'status' => 'error',
                    'message' => 'Exception: ' . $e->getMessage()
                ];
            }
        }
    }
    
    
    // Function to handle price updates
    private function handlePlanPrices($library_id,$branch_id, $fullday_price, $halfday_price, $allday_price, $fullnight_price)
    {
       
        $plans_prices = Plan::withoutGlobalScopes()->where('library_id', $library_id)->where('plan_id',1)->where('type', 'LIKE', '%MONTH%')->get();
 
        
       
        $plantype_prices = PlanType::withoutGlobalScopes()->where('library_id', $library_id)->get();

        foreach ($plans_prices as $plans_price) {
            foreach ($plantype_prices as $plantype_price) {
                // Initialize price variable
                $price = 0;

                // Calculate prices based on the type of plan
                if ($plantype_price->day_type_id == 1) {
                    $price = $fullday_price * $plans_price->plan_id;
                } elseif ($plantype_price->day_type_id == 2 || $plantype_price->day_type_id == 3) {
                    $price = $halfday_price * $plans_price->plan_id;
                }
                
                elseif($plantype_price->day_type_id == 8){
                    $price = $allday_price * $plans_price->plan_id;
                }elseif($plantype_price->day_type_id == 9){
                    $price = $fullnight_price * $plans_price->plan_id;
                }
                // elseif (in_array($plantype_price->day_type_id, [4, 5, 6, 7])) {
                //     $price = $hourly_price * $plans_price->plan_id;
                // }
                // Check if the plan_type_id exists before inserting
                if (PlanType::withoutGlobalScopes()->where('id', $plantype_price->id)->exists()) {
                    // Update or create plan type price
                    PlanPrice::withoutGlobalScopes()->updateOrCreate(
                        ['library_id' => $library_id,'branch_id'=>$branch_id, 'plan_id' => $plans_price->id, 'plan_type_id' => $plantype_price->id],
                        ['price' => $price]
                    );
                } else {
                    Log::warning("Attempted to insert price for non-existing plan type id: " . $plantype_price->id);
                }
            }
        }
    }

    

    private function expenseAdd($library_id){
        $data=['Electricity Bill','Water Camper','Internet Wi-Fi','Papers','Repair & Maintenance','Tea & Snacks','Petrol','Flex Oreinting'];
        foreach ($data as $expenseName) {
            Expense::withoutGlobalScopes()->create([
                'library_id' => $library_id,
                'name' => $expenseName
            ]);
        }
    }

    public function renewConfigration(){
        $library_id=getLibraryId();
        $today = date('Y-m-d');
        $today_renew = LibraryTransaction::where('library_id', getLibraryId())
            ->where('is_paid', 1)
            ->where('status', 0)
            ->where('start_date', '<=', $today)->first();
        if($today_renew){
            Library::where('id',$library_id)->update([
                'library_type'=>$today_renew->subscription
    
            ]);
        }
       
    
        $user = Auth::user();
        $planType=PlanType::withoutGlobalScopes()->where('library_id', $library_id)->first();
        
        if($planType){
            $start_time = Carbon::parse($planType->start_time);
            $end_time = Carbon::parse($planType->end_time);
            $totalHours = $planType->slot_hours;
            if($totalHours==24){
                $allday=true;
            }else{
                $allday=false;
            }

            $slots = $this->defineSlots($start_time, $end_time, $totalHours ,$allday);
           
            foreach ($slots as $slot) {
               
                $hasPermission = true; 
              
                if ($slot['type_id'] == 1 && !$user->can('has-permission', 'Full Day')) {
                    $hasPermission = false;
                } elseif ($slot['type_id'] == 2 && !$user->can('has-permission', 'First Half')) {
                    $hasPermission = false;
                } elseif ($slot['type_id'] == 3 && !$user->can('has-permission', 'Second Half')) {
                    $hasPermission = false;
                } elseif ($slot['type_id'] == 4 && !$user->can('has-permission', 'Hourly Slot 1')) {
                    $hasPermission = false;
                } elseif ($slot['type_id'] == 5 && !$user->can('has-permission', 'Hourly Slot 2')) {
                    $hasPermission = false;
                } elseif ($slot['type_id'] == 6 && !$user->can('has-permission', 'Hourly Slot 3')) {
                    $hasPermission = false;
                } elseif ($slot['type_id'] == 7 && !$user->can('has-permission', 'Hourly Slot 4')) {
                    $hasPermission = false;
                }elseif ($slot['type_id'] == 8 && !$user->can('has-permission', 'All Day')) {
                    $hasPermission = false;
                }elseif ($slot['type_id'] == 9 && !$user->can('has-permission', 'Full Night')) {
                    $hasPermission = false;
                }

                $existPlantype=PlanType::withoutGlobalScopes()->where('library_id',$library_id)->where('day_type_id',$slot['type_id'])->first();
                $id = $existPlantype ? $existPlantype->id : null;
                $data = PlanType::withTrashed()->find($id);
                if ($existPlantype) {
                    // If the plan type exists but is soft-deleted, restore it
                    if ($existPlantype->trashed()) {
                        $data->restore();
                    }
                }
              
                if (!$hasPermission) {
                    if ($existPlantype) {
                        $existPlantype->delete(); // Soft-delete if no permission
                    }
                } else{
                    $start_time_new = Carbon::parse($slot['start_time'])->format('H:i');
                    $end_time_new = Carbon::parse($slot['end_time'])->format('H:i');
                    Log::info('Parsed time', ['start_time_new' => $start_time_new, 'end_time_new' => $end_time_new]);

                    // Update or create plan type
                    PlanType::withoutGlobalScopes()->updateOrCreate(
                        ['library_id' => $library_id, 'day_type_id' => $slot['type_id']],
                        [
                            'name' => $slot['name'],
                            'start_time' => $start_time_new,
                            'end_time' => $end_time_new,
                            'slot_hours' => $slot['slot_hours'],
                            'image'=>'public/img/booked.png',
                        ]
                    );

                    Log::info('Plan type updated or created', ['slot' => $slot]);

                }

               
            }
            
            $plans_prices = Plan::withoutGlobalScopes()->where('library_id', $library_id)->withTrashed()->get();
            $plantype_prices = PlanType::withoutGlobalScopes()->where('library_id', $library_id)->withTrashed()->get();
            $onemonthplan = Plan::withoutGlobalScopes()->where('library_id', $library_id)->where('plan_id', 1)->first();
    
            foreach ($plans_prices as $plans_price) {
                foreach ($plantype_prices as $plantype_price) {
    
                    // Fetch the full-day price for the current plan and plan type
                    $fullday_price = PlanPrice::withoutGlobalScopes()->where('library_id', $library_id)->where('plan_type_id', $planType->id)
                                            ->where('plan_id', $onemonthplan->id)
                                            ->withTrashed()
                                            ->first();
                    
                    $price = 0;
    
                    // Calculate prices based on the type of plan
                    if ($plantype_price->day_type_id == 1) {
                        $price = $fullday_price->price * $plans_price->plan_id;
                    } elseif ($plantype_price->day_type_id == 2 || $plantype_price->day_type_id == 3) {
                        $price = ($fullday_price->price * $plans_price->plan_id) / 2;
                    } elseif (in_array($plantype_price->day_type_id, [4, 5, 6, 7])) {
                        $price = ($fullday_price->price * $plans_price->plan_id) / 4;
                    }
                    
                    
                    $existing_price = PlanPrice::withoutGlobalScopes()->where('library_id', $library_id)->where('plan_type_id', $plantype_price->id)
                                            ->where('plan_id', $plans_price->id)
                                            ->withTrashed()
                                            ->first();
                    
                    if ($existing_price) {
                        // If price exists and plan type is not deleted
                        if (!$plantype_price->trashed()) {
                            // If the existing price is deleted, restore it
                            if ($existing_price->trashed()) {
                                $existing_price->restore();
                            }
                            // Update the price
                            $existing_price->price = $price;
                            $existing_price->save();
                        } else {
                            // If plan type is deleted, ensure price is deleted
                            $existing_price->delete();
                        }
                    } else {
                        // If the plan type is not deleted and price doesn't exist, insert new price
                        if (!$plantype_price->trashed()) {
                            PlanPrice::create([
                                'library_id' =>$library_id,
                                'plan_type_id' => $plantype_price->id,
                                'plan_id'      => $plans_price->id,
                                'price'        => $price,
                            ]);
                        }
                    }
                }
            }

            $this->statusUpdate();
            return response()->json(['message' => 'Plan Configration successfully renewed!'], 200);
        }

        
        return response()->json(['error' => 'Plan not found!'], 404);

    }

    protected function statusUpdate(){
        $today = date('Y-m-d');
        Library::where('id',Auth::user()->id)->update([
            'is_paid'=>1,
            'status'=>1

        ]);
        LibraryTransaction::where('library_id', Auth::user()->id)
            ->where('is_paid', 1)
            ->where('end_date', '>=', $today)->update([
              
                'status'=>1,
                'is_paid'=>1
    
            ]);
        LibraryTransaction::where('library_id', Auth::user()->id)
        ->where('is_paid', 1)
        ->where('end_date', '<', $today)
        ->where('start_date', '<', $today)->update([
            
            'status'=>0

        ]);
    }

    // learner export functionality
    public function exportLearnerCSV()
    {
        Log::info("Export CSV function called.");  // Track function call start
        
        $fileName = 'learners.csv';
        
        try {
            // Create the streamed response
            $response = new StreamedResponse(function () {
                $handle = fopen('php://output', 'w');
                Log::info("CSV file opened for output.");

                // Set CSV headers
                fputcsv($handle, ['name', 'email', 'mobile', 'seat_no', 'dob', 'address', 'plan', 'plan_type', 'plan_price', 'join_date', 'start_date', 'end_date', 'paid_amount', 'paid_date', 'payment_mode']);
                Log::info("CSV headers written.");

                // Fetch learners data
                $learners = Learner::where('library_id', Auth::user()->id)->with(['learnerDetails', 'learnerTransactions'])->get();
                Log::info("Fetched learners data.", ['count' => $learners->count()]);

                foreach ($learners as $learner) {
                    if ($learner->learnerDetails->isEmpty() && $learner->learnerTransactions->isEmpty()) {
                        fputcsv($handle, [$learner->name, $learner->email, $learner->mobile, $learner->seat_no, $learner->dob, $learner->address]);
                        Log::info("Wrote learner without details or transactions.", ['learner' => $learner->id]);
                    } else {
                        foreach ($learner->learnerDetails as $detail) {
                            foreach ($learner->learnerTransactions as $transaction) {
                                fputcsv($handle, [
                                    $learner->name,
                                    $learner->email,
                                    $learner->mobile,
                                    $learner->seat_no,
                                    $learner->dob,
                                    $learner->address,
                                    $detail->plan->name, 
                                    $detail->planType->name, 
                                    $detail->plan_price_id,
                                    $detail->join_date,
                                    $detail->plan_start_date,
                                    $detail->plan_end_date,
                                    $transaction->paid_amount,
                                    $transaction->paid_date,
                                    $learner->payment_mode == 1 ? 'Online' : ($learner->payment_mode == 2 ? 'Offline' : 'Pay Later'),
                                ]);
                                Log::info("Wrote learner with details and transaction.", ['learner' => $learner->id]);
                            }
                        }
                    }
                }

                fclose($handle);  // Close the output stream
                Log::info("CSV file output closed.");
            });

            $response->headers->set('Content-Type', 'text/csv');
            $response->headers->set('Content-Disposition', 'attachment; filename="' . $fileName . '"');
            Log::info("Response headers set.");

            return $response;

        } catch (\Exception $e) {
            // Catch any exception, log it, and throw an error
            Log::error("Error occurred in CSV export.", ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to export CSV'], 500);
        }
    }

   
    
    
    

}
