{{--
    One modal per master. Every form posts to master.store via AJAX (custom-management.js);
    `data-title-add` / `data-title-edit` switch the heading when a row is edited.
--}}
@php
    $operatingHours = (int) ($branchHour->hour ?? 0);
@endphp

{{-- Floor --}}
<div class="modal fade cm-modal" id="cmModal-floor" tabindex="-1" aria-labelledby="cmModalTitle-floor" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content cm-form" novalidate data-title-add="Add Floor" data-title-edit="Edit Floor">
            <div class="modal-header">
                <span class="cm-modal-icon tone-teal" aria-hidden="true"><i class="fa-solid fa-layer-group"></i></span>
                <h5 class="modal-title" id="cmModalTitle-floor">Add Floor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="cm-form-alert alert alert-danger d-none" role="alert"></div>
                <input type="hidden" name="id">
                <input type="hidden" name="databasemodel" value="Floor">
                <input type="hidden" name="branch_id" value="{{ $branchId }}">
                <div class="row g-3">
                    <div class="col-sm-4">
                        <label class="form-label" for="cmFloorNo">Floor No. <span class="text-danger">*</span></label>
                        <input type="text" inputmode="numeric" class="form-control cm-digits" name="floor_no" id="cmFloorNo" maxlength="3" placeholder="e.g. 1" required>
                    </div>
                    <div class="col-sm-8">
                        <label class="form-label" for="cmFloorName">Floor Name</label>
                        <input type="text" class="form-control" name="name" id="cmFloorName" maxlength="255" placeholder="e.g. First Floor">
                    </div>
                    <div class="col-4">
                        <label class="form-label" for="cmFloorFrom">From Seat <span class="text-danger">*</span></label>
                        <input type="text" inputmode="numeric" class="form-control cm-digits" name="from_seat" id="cmFloorFrom" maxlength="5" required>
                    </div>
                    <div class="col-4">
                        <label class="form-label" for="cmFloorTo">To Seat <span class="text-danger">*</span></label>
                        <input type="text" inputmode="numeric" class="form-control cm-digits" name="to_seat" id="cmFloorTo" maxlength="5" required>
                    </div>
                    <div class="col-4">
                        <label class="form-label" for="cmFloorTotal">Total Seats</label>
                        <input type="text" class="form-control" id="cmFloorTotal" readonly tabindex="-1">
                    </div>
                </div>
                <p class="cm-help mt-3 mb-0">Branch seats: <strong>{{ (int) ($branchHour->seats ?? 0) }}</strong>. All floors together can not exceed this.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn cm-btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn cm-submit"><i class="fa-solid fa-floppy-disk"></i> Save Floor</button>
            </div>
        </form>
    </div>
</div>

{{-- Plan --}}
<div class="modal fade cm-modal" id="cmModal-plan" tabindex="-1" aria-labelledby="cmModalTitle-plan" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content cm-form" novalidate data-title-add="Add Plan" data-title-edit="Edit Plan">
            <div class="modal-header">
                <span class="cm-modal-icon tone-navy" aria-hidden="true"><i class="fa-solid fa-calendar-days"></i></span>
                <h5 class="modal-title" id="cmModalTitle-plan">Add Plan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="cm-form-alert alert alert-danger d-none" role="alert"></div>
                <input type="hidden" name="id">
                <input type="hidden" name="databasemodel" value="Plan">
                <input type="hidden" name="library_id" value="{{ getLibraryId() }}">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <label class="form-label" for="cmPlanType">Duration Type <span class="text-danger">*</span></label>
                        <select class="form-select" name="type" id="cmPlanType" required>
                            <option value="">Select type</option>
                            <option value="DAY">Day</option>
                            <option value="WEEK">Week</option>
                            <option value="MONTH">Month</option>
                            <option value="YEAR">Year</option>
                        </select>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="cmPlanNumber">Duration <span class="text-danger">*</span></label>
                        <input type="text" inputmode="numeric" class="form-control cm-digits" name="plan_id" id="cmPlanNumber" maxlength="3" placeholder="e.g. 1 for 1 Month" required>
                    </div>
                    <div class="col-12 d-none" id="cmPlanDaysWrap">
                        <label class="form-label" for="cmPlanDays">Billing Days</label>
                        <select class="form-select" name="monthdays" id="cmPlanDays"></select>
                        <p class="cm-help mt-1 mb-0">Calendar wise follows the actual month length.</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn cm-btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn cm-submit"><i class="fa-solid fa-floppy-disk"></i> Save Plan</button>
            </div>
        </form>
    </div>
</div>

{{-- Shift (Plan Type) --}}
<div class="modal fade cm-modal" id="cmModal-shift" tabindex="-1" aria-labelledby="cmModalTitle-shift" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content cm-form" novalidate data-title-add="Add Shift" data-title-edit="Edit Shift">
            <div class="modal-header">
                <span class="cm-modal-icon tone-amber" aria-hidden="true"><i class="fa-solid fa-clock"></i></span>
                <h5 class="modal-title" id="cmModalTitle-shift">Add Shift</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="cm-form-alert alert alert-danger d-none" role="alert"></div>
                <input type="hidden" name="id">
                <input type="hidden" name="databasemodel" value="PlanType">
                <input type="hidden" name="library_id" value="{{ getLibraryId() }}">
                <input type="hidden" name="branch_id" value="{{ $branchId }}">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label" for="cmShiftType">Shift <span class="text-danger">*</span></label>
                        <select class="form-select" name="day_type_id" id="cmShiftType" required>
                            <option value="">Select shift</option>
                            @can('has-permission', 'Full Day')<option value="1">Full Day</option>@endcan
                            @can('has-permission', 'First Half')<option value="2">First Half</option>@endcan
                            @can('has-permission', 'Second Half')<option value="3">Second Half</option>@endcan
                            @can('has-permission', 'Hourly Slot 1')<option value="4">Hourly Slot 1</option>@endcan
                            @can('has-permission', 'Hourly Slot 2')<option value="5">Hourly Slot 2</option>@endcan
                            @can('has-permission', 'Hourly Slot 3')<option value="6">Hourly Slot 3</option>@endcan
                            @can('has-permission', 'Hourly Slot 4')<option value="7">Hourly Slot 4</option>@endcan
                            @if($operatingHours === 24)
                                @can('has-permission', 'All Day')<option value="8">All Day</option>@endcan
                                @can('has-permission', 'Full Night')<option value="9">Full Night</option>@endcan
                            @endif
                            <option value="10">Reserved</option>
                            <option value="11">VIP</option>
                            @can('has-permission', 'Custom Plan')<option value="0">Custom</option>@endcan
                        </select>
                    </div>
                    <div class="col-12 d-none" id="cmShiftCustomWrap">
                        <label class="form-label" for="cmShiftCustom">Custom Shift Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="custom_plan_type" id="cmShiftCustom" maxlength="100" placeholder="e.g. Morning Batch">
                    </div>
                    <div class="col-12 d-none" id="cmShiftModeWrap">
                        <label class="form-label" for="cmShiftMode">Booking Type</label>
                        <select class="form-select" name="booking_mode" id="cmShiftMode">
                            <option value="fixed">Fixed timing</option>
                            <option value="duration">Fixed hours · start time chosen at booking</option>
                            <option value="free">Free · timing and price chosen at booking</option>
                            <option value="flexible">Flexible · any time within the range, general seat</option>
                        </select>
                        <small class="cm-help d-none" id="cmShiftModeHelp" data-seat="Start and end below are the time range this shift can be booked in. Numbered seats only." data-general="Learner can come any time between start and end below and stay for the shift hours. General seats only.">Start and end below are the time range this shift can be booked in. Numbered seats only.</small>
                    </div>
                    <div class="col-12 d-none" id="cmShiftDurationWrap">
                        <label class="form-label" for="cmShiftDuration">Shift Hours <span class="text-danger">*</span></label>
                        <input type="text" inputmode="decimal" class="form-control cm-decimal" name="duration_hours" id="cmShiftDuration" maxlength="5" placeholder="e.g. 4">
                    </div>
                    <div class="col-4">
                        <label class="form-label" for="cmShiftStart">Start Time <span class="text-danger">*</span></label>
                        <input type="text" class="form-control cm-time" name="start_time" id="cmShiftStart" data-lb-time required>
                    </div>
                    <div class="col-4">
                        <label class="form-label" for="cmShiftEnd">End Time <span class="text-danger">*</span></label>
                        <input type="text" class="form-control cm-time" name="end_time" id="cmShiftEnd" data-lb-time required>
                    </div>
                    <div class="col-4">
                        <label class="form-label" for="cmShiftHours">Duration (Hrs)</label>
                        <input type="text" class="form-control" name="slot_hours" id="cmShiftHours" readonly tabindex="-1">
                    </div>
                </div>
                <p class="cm-help mt-3 mb-0">Operating hours: <strong>{{ $operatingHours }} hrs</strong>. Reserved and VIP shifts must cover the full operating hours.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn cm-btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn cm-submit"><i class="fa-solid fa-floppy-disk"></i> Save Shift</button>
            </div>
        </form>
    </div>
</div>

{{-- Plan Price --}}
<div class="modal fade cm-modal" id="cmModal-price" tabindex="-1" aria-labelledby="cmModalTitle-price" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content cm-form" novalidate data-title-add="Add Plan Price" data-title-edit="Edit Plan Price">
            <div class="modal-header">
                <span class="cm-modal-icon tone-green" aria-hidden="true"><i class="fa-solid fa-indian-rupee-sign"></i></span>
                <h5 class="modal-title" id="cmModalTitle-price">Add Plan Price</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="cm-form-alert alert alert-danger d-none" role="alert"></div>
                <input type="hidden" name="id">
                <input type="hidden" name="databasemodel" value="PlanPrice">
                <input type="hidden" name="library_id" value="{{ getLibraryId() }}">
                <input type="hidden" name="branch_id" value="{{ $branchId }}">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <label class="form-label" for="cmPricePlan">Plan <span class="text-danger">*</span></label>
                        <select class="form-select" name="plan_id" id="cmPricePlan" required>
                            <option value="">Select plan</option>
                            @foreach($cmPlans as $plan)
                                {{-- Inactive plans stay selectable only so an existing price can still be edited. --}}
                                <option value="{{ $plan->id }}" @if($plan->deleted_at) data-inactive="1" @endif>{{ $plan->name }}{{ $plan->deleted_at ? ' (Inactive)' : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="cmPriceShift">Shift <span class="text-danger">*</span></label>
                        <select class="form-select" name="plan_type_id" id="cmPriceShift" required>
                            <option value="">Select shift</option>
                            @foreach($cmPlanTypes as $shift)
                                <option value="{{ $shift->id }}" data-day-type="{{ $shift->day_type_id }}" @if($shift->deleted_at) data-inactive="1" @endif>{{ $shift->name }}{{ $shift->deleted_at ? ' (Inactive)' : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="cmPriceAmount">Price (₹) <span class="text-danger">*</span></label>
                        <input type="text" inputmode="decimal" class="form-control cm-decimal" name="price" id="cmPriceAmount" maxlength="10" placeholder="Enter price" required>
                        <p class="cm-help mt-1 mb-0" id="cmPriceHint">Suggested from your 1 Month price; you can change it.</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn cm-btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn cm-submit"><i class="fa-solid fa-floppy-disk"></i> Save Price</button>
            </div>
        </form>
    </div>
</div>

{{-- Expense & Exam share the same single-field layout. --}}
@foreach([
    ['key' => 'expense', 'model' => 'Expense', 'tone' => 'rose', 'icon' => 'fa-wallet', 'label' => 'Expense Name', 'placeholder' => 'e.g. Electricity Bill', 'max' => 191],
    ['key' => 'exam', 'model' => 'Exam', 'tone' => 'indigo', 'icon' => 'fa-graduation-cap', 'label' => 'Exam Name', 'placeholder' => 'e.g. UPSC', 'max' => 255],
] as $single)
<div class="modal fade cm-modal" id="cmModal-{{ $single['key'] }}" tabindex="-1" aria-labelledby="cmModalTitle-{{ $single['key'] }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content cm-form" novalidate data-title-add="Add {{ $single['model'] }}" data-title-edit="Edit {{ $single['model'] }}">
            <div class="modal-header">
                <span class="cm-modal-icon tone-{{ $single['tone'] }}" aria-hidden="true"><i class="fa-solid {{ $single['icon'] }}"></i></span>
                <h5 class="modal-title" id="cmModalTitle-{{ $single['key'] }}">Add {{ $single['model'] }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="cm-form-alert alert alert-danger d-none" role="alert"></div>
                <input type="hidden" name="id">
                <input type="hidden" name="databasemodel" value="{{ $single['model'] }}">
                <label class="form-label" for="cmName-{{ $single['key'] }}">{{ $single['label'] }} <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="name" id="cmName-{{ $single['key'] }}" maxlength="{{ $single['max'] }}" placeholder="{{ $single['placeholder'] }}" required>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn cm-btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn cm-submit"><i class="fa-solid fa-floppy-disk"></i> Save {{ $single['model'] }}</button>
            </div>
        </form>
    </div>
</div>
@endforeach

{{-- Confirm sheet for activate / deactivate / delete. custom-management.js fills the text and tone. --}}
<div class="modal fade cm-modal cm-confirm" id="cmModal-confirm" tabindex="-1" aria-labelledby="cmConfirmTitle" aria-describedby="cmConfirmText" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body">
                <span class="cm-confirm-icon" aria-hidden="true"><i class="fa-solid fa-trash"></i></span>
                <h5 class="cm-confirm-title" id="cmConfirmTitle">Delete?</h5>
                <p class="cm-confirm-text" id="cmConfirmText"></p>
                <div class="cm-form-alert alert d-none" role="alert"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn cm-btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn cm-confirm-btn">Delete</button>
            </div>
        </div>
    </div>
</div>
