@extends('layouts.library')
@section('content')

<div class="container-fluid py-3">

    {{-- OPERATION HEADER --}}
    <div class="row mb-3">
        <div class="col-12 text-center">
            <span class="badge bg-warning text-dark px-3 py-2 mb-2">
                🔁 {{ Route::currentRouteName() == 'learner.renew.plan' ? 'PLAN RENEWAL' : 'SEAT BOOKING / APPROVAL' }}
            </span>
            <h5 class="fw-bold mb-0">
                {{ $customer->name ?? 'Learner Details' }}
            </h5>
            <small class="text-muted">
                Mobile: {{ $customer->mobile ?? '' }}
            </small>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-xl-9 col-lg-10 col-md-12">

            <form action="{{route('booking.details.approve')}}" method="POST" enctype="multipart/form-data">
                @csrf

                {{-- ================= BASIC DETAILS ================= --}}
                <div class="card shadow-sm mb-3">
                    <div class="card-header fw-semibold bg-light">
                        Basic & Seat Details
                    </div>
                    <div class="card-body">
                        <div class="row g-3">

                            <input type="hidden" name="booking_id" value="{{ $customer->id ?? '' }}">
                            <input type="hidden" name="branch_id" value="{{ $customer->branch_id ?? '' }}">

                            <div class="col-md-6">
                                <label>Assign Seat No?</label>
                                <select name="general_seat" id="qr_general_seat" class="form-select">
                                    <option value="yes">No</option>
                                    <option value="no">Yes, Allot Seat</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label>Choose Seat No</label>
                                <select name="seat_no" class="form-select" id="seat_id11">
                                    <option value="">GEN</option>
                                    @foreach($seatList as $value)
                                        <option value="{{ $value }}" {{ ($customer->seat_no ?? '') == $value ? 'selected' : '' }}>
                                            {{ $value }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label>Full Name *</label>
                                <input type="text" class="form-control char-only"
                                    name="name" value="{{ old('name') ?? $customer->name ?? '' }}">
                            </div>

                            <div class="col-md-6">
                                <label>Mobile *</label>
                                <input type="text" class="form-control digit-only"
                                    name="mobile" maxlength="10"
                                    value="{{ old('mobile') ?? $customer->mobile ?? '' }}">
                            </div>

                            <div class="col-md-6">
                                <label>Plan *</label>
                                <select id="plan_id11" class="form-select" name="plan_id">
                                    <option value="">Select Plan</option>
                                    @foreach($plans as $value)
                                        <option value="{{ $value->id }}"
                                            {{ old('plan_id', $customer->plan_id) == $value->id ? 'selected' : '' }}>
                                            {{ $value->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label>Plan Type *</label>
                                <select id="plan_type_id11" class="form-select" name="plan_type_id">
                                    <option value="">Choose Shift</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label>Plan Start Date *</label>
                                <input type="date" class="form-control"
                                    name="plan_start_date"
                                    value="{{ old('plan_start_date', $customer->plan_start_date) }}">
                            </div>

                            <input type="hidden" id="plan_price11" name="plan_price_id"
                                   value="{{ old('plan_price_id', $customer->plan_price_id) }}">
                        </div>
                    </div>
                </div>

                {{-- ================= ADDONS ================= --}}
                <div class="card shadow-sm mb-3">
                    <div class="card-header fw-semibold bg-light d-flex justify-content-between align-items-center">
                        Plan Add-ons
                        <i class="fa fa-plus qr_addonToggleIcon"></i>
                    </div>

                    <div class="card-body qr_lockerFields" style="display:none;">
                        <div class="row g-3">

                            <div class="col-md-4">
                                <label>Need Locker?</label>
                                <select name="locker" id="toggleFieldCheckbox11" class="form-select">
                                    <option value="no">No</option>
                                    <option value="yes">Yes</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label>Locker Amount</label>
                                <input type="text" id="locker_amount11" name="locker_amount"
                                       class="form-control" readonly>
                            </div>

                            <div class="col-md-4">
                                <label>Locker No</label>
                                <input type="text" id="locker_no11" name="locker_no"
                                       class="form-control digit-only">
                            </div>

                            <div class="col-md-6">
                                <label>Discount Type</label>
                                <select id="discountType11" name="discount_type" class="form-select">
                                    <option value="">None</option>
                                    <option value="percentage">Percentage</option>
                                    <option value="amount">Amount</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label>Discount Amount</label>
                                <input type="text" id="discount_amount11"
                                       name="discount_amount" class="form-control" readonly>
                            </div>

                        </div>
                    </div>
                </div>

                {{-- ================= PAYMENT ================= --}}
                <div class="card shadow-sm mb-3">
                    <div class="card-header fw-semibold bg-light">
                        Payment Details
                    </div>
                    <div class="card-body">
                        <div class="row g-3">

                            <div class="col-md-4">
                                <label>Payable Amount *</label>
                                <input id="paid_amount11" class="form-control digit-only"
                                       name="paid_amount">
                                <small id="pending_amt11" class="text-danger"></small>
                            </div>

                            <div class="col-md-4">
                                <label>Due Date</label>
                                <input type="date" id="due_date11"
                                       name="due_date"
                                       class="form-control" readonly>
                            </div>

                            <div class="col-md-4">
                                <label>Payment Mode *</label>
                                <select name="payment_mode" class="form-select">
                                    <option value="">Select</option>
                                    <option value="online">Online</option>
                                    <option value="offline">Offline</option>
                                </select>
                            </div>

                        </div>
                    </div>
                </div>

                {{-- ================= SUBMIT ================= --}}
                <div class="card shadow-sm">
                    <div class="card-body text-center">
                        <button type="submit"
                                class="btn btn-success btn-lg w-100">
                            ✅ Book / Renew Seat
                        </button>
                    </div>
                </div>

            </form>

        </div>
    </div>
</div>

@endsection
