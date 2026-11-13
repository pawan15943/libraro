@extends('layouts.admin')

@section('body_class', 'ax-custom-page')

@section('page_actions')
    <x-ui.button variant="gray" icon="fa-solid fa-arrow-left" :href="route('library.show', $library->id)">{{ \Illuminate\Support\Str::limit($library->library_name, 28) }}</x-ui.button>
@endsection

@section('content')
<x-ui.flash />

@if($branches->isEmpty())
    <x-ui.card>
        <x-ui.empty icon="fa-solid fa-code-branch" title="No branches yet" text="{{ $library->library_name }} has not added a branch." />
    </x-ui.card>
@else
    <div class="grid gap-x-5 md:grid-cols-2 xl:grid-cols-3">
        @foreach($branches as $key => $branch)
            <x-ui.card flush :title="$branch->display_name ?? $branch->name" :subtitle="'Branch '.($key + 1)">
                <x-slot:actions>
                    @if($branch->status)
                        <x-ui.badge tone="green" dot pill>Active</x-ui.badge>
                    @else
                        <x-ui.badge tone="red" dot pill>Inactive</x-ui.badge>
                    @endif
                </x-slot:actions>

                <div class="flex gap-4 px-4 pb-4 sm:px-5">
                    <div class="min-w-0 flex-1 space-y-2 text-sm">
                        <div class="flex gap-2 text-label-2"><i class="fa-solid fa-location-dot mt-1 w-4 text-label-4"></i><span>{{ $branch->library_address ?: 'Address not added' }}</span></div>
                        <div class="flex gap-2 text-label-2"><i class="fa-solid fa-phone mt-1 w-4 text-label-4"></i><span>{{ $branch->mobile ? '+91 '.$branch->mobile : 'Not added' }}</span></div>
                        <div class="flex gap-2 text-label-2 min-w-0"><i class="fa-solid fa-envelope mt-1 w-4 text-label-4"></i><span class="truncate">{{ $branch->email ?: 'Not added' }}</span></div>
                    </div>
                    @if($branch->uuid)
                        <a href="{{ route('qr.branch', $branch->uuid) }}" target="_blank" rel="noopener" class="flex-none self-start rounded-md bg-white p-1.5 shadow-0 [&_svg]:block [&_svg]:size-[84px]" title="Booking QR — opens the booking page">
                            {!! QrCode::size(100)->generate(route('qr.branch', $branch->uuid)) !!}
                        </a>
                    @endif
                </div>

                <div class="ui-list border-t border-sep">
                    <x-ui.row title="Seats & hours" icon="fa-solid fa-chair" :href="route('library.branch.seatHour', $branch->id)" chevron />
                    <x-ui.row title="Plans" icon="fa-solid fa-layer-group" :href="route('library.branch.plans', $branch->id)" chevron />
                    <x-ui.row title="Shifts" icon="fa-solid fa-clock" :href="route('library.branch.plantypes', $branch->id)" chevron />
                    <x-ui.row title="Prices" icon="fa-solid fa-tags" :href="route('library.branch.prices', $branch->id)" chevron />
                    <x-ui.row title="Edit branch" icon="fa-solid fa-pen" :href="route('library.branch.edit', $branch->id)" chevron />
                </div>
            </x-ui.card>
        @endforeach
    </div>
@endif
@endsection
