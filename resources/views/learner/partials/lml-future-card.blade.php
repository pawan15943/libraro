{{-- A booking that starts on an upcoming date (members list "Future bookings" filter, future bookings page).
     Expects $value: a row of fetchCustomerData() with future_booking. --}}
@php
    $fbInitials ??= fn ($name) => collect(explode(' ', trim((string) $name)))->filter()->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('') ?: '?';
    $fbMoney ??= fn ($v) => '₹' . rtrim(rtrim(number_format((float) $v, 2, '.', ','), '0'), '.');
@endphp
    @php
        $learner_detail_id = $value->learner_detail_id;
        $learner_id = $value->id;
        $transaction = learnerTransaction($value->id, $learner_detail_id);
        $totalPendingAmt = optional($transaction)->pending_amount ?? 0;
        $due_date = optional($transaction)->due_date;
        $op = getLearnerOperation($learner_detail_id);
        $operation = optional($op)->operation;
        $operationDate = optional($op)->created_at;
        $formattedDueDate = !empty($due_date) ? (is_object($due_date) ? (!empty($due_date->due_date) ? date('j M', strtotime($due_date->due_date)) : '') : date('j M', strtotime($due_date))) : '';
        $hasPendingBalance = (paylater($learner_detail_id) && $totalPendingAmt != 0) || pending_amt($learner_detail_id);
        $paybleRefundAmt = function_exists('paybleRefund') ? paybleRefund($learner_detail_id) : 0;
        $isNonExpiry = ((int) ($value->no_expiry ?? 0) === 1);
        $isDeleted = $operation == 'deleteSeat' && $value->deleted_at != null;

        if ($operation == 'closeSeat') {
            $stText = 'Closed seat on ' . ($operationDate ? date('j M Y', strtotime($operationDate)) : ''); $tone = 'mut';
        } elseif ($isDeleted) {
            $stText = 'Deleted seat on ' . ($operationDate ? date('j M Y', strtotime($operationDate)) : ''); $tone = 'bad';
        } elseif ($isNonExpiry) {
            $stText = 'Non-Expired'; $tone = 'pink';
        } elseif (!empty($value->plan_start_date)) {
            $stText = 'Starts on ' . date('j M Y', strtotime($value->plan_start_date)); $tone = 'blue';
        } else {
            $stText = 'Future booking'; $tone = 'blue';
        }
        $tag = $isNonExpiry ? 'Non-Expired' : 'Upcoming';
        $tagTone = $isNonExpiry ? 'pink' : 'blue';
        $seatTxt = $value->seat_no ? getSeatDisplayShortFloorName($value->seat_no) : 'GEN';
        $duration = !empty($value->plan_start_date) && !empty($value->plan_end_date)
            ? date('j M Y', strtotime($value->plan_start_date)) . ' → ' . date('j M Y', strtotime($value->plan_end_date))
            : (!empty($value->plan_start_date) ? 'From ' . date('j M Y', strtotime($value->plan_start_date)) : '—');
        $dueTxt = $fbMoney($totalPendingAmt) . (!empty($formattedDueDate) ? ' (' . $formattedDueDate . ')' : '');
        $fullyPaid = !$hasPendingBalance && !empty($totalPendingAmt) && $totalPendingAmt == 0;
        $shiftCol = ['#3a81d7', '#eb881f', '#8f68cb', '#2fa37a', '#5b6b8c', '#34939f', '#bf2a82', '#1d3c86'][((int) ($value->plan_type_id ?? 0)) % 8];
    @endphp
        <article class="learner-card lml-card lml-card--inline is-{{ $hasPendingBalance ? 'bad' : $tone }}" data-learner="{{ $learner_id }}">
            <div class="lml-card-top">
                <i class="lml-blink {{ $hasPendingBalance ? 'is-on' : '' }}" aria-hidden="true"></i>
                <span class="lml-seatno">Seat <b>{{ $seatTxt }}</b></span>
                <x-lb-icon :name="$isDeleted ? 'trash' : 'calendar-clock'" class="lml-top-ic" />
                <span class="lml-exp" title="{{ $stText }}">{{ $stText }}</span>
                @if(!empty($value->plan_end_date))<span class="lml-date" title="Plan end date">till {{ date('j M Y', strtotime($value->plan_end_date)) }}</span>@endif
                @if($hasPendingBalance)<span class="lml-duepill"><x-lb-icon name="indian-rupee" />{{ $dueTxt }} due</span>@endif
            </div>

            <div class="lml-card-body">
                <div class="lml-who">
                    <a href="{{ asset($value->profile_picture ? $value->profile_picture : 'public/img/student_profile.jpeg') }}" class="lml-av view-image {{ $hasPendingBalance ? 'is-due' : '' }}" title="View profile photo">
                        @if($value->profile_picture)<img src="{{ asset($value->profile_picture) }}" alt="{{ $value->name }}" loading="lazy" data-i="{{ $fbInitials($value->name) }}" onerror="this.replaceWith(this.dataset.i)">@else{{ $fbInitials($value->name) }}@endif
                    </a>
                    <div class="lml-who-t">
                        <h3><a href="{{ route('learners.show', $value->id) }}" title="{{ $value->name }}">{{ $value->name }}</a></h3>
                        <div class="lml-uid">UID {{ $value->learner_no }}
                            <button type="button" class="copy-action-btn lml-copy" data-copy="{{ $value->learner_no }}" title="Copy UID" aria-label="Copy UID"><x-lb-icon name="copy" /></button>
                            @if(!empty($value->mobile))
                            <i class="lml-uid-sep" aria-hidden="true"></i>
                            <a href="tel:+91{{ $value->mobile }}" class="lml-tel" title="Call +91 {{ $value->mobile }}" aria-label="Call"><x-lb-icon name="phone" /></a>
                            <button type="button" class="copy-action-btn lml-copy" data-copy="{{ $value->mobile }}" title="Copy mobile number" aria-label="Copy mobile number"><x-lb-icon name="copy" /></button>
                            @endif
                        </div>
                    </div>
                    <span class="lml-st lml-st--{{ $tagTone }}">{{ $tag }}</span>
                </div>

                <div class="lml-chips">
                    @if(!empty($value->plan_type_name))<span class="lml-chip"><i class="lml-chip-dot" style="background: {{ $shiftCol }}"></i>{{ $value->plan_type_name }}</span>@endif
                    @if(!empty($value->plan_name))<span class="lml-chip"><x-lb-icon name="calendar-range" />{{ $value->plan_name }}</span>@endif
                </div>
                <div class="lml-dur"><x-lb-icon name="calendar" /><span>{{ $duration }}</span></div>

                {{-- list layout columns (≥ 992px) --}}
                <span class="lml-col lml-col-seat">{{ $value->plan_type_name ?: '—' }}@if(!empty($value->plan_name))<small>{{ $value->plan_name }}</small>@endif</span>
                <span class="lml-col lml-col-till">{{ $duration }}</span>
                <span class="lml-col lml-col-fee">{{ $hasPendingBalance ? $dueTxt . ' due' : ($fullyPaid ? 'Fully paid' : '—') }}</span>
                <span class="lml-col lml-st-col"><span class="lml-st lml-st--{{ $tagTone }}">{{ $tag }}</span></span>

                <div class="lml-money">
                    @if($hasPendingBalance)
                    <div class="lml-due is-due">
                        <small>Payment due</small>
                        <b>{{ $dueTxt }}</b>
                        <a href="javascript:void(0)" data-id="{{ $learner_id }}" data-learnerDetail="{{ $learner_detail_id }}" class="lml-txn settlement-learner" title="Collect payment" aria-label="Collect payment"><x-lb-icon name="external-link" /></a>
                    </div>
                    @elseif($fullyPaid)
                    <div class="lml-due is-clear">
                        <small>Payment</small>
                        <b>Fully paid</b>
                        @if(optional($transaction)->id)
                        <form action="{{ route('learner.receipt.download') }}" method="POST" enctype="multipart/form-data" target="_blank" class="lml-rcpt">
                            @csrf
                            <input type="hidden" name="learner_id" value="{{ $learner_id }}">
                            <input type="hidden" name="id" value="{{ $transaction->id ?? 0 }}">
                            <input type="hidden" name="type" value="learner">
                            <button type="submit" class="noLoader" title="Download receipt" aria-label="Download receipt"><x-lb-icon name="download" /></button>
                        </form>
                        @endif
                    </div>
                    @else
                    <div class="lml-paid"><small>Payment</small><b>—</b></div>
                    @endif
                    <div class="lml-paid">
                        <small>Locker</small>
                        <b>{{ ($transaction && $transaction->locker_amount) ? $fbMoney($transaction->locker_amount) . ' paid' : 'No' }}</b>
                    </div>
                </div>
            </div>

            <div class="lml-card-act">
                @if($hasPendingBalance)
                <a href="javascript:void(0)" data-id="{{ $learner_id }}" data-learnerDetail="{{ $learner_detail_id }}" class="lp-btn settlement-learner lml-collect"><x-lb-icon name="indian-rupee" />Collect {{ $fbMoney($totalPendingAmt) }}</a>
                @else
                <a href="{{ route('learners.show', $value->id) }}" class="lp-btn lp-btn--ghost lml-viewbtn"><x-lb-icon name="eye" />View details</a>
                @endif
                @if(optional($transaction)->id)
                <form action="{{ route('learner.receipt.download') }}" method="POST" enctype="multipart/form-data" target="_blank" class="lml-rcpt2">
                    @csrf
                    <input type="hidden" name="learner_id" value="{{ $learner_id }}">
                    <input type="hidden" name="id" value="{{ $transaction->id }}">
                    <input type="hidden" name="learner_detail_id" value="{{ $learner_detail_id }}">
                    <input type="hidden" name="type" value="learner">
                    <button type="submit" class="lp-btn lp-btn--ghost noLoader" title="Print receipt" aria-label="Print receipt"><x-lb-icon name="printer" /><span>Receipt</span></button>
                </form>
                @endif
            </div>

            {{-- every action, always visible: upper bar in list view, two rows in card view --}}
            <div class="lml-ai mobile-actions-scroll">
                    @include('learner.partials.future-booking-actions', ['isMobile' => true])
            </div>
        </article>
