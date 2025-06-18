@extends('layouts.library')
@section('content')

<!-- Breadcrumb -->
<div class="heading-list justify-content-end">
    <a href="{{ route('branch.create') }}" class="btn btn-primary export m-0">
        <i class="fa-solid fa-plus "></i> Add Branch
    </a>
</div>
<div class="card p-0 mt-3">
    <div class="row">
        <div class="col-lg-12">
            <div class="table-responsive">
                <table class="table text-center  " id="datatable">
                    <thead>
                        <tr>
                            <th>S.No.</th>
                            <th>Branch Name</th>
                            <th>Contact Info</th>
                            <th>Email</th>
                            <th>Address</th>
                            <th>Master</th>
                            <th style="width:30%">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($branches as $key => $value)

                        <tr>
                            <td>{{$key+1}}</td>
                            <td>{{$value->name}}</td>
                            <td>{{$value->mobile ?? 'Not updated yet'}}
                            </td>
                            <td>
                                <span>{{$value->email ?? 'Not updated yet'}}</span>

                            </td>
                            <td>{{$value->library_address ?? 'Not updated yet'}}</td>
                            <td>

                                <ul class="actionalbls">
                                    @if(getCurrentBranch() !=0)

                                    <li><a href="{{route('seat.create',getCurrentBranch())}}" title="Seat Update "><i class="fa-solid fa-chair"></i></a></li>
                                    <li><a href="{{route('hour.create',getCurrentBranch())}}" title="Hour Update "><i class="fa-solid fa-clock-rotate-left"></i></a></li>
                                    <li><a href="{{route('extendDay.create',$value->id)}}" title="Extend Day"><i class="fa-solid fa-calendar-plus"></i></a></li>
                                    <li><a href="{{route('lockeramount.create',$value->id)}}" title="Locker Amount"><i class="fa-solid fa-lock"></i></a></li>
                                  
                                    @endif
                                </ul>
                            </td>
                            <td>

                                <ul class="actionalbls">

                                    <li><a href="{{route('branch.edit',$value->id)}}" data-bs-toggle="tooltip" data-bs-title="Branch Profile Edit" data-bs-placement="bottom"><i class="fas fa-edit"></i></a>
                                    </li>
                                    {{-- <li>
                                        <form action="{{ route('branch.destroy', $value->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this branch?');" style="display:inline-block;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" style="border: none; background: none; padding: 0;" data-bs-toggle="tooltip" data-bs-title="Delete Branch" data-bs-placement="bottom">
                                                <i class="fas fa-trash"></i>
                                            </button>
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
    </div>
</div>
@include('library.script')

@endsection