@extends('layouts.learner')
@section('content')


<div class="card">
    <form action="{{ route('my-attendance') }}" method="GET">
        <div class="row">

            <div class="col-lg-4">
                <label for="category_name">Select Month <span class="text-danger">*</span></label>
                <select name="request_name" class="form-select">
                    <option value="">Select Month</option>
                    @foreach($months as $key => $value)
                    <option value="{{$value['year_month']}}" {{ request('request_name') == $value['year_month'] ? 'selected' : '' }}>{{$value['month_name']}}</option>
                    @endforeach
                </select>

            </div>

            <div class="col-lg-3 mt-4">
                <button type="submit" class="btn btn-primary button">Search</button>
            </div>
        </div>
    </form>


</div>
<h4 class="py-4">My Attendance : {{$data->plan_type_name}} ({{ \Carbon\Carbon::parse($data->start_time)->format('h:i A') }} to {{ \Carbon\Carbon::parse($data->end_time)->format('h:i A') }})</h4>


<div class="row mb-4">
    <div class="col-lg-12">
        <div class="table-responsive">
            <table class="table text-center datatable dataTable" id="datatable">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Attandance</th>
                        <th>In Time</th>
                        <th>Out Time</th>
                    </tr>
                </thead>
                <tbody>
                    @if($my_attandance)
                    @foreach($my_attandance as $key => $value)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($value->date)->format('M j, Y') }}</td>
                        <td>
                            @if($value->attendance==1)
                            Present
                            @else
                            Absent
                            @endif
                        </td>
                        <td>{{ \Carbon\Carbon::parse($value->in_time)->format('h:i A') }}</td>
                        <td>{{ \Carbon\Carbon::parse($value->out_time)->format('h:i A') }}</td>
                    </tr>
                    @endforeach
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>


@endsection