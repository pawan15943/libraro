@extends('sitelayouts.layout')
@section('content')

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
