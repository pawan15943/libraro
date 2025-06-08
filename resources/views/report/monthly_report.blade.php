@extends('layouts.library')
@section('content')

<div class="row">
    <div class="col-lg-12">

        <div class="table-responsive tableRemove_scroll mt-2">
            <table class="table text-center datatable" id="datatable" style="display: table !important;">
                <thead>
                    <tr>
                        <th>S. No.</th>
                        <th>Month</th>
                        <th>Total Revenue (A)</th>
                        <th>Total Expenses (B)</th>
                        <th>Net Profit (A-B)</th>
                        <th>Monthly Revenue (C)</th>
                        <th>Monthly Net Profit (C-B)</th>
                        <th>Manage Expanse</th>
                    </tr>
                </thead>
                <tbody>
                     @if(isset($reportData) && !empty($reportData))
                    @php
                    $x=1;
                    @endphp
                    @foreach ($reportData as $key => $value)
                    @php
                    $dt = DateTime::createFromFormat('!m', $value['month']);
                    @endphp
                    <tr>
                        <td>{{$x++}}</td>
                        <td>{{ $dt->format('F') }}, {{ $value['year'] }}</td>
                        <td><i class="fa fa-inr"></i> {{ number_format($value['total_revenue'], 2) }}</td>
                        <td class="text-danger"><i class="fa fa-inr"></i> {{ number_format($value['total_expenses'], 2) }}</td>
                        <td class="text-success bold"><i class="fa fa-inr"></i> {{ number_format($value['total_revenue'] - $value['total_expenses'], 2) }}</td>
                        <td><i class="fa fa-inr"></i> {{ number_format($value['monthly_revenue'], 2) }}</td>
                        <td class="text-success bold"><i class="fa fa-inr"></i> {{ number_format($value['monthly_revenue'] - $value['total_expenses'], 2) }}</td>
                        <td>
                            <ul class="actionalbls">
                                <li>
                                    <a href="{{ route('report.expense', ['year' => $value['year'], 'month' => $value['month']]) }}" title="Add Expense">
                                        <i class="fas fa-plus"></i>
                                    </a>
                                </li>
                            </ul>
                        </td>

                    </tr>
                    @endforeach
                      @else
                    <tr>
                        <td colspan="7">No Data Avaiable to Show.</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>



@endsection