@extends('layouts.admin')
@section('content')
@php
     use Carbon\Carbon;
@endphp
<!-- Breadcrumb -->

<div class="row">
    <div class="col-lg-12">
        <div class="filter-box bg-white">
            <h4 class="mb-3">Filter Library By</h4>
            <form action="{{ route('library') }}" method="GET">
                <div class="row">
                    <div class="col-lg-3">
                        <label for="">Filter By Plan</label>
                        <select name="plan_id" id="plan_id" class="form-select">
                            <option value="">Choose Plan</option>
                            @foreach($plans as $plan)
                                <option value="{{ $plan->id }}" {{ old('plan_id', request('plan_id')) == $plan->id ? 'selected' : '' }}>
                                    {{ $plan->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-3">
                        <label for="">Filter By Payment Status</label>
                        <select name="is_paid" id="is_paid" class="form-select">
                            <option value="">Choose Payment Status</option>
                            <option value="1" {{ old('is_paid', request('is_paid')) == '1' ? 'selected' : '' }}>Paid</option>
                            <option value="0" {{ old('is_paid', request('is_paid')) == '0' ? 'selected' : '' }}>Unpaid</option>
                        </select>
                    </div>
                    <div class="col-lg-3">
                        <label for="">Filter By Active / Expired</label>
                        <select name="status" id="status" class="form-select">
                            <option value="">Choose Status</option>
                            <option value="active" {{ old('status', request('status')) == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="expired" {{ old('status', request('status')) == 'expired' ? 'selected' : '' }}>Expired</option>
                        </select>
                    </div>
                    <div class="col-lg-3">
                        <label for="">Search By Name, Mobile &amp; Email</label>
                        <input type="text" class="form-control" name="search" placeholder="Enter Name, Mobile or Email" value="{{ old('search', request('search')) }}">
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-lg-2">
                        <button class="btn btn-primary button"><i class="fa fa-search"></i> Search Records</button>
                    </div>
                </div>
            </form>
            
        </div>
        
        
        <div class="heading-list">
            <h4 class="">Library List </h4>
            <a href="{{route('library.create')}}" class="btn btn-primary button w-15"><i class="fa-solid fa-plus"></i> Add Library</a>
        </div>
        <div class="table-responsive mt-4">
            <table class="table text-center" id="datatable">
                <thead>
                    <tr>
                        <th>S.No.</th>
                        <th>Library Name</th>
                        <th>Contact Info</th>
                        <th>Plan</th>
                        <th>Starts On</th>
                        <th>Expired On</th>
                        <th style="width:30%">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($libraries as $key => $value)
                    @php
                    
                        $today = Carbon::today();
                         $libraryplan=App\Models\Subscription::where('id',$value->library_type)->value('name');
                         $libraryplanData=DB::table('library_transactions')->where('id',$value->latest_transaction_id)->first();
                        
                         $endDate = ($libraryplanData && $libraryplanData->end_date != null) ? Carbon::parse($libraryplanData->end_date) : null;
                         $diffInDays = $endDate ? $today->diffInDays($endDate, false) : 0;
                   @endphp

                    <tr>
                        <td>{{$key+1}}</td>
                        <td><span class="uppercase truncate d-block m-auto" data-bs-toggle="tooltip" data-bs-title="{{$value->library_name}}" data-bs-placement="bottom"> {{$value->library_name}}</span>
                          
                            <small>{{!empty($libraryplanData) && ($libraryplanData->is_paid==1) ? 'Paid' : 'Not Paid'}}</small>
                           
                           
                        </td>
                        <td><span class=" d-block m-auto" data-bs-toggle="tooltip" data-bs-title="{{$value->email}}" data-bs-placement="bottom">
                            @if($value->email_verified_at !='')
                            <i class="fa-solid fa-check text-success"></i>
                            @else
                            <i class="fa-solid fa-times text-danger"></i>
                            @endif
                           
                                {{$value->email}}</span>
                            <small>+91-{{$value->library_mobile}}</small>
                        </td>
                        <td>{{$libraryplan ?? 'NA'}}<br>
                           
                            @if($libraryplanData && $libraryplanData->month == 12)
                                <small>Yearly</small>
                            @else
                                <small>Monthly</small>
                            @endif
                        
                            
                        </td>
                        <td>
                            <span class="d-block m-auto">
                            @if($libraryplanData && $libraryplanData->start_date != null)
                            
                                {{ \Carbon\Carbon::parse($libraryplanData->start_date)->toFormattedDateString() }}
                            @else
                             NA
                            @endif
                             </span>
                            @if($value->status==1)
                            <small class="text-success">Active</small>
                            
                            @else
                            <small>Dactive </small>
                            @endif
                           
                        </td>
                        <td>
                            {{ $endDate ? $endDate->toFormattedDateString() : 'End Date : NA' }}
                           
                            <br>
                            
                            @if ($diffInDays > 0)
                            <small class="text-success ">{{ $diffInDays }} Days Left</small>
                            @elseif ($diffInDays < 0)
                                <small class="text-danger ">Expired {{ abs($diffInDays) }} Days ago</small>
                            @elseif($diffInDays == 0 && ($value->status > 0))
                                <small class="text-warning ">Expires today</small>
                            @else
                            <small>Not Paid</small>
                            @endif
                            
                        </td>

                        <td>
                            
                            <ul class="actionalbls">
                                <!-- View Library Info -->
                                <li><a href="{{route('library.show',$value->id)}}" data-bs-toggle="tooltip" data-bs-title="View Library Details" data-bs-placement="bottom"><i class="fas fa-eye"></i></a>
                                </li>

                                <!-- Edit Library Info -->
                                <li><a href="#" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Library Edit" data-bs-title="Edit Library Info"><i class="fas fa-edit"></i></a></li>

                                <!-- Upgrde Plan-->
                                {{-- <li><a href="{{route('library.upgrade',$value->id)}}" data-bs-toggle="tooltip" data-bs-placement="bottom" title="" data-original-title="Upgrade Plan"><i class="fa fa-arrow-up-short-wide"></i></a></li> --}}

                                <!-- Close Seat -->
                                <li><a href="#;" class="link-close-plan" data-id="11" data-bs-toggle="tooltip" data-bs-placement="bottom" title="" data-original-title="Close Seat"><i class="fas fa-times"></i></a></li>

                                <!-- Deletr learners -->
                                <li><a href="#" data-id="{{$value->id}}" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Delete learners" class="delete-learners" data-original-title="Delete learners"><i class="fas fa-trash"></i></a></li>

                                <!-- Delete masters -->
                                <li><a href="#" data-id="{{$value->id}}" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Delete library masters" class="delete-masters" data-original-title="Delete masters"><i class="fas fa-trash"></i></a></li>
                                 <!-- Delete library -->
                                <li><a href="#" data-id="{{$value->id}}" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Delete Library" class="delete-library" data-original-title="Delete Library"><i class="fas fa-trash"></i></a></li>

                                <!-- Make Payment -->
                                <li><a href="{{ route('library.payment', $value->id) }}" data-bs-toggle="tooltip" data-bs-placement="bottom" title=""  data-original-title="Make Payment"> <i class="fas fa-credit-card"></i> </a></li>

                                <!-- Sent Mail -->
                                <li><a href="#" data-id="11" data-bs-toggle="tooltip" data-bs-placement="bottom" title=""  data-original-title="Library-message"><i class="fas fa-envelope"></i></a></li>
                                <!-- Sent Mail -->
                                <li><a href="#" data-id="11" data-bs-toggle="tooltip" data-bs-placement="bottom" title=""  data-original-title="Library-message-whatsapp"><i class="fa-brands fa-whatsapp"></i></a></li>
                                {{-- <li><a href="{{ route('configration.upload', $value->id) }}" data-bs-toggle="tooltip" data-bs-placement="bottom" title="upload library configration" class="configration" data-original-title="upload library configration"> <i class="fas fa-upload"></i> </a></li> --}}
                            </ul>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@include('library.script')

@endsection