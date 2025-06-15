@extends('layouts.library')
@section('content')

<style>
    .actionIcon {
        display: none;
    }

    .btn.btn-primary.button {
        background: var(--c1) !important;
        color: #fff !important;
        margin: 0 auto;
        height: auto;
        gap: .8rem;
        width: auto;
        padding: .5rem 1.5rem;
    }

    ul.learner-info {
        list-style: none;
        padding: 0;
        display: flex;
        gap: 3.5rem;
        justify-content: space-between;
        padding: 1.2rem;
        border: 1px solid #dcdcdc;
        border-radius: .8rem;
        background: #fff;
        margin: 0;
        z-index: 2;
        position: relative;
        align-items: center;
        flex-wrap: wrap;
    }

    ul.learner-info .d-flex {
        flex-direction: column;

    }



    ul.learner-info span {
        font-size: .8rem;
        text-transform: uppercase;
        font-weight: 500;
        color: #ababab;
    }

    ul.learner-info h5 {
        font-weight: 700;
        margin: 0;
        font-size: 1rem;
    }

    .action {
        background: #f7f7f7;
        padding: 1rem;
        border-radius: 0 0 1rem 1rem;
        display: flex;
        padding-top: 1.5rem;
        gap: .5rem;
        margin-top: -.5rem;
        z-index: 0;
        position: relative;
        flex-wrap: wrap;
    }

    .action a {
        text-decoration: none;
    }

    .record {
        background: #f5f5f5;
        border-radius: .8rem;
        position: relative;
        z-index: 0;
    }

    .record i {
        background: #000;
        width: 36px;
        height: 35px;
        display: flex ! IMPORTANT;
        justify-content: center;
        align-items: center;
        border-radius: 35px;
        color: #fff;
        font-size: .8rem;
        box-shadow: 1px 0 15px #00000045;
    }

    i:hover {
        background: #ababab;
    }

    @media screen and (max-width:768px) {
        ul.learner-info {
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
        }
    }

    h2.font-weight-700 {
        font-size: 1.5rem;
        margin-bottom: 1.25rem;
    }

    input.form-control.form-control-lg.text-center {
        height: 50px !important;
        font-size: 1rem 16px !important;
    }
</style>

<!-- Content Header (Page header) -->

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



<div class="row">

    @can('has-permission', 'Filter')
    <div class="col-lg-12">
        <section>
            <div class="container">
                <div class="row justify-content-center mt-5">
                    <div class="col-lg-6 text-center">
                        <h2 class="font-weight-700">Search Here</h2>
                        <form action="{{ route('learner.search') }}" method="GET">
                            <div class="row g-4">
                                <div class="col-lg-12">
                                    <input type="text" name="search" class="form-control form-control-lg text-center" value="{{ request()->get('search') }}" placeholder="Search Here by Name | Mobile | Seat No">
                                </div>
                            </div>

                            <div class="row mt-3">
                                <div class="col-lg-12 text-center">
                                    <button class="btn btn-primary button">
                                        Search
                                    </button>
                                </div>
                            </div>
                        </form>

                    </div>
                </div>
                <div class="row mb-4 ">


                    <div class="col-lg-12">

                        @php

                        $user = Auth::user();
                        $permissions = $user->subscription ? $user->subscription->permissions : null;
                        @endphp
                        @foreach($learners ?? [] as $key => $value)
                        @php
                        $planStatus = getPlanStatusDetails($value->plan_end_date);

                        @endphp

                        <div class="record mt-3">
                            <ul class="learner-info">
                                <li>
                                    <div class="d-flex">
                                        <span>Seat No.</span>
                                        <h5>{{$value->seat_no ? $value->seat_no : 'General'}}</h5>
                                    </div>
                                </li>
                                <li style="flex:1;">
                                    <div class="d-flex ">
                                        <span>Name</span>
                                        <h5>{{$value->name}}</h5>
                                    </div>
                                </li>
                                <li>
                                    <div class="d-flex">
                                        <span>Plan</span>
                                        <h5>{{$value->plan_type_name}} {{$value->plan_name}}</h5>
                                    </div>
                                </li>
                                <li>
                                    <div class="d-flex">
                                        <span>Plan Starts on</span>
                                        <h5>{{$value->plan_start_date}}</h5>
                                    </div>
                                </li>
                                <li>
                                    <div class="d-flex">
                                        <span>Plan Expired on</span>
                                        <h5>{{$value->plan_end_date}} {!! getUserStatusDetails($value->plan_end_date) !!}</h5>
                                    </div>
                                </li>
                                <li>
                                    <div class="d-flex">
                                        <span>Status</span>
                                        <h5 class="text-success">ACTIVE</h5>
                                    </div>
                                </li>
                                <li>
                                    <div class="d-flex">
                                        <h5><i class="fa fa-angle-right action-items"></i></h5>
                                    </div>
                                </li>
                            </ul>
                            <div class="action actionIcon">
                                <!-- View Seat Info -->
                                @can('has-permission', 'View Seat')

                                <a href="{{ route('learners.show', $value->id) }}" title="View Seat Booking Full Details">
                                    <i class="fas fa-eye"></i>
                                </a>

                                @endcan

                                @if($planStatus['diff_extend_day'] > 0)
                                <!-- Edit Seat Info -->
                                @can('has-permission', 'Edit Seat')

                                <a href="{{ route('learners.edit', $value->id) }}" title="Edit Seat Booking Details">
                                    <i class="fas fa-edit"></i>
                                </a>

                                @endcan

                                <!-- Make Payment -->
                                @can('has-permission', 'Renew Seat')

                                <a href="{{ route('learner.payment', $value->learner_detail_id) }}" title="Payment Learner" class="payment-learner">
                                    <i class="fas fa-credit-card"></i>
                                </a>

                                @endcan

                                <!-- Renew Plan if near expiry -->
                                @if($planStatus['diff_in_days'] <= 5 && $planStatus['diff_extend_day']> 5)
                                    @can('has-permission', 'Renew Seat')

                                    <a href="{{ route('learner.renew.plan', $value->id) }}" title="Renew Plan">
                                        <i class="fa fa-arrow-up-short-wide"></i>
                                    </a>

                                    @endcan
                                    @endif

                                    <!-- Upgrade Plan if near expiry -->
                                    @if($planStatus['diff_in_days'] <= 5 && $planStatus['diff_extend_day']> 5)
                                        @can('has-permission', 'Upgrade Seat Plan')

                                        <a href="{{ route('learners.upgrade.renew', $value->id) }}" title="Upgrade Plan">
                                            <i class="fa fa-arrow-up-short-wide"></i>
                                        </a>

                                        @endcan
                                        @endif
                                        @endif

                                        <!-- Swap Seat -->
                                        @can('has-permission', 'Swap Seat')

                                        <a href="{{ route('learners.swap', $value->id) }}" title="Swap Seat">
                                            <i class="fa-solid fa-arrow-right-arrow-left"></i>
                                        </a>

                                        @endcan

                                        <!-- Change Plan -->
                                        @can('has-permission', 'Change Plan')

                                        <a href="{{ route('learner.change.plan', $value->id) }}" title="Change Plan">
                                            <i class="fa fa-arrow-up-short-wide"></i>
                                        </a>

                                        @endcan

                                        <!-- Generate ID Card -->

                                        <form action="{{ route('generateIdCard') }}" method="POST" enctype="multipart/form-data">
                                            @csrf
                                            <input type="hidden" name="detail_id" value="{{ $value->learner_detail_id }}">
                                            <input type="hidden" name="learner_id" value="{{ $value->id }}">
                                            <button type="submit" title="Print ID Card" style="outline: none; border:none;"><i class="fa fa-print"></i></button>
                                        </form>


                                        <!-- Close Seat -->
                                        @can('has-permission', 'Close Seat')

                                        <a href="javascript:void(0);" class="link-close-plan" data-id="{{ $value->id }}" title="Close" data-plan_end_date="{{ $value->plan_end_date }}">
                                            <i class="fas fa-times"></i>
                                        </a>

                                        @endcan

                                        <!-- Delete Learner -->
                                        @can('has-permission', 'Delete Seat')

                                        <a href="#" data-id="{{ $value->id }}" title="Delete Learner" class="delete-customer">
                                            <i class="fas fa-trash"></i>
                                        </a>

                                        @endcan

                                        <!-- Reactivate Learner -->
                                        @can('has-permission', 'Reactive Seat')
                                        @if($value->status == 0)

                                        <a href="{{ route('learners.reactive', $value->id) }}" title="Reactivate Learner">
                                            <i class="fa-solid fa-arrows-rotate"></i>
                                        </a>

                                        @endif
                                        @endcan

                                        @if($diffExtendDay > 0)
                                        <!-- WhatsApp Notification -->
                                        @can('has-permission', 'WhatsApp Notification')

                                        <a href="https://web.whatsapp.com/send?phone=91{{ $value->mobile }}&text=Hey!%20🌟%0A%0AJust%20a%20friendly%20reminder:%20Your%20library%20seat%20plan%20will%20expire%20in%205%20days!%20📚✨%0A%0ADon’t%20miss%20out%20on%20the%20chance%20to%20keep%20enjoying%20your%20favorite%20books%20and%20resources.%20Plus,%20renewing%20now%20means%20you%20can%20unlock%20exciting%20rewards!%20🎁"
                                            target="_blank"
                                            onclick="incrementMessageCount({{ $value->id }}, 'whatsapp')"
                                            class="whatsapp"
                                            data-bs-toggle="tooltip"
                                            data-bs-placement="bottom"
                                            title="Send WhatsApp Reminder">
                                            <i class="fa-brands fa-whatsapp"></i>
                                        </a>

                                        @endcan

                                        <!-- Email Notification -->
                                        @can('has-permission', 'Email Notification')

                                        <a href="mailto:{{ $value->email }}?subject=Library Seat Renewal Reminder&body=Hey!%20🌟%0D%0A%0D%0AJust%20a%20friendly%20reminder:%20Your%20library%20seat%20plan%20will%20expire%20in%205%20days!%20📚✨%0D%0A%0D%0ADon’t%20miss%20out%20on%20the%20chance%20to%20keep%20enjoying%20your%20favorite%20books%20and%20resources.%20Plus,%20renewing%20now%20means%20you%20can%20unlock%20exciting%20rewards!%20🎁"
                                            target="_blank"
                                            onclick="incrementMessageCount({{ $value->id }}, 'email')"
                                            class="message"
                                            data-bs-toggle="tooltip"
                                            data-bs-placement="bottom"
                                            title="Send Email Reminder">
                                            <i class="fas fa-envelope"></i>
                                        </a>

                                        @endcan
                                        @endif
                            </div>

                        </div>

                        @endforeach





                    </div>
                </div>

            </div>
        </section>

    </div>
    @endcan
</div>




<!-- Modal Popup end for Configration -->

<!-- /.content -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


<script>
    $('.actionIcon').hide();
    $('.action-items').on('click', function() {
        $(this).closest('.record').find('.actionIcon').stop(true, true).slideToggle('slow');
    });

    $(document).ready(function() {
        let table = new DataTable('#datatable', {
            searching: false, // This option hides the search bar
            ordering: false
        });
        var url = window.location.href;

        // Check if there are any URL parameters
        if (url.includes('?')) {
            // Redirect to the URL without parameters
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    });
</script>
@include('learner.popup')
@include('learner.script')
@endsection