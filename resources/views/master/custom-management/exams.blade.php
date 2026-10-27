@include('master.custom-management._toolbar', [
    'heading' => 'Exams',
    'hint' => 'Exams learners can pick while booking a seat.',
    'listable' => $cmExams->isNotEmpty(),
    'modal' => 'exam',
    'canAdd' => auth()->user()->can('has-permission', 'Add Exam Master'),
    'addLabel' => 'Add Exam',
])

@if($cmExams->isEmpty())
    @include('master.custom-management._empty', ['icon' => 'fa-graduation-cap', 'heading' => 'No exams added yet', 'text' => 'Add exams like UPSC, SSC or Banking.'])
@else
    <div class="cm-items cm-items-compact">
        @foreach($cmExams as $row)
            @include('master.custom-management._item', [
                'icon' => 'fa-graduation-cap', 'tone' => 'indigo',
                'heading' => $row->name,
                'subheading' => 'Competitive exam',
                'meta' => [],
                'inactive' => (bool) $row->deleted_at, 'locked' => false,
                'modal' => 'exam', 'table' => 'Exam', 'id' => $row->id,
                'record' => $row->only(['id', 'name']),
            ])
        @endforeach
    </div>
    @include('master.custom-management._no-results')
@endif
