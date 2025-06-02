@extends('layouts.admin')

@section('content')

<!-- Add Library Plan -->
<div class="card">
    @if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
    @endif

    <!-- Create Subscription -->
    <h4 class="mb-3">Create New Subscription</h4>
    <form action="{{ $subscription ? route('subscriptions.update', $subscription->id) : route('subscriptions.store') }}" 
    class="validateForm" 
    method="POST"
>
    @csrf
    @if($subscription)
        @method('PUT') {{-- For edit/update --}}
    @endif

    <div class="row g-4">
        <div class="col-lg-4">
            <label for="subscription">Subscription Name <span>*</span></label>
            <input 
                type="text" 
                class="form-control char-only" 
                name="name" 
                value="{{ old('name', $subscription->name ?? '') }}"
            >
            @error('name')
                <small class="text-danger">{{ $message }}</small>
            @enderror
        </div>
        <div class="col-lg-4">
            <label for="subscription">Monthly Plan Price <span>*</span></label>
            <input 
                type="text" 
                class="form-control digit-only" 
                name="monthly_fees" 
                value="{{ old('monthly_fees', $subscription->monthly_fees ?? '') }}"
            >
            @error('monthly_fees')
                <small class="text-danger">{{ $message }}</small>
            @enderror
        </div>
        <div class="col-lg-4">
            <label for="subscription">Yearly Plan Price</label>
            <input type="text" class="form-control digit-only"  name="yearly_fees" value="{{ old('yearly_fees', $subscription->yearly_fees ?? '') }}">
            @error('yearly_fees')
                <small class="text-danger">{{ $message }}</small>
            @enderror
        </div>
        {{-- <div class="col-lg-4">
            <label for="discount">Discount</label>
            <input type="text" class="form-control digit-only"  name="discount" value="{{ old('discount', $subscription->discount ?? '') }}">
            @error('discount')
                <small class="text-danger">{{ $message }}</small>
            @enderror
        </div>
        <div class="col-lg-4">
            <label for="expiry_date">Expiry Date</label>
            <input type="date" class="form-control"  name="expiry_date" value="{{ old('expiry_date', $subscription->expiry_date ?? '') }}">
            @error('expiry_date')
                <small class="text-danger">{{ $message }}</small>
            @enderror
        </div>
        <div class="col-lg-4">
            <label for="expiry_message">Expiry Message</label>
            <input type="text" class="form-control "  name="expiry_message" value="{{ old('expiry_message', $subscription->expiry_message ?? '') }}">
            @error('expiry_message')
                <small class="text-danger">{{ $message }}</small>
            @enderror
        </div> --}}
        <div class="col-lg-3">
            <button type="submit" class="btn btn-primary button">
                {{ $subscription ? 'Update Subscription' : 'Create Subscription' }}
            </button>
        </div>
    </div>
    
</form>

</div>

<h4 class="my-4">All Subscriptions List</h4>

<div class="row">
    <div class="col-lg-12">
        <div class="table-responsive">
            <table class="table text-center datatable border-bottom">
                <thead>
                    <tr>
                        <th>S.No.</th>
                        <th>Subscription Name</th>
                        <th>Monthly Price</th>
                        <th>Yearly Price</th>
                        <th>No. of Permission</th>
                        <th>Subscription Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($subscriptions as $key => $value)
                    @php
                      
                        $permisssionsCount = $value->permissions->count();
                        
                    @endphp
                 
                    <tr>
                        <td>1</td>
                        <td>{{$value->name}}</td>
                        <td>{{$value->monthly_fees}}</td>
                        <td>{{$value->yearly_fees}}</td>
                        <td>{{$permisssionsCount}} [ <a href="{{ route('planwise.permissions', $value->id) }}">View</a> | <a href="{{ route('subscriptions.permissions') }}">Edit</a> ]</td>
                        @if($value->deleted_at==null)
                        <td>Active</td>
                        @else
                        <td>DeActive</td>
                        @endif
                        
                        <td>
                            <ul class="actionalbls">
                                <li><a href="#" class="active-deactive" data-id="{{ $value->id }}" data-table="Subscription" title="Delete"><i class="fa fa-check"></i></a></li>
                                <li>
                                    <a href="{{ route('subscriptions.edit',$value->id) }}"><i class="fa fa-edit"></i></a>
                                </li>
                            </ul>
                        </td>
                        
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@include('master.script')
@endsection