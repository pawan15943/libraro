

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library managment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet">
    <link rel="stylesheet" href="http://localhost/genrate/public/css/home-style.css">

    <style>
        .process-step-1,
        .process-step-2 {
            height: 100vh;
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
</head>

<body>

    <div class="sacnd-data">
        <div class="container">
            <h4>Renew Plan for {{ $customer->name }}</h4>

<div class="card p-3">
    <div class="row">
        <div class="col-md-6"><strong>Mobile:</strong> {{ $customer->mobile }}</div>
        <div class="col-md-6"><strong>Seat No:</strong> {{ $customer->seat_no ?? 'N/A' }}</div>

        <div class="col-md-6"><strong>Current Plan:</strong> {{ $customer_detail->plan->name ?? 'N/A' }}</div>
        <div class="col-md-6"><strong>Plan Type:</strong> {{ $customer_detail->planType->name ?? 'N/A' }}</div>

        <div class="col-md-6"><strong>Start Date:</strong> {{ $customer_detail->plan_start_date }}</div>
        <div class="col-md-6"><strong>End Date:</strong> {{ $customer_detail->plan_end_date }}</div>
    </div>
</div>

@if($transaction)
<hr>
<h5>💰 Last Transaction Summary</h5>
<table class="table table-bordered">
    <thead>
        <tr>
            <th>Plan Price</th>
            <th>Locker</th>
            <th>Discount</th>
            <th>Total</th>
            <th>Paid</th>
            <th>Pending</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>{{ $transaction->plan_price }}</td>
            <td>{{ $transaction->locker_amount }}</td>
            <td>{{ $transaction->discount_amount }}</td>
            <td>{{ $transaction->total_amount }}</td>
            <td>{{ $transaction->paid_amount }}</td>
            <td>{{ $transaction->pending_amount }}</td>
        </tr>
    </tbody>
</table>

<div class="alert alert-info text-center">
    <h5>Amount to Pay: 
        <strong>{{ $transaction->total_amount }}</strong>
    </h5>
</div>
@endif
            <!-- resources/views/booking/form.blade.php -->
            <form action="{{ route('booking.store', $branch->uuid) }}" method="POST">
               @csrf

        {{-- Required fields for validation --}}
        <input type="hidden" name="renewal" value="1">
        <input type="hidden" name="name" value="{{ $customer->name }}">
        <input type="hidden" name="email" value="{{ $customer->email }}">
        <input type="hidden" name="mobile" value="{{ $customer->mobile }}">
       
        <input type="hidden" name="dob" value="{{ $customer->dob }}">

        <input type="hidden" name="plan_id" value="{{ $customer_detail->plan_id }}">
        <input type="hidden" name="plan_type_id" value="{{ $customer_detail->plan_type_id }}">
        <input type="hidden" name="plan_price_id" value="{{ $transaction->plan_price_id }}">

        {{-- New start & end date (maybe extended 1 month or per plan rules) --}}
        <input type="hidden" name="plan_start_date" value="{{ \Carbon\Carbon::parse($customer_detail->plan_end_date)->addDay()->toDateString() }}">
        

        <input type="hidden" name="payment_mode" value="online"> {{-- default or choose --}}

        {{-- Internal tracking --}}
        <input type="hidden" name="learner_detail_id" value="{{ $customer_detail->id }}">
        <input type="hidden" name="learner_transaction_id" value="{{ $transaction->id }}">

        <button type="submit" class="btn btn-success">Confirm Renewal</button>
            </form>

        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
   


</body>

</html>
