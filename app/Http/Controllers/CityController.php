<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Country;
use App\Models\State;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class CityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        
        $states=State::pluck('id','state_name');
       
       $citys=City::leftJoin('states','states.id','=','cities.state_id')->select('cities.id as city_id','cities.city_name as city_name','states.state_name','cities.is_active','cities.deleted_at')->withTrashed()->get();
        return view('master.city',compact('states','citys'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
      
        $validator = Validator::make($request->all(), [
            'city_name' => 'required|string|max:255',
            
            'state_id' => 'required',
        ],
        [
            'city_name.required' => 'City Field is required',
           
            'state_id.required' => 'State Field is required',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()]);
        }

        $data=$request->all();
      
        $data['is_active']=1;
        try {
            if($data['id']==null){
                City::create($data);
                $message='City Added successfully';
            }else{
                City::findOrFail($data['id'])->update($data);
                $message='City Updated successfully';
            }
            return response()->json(['success' => true, 'message' => $message]);

           
        }catch(Exception $e){
            return response()->json(['error' => true, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(City $city)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request)
    {
        $id=$request->id;
        $city=City::findOrFail($id);
        $state_name=State::where('id',$city->state_id)->first();
       
        return response()->json(['city' => $city,'state'=>$state_name->state_name]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, City $city)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
       
        $city = City::find($request->id);
   
        if ($city) {
            $city->delete();
            return response()->json(['success' => true, 'message' => 'City deleted successfully']);
           
        } else {
            return response()->json(['error' => true, 'message' => 'City not deleted.... ']);
        }
    
       
    }
    public function countryWiseState(Request $request){
       
        if($request->country_id){
            $countryId=$request->country_id;
            $state=State::where('country_id',$countryId)->pluck('state_name','id');
            
            return response()->json($state);
        }
        
       
    }
}
