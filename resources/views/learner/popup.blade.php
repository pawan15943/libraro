<link rel="stylesheet" href="{{ asset('public/css/booking-modal.css') }}?v={{ @filemtime(public_path('css/booking-modal.css')) }}" />
<link rel="stylesheet" href="{{ asset('public/css/lb-booking.css') }}?v={{ @filemtime(public_path('css/lb-booking.css')) }}" />

@can('has-permission', 'Book Seat')

@php
    $lbkHidden = toggleHideField();
    $lbkGeneralOk = auth()->user() && auth()->user()->can('has-permission', 'General Seat Booking') && !in_array('12', $lbkHidden);
    $lbkFmt = function ($t) { if (!$t) return ''; try { $c = \Carbon\Carbon::parse($t); return $c->format('i') === '00' ? $c->format('g A') : $c->format('g:i A'); } catch (\Throwable $e) { return (string) $t; } };
    // an advanced shift without its own time shows the library hours (PlanType::bookingTimes)
    $lbkShifts = \App\Models\PlanType::where('branch_id', getCurrentBranch())->whereNull('deleted_at')->get(['id', 'name', 'branch_id', 'parent_id', 'start_time', 'end_time', 'day_type_id', 'booking_mode'])
        ->mapWithKeys(fn ($pt) => [$pt->id => ['s' => $pt->bookingTimes()[0], 'e' => $pt->bookingTimes()[1], 'time' => $pt->isAnyTime() ? $pt->timeLabel() : (($pt->start_time && $pt->end_time) ? $lbkFmt($pt->start_time) . ' – ' . $lbkFmt($pt->end_time) : ''), 'day' => (int) $pt->day_type_id]]);
@endphp
<div class="modal fade booking-modal-module lbk" id="seatAllotmentModal" tabindex="-1" data-bs-focus="false" aria-labelledby="lbkTitle" aria-hidden="true" data-lbk-step="1"
     data-general-ok="{{ $lbkGeneralOk ? '1' : '0' }}" data-seats-url="{{ route('getAvailableSeats') }}" data-shifts='@json($lbkShifts)'>
    <div class="modal-dialog lbk-dialog">
        <div id="success-message" class="alert alert-success" style="display:none;"></div>
        <div class="modal-content">
            <div class="modal-header lbk-head">
                <div class="lbk-head-row">
                    <span class="lbk-head-ic lbk-head-seat"><x-lb-icon name="armchair" /><b id="lbkHeadCode"></b><em id="lbkHeadFloor"></em></span>
                    <div class="lbk-head-t">
                        <h2 class="lbk-title" id="lbkTitle"><span id="lbkHeadTitle">New booking</span></h2>
                        {{-- #seat_no_head is filled by the seat map (source of the fixed seat), not shown --}}
                        <span id="seat_no_head" hidden>Booking Form</span>
                        <small class="lbk-sub"><span id="lbkHeadSub"></span><span id="lbkHeadBranch">{{ getCurrentBranchName() }}</span></small>
                        {{-- step 2: what was picked in step 1 (shift · plan · till) --}}
                        <small class="lbk-sub lbk-sub-plan" id="lbkHeadPlan"></small>
                    </div>
                    <button type="button" class="lbk-head-edit" data-lbk-edit title="Change seat, shift or plan" aria-label="Change seat, shift or plan"><x-lb-icon name="pencil" /></button>
                    <button type="button" class="lbk-x" data-bs-dismiss="modal" aria-label="Close"><x-lb-icon name="x" /></button>
                </div>
                <div class="lbk-progress" aria-hidden="true"><i></i><i></i></div>
                <div class="lbk-steps"><span class="lbk-stepno">1 · Seat &amp; plan</span><span>2 · Details</span></div>
            </div>
            <form id="seatAllotmentForm">
                @csrf
                <div class="modal-body">
                    <div id="error-message" class="alert alert-danger mb-3 mt-0" style="display:none;"></div>
                    <div id="validation-error-message" class="alert alert-danger mb-3 mt-0" style="display:none;"></div>
                    <div class="detailes">

                        <input type="hidden" class="form-control char-only" name="seat_no" value="" id="seat_no" autocomplete="off">

                        {{-- ===== STEP 1 · Seat, shift & plan ===== --}}
                        <div class="lbk-step" data-step="1">

                            {{-- seat --}}
                            <div class="lbk-label lbk-label--row" id="lbkSeatSec">
                                <span>Seat</span><em class="lbk-opt" id="lbkSeatHint"></em>
                            </div>
                            <div class="lbk-seatpick" id="lbkSeatPick">
                                <label class="lbk-search"><x-lb-icon name="search" /><input type="search" id="lbkSeatQ" placeholder="Search seat" autocomplete="off" aria-label="Search seat"><button type="button" class="lbk-search-x" id="lbkSeatQx" aria-label="Clear" hidden><x-lb-icon name="x" /></button></label>
                                <div class="lbk-rail" id="lbkSeats" role="listbox" aria-label="Seats"></div>
                                <div class="lbk-note" id="lbkSeatEmpty" hidden>No free seat matches</div>
                            </div>
                            <div class="lbk-fixed" id="lbkFixedSeat" hidden>
                                <span class="lbk-fixed-ic"><x-lb-icon name="armchair" /></span>
                                <span class="lbk-fixed-t"><b id="lbkFixedName">Seat</b><small>Picked on the seat map</small></span>
                                <span class="lbk-fixed-tag"><x-lb-icon name="lock" />Locked</span>
                            </div>
                            <div class="row g-3 lbk-seatrow lbk-native">
                                <div class="col-lg-6">
                                    <label for="general_seat" class="form-label">Assign Seat No ?</label>
                                    <select name="general_seat" id="general_seat" class="form-select">
                                        <option value="yes">No</option>
                                        <option value="no">Yes, Allot a Seat No.</option>
                                    </select>
                                </div>
                                <div class="col-lg-6">
                                    <label for="seat_id" class="form-label">Choose Seat No. <span class="required-star">*</span></label>
                                    <select class="form-select" id="seat_id" disabled>
                                        <option value="" selected>Choose Seat No.</option>
                                        @foreach($newAvailableSeats as $key => $value)
                                        <option value="{{ $value['main'] }}">{{ $value['display'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            {{-- shift: every shift as a compact tile --}}
                            <section class="lbk-box lbk-shiftbox" id="lbkShiftBox">
                                <div class="lbk-box-h">
                                    <span class="lbk-box-k" id="lbkShiftHead">Shift</span>
                                    <span class="lbk-shift-hint" id="lbkShiftNote" hidden></span>
                                </div>
                                <div class="lbk-shifts" id="lbkShifts" role="listbox" aria-label="Shift"></div>
                            </section>

                            {{-- timing for "fixed hours" / "free" shifts (docs/ADVANCED_SHIFTS.md) --}}
                            <section class="lbk-box lbk-timing" id="lbkTimingBox" hidden data-url="{{ route('shiftTiming.windows') }}">
                                <div class="lbk-box-h"><span class="lbk-box-k" id="lbkTimingHead">Shift timing</span></div>
                                <div class="lbk-grid">
                                    <div class="lbk-f">
                                        <label for="shift_start_time" class="form-label">Starts at <span class="required-star">*</span></label>
                                        <input type="text" class="form-control" name="shift_start_time" id="shift_start_time" data-lb-time disabled>
                                    </div>
                                    <div class="lbk-f">
                                        <label for="shift_end_time" class="form-label">Ends at <span class="required-star lbk-free-only">*</span></label>
                                        <input type="text" class="form-control" name="shift_end_time" id="shift_end_time" data-lb-time disabled>
                                    </div>
                                    <div class="lbk-f lbk-f--full lbk-free-only">
                                        <label for="lbkFreePrice" class="form-label">Price for this plan (₹) <span class="required-star">*</span></label>
                                        <input type="text" inputmode="decimal" class="form-control" id="lbkFreePrice" maxlength="10" placeholder="Enter price">
                                    </div>
                                </div>
                                <div class="lbk-err" id="lbkTimingClash" role="alert" hidden></div>
                                <div class="lbk-note" id="lbkTimingNote"></div>
                                <div class="lbk-gaps" id="lbkTimingGaps"></div>
                            </section>
                            <div class="row g-3 lbk-native">
                                <div class="col-lg-6">
                                    <label for="plan_type_id" class="form-label">Plan Type / Shift <span class="required-star">*</span></label>
                                    <select id="plan_type_id" class="form-select" name="plan_type_id">
                                        <option value="">Choose</option>
                                    </select>
                                </div>
                            </div>

                            {{-- plan --}}
                            <div class="lbk-label lbk-label--row"><span>Plan</span><em class="lbk-opt" id="lbkPlanHint"></em></div>
                            <div class="lbk-chips" id="lbkPlans" role="listbox" aria-label="Plan"></div>
                            <div class="lbk-native">
                                <label for="plan_id3" class="form-label">Plan <span class="required-star">*</span></label>
                                <select name="plan_id" id="plan_id3" class="form-select lbk-select">
                                    <option value="">Choose a plan</option>
                                    @foreach($plans as $key => $value)
                                    <option value="{{$value->id}}">{{$value->name}}</option>
                                    @endforeach
                                    @include('learner.partials.custom-plan-options')
                                </select>
                            </div>
                            <div class="lbk-grid lbk-grid--mt">
                                <div class="lbk-f">
                                    <label for="plan_start_date" class="form-label">Starts on <span class="required-star">*</span></label>
                                    <div class="booking-date-group">
                                        <input type="date" class="form-control lbk-date-fmt" name="plan_start_date" id="plan_start_date">
                                        {{-- the date as "12 Oct 2026", same as Valid till (the native field stays for the picker) --}}
                                        <span class="lbk-date-txt" aria-hidden="true"></span>
                                        <x-lb-icon name="calendar-days" class="lbk-date-ic" />
                                    </div>
                                    <span id="chargeable_days" class="info-hint lbk-hint"></span>
                                </div>
                                <div class="lbk-f">
                                    <span class="form-label">Valid till</span>
                                    <div class="lbk-ro" id="lbkValidTill">Pick a plan</div>
                                    {{-- custom plan: the owner sets the valid-till date here (docs/CUSTOM_PLAN.md) --}}
                                    <input type="date" class="form-control" id="lbkCustomEnd" aria-label="Valid till" hidden>
                                    <span id="end_date_show" class="info-hint lbk-native-hint"></span>
                                </div>
                                <input type="hidden" id="plan_price_id" class="form-control" name="plan_price_id" placeholder="Example : 00 Rs" readonly>
                            </div>
                            @include('learner.partials.custom-plan-dates', ['select' => '#plan_id3', 'start' => '#plan_start_date', 'end' => '#lbkCustomEnd', 'endDisplay' => '#lbkValidTill', 'priceInputId' => 'lbkCustomPrice'])

                            {{-- extra shifts picked on the shift cards (same seat): hidden inputs, one booking and transaction (docs/ADVANCED_SHIFTS.md) --}}
                            <div id="lbkExtras" hidden data-price-url="{{ route('getPricePlanwise') }}" data-allowed="{{ libraryPlanAllows('Multiple Shifts') ? '1' : '0' }}"></div>
                            @if(planLocked('Multiple Shifts'))
                            <button type="button" class="lbk-pro-more is-pro-locked" data-pro-lock="More than one shift in a booking"><i class="fa-solid fa-layer-group"></i>Book more shifts on this seat<span class="lp-pro-badge">Pro</span></button>
                            @endif

                            @if(!in_array('3', $lbkHidden) || !in_array('6', $lbkHidden))
                            <div class="lbk-acc booking-caps-collapse-card" data-acc>
                                <button type="button" class="lbk-acc-h" aria-expanded="false" aria-controls="bookingAddonCollapse">
                                    <span class="lbk-acc-ic is-warn"><x-lb-icon name="layers" /></span>
                                    <span class="lbk-acc-t"><b>Locker &amp; discount<em>Optional</em></b><small id="lbkAddSub" hidden></small></span>
                                    <span class="lbk-acc-chev"><x-lb-icon name="chevron-down" /></span>
                                </button>
                                <div class="booking-card-collapse lbk-acc-b" id="bookingAddonCollapse" style="display: none;">
                                    <div class="booking-caps-body">
                                    <div class="row g-3">
                                        @if(!in_array('3', toggleHideField()))
                                        <div class="col-lg-4 col-6 {{ !is_locker() ? 'd-none' : '' }}">
                                            <label for="toggleFieldCheckbox2" class="form-label">Need a Locker ?</label>
                                            <select name="toggleFieldCheckbox" id="toggleFieldCheckbox2" class="form-select">
                                                <option value="no">No</option>
                                                <option value="yes">Yes, I Need a Locker</option>
                                            </select>
                                        </div>
                                        <div class="col-lg-4 col-6 {{ !is_locker() ? 'd-none' : '' }}" id="extraFieldContainer" readonly>
                                            <label for="locker_amount_book" class="form-label">Locker Amount</label>
                                            <input type="text" class="form-control digit-only" name="locker_amount" id="locker_amount_book" placeholder="Locker Amt." readonly>
                                        </div>
                                        <div class="col-lg-4 col-6 {{ !is_locker() ? 'd-none' : '' }}" id="extraFieldContainer2">
                                            <label for="locker_no" class="form-label">Locker No.</label>
                                            <input type="text" class="form-control digit-only" name="locker_no" id="locker_no" placeholder="Enter Locker No." readonly>
                                        </div>
                                        @endif
                                        @if(!in_array('6', toggleHideField()))
                                        <div class="col-lg-6">
                                            <label for="discountType" class="form-label">Discount Type</label>
                                            <select id="discountType" class="form-select" name="discountType">
                                                <option value="">Discount Type</option>
                                                <option value="amount">Amount</option>
                                                <option value="percentage">Percentage</option>
                                            </select>
                                        </div>
                                        <div class="col-lg-6">
                                            <label for="discount_amount" class="form-label">Discount Amount ( <span id="typeVal">INR / %</span> )</label>
                                            <input type="text" class="form-control digit-only" name="discount_amount" id="discount_amount" placeholder="Enter Discount Amount">
                                        </div>
                                        @endif
                                    </div>
                                </div>
                                </div>
                            </div>
                            @endif

                            {{-- price summary --}}
                            <div class="lbk-sum" id="lbkSum">
                                <div id="lbkSumLines"></div>
                                <div class="lbk-sum-t"><span>Total</span><b id="lbkSumTotal">₹0</b></div>
                            </div>

                            <div class="lbk-err" id="lbkStep1Err" hidden></div>
                        </div>

                        {{-- ===== STEP 2 · Learner details & payment ===== --}}
                        <div class="lbk-step" data-step="2">
                            <div class="lbk-recap" id="lbkRecap"></div>

                            {{-- learner photo: big, centred, above the learner card (optional) --}}
                            @if(!in_array('8', $lbkHidden))
                            <div class="lbk-photo lbk-photo--hero">
                                <div class="lbk-photo-btn" id="bookingAvatarTrigger" title="Add a photo (optional)" role="button" tabindex="0" aria-label="Add a photo (optional)">
                                    <div class="lbk-photo-ring" id="bookingAvatarCircle">
                                        <x-lb-icon name="user-round" id="bookingAvatarDefaultIcon" />
                                        <img id="bookingAvatarPreview" src="" alt="Profile Photo" style="display:none;">
                                    </div>
                                    <span class="lbk-photo-badge"><x-lb-icon name="camera" /></span>
                                </div>
                                <div class="avatar-inline-info">
                                    <b id="bookingAvatarTitle">Add photo</b>
                                    <small id="bookingAvatarSubtext">optional</small>
                                </div>
                                <input type="file" class="d-none image-cropper" name="profile_picture_image" id="profile_picture" autocomplete="off" accept=".jpeg, .jpg, .png, .webp" />
                                <img class="preview-img d-none" style="display:none !important; visibility:hidden !important; position:absolute !important;" alt="Preview">
                            </div>
                            @endif
                            {{-- learner: photo, name, mobile; email and birthday only when the branch shows them --}}
                            <section class="lbk-sec">
                                <div class="lbk-sec-h"><x-lb-icon name="user-round" /><b>Learner</b>
                                </div>
                                <div class="lbk-who">
                                    <div class="lbk-grid lbk-who-f">
                                        <div class="lbk-f">
                                            <label for="name" class="form-label">Full name <span class="required-star">*</span></label>
                                            <input type="text" class="form-control" name="name" id="name" placeholder="Learner's name" autocomplete="off">
                                        </div>
                                        <div class="lbk-f">
                                            <label for="mobile" class="form-label">Mobile <span class="required-star">*</span></label>
                                            <input type="text" inputmode="numeric" class="form-control digit-only" maxlength="10" minlength="10" name="mobile" id="mobile" placeholder="10 digits" autocomplete="off">
                                        </div>
                                    </div>
                                </div>
                                @if(!in_array('1', $lbkHidden) || !in_array('2', $lbkHidden))
                                <div class="lbk-grid lbk-grid--mt">
                                    @if(!in_array('1', $lbkHidden))
                                    <div class="lbk-f">
                                        <label for="email" class="form-label">Email <small class="lbk-optl">optional</small></label>
                                        <input type="email" class="form-control" name="email" id="email" placeholder="name@email.com" autocomplete="off">
                                        <span class="text-danger small" id="email-error"></span>
                                    </div>
                                    @endif
                                    @if(!in_array('2', $lbkHidden))
                                    <div class="lbk-f">
                                        <label for="dob" class="form-label">Birthday <small class="lbk-optl">optional</small></label>
                                        <div class="booking-date-group">
                                            <input type="date" class="form-control" name="dob" id="dob" max="<?php echo date('Y-m-d', strtotime('-5 years')); ?>">
                                            <x-lb-icon name="calendar-days" class="lbk-date-ic" />
                                        </div>
                                    </div>
                                    @endif
                                </div>
                                @endif
                            </section>

                            {{-- payment: how, how much now, when the rest is due (only when something is due), then the total --}}
                            <section class="lbk-sec">
                                <div class="lbk-sec-h"><x-lb-icon name="wallet" /><b>Payment</b></div>
                                <div class="lbk-modes" id="lbkModes" role="radiogroup" aria-label="Payment mode"></div>
                                <div class="lbk-native">
                                    <label for="payment_mode" class="form-label">Payment mode <span class="required-star">*</span></label>
                                    <select name="payment_mode" id="payment_mode" class="form-select">
                                        <option value="">Choose</option>
                                        <option value="1">Online</option>
                                        <option value="2">Offline</option>
                                        <option value="3">Pay Later</option>
                                    </select>
                                </div>

                                <div class="lbk-grid lbk-grid--mt booking-payment-card">
                                    <div class="lbk-f">
                                        <label for="paid_amount" class="form-label">Paying now (₹) <span class="required-star">*</span></label>
                                        <input id="paid_amount" inputmode="numeric" class="form-control digit-only" name="paid_amount" placeholder="0">
                                        <span id="pending_amt" class="info-hint lbk-due"></span>
                                    </div>
                                    <div class="lbk-f form-group lbk-duef">
                                        <label for="due_date" class="form-label">Rest due on <span class="required-star" id="due_date_star_booking" style="display: none;">*</span></label>
                                        <div class="booking-date-group position-relative">
                                            <input type="date" class="form-control" name="due_date" id="due_date" disabled>
                                            <x-lb-icon name="calendar-days" class="lbk-date-ic" />
                                        </div>
                                        <span class="invalid-feedback d-block booking-date-error-msg" id="due_date_error" style="display: none !important;"></span>
                                    </div>
                                </div>
                                @include('learner.partials.extra-as')

                                <div class="lbk-totalbox">
                                    <div class="lbk-pop" id="lbkPop" hidden role="dialog" aria-label="Price breakdown">
                                        <div class="lbk-pop-h">Price breakdown</div>
                                        <div id="lbkPopLines"></div>
                                        <div class="lbk-pop-t"><span>Total</span><span id="lbkPopTotal">₹0</span></div>
                                    </div>
                                    <button type="button" class="lbk-total-btn" id="lbkPopBtn" aria-expanded="false">
                                        <span><small id="lbkTotal2K">Total payable</small><b id="lbkTotal2">₹0</b></span>
                                        <span class="lbk-bd"><x-lb-icon name="receipt-text" />Breakdown</span>
                                    </button>
                                </div>
                            </section>

                            <div class="lbk-acc" data-acc>
                                <button type="button" class="lbk-acc-h" aria-expanded="false" aria-controls="lbkPrefBody">
                                    <span class="lbk-acc-ic is-ok"><x-lb-icon name="bell-ring" /></span>
                                    <span class="lbk-acc-t"><b>Reminders &amp; expiry<em>Optional</em></b><small id="lbkPrefSub">Alerts as per settings · Ends on the valid-till date</small></span>
                                    <span class="lbk-acc-chev"><x-lb-icon name="chevron-down" /></span>
                                </button>
                                <div class="lbk-acc-b" id="lbkPrefBody" style="display: none;">
                                    <div class="lbk-grid">
                                        <div class="lbk-f">
                                            <label for="sended_message_type" class="form-label">Send reminders via</label>
                                            <select id="sended_message_type" class="form-select" name="sended_message_type">
                                                <option value="">Select Type</option>
                                                @if($hasFreeWaba ?? false)
                                                <option value="whatsapp">WhatsApp Message Only</option>
                                                @endif
                                                @if($hasFreeText ?? false)
                                                <option value="text">Text Message Only</option>
                                                @endif
                                                @if(($hasFreeWaba ?? false) && ($hasFreeText ?? false))
                                                <option value="both">Both (WhatsApp & Text Message)</option>
                                                @endif
                                                <option value="no">No</option>
                                            </select>
                                        </div>
                                        <div class="lbk-f">
                                            <label for="no_expiry" class="form-label">No expiry seat</label>
                                            <select name="no_expiry" id="no_expiry" class="form-select">
                                                <option value="">Select Expiry Mode</option>
                                                <option value="1">Yes, Make it non expired seat.</option>
                                                <option value="0" selected>No</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @if(!in_array('7', $lbkHidden))
                            <div class="lbk-acc booking-caps-collapse-card" data-acc>
                                <button type="button" class="lbk-acc-h" aria-expanded="false" aria-controls="bookingOtherOptionalCollapse">
                                    <span class="lbk-acc-ic is-acc"><x-lb-icon name="id-card" /></span>
                                    <span class="lbk-acc-t"><b>More details<em>Optional</em></b><small>ID proof, address, exam, father's name, remark</small></span>
                                    <span class="lbk-acc-chev"><x-lb-icon name="chevron-down" /></span>
                                </button>
                                <div class="booking-card-collapse lbk-acc-b" id="bookingOtherOptionalCollapse" style="display: none;">
                                    <div class="booking-caps-body">
                                    <div class="row g-3">
                                        @if(!in_array('30', toggleHideField()))
                                        <div class="col-lg-6">
                                            <label for="alternate_mobile" class="form-label">Alternate Mobile No.</label>
                                            <input type="text" class="form-control digit-only" name="alternate_mobile" id="alternate_mobile" maxlength="10" minlength="10" placeholder="Enter Alternate Mobile No.">
                                        </div>
                                        @endif

                                        @if(!in_array('29', toggleHideField()))
                                        <div class="col-lg-6">
                                            <label for="father_name" class="form-label">Father Name</label>
                                            <input type="text" class="form-control char-only" name="father_name" id="father_name" placeholder="Enter Father name">
                                        </div>
                                        @endif

                                        @if(!in_array('4', toggleHideField()))
                                        <div class="col-lg-6">
                                            <label for="prepareFor" class="form-label">Prepare For</label>
                                            <select name="exam_id" id="prepareFor" class="form-select">
                                                <option value="">Learner is Prepare For Exam</option>
                                                @foreach($exams as $key => $value)
                                                <option value="{{$value->id}}">{{$value->name}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-lg-6">
                                            <label for="examDate" class="form-label">Exam Date</label>
                                            <input type="date" name="exam_date" id="examDate" class="form-control" min="{{ date('Y-m-d') }}">
                                        </div>
                                        <div class="col-lg-12">
                                            <label for="syllabus" class="form-label">Syllabus (one topic per line)</label>
                                            <textarea name="syllabus" id="syllabus" class="form-control" rows="2" maxlength="5000" placeholder="e.g. Polity, Geography, Maths"></textarea>
                                        </div>
                                        @endif

                                        @if(!in_array('5', toggleHideField()))
                                        <div class="col-12">
                                            <label class="form-label">ID document</label>
                                            <div class="lbk-idchips" id="lbkIdChips" role="radiogroup" aria-label="ID document type"></div>
                                            <div class="lbk-native">
                                                <label for="id_proof_name" class="form-label">ID Proof Name (Optional)</label>
                                                <select id="id_proof_name" class="form-select" name="id_proof_name">
                                                    <option value="">Select Id Proof</option>
                                                    <option value="1">Aadhar Card</option>
                                                    <option value="2">Driving License</option>
                                                    <option value="4">Pan Card</option>
                                                    <option value="5">Voter Id</option>
                                                    <option value="3">Other</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-lg-6">
                                            <label for="id_proof_number" class="form-label">ID Proof No.</label>
                                            <input type="text" class="form-control @error('id_proof_number') is-invalid @enderror" id="id_proof_number" name="id_proof_number" placeholder="Enter ID proof no." maxlength="12">
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label">Upload scan copy</label>
                                            <div class="doc-dropzone lbk-drop" id="bookingDocDropzone" title="Click or drag file to upload">
                                                <div class="doc-dropzone-content" id="bookingDocDropContent">
                                                    <span class="lbk-drop-ic"><x-lb-icon name="upload" /></span>
                                                    <div class="doc-dropzone-text"><b id="lbkDropTitle">Upload ID document</b></div>
                                                    <div class="doc-dropzone-hint" id="bookingDocDropHint">JPG, PNG, WEBP or PDF · up to 5 MB</div>
                                                </div>
                                                <div class="doc-dropzone-preview" id="bookingDocDropPreview" style="display:none;">
                                                    <span class="lbk-drop-ic is-ok"><x-lb-icon name="circle-check" /></span>
                                                    <div class="doc-file-info">
                                                        <span class="doc-file-name" id="bookingDocFileName">Document attached</span>
                                                        <div class="doc-file-actions-row">
                                                            <span class="doc-file-action text-danger" id="bookingRemoveDocFile" title="Change or remove file">Change file</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <input
                                                type="file"
                                                class="d-none id_proof_file image-cropper @error('id_proof') is-invalid @enderror"
                                                name="id_proof"
                                                id="id_proof_file_input"
                                                autocomplete="off"
                                                accept=".jpeg, .jpg, .png, .webp, .pdf">
                                            <img class="preview-img one d-none" style="display:none !important; visibility:hidden !important; position:absolute !important;" alt="Doc Preview">
                                            <span class="info-hint lbk-hint">Front side of the document.</span>
                                        </div>
                                        @endif

                                        @if(!in_array('32', toggleHideField()))
                                        <div class="col-lg-12">
                                            <label for="address" class="form-label">Full address</label>
                                            <textarea class="form-control" name="address" id="address" rows="3" placeholder="House no., street, area, city, PIN code"></textarea>
                                        </div>
                                        @endif
                                        @if(!in_array('31', toggleHideField()))
                                        <div class="col-lg-12">
                                            <label for="remark" class="form-label">Remark</label>
                                            <textarea class="form-control" name="remark" id="remark" rows="3" placeholder="Anything to remember about this learner"></textarea>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                                </div>
                            </div>
                            @endif

                            <div class="lbk-err" id="lbkStep2Err" hidden></div>
                        </div>

                    </div>
                </div>

                {{-- Footer: step 1 → Continue · total;  step 2 → back + Book seat · total --}}
                <div class="modal-footer booking-modal-footer lbk-foot">
                    <button type="button" class="lbk-back" id="lbkBack" aria-label="Back to seat & shift"><x-lb-icon name="arrow-left" /><span>Back</span></button>
                    <div class="lbk-total"><small id="lbkTotalK">Total payable</small><b id="lbkTotal">₹0</b></div>
                    <button type="button" class="lp-btn lbk-next" id="lbkNext"><span>Continue</span><em class="lbk-btn-amt" id="lbkNextAmt"></em><x-lb-icon name="arrow-right" /></button>
                    <button type="submit" class="lp-btn btn-book-seat lbk-submit" id="bookSeatSubmitBtn"><span>Book seat</span><em class="lbk-btn-amt" id="lbkBookAmt"></em><x-lb-icon name="check" /></button>
                    <button type="button" class="btn-cancel-booking d-none" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // 1. Profile Picture Avatar Upload Trigger & Live Preview
    $('#bookingAvatarTrigger').on('click', function(e) {
        e.preventDefault();
        $('#profile_picture').trigger('click');
    });

    $('#profile_picture').on('change', function() {
        const file = this.files && this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#bookingAvatarPreview').attr('src', e.target.result).show();
                $('#bookingAvatarDefaultIcon').hide();
                $('#bookingAvatarTitle').text('Photo selected');
                $('#bookingAvatarSubtext').text('Click avatar to change.');
            };
            reader.readAsDataURL(file);
        }
    });

    // Observer for Cropper image changes if cropper updates sibling preview-img
    const cropperSibling = document.querySelector('#profile_picture ~ .preview-img');
    if (cropperSibling) {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'src') {
                    const src = cropperSibling.getAttribute('src') || cropperSibling.src;
                    if (src && src !== window.location.href) {
                        $('#bookingAvatarPreview').attr('src', src).show();
                        $('#bookingAvatarDefaultIcon').hide();
                        $('#bookingAvatarTitle').text('Photo uploaded');
                    }
                }
            });
        });
        observer.observe(cropperSibling, { attributes: true, attributeFilter: ['src'] });
    }

    // 2. Document Dropzone Upload & Live Preview
    $('#bookingDocDropzone').on('click', function(e) {
        if ($(e.target).closest('#bookingRemoveDocFile').length) return;
        $('#id_proof_file_input').trigger('click');
    });

    $('#id_proof_file_input').on('change', function() {
        const file = this.files && this.files[0];
        if (file) {
            $('#bookingDocFileName').text(file.name);
            $('#bookingDocDropContent').hide();
            $('#bookingDocDropPreview').css('display', 'flex');
        }
    });

    $('#bookingRemoveDocFile').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $('#id_proof_file_input').val('');
        $('#bookingDocDropContent').show();
        $('#bookingDocDropPreview').hide();
        $('.preview-img.one').removeAttr('src');
    });

    // Observer for ID Proof Cropper
    const docSibling = document.querySelector('#id_proof_file_input ~ .preview-img.one');
    if (docSibling) {
        const docObserver = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'src') {
                    const src = docSibling.getAttribute('src') || docSibling.src;
                    if (src && src !== window.location.href) {
                        $('#bookingDocFileName').text('Document Attached');
                        $('#bookingDocDropContent').hide();
                        $('#bookingDocDropPreview').css('display', 'flex');
                    }
                }
            });
        });
        docObserver.observe(docSibling, { attributes: true, attributeFilter: ['src'] });
    }

    // 3. Collapsible header toggle handler
    $(document).on('click', '.booking-modal-module .booking-collapsible-header', function(e) {
        if ($(e.target).is('input, select, textarea, button, a, label')) return;
        e.preventDefault();
        e.stopPropagation();

        const $header = $(this);
        const target = $header.data('target');
        const $body = $(target);
        const $toggle = $header.find('.edit-header-toggle');

        if ($body.is(':visible')) {
            $toggle.removeClass('expanded');
            $body.stop(true, true).slideUp(200);
        } else {
            $toggle.addClass('expanded');
            $body.stop(true, true).slideDown(200);
        }
    });

    // Keyboard support for collapsible headers (Enter / Space)
    $(document).on('keydown', '.booking-modal-module .booking-collapsible-header', function(e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            $(this).trigger('click');
        }
    });

    // Reset form, validation states, and previews on modal close
    $('#seatAllotmentModal').on('hidden.bs.modal', function() {
        // Reset raw form fields
        if ($('#seatAllotmentForm').length && $('#seatAllotmentForm')[0]) {
            $('#seatAllotmentForm')[0].reset();
        }
        $('#seatAllotmentForm .is-invalid').removeClass('is-invalid');
        $('#seatAllotmentForm .invalid-feedback').remove();
        $('#error-message, #validation-error-message').hide().text('');

        // Reset Profile Avatar
        $('#profile_picture').val('');
        $('#bookingAvatarPreview').attr('src', '').hide();
        $('#bookingAvatarDefaultIcon').show();
        $('#bookingAvatarTitle').text('Profile Photo');
        $('#bookingAvatarSubtext').text('Tap the avatar to upload');

        // Reset ID Proof dropzone
        $('#id_proof_file_input').val('');
        $('#bookingDocDropContent').show();
        $('#bookingDocDropPreview').hide();
        $('#bookingDocFileName').text('Document Attached');
        $('.preview-img.one').removeAttr('src');

        // Reset seat fields
        $('#seat_no').val('');
        $('#seat_id').val('').prop('disabled', true);
        $('#general_seat').val('yes');

        // Reset collapsible states
        $('#bookingAddonCollapse').hide();
        $('.booking-collapsible-header[data-target="#bookingAddonCollapse"] .edit-header-toggle').removeClass('expanded');
        $('#bookingOtherOptionalCollapse').hide();
        $('.booking-collapsible-header[data-target="#bookingOtherOptionalCollapse"] .edit-header-toggle').removeClass('expanded');
    });


    // Booking drawer steps, seat / plan / shift pickers, totals: public/js/lb-booking.js

    // Reset Renew modal on close
    $('#seatAllotmentModal3').on('hidden.bs.modal', function() {
        if ($('#upgradeForm').length && $('#upgradeForm')[0]) {
            $('#upgradeForm')[0].reset();
        }
        $('#upgradeForm .is-invalid').removeClass('is-invalid');
        $('#upgradeForm .invalid-feedback').remove();
        $('#seatAllotmentModal3 .alert').hide().text('');
        $('#renewAddonCollapse').hide();
        $('.booking-collapsible-header[data-target="#renewAddonCollapse"] .edit-header-toggle').removeClass('expanded');
    });
});
</script>
<script src="{{ asset('public/js/lb-booking.js') }}?v={{ @filemtime(public_path('js/lb-booking.js')) }}" defer></script>
<link rel="stylesheet" href="{{ asset('public/css/lb-renew.css') }}?v={{ @filemtime(public_path('css/lb-renew.css')) }}" />
{{-- the library's running global offer fills the discount fields of new booking and renew (docs/GLOBAL_DISCOUNT.md) --}}
@include('partials.global-offer', ['forms' => [
    ['form' => '#seatAllotmentForm', 'price' => '#plan_price_id', 'type' => '#discountType', 'value' => '#discount_amount', 'typeName' => 'discountType', 'valueName' => 'discount_amount', 'locker' => '#locker_amount_book', 'lockerToggle' => '#toggleFieldCheckbox2', 'anchor' => '#lbkSumLines'],
    ['form' => '#upgradeForm', 'price' => '#plan_price_id2', 'type' => '#discount_type', 'value' => '#discount_amount3', 'typeName' => 'discountType', 'valueName' => 'discount_amount', 'locker' => '#locker_amount2', 'lockerToggle' => '#locker'],
]])
@endcan



@can('has-permission', 'Renew Seat')
{{-- Renew popup (panel theme). Same form, ids and names as before: #upgradeForm, plan_id2,
     plan_type_id_renew, plan_price_id2, locker, locker_amount2, locker_no2, discount_type,
     discount_amount3, previous_pending, new_plan_price2 (paid_amount), payment_mode, due_date2, no_expiry.
     Total payable = plan price + locker − discount + the learner's earlier dues (#previous_pending). --}}
<div class="modal fade booking-modal-module lrn" id="seatAllotmentModal3" tabindex="-1" data-bs-focus="false" aria-labelledby="seat_number_upgrades" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered lrn-dialog">
        <div class="modal-content lrn-content">
            <div class="lrn-head">
                <span class="lrn-head-ic"><x-lb-icon name="refresh-cw" /></span>
                <div class="lrn-head-t">
                    <h2 id="seat_number_upgrades">Renew Plan</h2>
                    <p>The new plan starts the day after the current one ends.</p>
                </div>
                <button type="button" class="lrn-x" data-bs-dismiss="modal" aria-label="Close"><x-lb-icon name="x" /></button>
            </div>
            <form id="upgradeForm">
                @csrf
                <div class="modal-body lrn-body">
                    <div class="alert alert-success success-message lrn-alert" style="display:none;"></div>
                    <div class="alert alert-danger error-message lrn-alert" style="display:none;"></div>
                    <div class="detailes">
                        <input type="hidden" id="hidden_plan">

                        {{-- learner: photo, name, seat, UID, plan status, then plan / shift / end date (filled by lrnWho()) --}}
                        <div class="lrn-who">
                            <div class="lrn-who-top">
                                <span class="lrn-ava" data-lrn="ava">L</span>
                                <div class="lrn-who-t">
                                    <div class="lrn-who-n"><b id="learner_name">NA</b><span class="lrn-pill is-seat" data-lrn="seat"><x-lb-icon name="armchair" /><span>Seat</span></span></div>
                                    <div class="lrn-tags">
                                        <span class="lrn-pill is-uid">UID: <span id="learner_uid" class="text-uppercase">NA</span></span>
                                        <span class="lrn-pill is-ok" data-lrn="status" hidden><x-lb-icon name="circle-check" /><span></span></span>
                                        <span class="lrn-pill is-mut"><x-lb-icon name="phone" /><span id="learner_mobilepop">NA</span></span>
                                    </div>
                                </div>
                            </div>
                            <div class="lrn-chips">
                                <span class="lrn-chip"><x-lb-icon name="calendar-days" /><span data-lrn="planname">—</span></span>
                                <span class="lrn-chip"><x-lb-icon name="clock" /><span data-lrn="shift">—</span></span>
                                <span class="lrn-chip" data-lrn-row="till" hidden><x-lb-icon name="calendar-check" /><span data-lrn="till"></span></span>
                            </div>
                        </div>

                        {{-- the renewal keeps the plan and shift: the fields stay in the form, the card above shows them --}}
                        <div class="lrn-planfields" hidden>
                            <select id="plan_id2" class="form-control" name="plan_id" @readonly(true)></select>
                            <select id="plan_type_id_renew" class="form-control" name="plan_type_id" @readonly(true)></select>
                            <input id="plan_price_id2" class="form-control" placeholder="Plan Price" name="plan_price_id" readonly>
                        </div>
                        <span id="chargeable_days_renew" class="lrn-hint"></span>

                        {{-- add-ons --}}
                        @if(!in_array('3', toggleHideField()) || !in_array('6', toggleHideField()))
                        <section class="lrn-card lrn-card--fold">
                            <div class="lrn-fold booking-collapsible-header" data-target="#renewAddonCollapse" role="button" tabindex="0">
                                <h3>Locker &amp; discount <span>Optional</span></h3>
                                <x-lb-icon name="chevron-down" class="edit-header-toggle" />
                            </div>
                            <div class="booking-card-collapse" id="renewAddonCollapse" style="display: none;">
                                <div class="lrn-grid">
                                    @if(!in_array('3', toggleHideField()))
                                    <div class="lrn-f {{ !is_locker() ? 'd-none' : '' }}">
                                        <label for="locker" class="form-label">Locker</label>
                                        <select name="locker" id="locker" class="form-select">
                                            <option value="no">No locker</option>
                                            <option value="yes">Yes, add a locker</option>
                                        </select>
                                    </div>
                                    <div class="lrn-f {{ !is_locker() ? 'd-none' : '' }}">
                                        <label for="locker_amount2" class="form-label">Locker fee</label>
                                        <input type="text" class="form-control @error('locker_amount') is-invalid @enderror" name="locker_amount" id="locker_amount2" readonly>
                                    </div>
                                    <div class="lrn-f {{ !is_locker() ? 'd-none' : '' }}" id="extraFieldContainer2">
                                        <label for="locker_no2" class="form-label">Locker no.</label>
                                        <input type="text" class="form-control digit-only" name="locker_no" id="locker_no2" placeholder="e.g. 12" readonly>
                                    </div>
                                    @endif
                                    @if(!in_array('6', toggleHideField()))
                                    <div class="lrn-f">
                                        <label for="discount_type" class="form-label">Discount</label>
                                        <select id="discount_type" class="form-select" name="discountType">
                                            <option value="">No discount</option>
                                            <option value="amount">Flat amount (₹)</option>
                                            <option value="percentage">Percent (%)</option>
                                        </select>
                                    </div>
                                    <div class="lrn-f">
                                        <label for="discount_amount3" class="form-label">Discount (<span id="typeVal2">₹ / %</span>)</label>
                                        <input type="text" class="form-control @error('discount_amount') is-invalid @enderror" name="discount_amount" id="discount_amount3" placeholder="0" value="">
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </section>
                        @endif

                        {{-- payment: the bill, then what is paid now --}}
                        <section class="lrn-card">
                            <h3>Payment</h3>
                            <div class="lrn-bill" aria-live="polite">
                                <div><span>Plan price</span><b data-lrn="plan">₹0</b></div>
                                <div data-lrn-row="locker" hidden><span>Locker</span><b data-lrn="locker">+ ₹0</b></div>
                                <div data-lrn-row="disc" hidden class="is-ok"><span>Discount</span><b data-lrn="disc">− ₹0</b></div>
                                <div data-lrn-row="prev" hidden class="is-due"><span>Earlier due <small>not paid yet</small></span><b data-lrn="prev">+ ₹0</b></div>
                                <div data-lrn-row="adv" hidden class="is-ok"><span>Advance used <small>paid earlier</small></span><b data-lrn="adv">− ₹0</b></div>
                                <div class="is-total"><span>Total payable</span><b data-lrn="total">₹0</b></div>
                            </div>
                            {{-- earlier dues: added to the total, read by the page scripts --}}
                            <input type="hidden" class="@error('previous_pending') is-invalid @enderror" name="previous_pending" id="previous_pending">
                            {{-- the learner's advance (extra paid earlier): on = it pays this bill (docs/LEARNER_ADVANCE.md) --}}
                            <input type="hidden" id="advance_balance" value="0">
                            <label class="lb-adv" id="renewAdvance" hidden>
                                <input type="hidden" name="use_advance" value="0">
                                <input type="checkbox" name="use_advance" value="1" id="use_advance_renew" checked>
                                <span class="lb-adv-t"><b>Use advance</b><small><span data-adv="bal">₹0</span> paid earlier by this learner</small></span>
                                <em data-adv="use">− ₹0</em>
                            </label>
                            @include('learner.partials.extra-as')
                            <div class="lrn-grid lrn-grid--2">
                                <div class="lrn-f">
                                    <label for="new_plan_price2" class="form-label">Paying now (₹) <span class="required-star">*</span></label>
                                    <input type="text" inputmode="numeric" class="form-control @error('paid_amount') is-invalid @enderror" name="paid_amount" id="new_plan_price2" placeholder="0" value="">
                                    <span id="pending_amt2" class="lrn-due"></span>
                                </div>
                                <div class="lrn-f">
                                    <label for="payment_mode" class="form-label">Payment mode <span class="required-star">*</span></label>
                                    <select name="payment_mode" id="payment_mode" class="form-select">
                                        <option value="">Select payment mode</option>
                                        <option value="1">Online</option>
                                        <option value="2">Offline</option>
                                        <option value="3">Pay Later</option>
                                    </select>
                                </div>
                                <div class="lrn-f form-group">
                                    <label for="due_date2" class="form-label">Due date <span class="required-star" id="due_date_star_renew" style="display: none;">*</span></label>
                                    <div class="booking-date-group position-relative">
                                        <input type="date" class="form-control" name="due_date" id="due_date2" disabled>
                                    </div>
                                    <span class="invalid-feedback d-block booking-date-error-msg" id="due_date2_error" style="display: none !important;"></span>
                                </div>
                                <div class="lrn-f">
                                    <label for="no_expiry_renew" class="form-label">Seat expiry</label>
                                    <select name="no_expiry" id="no_expiry_renew" class="form-select">
                                        <option value="0">Ends on the plan end date</option>
                                        <option value="1">Never expires (non-expiring seat)</option>
                                    </select>
                                </div>
                            </div>
                        </section>

                        <input type="hidden" class="form-control" name="seat_no" value="" id="update_seat_no">
                        <input type="hidden" class="form-control" name="learner_id" value="" id="update_user_id">
                        {{-- extra shifts of a multi-shift booking; the plan price is the booking total (docs/ADVANCED_SHIFTS.md) --}}
                        <input type="hidden" name="slot_prices_included" value="1">
                        <div id="renewCarryShifts"></div>
                    </div>
                </div>

                <div class="modal-footer booking-modal-footer lrn-foot">
                    <div class="lrn-foot-t"><small data-lrn="footK">Paying now</small><b data-lrn="footV">₹0</b></div>
                    <button type="button" class="lrn-btn" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="lrn-btn is-primary btn-book-seat" id="renewSeatSubmitBtn"><x-lb-icon name="refresh-cw" />Renew plan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan

<style>
    .reminder-choice-grid { display: flex; gap: 16px; }
    .reminder-choice-btn { flex: 1; border: 1px solid #e4e7ed; border-radius: 10px; background: #fff; padding: 22px 14px; display: flex; flex-direction: column; align-items: center; gap: 8px; cursor: pointer; transition: .15s ease; text-decoration: none; color: inherit; }
    .reminder-choice-btn:hover { border-color: #07156f; box-shadow: 0 4px 14px rgba(0,0,0,.08); transform: translateY(-2px); }
    .reminder-choice-icon { width: 54px; height: 54px; border-radius: 50%; display: grid; place-items: center; font-size: 1.5rem; }
    .reminder-choice-icon.whatsapp { background: #e7f9ee; color: #25D366; }
    .reminder-choice-icon.text { background: #e8f0ff; color: #0d6efd; }
    .reminder-choice-label { font-weight: 700; color: #222; font-size: 1rem; }
    .reminder-choice-sub { color: #777; font-size: .8rem; text-align: center; }
</style>

<div class="modal fade" id="sendReminderChooserModal" tabindex="-1" aria-labelledby="sendReminderChooserLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title px-2 fs-5" id="sendReminderChooserLabel">Send Reminder</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="reminder-choice-grid">
                    <button type="button" class="reminder-choice-btn open-waba chooser-action" data-bs-dismiss="modal" data-target-modal="#wabaSendModel">
                        <span class="reminder-choice-icon whatsapp"><i class="fab fa-whatsapp"></i></span>
                        <span class="reminder-choice-label">WhatsApp</span>
                        <span class="reminder-choice-sub">Send reminder via WhatsApp</span>
                    </button>
                    <button type="button" class="reminder-choice-btn open-text chooser-action" data-bs-dismiss="modal" data-target-modal="#textSendModel">
                        <span class="reminder-choice-icon text"><i class="fa-solid fa-message"></i></span>
                        <span class="reminder-choice-label">Text</span>
                        <span class="reminder-choice-sub">Send reminder via SMS text</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="sendReminderChooserFreeModal" tabindex="-1" aria-labelledby="sendReminderChooserFreeLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title px-2 fs-5" id="sendReminderChooserFreeLabel">Send Reminder</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="reminder-choice-grid">
                    <a href="javascript:;" id="freeWabaChoiceLink" target="_blank" class="reminder-choice-btn" data-bs-dismiss="modal">
                        <span class="reminder-choice-icon whatsapp"><i class="fab fa-whatsapp"></i></span>
                        <span class="reminder-choice-label">WhatsApp</span>
                        <span class="reminder-choice-sub">Send reminder via WhatsApp</span>
                    </a>
                    <a href="javascript:;" id="freeTextChoiceLink" class="reminder-choice-btn" data-bs-dismiss="modal">
                        <span class="reminder-choice-icon text"><i class="fa-solid fa-message"></i></span>
                        <span class="reminder-choice-label">Text</span>
                        <span class="reminder-choice-sub">Send reminder via SMS text</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="wabaSendModel" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">

        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title px-2 fs-5">Send WhatsApp Reminder</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div id="error-message" class="alert alert-danger mb-4 mt-0" style="display:none;"></div>
                <div id="validation-error-message" class="alert alert-danger mb-4 mt-0" style="display:none;"></div>
                <form id="notification_menual">
                    @csrf
                    <div class="detailes">
                        <div class="row g-3">
                            <div class="col-lg-12">
                                <label for="">Operation Name<span>*</span></label>
                                <select id="waba_template_select" class="form-select" name="template_id">
                                    <option value="">Select Template</option>
                                    @if(isset($wabaTemplates) && !empty($wabaTemplates))
                                    @foreach($wabaTemplates as $t)
                                    <option value="{{ $t->id }}">
                                        {{ $t->operation_name }} - {{ $t->template_name }}
                                    </option>
                                    @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="col-lg-12">
                                <label for="">Message Content<span>*</span></label>
                                <textarea id="waba_final_message" name="message" class="form-control mt-2" rows="5"></textarea>
                            </div>
                            <div class="col-lg-12">
                                <label>Choose Mobile No. <span class="text-danger">*</span></label>
                                <select id="learner_mobile_select" class="form-select" name="mobileNo">
                                    <option value="">Select Mobile</option>
                                </select>
                            </div>

                            <input type="hidden" id="modal_learner_id" name="learner_id">

                        </div>
                        <div class="row mt-4">
                            <div class="col-lg-12">
                                <input id="sendWabaMessage" class="btn btn-primary btn-block button" value="Send WhatsApp Message" autocomplete="off">
                            </div>
                        </div>

                    </div>
                </form>
            </div>

        </div>
    </div>
</div>
<div class="modal fade" id="textSendModel" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">

        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title px-2 fs-5">Send Text Reminder</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                {{-- <div id="error-message" class="alert alert-danger mb-4 mt-0" style="display:none;"></div>
                <div id="validation-error-message" class="alert alert-danger mb-4 mt-0" style="display:none;"></div> --}}
                <form>
                    @csrf
                    <div class="detailes">
                        <div class="row g-3">
                            <div class="col-lg-12">
                                <label for="">Operation Name<span>*</span></label>
                                <select id="text_template_select" class="form-select" name="template_id">
                                    <option value="">Select Template</option>
                                    @if(isset($textTemplates) && !empty($textTemplates))
                                    @foreach($textTemplates as $t)
                                    <option value="{{ $t->id }}">
                                        {{ $t->operation_name }} - {{ $t->template_name }}
                                    </option>
                                    @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="col-lg-12">
                                <label for="">Message Content<span>*</span></label>
                                <textarea id="text_final_message" name="message" class="form-control mt-2" rows="5"></textarea>
                            </div>
                            <div class="col-lg-12">
                                <label>Choose Mobile No. <span class="text-danger">*</span></label>
                                <select id="learner_mobile_select2" class="form-select" name="mobileNo">
                                    <option value="">Select Mobile</option>
                                </select>
                            </div>

                            <input type="hidden" id="modal_learner_id2" name="learner_id">

                        </div>
                        <div class="row mt-4">
                            <div class="col-lg-12">
                                <input id="sendTextMessage" class="btn btn-primary btn-block button" value="Send Text Message" autocomplete="off">
                            </div>
                        </div>

                    </div>
                </form>
            </div>

        </div>
    </div>
</div>
