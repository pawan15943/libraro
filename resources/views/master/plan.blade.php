@extends('layouts.library')
@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}" />
<!-- Main content -->


<div id="success-message" class="alert alert-success" style="display:none;"></div>
<div id="error-message" class="alert alert-danger" style="display:none;"></div>
@if($errors->any())
<div class="alert alert-danger">
    <ul>
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<!-- Masters -->
<div class="row">
    <div class="col-lg-12">
        <p class="info-message">
            <span class="close-btn" onclick="this.parentElement.style.display='none';">×</span>
            <b>Important :</b> Here you can create library plan like Monthly, weeekly and daywise.
        </p>
    </div>
</div>
<div class="card">

    <!-- Add Library User Form -->
    <form id="planForm" class="">
        @csrf

        <input type="hidden" name="id" value="{{ $plan->id ?? '' }}">
        <input type="hidden" name="library_id" value="{{getLibraryId()}}">
        <input type="hidden" name="databasemodel" value="Plan">
        <input type="hidden" name="redirect" value="{{ route('plan.index') }}">
        <div class="row">
            <div class="col-lg-12">
                <div class="how-use border p-3 mt-4 bg-light rounded mb-4">
                    <!-- Collapse Header -->
                    <h4 class="d-flex justify-content-between align-items-center" 
                        data-bs-toggle="collapse" 
                        data-bs-target="#howItWorksCollapse" 
                        style="cursor:pointer;">How the Libraro Intelligent System Works
                        <span class="toggle-icon">+</span>
                    </h4>

                    <!-- Collapsible Content -->
                    <div id="howItWorksCollapse" class="collapse">
                        <p class="m-0 pt-3">Dear library owner, </p>
                        <ul type="1" class="how-it-work">
                            <li>We have made <b>Plan Management</b> much easier for you.</li>
                            <li><b>You only need to set up everything for the 1-Month Plan.</b><br> 
                                Once the 1-Month Price and details are added, the system will take care of the rest.
                            </li>
                            <li>
                                Now you can add plans like <b>2-Month, 3-Month, and more,</b> and you do not need to enter prices for these plans.
                                Our intelligent system will automatically calculate their prices based on your 1-month plan.
                            </li>
                            <li>
                                <ul>
                                    <li><b>Plan Type / Shift :</b> <br>
                                        You can create multiple custom plan types. <br> 
                                        The system will use your <b>1-Month price</b> to calculate all other durations.
                                    </li>
                                    <li><b>Plan Price :</b> <br> 
                                        If you want to change the price, you can update the custom plan price in the plan price section.
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="row g-4">
            <div class="col-lg-4">
                <label for="">Type <span>*</span></label>
                <select class="form-select @error('type') is-invalid @enderror" name="type" id="type">
                    <option value="">Select Type</option>
                    <option value="MONTH" {{ old('type', isset($plan) ? $plan->type : '') == 'MONTH' ? 'selected' : '' }}>MONTH</option>
                    <option value="YEAR" {{ old('type', isset($plan) ? $plan->type : '') == 'YEAR' ? 'selected' : '' }}>YEAR</option>
                    <option value="DAY" {{ old('type', isset($plan) ? $plan->type : '') == 'DAY' ? 'selected' : '' }}>DAY</option>
                    <option value="WEEK" {{ old('type', isset($plan) ? $plan->type : '') == 'WEEK' ? 'selected' : '' }}>WEEK</option>

                </select>
                @error('type')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror

            </div>
            <div class="col-lg-4">
                <label for="">Plan (Accept only digits)<span>*</span></label>
                <input type="text" class="form-control digit-only @error('plan_id') is-invalid @enderror" name="plan_id" id="plan_id" value="{{ old('plan_id', $plan->plan_id ?? '') }}" placeholder="Ex : 1 for 1 Month & 2 for 2 Month">
                @error('plan_id')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>

            <div class="col-lg-4">
                <label for="">Monthly Days</label>
                <select class="form-select @error('monthdays') is-invalid @enderror" name="monthdays" id="monthdays"
                    data-selected="{{ old('monthdays', isset($plan) ? $plan->monthdays : '') }}">
                    <option value="">Select Month Days Option</option>
                    <option value="28">28 Days</option>
                    <option value="30">30 Days</option>
                    <option value="">Automatic (Calendar Wise)</option>
                </select>

                @error('monthdays')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>



        </div>
        <div class="row mt-4">
            <div class="col-lg-3">
                <button type="submit" class="btn btn-primary button" id="savePlanBtn"><i class="fa fa-plus"></i>
                    @if(isset($plan)) Edit Plan @else Add Plan @endif
                </button>
            </div>
        </div>

    </form>
</div>





<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/malihu-custom-scrollbar-plugin/3.1.5/jquery.mCustomScrollbar.min.css">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/malihu-custom-scrollbar-plugin/3.1.5/jquery.mCustomScrollbar.concat.min.js"></script>

<script>
    $(document).ready(function() {

        function updateMonthDays() {
            let type = $("#type").val();
            let plan = parseInt($("#plan_id").val());
            let monthDaysDropdown = $("#monthdays");

            // get selected value from old input or edit mode
            let selectedValue = monthDaysDropdown.attr("data-selected");

            // Hide if not MONTH
            if (type !== "MONTH") {
                monthDaysDropdown.html('<option value="">Select Month Days Option</option>');
                monthDaysDropdown.closest('.col-lg-4').hide();
                return;
            }

            // Show dropdown
            monthDaysDropdown.closest('.col-lg-4').show();
            monthDaysDropdown.empty();

            // If plan invalid
            if (!plan || plan <= 0) {
                monthDaysDropdown.append('<option value="">Select Month Days Option</option>');
                return;
            }

            // Create options
            if (plan === 1) {
                monthDaysDropdown.append('<option value="28">28 Days</option>');
                monthDaysDropdown.append('<option value="30">30 Days</option>');
                monthDaysDropdown.append('<option value="">Automatic (Calendar Wise)</option>');
            } else {
                monthDaysDropdown.append('<option value="' + (28 * plan) + '">' + (28 * plan) + ' Days</option>');
                monthDaysDropdown.append('<option value="' + (30 * plan) + '">' + (30 * plan) + ' Days</option>');
                monthDaysDropdown.append('<option value="">' + plan + ' Months (Calendar Wise)</option>');
            }

            // ----- AUTO SELECT LOGIC -----

            // case 1: if selected value exists → keep it
            if (selectedValue !== null && selectedValue !== undefined && selectedValue !== "") {
                monthDaysDropdown.val(selectedValue);
            } else {
                // case 2: blank/null → auto-select Calendar Wise ("")
                monthDaysDropdown.val("");
            }
        }

        $("#type").change(updateMonthDays);
        $("#plan_id").keyup(updateMonthDays);

        updateMonthDays(); // run on page load for edit mode
    });
</script>

<script>
    (function($) {
        $(window).on("load", function() {
            $(".contents").mCustomScrollbar({
                theme: "dark",
                scrollInertia: 300,
                axis: "y",
                autoHideScrollbar: false, // Keeps
            });
        });
    })(jQuery);
</script>

<!-- Toggle + / - Script -->
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const header = document.querySelector('[data-bs-target="#howItWorksCollapse"]');
        const icon = header.querySelector(".toggle-icon");
        const collapseEl = document.getElementById("howItWorksCollapse");

        collapseEl.addEventListener('show.bs.collapse', function () {
            icon.textContent = "−"; // Minus
        });

        collapseEl.addEventListener('hide.bs.collapse', function () {
            icon.textContent = "+"; // Plus
        });
    });
</script>
<!-- /.content -->
@include('master.script')
@endsection