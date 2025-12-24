@extends('sitelayouts.layout')
@section('content')

<section class="sacnd-data py-5">

    <div class="container">
        <!-- resources/views/booking/form.blade.php -->
        <form action="{{ route('booking.store', $branch->uuid) }}" method="POST">
            @if (session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
            @endif
            @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
            @endif

            <div class="row justify-content-center">
                <div class="col-lg-6">
                    <div class="online-booking">
                        <span class="steps">Step-1</span>
                        <h4 class="mb-4 text-center">Book your Seat</h4>
                        <div class="row g-3">
                            @csrf
                            <input type="hidden" id="branch_id" value="{{$branch->id}}">

                            <div class="col-lg-6">
                                <label for="general_seat">Assign Seat No?</label>
                                <select name="general_seat" id="general_seat" class="form-select">
                                    <option value="yes" {{ old('general_seat') == 'yes' ? 'selected' : '' }}>
                                        No
                                    </option>
                                    <option value="no" {{ old('general_seat') == 'no' ? 'selected' : '' }}>
                                        Yes, Allot a Seat No.
                                    </option>

                                </select>
                            </div>

                            <div class="col-lg-6">
                                <label for="seat_id">Choose Seat No. <span>*</span></label>
                                <select name="seat_no" class="form-select" id="seat_id">
                                    <option value="">Choose Seat No</option>
                                    @foreach($availableSeats as $value)
                                    <option value="{{ $value }}"
                                        {{ old('seat_no') == $value ? 'selected' : '' }}>
                                        {{ $value }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-lg-12">
                                <label>Name <span>*</span></label>
                                <input type="text" name="name" value="{{ old('name') }}" class="form-control char-only @error('name') is-invalid @enderror">
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-lg-6">
                                <label>Mobile (WhatsApp No)<span>*</span></label>
                                <input type="text" name="mobile" value="{{ old('mobile') }}" class="form-control digit-only @error('mobile') is-invalid @enderror" maxlength="10" minlength="8">
                                @error('mobile') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-lg-6">
                                <label for="">Plan <span>*</span></label>
                                <select name="plan_id" id="plan_id3" class="form-select @error('plan_id') is-invalid @enderror" name="plan_id">
                                    <option value="">Choose</option>
                                    @foreach($plans as $key => $value)
                                    <option value="{{ $value->id }}" {{ old('plan_id') == $value->id ? 'selected' : '' }}>{{$value->name}}</option>
                                    @endforeach
                                </select>
                                @error('plan_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-lg-6">
                                <label for="">Plan Type / Shift <span>*</span></label>
                                <select id="plan_type_id" class="form-select @error('plan_type_id') is-invalid @enderror" name="plan_type_id">
                                    <option value="">Choose</option>
                                </select>
                                @error('plan_type_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-lg-6">
                                <label for="">Final Payble Amount (INR)<span>*</span></label>
                                <input id="plan_price" type="text" class="form-control digit-only" name="plan_price_id" placeholder="Example : 00" value="{{ old('plan_price_id') }}" readonly>
                                @error('plan_price_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-lg-6">
                                <label for="">Plan Starts On <span>*</span></label>
                                <input type="date" class="form-control datepicker @error('plan_start_date') is-invalid @enderror" placeholder="Plan Starts On" name="plan_start_date" id="plan_start_date" value="{{ old('plan_start_date') }}">
                                @error('plan_start_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-lg-6">
                                <label for="">Payment Mode</label>
                                <select name="payment_mode" class="form-select @error('payment_mode') is-invalid @enderror">
                                    <option value="">Select Payment Mode</option>
                                    <option value="online" {{ old('payment_mode') == 'online' ? 'selected' : '' }}>Online</option>
                                    <option value="offline" {{ old('payment_mode') == 'offline' ? 'selected' : '' }}>Offline</option>

                                </select>
                                @error('payment_mode')
                                <div class="invalid-feedback" role="alert">
                                    {{ $message }}
                                </div>
                                @enderror
                            </div>

                            <div class="col-lg-12">
                                <button type="submit" class="btn btn-primary button">Next <i class="fa fa-long-arrow-right ms-2"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
        </form>
    </div>
    </div>
</section>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {

        function loadPlanTypes() {
            const generalSeat = $('#general_seat').val();
            const seatId = $('#seat_id').val();
            const branch_id = $('#branch_id').val();
            console.log("branchwith", branch_id);
            if (generalSeat === 'yes') {
                // General seat → no seat-wise filter
                $('#seat_id').prop('disabled', true).val('');
                getTypeSeatwise('', branch_id); // show general plan types
            } else if (generalSeat === 'no') {
                // Specific seat → enable seat selection
                $('#seat_id').prop('disabled', false);

                if (seatId) {
                    // If seat already selected
                    getTypeSeatwise(seatId, branch_id); // show seat-wise plan types
                } else {
                    // Seat not selected yet → clear plan type dropdown
                    $('#plan_type_id').html('<option value="">Choose</option>');
                }
            } else {
                // Empty / default selection
                $('#seat_id').prop('disabled', true).val('');
                $('#plan_type_id').html('<option value="">Choose</option>');
            }
        }
        loadPlanTypes();

        // On change of general seat
        $('#general_seat').on('change', function() {
            loadPlanTypes();
        });

        $('#plan_id3, #plan_type_id').on('change', function() {
            let plan_id = $('#plan_id3').val();
            let plan_type_id = $('#plan_type_id').val();
            let branch_id = $('#branch_id').val();

            if (plan_id && plan_type_id && branch_id) {
                $.ajax({
                    url: "{{ route('get.plan.price') }}",
                    type: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        plan_id: plan_id,
                        plan_type_id: plan_type_id,
                        branch_id: branch_id
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#plan_price').val(response.price);
                        } else {
                            $('#plan_price').val('');
                        }
                    }
                });
            }
        });

        function getTypeSeatwise(seatId, branchId) {

            $('#plan_type_id').empty().append('<option value="">Choose Shift</option>');
            $.ajax({
                url: '{{ route('getPlantypeSeatwise') }}',
                type: 'GET',
                data: {
                    "_token": "{{ csrf_token() }}",
                    "seatNo": seatId,
                    "branchId": branchId,
                },
                dataType: 'json',
                success: function(html) {
                    console.log(html);
                    if (html) {

                        let selectedOption = $("#plan_type_id").find("option:selected");

                        $("#plan_type_id").empty();
                        $("#plan_type_id").append('<option value="">Choose Shift</option>');

                        if (selectedOption.val() !== "") {
                            $("#plan_type_id").append('<option value="' + selectedOption.val() + '" selected>' + selectedOption.text() + '</option>');
                        }

                        $.each(html, function(index, planType) {
                            // Avoid adding the option that is already selected
                            if (planType.id != selectedOption.val()) {
                                $("#plan_type_id").append('<option value="' + planType.id + '">' + planType.name + '</option>');
                            }
                        });
                    } else {
                        $("#plan_type_id").empty();
                        $("#plan_type_id").append('<option value="">Select Plan Type</option>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX error:", status, error); // Log any errors
                }
            });

        }

        $('#seat_id').on('change', function() {
            const generalSeat = $('#general_seat').val();
            if (generalSeat === 'no') {
                const seatId = $(this).val();
                const branch_id = $('#branch_id').val();
                if (seatId) {
                    getTypeSeatwise(seatId, branch_id);
                } else {
                    $('#plan_type_id').html('<option value="">Choose</option>');
                }
            }
        });

    });
</script>

@endsection