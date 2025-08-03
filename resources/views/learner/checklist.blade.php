@extends('layouts.library')
@section('content')



<div class="row mb-4">
    <div class="col-lg-12">
       
          <form method="POST" action="{{ route('learner.idcard.bulk') }}" target="_blank">
             @csrf
            <table class="table text-center datatable border-bottom f-width">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="select_all"> All</th>
                        <th>Name</th>
                        
                    </tr>
                </thead>
                <tbody>
                    @foreach($learners as $learner)
                    <tr>
                        <td>
                            <input type="checkbox" name="learner_ids[]" value="{{ $learner->id }}" class="select_one">
                        </td>
                        <td>{{ $learner->name }}</td>
                     
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <button type="submit">🖨️ Print Selected ID Cards</button>
            </form>

   

        
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