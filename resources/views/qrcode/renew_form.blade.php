<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Responsive Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet">
    <link rel="stylesheet" href="http://localhost/genrate/public/css/home-style.css">

    <style>
        .process-step-1 {
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

        .process-step-1 span.footer {
            font-size: .8rem;
        }

        input.btn.btn-primary {
            background: #18225f;
            border-color: #18225f;
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
                                <h4>Welcome to Libraro</h4>
                                <span class="text-message">Please enter your Mobile Number to Proceed..</span>
                            </div>
                            <form method="POST" action="{{ route('renew.find', $branch->uuid) }}">
                                @csrf
                                <div class="row g-4 ">
                                    <input type="hidden" value="{{$branch->id}}" name="branch">
                                    <div class="col-lg-12">
                                        <input type="text" class="form-control @error('mobile') is-invalid @enderror" placeholder="Enter your mobile Number" name="mobile">
                                        @error('mobile')  
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span> 
                                        @enderror
                                    </div>
                                    <div class="col-lg-12 text-center">
                                        <input type="submit" class="btn btn-primary" value="NEXT">
                                    </div>
                                </div>
                            </form>
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