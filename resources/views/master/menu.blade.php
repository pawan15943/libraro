@extends('layouts.admin')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}" />
<div class="row">
    <!-- Page Main Content -->
    <div class="col-lg-12">

        <div class="card card-default">

            <!-- Add Menus Fields -->
            <div class="card-body">
                <form id="submit" action="{{ isset($menu) && $menu->id ? route('menu.update', $menu->id) : route('menu.store') }}" method="post">
                    @csrf
                    @if(isset($menu) && $menu->id)
                    @method('PUT')
                    @endif

                    @if(session('message'))
                    <div class="alert alert-success">
                        {{ session('message') }}
                    </div>
                    @endif

                    <div class="row g-4">
                        @if(isset($menu) && $menu->id)
                        <input type="hidden" name="id" value="{{ $menu->id }}" id="menu_id">
                        @endif

                        <div class="col-lg-4">
                            <label for="class_name"> Menu Name<sup class="text-danger">*</sup> </label>
                            <input type="text" id="name" name="name" value="{{ old('name', isset($menu) && $menu->name ? $menu->name : '') }}" class="form-control char-only @error('name') is-invalid @enderror">
                            @error('name')
                            <span class="invalid-feedback" role="alert">
                                {{ $message }}
                            </span>
                            @enderror
                        </div>
                        <div class="col-lg-4">
                            <label for="class_name"> Menu Link<sup class="text-danger">*</sup> </label>
                            <input type="text" id="url" name="url" value="{{ old('url', isset($menu) && $menu->url ? $menu->url : '') }}" class="form-control @error('url') is-invalid @enderror">
                            @error('url')
                            <span class="invalid-feedback" role="alert">
                                {{ $message }}
                            </span>
                            @enderror
                        </div>
                        <div class="col-lg-4">
                            <label for="class_name">Add Icon Class</label>
                            <input type="text" id="icon" name="icon" value="{{ old('icon', isset($menu) && $menu->icon ? $menu->icon : '') }}" class="form-control char-only-sps @error('icon') is-invalid @enderror">
                            @error('icon')
                            <span class="invalid-feedback" role="alert">
                                {{ $message }}
                            </span>
                            @enderror
                        </div>
                        {{-- <div class="col-lg-4">
                            <label for="class_name"> Slug<sup class="text-danger">*</sup></label>
                            <input type="text" id="slug" name="slug" value="{{ old('slug', isset($menu) && $menu->slug ? $menu->slug : '') }}" class="form-control char-only-sps @error('slug') is-invalid @enderror">
                            @error('slug')
                            <span class="invalid-feedback" role="alert">
                                {{ $message }}
                            </span>
                            @enderror
                        </div> --}}
                        <div class="col-lg-4">
                            <label for="parent_id">Parent </label>
                            <select id="parent_id" name="parent_id" class="form-select @error('parent_id') is-invalid @enderror">
                                <option value="">Select Parent</option>
                                @foreach($menusParent as $key => $value)
                                    <option 
                                        value="{{ $key }}" 
                                        {{ old('parent_id', isset($menu) && $menu->parent_id == $key ? $menu->parent_id : '') == $key ? 'selected' : '' }}>
                                        {{ $value }}
                                    </option>
                                @endforeach
                            </select>
                            
                            @error('parent_id')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                            
                            
                        </div>
                       
                        <div class="col-lg-4">
                            <label for="class_name">Menu Order<sup class="text-danger">*</sup></label>
                            <input type="text" id="order" name="order" value="{{ old('order', isset($menu) && $menu->order ? $menu->order : '') }}" class="form-control digit-only @error('order') is-invalid @enderror">
                            @error('order')
                            <span class="invalid-feedback" role="alert">
                                {{ $message }}
                            </span>
                            @enderror
                        </div>
                        <div class="col-lg-4">
                            <label for="class_name">Guard<sup class="text-danger">*</sup></label>
                            <select id="guard" name="guard" class="form-select @error('guard') is-invalid @enderror">
                                <option value="">Select Guard</option>
                                <option value="web" {{ old('guard', isset($menu) && $menu->guard == 'web' ? 'web' : '') == 'web' ? 'selected' : '' }}>Web</option>
                                <option value="library" {{ old('guard', isset($menu) && $menu->guard == 'library' ? 'library' : '') == 'library' ? 'selected' : '' }}>Library</option>
                                <option value="learner" {{ old('guard', isset($menu) && $menu->guard == 'learner' ? 'learner' : '') == 'learner' ? 'selected' : '' }}>Learner</option>
                            </select>
                            
                            @error('guard')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                            
                        </div>
                        <div class="col-lg-4">
                            <label for="class_name"> Permission </label>
                            <select id="has_permissions" name="has_permissions" class="form-select @error('has_permissions') is-invalid @enderror">
                                <option value="">Select Permission</option>
                                @foreach($permissions as $key => $value)
                                    <option value="{{ $value->name }}" 
                                        {{ old('has_permissions', isset($menu) && $menu->has_permissions ? $menu->has_permissions : '') == $value->name ? 'selected' : '' }}>
                                        {{ $value->name }}
                                    </option>
                                @endforeach
                            </select>

                            @error('has_permissions')
                            <span class="invalid-feedback" role="alert">
                                {{ $message }}
                            </span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-lg-3 mt-4">
                        <button type="submit" class="btn btn-primary button">
                            {{ isset($menu) && $menu->id ? 'Update' : 'Add' }}
                        </button>
                    </div>
                    
                </form>
            </div>
            <!-- end -->

            <!-- Add Menus List -->
            <div class="card-body p-0">
                <h4 class="px-3 py-2">All Menus List</h4>
                <div class="table-responsive tableRemove_scroll mt-2">
                    <table class="table table-hover dataTable m-0" id="datatable" style="display:table !important">
                        <thead>
                            <tr>
                                <th>S.No.</th>
                                <th>Menu Name</th>
                                <th>Route</th>
                                <th>Guard</th>
                                <th>Permission</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                            $x = 1;
                            @endphp
                            @foreach($menus as $key => $value)
                            <tr>
                                <td>{{ $x++ }}</td>
                                <td>{{ $value->name }}</td>
                                <td>{{ $value->url }}</td>
                                <td>{{ $value->guard }}</td>
                                <td>{{ $value->has_permissions }}</td>
                                <td><span class="status-column">
                                        {{ $value->trashed() ? 'Inactive' : 'Active' }}
                                    </span></td>
                                <td >
                                    <ul class="actionalbls">
                                        <li><a href="{{ route('menu.edit', $value->id) }}" title="Edit Menu"><i class="fas fa-edit"></i></a></li>
                                        <li> <a href="#" class="active-deactive" data-id="{{ $value->id }}" data-table="Menu" title="Active/Deactive">

                                                @if($value->trashed())
                                                <i class="fas fa-check"></i>
                                                @else
                                                <i class="fas fa-ban"></i>
                                                @endif
                                            </a></li>
                                        <li>
                                            <form method="POST" action="{{ route('menu.destroy', $value->id) }}" style="display:inline-block;">
                                                @csrf
                                                @method('DELETE')
                                                <i class="fas fa-trash"></i>
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
            <!-- end -->

        </div>
    </div>
</div>
@include('master.script')
@endsection