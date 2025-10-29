@extends('layouts.library')
@section('content')

<!-- Content Header (Page header) -->
@php
use Carbon\Carbon;
$currentYear = date('Y');
$currentMonth = date('m');
@endphp

@if (session('error'))
<div class="alert alert-danger">
    {{ session('error') }}
</div>
@endif
@if (session('success'))
<div class="alert alert-success">
    {{ session('success') }}
</div>
@endif


<div class="row mb-4">
   
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
                        <th class="merged-display">Seat No.</th>
                        <th class="merged-display">Learner Info</th>
                        <th class="merged-display">Contact Info</th>
                        <th class="merged-display">Active Plan</th>
                        <th class="merged-display">Due date</th>
                        <th class="merged-display">Make Payment</th>
                        <th class="merged-display">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($learners as $value)
                    @foreach ($value->learnerDetails as $detail)
          
                    <tr>
                        <td class="d-none export-seat-no">{{getSeatDisplayByMainNo($value->seat_no) ?? "GEN" }}</td>
                        <td class="d-none export-plan-type">{{ $detail->planType->name ?? '' }}</td>
                        <td class="d-none export-name">{{ $value->name ?? '' }}</td>
                        <td class="d-none export-email">{{ $value->email ?? 'Email ID Not Available' }}</td>
                        <td class="d-none export-mobile">{{ $value->mobile ?? '' }}</td>
                        <td class="d-none export-start-date">{{ $detail->plan_start_date ?? '' }}</td>
                        <td class="d-none export-plan-name">{{ $detail->plan->name ?? '' }}</td>
                        <td class="d-none export-end-date">{{ $detail->plan_end_date }}</td>
                        <td class="d-none export-expiry-status">
                         {!! getUserStatusDetails($detail->plan_end_date) !!}
                        </td>
                        <td class="merged-display">{{getSeatDisplayByMainNo($value->seat_no) ?? 'GEN'}}<br>
                            <small>{{$detail->planType->name ?? ''}}</small>
                        </td>
                        <td class="merged-display"><span class="uppercase truncate name" data-bs-toggle="tooltip"
                                data-bs-title="{{$value->name}}" data-bs-placement="bottom">{{$value->name}}</span>
                            <br> <small>{{$value->dob ?? ''}}</small>
                        </td>
                        <td class="merged-display"><span class="truncate" >
                            {!! $value->email ? $value->email : '<i class="fa-solid fa-times text-danger"></i> Email ID Not Available' !!} 
                            </span> <br>
                            <small> +91-{{$value->mobile ?? ''}}</small>
                        </td>
                        <td class="merged-display">{{$detail->plan_start_date}}<br>
                            <small>{{$detail->plan->name}}</small>
                        </td>
                       
                        <td class="merged-display">{{$detail->plan_end_date}}<br>
                             {!! getUserStatusDetails($detail->plan_end_date) !!}
                        </td>
                      
                        <td>
                            <ul class="actionalbls">
                            <!-- Make payment -->
                            <li><a href="{{route('learner.payment',$detail->id)}}" title="Payment Lerners" class="payment-learner"><i class="fas fa-credit-card"></i></a></li>
                            </ul>
                        </td>
                        <td>
                            <ul class="actionalbls">
                             <!-- Sent Mail -->
                             <li><a target="_blank" href="https://wa.me/{{ $value->mobile }}?text={{ rawurlencode("Dear {$value->name},\n\nYour plan expired on {$value->plan_end_date}.\n\nPlease renew it as soon as possible to continue uninterrupted access to your library seat.\n\nFor help, feel free to contact our support team.\n\n– Team Libraro") }}">
                                        <i class="fab fa-whatsapp"></i>
                                    </a></li>

                             <!-- Sent Mail -->
                             {{-- <li><a href="mailto:RECIPIENT_EMAIL?subject=Library Seat Renewal Reminder&body=Hey!%20🌟%0D%0A%0D%0AJust%20a%20friendly%20reminder:%20Your%20library%20seat%20plan%20will%20expire%20in%205%20days!%20📚✨%0D%0A%0D%0ADon%E2%80%99t%20miss%20out%20on%20the%20chance%20to%20keep%20enjoying%20your%20favorite%20books%20and%20resources.%20Plus,%20renewing%20now%20means%20you%20can%20unlock%20exciting%20rewards!%20🎁" target="_blank" data-id="{{$value->id}}" data-bs-toggle="tooltip" data-bs-placement="bottom" title=""  data-original-title="Send Email Reminders"><i class="fas fa-envelope"></i></a></li> --}}
                            </ul>
                        </td>

                    </tr>
                    @endforeach
                    @endforeach
                </tbody>
                

            </table>
            

        </div>
    </div>
</div>

<script>
    $(document).ready(function () {
        var table =$('#datatable').DataTable({
           
            buttons: [
                {
                    extend: 'csvHtml5',
                    text: 'Export CSV',
                    title: 'UpcomingPaymentReport',
                    exportOptions: {
                        columns: function (idx, data, node) {
                             return $(node).hasClass('d-none'); // export only hidden columns
                        },
                        format: {
                            body: function (data) {
                                return data.replace(/<[^>]+>/g, '').replace(/&nbsp;/g, ' ').trim();
                            },
                             header: function (data, columnIdx) {
                                const headers = [
                                    'Seat No',
                                    'Plan Type',
                                    'Name',
                                    'Email',
                                    'Mobile',
                                    'Start Date',
                                    'Plan Name',
                                    'Due Date',
                                    'Expiry Status',
                                ];
                                return headers[columnIdx] ?? '';
                            }
                        }
                    }

                }
            ],
             columnDefs: [
                { targets: 'export-seat-no', visible: false },
                { targets: 'export-plan-type', visible: false },
                { targets: 'export-name', visible: false },
                { targets: 'export-email', visible: false },
                { targets: 'export-mobile', visible: false },
                { targets: 'export-start-date', visible: false },
                { targets: 'export-plan-name', visible: false },
                { targets: 'export-end-date', visible: false },
                { targets: 'export-expiry-status', visible: false },
                { targets: 'merged-display', visible: true }
            ],
           
            lengthMenu: [10, 25, 50, 100],
            pageLength: 10
        });
         table.buttons().container().appendTo('#export');
    });
</script>

@endsection