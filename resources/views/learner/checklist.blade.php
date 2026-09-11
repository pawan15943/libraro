@extends('layouts.library')

@section('content')

<link rel="stylesheet" href="{{ asset('public/css/learner-checklist.css') }}?v={{ time() }}">

<div class="learner-checklist-module">
    @if(count($learners) > 0)
    <form method="POST" action="{{ route('learner.idcard.bulk') }}" target="_blank" id="bulkPrintForm">
        @csrf

        <!-- Top Action Toolbar: Right-aligned controls (no extra heading) -->
        <div class="checklist-toolbar-bar">
            <div class="toolbar-controls-group">
                <div class="print-type-select-wrap">
                    <label class="print-type-label">
                        <i class="fa-solid fa-layer-group"></i> Print Type:
                    </label>
                    <select name="print_type" class="print-type-select" required>
                        <option value="both" selected>Print Both Sides</option>
                        <option value="single">Print One Side</option>
                    </select>
                </div>

                <button type="submit" class="btn-print-bulk">
                    <i class="fa-solid fa-print"></i> Print ID Cards
                    <span class="selected-count-pill" id="selectedCountBadge">0 Selected</span>
                </button>
            </div>
        </div>

        <!-- Table Card -->
        <div class="checklist-table-card">
            <div class="table-card-header">
                <h4 class="table-card-title">
                    <i class="fa-solid fa-id-card"></i> Learners ID Card Checklist
                </h4>
                <span class="table-card-meta">
                    Total Active Learners: <b>{{ count($learners) }}</b>
                </span>
            </div>

            <div class="table-responsive">
                <table class="table checklist-custom-table text-center datatable" id="checklistTable">
                    <thead>
                        <tr>
                            <th style="width: 50px;">
                                <input type="checkbox" id="select_all" class="custom-checkbox-input" data-bs-toggle="tooltip" title="Select / Deselect All">
                            </th>
                            <th style="width: 65px;">Photo</th>
                            <th>UID</th>
                            <th style="text-align: left; padding-left: 16px;">Learner Name</th>
                            <th>Mobile Number</th>
                            <th>Father Name</th>
                            <th>Payment Status</th>
                            <th>Plan End Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($learners as $learner)
                        <tr>
                            {{-- Checkbox --}}
                            <td>
                                <input type="checkbox" name="learner_ids[]" value="{{ $learner->id }}" class="select_one custom-checkbox-input">
                            </td>

                            {{-- Photo --}}
                            <td>
                                <img src="{{ $learner->profile_picture ? asset($learner->profile_picture) : asset('public/img/student_profile.jpeg') }}" 
                                     class="learner-avatar-img" 
                                     alt="Profile Photo"
                                     onerror="this.src='{{ asset('public/img/student_profile.jpeg') }}'">
                            </td>

                            {{-- UID --}}
                            <td>
                                <span class="uid-badge">{{ $learner->learner_no }}</span>
                            </td>

                            {{-- Name --}}
                            <td style="text-align: left; padding-left: 16px;">
                                <span class="learner-name-text">{{ $learner->name ?? 'N/A' }}</span>
                            </td>

                            {{-- Mobile --}}
                            <td>
                                <span class="learner-mobile-text">+91-{{ $learner->mobile }}</span>
                            </td>

                            {{-- Father Name --}}
                            <td>
                                <span class="learner-father-text">{{ $learner->father_name ?? 'Not Available' }}</span>
                            </td>

                            {{-- Payment Status --}}
                            <td>
                                @if($learner->is_paid == 1)
                                    <span class="status-chip status-chip-paid">
                                        <i class="fa-solid fa-circle-check"></i> Paid
                                    </span>
                                @else
                                    <span class="status-chip status-chip-unpaid">
                                        <i class="fa-solid fa-circle-xmark"></i> Unpaid
                                    </span>
                                @endif
                            </td>

                            {{-- Plan End Date --}}
                            <td>
                                <span class="plan-date-text">
                                    <i class="fa-regular fa-calendar-days"></i> 
                                    {{ !empty($learner->plan_end_date) ? \Carbon\Carbon::parse($learner->plan_end_date)->format('d M, Y') : 'N/A' }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Bottom Action Bar -->
            <div class="checklist-bottom-bar">
                <span class="text-muted font-12">
                    <i class="fa-solid fa-circle-info me-1"></i> Select learners and click print to generate formatted bulk ID cards.
                </span>
                <button type="submit" class="btn-print-bulk">
                    <i class="fa-solid fa-print"></i> Print ID Cards
                    <span class="selected-count-pill" id="selectedCountBadgeBottom">0 Selected</span>
                </button>
            </div>
        </div>
    </form>
    @else
    <!-- Empty State -->
    <div class="checklist-empty-card">
        <i class="fa-solid fa-users-slash fa-3x text-muted mb-3 d-block"></i>
        <h4>No active learners found</h4>
        <p>There are currently no active learners registered in this branch to generate ID cards.</p>
    </div>
    @endif
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const selectAll = document.getElementById('select_all');
        const badgeTop = document.getElementById('selectedCountBadge');
        const badgeBottom = document.getElementById('selectedCountBadgeBottom');
        const form = document.getElementById('bulkPrintForm');

        function updateSelectedCount() {
            const checkboxes = document.querySelectorAll('.select_one');
            let count = 0;
            checkboxes.forEach(function(cb) {
                const tr = cb.closest('tr');
                if (cb.checked) {
                    count++;
                    if (tr) tr.classList.add('row-selected');
                } else {
                    if (tr) tr.classList.remove('row-selected');
                }
            });

            const text = count + ' Selected';
            if (badgeTop) badgeTop.textContent = text;
            if (badgeBottom) badgeBottom.textContent = text;

            if (selectAll && checkboxes.length > 0) {
                selectAll.checked = (count === checkboxes.length);
                selectAll.indeterminate = (count > 0 && count < checkboxes.length);
            }
        }

        if (selectAll) {
            selectAll.addEventListener('change', function() {
                const checkboxes = document.querySelectorAll('.select_one');
                checkboxes.forEach(cb => {
                    cb.checked = selectAll.checked;
                });
                updateSelectedCount();
            });
        }

        document.addEventListener('change', function(e) {
            if (e.target && e.target.classList.contains('select_one')) {
                updateSelectedCount();
            }
        });

        if (form) {
            form.addEventListener('submit', function(e) {
                const selected = document.querySelectorAll('.select_one:checked');
                if (selected.length === 0) {
                    e.preventDefault();
                    if (typeof toastr !== 'undefined') {
                        toastr.warning('Please select at least one learner to print ID cards.');
                    } else {
                        alert('Please select at least one learner to print ID cards.');
                    }
                }
            });
        }

        updateSelectedCount();
    });
</script>

@endsection