@extends('sitelayouts.layout')
@section('content')

<div class="sacnd-data" style="min-height: 500px; display:flex; align-items:center;">
    <div class="container">
        <div class="row justify-content-center align-items-center h-100">
            <div class="col-lg-4">
                <div class="process-step-1">
                    <div class="action-content">
                        <div class="headings text-center">
                            <h4>Welcome to Libraro</h4>
                            <span class="text-message">Please enter your Mobile Number to Proceed..</span>
                        </div>
                        <form method="POST" action="{{ route('renew.find', $branch->uuid) }}">
                            @csrf
                            <div class="row g-4 ">
                                <input type="hidden" value="{{$branch->id}}" name="branch">
                                <div class="col-lg-12">
                                    <input type="text" class="form-control @error('mobile') is-invalid @enderror" placeholder="Enter your mobile Number" name="mobile">
                                    @error('mobile')  
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span> 
                                    @enderror
                                </div>
                                <div class="col-lg-12">
                                    <input type="text" class="form-control @error('learner_no') is-invalid @enderror" placeholder="Enter your Learner Number" name="learner_no">
                                    @error('learner_no')  
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span> 
                                    @enderror
                                </div>
                                <div class="col-lg-12 text-center">
                                    <input type="submit" class="btn btn-primary" value="NEXT">
                                </div>
                            </div>
                        </form>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>
</div>

@endsection