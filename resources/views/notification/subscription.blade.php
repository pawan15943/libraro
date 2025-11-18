@extends('layouts.admin')

@section('content')
<div class="container py-4">
    <h2>Buy Notification Credits</h2>

    @if(session('success'))
      <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
      <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form id="subscriptionForm" action="{{ route('admin.notifications.subscription.purchase') }}" method="POST">
        @csrf

        <div class="row">
            {{-- For each channel show plans --}}
            @foreach ($plans as $channel => $channelPlans)
                <div class="col-md-4">
                    <div class="card mb-3">
                        <div class="card-header text-uppercase">{{ $channel }}</div>
                        <div class="card-body">
                            @foreach($channelPlans as $plan)
                                <div class="form-check mb-2">
                                    <input class="form-check-input plan-radio" type="radio"
                                           name="{{ $channel }}_plan_id" id="{{ $channel }}_plan_{{ $plan->id }}"
                                           value="{{ $plan->id }}"
                                           data-price="{{ $plan->price }}"
                                           data-amount="{{ $plan->amount }}">
                                    <label class="form-check-label" for="{{ $channel }}_plan_{{ $plan->id }}">
                                        <strong>{{ number_format($plan->amount) }}</strong> messages -
                                        ₹{{ number_format($plan->price,2) }}
                                    </label>
                                </div>
                            @endforeach

                            {{-- option: no plan --}}
                            <div class="form-check">
                                <input class="form-check-input plan-radio-none" type="radio"
                                       name="{{ $channel }}_plan_id" id="{{ $channel }}_plan_none" value="">
                                <label class="form-check-label" for="{{ $channel }}_plan_none">
                                    Do not buy for {{ $channel }}
                                </label>
                            </div>

                            {{-- show existing subscription for this channel --}}
                            @if($subscription)
                                <hr>
                                <div>
                                    <small>Existing: 
                                    @if($channel == 'waba') {{ $subscription->waba_amount ?? 0 }}
                                    @elseif($channel == 'text') {{ $subscription->text_amount ?? 0 }}
                                    @elseif($channel == 'email') {{ $subscription->email_amount ?? 0 }}
                                    @endif messages</small>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="card p-3 mb-3">
            <h5>Order Summary</h5>
            <div class="row">
                <div class="col-md-4">
                    <p>Total Price: <strong id="totalPrice">₹0.00</strong></p>
                </div>
                <div class="col-md-4">
                    <p>Total Messages: <strong id="totalMessages">0</strong></p>
                </div>
                <div class="col-md-4 text-right">
                    <button type="submit" class="btn btn-primary">Buy (Simulate Payment)</button>
                </div>
            </div>
        </div>
    </form>
</div>

@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
    const radios = document.querySelectorAll('.plan-radio');
    const priceEl = document.getElementById('totalPrice');
    const messagesEl = document.getElementById('totalMessages');

    function recalc() {
        let totalPrice = 0;
        let totalMessages = 0;
        radios.forEach(r => {
            if (r.checked) {
                totalPrice += parseFloat(r.getAttribute('data-price') || 0);
                totalMessages += parseInt(r.getAttribute('data-amount') || 0);
            }
        });
        priceEl.textContent = '₹' + totalPrice.toFixed(2);
        messagesEl.textContent = totalMessages;
    }

    radios.forEach(r => r.addEventListener('change', recalc));
    // also trigger on load in case default radio
    recalc();
});
</script>
@endsection
