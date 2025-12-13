@extends('layouts.library')

@section('content')
<style>
    .font-family {
        font-family: 'Outfit', 'sans-sarif';
        font-weight: 400;
    }

    .refer-and-earn-main .refer-and-earn {
        background: #fff;
        padding: 0 1.5rem;
        border: 1px solid #dedede;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-radius: 1rem;
    }

    .refer-and-earn-main .refer-and-earn img {
        width: 400px;
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

    .refer-and-earn-main button.nav-link.px-3.py-2.active {
        background: #18225f !important;
        color: #fff;
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
        .refer-and-earn{
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
                <img src="{{ asset('public/img/refer-earn.png')}}" alt="Refer & Earn">
            </div>
        </div>
    </div>

    <div class="row  justify-content-center mt-4 text-center">
        <div class="col-lg-10">
            <div class="row g-4 justify-content-center">
                <div class="col-lg-4">
                    <div class="bg-white p-3 rounded-4 border">
                        <h4>10</h4>
                        <span class="refral">Max Referrals</span>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="bg-white p-3 rounded-4 border">
                        <h4>5</h4>
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
                <button class="nav-link active py-2 m-0" data-bs-target="#tabRefer" data-bs-toggle="pill">
                    Refer Method
                </button>
                <button class="nav-link py-2" data-bs-target="#tabYourRef" data-bs-toggle="pill">
                    Your Referrals
                </button>
                <button class="nav-link py-2" data-bs-target="#tabCompleted" data-bs-toggle="pill">
                    Completed
                </button>
            </div>
            <!-- Tab Content -->
            <div class="tab-content p-4 bg-white border rounded-4 mt-3">

                <!-- TAB 1: Refer Methods -->
                <div class="tab-pane fade show active" id="tabRefer">
                    <div class="row jusitify-content-center">
                        <div class="col-lg-6">
                            <h5 class="fw-bold mb-2">Refer by Code</h5>
                            <div
                                class="d-flex p-3 border rounded-4 justify-content-between align-items-center mb-3">
                                <span class="fw-bold fs-5">REF12345</span>
                                <button class="btn btn-outline-primary btn-sm copy"><i
                                        class="fa fa-copy"></i></button>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <h5 class="fw-bold mb-2">Refer by Link</h5>
                            <div
                                class="d-flex p-3 border rounded-4 justify-content-between align-items-center mb-3">
                                <span class="text-truncate">https://yourapp.com/ref/REF12345</span>
                                <button class="btn btn-outline-primary btn-sm copy"><i
                                        class="fa fa-copy"></i></button>
                            </div>
                        </div>
                        <div class="col-lg-12">
                            <div class="text-center mt-3">
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=REF12345"
                                    class="rounded-4 p-3 shadow-sm">
                            </div>
                            <h5 class="fw-bold mb-2 mt-2 text-center">Refer by QR</h5>

                        </div>
                    </div>
                </div>

                <!-- TAB 2: Your Referrals -->
                <div class="tab-pane fade" id="tabYourRef">
                    <div class="row">
                        <h5 class="fw-bold mb-3">Your Referrals</h5>

                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-people fs-1 mb-2"></i>
                            <p>No new referrals yet</p>
                        </div>
                    </div>
                </div>

                <!-- TAB 3: Completed Referrals -->
                <div class="tab-pane fade" id="tabCompleted">
                    <div class="row">
                        <h5 class="fw-bold mb-3">Completed Referrals</h5>

                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-check2-circle fs-1 mb-2"></i>
                            <p>No completed referrals yet</p>
                        </div>
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
<script>
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
</script>
@endsection