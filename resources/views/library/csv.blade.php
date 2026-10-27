@extends('layouts.library')

@section('content')
<link rel="stylesheet" href="{{ asset('public/css/learner-import.css') }}?v={{ time() }}">

@php
    // Admins reach this page through configration.upload, so their helper routes differ.
    $isAdmin = Auth::guard('web')->check();
    $exportUrl = $isAdmin ? route('web.export.invalid.records') : route('library.export.invalid.records');
    $clearUrl = $isAdmin ? route('web.clear.session') : route('library.clear.session');
    $invalidRecords = session('invalidRecords', []);
    $hasResult = session()->has('successCount') || count($invalidRecords) > 0;
    $importedCount = (int) session('successCount', 0);
    // Columns the importer reads (see Controller::validateAndInsert); the first group is required.
    $requiredColumns = ['name', 'mobile', 'plan', 'plan_type', 'start_date', 'paid_amount', 'pending_amount'];
    $optionalColumns = ['seat_no', 'email', 'locker', 'locker_no'];
@endphp

<div class="learner-import-module" id="learnerImport"
     data-required-columns='@json($requiredColumns)'
     data-clear-url="{{ $clearUrl }}"
     data-csrf="{{ csrf_token() }}">

@can('has-permission', 'Import Student')

    {{-- Toolbar: what this page does + the two things people look for first. --}}
    <div class="li-toolbar">
        <p class="li-hint">Add many learners at once from a CSV file. Check the file here before it is imported.</p>
        <div class="li-toolbar-actions">
            <button type="button" class="li-btn li-btn-ghost" data-bs-toggle="modal" data-bs-target="#liRulesModal">
                <i class="fa-regular fa-circle-question"></i> Import rules
            </button>
            <a href="{{ asset('public/sample/learners-data-sample.csv') }}" class="li-btn li-btn-soft" download="learners-data-sample.csv">
                <i class="fa-solid fa-download"></i> Sample CSV
            </a>
        </div>
    </div>

    @if($errors->any())
        <div class="li-banner is-error" role="alert">
            <i class="fa-solid fa-circle-exclamation"></i>
            <div>
                <p class="li-banner-title">The file could not be imported</p>
                @foreach($errors->all() as $error)
                    <p class="li-banner-text">{{ $error }}</p>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Result of the last import: counts first, then the rows that need fixing. --}}
    @if($hasResult)
        <section class="li-card li-result" id="liResult" aria-label="Import result">
            <div class="li-result-head">
                <div>
                    <p class="li-eyebrow">Last import</p>
                    <h5 class="li-card-title">
                        @if(count($invalidRecords) === 0)
                            All learners were imported
                        @elseif($importedCount === 0)
                            No learners were imported
                        @else
                            Imported with some issues
                        @endif
                    </h5>
                </div>
                <div class="li-result-stats">
                    <div class="li-stat is-ok">
                        <span class="li-stat-icon"><i class="fa-solid fa-check"></i></span>
                        <div><strong>{{ $importedCount }}</strong><span>Imported</span></div>
                    </div>
                    <div class="li-stat {{ count($invalidRecords) ? 'is-bad' : 'is-neutral' }}">
                        <span class="li-stat-icon"><i class="fa-solid fa-xmark"></i></span>
                        <div><strong>{{ count($invalidRecords) }}</strong><span>Need fixing</span></div>
                    </div>
                </div>
            </div>

            @if(count($invalidRecords))
                <p class="li-result-help">Fix these rows in your file and upload only them again.@if($importedCount > 0) The {{ $importedCount }} imported learners are already saved.@endif</p>
                <div class="li-errors">
                    @foreach($invalidRecords as $index => $record)
                        @php
                            $message = $record['error'] ?? $record['error_message'] ?? 'Unknown error';
                            $message = is_array($message) ? implode(' ', \Illuminate\Support\Arr::flatten($message)) : $message;
                        @endphp
                        <div class="li-error-row">
                            <span class="li-error-index">{{ $index + 1 }}</span>
                            <div class="li-error-main">
                                <p class="li-error-name">{{ $record['name'] ?? 'No name' }}
                                    <span>{{ collect([$record['mobile'] ?? null, $record['plan'] ?? null, $record['plan_type'] ?? null, $record['start_date'] ?? null])->filter()->implode(' · ') }}</span>
                                </p>
                                <p class="li-error-text">{{ $message }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="li-result-actions">
                    <a href="{{ $exportUrl }}" class="li-btn li-btn-soft"><i class="fa-solid fa-file-arrow-down"></i> Download rows to fix</a>
                    <button type="button" class="li-btn li-btn-ghost" id="liClearResult"><i class="fa-solid fa-xmark"></i> Dismiss</button>
                </div>
            @endif
        </section>
    @endif

    <div class="li-layout">
        {{-- Main column: the three steps. --}}
        <div class="li-main">
            <ol class="li-steps" aria-label="Import steps">
                <li class="li-step is-done" data-step="1"><span>1</span> Get the sample</li>
                <li class="li-step is-current" data-step="2"><span>2</span> Choose your file</li>
                <li class="li-step" data-step="3"><span>3</span> Check &amp; import</li>
            </ol>

            <section class="li-card">
                <form action="{{ route('library.csv.upload') }}" method="POST" enctype="multipart/form-data" id="liForm" novalidate>
                    @csrf
                    <input type="file" name="csv_file" id="liFile" class="li-file-input" accept=".csv,text/csv" required>

                    {{-- Empty state: the whole area is a drop target and a button. --}}
                    <label for="liFile" class="li-drop" id="liDrop">
                        <span class="li-drop-icon"><i class="fa-solid fa-cloud-arrow-up"></i></span>
                        <span class="li-drop-title">Drop your CSV file here</span>
                        <span class="li-drop-sub">or <u>browse</u> from your computer · .csv only</span>
                    </label>

                    {{-- Chosen file + what the browser found in it. --}}
                    <div class="li-file d-none" id="liFileCard">
                        <div class="li-file-row">
                            <span class="li-file-icon"><i class="fa-solid fa-file-csv"></i></span>
                            <div class="li-file-info">
                                <p class="li-file-name" id="liFileName">file.csv</p>
                                <p class="li-file-meta" id="liFileMeta">0 KB</p>
                            </div>
                            <button type="button" class="li-icon-btn" id="liFileRemove" aria-label="Remove file"><i class="fa-solid fa-xmark"></i></button>
                        </div>
                        <ul class="li-checks" id="liChecks" aria-live="polite"></ul>
                        <div class="li-preview d-none" id="liPreview">
                            <p class="li-preview-title">First rows in your file</p>
                            <div class="li-preview-scroll"><table class="li-preview-table" id="liPreviewTable"></table></div>
                        </div>
                    </div>

                    <button type="submit" class="li-btn li-btn-primary li-submit" id="liSubmit" disabled>
                        <i class="fa-solid fa-file-import"></i> <span>Import learners</span>
                    </button>
                </form>
            </section>
        </div>

        {{-- Side column: values the file must match. --}}
        <aside class="li-side">
            <section class="li-card">
                <p class="li-side-title">Columns</p>
                <p class="li-side-text">Keep the header row exactly like the sample.</p>
                <div class="li-chips">
                    @foreach($requiredColumns as $column)
                        <code class="li-chip is-required" title="Required">{{ $column }}</code>
                    @endforeach
                    @foreach($optionalColumns as $column)
                        <code class="li-chip" title="Optional">{{ $column }}</code>
                    @endforeach
                </div>
                <p class="li-legend"><span class="li-legend-dot"></span> Required</p>
            </section>

            <section class="li-card">
                <p class="li-side-title">Plans <span class="li-count">{{ count($plans ?? []) }}</span></p>
                <p class="li-side-text">Use one of these in the <code>plan</code> column.</p>
                @if(!empty($plans) && count($plans))
                    <div class="li-chips">
                        @foreach($plans as $plan)
                            <span class="li-chip is-value">{{ $plan->name }}</span>
                        @endforeach
                    </div>
                @else
                    <p class="li-empty">No plans yet. Add plans first.</p>
                @endif
            </section>

            <section class="li-card">
                <p class="li-side-title">Shifts <span class="li-count">{{ count($plantypes ?? []) }}</span></p>
                <p class="li-side-text">Use one of these in <code>plan_type</code>. Prices are for 1 month.</p>
                @if(!empty($plantypes) && count($plantypes))
                    <ul class="li-list">
                        @foreach($plantypes as $shift)
                            <li>
                                <div>
                                    <p class="li-list-title">{{ $shift->plan_type }}</p>
                                    <p class="li-list-sub">
                                        @if($shift->start_time && $shift->end_time)
                                            {{ \Carbon\Carbon::parse($shift->start_time)->format('h:i A') }} – {{ \Carbon\Carbon::parse($shift->end_time)->format('h:i A') }}
                                        @else
                                            —
                                        @endif
                                    </p>
                                </div>
                                <span class="li-price">₹{{ number_format((float) $shift->plan_price) }}</span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="li-empty">No shifts priced for a 1 month plan yet.</p>
                @endif
            </section>
        </aside>
    </div>

    {{-- Import rules --}}
    <div class="modal fade li-modal" id="liRulesModal" tabindex="-1" aria-labelledby="liRulesTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="liRulesTitle">Import rules</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <ol class="li-rules">
                        <li><strong>Match your setup.</strong> Plan, shift and price must match what is set up in your library.</li>
                        <li><strong>One row per shift.</strong> A learner in both First Half and Second Half needs two rows. Hourly shifts work the same way.</li>
                        <li><strong>Paid amount means paid.</strong> Any amount in <code>paid_amount</code> is recorded as received.</li>
                        <li><strong>Extended learners.</strong> Enter the real start date; the extension is worked out from your library's extension policy.</li>
                        <li><strong>Expired learners.</strong> To keep old records, enter their real start date.</li>
                        <li><strong>Dates.</strong> Use the same date format as the sample file (DD-MM-YYYY).</li>
                        <li><strong>Mobile.</strong> 10 digits, numbers only.</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

@else
    <section class="li-card li-denied">
        <span class="li-denied-icon"><i class="fa-solid fa-lock"></i></span>
        <h5 class="li-card-title">You can't import learners</h5>
        <p class="li-side-text">Ask the library owner to give you the Import Student permission.</p>
    </section>
@endcan
</div>

<script src="{{ asset('public/js/learner-import.js') }}?v={{ time() }}"></script>
@endsection
