@extends('layouts.library')

@section('content')
@php
$planEndDate = $customer->plan_end_date;
$today = \Carbon\Carbon::today();

if ($planEndDate) {
    $endDate = \Carbon\Carbon::parse($planEndDate);
    $diffInDays = $today->diffInDays($endDate, false); // negative if in past
    $extendDays = function_exists('getExtendDays') ? getExtendDays() : 0;
    $inextendDate = $endDate->copy()->addDays($extendDays);
    $diffExtendDay = $today->diffInDays($inextendDate, false);

    if ($diffInDays < 0 && $diffExtendDay < 0) {
        $statusText = 'Expired ' . abs($diffInDays) . ' days ago';
        $statusClass = 'status-expired';
        $statusIcon = 'fa-solid fa-circle-xmark';
    } elseif ($diffInDays < 0 && $diffExtendDay > 0) {
        $statusText = 'Extension: ' . abs($diffExtendDay) . ' days left';
        $statusClass = 'status-warning';
        $statusIcon = 'fa-solid fa-clock-rotate-left';
    } elseif ($diffInDays < 0 && $diffExtendDay == 0) {
        $statusText = 'Extension ends today';
        $statusClass = 'status-warning';
        $statusIcon = 'fa-solid fa-triangle-exclamation';
    } elseif ($diffInDays == 0) {
        $statusText = 'Expires today';
        $statusClass = 'status-warning';
        $statusIcon = 'fa-solid fa-triangle-exclamation';
    } elseif ($diffInDays == 1) {
        $statusText = 'Expires in 1 day';
        $statusClass = 'status-warning';
        $statusIcon = 'fa-solid fa-clock';
    } elseif ($diffInDays <= 5) {
        $statusText = 'Expires in ' . $diffInDays . ' days';
        $statusClass = 'status-warning';
        $statusIcon = 'fa-solid fa-clock';
    } else {
        $statusText = 'Active (Expires in ' . $diffInDays . ' days)';
        $statusClass = 'status-active';
        $statusIcon = 'fa-solid fa-circle-check';
    }
} else {
    $statusText = 'Active';
    $statusClass = 'status-active';
    $statusIcon = 'fa-solid fa-circle-check';
}

$planDetails = getPlanStatusDetails($customer->plan_end_date);
$class = $planDetails['class'];
@endphp

<link rel="stylesheet" href="{{ asset('public/css/learner-swap.css') }}?v={{ time() }}" />

<input id="swap_plan_type_id" type="hidden" name="plan_type_id" value="{{ $customer->plan_type_id }}">

<div class="learner-swap-module">
    <div class="learner-swap-wrapper">

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                <i class="fa-solid fa-circle-exclamation me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- Mobile Back Navigation Bar --}}
        <!-- <div class="mobile-nav-bar d-md-none mb-2">
            <a href="{{ route('learners') }}" class="btn-mobile-back">
                <i class="fa-solid fa-arrow-left me-1"></i> Back to Learners
            </a>
        </div> -->

        {{-- SEAT INFO HERO CARD (GLASSMORPHISM) --}}
        <div class="learner-seat-header-card">
            <div class="seat-header-main">
                <div class="seat-header-identity">
                    <div class="seat-header-avatar-box">
                        @if($customer->profile_picture)
                            <img id="topSeatAvatarImg" src="{{ asset($customer->profile_picture) }}" alt="{{ $customer->name }}" class="avatar-user-photo">
                        @else
                            <img id="topSeatAvatarImg" src="{{ asset($customer->image) }}" alt="Seat" class="avatar-seat-chair {{ $class }}">
                        @endif
                    </div>
                    <div class="seat-header-info">
                        <div class="seat-badge-row">
                            <span class="seat-status-badge {{ $statusClass }}">
                                <i class="{{ $statusIcon }} me-1"></i>{{ $statusText }}
                            </span>
                        </div>
                        <h3 class="seat-title text-uppercase">
                            {{ strtoupper($customer->name) }}
                        </h3>
                        <p class="seat-subtitle">
                            <span>UID: <strong class="seat-uid-tag">{{ $customer->learner_no ?? ('#' . $customer->id) }}</strong></span>
                        </p>
                    </div>
                </div>
                <div class="seat-header-actions">
                    <a href="{{ route('learners') }}" class="btn-seat-back btn-back-desktop" title="Go Back">
                        <i class="fa-solid fa-arrow-left"></i> <span class="btn-back-text">Go Back</span>
                    </a>
                    {{-- Mobile Collapse/Expand Toggle Arrow (Closed by default on mobile) --}}
                    <button type="button" class="btn-seat-collapse is-collapsed" id="btnToggleDetails" title="Show / Hide Details" aria-expanded="false">
                        <i class="fa-solid fa-chevron-down toggle-icon"></i>
                    </button>
                </div>
            </div>

            @php
                $currentSeatNo = $customer->seat_no ?? ($customer->learner->seat_no ?? null);
                $currentBranchId = $customer->branch_id ?? ($customer->learner->branch_id ?? getCurrentBranch());
                $floorDisplay = 'Ground Floor';
                if ($currentSeatNo && is_numeric($currentSeatNo)) {
                    $floorObj = \App\Models\Floor::withoutGlobalScopes()
                        ->where('branch_id', $currentBranchId)
                        ->where('from_seat', '<=', (int)$currentSeatNo)
                        ->where('to_seat', '>=', (int)$currentSeatNo)
                        ->whereNull('deleted_at')
                        ->first();
                    if ($floorObj && !empty($floorObj->name)) {
                        $floorDisplay = str_ends_with(strtolower($floorObj->name), 'floor') ? $floorObj->name : ($floorObj->name . ' Floor');
                    } else {
                        $firstFloor = \App\Models\Floor::withoutGlobalScopes()
                            ->where('branch_id', $currentBranchId)
                            ->whereNull('deleted_at')
                            ->first();
                        if ($firstFloor && !empty($firstFloor->name)) {
                            $floorDisplay = str_ends_with(strtolower($firstFloor->name), 'floor') ? $firstFloor->name : ($firstFloor->name . ' Floor');
                        }
                    }
                }
            @endphp

            {{-- Engaging Mobile-only Seat No & Floor Strip --}}
            <div class="seat-header-mobile-meta">
                <div class="mobile-meta-pill pill-seat">
                    <span class="meta-pill-icon"><i class="fa-solid fa-chair"></i></span>
                    <div class="meta-pill-text">
                        <span class="meta-pill-label">Seat No</span>
                        <strong class="meta-pill-val">{{ $currentSeatNo ? ('#' . $currentSeatNo) : 'Not Assigned' }}</strong>
                    </div>
                </div>
                <div class="mobile-meta-divider"></div>
                <div class="mobile-meta-pill pill-floor">
                    <span class="meta-pill-icon"><i class="fa-solid fa-layer-group"></i></span>
                    <div class="meta-pill-text">
                        <span class="meta-pill-label">Floor</span>
                        <strong class="meta-pill-val">{{ $floorDisplay }}</strong>
                    </div>
                </div>
            </div>

            {{-- 4 GLASSMORPHIC DETAIL TILES (Closed by default on mobile) --}}
            <div class="glass-info-grid is-collapsed" id="glassInfoGrid">
                {{-- Tile 1: Plan Type (e.g. Monthly, Quarterly, Yearly) --}}
                <div class="glass-tile tile-plan">
                    <div class="glass-tile-icon">
                        <i class="fa-solid fa-calendar-days"></i>
                    </div>
                    <div class="glass-tile-content">
                        <div class="glass-tile-label">Plan Type</div>
                        <div class="glass-tile-value">{{ $customer->plan_name ?? 'Monthly' }}</div>
                    </div>
                </div>

                {{-- Tile 2: Shift / Plan --}}
                <div class="glass-tile tile-shift">
                    <div class="glass-tile-icon">
                        <i class="fa-regular fa-clock"></i>
                    </div>
                    <div class="glass-tile-content">
                        <div class="glass-tile-label">Shift / Plan</div>
                        <div class="glass-tile-value">{{ $customer->plan_type_name ?? 'N/A' }}</div>
                    </div>
                </div>

                {{-- Tile 3: Valid Till --}}
                <div class="glass-tile tile-date">
                    <div class="glass-tile-icon">
                        <i class="fa-regular fa-calendar-check"></i>
                    </div>
                    <div class="glass-tile-content">
                        <div class="glass-tile-label">Valid Till</div>
                        <div class="glass-tile-value">
                            {{ $customer->plan_end_date ? \Carbon\Carbon::parse($customer->plan_end_date)->format('d M, Y') : 'N/A' }}
                        </div>
                    </div>
                </div>

                {{-- Tile 4: Contact Mobile --}}
                <div class="glass-tile tile-contact">
                    <div class="glass-tile-icon">
                        <i class="fa-solid fa-phone"></i>
                    </div>
                    <div class="glass-tile-content">
                        <div class="glass-tile-label">Mobile</div>
                        <div class="glass-tile-value">
                            @if($customer->mobile)
                                <a href="tel:{{ $customer->mobile }}">{{ $customer->mobile }}</a>
                            @else
                                <span>Not Provided</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- SWAP SEAT FORM CARD (GLASSMORPHISM - NO HEADING) --}}
        <form action="{{ route('learners.swap-seat', $customer->id) }}" method="POST" enctype="multipart/form-data" id="swapseat">
            @csrf
            @method('PUT')
            <input id="user_id" type="hidden" name="learner_id" value="{{ $customer->id }}">
            <input type="hidden" value="{{ $customer->seat_no }}" id="swap_old_value">
            <input type="hidden" id="swap_plan_type_id" value="{{ $customer->plan_type_id }}">

            <div class="swap-card">
                <div class="swap-card-body">
                    <div class="swap-tip-box">
                        <i class="fa-solid fa-circle-info"></i>
                        <div>
                            You can swap the learner to any vacant seat available in their current shift.
                        </div>
                    </div>

                    <div class="seat-swap-interactive-row">
                        {{-- Current Seat Block --}}
                        <div class="swap-seat-block">
                            <label class="form-label">
                                <span>Current Seat</span>
                                <span class="form-label-badge">Allocated</span>
                            </label>
                            <div class="current-seat-display-box">
                                <span><i class="fa-solid fa-chair chair-icon me-2"></i> {{ getSeatDisplayShortFloorName($customer->seat_no) ?? 'Gen' }} - {{ $customer->plan_type_name }}</span>
                                <i class="fa-solid fa-lock lock-badge" title="Locked Allotment"></i>
                            </div>
                            <input class="form-control" value="{{ $customer->seat_no ?? 'Gen' }} - {{ $customer->plan_type_name }}" type="hidden">
                        </div>

                        {{-- Arrow Divider --}}
                        <div class="swap-arrow-divider" title="Swap Seat">
                            <i class="fa-solid fa-arrow-right-arrow-left"></i>
                        </div>

                        {{-- New Seat Block with Simple Real-time Search --}}
                        <div class="swap-seat-block target-seat-block">
                            <label class="form-label" for="seat_search_input">
                                <span>New Target Seat</span>
                                <span class="form-label-badge" id="seatAvailableBadge">{{ count($newAvailableSeats) }} Available</span>
                            </label>

                            {{-- Native select kept in sync for form submission and AJAX listeners --}}
                            <select name="seat_id" id="new_seat_id" class="d-none @error('seat_id') is-invalid @enderror">
                                <option value="">Select or General Seat</option>
                                <option value="">General Seat</option>
                                @foreach($newAvailableSeats as $key => $value)
                                <option value="{{ $value['main'] }}">{{ $value['display'] }}</option>
                                @endforeach
                            </select>

                            {{-- Searchable Combobox Field --}}
                            <div class="seat-search-combobox" id="seatSearchCombobox">
                                <div class="seat-search-input-wrapper" id="seatSearchInputWrapper">
                                    <span class="seat-search-icon">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                    </span>
                                    <input 
                                        type="text" 
                                        id="seat_search_input" 
                                        class="seat-search-input" 
                                        placeholder="Search seat (e.g. 12, Floor 1)..." 
                                        autocomplete="off"
                                        spellcheck="false"
                                    >
                                    <button type="button" class="seat-search-clear-btn" id="seatSearchClearBtn" title="Clear selection" style="display: none;">
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                    <button type="button" class="seat-search-dropdown-arrow" id="seatSearchDropdownArrow" title="Show all seats" tabindex="-1">
                                        <i class="fa-solid fa-chevron-down"></i>
                                    </button>
                                </div>

                                {{-- Dropdown List --}}
                                <div class="seat-dropdown-menu" id="seatDropdownMenu" style="display: none;">
                                    <div class="seat-dropdown-meta">
                                        <span id="seatDropdownCount">Available Seats ({{ count($newAvailableSeats) }})</span>
                                        <span class="seat-dropdown-hint">Type to filter</span>
                                    </div>
                                    <div class="seat-dropdown-list" id="seatDropdownList" role="listbox">
                                        {{-- General Seat Option --}}
                                        <div class="seat-dropdown-item" data-value="" data-text="General Seat Unreserved" data-display="General Seat">
                                            <div class="seat-item-left">
                                                <i class="fa-solid fa-chair seat-item-icon general-icon"></i>
                                                <div class="seat-item-info">
                                                    <span class="seat-item-title">General Seat</span>
                                                    <span class="seat-item-subtitle">Unreserved / Open Seat</span>
                                                </div>
                                            </div>
                                            <span class="seat-item-tag general-tag">General</span>
                                        </div>

                                        {{-- Available Seats List --}}
                                        @foreach($newAvailableSeats as $key => $value)
                                        <div class="seat-dropdown-item" data-value="{{ $value['main'] }}" data-text="{{ strtolower($value['display'] . ' ' . $value['main'] . ' seat ' . $value['main']) }}" data-display="{{ $value['display'] }}">
                                            <div class="seat-item-left">
                                                <i class="fa-solid fa-chair seat-item-icon"></i>
                                                <div class="seat-item-info">
                                                    <span class="seat-item-title">{{ $value['display'] }}</span>
                                                    <span class="seat-item-subtitle">Seat #{{ $value['main'] }}</span>
                                                </div>
                                            </div>
                                            <span class="seat-item-tag">Available</span>
                                        </div>
                                        @endforeach

                                        {{-- No Results Match Found --}}
                                        <div class="seat-dropdown-empty" id="seatDropdownEmpty" style="display: none;">
                                            <i class="fa-solid fa-ban me-2"></i> No seats match "<span id="seatEmptyQuery"></span>"
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @error('seat_id')
                            <span class="invalid-feedback d-block" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                    </div>

                    <div class="swap-status-wrapper">
                        <label class="form-label" style="margin-bottom: 0.35rem;">Realtime Seat Availability Status</label>
                        <div id="swap_status"></div>
                    </div>
                </div>
            </div>

            {{-- ACTION BUTTON BAR (OUTSIDE BOX) --}}
            <div class="form-action-bar">
                <button type="submit" class="btn-submit-swap" id="swapsubmit">
                    <i class="fa-solid fa-right-left"></i> Swap Seat
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.getElementById("swapsubmit").disabled = true;

    document.addEventListener('DOMContentLoaded', function() {
        handleFormChanges('swapseat', "{{ $customer->id }}");

        // Simple and robust seat search combobox
        const searchInput = document.getElementById('seat_search_input');
        const clearBtn = document.getElementById('seatSearchClearBtn');
        const arrowBtn = document.getElementById('seatSearchDropdownArrow');
        const wrapper = document.getElementById('seatSearchInputWrapper');
        const combobox = document.getElementById('seatSearchCombobox');
        const dropdown = document.getElementById('seatDropdownMenu');
        const countMeta = document.getElementById('seatDropdownCount');
        const emptyState = document.getElementById('seatDropdownEmpty');
        const emptyQuery = document.getElementById('seatEmptyQuery');
        const nativeSelect = document.getElementById('new_seat_id');
        const items = Array.from(document.querySelectorAll('.seat-dropdown-item'));

        let selectedValue = '';
        let selectedDisplay = '';
        let highlightedIndex = -1;

        function updateDropdownPosition() {
            if (dropdown.style.display !== 'block') return;
            const rect = wrapper.getBoundingClientRect();
            const dropdownHeight = dropdown.offsetHeight || 190;
            const spaceBelow = window.innerHeight - rect.bottom;
            const spaceAbove = rect.top;

            // Smart collision detection: flip upwards if tight at the bottom
            if (spaceBelow < (dropdownHeight + 20) && spaceAbove > dropdownHeight) {
                dropdown.classList.add('drop-up');
            } else {
                dropdown.classList.remove('drop-up');
            }
        }

        function openDropdown() {
            dropdown.style.display = 'block';
            wrapper.classList.add('is-focused');
            updateDropdownPosition();
        }

        function closeDropdown() {
            dropdown.style.display = 'none';
            dropdown.classList.remove('drop-up');
            wrapper.classList.remove('is-focused');
            highlightedIndex = -1;
            items.forEach(el => el.classList.remove('is-highlighted'));
            // If user typed something but didn't select, restore selected label or clear
            if (selectedDisplay) {
                searchInput.value = selectedDisplay;
                clearBtn.style.display = 'inline-flex';
            } else {
                searchInput.value = '';
                clearBtn.style.display = 'none';
            }
            filterSeats('');
        }

        function filterSeats(query) {
            const cleanQuery = query.toLowerCase().trim();
            let visibleCount = 0;

            items.forEach(item => {
                const text = (item.getAttribute('data-text') || '').toLowerCase();
                const display = (item.getAttribute('data-display') || '').toLowerCase();
                const val = (item.getAttribute('data-value') || '').toLowerCase();

                if (!cleanQuery || text.includes(cleanQuery) || display.includes(cleanQuery) || val === cleanQuery) {
                    item.style.display = 'flex';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            });

            if (visibleCount === 0) {
                emptyState.style.display = 'flex';
                emptyQuery.textContent = query;
                countMeta.textContent = 'No seats found';
            } else {
                emptyState.style.display = 'none';
                countMeta.textContent = cleanQuery ? `Matching Seats (${visibleCount})` : `Available Seats (${items.length - 1})`;
            }

            highlightedIndex = -1;
            items.forEach(el => el.classList.remove('is-highlighted'));
            updateDropdownPosition();
        }

        function selectSeat(value, display) {
            selectedValue = value;
            selectedDisplay = display;

            searchInput.value = display;
            clearBtn.style.display = 'inline-flex';

            items.forEach(item => {
                if (item.getAttribute('data-value') === value) {
                    item.classList.add('is-selected');
                } else {
                    item.classList.remove('is-selected');
                }
            });

            // Update native select and fire events
            nativeSelect.value = value;
            if (window.jQuery) {
                $(nativeSelect).trigger('change');
            }
            nativeSelect.dispatchEvent(new Event('change', { bubbles: true }));

            dropdown.style.display = 'none';
            wrapper.classList.remove('is-focused');
        }

        function clearSelection() {
            selectedValue = '';
            selectedDisplay = '';
            searchInput.value = '';
            clearBtn.style.display = 'none';

            items.forEach(item => item.classList.remove('is-selected'));
            nativeSelect.value = '';

            if (window.jQuery) {
                $(nativeSelect).trigger('change');
            }
            nativeSelect.dispatchEvent(new Event('change', { bubbles: true }));

            const swapStatus = document.getElementById('swap_status');
            if (swapStatus) swapStatus.innerHTML = '';
            const submitBtn = document.getElementById('swapsubmit');
            if (submitBtn) submitBtn.disabled = true;

            filterSeats('');
            searchInput.focus();
        }

        // Event Listeners
        searchInput.addEventListener('focus', function() {
            openDropdown();
            filterSeats(this.value === selectedDisplay ? '' : this.value);
        });

        searchInput.addEventListener('input', function() {
            openDropdown();
            clearBtn.style.display = this.value ? 'inline-flex' : (selectedDisplay ? 'inline-flex' : 'none');
            filterSeats(this.value);
        });

        arrowBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            if (dropdown.style.display === 'block') {
                closeDropdown();
            } else {
                openDropdown();
                searchInput.focus();
                filterSeats('');
            }
        });

        clearBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            clearSelection();
        });

        items.forEach(item => {
            item.addEventListener('click', function(e) {
                e.stopPropagation();
                const val = this.getAttribute('data-value') || '';
                const disp = this.getAttribute('data-display') || 'General Seat';
                selectSeat(val, disp);
            });
        });

        // Close on outside click
        document.addEventListener('click', function(e) {
            if (!combobox.contains(e.target)) {
                if (dropdown.style.display === 'block') {
                    closeDropdown();
                }
            }
        });

        // Keyboard navigation
        searchInput.addEventListener('keydown', function(e) {
            const visibleItems = items.filter(el => el.style.display !== 'none');

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (dropdown.style.display !== 'block') {
                    openDropdown();
                }
                if (visibleItems.length > 0) {
                    highlightedIndex = (highlightedIndex + 1) % visibleItems.length;
                    visibleItems.forEach((el, idx) => {
                        el.classList.toggle('is-highlighted', idx === highlightedIndex);
                    });
                    visibleItems[highlightedIndex].scrollIntoView({ block: 'nearest' });
                }
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (visibleItems.length > 0) {
                    highlightedIndex = (highlightedIndex - 1 + visibleItems.length) % visibleItems.length;
                    visibleItems.forEach((el, idx) => {
                        el.classList.toggle('is-highlighted', idx === highlightedIndex);
                    });
                    visibleItems[highlightedIndex].scrollIntoView({ block: 'nearest' });
                }
            } else if (e.key === 'Enter') {
                if (dropdown.style.display === 'block' && highlightedIndex >= 0 && visibleItems[highlightedIndex]) {
                    e.preventDefault();
                    const item = visibleItems[highlightedIndex];
                    selectSeat(item.getAttribute('data-value') || '', item.getAttribute('data-display') || '');
                }
            } else if (e.key === 'Escape') {
                closeDropdown();
            }
        });

        // Reposition dynamically on resize or scroll
        window.addEventListener('resize', updateDropdownPosition);
        window.addEventListener('scroll', updateDropdownPosition, true);

        // Mobile info details collapse toggle
        const btnToggleDetails = document.getElementById('btnToggleDetails');
        const infoGrid = document.getElementById('glassInfoGrid');

        if (btnToggleDetails && infoGrid) {
            btnToggleDetails.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                const isCurrentlyCollapsed = infoGrid.classList.contains('is-collapsed');

                if (isCurrentlyCollapsed) {
                    infoGrid.classList.remove('is-collapsed');
                    btnToggleDetails.classList.remove('is-collapsed');
                    btnToggleDetails.setAttribute('aria-expanded', 'true');
                } else {
                    infoGrid.classList.add('is-collapsed');
                    btnToggleDetails.classList.add('is-collapsed');
                    btnToggleDetails.setAttribute('aria-expanded', 'false');
                }
            });
        }
    });
</script>

@endsection