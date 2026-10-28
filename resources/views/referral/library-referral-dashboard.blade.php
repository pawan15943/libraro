@extends('layouts.library')

@push('lp-styles')
<link rel="stylesheet" href="{{ asset('public/css/lb-pages.css') }}?v={{ @filemtime(public_path('css/lb-pages.css')) }}">
@endpush

@section('content')

@php
    // Business rules (LibraryReferralController): 10 points per completed referral, 30 points = 30 free days.
    $rfPerReferral = 10;
    $rfGoal = 30;
    $rfProgress = $earnReward % $rfGoal;
    $rfPct = $is_redeem ? 100 : (int) round($rfProgress / $rfGoal * 100);
    $rfNeed = $is_redeem ? 0 : (int) ceil(($rfGoal - $rfProgress) / $rfPerReferral);
    $rfLibrary = optional(getLibrary())->library_name ?: 'my library';
    $rfShareText = "I manage {$rfLibrary} on Libraro — seats, fees and attendance in one app. Sign up with my link: {$referralLink}";
    $rfQr = 'https://api.qrserver.com/v1/create-qr-code/?size=360x360&margin=12&data=' . urlencode($referralLink);
    $rfMethod = fn ($t) => match (strtolower((string) $t)) { 'link' => ['Link', 'link'], 'code' => ['Code', 'hash'], 'qr' => ['QR', 'qr-code'], default => [ucfirst((string) $t) ?: 'Referral', 'share-2'] };
@endphp

{{-- Rules --}}
<div class="modal fade lpg-modal" id="rfRules" tabindex="-1" aria-labelledby="rfRulesTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header">
                <span class="lpg-ic"><x-lb-icon name="scroll-text" /></span>
                <div><h2 id="rfRulesTitle">Referral rules</h2><small>Please read before you share</small></div>
                <button type="button" class="lpg-close" data-bs-dismiss="modal" aria-label="Close"><x-lb-icon name="x" /></button>
            </div>
            <div class="modal-body">
                <ol class="rf-rules">
                    <li><b>Who can refer</b>Only registered library owners can take part.</li>
                    <li><b>Who you can refer</b>New library owners who aren’t already on Libraro.</li>
                    <li><b>Successful referral</b>They sign up with your code or link and finish onboarding and payment.</li>
                    <li><b>When you earn</b>Points are added only after every step above is complete.</li>
                    <li><b>Limit</b>Up to {{ $maxReferrals }} library owners, unless more slots are granted.</li>
                    <li><b>No misuse</b>Fake accounts, self-referrals or fraud remove you from the programme.</li>
                    <li><b>Using rewards</b>Rewards are redeemed inside Libraro as subscription days.</li>
                    <li><b>Changes</b>The programme can be changed, paused or ended at any time.</li>
                    <li><b>Non-transferable</b>Rewards can’t be transferred, exchanged or converted to cash.</li>
                    <li><b>Terms</b>Everyone taking part must follow the Libraro Terms &amp; Conditions.</li>
                </ol>
            </div>
        </div>
    </div>
</div>

{{-- Redeem --}}
<div class="modal fade lpg-modal" id="redeemModal" tabindex="-1" aria-labelledby="rfRedeemTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('library.redeem') }}" class="modal-content" id="rfRedeemForm">
            @csrf
            <div class="modal-header">
                <span class="lpg-ic is-ok"><x-lb-icon name="gift" /></span>
                <div><h2 id="rfRedeemTitle">Redeem 30 free days?</h2><small>Uses {{ $rfGoal }} points (3 referrals)</small></div>
                <button type="button" class="lpg-close" data-bs-dismiss="modal" aria-label="Close"><x-lb-icon name="x" /></button>
            </div>
            <div class="modal-body">
                <p class="rf-note">30 days are added to your Libraro subscription right away. If your plan has already ended, the 30 days start today.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="lp-btn lp-btn--ghost" data-bs-dismiss="modal">Not now</button>
                <button type="submit" class="lp-btn lp-btn--ok"><x-lb-icon name="check" />Redeem</button>
            </div>
        </form>
    </div>
</div>

<div class="lpg rf">
    <header class="lpg-head">
        <div class="lpg-head-t">
            <div class="lpg-crumb"><a href="{{ route('library.home') }}">Dashboard</a><x-lb-icon name="chevron-right" /><span>Refer &amp; earn</span></div>
            <h1>Refer &amp; earn</h1>
            <p>Invite other library owners to Libraro. Every 3 who join gets you 30 days of Libraro free.</p>
        </div>
        <div class="lpg-head-r">
            <button type="button" class="lp-btn lp-btn--ghost" data-bs-toggle="modal" data-bs-target="#rfRules"><x-lb-icon name="info" />Rules</button>
        </div>
    </header>

    {{-- Reward progress --}}
    <section class="rf-hero">
        <div class="rf-hero-top">
            <div>
                <span class="rf-k">Your reward points</span>
                <strong class="rf-v">{{ $earnReward }}<small> pts</small></strong>
            </div>
            <span class="rf-medal"><x-lb-icon name="gift" /></span>
        </div>
        <div class="rf-bar" role="progressbar" aria-valuemin="0" aria-valuemax="{{ $rfGoal }}" aria-valuenow="{{ $is_redeem ? $rfGoal : $rfProgress }}" aria-label="Progress to the next reward"><i style="width: {{ $rfPct }}%"></i></div>
        <div class="rf-hero-f">
            @if($is_redeem)
                <p><b>30 free days unlocked.</b> Redeem them whenever you like.</p>
                <button type="button" class="lp-btn lp-btn--white" data-bs-toggle="modal" data-bs-target="#redeemModal"><x-lb-icon name="gift" />Redeem 30 days</button>
            @else
                <p><b>{{ $rfNeed }} more {{ $rfNeed === 1 ? 'referral' : 'referrals' }}</b> to unlock 30 free days · {{ $rfPerReferral }} pts each</p>
            @endif
        </div>
    </section>

    <div class="lpg-stats rf-stats">
        <div class="lpg-stat is-ok">
            <span class="lpg-stat-ic"><x-lb-icon name="badge-check" /></span>
            <span>Joined</span>
            <b>{{ $completedReferrals }}</b>
            <small>Successful referrals</small>
        </div>
        <div class="lpg-stat is-warn">
            <span class="lpg-stat-ic"><x-lb-icon name="hourglass" /></span>
            <span>In progress</span>
            <b>{{ $yourReferrals->count() }}</b>
            <small>Signed up, not done yet</small>
        </div>
        <div class="lpg-stat">
            <span class="lpg-stat-ic"><x-lb-icon name="users" /></span>
            <span>Slots left</span>
            <b>{{ $availableReferrals }}<small style="font-size:14px;color:var(--lp-mut2)"> / {{ $maxReferrals }}</small></b>
            <small>Referrals you can still make</small>
        </div>
    </div>

    <div class="rf-grid">
        {{-- Share --}}
        <section class="lpg-card rf-share">
            <div class="lpg-card-h">
                <span class="lpg-ic"><x-lb-icon name="share-2" /></span>
                <div><h2>Share your invite</h2><p>Send the link, or let them scan the QR.</p></div>
            </div>

            <div class="rf-field">
                <span class="rf-field-k">Referral code</span>
                <div class="rf-copy">
                    <code class="rf-code">{{ $referralCode }}</code>
                    <button type="button" class="lp-btn lp-btn--soft lp-btn--sm rf-copy-btn" data-copy="{{ $referralCode }}"><x-lb-icon name="copy" /><span>Copy</span></button>
                </div>
            </div>
            <div class="rf-field">
                <span class="rf-field-k">Invite link</span>
                <div class="rf-copy">
                    <span class="rf-link" title="{{ $referralLink }}">{{ preg_replace('#^https?://#', '', $referralLink) }}</span>
                    <button type="button" class="lp-btn lp-btn--soft lp-btn--sm rf-copy-btn" data-copy="{{ $referralLink }}"><x-lb-icon name="copy" /><span>Copy</span></button>
                </div>
            </div>

            <div class="rf-actions">
                <a href="https://wa.me/?text={{ urlencode($rfShareText) }}" target="_blank" rel="noopener" class="lp-btn rf-wa noLoader"><x-lb-icon name="message-circle" />Share on WhatsApp</a>
                <button type="button" class="lp-btn lp-btn--ghost" id="rfNativeShare" hidden><x-lb-icon name="share-2" />More options</button>
            </div>

            <div class="rf-qr">
                <img src="{{ $rfQr }}" alt="QR code for your invite link" width="148" height="148" loading="lazy">
                <div>
                    <b>Scan to join</b>
                    <p>Show this on your phone or print it at your front desk.</p>
                    <a href="{{ $rfQr }}" target="_blank" rel="noopener" class="lp-link noLoader" download="libraro-referral-qr.png"><x-lb-icon name="download" />Open QR image</a>
                </div>
            </div>
        </section>

        {{-- How it works --}}
        <section class="lpg-card rf-how">
            <div class="lpg-card-h">
                <span class="lpg-ic is-violet"><x-lb-icon name="sparkles" /></span>
                <div><h2>How it works</h2><p>Three steps, no paperwork.</p></div>
            </div>
            <ol class="rf-steps">
                <li><span>1</span><div><b>Share your link or code</b><p>With any library owner who isn’t on Libraro yet.</p></div></li>
                <li><span>2</span><div><b>They join and pay</b><p>Once they finish signing up, it counts as a successful referral.</p></div></li>
                <li><span>3</span><div><b>You earn {{ $rfPerReferral }} points</b><p>Every {{ $rfGoal }} points becomes 30 free days on your plan.</p></div></li>
            </ol>
            <button type="button" class="lp-link rf-rules-link" data-bs-toggle="modal" data-bs-target="#rfRules">Read the full rules<x-lb-icon name="arrow-right" /></button>
        </section>
    </div>

    {{-- Referrals --}}
    <h2 class="lpg-sec">Your referrals</h2>
    <ul class="lpg-seg rf-tabs" role="tablist">
        <li role="presentation"><button class="lpg-seg-b active" data-bs-toggle="tab" data-bs-target="#rfPending" type="button" role="tab" aria-selected="true"><x-lb-icon name="hourglass" />In progress<span class="lpg-n">{{ $yourReferrals->count() }}</span></button></li>
        <li role="presentation"><button class="lpg-seg-b" data-bs-toggle="tab" data-bs-target="#rfDone" type="button" role="tab" aria-selected="false"><x-lb-icon name="badge-check" />Joined<span class="lpg-n">{{ $completedList->count() }}</span></button></li>
    </ul>

    <div class="tab-content rf-lists">
        @foreach([['rfPending', $yourReferrals, true], ['rfDone', $completedList, false]] as [$paneId, $list, $isPending])
            <div class="tab-pane fade {{ $isPending ? 'show active' : '' }}" id="{{ $paneId }}" role="tabpanel">
                @if($list->count())
                    <div class="lpg-list">
                        @foreach($list as $ref)
                            @php [$mLabel, $mIcon] = $rfMethod($ref->referral_type); @endphp
                            <div class="lpg-row">
                                <span class="lpg-ic {{ $isPending ? 'is-warn' : 'is-ok' }}"><x-lb-icon :name="$isPending ? 'hourglass' : 'badge-check'" /></span>
                                <div class="lpg-row-t">
                                    <b>{{ $ref->referred_library_name ?: 'New library' }}</b>
                                    <small><span>via {{ $mLabel }}</span><span>{{ \Carbon\Carbon::parse($isPending ? $ref->created_at : $ref->updated_at)->format('d M Y') }}</span></small>
                                </div>
                                <div class="lpg-row-r">
                                    @if($isPending)
                                        <span class="lpg-tag is-warn">In progress</span>
                                    @elseif($ref->redeem_status)
                                        <span class="lpg-tag">Redeemed</span>
                                    @else
                                        <span class="lpg-tag is-ok">+{{ $rfPerReferral }} pts</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="lpg-empty">
                        <span><x-lb-icon :name="$isPending ? 'user-plus' : 'badge-check'" /></span>
                        <b>{{ $isPending ? 'No one has signed up yet' : 'No completed referrals yet' }}</b>
                        <p>{{ $isPending ? 'Share your link on WhatsApp — owners who sign up with it appear here.' : 'When a referred library finishes signing up and paying, it moves here and you earn points.' }}</p>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        function copy(text) {
            if (navigator.clipboard && window.isSecureContext) return navigator.clipboard.writeText(text);
            var t = document.createElement('textarea');
            t.value = text; t.setAttribute('readonly', ''); t.style.position = 'fixed'; t.style.opacity = '0';
            document.body.appendChild(t); t.select();
            try { document.execCommand('copy'); } finally { document.body.removeChild(t); }
            return Promise.resolve();
        }

        document.querySelectorAll('.rf-copy-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                copy(btn.dataset.copy).then(function () {
                    var label = btn.querySelector('span');
                    btn.classList.add('is-done');
                    label.textContent = 'Copied';
                    if (window.lpToast) window.lpToast('Copied to clipboard');
                    setTimeout(function () { btn.classList.remove('is-done'); label.textContent = 'Copy'; }, 1600);
                });
            });
        });

        // Phones: the system share sheet (Telegram, SMS, email…)
        var share = document.getElementById('rfNativeShare');
        if (navigator.share && share) {
            share.hidden = false;
            share.addEventListener('click', function () {
                navigator.share({ title: 'Join Libraro', text: @json($rfShareText), url: @json($referralLink) }).catch(function () {});
            });
        }

        var redeem = document.getElementById('rfRedeemForm');
        if (redeem) redeem.addEventListener('submit', function () { redeem.querySelector('[type="submit"]').classList.add('is-busy'); });
    });
</script>

@endsection
