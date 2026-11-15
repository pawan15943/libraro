{{--
    Seat / learner details drawer (#seatAllotmentModal2).
    Opened by .second_popup / .second_popup_without_seat (learner/script.blade.php), which loads
    route('learners.show') as JSON and fills the legacy fields below. public/js/lb-seatdetail.js
    reads the same JSON response and paints the visible design. Every id the old modal had is kept.
--}}
@php
    $lsdHidden = toggleHideField();
@endphp
<link rel="stylesheet" href="{{ asset('public/css/lb-seatdetail.css') }}?v={{ @filemtime(public_path('css/lb-seatdetail.css')) }}">
<div class="modal fade lsd" id="seatAllotmentModal2" tabindex="-1" aria-labelledby="lsdTitle" aria-hidden="true"
     data-hide-dob="{{ in_array('2', $lsdHidden) ? '1' : '0' }}" data-hide-email="{{ in_array('1', $lsdHidden) ? '1' : '0' }}"
     data-branch="{{ getCurrentBranchName() }}" data-profile-url="{{ route('learners.show', '__ID__') }}">
    <div class="modal-dialog lsd-dialog">
        <div class="modal-content lsd-content">
            <div class="lsd-head">
                <span class="lsd-head-ic" data-lsd-headic><x-lb-icon name="armchair" /></span>
                <div class="lsd-head-t">
                    <small>Seat details</small>
                    <h2 id="lsdTitle" data-lsd="title">Seat</h2>
                    <span data-lsd="kind">&nbsp;</span>
                </div>
                <button type="button" class="lsd-x" data-bs-dismiss="modal" aria-label="Close"><x-lb-icon name="x" /></button>
            </div>

            <div class="lsd-body" id="lsdBody">
                {{-- loading skeleton (shown until the learner data arrives) --}}
                <div class="lsd-skel" aria-hidden="true">
                    <i style="height: 132px"></i><i style="height: 14px; width: 30%"></i><i style="height: 64px"></i><i style="height: 64px"></i><i style="height: 64px"></i><i style="height: 14px; width: 26%"></i>
                    <div class="lsd-skel-row"><i></i><i></i><i></i><i></i><i></i></div>
                </div>

                <div class="lsd-view">
                    {{-- members-list variant (centred card) --}}
                    <div class="lsd-mview">
                        <div class="lsd-mhead">
                            <span class="lsd-av lsd-av--xl" data-lsd="mavatar"></span>
                            <div class="lsd-mhead-t">
                                <b data-lsd="mname">—</b>
                                <small><x-lb-icon name="fingerprint-pattern" />UID <span data-lsd="muid">—</span></small>
                                <span class="lsd-pills" data-lsd="mpills"></span>
                            </div>
                            <button type="button" class="lsd-x" data-bs-dismiss="modal" aria-label="Close"><x-lb-icon name="x" /></button>
                        </div>
                        <div class="lsd-tiles" data-lsd="tiles"></div>
                        {{-- multi-shift booking: every shift with its seat, timing and fee (docs/ADVANCED_SHIFTS.md) --}}
                        <div class="lsd-shiftbox" data-lsd="mshiftsWrap" hidden>
                            <div class="lsd-label">Shifts <em data-lsd="mshiftsCount"></em></div>
                            <div class="lsd-shiftlist" data-lsd="mshifts"></div>
                        </div>
                        <div class="lsd-prog lsd-prog--m">
                            <div class="lsd-prog-t"><span>Plan used</span><span data-lsd="mleft">—</span></div>
                            <div class="lsd-bar"><i data-lsd="mbar"></i></div>
                        </div>
                        <div class="lsd-label lsd-label--row"><span>Payments</span><a href="#" class="lsd-alltx" data-lsd="alltx" hidden>All transactions<x-lb-icon name="chevron-right" /></a></div>
                        <div class="lsd-paylist" data-lsd="pay"></div>
                    </div>

                    <div class="lsd-bday" data-lsd="bday" hidden>
                        <span class="lsd-bday-ic"><x-lb-icon name="cake" /></span>
                        <span class="lsd-bday-t"><b data-lsd="bdayTitle">Birthday today!</b><small>Send a wish — it takes 2 seconds.</small></span>
                        <a href="#" target="_blank" rel="noopener" class="lsd-bday-btn" data-lsd="bdayLink"><x-lb-brand name="whatsapp" />Send wishes</a>
                    </div>

                    <div class="lsd-member">
                        {{-- payment due: one bar above the photo (filled by lb-seatdetail.js) --}}
                        <div class="lsd-duebar" data-lsd="duebar" hidden>
                            <span class="lsd-duebar-ic"><x-lb-icon name="hand-coins" /></span>
                            <span class="lsd-duebar-t"><b data-lsd="dueAmt">₹0</b> payment due<small data-lsd="dueWhen"></small></span>
                            <a href="javascript:void(0)" class="lsd-duebar-btn settlement-learner" data-lsd="dueBtn" data-id="" data-learnerdetail="">Collect</a>
                        </div>
                        <div class="lsd-member-row">
                            <span class="lsd-av" data-lsd="avatar"></span>
                            <div class="lsd-member-t">
                                <b data-lsd="name">—</b>
                                <small><x-lb-icon name="fingerprint-pattern" />UID <span data-lsd="uid">—</span></small>
                                <span class="lsd-pills" data-lsd="pills"></span>
                            </div>
                            <a href="#" target="_blank" rel="noopener" class="lsd-wa" data-lsd="wa" aria-label="WhatsApp" title="WhatsApp" hidden><x-lb-brand name="whatsapp" /></a>
                        </div>
                        <div class="lsd-prog">
                            <div class="lsd-prog-t"><span>Plan used</span><span data-lsd="left">—</span></div>
                            <div class="lsd-bar"><i data-lsd="bar"></i></div>
                        </div>
                        <div class="lsd-member-acts">
                            @if(Auth::user()->can('has-permission', 'Edit Seat'))
                            <a href="javascript:void(0)" class="lsd-chip header-edit-profile-btn" id="headerEditProfileBtn"><x-lb-icon name="user-round" />Edit profile</a>
                            @endif
                            <a href="javascript:void(0)" class="lsd-chip header-edit-plan-btn" id="headerEditPlanBtn" style="display:none;"><x-lb-icon name="square-pen" />Edit plan</a>
                        </div>
                    </div>

                    <div class="lsd-label lsd-label--details">Details</div>
                    <div class="lsd-groups" data-lsd="groups"></div>

                    <div class="lsd-label lsd-label--row">
                        <span>Actions</span>
                        <span class="lsd-arrows">
                            <button type="button" class="lsd-arrow" id="opScrollLeftBtn" aria-label="Scroll actions left"><x-lb-icon name="chevron-left" /></button>
                            <button type="button" class="lsd-arrow" id="opScrollRightBtn" aria-label="Scroll actions right"><x-lb-icon name="chevron-right" /></button>
                        </span>
                    </div>
                    <div class="modal-op-items lsd-ops" id="modalOpContainer">
                        <div class="lsd-ops-loading" id="modalOpLoadingPlaceholder">Loading actions…</div>
                    </div>

                    <div class="lsd-docs" data-lsd="docsWrap" hidden>
                        <div class="lsd-label">Uploaded documents <em data-lsd="docsCount"></em></div>
                        <div class="lsd-hscroll" data-lsd="docs"></div>
                    </div>

                    <div class="lsd-tx" data-lsd="txWrap" hidden>
                        <div class="lsd-label">Last transaction</div>
                        <div class="lsd-hscroll" data-lsd="tx"></div>
                    </div>
                </div>

                {{-- legacy fields: filled by learner/script.blade.php, read by renew / edit flows --}}
                <div class="lsd-legacy" hidden>
                    <span id="seat_details_info"></span><span id="seat_name"></span>
                    <h5 id="owner">NA</h5><h5 id="learner_dob">NA</h5><h5 id="learner_mobile">NA</h5><h5 id="learner_email">NA</h5>
                    <h5 id="subscriptionDisplay">—</h5><span id="planName"></span><span id="planTypeName"></span>
                    <h5 id="planDurationDisplay">—</h5><span id="startOn"></span><span id="endOn"></span>
                    <h5 id="planPriceModeDisplay">—</h5><span id="price"></span><span id="paymentmode"></span>
                    <h5 id="planTiming">—</h5><span id="joinOn"></span><span id="proof"></span>
                    <div id="extendday"></div>
                </div>
            </div>

            <div class="lsd-foot" data-lsd="foot">
                @can('has-permission', 'Renew Seat')
                <input type="hidden" value="" id="user_id">
                <input type="hidden" value="" id="learner_detail_id">
                <a id="upgrade" class="lp-btn lsd-renew" href="javascript:void(0)" style="display:none;"><x-lb-icon name="refresh-cw" />Renew library membership</a>
                @else
                <input type="hidden" value="" id="user_id">
                <input type="hidden" value="" id="learner_detail_id">
                @endcan
                <a href="#" class="lsd-close lsd-full" data-lsd="full" hidden>Full profile</a>
                <button type="button" class="lsd-close" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<script src="{{ asset('public/js/lb-seatdetail.js') }}?v={{ @filemtime(public_path('js/lb-seatdetail.js')) }}" defer></script>
