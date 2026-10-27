@include('master.custom-management._toolbar', [
    'heading' => 'Expense Heads',
    'hint' => 'Expense names used while adding daily and monthly expenses.',
    'listable' => $cmExpenses->isNotEmpty(),
    'modal' => 'expense',
    'canAdd' => auth()->user()->can('has-permission', 'Add Expense Master'),
    'addLabel' => 'Add Expense',
])

@if($cmExpenses->isEmpty())
    @include('master.custom-management._empty', ['icon' => 'fa-wallet', 'heading' => 'No expense heads yet', 'text' => 'Add heads like Rent, Electricity Bill or Internet.'])
@else
    <div class="cm-items cm-items-compact">
        @foreach($cmExpenses as $row)
            @include('master.custom-management._item', [
                'icon' => 'fa-wallet', 'tone' => 'rose',
                'heading' => $row->name,
                'subheading' => 'Expense head',
                'meta' => [],
                'inactive' => (bool) $row->deleted_at, 'locked' => false,
                'modal' => 'expense', 'table' => 'Expense', 'id' => $row->id,
                'record' => $row->only(['id', 'name']),
            ])
        @endforeach
    </div>
    @include('master.custom-management._no-results')
@endif
