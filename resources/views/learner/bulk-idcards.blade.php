<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LIBRARY ID CARD</title>
    <link rel="icon" href="{{ asset('public/img/favicon.ico') }}" type="image/x-icon">
  <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&display=swap');

        @page {
            size: A4 portrait;
            margin: 5mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Outfit', sans-serif;
            background: #f5f5f5;
        }

        @media print {
            body {
                background: white;
                padding: 0;
                margin: 0;
            }

            .no-print {
                display: none !important;
            }

            .print-container {
                width: 100%;
                height: 100vh;
                display: block;
            }
        }


        .print-container {
            width: 210mm;
            max-width: 100%;
            margin: 0 auto;
            background: white;
            padding: 10mm;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            grid-template-rows: repeat(4, 63.8mm);
            gap: 5mm;
            min-height: 277mm;
        }

        .card {
            border: 1px solid #dedede;
            display: flex;
            align-items: center;
            font-weight: 700;
            height: 63.8mm;
            width: 100%;
            page-break-inside: avoid;
            position: relative;
            flex-direction: column;
            align-items: flex-start;
            border-radius: .5rem;
            overflow: hidden;
        }

        .card.back,
        .card.front {
            background: url('../public/img/bg-id-card.webp');
            background-size: cover;
            background-repeat: no-repeat;
        }


        /* Fallback for browsers that don't support CSS Grid */
        @supports not (display: grid) {
            .print-container {
                display: flex;
                flex-wrap: wrap;
                justify-content: space-between;
            }

            .card {
                width: calc(50% - 2.5mm);
                margin-bottom: 5mm;
            }
        }

        .preview-note {
            text-align: center;
            margin-top: 20px;
            font-style: italic;
            color: #666;
        }
    </style>

    <style>
        .profiile {
            display: flex;
            gap: 1rem;
            width: 100%;
            padding: .4rem .8rem;
        }

        .seattt {
            display: flex;
            justify-content: center;
            flex-direction: column;
        }

        .seattt span {
            font-size: .8rem;
            display: block;
            text-align: center;
            font-weight: 500;
        }

        .profiile img {
            width: 50px;
            height: 50px;
            border-radius: 50px;
            background: #ababab;
        }

        .profiile .iiinfo {
            padding: 0;
            margin: 0;
            width: 100%;
        }

        .profiile .iiinfo h4 {
            font-size: 1rem;
            margin: 0;
            color: maroon;
        }

        .profiile .iiinfo ul {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            gap: 1rem;
            justify-content: space-between;
        }

        .profiile .iiinfo ul li span {
            font-size: .8rem;
            font-weight: 400;
            color: #ababab;
        }

        .profiile .iiinfo ul li p {
            font-size: .9rem;
            margin: 0;
            padding: 0;
        }

        .plaanInfo {
            padding: .4rem .8rem;
            border-top: 1px solid #dedede;
            width: 100%;
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            justify-content: space-between;
            flex: 1;
        }



        .plaanInfo ul {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
            gap: 1rem;
            justify-content: space-between;
            flex: 1;
        }

        .plaanInfo ul li span {
            font-size: .8rem;
            font-weight: 400;
            color: #ababab;
        }

        .plaanInfo ul li p {
            font-size: .9rem;
            margin: 0;
            padding: 0;
        }

        .barcode {
            width: 85px;
            height: 85px;
            border: 1px solid #dedede;
            border-radius: .5rem;
            margin-top: .4rem;
        }

        .library-name {
            text-align: center;
            font-size: .8rem;
            color: navy;
            font-weight: 600;
            width: 100%;
            /* border-top: 1px solid #dedede; */
            display: flex;
            justify-content: center;
            align-items: center;
            /* background: #eef1ff; */
            padding: .5rem 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;

        }

        .library-innnfoo h4 {
            margin: 0;
        }

        .library-innnfoo {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            justify-content: center;
            height: 100%;
        }

        .library-innnfoo ul {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            justify-content: center;
            flex-direction: column;
            align-items: center;
            gap: .5rem;
        }

        .library-innnfoo ul li {
            text-align: center;
            font-size: .9rem;
            font-weight: 500;
        }

        .truncate_name {
            width: 250px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .truncate_fname {
            width: 140px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>
</head>

<body>
     <div class="print-container">
        @foreach($learner_details as $key => $learner_detail)
            
        
        <div class="card front">
            <div class="profiile">
                <div class="seattt">
                    <img src="{{ $learner_detail->learner->profile_picture ? asset($learner_detail->learner->profile_picture) : 'https://placehold.co/600x400'}}" alt="profile">
                    <span>Seat {{ $learner_detail->seat_no ?? 'GEN'}}</span>
                </div>
                <div class="iiinfo">
                    <h4 class="truncate_name">{{ $learner_detail->learner->name}}</h4>
                    <ul>
                        <li>
                            <span>Father name</span>
                            <p class="m-0 truncate_fname">{{ $learner_detail->learner->father_name ?? ''}}</p>
                        </li>
                        <li>
                            <span>Mobile</span>
                            <p class="m-0">91-{{ $learner_detail->learner->mobile ?? ''}}</p>
                        </li>
                    </ul>
                </div>

            </div>
            <div class="plaanInfo">
                <ul>
                    <li><span>Plan Start On</span>
                        <p class="m-0">{{ $learner_detail->plan_start_date ?? ''}}</p>
                    </li>
                    <li><span>Ends On</span>
                        <p class="m-0">{{ $learner_detail->plan_end_date ?? ''}}</p>
                    </li>
                </ul>
                <div class="barcode">{!! QrCode::size(100)->generate($learner_detail->learner->learner_no) !!}</div>
            </div>
            <div class="library-name">{{$branch->display_name ?? $branch->name}}</div>
        </div>
        <div class="card back">
            <div class="library-innnfoo">
                <h4>Library Info</h4>
                <ul>
                    <li>Address : {{$branch->library_address ?? ''}} {{$branch->city->city_name ?? ''}}, {{$branch->state->state_name ?? ''}}, {{$branch->state->library_zip ?? ''}}</li>
                    <li>Contact Number : {{$branch->mobile ?? ''}}</li>
                    <li>Email Id : {{$branch->email ?? ''}}</li>
                </ul>
            </div>
        </div>
        @endforeach
     </div>
    {{-- <div class="receipt_wrapper">
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
            
        <span class="d-block ">Seat No : {{ $learner_detail->seat_no}}</span>
        
        <p>{{ $learner_detail->plan->name}}</p>
        <p>{{ $learner_detail->plan_start_date}}</p>
        <p>{{ $learner_detail->plan_end_date}}</p>
        <button class="mb-3"> Booked for <b>{{ $learner_detail->planType->name}}</b></button>
        </div>
    </div> --}}
</body>
 <script>
        function printCards() {
            // Small delay to ensure the click event completes
            setTimeout(() => {
                try {
                    window.print();
                } catch (e) {
                    alert('Print function not available. Please use Ctrl+P (Windows) or Cmd+P (Mac) to print.');
                }
            }, 100);
        }

        function openPrintPreview() {
            // For browsers that support it, open print preview
            try {
                window.print();
            } catch (e) {
                alert('Please use your browser\'s print function (Ctrl+P or Cmd+P) to see print preview.');
            }
        }

        // Alternative print methods for different browsers
        document.addEventListener('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                e.preventDefault();
                printCards();
            }
        });
    </script>
</html>
