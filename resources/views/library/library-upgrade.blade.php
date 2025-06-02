@extends('layouts.library')

@section('content')
@if(getLibraryData())
<div class="row">
    <div class="col-lg-9">
        <div class="actions">
            <div class="upper-box">
                <div class="d-flex">
                    <h4 class="mb-3">Library Info</h4>
                    <a href="javascript:void(0);" class="go-back"
                        onclick="window.history.back();">Go Back <i
                            class="fa-solid fa-backward pl-2"></i></a>
                </div>
                <div class="row g-4">
                    <div class="col-lg-6">
                        <span>Library Name</span>
                       
                        <h5 class="uppercase"> {{ getLibraryData()->library->library_name }}</h5>
                    </div>
                    <div class="col-lg-6">
                        <span>Library Join Date </span>
                        <h5>{{getLibraryData()->library->join_date}}</h5>
                    </div>
                    <div class="col-lg-6">
                        <span>Library Number</span>
                        <h5>+91-{{getLibraryData()->library->library_mobile}}</h5>
                    </div>
                    <div class="col-lg-6">
                        <span>Library Email Id</span>
                        <h5>{{getLibraryData()->library->email}}</h5>
                    </div>
                </div>
            </div>
          
            <div class="action-box">
                <h4>Library Plan Info :</h4>
                <div class="row g-4">
                    <div class="col-lg-4">
                        <span>Plan</span>
                        <h5>{{getLibraryData()->latest_transaction->month ?? ''}} MONTHS</h5>
                    </div>
                    <div class="col-lg-4">
                        <span>Plan Type</span>
                        <h5>{{getLibraryData()->plan->name}}</h5>
                    </div>
                    <div class="col-lg-4">
                        <span>Plan Price</span>
                        <h5>{{getLibraryData()->latest_transaction->amount ?? ''}}</h5>
                    </div>
                    <div class="col-lg-4">
                        <span>Seat Booked On</span>
                        <h5>{{getLibraryData()->latest_transaction->transaction_date ?? ''}}</h5>
                    </div>
                    <div class="col-lg-4">
                        <span>Plan Starts On</span>
                        <h5>{{getLibraryData()->latest_transaction->start_date ?? ''}}</h5>
                    </div>
                    <div class="col-lg-4">
                        <span>Plan Ends On</span>
                        <h5>{{getLibraryData()->latest_transaction->end_date ?? ''}}</h5>
                    </div>
                 
                    <div class="col-lg-4">
                        <span>Plan Expired In</span>
                        <h5><span class="text-success">Plan Expires in 24 Days</span></h5>
                    </div>
                    <div class="col-lg-4">
                        <span>Current Plan Status</span>
                        <h5>
                            @if(getLibraryData()->library->status==1)
                            <span class="text-success">Active</span>
                            @else
                            <span class="text-danger">InActive</span>
                            @endif
                            

                        </h5>
                    </div>
                </div>
              
                <h4 class="mt-4"> Library Payment Info :</h4>
                @foreach(getLibraryData()->all_transactions as  $value)
                    <div class="row g-4">
                        <div class="col-lg-4">
                            <span>Payment Mode</span>
                            <h5>{{$value->payment_mode}}</h5>
                        </div>
                        <div class="col-lg-4">
                            <span>Payment Status</span>
                            <h5>
                                @if($value->is_paid==1)
                                <span
                                class="text-success">Paid</span>
                                @else
                                <span class="text-danger">Pending</span>  
                                @endif
                                 
                            </h5>
                        </div>
                        <div class="col-lg-4">
                            <span>Transaction Id</span>
                              <h5>{{$value->transaction_id}}</h5>
                        </div>
                        <div class="col-lg-4">
                            <span>Payment Date</span>
                              <h5>{{$value->transaction_date}}</h5>
                        </div>
                    </div>
                @endforeach
                
                
               
            </div>
            <div class="action-box">
            <h4>Actionable :</h4>
                <div class="col-lg-4">
                    <label for=""> Plan <span>*</span></label>
                    <select id="subscription" class="form-control @error('subscription') is-invalid @enderror" name="subscription" >
                        <option value="">Select Plan</option>
                        @foreach($plans as $key => $value)
                        <option value="{{ $value->id }}" {{ old('subscription', getLibraryData()->plan->id) == $value->id ? 'selected' : '' }}>{{ $value->name }}</option>
                        @endforeach
                    </select>
                    @error('subscription')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>
                <div class="col-lg-4">
                    <label for=""> Plan <span>*</span></label>
                    <select id="plan_id" class="form-control @error('plan_id') is-invalid @enderror" name="plan_id" >
                        <option value="">Select Plan</option>
                        <option value="monthly_fees">Monthly</option>
                        <option value="yearly_fees">Yearly</option>
                        
                    </select>
                </div>
            </div>
        </div>
    </div>
   
</div>
@endif

@endsection