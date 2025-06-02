@extends('layouts.admin')
@section('content')

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="row">
    <div class="col-lg-12">
       
        <div class="table-responsive">
            <table class="table text-center datatable dataTable" id="datatable">
             
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Mobile</th>
                            <th>Email</th>
                            <th>Message</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                       
                        @foreach($data as $key => $value)
                            <tr>
                                <td>{{ $key + 1 }}</td>

                                <td>{{ $value->full_name }}</td>
                                <td>{{ $value->mobile_number }}</td>
                                <td>{{ $value->email }}</td>
                                <td>{{ $value->message }}</td>
                                <td>{{ $value->created_at }}</td>
                                
                            </tr>
                        @endforeach
                    </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        let table = new DataTable('#datatable', {
          
        });
        
    });
</script>
@endsection