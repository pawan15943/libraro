@extends('layouts.admin')
@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}" />

<div class="row">
    <!-- Page Main Content -->
    <div class="col-lg-12">
        <div id="success-message" class="alert alert-success" style="display:none;"></div>
        <div id="error-message" class="alert alert-danger" style="display:none;"></div>
        <div class="card card-default">
            
            <!-- Add State Fields -->
            <div class="card-body">
                <form id="submit">
                    @csrf
                    @if(session('message'))
                    <div class="alert alert-success">
                        {{ session('message') }}
                    </div>
                    @endif
                    <div class="row g-4">
                        <input type="hidden" name="id" value="" id="state_id">
                        <div class="col-lg-5">
                            <input type="text" id="state" name="state_name" value="{{ old('state') }}" class="form-control char-only @error('state') is-invalid @enderror" placeholder="State Name">
                            @error('state')
                            <span class="invalid-feedback" role="alert">
                                {{ $message }}
                            </span>
                            @enderror
                        </div>
                        <div class="col-lg-3">
                            <button type="submit" class="btn btn-primary btn-block" id="submit_id">Add State</button>
                        </div>
                    </div>
                </form>
            </div>
            <!-- end -->
        
            <!-- ALL State List -->
            <div class="card-body p-0">
                <div class="table-responsive tableRemove_scroll mt-2">
                    <table class="table table-hover dataTable m-0" id="datatable" style="display:table !important">
                        <thead>
                            <tr>
                                <th>S.No.</th>
                                <th>State Name</th>
                                <th>State Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            @php
                            $x=1;
                            @endphp
                            @foreach($states as $key => $state)
                            <tr>
                                <td>{{$x++}}</td>
                                <td>{{$state->state_name}}</td>
                                <td><span class="status-column">
                                    {{ $state->trashed() ? 'Inactive' : 'Active' }}
                                </span></td>
                                <td>
                                    <ul class="actionables">
                                        <li>
                                        <a href="javascript:void(0)" type="button" class="state_edit" data-id="{{$state->state_id}}"><i class="fas fa-edit"></i></a>
                                        </li>
                                        <li> <a href="#" class="active-deactive" data-id="{{$state->state_id}}" data-table="State" title="Active/Deactive">
                                           
                                            @if($state->deleted_at)
                                                <i class="fas fa-check"></i>
                                            @else
                                            <i class="fas fa-ban"></i>
                                            @endif
                                        </a></li>
                                        {{-- <li>
                                        <form method="POST" action="{{ route('state.destroy', $state->state_id) }}">
                                        @csrf
                                        <input name="_method" type="hidden" value="DELETE">

                                        <i class="fas fa-trash"></i>
                                        </form>
                                        </li> --}}
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

@include('master.script')
<script type="text/javascript">
    $(document).ready(function() {
        $(document.body).on('click', '.state_edit', function() {
            var state_id = $(this).data('id');
            var errors = {};

            if (!state_id) {
                errors.state_id = 'State is required.';
            }
            $('#submit_id').text('');
            $('#submit_id').append('Edit State');
           
            $.ajax({
                url: '{{ route('state.edit')}}',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
                },
                type: 'GET',
                data: {
                    "_token": "{{ csrf_token() }}",
                    "id": state_id,

                },

                dataType: 'json',
                success: function(response) {

                    $('#state').val(response.state.state_name);
                    $('#countryid').val(response.state.country_id);
                    $('#state_id').val(response.state.id);

                }
            });

        });
        $(document.body).on('submit', '#submit', function() {

            event.preventDefault();


            var state_name = $('#state').val();
            var state_id = $('#state_id').val();

            var errors = {};

           
            if (state_name == '' || state_name == undefined) {
                errors.state_name = 'State is required.';
            }


            $.ajax({
                url: '{{ route('state.store')}}',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
                },
                type: 'POST',
                data: {
                    "_token": "{{ csrf_token() }}",

                    state_name: state_name,
                    id: state_id,
                },

                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $("#success-message").text(response.message).show();
                        $("#error-message").hide();
           
                        // Optional: Refresh the page to update data
                        setTimeout(function() {
                            location.reload();
                        }, 1000); // Adjust the delay as needed
                    } else if (response.errors) {

                        $(".is-invalid").removeClass("is-invalid");
                        $(".invalid-feedback").remove();

                        $.each(response.errors, function(key, value) {

                            if (key == 'state_name') {
                                $("#state").addClass("is-invalid");
                                $("#state").after('<span class="invalid-feedback" role="alert">' + value + '</span>');
                            }

                        });
                    } else {
                        $("#error-message").text(response.message).show();
                        $("#success-message").hide();
                    }

                }
            });
        });

        $('.delete').click(function(e) {
            if (!confirm('Are you sure you want to delete this State?')) {
                e.preventDefault();
            }
        });
    });
</script>

@endsection