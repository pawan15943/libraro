@extends('layouts.admin')

@section('body_class', 'ax-custom-page')

@section('page_actions')
    <x-ui.button variant="gray" icon="fa-solid fa-arrow-left" :href="route('library.branches', $branch->library_id)">Branches</x-ui.button>
@endsection

@section('content')
<x-ui.flash />

<div class="max-w-2xl">
    @if(!$hourRow)
        <x-ui.card>
            <x-ui.empty icon="fa-regular fa-clock" title="No operating hours yet" text="This record is normally created when the branch is first set up." />
        </x-ui.card>
    @else
        <form method="POST" action="{{ route('library.branch.seatHour.save', $branch->id) }}">
            @csrf
            <x-ui.section :label="$branch->display_name ?? $branch->name" note="Seat count can only be increased once set, never decreased.">
                <x-ui.card>
                    <div class="ui-fields sm:grid-cols-2">
                        <x-ui.input type="number" name="seats" label="Total seats" min="1" :value="$hourRow->seats" icon="fa-solid fa-chair" required />
                        <x-ui.select name="hour" label="Open hours a day" :options="collect(range(10, 24))->mapWithKeys(fn ($h) => [$h => $h.' hours'])" :value="$hourRow->hour" placeholder="Select hours" required />
                    </div>
                    <x-slot:footer>
                        <x-ui.button type="submit" icon="fa-solid fa-check">Save</x-ui.button>
                    </x-slot:footer>
                </x-ui.card>
            </x-ui.section>
        </form>
    @endif
</div>
@endsection
