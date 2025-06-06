<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\City;
use App\Models\Library;
use App\Models\LibraryUser;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use DB;
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
       
    

        return back();
    }

    public function index(){
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
        return view('library.branch-list',compact('branches'));
    }
  public function edit($id){
     
        
        $branch = $id ? Branch::find($id) : null;
       
        $states = State::where('is_active', 1)->get();
        $cities = City::where('is_active', 1)->get();
        $features = DB::table('features')->whereNull('deleted_at')->get();

        return view('library.branch-update', compact('branch','states', 'cities', 'features'));
    }



   public function update(Request $request, $id)
    {
       
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'library_category' => 'required',
            'working_days' => 'required',
            'mobile' => 'required|string|max:10',
            'email' => 'required|email',
            'library_address' => 'required|string',
            'library_zip' => 'required|string|max:6',
            
            'state_id' => 'required|exists:states,id',
            'city_id' => 'required|exists:cities,id',
            'library_logo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:200|dimensions:width=250,height=250',
            'features' => 'nullable|array', 
            'features.*' => 'integer',
            'google_map'=>'nullable',
            'description'=>'nullable',
            'library_images.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);
     
        
        if ($request->hasFile('library_images')) {
            $uploadedFiles = [];
            
            foreach ($request->file('library_images') as $file) {
                $library_imageNewName = "library_img_" . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads'), $library_imageNewName);
                $uploadedFiles[] = 'uploads/' . $library_imageNewName;
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
            $library_logo->move(public_path('uploads'), $library_logoNewName);
            $validated['library_logo'] = 'uploads/' . $library_logoNewName;
        }
       
        $featuresJson = (isset($request->features) && $validated['features']) ? json_encode($validated['features']) : null;
      
        // $library = Library::where('id', getAuthenticatedUser()->id)->first();
        // $libraryCode = $this->generateLibraryCode();
       
        // $update=$library->update($validated);
      
        // if ($update) {
        //     $library->update(['is_profile' => 1]);
        //     if (empty($library->library_no)) {
        //         $libraryCode = $this->generateLibraryCode();
        //         $library->library_no = $libraryCode;
        //         $library->save();
        //         $this->sendSuccessfulEmail($library);
        //     }
        // }
        $branch = $id ? Branch::find($id) : null;
       

        if (!$branch) {
            return redirect()->back()->with('error', 'Branch not found.');
        }
        $branch->update($validated);

        return redirect()->route('branch.list')->with('success', 'Profile updated successfully!');
    }
    
}
