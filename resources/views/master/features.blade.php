@extends('layouts.admin')

@section('body_class', 'ax-custom-page')

@section('content')
@php $editing = isset($feature) && $feature; @endphp

<x-ui.flash />

<div class="grid gap-4 lg:grid-cols-[minmax(0,340px)_minmax(0,1fr)] lg:items-start">
    {{-- add / edit --}}
    <x-ui.card :title="$editing ? 'Edit feature' : 'Add feature'" subtitle="Shown on plan cards with its icon." icon="fa-solid fa-wand-magic-sparkles" class="lg:sticky lg:top-4">
        <form action="{{ $editing ? route('feature.storeFeature', $feature->id) : route('feature.storeFeature') }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-4">
            @csrf
            <x-ui.input name="name" id="name" label="Feature" :value="$editing ? $feature->name : null" placeholder="e.g. Unlimited learners" required />

            <div class="ui-field">
                <label class="ui-label" for="image">Icon image</label>
                <div class="flex items-center gap-3">
                    @if($editing && $feature->image)
                        <img src="{{ asset('public/'.$feature->image) }}" alt="" class="size-11 shrink-0 rounded-md bg-surface-3 object-contain p-1.5">
                    @endif
                    <input type="file" name="image" id="image" accept="image/*" class="ui-input min-w-0 flex-1 @error('image') is-invalid @enderror">
                </div>
                @error('image')<div class="ui-error"><i class="fa-solid fa-circle-exclamation"></i>{{ $message }}</div>@else<div class="ui-hint">PNG, JPG or GIF, up to 2 MB.</div>@enderror
            </div>

            <div class="flex gap-2">
                <x-ui.button type="submit" icon="fa-solid fa-check" class="flex-1">{{ $editing ? 'Update feature' : 'Add feature' }}</x-ui.button>
                @if($editing)
                    <x-ui.button variant="gray" :href="route('feature.create')">Cancel</x-ui.button>
                @endif
            </div>
        </form>
    </x-ui.card>

    {{-- list --}}
    <x-ui.card flush :title="'All features'" :subtitle="$features->count().' in total'">
        <div class="ui-table-wrap">
            <table class="ui-table" id="datatable">
                <thead>
                    <tr>
                        <th class="w-12 max-sm:hidden">#</th>
                        <th>Feature</th>
                        <th>Icon</th>
                        <th class="is-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($features as $index => $row)
                        <tr>
                            <td class="text-label-3 ui-num max-sm:hidden">{{ $index + 1 }}</td>
                            <td><span class="ui-table__title">{{ $row->name }}</span></td>
                            <td>
                                @if($row->image)
                                    <img src="{{ asset('public/'.$row->image) }}" alt="" class="size-9 rounded-sm bg-surface-3 object-contain p-1">
                                @else
                                    <span class="text-label-4">—</span>
                                @endif
                            </td>
                            <td class="is-end">
                                <x-ui.button size="sm" variant="gray" icon="fa-solid fa-pen" label="Edit {{ $row->name }}" :href="route('feature.edit', $row->id)" />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-ui.card>
</div>

@include('master.script')
@endsection
