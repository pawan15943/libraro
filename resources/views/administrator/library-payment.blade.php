@extends('layouts.admin')


@section('content')
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


<!-- Content -->

<form action="{{route('admin.library.payment.store')}}" method="POST" enctype="multipart/form-data">
    @csrf
    <input name="library_id" value="{{$library_id}}" type="hidden">
    <div class="row mb-4">
        <div class="col-lg-12">
            <div class="card">
                <div class="row g-4">
                    <div class="col-lg-6">
                        <label for="">Payment<span>*</span></label>
                        <select name="payment" class="form-select @error('payment') is-invalid @enderror">
                            <option value="">Select Payment</option>
                            <option value="new">New Payment</option>
                            <option value="renew">Renew Payment</option>
                            <option value="pending">Pending Payment</option>
                            <option value="pre">Pre Payment</option>
                          
                        </select>
                        @error('payment')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                    <div class="col-lg-6">
                        <label for="">Subscription Type <span>*</span></label>
                        <select name="month" class="form-select @error('month') is-invalid @enderror" id="month">
                            <option value="">Select</option>
                            <option value="monthly">Monthly</option>
                            <option value="yearly">Yearly</option>
                          
                        </select>
                        @error('month')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                    <div class="col-lg-4">
                        <label for="">Plan <span>*</span></label>
                        <select name="library_type" class="form-select @error('library_type') is-invalid @enderror" id="library_type">
                         
                            <option value="">Select</option>
                            @foreach($plans as $key => $value)
                            <option value="{{$key}}">{{$value}}</option>  
                            @endforeach
                          
                        </select>
                        @error('library_type')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                </div>
                
                <div class="row justify-content-center mb-4">
                
                    <div class="col-lg-7">
                        <div class="card">
                            <div class="col-lg-12">
                                <h4 class="text-center mb-4">Order Summary</h4>
                                <div class="row g-4">
                                    <div class="col-lg-8">
                                        Amount Paid
                                    </div>
                                    <div class="col-lg-4 text-end">
                                        <i class="fa fa-inr"></i> <span id="amount_paid">0</span>
                                        <input type="hidden" id="amount" class="form-control" name="amount" >
                                    </div>
                        
                                    <div class="col-lg-8">
                                        <a href="#">Offer & Discounts</a>
                                    </div>
                                    <div class="col-lg-4 text-end">
                                        <i class="fa fa-inr"></i> <span id="discount">0</span>
                                    </div>
                        
                                    <div class="col-lg-8">
                                        GST %
                                    </div>
                                    <div class="col-lg-4 text-end">
                                        <i class="fa fa-inr"></i> <span id="gst">0</span>
                                    </div>
                        
                                    <div class="col-lg-8">
                                        <b>Total Amount to Pay</b>
                                    </div>
                                    <div class="col-lg-4 text-end">
                                        <b><i class="fa fa-inr"></i> <span id="total_amount">0</span></b>
                                    </div>
                        
                                    <div class="col-lg-12 mt-3">
                                        <label for="paid_amount" class="form-label">Paid Amount</label>
                                        <input type="text" id="paid_amount" class="form-control" name="paid_amount" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                    </div> 
                    <div class="col-lg-5">
                        <div class="card mt-4">
                            <h4 class="mb-3 text-center">Transaction Summery</h4>
                            <div class="row g-4">
                                <div class="col-lg-6">
                                    <span>Transaction Date</span>
                                    <input type="date" name="transaction_date" class="form-control" value="{{ old('transaction_date', date('Y-m-d')) }}">
                                    @if($errors->has('transaction_date'))
                                    <span class="text-danger">{{ $errors->first('transaction_date') }}</span>
                                    @endif
            
                                </div>
            
                                <div class="col-lg-6">
                                    <span>Transaction Id</span>
                                    <input type="text" name="transaction_id" class="form-control" value="{{ old('transaction_id', mt_rand(10000000, 99999999)) }}">
                                    @if($errors->has('transaction_id'))
                                    <span class="text-danger">{{ $errors->first('transaction_id') }}</span>
                                    @endif
                                </div>
            
                                <div class="col-lg-12">
                                    <span>Payment Method</span>
                                    <select name="payment_method" class="form-select">
                                        <option value="">Select Mode</option>
                                        {{-- <option value="1" {{ old('payment_method') == 'Online' ? 'selected' : '' }}>Online</option> --}}
                                        <option value="2" {{ old('payment_method') == 'Offline' ? 'selected' : '' }}>Offline</option>
                                    </select>
                                    @if($errors->has('payment_method'))
                                    <span class="text-danger">{{ $errors->first('payment_method') }}</span>
                                    @endif
                                </div>
                            </div>
            
                        </div>
                    </div>       
                        
                    
                </div>
                <div class="row justify-content-center mt-3">
                    <div class="col-lg-12">
    
                        <input type="hidden" name="library_transaction_id" value="">
                        <button type="submit" class="btn btn-primary btn-block button" id="pay-btn"> Make Payment </button>
    
                    </div>
                </div>

               
            </div>
        </div>
    </div>

</form>
<script>
    function getFees(library_type, month) {
        $.ajax({
            url: '{{ route('get.subscription.fees')}}',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
            },
            type: 'GET',
            data: {
                "_token": "{{ csrf_token() }}",
                "library_type": library_type,
                "month": month,
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    // Populate order summary
                    $('#amount_paid').text(response.fees);
                    $('#discount').text(response.discount);
                    $('#gst').text(response.gst);
                    $('#total_amount').text(response.paid_amount);

                    // Populate paid amount input field
                    $('#paid_amount').val(response.paid_amount);
                    $('#amount').val(response.fees);
                } else {
                    // Reset all fields if there's an error
                    $('#amount_paid, #discount, #gst, #total_amount').text('0');
                    $('#paid_amount').val('');
                    alert(response.message);
                }
            }
        });
    }

    $(document).ready(function () {
        $(document.body).on('change', '#library_type, #month', function () {
            var library_type = $('#library_type').val();
            var month = $('#month').val();
            
            if (library_type && month) {
                getFees(library_type, month);
            }
        });
    });
</script>



@endsection