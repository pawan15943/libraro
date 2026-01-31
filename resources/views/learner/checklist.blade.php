@extends('layouts.library')
@section('content')



<div class="row mb-4">
    <div class="col-lg-12">
        <form method="POST" action="{{ route('learner.idcard.bulk') }}" target="_blank">
            <div class="mb-3" style="max-width:250px;">
            <label class="form-label">Print Type</label>
            <select name="print_type" class="form-select" required>
                <option value="">Select Print Type</option>
                <option value="single">Print one side</option>
                <option value="both">Print both side</option>
            </select>
        </div>

            <div class="table-responsive">
                @csrf
                <table class="table text-center datatable">
                    <thead>
                        <tr>
                            <th style="width: 5%;"><input type="checkbox" id="select_all"> </th>
                            <th>Photo</th>
                            <th>UID</th>
                            <th>Name</th>
                            <th>Mobile Number</th>
                            <th>Father Name</th>
                            <th>Payment Status</th>
                            <th>Plan End Date</th>
                        </tr>
                    </thead>
                    <tbody>
                       
                        @foreach($learners as $learner)
                        <tr>
                            <td>
                                <input type="checkbox" name="learner_ids[]" value="{{ $learner->id }}" class="select_one">
                            </td>
                            <td><img src="{{ $learner->profile_picture ? asset($learner->profile_picture) : asset('public/img/student_profile.jpeg') }}" class="profile" alt="Profile Photo"></td>
                            <td>{{ $learner->learner_no }}</td>
                            <td>{{ $learner->name ?? ''}}</td>
                            <td>+91-{{ $learner->mobile }}</td>
                            <td>{{ $learner->father_name ?? 'Not Available' }}</td>
                            <td class="text-success">{{ $learner->is_paid==1 ? 'Paid' : 'Unpaid' }}</td>
                            <td>{{ $learner->plan_end_date ?? ''}}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <button type="submit" class="print-cards mb-4">🖨️ Print Selected ID Cards</button>
            </div>
        </form>
    </div>
</div>



<script>
    // Select/Deselect all checkboxes
    document.getElementById('select_all').onclick = function() {
        let checkboxes = document.querySelectorAll('.select_one');
        for (let checkbox of checkboxes) {
            checkbox.checked = this.checked;
        }
    };
</script>

@endsection