{{-- Pane header: hint, then search / grid-list switch / Add button for record lists. --}}
<div class="cm-toolbar">
    {{-- The layout already shows the master's heading + breadcrumb; only the hint stays here. --}}
    <p class="cm-pane-hint">{{ $hint ?? '' }}</p>
    <div class="cm-toolbar-controls">
        @if(!empty($listable))
            <label class="cm-search">
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                <input type="search" class="cm-search-input" placeholder="Search" aria-label="Search {{ $heading }}">
            </label>
            <div class="cm-view-switch" role="group" aria-label="Change view">
                <button type="button" data-cm-view="grid" title="Grid view" aria-label="Grid view"><i class="fa-solid fa-table-cells-large"></i></button>
                <button type="button" data-cm-view="list" title="List view" aria-label="List view"><i class="fa-solid fa-list"></i></button>
            </div>
        @endif
        @if(!empty($modal) && !empty($canAdd))
            <button type="button" class="cm-add-btn" data-cm-add="{{ $modal }}">
                <i class="fa-solid fa-plus"></i> <span>{{ $addLabel }}</span>
            </button>
        @endif
    </div>
</div>
