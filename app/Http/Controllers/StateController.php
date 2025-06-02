<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\State;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
         
        $states=State::select('states.*','states.id as state_id')->withTrashed()->get();
       
        return view('master.state',compact('states'));
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
            'state_name' => 'required|string|max:255',
            
        ],
       );
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()]);
        }
        $data=$request->all();
       
        $data['is_active']=1;
        try {
            if($data['id']==null){
                State::create($data);
                $message='State Added successfully';
            }else{
                State::findOrFail($data['id'])->update($data);
                $message='State Updated successfully';
            }
            
            return response()->json(['success' => true, 'message' => $message]);
        }catch(Exception $e){
            return response()->json(['error' => true, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(State $state)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request)
    {
       
        $id=$request->id;
        $state=State::findOrFail($id);
        return response()->json(['state' => $state]);
        
       
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, State $state)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    // public function destroy(State $state)
    // {
    //     //
    // }

    public function destroy($id)
    {
        State::find($id)->delete();
  
        return redirect()->back();
    }

    
}
