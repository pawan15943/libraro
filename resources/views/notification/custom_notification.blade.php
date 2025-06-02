@extends('layouts.admin')

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
<!-- Content -->
<form action="{{ $notificat ? route('notifications.update', $notificat->batch_id) : route('notifications.send') }}" method="POST">
    @csrf

    @php
    if($notificat){
    $data = json_decode($notificat->data, true);
    }

    @endphp
    <h4 class="card-title mb-4">{{ $notificat ? 'Edit Notification' : 'Send Notification' }}</h4>

    <div class="row mb-4">
        <div class="col-lg-12">
            <div class="card">
                <div class="row g-4">
                    <!-- Guard Selection -->
                    <div class="col-lg-12">
                        <label for="guard">Select Type <span class="text-danger">*</span></label>
                        <select class="form-select @error('guard') is-invalid @enderror" id="guard" name="guard" required>
                            <option value="">Select</option>
                            <option value="web" {{ old('guard', $notificat->guard ?? '') == 'web' ? 'selected' : '' }}>Administrator</option>
                            <option value="library" {{ old('guard', $notificat->guard ?? '') == 'library' ? 'selected' : '' }}>Library</option>
                            <option value="learner" {{ old('guard', $notificat->guard ?? '') == 'learner' ? 'selected' : '' }}>Learner</option>
                        </select>
                        @error('guard')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>

                    <!-- Title Input -->
                    <div class="col-lg-12">
                        <label for="title">Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title', $data['title'] ?? '') }}" required>
                        @error('title')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>

                    <!-- Description Input -->
                    <div class="col-lg-12">
                        <label for="description">Description <span class="text-danger">*</span></label>
                        <textarea class="form-control notification @error('description') is-invalid @enderror" id="description" name="description" rows="3" required>{{ old('description', $data['description'] ?? '') }}</textarea>
                        @error('description')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>

                    <!-- Link Input -->
                    <div class="col-lg-6">
                        <label for="link">Link</label>
                        <input type="url" class="form-control @error('link') is-invalid @enderror" id="link" name="link" value="{{ old('link', $data['link'] ?? '') }}">
                        @error('link')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>

                    <!-- Image URL Input -->
                    <div class="col-lg-6">
                        <label for="image">Image URL</label>
                        <input type="url" class="form-control @error('image') is-invalid @enderror" id="image" name="image" value="{{ old('image', $data['image'] ?? '') }}">
                        @error('image')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>

                    <!-- Start Date -->
                    <div class="col-lg-6">
                        <label for="start_date">Start Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('start_date') is-invalid @enderror" id="start_date" name="start_date" value="{{ old('start_date', $notificat->start_date ?? '') }}" required>
                        @error('start_date')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>

                    <!-- Expiry Date -->
                    <div class="col-lg-6">
                        <label for="end_date">Expiry Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('end_date') is-invalid @enderror" id="end_date" name="end_date" value="{{ old('end_date', $notificat->end_date ?? '') }}" required>
                        @error('end_date')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>

                    <!-- Submit Button -->
                    <div class="col-lg-3 mt-4">
                        <button type="submit" class="btn btn-primary button">{{ $notificat ? 'Update Notification' : 'Send Notification' }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<div class="col-lg-12 mb-4">
    <h4 class="mb-4">Notification List</h4>
    <div class="table-responsive">
        <table class="table text-center datatable border-bottom">
            <thead>
                <tr>
                    <th>Banner</th>
                    <th>Title</th>
                    <th class="w-25">Description</th>
                    <th>Notification Link</th>
                    <th>Start Date</th>
                    <th>Expire Date</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($notifications as $index => $notification)
                @php
                $data = json_decode($notification->data, true);
                @endphp
                <tr>
                    <td>
                        @if(!empty($data['image']))
                        <img src="{{ $data['image'] }}" alt="Notification Image" style="width: 50px; height: auto;">
                        @else
                        <img src="http://localhost/libraryProject/public/img/user.png" alt="dummy" class="dummy">
                        @endif
                    </td>
                    <td style="text-transform:capitalize; font-weight:700 !important; ">{{ $data['title'] ?? 'N/A' }}</td>
                    <td class="text-left" style="text-align:left;"><span class="m-0">{{ $data['description'] ?? 'N/A' }}</span></td>

                    <td>
                        @if(!empty($data['link']))
                        <a href="{{ $data['link'] }}" class="btn btn-primary button button-sm" target="_blank">View</a>
                        @else
                        N/A
                        @endif
                    </td>
                    <td>{{ $notification->start_date }}</td>
                    <td>{{ $notification->end_date }}</td>
                    <td>
                        {{ now()->between($notification->start_date, $notification->end_date) ? 'Active' : 'Expired' }}
                    </td>
                    <td>
                        <ul class="actionalbls">
                            <li>
                                <a href="{{ route('notifications.edit', $notification->batch_id) }}"><i class="fa fa-edit"></i></a>
                            </li>
                        </ul>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>


@include('library.script')
@endsection