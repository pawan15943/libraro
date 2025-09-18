@extends('sitelayouts.layout')
@section('content')

    <style>
       
        .sacnd-data {
            background: linear-gradient(2deg, #d6faff, transparent);
            display: flex;
            align-items: center;
            justify-content: center;  
        }

        .sacnd-data .form-control,.sacnd-data .form-select{
            height: 45px;
        }



        .action-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
        }

        .action-content span.text-message {
            color: #a1a1a1;
            font-size: .9rem;
        }

        .sacnd-data span.footer {
            font-size: .8rem;
        }

        input.btn.btn-primary {
            background: #18225f;
            border-color: #18225f;
        }

        ul.action-list {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            list-style: none;
            padding: 0;
            justify-content: space-between;
        }

        ul.action-list li {
            width: calc(100% / 2 - .75rem);

        }

        ul.action-list li a {
            text-decoration: none;
            display: block;
            text-align: center;
            padding: 2rem 2rem;
            background: #fff;
            box-shadow: 1px 0 5px #00000021;
            border-radius: 1rem;
            font-weight: 700;
        }

    </style>

    <section class="sacnd-data py-5">
        <div class="container">
            <!-- resources/views/booking/form.blade.php -->
            <div class="row justify-content-center">
                <div class="col-lg-6">
                    <form action="{{ route('booking.store', $branch->uuid) }}" method="POST">
                        <h4 class="mb-4 text-center">Book your Seat</h4>
                        <div class="row g-4">
                            @csrf
                            <input type="hidden" id="branch_id" value="{{$branch->id}}">
                            <div class="col-lg-12">
                                <label>Name <span>*</span></label>
                                <input type="text" name="name" value="{{ old('name') }}" class="form-control char-only @error('name') is-invalid @enderror">
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>



                            <div class="col-lg-6">
                                <label>Mobile (WhatsApp No)<span>*</span></label>
                                <input type="text" name="mobile" value="{{ old('mobile') }}" class="form-control digit-only @error('mobile') is-invalid @enderror">
                                @error('mobile') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-lg-6">
                                <label>Password <span>*</span></label>
                                <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" value="{{ old('password') }}">
                                @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>




                            <div class="col-lg-6">
                                <label for="">Plan <span>*</span></label>
                                <select name="plan_id" id="plan_id" class="form-select @error('plan_id') is-invalid @enderror" name="plan_id">

                                    <option value="">Choose</option>
                                    @foreach($plans as $key => $value)
                                    <option value="{{$value->id}}">{{$value->name}}</option>
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
                                    @foreach($planType as $key => $value)
                                    <option value="{{$value->id}}">{{$value->name}}</option>
                                    @endforeach
                                </select>
                                @error('plan_type_id') <div class="invalid-feedback">{{ $message }}</div> @enderror

                            </div>
                            <div class="col-lg-6">
                                <label for="">Final Payble Amount (INR)<span>*</span></label>
                                <input id="plan_price" type="text" class="form-control digit-only" name="plan_price_id" placeholder="Example : 00" readonly>
                                @error('plan_price_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-lg-6">
                                <label for="">Plan Starts On <span>*</span></label>
                                <input type="date" class="form-control datepicker @error('plan_start_date') is-invalid @enderror" placeholder="Plan Starts On" name="plan_start_date" id="plan_start_date">
                                @error('plan_start_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-lg-12">
                                <label for="">Payment Mode</label>
                                <select name="payment_mode" class="form-select @error('payment_mode') is-invalid @enderror">
                                    <option value="">Select Payment Mode</option>
                                    <option value="online">Online</option>
                                    <option value="offline">Offline</option>

                                </select>
                                @error('payment_mode')
                                <div class="invalid-feedback" role="alert">
                                    {{ $message }}
                                </div>
                                @enderror
                            </div>

                            <div class="col-lg-12">
                                <button type="submit" class="btn btn-primary button">Submit</button>
                            </div>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </section>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#plan_id, #plan_type_id').on('change', function() {
                let plan_id = $('#plan_id').val();
                let plan_type_id = $('#plan_type_id').val();
                let branch_id = $('#branch_id').val();

                if (plan_id && plan_type_id) {
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
        });
    </script>

@endsection
