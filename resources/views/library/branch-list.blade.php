@extends('layouts.library')
@section('content')

<!-- Breadcrumb -->

<div class="row">
    <div class="col-lg-12">
       
         <a href="{{ route('branch.create') }}" class="btn btn-primary export">
            <i class="fa-solid fa-plus "></i> Add Branch
        </a>
        
        <div class="heading-list">
            <h4 class="">Branch List </h4>
            
        </div>
        <div class="table-responsive mt-4">
            <table class="table text-center" id="datatable">
                <thead>
                    <tr>
                        <th>S.No.</th>
                        <th>Branch Name</th>
                        <th>Contact Info</th>
                        <th>Email</th>
                        <th>Address</th>
                        <th style="width:30%">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($branches as $key => $value)
                  
                    <tr>
                        <td>{{$key+1}}</td>
                        <td>{{$value->name}}</td>
                        <td>{{$value->mobile}}
                        </td>
                        <td>
                            <span>{{$value->email}}</span>

                        </td>
                        <td>{{$value->library_address}}</td>
                        <td>
                            
                            <ul class="actionalbls">
                            
                                <li><a href="{{route('branch.edit',$value->id)}}" data-bs-toggle="tooltip" data-bs-title="Branch Profile Edit" data-bs-placement="bottom"><i class="fas fa-edit"></i></a>
                                </li>
                               <li>
                                    <form action="{{ route('branch.destroy', $value->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this branch?');" style="display:inline-block;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" style="border: none; background: none; padding: 0;" data-bs-toggle="tooltip" data-bs-title="Delete Branch" data-bs-placement="bottom">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </li>


                            </ul>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@include('library.script')

@endsection