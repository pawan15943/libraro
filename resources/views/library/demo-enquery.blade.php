@extends('layouts.library')

@section('content')


    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>Demo Bookings</h4>
        <a href="{{ route('demo-users.create') }}" class="btn btn-primary button w-25"> + Add Demo User</a>
    </div>

        <div class="table-responsive">

        
            <table class="table text-center datatable border-bottom dataTable no-footer" id="datatable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Mobile</th>
                        <th>Plan Info</th>
                        <th>Payment Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                     @if($qrbookings?->count() > 0)
                        @php
                        $x = 1;
                        @endphp
                    @foreach($qrbookings as $key => $value)

                    <tr>
                        <td>
                            {{$key+1}}
                        </td>
                        <td>{{$value->name}}<br>{{$value->seat_no ? getSeatDisplayByMainNo($value->seat_no) : 'GEN'}}</td>
                        <td>{{$value->mobile ? '+91-'.decryptData($value->mobile) : ''}}</td>
                        <td>{{ $value->planType->name ?? 'N/A' }} | {{ number_format($value->total_amount ?? 0, 0) }} <br> {{ \Carbon\Carbon::parse($value->plan_start_date)->format('d-m-Y') }}</td>

                        @if($value->payment_screenshot)
                        <td>
                            <a href="{{ asset($value->payment_screenshot) }}" target="_blank" class="badge bg-success text-decoration-none">
                                Paid
                            </a>
                        </td>
                        @else
                        <td>
                            <span class="badge bg-danger">Unpaid</span>
                        </td>
                        @endif

                        <td class="text-center">
                            <ul class="actions-icons justify-content-center">
                               
                                @if( $value->payment_screenshot && $value->payment_mode=='online' )
                                <li>
                                    <form action="{{route('booking.details.approve')}}" method="POST" enctype="multipart/form-data" class="approve-form">
                                        @csrf
                                        <input type="hidden" name="booking_id" value="{{ $value->id }}">
                                        <input type="hidden" name="direct_validate" value="1"> <!-- skip validation -->
                                        <button type="submit" class="btn btn-success noLoader" ><i class="fa fa-check"></i></button>
                                    </form>

                                </li>
                                @endif
                               
                                <li><a href="{{ route('booking.details', $value->id) }}"><i class="fa fa-eye"></i></a></li>
                                <li>
                                    <a href="javascript:void(0)"
                                        class="delete-booking"
                                        data-id="{{ $value->id }}">
                                        <i class="fa fa-trash"></i>
                                    </a>
                                </li>
                                <li>
                                    <a href="javascript:void(0)"
                                        class="delete-booking"
                                        data-id="{{ $value->id }}">
                                        <i class="fab fa-whatsapp"></i>
                                    </a>
                                </li>
                            </ul>
                        </td>
                    </tr>
                    @endforeach
                    @else
                    <tr>
                        <th colspan="6" class="text-center" style="height: 230px;">No Booking Found yet</th>
                    </tr>
                    @endif
                </tbody>
            </table>
      </div>


@endsection