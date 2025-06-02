@extends('layouts.library')
@section('content')

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif
<div class="card mb-4">

    <h4 class="mb-4">Add Request</h4>
    <form action="{{ route('requaet.store') }}" method="POST" enctype="multipart/form-data" class="validateForm">
        @csrf
        <div class="row g-4">


            <div class="form-group">
                <label for="category_name">Request Type <span class="text-danger">*</span></label>
                <select name="request_name" class="form-select  @error('request_name') is-invalid @enderror">
                    <option value="">Select Request</option>
                    <option value="{{ 'changePlan' ?? old('request_name') }}">Change Plan</option>
                    <option value="{{ 'upgradePlan' ?? old('request_name') }}">Upgrade/Downgrade Plan</option>
                    <option value="{{ 'renewPlan' ?? old('request_name') }}">Renew Plan</option>
                    <option value="{{ 'closePlan' ?? old('request_name') }}">Close Plan</option>
                    <option value="{{ 'changePlan' ?? old('request_name') }}">Change/Swap seat</option>

                </select>
                @error('request_name')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>
            <div class="col-lg-12">
                <label for="category_name">Request Description <span class="text-danger">*</span></label>
                <textarea name="description" class="form-control  @error('description') is-invalid @enderror" placeholder="Enter your request description"></textarea>
                @error('description')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>

            <div class="col-lg-3 mt-4">
                <button type="submit" class="btn btn-primary button">{{ 'Add ' }}</button>
            </div>
    </form>
</div>

</div>

<div class="row mb-4">
    <div class="col-lg-12">
        <div class="table-responsive">
            <table class="table text-center" id="datatable">
                <thead>
                    <tr>
                        <th>S.No.</th>
                        <th>Request Name</th>
                        <th>Description</th>
                        <th>Request Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($learner_request as $index => $value)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $value->request_name }}</td>
                        <td>{{ $value->description }}</td>
                        <td>{{$value->request_date}}</td>
                        <td>@if($value->request_status==0)
                            <span class=" text-danger d-inline">Pending</span>
                            @else
                            <span class=" text-success d-inline">Resolved (By Admin)</span>
                            @endif
                        </td>
                        
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        let table = new DataTable('#datatable', {
          
        });
        
    });
</script>
@endsection