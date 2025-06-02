<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\Branch;
use App\Models\Category;
use App\Models\City;
use App\Models\DemoRequest;
use App\Models\Feedback;
use App\Models\Hour;
use App\Models\Inquiry;
use App\Models\Learner;
use App\Models\LearnerFeedback;
use App\Models\Library;
use App\Models\LibraryEnquiry;
use App\Models\Page;
use App\Models\PlanPrice;
use App\Models\PlanType;
use App\Models\Seat;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use DB;

class SiteController extends Controller
{
    public function aboutUs(){
        return view('site.about-us');
    }
    public function blog(){
        $data=Blog::get();
        return view('site.blog',compact('data'));
    }
    public function contactUs(){
        return view('site.contact-us');
    }
    public function privacyPolicy(){
        return view('site.privacy-policy');
    }
    public function termAndCondition(){
        return view('site.term-and-condition');
    }
    public function refundPolicy(){
        return view('site.refund-policy');
    }
    public function home(){
        $happy_customers=Feedback::withoutGlobalScopes()->leftJoin('libraries','feedback.library_id','=','libraries.id')->leftJoin('branches','libraries.id','=','branches.library_id')->leftJoin('cities','cities.id','branches.city_id')->where('feedback.rating','>',4)->select('libraries.library_owner','libraries.library_name','libraries.created_at','feedback.*','cities.city_name')->where('feedback.library_id', getLibraryId())->get();

      
        $subscriptions = Subscription::with('permissions')->get();
        $premiumSub=Subscription::orderBy('id','DESC')->first();
        return view('site.home',compact('subscriptions','premiumSub','happy_customers'));
    }
    public function searchLibrary(){
        $cities = City::pluck('city_name', 'id');
        $topLibraries = Library::take(5)->get();
        $library_count=Library::count();
        $learner_count=Learner::count();
        $city_count=City::count();
        $feedback_count=Feedback::count();
     $happy_customers=Feedback::withoutGlobalScopes()->leftJoin('libraries','feedback.library_id','=','libraries.id')->leftJoin('branches','libraries.id','=','branches.library_id')->leftJoin('cities','cities.id','branches.city_id')->where('feedback.rating','>',4)->select('libraries.library_owner','libraries.library_name','libraries.created_at','feedback.*','cities.city_name')->where('feedback.library_id', getLibraryId())->get();
       
        return view('site.library-directory' ,compact('cities','topLibraries','learner_count','library_count','city_count','happy_customers','feedback_count'));
    }
    public function listPage(){
        $pages = Page::all();
        return view('administrator.indexpage',compact('pages'));
    }

    public function createpage(){
        return view('administrator.createpage');
    }
    public function editPage($id)
    {
       
        $page = Page::findOrFail($id);

        return view('administrator.createpage', compact('page'));
    }
    public function pageStore(Request $request, $id = null)
    {
        // Validation
        $data =$request->validate([
            'page_title' => 'required|string|max:255',
            'page_slug' => 'required|string|max:255|unique:pages,page_slug,' . $id,
            'page_content' => 'required|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keyword' => 'nullable|string',
            'meta_og' => 'nullable|string',
            'route' => 'nullable|string',
            'page_schema' => 'nullable',
        ]);

        // If $id exists, update the existing page
        if ($id) {
            $page = Page::findOrFail($id); // Find the page by ID, or fail if not found
            $page->update( $data);
            $message = 'Page updated successfully!';
        } else {
            // If $id does not exist, create a new page
            Page::create($data );
            $message = 'Page Crete successfully!';
        }

        // Redirect or return with success message
        return redirect()->route('page')->with('success',$message);
    }

    public function createBlog(){
        $categories=Category::get();
        return view('administrator.addBlog',compact('categories'));
    }

    public function editBlog($id)
    {
       
        $categories=Category::get();
        $data = Blog::findOrFail($id);
      
        return view('administrator.addBlog', compact('data','categories'));
    }
    public function blogStore(Request $request, $id = null)
    {
       
        $data = $request->validate([
            'page_title' => 'required|string|max:255',
            'page_slug' => 'required|string|max:255|unique:blogs,page_slug,' . $id,
            'page_content' => 'required|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keyword' => 'nullable|string',
            'meta_og' => 'nullable|string',
            'header_image' => $id ? 'nullable|image|mimes:jpg,jpeg,png|max:2048' : 'required|image|mimes:jpg,jpeg,png|max:2048',
    
            'categories_id' => 'nullable|array', // For the multiple-select dropdown
            'categories_id.*' => 'nullable|integer|exists:categories,id',
        ]);
    
        // Handle categories
        $categoryIds = [];
        if ($request->categories) {
            $categories = json_decode($request->categories, true);
    
            foreach ($categories as $categoryName) {
                if (isset($categoryName['value'])) {
                    $category = Category::firstOrCreate(['name' => trim($categoryName['value'])]);
                    $categoryIds[] = $category->id; // Collect the category ID
                }
            }
        }
    
        if (!empty($categoryIds)) {
            $data['categories_id'] = json_encode($categoryIds); // Store all category IDs as a JSON array
        }
    
        // Handle tags
        $tags = [];
        if ($request->tags) {
            $decodedTags = json_decode($request->tags, true);
    
            foreach ($decodedTags as $tag) {
                if (isset($tag['value'])) {
                    $tags[] = $tag['value']; // Add only the tag value to the array
                }
            }
        }
    
        if (!empty($tags)) {
            $data['tags'] = json_encode($tags); // Save tags as a JSON array like ["tag one", "tag two"]
        }
    

        if ($request->hasFile('header_image')) {
            $header_image = $request->file('header_image');
            $library_logoNewName = "header_image" . time() . '.' . $header_image->getClientOriginalExtension();
            $header_image->move(public_path('uploads'), $library_logoNewName);
            $data['header_image'] = 'uploads/' . $library_logoNewName;
        }
    
        // Save or update the blog
        $blog = $id ? Blog::findOrFail($id) : new Blog();
        $blog->fill($data);
        $blog->save();
    
        $message = $id ? 'Blog updated successfully!' : 'Blog created successfully!';
        return redirect()->route('blogs')->with('success', $message);
    }

    public function listBlog(){
        $blogs = Blog::all();
        return view('administrator.indexblog',compact('blogs'));
    }

    public function demoRequestStore(Request $request){
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'mobile_number' => 'required|digits:10',
            'email' => 'required|email',
            'preferred_date' => 'required|date',
            'preferred_time' => 'nullable|string',
            'terms' => 'required'
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DemoRequest::create([
            'full_name' => $request->full_name,
            'mobile_number' => $request->mobile_number,
            'email' => $request->email,
            'preferred_date' => $request->preferred_date,
            'preferred_time' => $request->preferred_time
        ]);
        return response()->json([
            'status' => 'success',
            'message' => 'Request stored successfully!'
        ]);
      
    }
    
    public function Inquerystore(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'full_name' => 'required|string|max:255',
            'mobile_number' => 'required|string|max:15',
            'email' => 'required|email|max:255',
            'message' => 'required|string|max:1000',
              'terms' => 'required'
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }
       
        $data = $validator->validated();
        unset($data['terms']);

        Inquiry::create($data);
        return response()->json([
            'status' => 'success',
            'message' => 'Inquiry submitted successfully!'
        ]);
        
    }

    public function demoRequest(){
        $data=DemoRequest::get();
        return view('administrator.demoRequest',compact('data'));
    }

    public function inqueryShow(){
        $data=Inquiry::get();
        return view('administrator.inquery',compact('data'));
    }

    public function storeSelectedPlan(Request $request)
    {
        session([
            'selected_plan_id' => $request->plan_id,
            'selected_plan_mode' => $request->plan_mode
        ]);

        return response()->json(['success' => true]);
    }

    public function blogDetail($slug){
        
        $data=Blog::where('page_slug',$slug)->first();
        return view('site.blog-details',compact('data'));
    }
    // public function getLibrariesLocations()
    // {
        
    //     $libraries = Library::join('branches','libraries.id','=','branches.library_id')->whereNotNull('latitude')
    //                         ->whereNotNull('longitude')
    //                         ->select('branches.name as library_name', 'latitude', 'longitude', 'library_address')
    //                         ->get();

    //     return response()->json($libraries);
    // }

    public function getLibrariesLocations()
    {
        $libraries = Branch::with('library')
            ->whereHas('library', function ($q) {
                $q->where('is_paid', 1)->where('is_profile', 1);
            })
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->select('library_address', 'name as library_name', 'latitude', 'longitude')
            ->get();

        return response()->json($libraries);
    }

    public function libraryDetail($slug){
        $features=DB::table('features')->whereNull('deleted_at')->get();
        $library=Branch::where('slug',$slug)->with('state', 'city','library.subscription','library')->first();
      
        if(empty($library)){
           return view('errors.404');
        }else{

            $our_package = PlanPrice::leftJoin('plan_types', 'plan_prices.plan_type_id', '=', 'plan_types.id')
            ->leftJoin('plans', 'plan_prices.plan_id', '=', 'plans.id')
            ->select(
                'plans.name as plan_name',
                'plan_types.name as plan_type_name',
                'plan_types.start_time',
                'plan_types.end_time',
                'plan_types.slot_hours',
                'plan_prices.price',
                'plans.plan_id'
            )
            ->where('plan_prices.library_id', $library->library_id) // Specify table name for library_id
            ->where('plans.plan_id', 1)
            ->get();

        
            $total_seat = Hour::withoutGlobalScopes()->where('branch_id', $library->id)->value('seats') ?? 0;

            $operating=PlanType::where('library_id',$library->library_id)->where('day_type_id',1)->select('start_time','end_time')->first();
           
            $learnerFeedback=LearnerFeedback::where('library_id',$library->library_id)->with(['learner'])->get();
            $libraryplantype=PlanType::where('library_id',$library->library_id)->pluck('name','id');
        }   

        return view('site.library-details',compact('library','features','our_package','total_seat','operating','learnerFeedback','libraryplantype'));
    }

    public function reviewstore(Request $request)
    {
       
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'rating' => 'required|integer|min:1|max:5',
            'comments' => 'required|string',
            'library_id' => 'required',
        ]);
       
        LearnerFeedback::create($validatedData);
        return response()->json([
            'status' => 'success',
            'message' => 'Review submitted successfully!'
        ]);
        
    }

    public function libraryInquerystore(Request $request)
    {
       
        $validator = Validator::make($request->all(),[
            'name' => 'required|string|max:255',
            'mobile' => 'required|string|max:15',
            'enquiry' => 'required|string|max:1000',
            'shift_time' => 'nullable',
            'library_id' => 'required',
          
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }
       
        $data = $validator->validated();
        LibraryEnquiry::create($data);
        return response()->json([
            'status' => 'success',
            'message' => 'Inquiry submitted successfully!'
        ]);
        
    }


    
}
