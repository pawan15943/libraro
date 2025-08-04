@extends('layouts.library')
@section('content')


<div class="card p-0">
    <div class="row mb-4">
        <div class="col-lg-12">
        
            <form method="POST" action="{{ route('learner.idcard.bulk') }}" target="_blank">
                @csrf
                <table class="table text-center datatable border-bottom f-width">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="select_all"> </th>
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
                            <td>{{ $learner->name }}</td>
                            <td>{{ $learner->mobile }}</td>
                            <td>{{ $learner->father_name ?? 'Not Available' }}</td>
                            <td>{{ $learner->is_paid==1 ? 'Paid' : 'Unpaid' }}</td>
                            <td>{{ $learner->plan_end_date }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                <button type="submit">🖨️ Print Selected ID Cards</button>
                </form>

    

            
        </div>
    </div>
</div>
 <script>
        // Select/Deselect all checkboxes
        document.getElementById('select_all').onclick = function () {
            let checkboxes = document.querySelectorAll('.select_one');
            for (let checkbox of checkboxes) {
                checkbox.checked = this.checked;
            }
        };
    </script>

@endsection