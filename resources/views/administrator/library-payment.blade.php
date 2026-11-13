@extends('layouts.admin')

@section('body_class', 'ax-custom-page')

@section('page_actions')
    <x-ui.button variant="gray" icon="fa-solid fa-building-columns" :href="route('library.show', $library_id)">Library</x-ui.button>
@endsection

@section('content')
<x-ui.flash />

<form action="{{ route('admin.library.payment.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <input name="library_id" value="{{ $library_id }}" type="hidden">
    <input type="hidden" name="library_transaction_id" value="">

    <div class="grid gap-x-5 lg:grid-cols-[minmax(0,1fr)_380px] lg:items-start">
        <div>
            <x-ui.section label="Plan">
                <x-ui.card>
                    <div class="ui-field">
                        <span class="ui-label">Payment<span class="req">*</span></span>
                        <x-ui.segmented name="payment" :options="['new' => 'New', 'renew' => 'Renewal', 'pending' => 'Pending', 'pre' => 'Pre-payment']" block />
                        @error('payment')<div class="ui-error"><i class="fa-solid fa-circle-exclamation"></i>{{ $message }}</div>@enderror
                    </div>
                    <div class="ui-fields sm:grid-cols-2">
                        <x-ui.select name="month" id="month" label="Billing" :options="['monthly' => 'Monthly', 'yearly' => 'Yearly']" placeholder="Select" required />
                        <x-ui.select name="library_type" id="library_type" label="Plan" :options="$subscriptionPlans" placeholder="Select" required />
                    </div>
                </x-ui.card>
            </x-ui.section>

            <x-ui.section label="Transaction">
                <x-ui.card>
                    <div class="ui-fields sm:grid-cols-2">
                        <x-ui.input type="date" name="transaction_date" label="Date" :value="date('Y-m-d')" />
                        <x-ui.input name="transaction_id" label="Transaction ID" :value="mt_rand(10000000, 99999999)" class="ui-mono" />
                    </div>
                    <x-ui.select name="payment_method" label="Payment method" :options="['2' => 'Offline']" placeholder="Select mode" />
                </x-ui.card>
            </x-ui.section>
        </div>

        <x-ui.section label="Order summary" class="lg:sticky lg:top-20">
            <x-ui.card flush>
                <x-ui.kv>
                    <x-ui.kv-row label="Plan amount">₹<span id="amount_paid">0</span></x-ui.kv-row>
                    <x-ui.kv-row label="Offers & discounts">− ₹<span id="discount">0</span></x-ui.kv-row>
                    <x-ui.kv-row label="GST">₹<span id="gst">0</span></x-ui.kv-row>
                    <x-ui.kv-row label="Total to pay" class="font-semibold"><span class="ui-title-3">₹<span id="total_amount">0</span></span></x-ui.kv-row>
                </x-ui.kv>
                <input type="hidden" id="amount" name="amount">
                <div class="border-t border-sep p-4 sm:p-5">
                    <x-ui.input name="paid_amount" id="paid_amount" label="Paid amount" prefix="₹" readonly placeholder="Choose billing and plan" />
                    <x-ui.button type="submit" id="pay-btn" size="lg" block class="mt-4" icon="fa-solid fa-check">Make Payment</x-ui.button>
                </div>
            </x-ui.card>
        </x-ui.section>
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
