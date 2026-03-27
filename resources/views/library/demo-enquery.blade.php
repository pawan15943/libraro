@extends('layouts.library')

@section('content')
<style>
    ul.actions-icons.d-flex {
        justify-content: flex-start;
    }

    ul.actions-icons.d-flex li+li {
        margin-left: 1.3rem;
    }

    .revenue-info ul {
        align-items: center;
    }

    p.uppercase {
        text-transform: uppercase;
    }
</style>



@if($qrbookings?->count() > 0)
    <div class="heading-list justify-content-end mb-4">
        <a href="{{ route('demo-users.create') }}" class="btn btn-primary export m-0">
            <i class="fa-solid fa-plus "></i> Add Inquiry
        </a>
    </div>
    <div class="row g-2 mb-4">
        
        @foreach($qrbookings as $key => $value)
        <div class="col-lg-12">
            <div class="revenue-info">
                <ul>

                    {{-- Profile Image --}}
                    <li style="width: 8%;">
                        @if($value->profile_picture)
                            <img src="{{ asset($value->profile_picture) }}" alt="profile" class="profile-learner">

                        @else
                            <img src="{{ asset('public/img/student_profile.jpeg') }}" alt="profile" class="profile-learner">
  
                        @endif
                    </li>

                    {{-- Seat + Plan --}}
                    <li>
                        <span>Seat No</span>
                        <p class="uppercase">
                            {{ $value->seat_no ? getSeatDisplayByMainNo($value->seat_no) : 'GEN' }}
                            : {{ $value->planType->name ?? 'N/A' }}
                        </p>
                    </li>

                    {{-- Name --}}
                    <li>
                        <span>Name</span>
                        <p class="uppercase">{{ $value->name }}</p>
                    </li>

                    {{-- Mobile --}}
                    <li>
                        <span>Mobile</span>
                        <p>
                            {{ $value->mobile ? '+91-'.decryptData($value->mobile) : '-' }}
                        </p>
                    </li>

                    {{-- Plan Info --}}
                    <li>
                        <span>Plan Price</span>
                        <p>
                            ₹{{ number_format($value->total_amount ?? 0, 0) }}
                            @if($value->payment_screenshot)
                            <a href="{{ asset($value->payment_screenshot) }}" target="_blank" class="text-success">
                                Paid
                            </a>
                            @else
                            <span class="text-danger">Unpaid</span>
                            @endif
                        </p>
                    </li>
                    {{-- Plan Info --}}
                    <li>
                        <span>Start Date</span>
                        <p>
                            {{ \Carbon\Carbon::parse($value->plan_start_date)->format('d-m-Y') }}

                        </p>
                    </li>

                

                    {{-- Actions --}}
                    <li>
                        <p>
                            <ul class="actions-icons d-flex">

                                {{-- Approve --}}
                                @if($value->payment_screenshot && $value->payment_mode=='online')
                                <li>
                                    <form action="{{route('booking.details.approve')}}" method="POST" class="approve-form">
                                        @csrf
                                        <input type="hidden" name="booking_id" value="{{ $value->id }}">
                                        <input type="hidden" name="direct_validate" value="1">
                                        <button type="submit" class="btn btn-success btn-sm noLoader">
                                            <i class="fa fa-check"></i>
                                        </button>
                                    </form>
                                </li>
                                @endif

                                {{-- View --}}
                                <li>
                                    <a href="{{ route('booking.details', $value->id) }}">
                                        <i class="fa fa-eye"></i>
                                    </a>
                                </li>

                                {{-- Delete --}}
                                <li>
                                    <a href="javascript:void(0)" class="delete-booking" data-id="{{ $value->id }}">
                                        <i class="fa fa-trash"></i>
                                    </a>
                                </li>

                                {{-- WhatsApp --}}
                                <li>
                                    <a href="https://wa.me/+91{{decryptData($value->mobile) }}?text=Your%20demo%20plan%20is%20about%20to%20expire.%20Please%20book%20your%20monthly%20seat%20to%20experience%20the%20Library.%0A%0A-%20Team%20XYZ%20Library" target="_blank">
                                   
                                        <i class="fab fa-whatsapp"></i>
                                    </a>
                                </li>

                            </ul>
                        </p>
                    </li>

                </ul>
            </div>
        </div>
        @endforeach
    </div>
@else
<div class="no-data-found">
    <script
        src="https://unpkg.com/@lottiefiles/dotlottie-wc@0.8.1/dist/dotlottie-wc.js"
        type="module"></script>

    <dotlottie-wc
        src="https://lottie.host/5d973bf9-2f1d-4dd5-925f-86da95dbd7b1/t7dXaWIroC.lottie"
        style="width: 200px;height: 200px"
        autoplay
        loop></dotlottie-wc>
    <h4>You haven’t added any Demo Inquiry yet.</h4>
    <!-- Masters -->
    @can('has-permission','Add Exam Master')
    <div class="heading-list justify-content-end mb-1">
        <a href="{{ route('demo-users.create') }}" class="btn btn-primary export m-0">
            <i class="fa-solid fa-plus "></i> Add Inquiry
        </a>
    </div>
    @else
    <span class="text-danger">You don't have Permission to add Exams</span>
    @endcan
    
</div>
@endif


<script>
        $(document).on('click', '.delete-booking', function(e) {
      
            e.preventDefault();
            let bookingId = $(this).data('id');

            Swal.fire({
                title: 'Are you sure?',
                text: "This booking will be permanently deleted!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ url('booking') }}/" + bookingId,
                        type: "DELETE",
                        data: {
                            _token: "{{ csrf_token() }}",
                        },
                        success: function(response) {
                            Swal.fire(
                                'Deleted!',
                                'Booking has been deleted.',
                                'success'
                            ).then(() => {
                                location.reload(); // refresh page after delete
                            });
                        },
                        error: function() {
                            Swal.fire(
                                'Error!',
                                'Something went wrong. Please try again.',
                                'error'
                            );
                        }
                    });
                }
            });
        });
    </script>

@endsection
