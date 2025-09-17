@extends('sitelayouts.layout')
@section('content')

<style>
    .process-step-1,
    .process-step-2 {
        display: flex;
        align-items: center;
        flex-direction: column;
        gap: 1rem;
        justify-content: space-between;
    }

    .sacnd-data {
        background: linear-gradient(2deg, #d6faff, transparent);
    }

    .sacnd-data h2 {
        font-size: 1.5rem;
        color: #0090ae;
    }

    .action-content {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 1rem;
    }

    .action-content span.text-message {
        color: #a1a1a1;
        font-size: .9rem;
    }

    .sacnd-data span.footer {
        font-size: .8rem;
    }

    input.btn.btn-primary {
        background: #18225f;
        border-color: #18225f;
    }

    ul.action-list {
        display: flex;
        flex-wrap: wrap;
        gap: 1.5rem;
        list-style: none;
        padding: 0;
        justify-content: space-between;
    }

    ul.action-list li {
        width: calc(100% / 2 - .75rem);

    }

    ul.action-list li a {
        text-decoration: none;
        display: block;
        text-align: center;
        padding: 2rem 2rem;
        background: #fff;
        box-shadow: 1px 0 5px #00000021;
        border-radius: 1rem;
        font-weight: 700;
    }

</style>




<div class="sacnd-data py-5" style="min-height: 500px; display:flex; align-items:center;">
    <div class="container">
        <div class="row justify-content-center align-items-center h-100">
            <div class="col-lg-3">
                <div class="process-step-1">
                    <div class="action-content">
                        <div class="headings text-center">
                            <h4 class="mb-4">What would you like to do?</h4>
                            <h2>{{ $branch->name }} - Library</h2>
                            <span class="text-message">Please select an option from the list below to
                                proceed.</span>
                        </div>
                        <ul class="action-list">
                            <li><a href="{{ route('booking.form', $branch->uuid) }}">Book<br> Seat</a></li>
                            <li><a href="{{ route('renew.form', $branch->uuid) }}">Re-New Seat</a></li>
                            <!-- <li><a href="">Upgrade Plan</a></li>
                                <li><a href="">Change Plan</a></li>
                                <li><a href="">Close Plan</a></li>
                                <li><a href="">Raise Complaint</a></li> -->
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>




@endsection
