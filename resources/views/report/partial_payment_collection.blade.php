@extends('layouts.library')
@section('content')

<!-- Content Header (Page header) -->
@php
use Carbon\Carbon;
$currentYear = date('Y');
$currentMonth = date('m');
$today = \Carbon\Carbon::today();


@endphp
@can('has-permission','Partial Payment Report')
<div class="row">

    <div class="col-lg-12">
        <div class="filter-box">
            <h4 class="mb-3">Filter Box</h4>

            <form action="{{ route('partial.payment.collection.report') }}" method="GET">
                <div class="row g-4">

                    <div class="col-lg-2">
                        <label for="year">Filter By Year</label>
                        <select id="year" class="form-select " name="year">
                            <option value="">Select Year</option>
                            @foreach($dynamicyears as $year)

                            <option value="{{ $year }}"
                                {{ (request('year') ?? $currentYear) == $year ? 'selected' : '' }}>
                                {{ $year }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-2">
                        <label for="month">Select Month:</label>
                        <select id="month" class="form-select " name="month">
                            <option value="">Select Month</option>
                            @foreach($dynamicmonths as $month)
                            <option value="{{ str_pad($month, 2, '0', STR_PAD_LEFT) }}"
                                {{ request('month') == str_pad($month, 2, '0', STR_PAD_LEFT) ? 'selected' : '' }}>
                                {{ DateTime::createFromFormat('!m', $month)->format('M') }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                </div>

                <div class="row mt-3">
                    <div class="col-lg-2">
                        <button class="btn btn-primary button">
                            <i class="fa fa-search"></i> Search Records
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="row mb-4 mt-4">

    <div class="col-lg-12">
        <div id="export" class="mb-3"></div>
        <div class="table-responsive ">
            <table class="table text-center datatable border-bottom" id="datatable">
                <thead>
                    <tr>
                        <th class="d-none"></th>
                        <th class="d-none"></th>
                        <th class="d-none"></th>
                        <th class="d-none"></th>
                        <th class="d-none"></th>
                        <th class="d-none"></th>
                        <th class="d-none"></th>
                        <th class="d-none"></th>
                        <th class="d-none"></th>
                        <th class="d-none"></th>
                        <th class="merged-display">Seat No.</th>
                        <th class="merged-display">Learner Name</th>
                        <th class="merged-display">Contact Info</th>
                        <th class="merged-display">Pending Amt</th>
                        <th class="merged-display">Due Date</th>
                        <th class="merged-display">Payment Status</th>
                        <th class="merged-display">Payment Mode</th>
                        <th class="merged-display">Payment Date</th>
                        <th class="merged-display">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($learners as $value)
                    @php
                    $dueDate = \Carbon\Carbon::parse($value->due_date);
                    @endphp
                    <tr>

                        <td class="d-none export-seat-no">{{ getSeatDisplayByMainNo($value->seat_no) ?? "GEN" }}</td>
                        <td class="d-none export-name">{{ $value->name ?? '' }}</td>
                        <td class="d-none export-email">{{ $value->email ?? 'Email ID Not Available' }}</td>
                        <td class="d-none export-mobile">
                            {{$value->mobile ? decryptData($value->mobile) : ''}}

                        </td>
                        <td class="d-none export-pending-amount">{{ $value->pending_amount ?? '' }}</td>
                        <td class="d-none export-due-date">{{ $value->due_date ?? '' }}</td>
                        <td class="d-none export-our-due">
                            @if($value->status != 1 && $dueDate->lt($today))
                            @php
                            $overdueDays = $today->diffInDays($dueDate);
                            @endphp
                            {{ $overdueDays }} day{{ $overdueDays > 1 ? 's' : '' }}
                            @endif

                        </td>
                        <td class="d-none export-payment-status"> @if($value->status == 1)
                            Paid
                            @else
                            Unpaid
                            @endif</td>
                        <td class="d-none export-payment-mode">{{ $value->payment_mode ?? 'Not Yet'}}</td>
                        <td class="d-none export-paid-date">{{ $value->paid_date ?? 'Not Paid Yet'}}</td>

                        <td class="merged-display">{{getSeatDisplayByMainNo($value->seat_no) ?? 'General'}}</td>
                        <td class="merged-display" class="uppercase"><span class="uppercase truncate name my-0" data-bs-toggle="tooltip"
                                data-bs-title="{{$value->name}}" data-bs-placement="bottom">{{$value->name}}</span></td>
                        <td class="merged-display"><span class="truncate">
                                {!! $value->email ? decryptData($value->email) : '<i class="fa-solid fa-times text-danger"></i> Email ID Not Available' !!}
                            </span> <br>
                            <small> +91-{{decryptData($value->mobile)}}</small>
                        </td>

                        <td class="merged-display">₹ {{$value->pending_amount}} </td>

                        <td class="merged-display">
                            {{ $value->due_date }}

                            @if($value->status != 1 && $dueDate->lt($today))
                            @php
                            $overdueDays = $today->diffInDays($dueDate);
                            @endphp
                            <br>
                            <small class="text-danger">{{ $overdueDays }} day{{ $overdueDays > 1 ? 's' : '' }} overdue</small>
                            @endif
                        </td>
                        <td class="merged-display">
                            @if($value->status == 1)
                            <span class="text-success">Paid</span>
                            @else
                            <span class="text-warning">Unpaid</span>
                            @endif
                        </td>
                        <td class="merged-display">{{ $value->payment_mode ?? 'Not Yet'}} </td>
                        <td class="merged-display">{{ $value->paid_date ?? 'Not Paid Yet'}} </td>
                        <td class="merged-display">
                            <ul class="actionalbls">
                                @can('has-permission', 'WhatsApp Notification')
                                <li>
                                   

                                    <a class="w-auto d-inline-flex align-items-center gap-1"
                                    target="_blank"
                                    href="https://wa.me/{{ decryptData($value->mobile) }}?text={{ rawurlencode(
                                        'Dear ' . $value->name . "\n\n" .
                                        'This is a gentle reminder that your library seat payment is still pending.' . "\n\n" .
                                        'Your due date was ' . \Carbon\Carbon::parse($value->due_date)->format('d-m-Y') . '. To avoid seat cancellation, please complete the payment at the earliest.' . "\n\n" .
                                        'If you have already made the payment, kindly ignore this message.' . "\n\n" .
                                        'For any assistance, feel free to contact our support team.' . "\n\n" .
                                        '– Team ' . getCurrentBranchName()
                                    ) }}">
                                        <i class="fab fa-whatsapp text-success"
                                        data-bs-placement="bottom"
                                        data-bs-toggle="tooltip"
                                        data-bs-title="Send Pending Payment Reminder"
                                        style="font-size: .1rem;"></i>
                                    </a>



                                </li>

                                @endcan
                            </ul>
                        </td>

                    </tr>
                    @endforeach

                </tbody>


            </table>


        </div>
    </div>
</div>
@else
<div class="card text-center">
    <span class="text-danger">You don't have Permission to View Partial Payment Report.</span>
</div>
@endcan
<script>
    $(document).ready(function() {
        var table = $('#datatable').DataTable({

            buttons: [{
                extend: 'csvHtml5',
                text: 'Export CSV',
                exportOptions: {
                    columns: function(idx, data, node) {
                        return $(node).hasClass('d-none'); // export only hidden columns
                    },
                    format: {
                        body: function(data) {
                            return data.replace(/<[^>]+>/g, '').replace(/&nbsp;/g, ' ').trim();
                        },
                        header: function(data, columnIdx) {
                            const headers = [
                                'Seat No',
                                'Name',
                                'Email',
                                'Mobile',
                                'Pending Amount',
                                'Due date',
                                'Over Due',
                                'Payment Status',
                                'Payment Mode',
                                'Payment Date',
                            ];
                            return headers[columnIdx] ?? '';
                        }
                    }
                },
                title: 'PartialPaymentCollectionReport'

            }],
            columnDefs: [{
                    targets: 'export-seat-no',
                    visible: false
                },
                {
                    targets: 'export-name',
                    visible: false
                },
                {
                    targets: 'export-email',
                    visible: false
                },
                {
                    targets: 'export-mobile',
                    visible: false
                },
                {
                    targets: 'export-pending-amount',
                    visible: false
                },
                {
                    targets: 'export-due-date',
                    visible: false
                },
                {
                    targets: 'export-our-due',
                    visible: false
                },
                {
                    targets: 'export-payment-status',
                    visible: false
                },
                {
                    targets: 'export-payment-mode',
                    visible: false
                },
                {
                    targets: 'export-paid-date',
                    visible: false
                },
                {
                    targets: 'merged-display',
                    visible: true
                }
            ],

            lengthMenu: [10, 25, 50, 100],
            pageLength: 10
        });
        // Move export button to a custom container
        table.buttons().container().appendTo('#export');
    });
</script>


@endsection