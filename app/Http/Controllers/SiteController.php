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
use App\Models\Setting;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use DB;
use Illuminate\Support\Facades\Mail;
use Auth;
class SiteController extends Controller
{
    public function aboutUs()
    {
        return view('site.about-us');
    }
    public function blog(Request $request)
    {
        $query = Blog::published();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('page_title', 'like', "%{$search}%")
                  ->orWhere('page_content', 'like', "%{$search}%")
                  ->orWhere('excerpt', 'like', "%{$search}%")
                  ->orWhere('tags', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $catId = (int) $request->category;
            $query->where(function($q) use ($catId) {
                $q->whereJsonContains('categories_id', $catId)
                  ->orWhereJsonContains('categories_id', (string) $catId);
            });
        }

        if ($request->filled('tag')) {
            $tag = $request->tag;
            $query->where('tags', 'like', "%{$tag}%");
        }

        $featuredBlog = Blog::published()->featured()->latest('published_at')->first();
        $data = $query->latest('published_at')->paginate(9)->withQueryString();
        $categories = Category::all();
        $popularBlogs = Blog::published()->orderBy('views_count', 'desc')->take(5)->get();

        return view('site.blog', compact('data', 'featuredBlog', 'categories', 'popularBlogs'));
    }
    public function contactUs()
    {
        return view('site.contact-us');
    }
    public function privacyPolicy()
    {
        return view('site.privacy-policy');
    }
    public function termAndCondition()
    {
        return view('site.term-and-condition');
    }
    public function refundPolicy()
    {
        return view('site.refund-policy');
    }
    public function home()
    {
        $happy_customers = Feedback::withoutGlobalScopes()->leftJoin('libraries', 'feedback.library_id', '=', 'libraries.id')->leftJoin('branches', 'libraries.id', '=', 'branches.library_id')->leftJoin('cities', 'cities.id', 'branches.city_id')->where('feedback.rating', '>', 4)->select('libraries.library_owner', 'libraries.library_name', 'libraries.created_at', 'feedback.*', 'cities.city_name')->get();
        $subscriptions = Subscription::with('permissions')->get();
        $premiumSub = Subscription::orderBy('id', 'DESC')->first();
        $features=DB::table('subscription_plan_features')->where('feature_status',1)->get();
        return view('site.home', compact('subscriptions', 'premiumSub', 'happy_customers','features'));
    }
    public function searchLibrary()
    {
        $cities = City::whereHas('branches')->pluck('city_name', 'id');
        $topLibraries = Library::take(5)->get();
        $library_count = Library::count();
        $learner_counter = Learner::count();
        $city_count = City::count();
        $feedback_count = Feedback::count();
        $happy_customers = Feedback::withoutGlobalScopes()->leftJoin('libraries', 'feedback.library_id', '=', 'libraries.id')->leftJoin('branches', 'libraries.id', '=', 'branches.library_id')->leftJoin('cities', 'cities.id', 'branches.city_id')->where('feedback.rating', '>', 4)->select('libraries.library_owner', 'libraries.library_name', 'libraries.created_at', 'feedback.*', 'cities.city_name')->where('feedback.library_id', getLibraryId())->get();

        return view('site.library-directory', compact('cities', 'topLibraries', 'learner_counter', 'library_count', 'city_count', 'happy_customers', 'feedback_count'));
    }
    public function listPage()
    {
        $pages = Page::all();
        return view('administrator.indexpage', compact('pages'));
    }

    public function createpage()
    {
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
        $data = $request->validate([
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
            $page->update($data);
            $message = 'Page updated successfully!';
        } else {
            // If $id does not exist, create a new page
            Page::create($data);
            $message = 'Page Crete successfully!';
        }

        // Redirect or return with success message
        return redirect()->route('page')->with('success', $message);
    }

    public function createBlog()
    {
        $categories = Category::all();
        return view('administrator.addBlog', compact('categories'));
    }

    public function editBlog($id)
    {
        $categories = Category::all();
        $data = Blog::findOrFail($id);

        return view('administrator.addBlog', compact('data', 'categories'));
    }

    public function blogStore(Request $request, $id = null)
    {
        $data = $request->validate([
            'page_title'       => 'required|string|max:255',
            'page_slug'        => 'required|string|max:255|unique:blogs,page_slug,' . $id,
            'page_content'     => 'required|string',
            'excerpt'          => 'nullable|string|max:1000',
            'meta_title'       => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keyword'     => 'nullable|string|max:500',
            'meta_og'          => 'nullable|string',
            'canonical_url'    => 'nullable|url|max:255',
            'meta_robots'      => 'nullable|string|max:100',
            'focus_keyword'    => 'nullable|string|max:255',
            'schema_type'      => 'nullable|string|max:100',
            'author_name'      => 'nullable|string|max:255',
            'status'           => 'required|in:published,draft,scheduled',
            'published_at'     => 'nullable|date',
            'is_featured'      => 'nullable|boolean',
            'image_alt'        => 'nullable|string|max:255',
            'header_image'     => $id ? 'nullable|image|mimes:jpg,jpeg,png,webp|max:3072' : 'required|image|mimes:jpg,jpeg,png,webp|max:3072',
            'categories_id'    => 'nullable|array',
            'categories_id.*'  => 'nullable|integer|exists:categories,id',
        ]);

        $data['is_featured'] = $request->has('is_featured') ? 1 : 0;
        $data['author_name'] = $request->author_name ?: 'Libraro Team';
        $data['meta_robots'] = $request->meta_robots ?: 'index, follow';
        $data['schema_type'] = $request->schema_type ?: 'BlogPosting';

        // Auto-calculate reading time
        $data['reading_time'] = Blog::calculateReadingTime($request->page_content);

        // Handle publication timestamp
        if ($data['status'] === 'published' && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        // Handle categories array from select or tagify
        $categoryIds = $request->categories_id ?? [];
        if ($request->categories) {
            $categoriesDecoded = json_decode($request->categories, true);
            if (is_array($categoriesDecoded)) {
                foreach ($categoriesDecoded as $catItem) {
                    if (isset($catItem['value'])) {
                        $catModel = Category::firstOrCreate(['name' => trim($catItem['value'])]);
                        if (!in_array($catModel->id, $categoryIds)) {
                            $categoryIds[] = $catModel->id;
                        }
                    }
                }
            }
        }
        $data['categories_id'] = json_encode(array_map('intval', $categoryIds));

        // Handle tags array
        $tags = [];
        if ($request->tags) {
            $decodedTags = json_decode($request->tags, true);
            if (is_array($decodedTags)) {
                foreach ($decodedTags as $tagItem) {
                    if (isset($tagItem['value'])) {
                        $tags[] = trim($tagItem['value']);
                    }
                }
            } else if (is_string($request->tags)) {
                $tags = array_map('trim', explode(',', $request->tags));
            }
        }
        $data['tags'] = json_encode($tags);

        // Handle header image upload
        if ($request->hasFile('header_image')) {
            $header_image = $request->file('header_image');
            $imageName = "header_image_" . time() . '.' . $header_image->getClientOriginalExtension();
            $header_image->move(public_path('uploads'), $imageName);
            $data['header_image'] = 'uploads/' . $imageName;
        }

        // Save or update
        $blog = $id ? Blog::findOrFail($id) : new Blog();
        $blog->fill($data);
        $blog->save();

        $message = $id ? 'Blog updated successfully with SEO settings!' : 'Blog created successfully with SEO settings!';
        return redirect()->route('blogs')->with('success', $message);
    }

    public function listBlog()
    {
        $blogs = Blog::latest()->get();
        return view('administrator.indexblog', compact('blogs'));
    }

    public function demoRequestStore(Request $request)
    {
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
        $validator = Validator::make($request->all(), [
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

    public function demoRequest()
    {
        $data = DemoRequest::get();
        return view('administrator.demoRequest', compact('data'));
    }

    public function inqueryShow()
    {
        $data = Inquiry::get();
        return view('administrator.inquery', compact('data'));
    }

    public function storeSelectedPlan(Request $request)
    {
        session([
            'selected_plan_id' => $request->plan_id,
            'selected_plan_mode' => $request->plan_mode
        ]);

        return response()->json(['success' => true]);
    }

    public function blogDetail($slug)
    {
        $data = Blog::published()->where('page_slug', $slug)->first();
        if (!$data) {
            // Allow draft preview if admin is logged in
            if (Auth::guard('web')->check()) {
                $data = Blog::where('page_slug', $slug)->firstOrFail();
            } else {
                abort(404);
            }
        }

        // Increment views count safely
        $data->increment('views_count');

        $tagsArray = $data->tags_array;
        $categories = $data->categories_models;

        $categoryIds = is_array($data->categories_id) ? $data->categories_id : (json_decode($data->categories_id, true) ?? []);
        $relatedBlogs = Blog::published()
            ->where('id', '!=', $data->id)
            ->where(function($q) use ($categoryIds) {
                foreach ($categoryIds as $catId) {
                    $q->orWhereJsonContains('categories_id', (int)$catId)
                      ->orWhereJsonContains('categories_id', (string)$catId);
                }
            })
            ->take(8)
            ->get();

        if ($relatedBlogs->isEmpty()) {
            $relatedBlogs = Blog::published()->where('id', '!=', $data->id)->latest('published_at')->take(8)->get();
        }

        $popularBlogs = Blog::published()->where('id', '!=', $data->id)->orderBy('views_count', 'desc')->take(5)->get();
        $allCategories = Category::all();

        $jsonLdSchema = $data->json_ld_schema;

        return view('site.blog-details', compact('data', 'categories', 'tagsArray', 'relatedBlogs', 'popularBlogs', 'allCategories', 'jsonLdSchema'));
    }

    public function blogSitemap()
    {
        $blogs = Blog::published()->latest('published_at')->get();
        
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        
        $xml .= '<url>';
        $xml .= '<loc>' . route('blog') . '</loc>';
        $xml .= '<changefreq>daily</changefreq>';
        $xml .= '<priority>0.8</priority>';
        $xml .= '</url>';

        foreach ($blogs as $blog) {
            $xml .= '<url>';
            $xml .= '<loc>' . route('blog-detail', ['slug' => $blog->page_slug]) . '</loc>';
            $xml .= '<lastmod>' . ($blog->updated_at ? $blog->updated_at->toAtomString() : now()->toAtomString()) . '</lastmod>';
            $xml .= '<changefreq>weekly</changefreq>';
            $xml .= '<priority>0.7</priority>';
            $xml .= '</url>';
        }

        $xml .= '</urlset>';

        return response($xml, 200)->header('Content-Type', 'text/xml');
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

    public function libraryDetail($slug)
    {
        
        $features = DB::table('features')->whereNull('deleted_at')->get();
        $library = Branch::where('slug', $slug)->with('state', 'city', 'library.subscription', 'library')->first();

        if (empty($library)) {
            return view('errors.404');
        } else {
           
            $our_package = PlanPrice::withoutGlobalScopes()->leftJoin('plan_types', 'plan_prices.plan_type_id', '=', 'plan_types.id')
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
                ->where('plan_prices.branch_id', $library->id) 
                ->where('plans.plan_id', 1)
                ->get();
               

            $total_seat = Hour::withoutGlobalScopes()->where('branch_id', $library->id)->value('seats') ?? 0;

            $operating = PlanType::withoutGlobalScopes()->where('branch_id', $library->id)->where('day_type_id', 1)->select('start_time', 'end_time')->first();

            $learnerFeedback = LearnerFeedback::where('library_id', $library->library_id)->with(['learner'])->get();
            $libraryplantype = PlanType::withoutGlobalScopes()->where('branch_id', $library->id)->pluck('name', 'id');
        }
      

        return view('site.library-details', compact('library', 'features', 'our_package', 'total_seat', 'operating', 'learnerFeedback', 'libraryplantype'));
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

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'mobile' => 'required|string|max:15',
            'enquiry' => 'required|string|max:1000',
            'shift_time' => 'nullable',
            'branch_id' => 'required',

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

    public function videoIndex()
    {
        $videos = Setting::latest()->get();
        return view('administrator.video-upload', compact('videos'));
    }

    public function videoStore(Request $request)
    {
        
        $data = $request->validate([
            'video_titel' => 'required|string|max:255',
            // 'youtube_link' => 'nullable|url',
             'youtube_link' => 'nullable',
            'video' => 'nullable|mimes:mp4,mov,avi,wmv|max:51200', // 50MB max
        ]);

        if ($request->hasFile('video')) {
            $videio = $request->file('video');
            $videioName = "library_video" . time() . '.' . $videio->getClientOriginalExtension();
            $videio->move(public_path('uploade'), $videioName);
            $data['video'] = $videioName;

        }

        Setting::create($data);

        return redirect()->back()->with('success', 'Video uploaded!');
    }

    public function libraryManagmentLandingPage(){
        $happy_customers = Feedback::withoutGlobalScopes()->leftJoin('libraries', 'feedback.library_id', '=', 'libraries.id')->leftJoin('branches', 'libraries.id', '=', 'branches.library_id')->leftJoin('cities', 'cities.id', 'branches.city_id')->where('feedback.rating', '>', 4)->select('libraries.library_owner', 'libraries.library_name', 'libraries.created_at', 'feedback.*', 'cities.city_name')->get();
        $subscriptions = Subscription::with('permissions')->get();
        $premiumSub = Subscription::orderBy('id', 'DESC')->first();
        return view('site.library-managment-portal',compact('premiumSub','subscriptions','happy_customers'));
    }

    public function leadstore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'library_name' => 'required|string|max:255',
            'contact_number' => 'required|string|max:15',
            'email' => 'required|email|max:255',
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
        DB::table('lp_lead')->insert($data);
         \Log::info('landingPageSuccessMail');
        $this->landingPageSuccessMail($data);
       
        return response()->json([
            'status' => 'success',
            'message' => 'Inquiry submitted successfully!'
        ]);
    }

     public function landingPageSuccessMail($data){
          Mail::send('email.lending-page-response-mail', $data, function($message) use ($data) {
            $message->to($data['email'], $data['library_name'])->subject('Welcome to Libraro – Thank You for Connecting With Us');
        });
    }
}
