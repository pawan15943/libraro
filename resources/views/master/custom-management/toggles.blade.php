{{--
    Show / Hide Options (App\Support\FeatureToggles): one switch per feature, grouped by category.
    Switch ON = shown. Saved per branch in branches.hide_field (the list of what is hidden), instantly.
--}}
<link rel="stylesheet" href="{{ asset('public/css/lb-visibility.css') }}?v={{ @filemtime(public_path('css/lb-visibility.css')) }}">

@if(!$branchId)
    @include('master.custom-management._no-branch', ['text' => 'Show / hide options are saved per branch.'])
@else
@php
    $vzGroups = \App\Support\FeatureToggles::grouped($hiddenFields ?? []);
    $vzAll = collect($vzGroups)->flatMap(fn ($g) => $g['items']);
    $vzShown = $vzAll->where('shown', true)->count();
    $vzBranch = function_exists('getCurrentBranchName') ? getCurrentBranchName() : null;
    $vzWhere = ['library' => null, 'learner' => 'Learner app', 'both' => 'Panel + learner app'];
@endphp
<div class="vz" id="vzRoot" data-url="{{ route('branch.update.hidefield') }}" data-csrf="{{ csrf_token() }}">

    {{-- summary --}}
    <section class="vz-hero">
        <span class="vz-hero-ic"><x-lb-icon name="sliders-horizontal" /></span>
        <div class="vz-hero-t">
            <b>Choose what {{ $vzBranch ? $vzBranch : 'this branch' }} uses</b>
            <p>Switch off what you don't need. It goes away from the menu, buttons, forms and the learner app, and its pages can't be opened. Changes save as you tap.</p>
        </div>
        <div class="vz-hero-stats" aria-live="polite">
            <span class="vz-stat"><b data-vz-shown>{{ $vzShown }}</b><small>shown</small></span>
            <span class="vz-stat is-off"><b data-vz-hidden>{{ $vzAll->count() - $vzShown }}</b><small>hidden</small></span>
            <span class="vz-save" data-vz-save data-state="idle"><x-lb-icon name="circle-check" class="vz-save-ok" /><span class="vz-save-spin" aria-hidden="true"></span><span data-vz-save-t>All saved</span></span>
        </div>
    </section>

    {{-- search + filter, then the category chips (jump to a section) --}}
    <div class="vz-bar">
        <label class="vz-search">
            <x-lb-icon name="search" />
            <input type="search" data-vz-search placeholder="Search options: locker, chat, attendance…" aria-label="Search options" autocomplete="off">
        </label>
        <div class="vz-filter" role="group" aria-label="Filter">
            <button type="button" class="is-on" data-vz-filter="all">All</button>
            <button type="button" data-vz-filter="hidden">Hidden</button>
            <button type="button" data-vz-filter="learner">Learner app</button>
        </div>
    </div>
    <nav class="vz-cats" aria-label="Categories">
        @foreach($vzGroups as $cKey => $g)
            <a href="#vz-{{ $cKey }}" class="vz-cat" data-vz-cat="{{ $cKey }}"><x-lb-icon :name="$g['icon']" /><span>{{ $g['title'] }}</span><em data-vz-cat-count>{{ collect($g['items'])->where('shown', false)->count() ?: '' }}</em></a>
        @endforeach
    </nav>

    <div class="vz-groups">
        @foreach($vzGroups as $cKey => $g)
            @php $gShown = collect($g['items'])->where('shown', true)->count(); @endphp
            <section class="vz-group" id="vz-{{ $cKey }}" data-vz-group="{{ $cKey }}">
                <div class="vz-group-h">
                    <span class="vz-group-ic"><x-lb-icon :name="$g['icon']" /></span>
                    <div class="vz-group-t">
                        <h3>{{ $g['title'] }}</h3>
                        <p>{{ $g['hint'] }} · <span data-vz-group-count>{{ $gShown }} of {{ count($g['items']) }} shown</span></p>
                    </div>
                    <div class="vz-group-all" role="group" aria-label="All {{ $g['title'] }}">
                        <button type="button" data-vz-all="1" title="Show every option in {{ $g['title'] }}"><x-lb-icon name="eye" /><span>Show all</span></button>
                        <button type="button" data-vz-all="0" title="Hide every option in {{ $g['title'] }}"><x-lb-icon name="eye-off" /><span>Hide all</span></button>
                    </div>
                </div>
                <div class="vz-list">
                    @foreach($g['items'] as $it)
                        <label class="vz-item {{ $it['shown'] ? '' : 'is-off' }}" data-vz-item data-where="{{ $it['where'] }}" data-text="{{ strtolower($it['label'] . ' ' . $it['hint'] . ' ' . $g['title']) }}">
                            <span class="vz-item-ic"><x-lb-icon :name="$it['icon']" /></span>
                            <span class="vz-item-t">
                                <b>{{ $it['label'] }}@if($vzWhere[$it['where']] ?? null)<em class="vz-tag {{ $it['where'] === 'learner' ? 'is-app' : '' }}"><x-lb-icon name="smartphone" />{{ $vzWhere[$it['where']] }}</em>@endif</b>
                                @if($it['hint'])<small>{{ $it['hint'] }}</small>@endif
                            </span>
                            <span class="vz-state" data-vz-state>{{ $it['shown'] ? 'Shown' : 'Hidden' }}</span>
                            <input type="checkbox" class="vz-sw" role="switch" value="{{ $it['key'] }}" @checked($it['shown']) aria-label="Show {{ $it['label'] }}">
                        </label>
                    @endforeach
                </div>
            </section>
        @endforeach

        <div class="vz-empty" data-vz-empty hidden>
            <span class="vz-empty-ic"><x-lb-icon name="search" /></span>
            <b>No option matches</b>
            <p>Try another word, or clear the search.</p>
        </div>
    </div>
</div>
<script src="{{ asset('public/js/lb-visibility.js') }}?v={{ @filemtime(public_path('js/lb-visibility.js')) }}"></script>
@endif
