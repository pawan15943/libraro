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
            <div class="row justify-content-center align-items-center h-100">
                <div class="col-lg-3">
                    <div class="process-step-1">
                        <img src="http://localhost/genrate/public/img/libraro.svg" alt="logo" class="logo">
                        <div class="action-content">
                            <div class="headings text-center">
                                <h4>What would you like to do?</h4>
                                <h2>{{ $branch->name }} - Library</h2>
                                <span class="text-message">Please select an option from the list below to
                                    proceed.</span>
                            </div>
                            <ul class="action-list">
                                 <li><a href="{{ route('booking.form', $branch->uuid) }}">Book Seat</a></li>
                                <li><a href="{{ route('renew.form', $branch->uuid) }}">Re-New Plan</a></li>
                                <!-- <li><a href="">Upgrade Plan</a></li>
                                <li><a href="">Change Plan</a></li>
                                <li><a href="">Close Plan</a></li>
                                <li><a href="">Raise Complaint</a></li> -->
                            </ul>
                        </div>
                        <span class="footer">Copyright © 2025 Libraro.in. All Rights Reserved.</span>
                    </div>
                </div>
            </div>
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