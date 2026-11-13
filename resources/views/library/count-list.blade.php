@extends('layouts.admin')
@section('content')

<!-- Content Header (Page header) -->
@php
    use Carbon\Carbon;
    use App\Helpers\HelperService; 
    if(request('type') === 'total_booking'){
        $text='Total Slots Bookings';
    }elseif(request('type') === 'active_booking'){
        $text='Active Slots';
    }elseif(request('type') === 'expired_seats'){
        $text='Expired Slots';
    }elseif(request('type') === 'thisbooking_slot'){
        $text='This month total slots';
    }elseif(request('type') === 'booing_slot'){
        $text='This month Booked slots';
    }elseif(request('type') === 'till_previous_book'){
        $text='Previous month booked slots';
    }elseif(request('type') === 'expire_booking_slot'){
        $text='This month Expired';
    }elseif(request('type') === 'expired_in_five'){
        $text='Expired in 5 Days';
    }elseif(request('type') === 'extended_seat'){
        $text='Extended Seats';
    }elseif(request('type') === 'online_paid'){
        $text='Online Paid';
    }elseif(request('type') === 'offline_paid'){
        $text='Offline Paid';
    }elseif(request('type') === 'other_paid'){
        $text='Pay Later';
    }elseif(request('type') === 'swap_seat'){
        $text='Swap Seats';
    }elseif(request('type') === 'learnerUpgrade'){
        $text='Upgrade Seats';
    }elseif(request('type') === 'reactive_seat'){
        $text='Reactive Seats';
    }elseif(request('type') === 'renew_seat'){
        $text='Renew Seats';
    }elseif(request('type') === 'close_seat'){
        $text='Close Seats';
    }elseif(request('type') === 'delete_seat'){
        $text='Delete Seats';
    }else{
        $text='';
    }
@endphp
<div class="row mb-4">
    <div class="col-lg-12">
        <b class="d-block pb-3">{{$text}} for  {{ request('month') }}/{{ request('year') }}: [{{$result->count()}}]</b>
        <div class="table-responsive">
            <table class="table text-center datatable border-bottom" id="datatable">
                <thead>
                    <tr>
                        <th>Seat No.</th>
                        <th>Learner Info</th>
                        <th>Contact Info</th>
                        <th>Active Plan</th>
                        <th>Expired On</th>
                        <th>Status</th>
                        <th>Action</th>
                       
                    </tr>
                </thead>
                <tbody>
                    
                    @foreach ($result  as $key => $value)
                   
                    @php

                         $libraryplan=App\Models\Subscription::where('id',$value->library_type)->value('name');
                         $libraryplanData=DB::table('library_transactions')->where('id',$value->latest_transaction_id)->first();

                         $endDate = ($libraryplanData && $libraryplanData->end_date != null) ? Carbon::parse($libraryplanData->end_date) : null;
                         $diffInDays = $endDate ? $today->diffInDays($endDate, false) : 0;
                   @endphp

                    <tr>
                        <td>{{$key+1}}</td>
                        <td><span class="uppercase truncate d-block m-auto" data-bs-toggle="tooltip" data-bs-title="{{$value->library_name}}" data-bs-placement="bottom"> {{$value->library_name}}</span>
                            {{-- <small>{{$value->library_owner_contact}}</small> --}}
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
                        <td>{{$libraryplan}}<br>
                           
                            @if($libraryplanData && $libraryplanData->month == 12)
                                <small>Yearly</small>
                            @else
                                <small>Monthly</small>
                            @endif
                        
                            
                        </td>
                        <td>
                            @if($libraryplanData && $libraryplanData->start_date != null)
                            <span class="d-block m-auto">
                                {{ \Carbon\Carbon::parse($libraryplanData->start_date)->toFormattedDateString() }}
                            </span>
                             @endif
                        
                            @if($value->status==1)
                            <small class="text-success">Active</small>
                            @else
                            <small class="text-danger">Expired</small>
                            @endif
                           
                        </td>
                        <td>
                            {{ $endDate ? $endDate->toFormattedDateString() : 'N/A' }}
                           
                           
                            <br>
                            @if ($diffInDays > 0)
                            <small class="text-success ">{{ $diffInDays }} Days Pending</small>
                            @elseif ($diffInDays < 0)
                                <small class="text-danger ">Expired {{ abs($diffInDays) }} Days ago</small>
                                
                            @else
                                <small class="text-warning ">Expires today</small>
                            @endif
                            
                        </td>

                        <td>
                            
                            <ul class="actionalbls">
                                <!-- View Library Info -->
                                <li><a href="{{route('library.show',$value->id)}}" data-bs-toggle="tooltip" data-bs-title="View Library Details" data-bs-placement="bottom"><i class="fas fa-eye"></i></a>
                                </li>

                                <!-- Edit Library Info -->
                                <li><a href="#" data-bs-toggle="tooltip" data-bs-placement="bottom" title="" data-bs-title="Edit Library Info"><i class="fas fa-edit"></i></a></li>

                                <!-- Upgrde Plan-->
                                <li><a href="#" data-bs-toggle="tooltip" data-bs-placement="bottom" title="" data-original-title="Upgrade Plan"><i class="fa fa-arrow-up-short-wide"></i></a></li>

                                <!-- Close Seat -->
                                <li><a href="#;" class="link-close-plan" data-id="11" data-bs-toggle="tooltip" data-bs-placement="bottom" title="" data-original-title="Close Seat"><i class="fas fa-times"></i></a></li>

                                <!-- Deletr learners -->
                                <li><a href="#" data-id="{{$value->id}}" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Delete Lerners" class="delete-learners" data-original-title="Delete Lerners"><i class="fas fa-trash"></i></a></li>
                                <!-- Delete masters -->
                                <li><a href="#" data-id="{{$value->id}}" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Delete masters" class="delete-masters" data-original-title="Delete masters"><i class="fas fa-trash"></i></a></li>
                                <!-- Make Payment -->
                                <li><a href="{{ route('library.payment', $value->id) }}" data-bs-toggle="tooltip" data-bs-placement="bottom" title="" class="delete-customer" data-original-title="Make Payment"> <i class="fas fa-credit-card"></i> </a></li>

                                <!-- Sent Mail -->
                                <li><a href="#" data-id="11" data-bs-toggle="tooltip" data-bs-placement="bottom" title="" class="delete-customer" data-original-title="Delete Lerners"><i class="fas fa-envelope"></i></a></li>
                                <!-- Sent Mail -->
                                <li><a href="#" data-id="11" data-bs-toggle="tooltip" data-bs-placement="bottom" title="" class="delete-customer" data-original-title="Delete Lerners"><i class="fa-brands fa-whatsapp"></i></a></li>
                                <li><a href="{{ route('configration.upload', $value->id) }}" data-bs-toggle="tooltip" data-bs-placement="bottom" title="upload library configration" class="configration" data-original-title="upload library configration"> <i class="fas fa-upload"></i> </a></li>
                            </ul>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
          
        </div>
    </div>
</div>

@endsection