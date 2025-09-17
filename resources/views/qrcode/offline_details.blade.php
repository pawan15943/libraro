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


<div class="sacnd-data" style="min-height: 500px; display:flex; align-items:center;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-4 text-center">
                <h2 class="text-success mb-3">Thank You for Registering!</h2>


                
                <p class="mb-3">Please visit the branch to confirm your booking.</p>
                
                <p>OR</p></br>
               

                <div class="border p-3 bg-white rounded-4">
                    <h4 class="mb-3" >{{ $booking->branch->name }}</h4>
                    <p class="m-">Address : {{ $booking->branch->library_address }}</p>
                    <p class="m-"> Call At : 91-{{ $booking->branch->mobile }}</p>
                </div>

            </div>
        </div>
    </div>
</div>

@endsection
