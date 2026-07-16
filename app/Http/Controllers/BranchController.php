<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\City;
use App\Models\Floor;
use App\Models\Hour;
use App\Models\Library;
use App\Models\LibraryUser;
use App\Models\Plan;
use App\Models\State;
use App\Services\LibraryConfigurationService;
use App\Services\LibraryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class BranchController extends Controller
{
    public function switch(Request $req)
    {
        $req->validate([
            'branch_id' => 'required|integer|min:0'
        ]);

        $id = $req->branch_id;
        $user = getAuthenticatedUser();
     
        // if ($id > 0 && ! $user->branches->contains('id', $id)) {
        //     abort(403);
        // }
        if (Auth::guard('library')->check()){
            Library::where('id',$user->id)->update([
                'current_branch' => $id,
            ]);
        }elseif(Auth::guard('library_user')->check()){
            LibraryUser::where('id',$user->id)->update([
                'current_branch' => $id,
            ]);
        }
       
        session()->put('branch_switched', true);
        session()->put('branch_switched_at', time());

        return back();
    }

   public function index(LibraryService $libraryService)
    {
        $library = getLibrary();

       if ($library->is_paid == 1 && Branch::where('library_id',$library->id)->count() == 0) {

        $redirectUrl = $libraryService->checkLibraryStatus();

        if ($redirectUrl) {
            return redirect()->to($redirectUrl);
        }
    }

          $branches = [];

            if (Auth::guard('library')->check()) {
                $user = Auth::guard('library')->user();
                $branches = $user->branches; // Assuming a 'branches' relationship exists
            }elseif (Auth::guard('library_user')->check()) {
                $user = Auth::guard('library_user')->user();

                // Assuming $user->branch_id is already an array
                $branchIds = $user->branch_id;

                if (is_array($branchIds)) {
                    $branches = Branch::whereIn('id', $branchIds)->get();
                }
            }
        return view('register.branch-list',compact('branches'));
    }
    public function branchForm($id = null)
    {
        $branch = $id ? Branch::findOrFail($id) : null;
        $states = State::where('is_active', 1)->get();
        $cities = City::where('is_active', 1)->get();
        $features = DB::table('features')->whereNull('deleted_at')->get();

        return view('register.branch-update', compact('branch', 'states', 'cities', 'features'));
    }

    public function store(Request $request)
    {
        
        $validation = branchCountValidation();
       
       if ($validation['success']) {
            return back()->withInput()->with('error', $validation['message']);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'display_name' => 'nullable|string|max:255',
            'email' => 'required|email',
            'mobile' => 'required|digits:10',
           
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,svg,webp|max:2048',
            'library_images.*' => 'nullable|image|mimes:jpeg,png,jpg,svg,webp|max:2048',
            'locker_amount'=>'required',
            'token_money'=>'nullable',
            'upi_id'          => 'nullable',
            'extend_days'=>'required',
            'hour'=>'required',
            'seats'=>'required',
            'founder_day'=>'required',
             'monthdays' => 'nullable|integer|in:28,30',
            'plans' => 'required|array|min:1',
            'plans.*' => 'string',
            'working_days' => 'nullable',
            'description'=>'nullable',
            'library_category' => 'nullable',
            'library_address' => 'required|string',
            'state_id' => 'nullable|integer',
            'city_id' => 'nullable|integer',
            'library_zip' => 'nullable|digits:6',
           
            // 'floors' => 'nullable|array',
            // 'floors.*.name' => 'required_with:floors.*.from,floors.*.to',
            // 'floors.*.from' => 'required_with:floors.*.name,floors.*.to|integer',
            // 'floors.*.to'   => 'required_with:floors.*.name,floors.*.from|integer|gte:floors.*.from',
           
        ]);
   
       
      
        

        $alreadyHave=Plan::where('library_id', getLibraryId())
        ->where('plan_id', 1)
        ->where('type', 'MONTH')->exists();
        $hasMonthPlan = false;

        foreach ($validated['plans'] as $plan) {
            [$number, $type] = explode(' ', $plan);
            if (strtoupper($type) === 'MONTH') {
                $hasMonthPlan = true;
                break;
            }
        }

        if (!$hasMonthPlan && !$alreadyHave) {
            return back()
                ->withInput()
                ->withErrors([
                    'plans' => 'At least one MONTH plan is required.'
                ]);
        }

          DB::beginTransaction();

        try {

        $validated['library_id']=getLibraryId();

        $slug = Str::slug($request->name);

        $exists = Branch::where('slug', $slug)
            ->where('library_id', getLibraryId())
            ->exists();

        if ($exists) {
            return back()
                ->withInput()
                ->withErrors(['name' => 'This branch name already exists in this library.']);
        }

   
        $hour = $validated['hour'];
        $seats = $validated['seats'];
        unset($validated['hour'], $validated['seats']); // remove from $validated
        
         // ---------- FLOOR SEAT VALIDATION ----------
         $floors = collect($request->floors)
        ->filter(fn ($floor) =>
            filled($floor['name']) ||
            filled($floor['from']) ||
            filled($floor['to'])
        )
        ->values()
        ->toArray();

        $validated['display_name']=$validated['display_name'] ?? $validated['name'];

       $totalFloorSeats = 0;

        if (!empty($floors)) {

            foreach ($floors as $index => $floor) {

                if (empty($floor['from']) || empty($floor['to'])) {
                    return back()
                        ->withInput()
                        ->with('error', 'Seat range is required for each floor');
                }

                if ($floor['to'] < $floor['from']) {
                    return back()
                        ->withInput()
                        ->with(
                            'error',
                            'Seat To must be greater than or equal to Seat From'
                        );
                }

                $floorSeatCount = ($floor['to'] - $floor['from']) + 1;
                $totalFloorSeats += $floorSeatCount;
            }

            if ($totalFloorSeats > $seats) {
                return back()
                    ->withInput()
                    ->with(
                        'error',
                        "Total floor seats ({$totalFloorSeats}) cannot exceed branch seats ({$seats})"
                    );
            }
        }

        $branchData = collect($validated)->except([
            'plans',
            'monthdays',
            'floors',
        ])->toArray();

        // ---------- FLOOR SEAT End VALIDATION ----------

        $branch = new Branch($branchData);

        // Handle logo upload
        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $fileName = 'logo_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $destinationFolder = public_path('upload/library_logo');
            if (!File::exists($destinationFolder)) {
                File::makeDirectory($destinationFolder, 0777, true);
            }
            $file->move($destinationFolder, $fileName);
            $branch->logo = 'upload/library_logo/' . $fileName;
        }

        // Handle features (save as JSON)
        if ($request->has('features')) {
            $branch->features = json_encode($request->features);
        }
       
        // Google map
        $branch->google_map = $request->google_map;
        $branch->slug = $slug;

        $branch->save();
         // Save hour and seats in the hour table
        Hour::create([
            'branch_id' => $branch->id,
            'library_id' => getLibraryId(),
            'hour' => $request->hour,
            'seats' => $request->seats,
        ]);
        // Plan add
        if ($request->has('plans')) {

            // 1️⃣ Find base month days (from 1 MONTH)
            $baseMonthDays = null;

            foreach ($request->plans as $plan) {
                [$number, $type] = explode(' ', $plan);

                if ((int)$number === 1 && strtoupper($type) === 'MONTH') {
                    $baseMonthDays = $request->monthdays ?: null;
                    break;
                }
            }

            // 2️⃣ Save plans using helper
            foreach ($request->plans as $plan) {

                [$number, $type] = explode(' ', $plan);

                $number = (int) $number;
                $type   = strtoupper($type);

                $totalDays = calculatePlanDays(
                    $number,
                    $type,
                    $baseMonthDays
                );

                $monthdays = ($type === 'MONTH') ? $baseMonthDays : null;

                Plan::create([
                    'library_id' => getLibraryId(),
                    'plan_id'    => $number,
                    'type'       => $type,
                    'name'       => $plan,
                    'monthdays'  => $monthdays,   // base (28/30)
                   
                ]);
            }
        }


       // Add Floor
        if (!empty($floors)) {

            foreach ($floors as $index => $floor) {

                $from = (int) $floor['from'];
                $to   = (int) $floor['to'];

                Floor::create([
                    'branch_id'   => $branch->id,
                    'name'        => $floor['name'],
                    'floor_no'    => $index + 1,
                    'from_seat'   => $from,
                    'to_seat'     => $to,
                    'total_seats' => ($to - $from) + 1,
                ]);
            }
        }

        // Handle multiple image uploads
        if ($request->hasFile('library_images')) {
            foreach ($request->file('library_images') as $image) {
                $fileName = 'img_' . time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                $destinationFolder = public_path('upload/library_images');
                if (!File::exists($destinationFolder)) {
                    File::makeDirectory($destinationFolder, 0777, true);
                }
                $image->move($destinationFolder, $fileName);
                
            }
        }
        DB::commit();
        return redirect()->route('branch.list')->with('success', 'Branch added successfully.');

        } catch (\Exception $e) {

            DB::rollBack();

            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'display_name' => 'required|string|max:255',
            'library_category' => 'nullable',
            'working_days' => 'nullable',
            'mobile' => 'required|string|max:10',
            'email' => 'required|email',
            'library_address' => 'required|string',
            'library_zip' => 'required|string|max:6',
            'state_id' => 'required|exists:states,id',
            'city_id' => 'required|exists:cities,id',
            'library_logo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:200|dimensions:max_width=250,max_height=250',
            'features' => 'nullable|array', 
            'features.*' => 'integer',
            'google_map'=>'nullable',
            'description'=>'nullable',
            'locker_amount'=>'nullable',
             'upi_id'=>'nullable',
            'token_money'=>'nullable',
            'extend_days'=>'nullable',
            'longitude'=>'required',
            'latitude'=>'required',
             'library_images' => 'nullable|array|max:4',
            'library_images.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'fixed_billing_date'=>'nullable|integer|min:1|max:31',
        ]);
        
       
        if ($request->hasFile('library_images')) {
            $uploadedFiles = [];
            
            foreach ($request->file('library_images') as $file) {
                $library_imageNewName = "library_img_" . uniqid() . '.' . $file->getClientOriginalExtension();
                $destinationFolder = public_path('upload/library_images');
                if (!File::exists($destinationFolder)) {
                    File::makeDirectory($destinationFolder, 0777, true);
                }
                $file->move($destinationFolder, $library_imageNewName);
                $uploadedFiles[] = 'upload/library_images/' . $library_imageNewName;
            }
        } else {
            $uploadedFiles = []; 
        }
        
        // Retrieve existing images from database
        $existingImages = json_decode($library->library_images ?? '[]', true);
        
        // Handle deleted images
        $deletedImages = $request->input('deleted_images', []);
        $remainingImages = array_diff($existingImages, $deletedImages);
        
        // Merge new and remaining images
        $finalImages = array_merge($remainingImages, $uploadedFiles);
        
        // Update only if images exist
        if (!empty($finalImages)) {
            $validated['library_images'] = json_encode($finalImages);
        } else {
            unset($validated['library_images']); 
        }
        if ($request->hasFile('library_logo')) {
            $library_logo = $request->file('library_logo');
            $library_logoNewName = "library_logo_" . time() . '.' . $library_logo->getClientOriginalExtension();
            $destinationFolder = public_path('upload/library_logo');
            if (!File::exists($destinationFolder)) {
                File::makeDirectory($destinationFolder, 0777, true);
            }
            $library_logo->move($destinationFolder, $library_logoNewName);
            $validated['library_logo'] = 'upload/library_logo/' . $library_logoNewName;
        }
        if(($request->longitude && $request->latitude) || $request->google_map){
                $validated['is_profile'] =1;         
        }else{
            $validated['is_profile'] =0; 
        }
        
        $featuresJson = (isset($request->features) && $validated['features']) ? json_encode($validated['features']) : null;
        
      
        $branch = $id ? Branch::find($id) : null;
        

        if (!$branch) {
            return redirect()->back()->with('error', 'Branch not found.');
        }
        $branch->update($validated);

        return redirect()->route('branch.list')->with('success', 'Profile updated successfully!');
    }

    public function destroy($id)
    {
        $branch = Branch::findOrFail($id);

        // Ensure there is more than one branch in the same library
        $multipleBranches = Branch::where('library_id', $branch->library_id)->count() > 1;

        // Ensure the current branch is NOT set as the library's current branch
        $notCurrentBranch = !Library::where('id', $branch->library_id)
                                    ->where('current_branch', $id)
                                    ->exists();

        if ($multipleBranches && $notCurrentBranch) {
            $branch->delete();
            return redirect()->route('branch.list')->with('success', 'Branch deleted successfully.');
        }

        return redirect()->route('branch.list')->with('error', 'Cannot delete this branch. It is either the only branch or the current active branch of the library.');
    }

    public function branchConfigurForm($id = null)
    {
        $branch = $id ? Branch::findOrFail($id) : null;
        $states = State::where('is_active', 1)->get();
        $cities = City::where('is_active', 1)->get();
        $features = DB::table('features')->whereNull('deleted_at')->get();
        $branchCount = getCurrentBranch() ? Branch::where('id', getCurrentBranch())->count() : 0;

        return view('register.branch-configure', compact('branch', 'states', 'cities', 'features','branchCount'));
    }

    
    // public function branchConfigure(Request $request)
    // {
    //     /* =========================
    //     BRANCH COUNT VALIDATION
    //     ========================= */
    //     $validation = branchCountValidation();

    //     if ($validation['success']) {
    //         return response()->json([
    //             'status'  => false,
    //             'message' => $validation['message']
    //         ], 400);
    //     }

    //     $planCount =Plan::where('library_id', getLibraryId())->count();

    //     /* =========================
    //     BASE VALIDATION
    //     ========================= */
    //     $rules = [
    //         'name'            => 'required|string|max:255',
    //         'email'           => 'required|email',
    //         'mobile'          => 'required|digits:10',
    //         'logo'            => 'nullable|image|mimes:jpeg,png,jpg,svg,webp|max:2048',
    //         'library_images.*'=> 'nullable|image|mimes:jpeg,png,jpg,svg,webp|max:2048',
    //         'locker_amount'   => 'required',
    //         'token_money'     => 'nullable',
    //         'upi_id'          => 'nullable',
    //         'extend_days'     => 'required',
    //         'hour'            => 'required',
    //         'seats'           => 'required',
    //         'founder_day'     => 'required',
    //         'monthdays'       => 'nullable|integer|in:28,30',
    //         'fixed_billing_date' => 'nullable|integer|min:1|max:31',

    //     ];

    //     // 🔹 Only require plans if no plan exists
    //     if ($planCount === 0) {
    //         $rules['plans']   = 'required|array|min:1';
    //         $rules['plans.*'] = 'string';
    //     } else {
    //         $rules['plans'] = 'nullable|array';
    //     }

    //     $validator = Validator::make($request->all(), $rules);

    //     $plans  = $request->input('plans', []);
    //     $floorses = $request->input('floors', []);

    //     /* =========================
    //     UNIQUE BRANCH NAME
    //     ========================= */
    //     $slug = Str::slug($request->name.'-'.getLibraryId());
        
    //     $existingBranch = Branch::where('slug', $slug)
    //         ->where('library_id', getLibraryId())
    //         ->first();

    //    $branchCount= Branch::where('library_id', getLibraryId())->count();

    //     /* =========================
    //     MONTH PLAN REQUIRED
    //     ========================= */
       
    //     if ($existingBranch || $branchCount == 0){
          
    //         $validator->after(function ($validator) use ($plans) {

    //             // $alreadyHave = Plan::where('library_id', getLibraryId())
    //             //     ->where('plan_id', 1)
    //             //     ->where('type', 'MONTH')
    //             //     ->exists();

    //             $hasMonthPlan = false;
            

    //             foreach ($plans ?? [] as $plan) {
                
    //                 if (strtoupper($plan) === '1 MONTH') {
    //                     $hasMonthPlan = true;
    //                     break;
    //                 }
    //             }
            
    //             if ($hasMonthPlan == false ) {
            
    //                 $validator->errors()->add(
    //                     'plans',
    //                     '1 MONTH plan is required.'
    //                 );
    //             }
                
    //         });
    //     }

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'status' => false,
    //             'errors' => $validator->errors()
    //         ], 422);
    //     }

    //     DB::beginTransaction();

    //     try {

    //         $validated = $validator->validated();
            
    //         $validated['library_id']  = getLibraryId();
    //         $validated['display_name'] = $validated['display_name'] ?? $validated['name'];

           

    //         $hour  = $validated['hour'];
    //         $seats = $validated['seats'];
    //         unset($validated['hour'], $validated['seats']);

    //         /* =========================
    //         FLOOR VALIDATION
    //         ========================= */
    //         $floors = collect($floorses)
    //             ->filter(fn ($floor) =>
    //                 filled($floor['name']) ||
    //                 filled($floor['from']) ||
    //                 filled($floor['to'])
    //             )
    //             ->values()
    //             ->toArray();

    //         $totalFloorSeats = 0;

    //         foreach ($floors as $index => $floor) {
    //              //  If from/to is filled, name is required
    //             if ((filled($floor['from']) || filled($floor['to'])) && empty($floor['name'])) {
    //                 throw new \Exception(
    //                     "Floor name is required when seat range is provided (Row ".($index + 1).")"
    //                 );
    //             }

    //             if (empty($floor['from']) || empty($floor['to'])) {
    //                 throw new \Exception(
    //                     'Seat range is required for each floor.'
    //                 );
    //             }

    //             if ($floor['to'] < $floor['from']) {
    //                 throw new \Exception(
    //                     'Seat To must be greater than or equal to Seat From.'
    //                 );
    //             }

    //             $totalFloorSeats += ($floor['to'] - $floor['from']) + 1;
    //         }

    //         if ($totalFloorSeats > $seats) {
    //             throw new \Exception(
    //                 "Total floor seats ({$totalFloorSeats}) cannot exceed branch seats ({$seats})"
    //             );
    //         }

    //         /* =========================
    //         CREATE BRANCH
    //         ========================= */
    //         $branchData = collect($validated)->except([
    //             'plans',
    //             'monthdays',
    //             'floors',
    //         ])->toArray();

    //         $branch = $existingBranch ?? new Branch();
    //          $branch->fill($branchData);
    //         $branch->library_id = getLibraryId();
            
    //         if ($request->hasFile('logo')) {
    //             $branch->logo = $request->file('logo')
    //                 ->store('uploads/logo', 'public');
    //         }

    //         if ($request->has('features')) {
    //             $branch->features = json_encode($request->features);
    //         }

    //         $branch->google_map = $request->google_map;
    //         $branch->slug = $slug;
    //         $branch->save();

    //         /* =========================
    //         HOURS
    //         ========================= */
    //          Hour::updateOrCreate(
    //             [
    //                 'branch_id'  => $branch->id,
    //                 'library_id' => getLibraryId(),
    //             ],
    //             [
    //                 'hour'  => $hour,
    //                 'seats' => $seats,
    //             ]
    //         );

    //         /* =========================
    //         PLANS
    //         ========================= */
    //        if ($existingBranch || $branchCount == 0){
            
    //                 // DELETE REMOVED PLANS
    //             Plan::where('library_id', getLibraryId())
    //                 ->whereNotIn('name', $plans)
    //                 ->delete();

    //             $baseMonthDays = null;
    //             foreach ($plans as $plan) {
    //                 [$num, $type] = explode(' ', $plan);
    //                 if ((int)$num === 1 && strtoupper($type) === 'MONTH') {
    //                     $baseMonthDays = $request->monthdays ?: null;
    //                     break;
    //                 }
    //             }

    //             foreach ($plans as $plan) {
    //                 [$num, $type] = explode(' ', $plan);

    //                 Plan::updateOrCreate(
    //                     [
    //                         'library_id' => getLibraryId(),
    //                         'name'       => $plan,
    //                     ],
    //                     [
    //                         'plan_id'   => (int)$num,
    //                         'type'      => strtoupper($type),
    //                         'monthdays' => strtoupper($type) === 'MONTH'
    //                             ? $baseMonthDays
    //                             : null,
    //                     ]
    //                 );
    //             }
    //         }

    //         /* =========================
    //         FLOORS
    //         ========================= */
    //         Floor::where('branch_id', $branch->id)->delete();

    //         foreach ($floors as $index => $floor) {
    //             Floor::create([
    //                 'branch_id'   => $branch->id,
    //                 'name'        => $floor['name'],
    //                 'floor_no'    => $index + 1,
    //                 'from_seat'   => (int)$floor['from'],
    //                 'to_seat'     => (int)$floor['to'],
                    
    //             ]);
    //         }

    //         /* =========================
    //         LIBRARY IMAGES
    //         ========================= */
    //         if ($request->hasFile('library_images')) {
    //             foreach ($request->file('library_images') as $image) {
    //                 $image->store('uploads/library_images', 'public');
    //             }
    //         }
    //         Library::where('id',getLibraryId())->update([
    //             'current_branch'=> $branch->id
    //         ]);

    //         DB::commit();

    //         return response()->json([
    //         'status'   => true,
    //         'redirect' => route('library.home'),
    //             'message'  => 'Branch added successfully.'
    //         ]);

    //     } catch (\Exception $e) {

    //         DB::rollBack();

    //         return response()->json([
    //             'status'  => false,
    //             'message' => $e->getMessage()
    //         ], 400);
    //     }
    // }

    public function branchConfigure(Request $request,LibraryConfigurationService $service)
    {
        /* =========================
        BRANCH COUNT VALIDATION
        ========================= */
        $validation = branchCountValidation();

        if ($validation['success']) {
            return response()->json([
                'status'  => false,
                'message' => $validation['message']
            ], 400);
        }

        $planCount =Plan::where('library_id', getLibraryId())->count();

        /* =========================
        BASE VALIDATION
        ========================= */
        $rules = [
            'name'            => 'required|string|max:255',
            'display_name'    => 'nullable|string',
            'email'           => 'required|email',
            'mobile'          => 'required|digits:10',
            'library_logo'            => 'nullable|image|mimes:jpeg,png,jpg,svg,webp|max:2048',
            'library_images.*'=> 'nullable|image|mimes:jpeg,png,jpg,svg,webp|max:2048',
            'locker_amount'   => 'required',
            'token_money'     => 'nullable',
            'upi_id'          => 'nullable',
            'extend_days'     => 'required',
            'hour'            => 'required',
            'seats'           => 'required',
            'founder_day'     => 'required',
            'monthdays'       => 'nullable|integer|in:28,30',
            'fixed_billing_date' => 'nullable|integer|min:1|max:31',

            'floors' => 'nullable|array',
            'floors.*.name' => 'nullable|string',
            'floors.*.from' => 'nullable|integer|min:1',
            'floors.*.to'   => 'nullable|integer|min:1',

        ];


        // 🔹 Only require plans if no plan exists
        if ($planCount === 0) {
            $rules['plans']   = 'required|array|min:1';
            $rules['plans.*'] = 'string';
        } else {
            $rules['plans'] = 'nullable|array';
        }


        $validator = Validator::make($request->all(), $rules);

        $validated = $validator->validated();

        $validated['features'] = $request->features ?? null;
        $validated['google_map'] = $request->google_map ?? null;
       

        /* =========================
        UNIQUE BRANCH NAME
        ========================= */
        $slug = Str::slug($request->name.'-'.getLibraryId());
        
        $existingBranch = Branch::where('slug', $slug)
            ->where('library_id', getLibraryId())
            ->first();

       $branchCount= Branch::where('library_id', getLibraryId())->count();
        $plans  = $request->input('plans', []);
        $floorses = $request->input('floors', []);

        /* =========================
        DUPLICATE BRANCH NAME
        ========================= */
        $validator->after(function ($validator) use ($existingBranch) {
            if ($existingBranch) {
                $validator->errors()->add(
                    'name',
                    'This branch name already exists in this library.'
                );
            }
        });

        /* =========================
        MONTH PLAN REQUIRED
        ========================= */
        if ($branchCount == 0){


            $validator->after(function ($validator) use ($plans) {
                $hasMonthPlan = false;


                foreach ($plans ?? [] as $plan) {

                    if (strtoupper($plan) === '1 MONTH') {
                        $hasMonthPlan = true;
                        break;
                    }
                }

                if ($hasMonthPlan == false ) {

                    $validator->errors()->add(
                        'plans',
                        '1 MONTH plan is required.'
                    );
                }

            });
        }

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        /* ================= CALL SERVICE ================= */

        $response = $service->configure(
            $request,          // pass full request
            $validated,
            getLibraryId(),
            $existingBranch,
            $branchCount
        );

        if ($response['status'] === true) {
           
            return response()->json([
                'status'   => true,
                'redirect' => route('library.home'),
                'message'  => $response['message']
            ]);
        }
       

        return response()->json($response);

        
    }

    
}
