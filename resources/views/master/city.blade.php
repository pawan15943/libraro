@extends('layouts.admin')
@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}" />
<div class="row">
    <!-- Page Main Content -->
    <div class="col-lg-12">
        <div id="success-message" class="alert alert-success" style="display:none;"></div>
        <div id="error-message" class="alert alert-danger" style="display:none;"></div>
        <div class="card card-default">
            
            <!-- Add City Fields -->
            <div class="card-body">
                <form id="submit">
                    @csrf
                    @if(session('message'))
                    <div class="alert alert-success">
                        {{ session('message') }}
                    </div>
                    @endif
                    <div class="row">
                    <input type="hidden" name="id" value="" id="city_id">
                    <div class="col-lg-6">
                        <label> State <sup class="text-danger">*</sup></label>
                        <select id="stateid" name="state_id" class="form-control @error('state') is-invalid @enderror" placeholder="Select State">
                            @error('state')
                            <span class="invalid-feedback" role="alert">
                                {{ $message }}
                            </span>
                            @enderror
                            <option value="">Select State</option>
                            @foreach ($states as $key => $state)
                            <option value="{{$state}}">{{$key}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-6">
                        <label> City Name <sup class="text-danger">*</sup></label>
                        <input type="text" id="city" name="city_name" value="{{ old('city') }}" class="form-control @error('city') is-invalid @enderror char-only">
                        @error('city')
                        <span class="invalid-feedback" role="alert">
                            {{ $message }}
                        </span>
                        @enderror
                    </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-lg-3">
                            <button type="submit" class="btn btn-primary btn-block" id="submit_id">Add City </button>
                        </div>
                    </div>
                </form>
            </div>
            <!-- end -->

            <!-- All City List -->
            <div class="card-body p-0">
                <h4 class="px-3 py-2">All Cities List</h4>
                <div class="table-responsive tableRemove_scroll mt-2">
                    <table class="table table-hover dataTable m-0" id="datatable" style="display:table !important">
                        <thead>
                            <tr>
                                <th>S.No.</th>
                                <th>City Name</th>
                                <th>State </th>
                                <th>City Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        @php
                        $x=1;
                        @endphp
                        <tbody>
                            @foreach($citys as $key => $city)
                            <tr>
                                <td>{{$x++}}</td>
                                <td>{{$city->city_name}}</td>
                                <td>{{$city->state_name}}</td>
                                <td><span class="status-column">
                                    {{ $city->trashed() ? 'Inactive' : 'Active' }}
                                </span></td>
                                <td>
                                    <ul class="actionables">
                                        <li><a href="javascript:void(0)" type="button" class="city_edit" data-id="{{$city->city_id}}"><i class="fas fa-edit"></i></a></li>
                                       <li> <a href="#" class="active-deactive" data-id="{{ $city->city_id }}" data-table="City" title="Active/Deactive">
                                        
                                        @if($city->deleted_at)
                                        <i class="fas fa-check"></i>
                                        @else
                                        <i class="fas fa-ban"></i>
                                        @endif
                                        </a></li>
                                        {{-- <li><a href="javascript:void(0)" type="button" class="delete" data-id="{{$city->city_id}}"><i class="fas fa-trash"></i></a></li> --}}
                                    </ul>
                                   
                                </td>
                            </tr>
                            @endforeach

                        </tbody>
                    </table>
                </div>
            </div>
            <!-- end -->

        </div>
    </div>
</div>


<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

@include('master.script')
<script type="text/javascript">
    $(document).ready(function() {
        $(document.body).on('submit', '#submit', function(event) {
            event.preventDefault();
            var state_id = $("#stateid").val();
            var city_name = $('#city').val();
            var city_id = $('#city_id').val();
            var errors = {};
            if (!state_id) {
                errors.state_id = 'State is required.';
            }
            if (!city_name) {
                errors.city_name = 'City is required.';
            }

            $.ajax({
                url: '{{ route('city.store') }}',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
                },
                type: 'POST',
                data: {
                    "_token": "{{ csrf_token() }}",
                    state_id: state_id,
                    city_name: city_name,
                    id: city_id,
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $("#success-message").text(response.message).show();
                        $("#error-message").hide();
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else if (response.errors) {
                        $(".is-invalid").removeClass("is-invalid");
                        $(".invalid-feedback").remove();

                        $.each(response.errors, function(key, value) {
                            var element = $("[name='" + key + "']"); // Target element by name attribute
                          
                            element.addClass("is-invalid");
                            element.after('<span class="invalid-feedback" role="alert">' + value + '</span>');
                        });
                    } else {
                        $("#error-message").text(response.message).show();
                        $("#success-message").hide();
                    }
                },
                error: function(xhr) {
                    $("#error-message").text(response.message).show();
                    $("#success-message").hide();
                }
            });
        });

        $(document.body).on('click', '.city_edit', function() {
            var city_id = $(this).data('id');
            var errors = {};
           
            if (!city_id) {
                errors.city_id = 'City is required.';
            }
            $('#submit_id').text('');
            $('#submit_id').append('Edit City');
            $.ajax({
                url: '{{ route('city.edit') }}',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
                },
                type: 'GET',
                data: {
                    "_token": "{{ csrf_token() }}",
                    "id": city_id,
                },
                dataType: 'json',
                success: function(response) {
                    $('#city_id').val(response.city.id);
                    $('#city').val(response.city.city_name);
                    $("#stateid").val(response.city.state_id);
                },
                error: function(xhr) {
                    toastr.error('An error occurred. Please try again.');
                }
            });
        });

        $('.delete').click(function(e) {
            if (!confirm('Are you sure you want to delete this City?')) {
                e.preventDefault();
                return;
            }
            var city_id = $(this).data('id');
            $.ajax({
                url: '{{ route('city.destroy') }}',
                type: 'POST',
                data: {
                    "_token": "{{ csrf_token() }}",
                    "id": city_id,
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function(xhr) {
                    toastr.error('An error occurred. Please try again.');
                }
            });
        });
    });
</script>

@endsection