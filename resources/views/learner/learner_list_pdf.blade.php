<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Learner List</title>
    <style>
        @page {
            margin: 15px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #222;
        }

        h2 {
            text-align: center;
            margin: 0 0 4px 0;
        }

        .generated-on {
            text-align: center;
            font-size: 10px;
            color: #666;
            margin-bottom: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #999;
            padding: 5px 6px;
            text-align: left;
            vertical-align: middle;
        }

        th {
            background-color: #2c3e50;
            color: #fff;
            font-size: 11px;
        }

        tr:nth-child(even) {
            background-color: #f5f5f5;
        }

        .photo-cell {
            width: 160px;
            text-align: center;
        }

        .photo-cell img {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border: 1px solid #ccc;
        }

        .status-active {
            color: #1a7a1a;
            font-weight: bold;
        }

        .status-aboutToExpire {
            color: #b8860b;
            font-weight: bold;
        }

        .status-extedned {
            color: #b5560c;
            font-weight: bold;
        }

        .status-expired {
            color: #b02020;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <h2>Learner List</h2>
    <p class="generated-on">Generated on {{ date('j M Y, h:i A') }} &nbsp;|&nbsp; Total Records: {{ $learners->count() }}</p>

    <table>
        <thead>
            <tr>
                <th>Seat No</th>
                <th>Photo</th>
                <th>Name</th>
                <th>Mobile</th>
                <th>Plan</th>
                <th>Plan Type</th>
                <th>Plan Start Date</th>
                <th>Plan End Date</th>
                <th>Status</th>
                <th>Pending Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse($learners as $value)
            @php
                $planStatus = getPlanStatusDetails($value->plan_end_date);
                $transaction = learnerTransaction($value->id, $value->learner_detail_id);
                $pendingAmount = optional($transaction)->pending_amount;

                $photoData = null;
                if (!empty($value->profile_picture) && file_exists(base_path($value->profile_picture))) {
                    $photoPath = base_path($value->profile_picture);
                } else {
                    $photoPath = public_path('img/student_profile.jpeg');
                }
                if (file_exists($photoPath)) {
                    $photoMime = mime_content_type($photoPath) ?: 'image/jpeg';
                    $photoData = 'data:' . $photoMime . ';base64,' . base64_encode(file_get_contents($photoPath));
                }
            @endphp
            <tr>
                <td>{{ $value->seat_no ? getSeatDisplayByMainNo($value->seat_no) : 'GEN' }}</td>
                <td class="photo-cell">
                    @if($photoData)
                    <img src="{{ $photoData }}" alt="photo">
                    @endif
                </td>
                <td>{{ $value->name }}</td>
                <td>{{ $value->mobile }}</td>
                <td>{{ $value->plan_name ?? '' }}</td>
                <td>{{ $value->plan_type_name ?? '' }}</td>
                <td>{{ $value->plan_start_date ? date('j M Y', strtotime($value->plan_start_date)) : '' }}</td>
                <td>{{ $value->plan_end_date ? date('j M Y', strtotime($value->plan_end_date)) : '' }}</td>
                <td class="status-{{ $planStatus['class'] }}">{{ $planStatus['status'] }}</td>
                <td>{{ $pendingAmount !== null ? number_format($pendingAmount, 2) : '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="10" style="text-align:center;">No learners found for the selected filters.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</body>

</html>
