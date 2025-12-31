@extends('layouts.library')

@section('content')
<style>
    .font-family {
        font-family: 'Outfit', 'sans-sarif';
        font-weight: 400;
    }

    .library-dashbaord .border {
        border: none ! IMPORTANT;
    }

    span.refral {
        font-weight: 500;
        font-family: 'Outfit', 'sans-sarif';
    }

    .refer-and-earn-main .refer-and-earn {
        background: #fff;
        padding: 0 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-radius: 1rem;
    }

    .refer-and-earn-main .refer-and-earn img {
        width: 300px;
    }

    .d-flex.p-3.border.rounded-4.justify-content-between.align-items-center.mb-3 {
        border: 2px dashed #ababab !important;
    }

    .refer-and-earn-main div#refTabs button {
        display: block;
        width: calc(100% / 3 - 1rem);
        font-family: 'Outfit', 'sans-sarif';
    }

    .nav-pills .nav-link,
    .nav-pills .show>.nav-link {
        box-shadow: none;
        color: #000;
        border: 1px solid #dedede;
    }

    .refer-and-earn-main button.nav-link.px-3.py-2 {
        background: #fff;
        color: #000;
        text-transform: uppercase;
        font-weight: 600;
        font-family: 'outfit', 'sans-sarif';
        font-size: .9rem;
    }

    .refer-and-earn-main button.nav-link.px-3.py-2 {
        border: 1px solid #e3e3ff;
        background: #e9e9ff !important;
    }



    .refer-and-earn-main a.refral {
        width: 100%;
        height: 100%;
        background: #18225f;
        border-radius: .8rem;
        color: #fff;
        text-decoration: none;
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: 1.2rem;
    }

    .refer-and-earn-main h5.fw-bold.mb-2 {
        font-size: .8rem;
        font-weight: 500 !IMPORTANT;
        color: #858383;
    }

    .refer-and-earn-main span.fw-bold.fs-5 {
        font-size: 1rem ! IMPORTANT;
    }

    .refer-and-earn-main button.btn.btn-outline-primary.btn-sm {
        border: none;
        color: #00BCD4;
        background: #f5f5f5;
    }

    .content.bg-transparent {
        background: #fff;
    }

    button.btn.btn-outline-primary.btn-sm.copy {
        width: 35px;
        height: 35px;
        border-radius: 35px;
        box-shadow: none;
    }

    @media screen and (max-width: 768px) {
        .refer-and-earn {
            padding: 1.5rem !important;
        }

        .refer-and-earn-main .refer-and-earn {
            flex-direction: column;
            text-align: center;
        }

        .refer-and-earn-main .refer-and-earn img {
            width: 100%;
            margin-top: 1rem;
        }

    }

    .rewardsCreadit {
        background: linear-gradient(208deg, #07e1fd, #001276);
        padding: 1.5rem;
        border-radius: .8rem;
        margin-top: -2.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .rewardsCreadit button {
        margin: 0 !IMPORTANT;
        border-radius: 2rem;
        font-family: 'outfit', 'sans-sarif';
        background: #18225f;
        padding: .3rem 1.5rem;
        font-weight: 500;
        color: #fff;
    }

    .earnedReward * {
        color: #fff;
    }

    button.nav-link.py-3.m-0.rounded-3 {
        border: none;
    }

    .refer-and-earn-main button.nav-link.px-3.py-2.active {
        background: #18225f !important;
        color: #fff;
    }

    .nav-pills .nav-link.active,
    .nav-pills .show>.nav-link {
        display: block;
        width: calc(100% / 3 - 1rem);
        font-family: 'Outfit', 'sans-sarif';
        font-size: 1.1rem;
        text-transform: uppercase;
        background: linear-gradient(45deg, #8BC34A, #4CAF50);
        font-weight: 500;
        color: #fff;
    }

    .nav-pills .nav-link,
    .nav-pills .show>.nav-link {
        border: none !important;
        font-size: 1.1rem;
        text-transform: uppercase;

    }
</style>
<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="exampleModalLabel">Referral Rules</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <ol class="ps-3 font-family">
                    <li class="mb-2 font-family">
                        <b>Who Can Refer:</b>
                        Only registered users/library owners can participate in the referral program.
                    </li>

                    <li class="mb-2 font-family">
                        <b>Who You Can Refer:</b>
                        You may refer only new library owners who are not already registered on our platform.
                    </li>

                    <li class="mb-2 font-family">
                        <b>Successful Referral:</b>
                        A referral is considered successful when the referred library owner registers using your
                        referral link/code and completes the required onboarding or payment process.
                    </li>

                    <li class="mb-2 font-family">
                        <b>Reward Eligibility:</b>
                        Rewards are issued only after the referred user successfully completes all required steps.
                    </li>

                    <li class="mb-2 font-family">
                        <b>Maximum Referrals:</b>
                        Each user can refer up to <b>10 library owners</b> unless additional referral
                        slots are granted.
                    </li>

                    <li class="mb-2 font-family">
                        <b>Misuse Prohibited:</b>
                        Fake accounts, self-referrals, or fraudulent activity will lead to removal from the referral
                        program.
                    </li>

                    <li class="mb-2 font-family">
                        <b>Reward Usage:</b>
                        Earned rewards can be redeemed only within the platform as per the available options.
                    </li>

                    <li class="mb-2 font-family">
                        <b>Program Changes:</b>
                        The platform may modify, pause, or terminate the referral program at any time.
                    </li>

                    <li class="mb-2 font-family">
                        <b>Non-Transferable:</b>
                        Referral rewards cannot be transferred, exchanged, or converted to cash unless stated
                        otherwise.
                    </li>

                    <li class="mb-2 font-family">
                        <b>Compliance:</b>
                        All participants must comply with the platform’s Terms & Conditions.
                    </li>
                </ol>
            </div>

        </div>
    </div>
</div>

<div class="modal fade" id="redeemModal">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('library.redeem') }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5>Confirm Redemption</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <p>You will redeem <b>30 points</b> (3 referrals).</p>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">
                        Confirm Redeem
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>


<div class="refer-and-earn-main my-4">
    <!-- Hero Banner -->
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="refer-and-earn">
                <div class="content bg-transparent">
                    <h2 class="mb-2">Refer & Earn</h2>
                    <p>Use our Refer & Earn module to invite other library owners. Each successful referral
                        rewards you with exclusive benefits.</p>
                </div>
                <img src="{{ asset('public/img/refer-earn.png') }}" alt="Refer & Earn">
            </div>
        </div>
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="rewardsCreadit">
                    <div class="earnedReward">
                        <span>My Earned Reward Points</span>
                        <h4 class="text-white">100</h4>
                    </div>

                    <button class="btn btn-warning mt-3"
                        data-bs-toggle="modal"
                        data-bs-target="#redeemModal">
                        Redeem Now ({{ $earnReward }} pts)
                    </button>

                </div>
            </div>
        </div>

    </div>

    <div class="row justify-content-center mt-4 text-center">
        <div class="col-lg-10">
            <div class="row g-4 justify-content-center">
                <div class="col-lg-4">
                    <div class="bg-white p-3 rounded-4 border">
                        <h4>{{ $maxReferrals }}</h4>
                        <span class="refral">Max Referrals</span>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="bg-white p-3 rounded-4 border">
                        <h4>{{ $availableReferrals }}</h4>
                        <span class="refral">Max Available</span>
                    </div>
                </div>
                <div class="col-lg-4">
                    <a href="javascript:;" data-bs-toggle="modal" data-bs-target="#exampleModal"
                        class="refral">How it works?</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row justify-content-center mt-4">
        <div class="col-lg-10">
            <div class="nav nav-pills justify-content-between" id="refTabs">
                <button class="nav-link active py-3 m-0 rounded-3" data-bs-target="#tabRefer" data-bs-toggle="pill">
                    Refer Method
                </button>
                <button class="nav-link py-3 rounded-3" data-bs-target="#tabYourRef" data-bs-toggle="pill">
                    Your Referrals
                </button>
                <button class="nav-link py-3 rounded-3" data-bs-target="#tabCompleted" data-bs-toggle="pill">
                    Completed
                </button>
            </div>

            <div class="tab-content p-4 bg-white border rounded-4 mt-3">

                <!-- TAB 1 -->
                <div class="tab-pane fade show active" id="tabRefer">
                    <div class="row jusitify-content-center">
                        <div class="col-lg-6">
                            <h5 class="fw-bold mb-2">Refer by Code</h5>
                            <div class="d-flex p-3 border rounded-4 justify-content-between align-items-center mb-3">
                                <span class="fw-bold fs-5">{{ $referralCode }}</span>
                                <button class="btn btn-outline-primary btn-sm copy"
                                    data-copy="{{ $referralCode }}">
                                    <i class="fa fa-copy"></i>
                                </button>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <h5 class="fw-bold mb-2">Refer by Link</h5>
                            <div class="d-flex p-3 border rounded-4 justify-content-between align-items-center mb-3">
                                <span class="text-truncate">{{ $referralLink }}</span>
                                <button class="btn btn-outline-primary btn-sm copy"
                                    data-copy="{{ $referralLink }}">
                                    <i class="fa fa-copy"></i>
                                </button>
                            </div>
                        </div>

                        <div class="col-lg-12">
                            <div class="text-center mt-3">
                                <img
                                    src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data={{ urlencode($referralLink) }}"
                                    class="rounded-4 p-3 shadow-sm">
                            </div>
                            <h5 class="fw-bold mb-2 mt-2 text-center">Refer by QR</h5>
                        </div>
                    </div>
                </div>

                <!-- TAB 2 -->
                <div class="tab-pane fade" id="tabYourRef">
                    <div class="row">
                        <h5 class="fw-bold mb-3">Your Referrals</h5>

                        @if($yourReferrals->count())
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Referral Code</th>
                                    <th>Method</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($yourReferrals as $ref)
                                <tr>
                                    <td>{{ $ref->referral_code }}</td>
                                    <td>{{ ucfirst($ref->referral_type) }}</td>
                                    <td><span class="badge bg-warning">Pending</span></td>
                                    <td>{{ \Carbon\Carbon::parse($ref->created_at)->format('d M Y') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @else
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-people fs-1 mb-2"></i>
                            <p>No new referrals yet</p>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- TAB 3 -->
                <div class="tab-pane fade" id="tabCompleted">
                    <div class="row">
                        <h5 class="fw-bold mb-3">Completed Referrals</h5>

                        @if($completedList->count())
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Referral Code</th>
                                    <th>Method</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($completedList as $ref)
                                <tr>
                                    <td>{{ $ref->referral_code }}</td>
                                    <td>{{ ucfirst($ref->referral_type) }}</td>
                                    <td>{{ \Carbon\Carbon::parse($ref->updated_at)->format('d M Y') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @else
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-check2-circle fs-1 mb-2"></i>
                            <p>No completed referrals yet</p>
                        </div>
                        @endif
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
<!-- Toast (Bottom Right) -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 9999;">
    <div id="copyToast" class="toast text-white bg-success border-0" role="alert">
        <div class="d-flex">
            <div class="toast-body">
                Copied successfully!
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>


{{-- <div class="container">
    <h4 class="mb-4">Refer Another Library</h4> --}}

{{-- Referral Summary --}}
{{-- <div class="row mb-4">
        <div class="col-md-3">
            <div class="card p-3">Total Referrals <br><b>{{ $total }}</b></div>
</div>
<div class="col-md-3">
    <div class="card p-3">Completed <br><b>{{ $completed }}</b></div>
</div>
<div class="col-md-3">
    <div class="card p-3">Pending <br><b>{{ $pending }}</b></div>
</div>
</div> --}}

{{-- Referral Code --}}
{{-- <div class="card mb-3">
        <div class="card-body">
            <h6>Referral Code</h6>
            <input type="text" class="form-control" value="{{ auth()->user()->referral_code }}" readonly>
</div>
</div> --}}

{{-- Referral Link --}}
{{-- <div class="card mb-3">
        <div class="card-body">
            <h6>Referral Link</h6>
            <input type="text" class="form-control" value="{{ url('/library/register?ref='.auth()->user()->referral_code) }}" readonly>
</div>
</div> --}}

{{-- QR Code --}}
{{-- <div class="card mb-3">
        <div class="card-body text-center">
            <h6>Referral QR Code</h6>
            {!! QrCode::size(180)->generate(url('/library/register?ref='.auth()->user()->referral_code)) !!}
        </div>
    </div> --}}
{{-- </div> --}}
<script>
    document.querySelectorAll('.copy').forEach(btn => {
        btn.addEventListener('click', function() {
            navigator.clipboard.writeText(this.dataset.copy);
            new bootstrap.Toast(document.getElementById('copyToast')).show();
        });
    });
</script>
{{-- <script>
    $(".copy").on("click", function() {
        let textToCopy = $(this).text().trim();

        // Try modern clipboard API
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(textToCopy).then(() => {
                showCopyToast();
            }).catch(err => {
                fallbackCopy(textToCopy);
            });
        } else {
            // Fallback for HTTP or unsupported browsers
            fallbackCopy(textToCopy);
        }
    });

    function fallbackCopy(text) {
        let temp = $("<textarea>");
        $("body").append(temp);
        temp.val(text).select();
        document.execCommand("copy");
        temp.remove();

        showCopyToast();
    }

    function showCopyToast() {
        let toastEl = $("#copyToast");
        let toast = new bootstrap.Toast(toastEl[0]);
        toast.show();
    }
</script> --}}
@endsection