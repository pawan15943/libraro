@extends('layouts.library')

@section('content')

@php use Illuminate\Support\Str; @endphp

<style>
    .subscription-card {
        background: linear-gradient(135deg, #001f3f 0%, #003366 100%);
        border-radius: 20px;
        padding: 25px;
        color: white;
    }

    .credit-btn {
        border: 1px solid #ccc;
        width: 50px;
        border-radius: 12px;
        font-size: 15px;
        margin-right: 10px;
        cursor: pointer;
        transition: all .15s;
        background: white;
        height: 50px;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .credit-btn.active {
        background: #001f3f;
        color: white;
        border-color: #001f3f;
        font-weight: bold;
    }

    .channel-box {
        border: 1px solid #eee;
        padding: 25px;
        border-radius: 20px;
        margin-bottom: 15px;
        background: #fff;
    }

    .pay-btn {
        background: #001f3f;
        border: none;
        padding: 10px 35px;
        border-radius: 40px;
        color: white;
        font-size: 20px;
        margin-top: 20px;
        display: inline-block;
    }

    .summary-plan-list {
        list-style: none;
        padding-left: 0;
    }

    .summary-plan-list li {
        padding: 6px 0;
        border-bottom: 1px dashed #eee;
    }

    .plan-features2 {
        font-size: 14px;
        color: #666;
        list-style: none;
        padding: 0;
    }

</style>


<div class="container mt-4">

    <form method="POST" action="{{ route('notifications.subscription.purchase') }}">
        @csrf

        <div class="row justify-content-center">

            {{-- LOOP THROUGH CHANNELS --}}
            @foreach ($planswaba as $channel => $channelPlans)

            @php
            $channelUpper = strtoupper($channel);
            $channelId = Str::slug($channel,'_');
            @endphp

            <div class="col-md-4">

                <div class="channel-box">

                    <h4 class="text-center mb-3">{{ ucfirst($channel) }} Subscription</h4>

                    <div class="subscription-card text-center">
                        <div style="opacity: 0.7; font-size:14px;">
                            ₹0.15/{{ $channelUpper }} (Original)
                        </div>

                        <div style="font-size:32px; font-weight:700;">
                            ₹0.10/{{ $channelUpper }}
                        </div>

                        <div style="opacity: 0.8;">
                            Minimum credits to purchase: {{ $channelPlans->min('amount') }}
                        </div>
                    </div>

                    <p class="mt-4 mb-2">How many {{ ucfirst($channel) }} credits?</p>

                    <div class="d-flex mb-3 channel-buttons" data-channel="{{ $channelId }}">

                        {{-- DYNAMIC BUTTONS FROM DB --}}
                        @foreach($channelPlans as $key=> $cplan)
                        <div class="credit-btn" data-value="{{ $cplan->amount }}" data-price="{{ $cplan->amount }}" data-plan-name="{{ $cplan->type }}" data-plan-id="{{ $cplan->id }}">
                            {{ $cplan->type }}
                        </div>
                        
                        @endforeach

                    </div>

                    <input type="hidden" name="{{ $channelId }}_credits" id="{{ $channelId }}_credits">
                    <input type="hidden" name="{{ $channelId }}_price" id="{{ $channelId }}_price">
                    <input type="hidden" name="{{ $channelId }}_plan_name" id="{{ $channelId }}_plan_name">
                    <input type="hidden" name="{{ $channelId }}_plan_id" id="{{ $channelId }}_plan_id">

                    <ul class="plan-features2">
                        <li>{{ $channelUpper }} credits will be added</li>
                        <li>Credits have no separate validity — use anytime</li>
                    </ul>

                </div>
            </div>

            @endforeach
        </div>

        {{-- FINAL SUMMARY --}}
        <div class="row justify-content-center">
            <div class="col-lg-4">
                <div class="card p-4 my-3">
                    <h4 class="mb-4">Your Order Summary</h4>

                    <p>Total Credits: <strong id="totalCredits">0</strong></p>
                    <p>Total Amount: <strong id="totalAmount">₹0</strong></p>
                    <input type="hidden" name="total_amount" id="total_amount_pay">

                    <div class="mt-3">
                        <strong>Selected Plans</strong>
                        <ul id="selectedPlans" class="summary-plan-list">
                            <li class="text-muted">No plans selected</li>
                        </ul>
                    </div>

                    <button type="submit" class="pay-btn">Pay</button>
                </div>
            </div>
        </div>

    </form>
</div>



<script>
    document.addEventListener("DOMContentLoaded", function() {

        // Use event delegation so this works even if DOM changes
        document.querySelectorAll('.channel-buttons').forEach(container => {
            container.addEventListener('click', function(e) {
                const btn = e.target.closest('.credit-btn');
                if (!btn) return;

                // remove active style from siblings
                container.querySelectorAll('.credit-btn').forEach(b => b.classList.remove('active'));

                btn.classList.add('active');

                let channel = container.dataset.channel;
                let creditVal = btn.dataset.value || 0;
                let priceVal = btn.dataset.price || 0;
                let planName = btn.dataset.planName || '';

                // set hidden inputs
                const creditsInput = document.getElementById(channel + '_credits');
                const priceInput = document.getElementById(channel + '_price');
                const nameInput = document.getElementById(channel + '_plan_name');
                document.getElementById(channel + '_plan_id').value = btn.dataset.planId;

                if (creditsInput) creditsInput.value = creditVal;
                if (priceInput) priceInput.value = priceVal;
                if (nameInput) nameInput.value = planName;

                updateSummary();
            });
        });

        function updateSummary() {
            let totalCredits = 0;
            let totalAmount = 0;
            let selected = [];

            // for each channel hidden inputs (find inputs that end with _credits)
            document.querySelectorAll('input[type=hidden]').forEach(input => {
                if (!input.id.endsWith('_credits')) return;

                const base = input.id.replace('_credits', '');
                const credits = parseInt(input.value || 0);
                const price = parseFloat((document.getElementById(base + '_price') || {
                    value: 0
                }).value || 0);
                const planName = (document.getElementById(base + '_plan_name') || {
                    value: ''
                }).value || '';

                if (credits > 0) {
                    totalCredits += credits;
                    // price in DB is price for plan (already currency), so add price once
                    totalAmount += price;

                    selected.push({
                        channel: base.replace('_', ' ')
                        , planName: planName
                        , credits: credits
                        , price: price
                    });
                }
            });

            document.getElementById('totalCredits').innerText = totalCredits.toString();
            document.getElementById('totalAmount').innerText = "₹" + totalAmount.toFixed(2);
            document.getElementById('total_amount_pay').value = totalAmount.toFixed(2);

            // render selected plans
            const selEl = document.getElementById('selectedPlans');
            selEl.innerHTML = '';
            if (selected.length === 0) {
                selEl.innerHTML = '<li class="text-muted">No plans selected</li>';
            } else {
                selected.forEach(s => {
                    const li = document.createElement('li');
                    li.innerHTML = '<strong>' + (s.planName || 'Plan') + '</strong> (' + s.channel.replace('_', ' ') + ') — ' + s.credits + ' credits — ₹' + parseFloat(s.price).toFixed(2);
                    selEl.appendChild(li);
                });
            }
        }

        // initialize summary in case existing values present
        updateSummary();
    });

</script>

@endsection
