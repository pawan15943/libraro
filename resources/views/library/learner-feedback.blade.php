@extends('layouts.library')

@section('title', 'Admin Dashboard')

@section('content')
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
<div class="row justify-content-center mb-4">
    <div class="col-lg-12">
        <div class="table-responsive">
            <table class="table text-center datatable" id="datatable">
                <thead>
                    <tr>
                        <th>S.No</th>
                        <th>Learner Name</th>
                        <th>Frequency</th>
                        <th>Purpose</th>
                        <th>Resources</th>
                        <th>Resource Suggestions</th>
                        <th>Rating</th>
                        <th>Staff helpful</th>
                        <th>Comment</th>
                    </tr>
                </thead>

                <tbody>
                  
                    @foreach($data as $key => $value)
                  
                    <tr>
                        <td>{{$key+1}}</td>
                        <td>{{$value->learner_name}}</td>
                        <td>@if($value->frequency==1)
                        Daily
                        @elseif($value->frequency==2)
                        Weekly
                        @elseif($value->frequency==3)
                        Occasionally
                        @elseif($value->frequency==4)
                        Rarely
                        @endif
                        </td>
                        <td> {{$value->purpose}}</td>
                        <td>@if($value->resources==1)
                            Yes
                            @else
                           No
                            @endif
                        </td>
                        <td> {{$value->resource_suggestions}}</td>
                        <td> {{$value->rating}}</td>
                        <td>@if($value->staff==1)
                        Yes
                            @else
                           No
                            @endif
                        </td>
                        <td>{{$value->comments}}</td>
                      
                        
                        
                    </tr>
                    @endforeach
                   
                </tbody>
            </table>
        </div>
    </div>
</div>
@include('library.script')
@endsection