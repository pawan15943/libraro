@extends('layouts.library')
@section('content')

<link rel="stylesheet" href="{{ asset('public/css/toggle-feature.css') }}?v={{ time() }}">

@php
    $categoriesNormalized = $data->map(function($item) {
        $cat = trim($item->category ?? 'Other');
        if (strtolower($cat) === 'dasboard' || strtolower($cat) === 'dashboard') {
            $cat = 'Dashboard';
        } elseif (strtolower($cat) === 'booking form') {
            $cat = 'Booking Form';
        } elseif (strtolower($cat) === 'menu') {
            $cat = 'Menu';
        } elseif (strtolower($cat) === 'button') {
            $cat = 'Button';
        } elseif (strtolower($cat) === 'option') {
            $cat = 'Option';
        }
        $item->normalized_category = $cat;
        return $item;
    });

    $groupedData = $categoriesNormalized->groupBy('normalized_category');
    $totalCount = $data->count();
    $hiddenCount = $data->filter(function($item) use ($hiddenFields) {
        return in_array($item->id, $hiddenFields);
    })->count();
    $visibleCount = $totalCount - $hiddenCount;
@endphp

<div class="toggle-feature-module">
    <!-- KPI Summary Metrics Cards (Mobile-First 3 Compact Columns) -->
    <div class="kpi-row">
        <div class="kpi-card kpi-total">
            <div class="kpi-icon-box">
                <i class="fa-solid fa-list-check"></i>
            </div>
            <div class="kpi-info">
                <div class="kpi-label">Total</div>
                <div class="kpi-value" id="totalFeaturesCount">{{ $totalCount }}</div>
            </div>
        </div>
        <div class="kpi-card kpi-visible">
            <div class="kpi-icon-box">
                <i class="fa-solid fa-eye"></i>
            </div>
            <div class="kpi-info">
                <div class="kpi-label">Visible</div>
                <div class="kpi-value" id="visibleFeaturesCount">{{ $visibleCount }}</div>
            </div>
        </div>
        <div class="kpi-card kpi-hidden">
            <div class="kpi-icon-box">
                <i class="fa-solid fa-eye-slash"></i>
            </div>
            <div class="kpi-info">
                <div class="kpi-label">Hidden</div>
                <div class="kpi-value" id="hiddenFeaturesCount">{{ $hiddenCount }}</div>
            </div>
        </div>
    </div>

    <!-- Toolbar: Search, Status Filter & Category Tabs -->
    <div class="toolbar-card">
        <div class="toolbar-filter-row">
            <div class="search-input-group">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input type="text" id="toggleSearchInput" class="search-input" placeholder="Search feature name or keyword..." autocomplete="off">
                <button type="button" class="search-clear-btn" id="toggleSearchClearBtn" title="Clear search">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="status-filter-group">
                <button type="button" class="status-filter-btn active" data-status-filter="all">All (<span id="statusAllCount">{{ $totalCount }}</span>)</button>
                <button type="button" class="status-filter-btn" data-status-filter="visible">Visible (<span id="statusVisibleCount">{{ $visibleCount }}</span>)</button>
                <button type="button" class="status-filter-btn" data-status-filter="hidden">Hidden (<span id="statusHiddenCount">{{ $hiddenCount }}</span>)</button>
            </div>
        </div>
        <div class="category-scroll-container">
            <button type="button" class="cat-filter-btn active" data-category="all">
                <i class="fa-solid fa-border-all"></i>
                <span>All</span>
                <span class="cat-count">{{ $totalCount }}</span>
            </button>
            @foreach($groupedData as $catName => $items)
                <button type="button" class="cat-filter-btn" data-category="{{ Str::slug($catName) }}">
                    <span>{{ $catName }}</span>
                    <span class="cat-count">{{ $items->count() }}</span>
                </button>
            @endforeach
        </div>
    </div>

    <!-- Category Sections with Feature Rows -->
    @php $itemIndex = 1; @endphp
    @foreach($groupedData as $category => $items)
        @php
            $categorySlug = Str::slug($category);
            $iconClass = 'fa-layer-group';
            $lowerCat = strtolower($category);
            if (str_contains($lowerCat, 'dashboard')) $iconClass = 'fa-chart-pie';
            elseif (str_contains($lowerCat, 'booking')) $iconClass = 'fa-file-signature';
            elseif (str_contains($lowerCat, 'menu')) $iconClass = 'fa-bars';
            elseif (str_contains($lowerCat, 'button')) $iconClass = 'fa-toggle-on';
            elseif (str_contains($lowerCat, 'option')) $iconClass = 'fa-sliders';
        @endphp
        <div class="category-section" data-cat-section="{{ $categorySlug }}">
            <div class="category-header">
                <h5 class="category-header-title">
                    <i class="fa-solid {{ $iconClass }} category-icon"></i>
                    <span>{{ $category }}</span>
                </h5>
                <span class="category-badge">{{ $items->count() }} {{ Str::plural('option', $items->count()) }}</span>
            </div>
            <div class="category-body">
                @foreach($items as $value)
                    @php
                        $isHidden = in_array($value->id, $hiddenFields);
                        $desc = !empty($value->description) ? $value->description : 'Configure display and accessibility for ' . strtolower($value->name) . '.';
                    @endphp
                    <div class="feature-row {{ $isHidden ? 'is-hidden' : 'is-visible' }}" 
                         id="featureRow{{ $value->id }}"
                         data-id="{{ $value->id }}"
                         data-name="{{ strtolower($value->name) }}"
                         data-desc="{{ strtolower($desc) }}"
                         data-category="{{ $categorySlug }}"
                         data-state="{{ $isHidden ? 'hidden' : 'visible' }}">
                        
                        <!-- Top Row: Serial Badge, Feature Title & Description -->
                        <div class="feature-info-col">
                            <div class="feature-number">{{ $itemIndex++ }}</div>
                            <div class="feature-details">
                                <div class="feature-name">{{ $value->name }}</div>
                                <p class="feature-desc">{{ $desc }}</p>
                            </div>
                        </div>

                        <!-- Next Row: Status Indicator on Left, Toggle Switch on Right -->
                        <div class="feature-action-col">
                            <span class="status-pill {{ $isHidden ? 'is-hidden' : 'is-visible' }}" id="statusPill{{ $value->id }}">
                                <i class="fa-solid {{ $isHidden ? 'fa-eye-slash' : 'fa-circle-check' }} me-1"></i>
                                <span>{{ $isHidden ? 'Hidden' : 'Visible' }}</span>
                            </span>

                            <div class="show-hide-toggle-control {{ $isHidden ? 'is-hidden' : 'is-visible' }}" id="switchControl{{ $value->id }}">
                                <span class="toggle-side-label label-show" title="Click to Show">
                                    <i class="fa-solid fa-eye"></i> Show
                                </span>
                                <div class="form-check form-switch m-0">
                                    <input class="form-check-input toggle_hide" 
                                           type="checkbox" 
                                           id="myToggle{{ $value->id }}" 
                                           data-hide_field="{{ $value->id }}" 
                                           data-name="{{ $value->name }}"
                                           role="switch"
                                           aria-label="Toggle {{ $value->name }}"
                                           {{ $isHidden ? 'checked' : '' }}>
                                </div>
                                <span class="toggle-side-label label-hide" title="Click to Hide">
                                    Hide <i class="fa-solid fa-eye-slash"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach

    <!-- Empty Search State -->
    <div class="empty-search-state" id="emptySearchState">
        <div class="empty-search-icon">
            <i class="fa-solid fa-filter-circle-xmark"></i>
        </div>
        <div class="empty-search-title">No matching options found</div>
        <p class="empty-search-desc">Try searching with a different keyword or resetting the category filter.</p>
    </div>
</div>

<script>
$(document).ready(function () {
    var activeCategory = 'all';
    var activeStatus = 'all';

    // Toast Notification Helper
    function showToast(type, msg) {
        if (typeof toastr !== 'undefined') {
            toastr.options = {
                "closeButton": true,
                "progressBar": true,
                "positionClass": "toast-top-right",
                "timeOut": "2000",
                "preventDuplicates": false
            };
            if (type === 'success') {
                toastr.success(msg);
            } else if (type === 'info') {
                toastr.info(msg);
            } else {
                toastr.error(msg);
            }
        } else if (typeof Swal !== 'undefined') {
            Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 2000,
                timerProgressBar: true
            }).fire({
                icon: type === 'info' ? 'info' : (type === 'error' ? 'error' : 'success'),
                title: msg
            });
        }
    }

    // Refresh KPI Counts Dynamically
    function updateKpiCounters() {
        var total = $('.feature-row').length;
        var hidden = $('.feature-row[data-state="hidden"]').length;
        var visible = total - hidden;

        $('#totalFeaturesCount').text(total);
        $('#visibleFeaturesCount').text(visible);
        $('#hiddenFeaturesCount').text(hidden);

        $('#statusAllCount').text(total);
        $('#statusVisibleCount').text(visible);
        $('#statusHiddenCount').text(hidden);
    }

    // Click on Left "Show" Label -> switches to Show (unchecked)
    $(document).on('click', '.toggle-side-label.label-show', function (e) {
        e.preventDefault();
        var $control = $(this).closest('.show-hide-toggle-control');
        var $input = $control.find('.toggle_hide');
        if ($input.prop('checked')) {
            $input.prop('checked', false).trigger('change');
        }
    });

    // Click on Right "Hide" Label -> switches to Hide (checked)
    $(document).on('click', '.toggle-side-label.label-hide', function (e) {
        e.preventDefault();
        var $control = $(this).closest('.show-hide-toggle-control');
        var $input = $control.find('.toggle_hide');
        if (!$input.prop('checked')) {
            $input.prop('checked', true).trigger('change');
        }
    });

    // Handle Toggle Checkbox Change
    $(document).on('change', '.toggle_hide', function () {
        var $input = $(this);
        var isChecked = $input.prop('checked'); // checked == hidden
        var featureId = $input.data('hide_field');
        var featureName = $input.data('name') || 'Option';
        var $control = $('#switchControl' + featureId);
        var $row = $('#featureRow' + featureId);
        var $pill = $('#statusPill' + featureId);

        // Optimistic UI updates
        $control.addClass('toggle-row-updating');
        if (isChecked) {
            $control.removeClass('is-visible').addClass('is-hidden');
            $row.removeClass('is-visible').addClass('is-hidden').attr('data-state', 'hidden');
            $pill.removeClass('is-visible').addClass('is-hidden')
                 .html('<i class="fa-solid fa-eye-slash me-1"></i><span>Hidden</span>');
        } else {
            $control.removeClass('is-hidden').addClass('is-visible');
            $row.removeClass('is-hidden').addClass('is-visible').attr('data-state', 'visible');
            $pill.removeClass('is-hidden').addClass('is-visible')
                 .html('<i class="fa-solid fa-circle-check me-1"></i><span>Visible</span>');
        }

        updateKpiCounters();

        // If currently filtering by status, re-apply filter to handle newly hidden/visible row
        if (activeStatus !== 'all') {
            applyCombinedFilters();
        }

        // Collect all currently checked (hidden) field IDs
        var selectedIds = [];
        $('.toggle_hide:checked').each(function () {
            selectedIds.push($(this).data('hide_field'));
        });

        // AJAX POST to branch.update.hidefield
        $.ajax({
            url: "{{ route('branch.update.hidefield') }}",
            type: "POST",
            data: {
                _token: '{{ csrf_token() }}',
                hidden_ids: selectedIds
            },
            success: function (response) {
                $control.removeClass('toggle-row-updating');
                if (isChecked) {
                    showToast('info', featureName + ' is now Hidden');
                } else {
                    showToast('success', featureName + ' is now Visible (Shown)');
                }
            },
            error: function () {
                $control.removeClass('toggle-row-updating');
                // Revert state on network/server error
                $input.prop('checked', !isChecked);
                if (!isChecked) {
                    $control.removeClass('is-visible').addClass('is-hidden');
                    $row.removeClass('is-visible').addClass('is-hidden').attr('data-state', 'hidden');
                    $pill.removeClass('is-visible').addClass('is-hidden')
                         .html('<i class="fa-solid fa-eye-slash me-1"></i><span>Hidden</span>');
                } else {
                    $control.removeClass('is-hidden').addClass('is-visible');
                    $row.removeClass('is-hidden').addClass('is-visible').attr('data-state', 'visible');
                    $pill.removeClass('is-hidden').addClass('is-visible')
                         .html('<i class="fa-solid fa-circle-check me-1"></i><span>Visible</span>');
                }
                updateKpiCounters();
                if (activeStatus !== 'all') {
                    applyCombinedFilters();
                }
                showToast('error', 'Failed to update ' + featureName + '. Please try again.');
            }
        });
    });

    // Multi-Filter Engine: Keyword Search + Category Filter + Status Filter
    function applyCombinedFilters() {
        var query = $('#toggleSearchInput').val().toLowerCase().trim();
        var totalMatches = 0;

        if (query.length > 0) {
            $('#toggleSearchClearBtn').show();
        } else {
            $('#toggleSearchClearBtn').hide();
        }

        $('.category-section').each(function () {
            var $section = $(this);
            var sectionCat = $section.data('cat-section');
            var sectionMatchCount = 0;

            // Check if this category section is allowed
            var catAllowed = (activeCategory === 'all' || activeCategory === sectionCat);

            $section.find('.feature-row').each(function () {
                var $row = $(this);
                var rowName = $row.data('name') || '';
                var rowDesc = $row.data('desc') || '';
                var rowState = $row.attr('data-state') || 'visible';

                var matchesKeyword = (query === '' || rowName.indexOf(query) !== -1 || rowDesc.indexOf(query) !== -1);
                var matchesStatus = (activeStatus === 'all' || activeStatus === rowState);

                if (catAllowed && matchesKeyword && matchesStatus) {
                    $row.show();
                    sectionMatchCount++;
                    totalMatches++;
                } else {
                    $row.hide();
                }
            });

            if (sectionMatchCount > 0) {
                $section.show();
            } else {
                $section.hide();
            }
        });

        if (totalMatches === 0) {
            $('#emptySearchState').show();
        } else {
            $('#emptySearchState').hide();
        }
    }

    // Search input listener
    $('#toggleSearchInput').on('input', applyCombinedFilters);

    // Clear search button
    $('#toggleSearchClearBtn').on('click', function () {
        $('#toggleSearchInput').val('').focus();
        applyCombinedFilters();
    });

    // Category filter tabs
    $('.cat-filter-btn').on('click', function () {
        $('.cat-filter-btn').removeClass('active');
        $(this).addClass('active');
        activeCategory = $(this).data('category');
        applyCombinedFilters();
    });

    // Status filter switcher
    $('.status-filter-btn').on('click', function () {
        $('.status-filter-btn').removeClass('active');
        $(this).addClass('active');
        activeStatus = $(this).data('status-filter');
        applyCombinedFilters();
    });
});
</script>

@endsection