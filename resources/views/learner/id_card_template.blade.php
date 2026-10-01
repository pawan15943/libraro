<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LIBRARY ID CARD - {{ $learner_detail->learner->name ?? 'Learner' }}</title>
    <link rel="icon" href="{{ asset('public/img/favicon.ico') }}" type="image/x-icon">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- FontAwesome 6 Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" />

    <!-- Dedicated ID Card Stylesheet -->
    <link rel="stylesheet" href="{{ asset('public/css/id-card.css') }}?v={{ time() }}">

    <style>
        @page {
            size: A4 portrait;
            margin: 5mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Outfit', sans-serif;
            background: #f8fafc;
            color: #1e293b;
        }

        .card.back,
        .card.front {
            background-image: url('{{ asset('public/img/bg-id-card.webp') }}');
            background-size: cover;
            background-repeat: no-repeat;
            background-position: center;
        }
    </style>
</head>

@php
    $start = $learner_detail->planType->start_time ?? null;
    $end   = $learner_detail->planType->end_time ?? null;

    $startTime = $start ? \Carbon\Carbon::parse($start)->format('h:i A') : '';
    $endTime   = $end ? \Carbon\Carbon::parse($end)->format('h:i A') : '';
@endphp

<body>
    <div class="learner-idcard-module">
        <!-- Sticky Action Bar for Mobile & Desktop -->
        <header class="id-card-action-bar no-print">
            <div class="action-bar-inner">
                <button type="button" class="action-btn back-btn" onclick="if(window.history.length > 1){ window.history.back(); } else { window.close(); }">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span>Back</span>
                </button>
                <h1 class="action-bar-title">
                    <i class="fa-solid fa-id-badge" style="color: #34939F;"></i>
                    <span>Learner ID Card</span>
                </h1>
                <button type="button" class="action-btn print-btn" onclick="printCards()">
                    <i class="fa-solid fa-print"></i>
                    <span>Print / PDF</span>
                </button>
            </div>
        </header>

        <!-- Main ID Cards Container -->
        <main class="id-card-screen-wrap">
            <div class="cards-grid-layout">
                <!-- Front Side Card -->
                <div class="card-column">
                    <div class="card-side-tag no-print">
                        <i class="fa-regular fa-id-card"></i> FRONT SIDE
                    </div>
                    <div class="card front">
                        <div class="profiile">
                            <div class="seattt">
                                <img src="{{ $learner_detail->learner->profile_picture ? asset($learner_detail->learner->profile_picture) : 'https://placehold.co/600x400'}}" alt="profile">
                                <span>Seat {{ getSeatDisplayShortFloor($learner_detail->seat_no) ?? 'GEN'}}</span>
                            </div>
                            <div class="iiinfo">
                                <h4 class="truncate_name">UID : {{ $learner_detail->learner->learner_no}}</h4>
                                <ul>
                                    <li>
                                        <span>Full name</span>
                                        <p class="m-0 truncate_fname">{{ $learner_detail->learner->name}}</p>
                                    </li>
                                    <li>
                                        <span>Mobile No</span>
                                        <p class="m-0">+91-{{ $learner_detail->learner->mobile ?? ''}}</p>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <div class="plaanInfo">
                            <ul>
                                <li>
                                    <span>Plan Start On</span>
                                    <p class="m-0">
                                        {{ $learner_detail->plan_start_date 
                                            ? \Carbon\Carbon::parse($learner_detail->plan_start_date)->format('d-m-Y') 
                                            : '' 
                                        }}
                                    </p>
                                </li>
                                <li>
                                    <span>Ends On</span>
                                    <p class="m-0">
                                        {{ $learner_detail->plan_end_date 
                                            ? \Carbon\Carbon::parse($learner_detail->plan_end_date)->format('d-m-Y') 
                                            : '' 
                                        }}
                                    </p>
                                </li>
                                <li class="w-100">
                                    <span>Shift</span>
                                    <p class="m-0">{{ $startTime }} to {{ $endTime }}</p>
                                </li>
                            </ul>
                            <div class="barcode pe-1">
                                {!! QrCode::size(100)->generate(generateLearnerQrPayload($branch->library_id ?? $learner_detail->branch_id, $learner_detail->learner->learner_no)) !!}
                            </div>
                        </div>

                        <div class="library-name">{{$branch->display_name ?? $branch->name}}</div>
                    </div>
                </div>

                <!-- Back Side Card -->
                <div class="card-column">
                    <div class="card-side-tag no-print">
                        <i class="fa-solid fa-rotate"></i> BACK SIDE
                    </div>
                    <div class="card back">
                        <div class="library-innnfoo">
                            <h4><i class="fa-solid fa-building-columns" style="color: #34939F;"></i> Library Info</h4>
                            <ul>
                                @if(!empty($branch->library_address) || !empty($branch->city?->city_name))
                                <li>
                                    <i class="fa-solid fa-location-dot"></i>
                                    <span>{{$branch->library_address ?? ''}} {{$branch->city->city_name ?? ''}}, {{$branch->state->state_name ?? ''}} {{$branch->state->library_zip ?? ''}}</span>
                                </li>
                                @endif
                                @if(!empty($branch->mobile))
                                <li>
                                    <i class="fa-solid fa-phone"></i>
                                    <span>Contact: +91-{{$branch->mobile}}</span>
                                </li>
                                @endif
                                @if(!empty($branch->email))
                                <li>
                                    <i class="fa-solid fa-envelope"></i>
                                    <span>{{$branch->email}}</span>
                                </li>
                                @endif
                            </ul>
                            <div class="id-card-terms">
                                <i class="fa-solid fa-shield-halved" style="color: #34939F;"></i>
                                <span>Non-transferable &bull; Return if found</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function printCards() {
            setTimeout(() => {
                try {
                    window.print();
                } catch (e) {
                    alert('Print function not available. Please use Ctrl+P (Windows) or Cmd+P (Mac) to print.');
                }
            }, 100);
        }

        document.addEventListener('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                e.preventDefault();
                printCards();
            }
        });
    </script>
</body>
</html>
