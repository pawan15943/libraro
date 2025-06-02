@extends('layouts.learner')
@section('content')
@if (session('error'))
<div class="alert alert-danger">
    {{ session('error') }}
</div>
@endif

@if($is_feedback)
@if (session('success'))
<div class="alert alert-success">
    {{ session('success') }}
</div>
@else
<div class="alert alert-success">
    {{ "Your Feedback already Submmitted." }}
</div>
@endif

@else 

<div class="card mb-4">
   
    <form action="{{ route('learner.feedback.store') }}" method="POST" class="validateForm">
        @csrf
        <div class="row g-4">
            <h4>Library Feedback Form</h4>
            <!-- Frequency of Visits -->
            <div class="col-lg-6">
                <label for="frequency">How often do you visit the library?</label>
                <select id="frequency" name="frequency" class="form-control @error('frequency') is-invalid @enderror">
                    <option value="">Select Option</option>
                    <option value="1" {{ old('frequency') == '1' ? 'selected' : '' }}>Daily</option>
                    <option value="2" {{ old('frequency') == '2' ? 'selected' : '' }}>Weekly</option>
                    <option value="3" {{ old('frequency') == '3' ? 'selected' : '' }}>Occasionally</option>
                    <option value="4" {{ old('frequency') == '4' ? 'selected' : '' }}>Rarely</option>
                </select>
                @error('frequency')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>

            <!-- Purpose of Visit -->
            <div class="col-lg-6">
                <label for="purpose">Why do you usually visit the library?</label>
                <input type="text" id="purpose" name="purpose" class="form-control @error('purpose') is-invalid @enderror" placeholder="e.g., Borrow books, Study, Research" value="{{ old('purpose') }}">
                @error('purpose')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>

            <!-- Resource Availability -->
            <div class="col-lg-12">
                <label for="resources">Are the books/resources you need usually available?</label>
                <select id="resources" name="resources" class="form-control @error('resources') is-invalid @enderror">
                    <option value="">Select Option</option>
                    <option value="1" {{ old('resources') == '1' ? 'selected' : '' }}>Yes</option>
                    <option value="2" {{ old('resources') == '2' ? 'selected' : '' }}>No</option>
                    <option value="partially" {{ old('resources') == 'partially' ? 'selected' : '' }}>Partially</option>
                </select>
                @error('resources')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>

            <!-- Resource Suggestions -->
            <div class="col-lg-12">
                <label for="resource_suggestions">Suggestions for new resources or books:</label>
                <textarea id="resource_suggestions" name="resource_suggestions" class="form-control no-validate" rows="3" placeholder="Enter your suggestions">{{ old('resource_suggestions') }}</textarea>
            </div>

            <!-- Library Environment -->
            <div class="col-lg-12">
                <label for="rating">How would you rate the library environment? (Rating 1-5)</label>
                <input type="number" id="rating" name="rating" class="form-control @error('rating') is-invalid @enderror" placeholder="Rate (1 to 5)" min="1" max="5" value="{{ old('rating') }}">
                @error('rating')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>

            <!-- Library Staff -->
            <div class="col-lg-12">
                <label for="staff">Are the library staff helpful and approachable?</label>
                <select id="staff" name="staff" class="form-control @error('staff') is-invalid @enderror">
                    <option value="">Select Option</option>
                    <option value="1" {{ old('staff') == '1' ? 'selected' : '' }}>Yes</option>
                    <option value="2" {{ old('staff') == '2' ? 'selected' : '' }}>No</option>
                    <option value="sometimes" {{ old('staff') == 'sometimes' ? 'selected' : '' }}>Sometimes</option>
                </select>
                @error('staff')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>

            <!-- Additional Comments -->
            <div class="col-lg-12">
                <label for="comments">What do you like most about the library? Any suggestions?</label>
                <textarea id="comments" name="comments" class="form-control no-validate" rows="3" placeholder="Enter your comments">{{ old('comments') }}</textarea>
            </div>

            <!-- Submit Button -->
            <div class="col-lg-3">
                <button type="submit" class="btn btn-primary button">Submit Feedback</button>
            </div>
        </div>
    </form>

</div>
@endif

<script>
    $(document).ready(function() {
        let table = new DataTable('#datatable', {
          
        });
        
    });
</script>


@endsection