<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Management Software Subscription Receipt</title>

    <style>
        body {
            font-size: 13px;
            color: #333;
            font-family: 'Roboto', sans-serif;
        }

        table, th, td {
            border: 1px solid black;
            border-collapse: collapse;
            padding: 10px 12px;
        }

        h2, h3, h4, h5, h6 {
            margin: 0px;
        }

        p {
            line-height: 22px;
            font-size: 13px;
        }

        b {
            color: #000;
        }

        .tab_title {
            font-size: 21px;
            font-family: 'PT Serif', serif;
        }

        .logo img {
            margin-top: 15px;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .receipt_header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-bottom: 30px;
        }

        .address_header h5 {
            color: #000;
            font-size: 15px;
            margin-bottom: 15px;
            font-weight: 500;
        }

        .address_header .address {
            max-width: 270px;
            margin-left: auto;
            font-size: 14px;
            line-height: 22px;
            margin-bottom: 15px;
        }

        .address_header a {
            color: #333;
            text-decoration: none;
        }

        .pdf_descContent li, .pdf_descContent p {
            line-height: 26px;
        }
    </style>
</head>

<body>
    <div class="receipt_wrapper">
        <!-- header -->
        <div class="receipt_header">
            <div class="logo">
                <img src="{{ asset('img/logo.png') }}" alt="Library Logo">
            </div>
            <div class="address_header text-right">
                <h5>Library Management System Headquarters:</h5>
                <div class="address">
                    123 Library Road, Knowledge City<br>
                    Near BookHub Station, Cityville, Countryland
                </div>
                <a href="www.librarysystem.com" title="Library System">Website: www.librarysystem.com</a><br>
                <a href="mailto:support@librarysystem.com" title="Library System">Email: support@librarysystem.com</a>
            </div>
        </div>

        <!-- Main content-->
        <div class="seat--info">
            
        <span class="d-block ">Seat No : {{ $learner->seat_no}}</span>
        
        <p>{{ $learner_detail->plan->name}}</p>
        <p>{{ $learner_detail->plan_start_date}}</p>
        <p>{{ $learner_detail->plan_end_date}}</p>
        <button class="mb-3"> Booked for <b>{{ $learner_detail->planType->name}}</b></button>
        </div>
    </div>
</body>

</html>
