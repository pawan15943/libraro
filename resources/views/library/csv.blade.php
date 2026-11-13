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
    // Only for advanced shifts (Fixed hours / Direct booking), see the Shifts tab
    $shiftColumns = ['shift_start_time', 'shift_end_time', 'shift_price'];
    $templateColumns = array_merge(['seat_no', 'name', 'email', 'mobile', 'plan', 'plan_type', 'start_date', 'paid_amount', 'pending_amount', 'locker', 'locker_no'], $shiftColumns);
    $plans = collect($plans ?? []);
    $shifts = collect($plantypes ?? []);
    $planDuration = function ($plan) {
        $n = (int) $plan->plan_id;
        return match (strtoupper((string) $plan->type)) {
            'DAY' => $n . ' ' . \Illuminate\Support\Str::plural('day', $n),
            'WEEK' => $n . ' ' . \Illuminate\Support\Str::plural('week', $n),
            'MONTH' => !empty($plan->monthdays) ? $plan->monthdays . ' days' : $n . ' ' . \Illuminate\Support\Str::plural('month', $n),
            'YEAR' => $n . ' ' . \Illuminate\Support\Str::plural('year', $n),
            'CUSTOM' => 'Custom · ' . $n . ' days',
            default => '',
        };
    };
    // Failed rows grouped by their reason ("Seat Already Booked: …" → "Seat Already Booked")
    $reasonOf = function ($record) {
        $message = $record['error'] ?? $record['error_message'] ?? 'Unknown error';
        return is_array($message) ? implode(' ', \Illuminate\Support\Arr::flatten($message)) : (string) $message;
    };
    $errorGroups = collect($invalidRecords)->map(function ($record) use ($reasonOf) {
        $message = $reasonOf($record);
        return preg_match('/^([A-Z][A-Za-z ]{2,40}):/', $message, $m) ? $m[1] : 'Other';
    })->countBy()->sortDesc();
@endphp

<div class="learner-import-module" id="learnerImport"
     data-required-columns='@json($requiredColumns)'
     data-template-columns='@json($templateColumns)'
     data-clear-url="{{ $clearUrl }}"
     data-csrf="{{ csrf_token() }}">

@can('has-permission', 'Import Student')

    {{-- Header --}}
    <header class="li-head">
        <div>
            <h4 class="li-title">Import learners</h4>
            <p class="li-sub">Upload a CSV file to add many learners at once. Plan and shift names must match your setup below.</p>
        </div>
        <div class="li-head-actions">
            <button type="button" class="li-btn li-btn-ghost" data-bs-toggle="modal" data-bs-target="#liRulesModal">
                <i class="fa-regular fa-circle-question"></i> Rules
            </button>
            <a href="{{ asset('public/sample/learners-data-sample.csv') }}" class="li-btn li-btn-ghost" download="learners-data-sample.csv">
                <i class="fa-regular fa-file-lines"></i> Sample
            </a>
            <button type="button" class="li-btn li-btn-soft" id="liTemplate">
                <i class="fa-solid fa-download"></i> Blank template
            </button>
        </div>
    </header>

    @if($errors->any())
        <div class="li-alert is-error" role="alert">
            <i class="fa-solid fa-circle-exclamation"></i>
            <div>
                <p class="li-alert-title">The file could not be imported</p>
                @foreach($errors->all() as $error)
                    <p class="li-alert-text">{{ $error }}</p>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Result of the last import --}}
    @if($hasResult)
        @php $failed = count($invalidRecords); @endphp
        <section class="li-card li-result {{ $failed ? 'has-issues' : 'is-clean' }}" id="liResult" aria-label="Import result">
            <div class="li-result-head">
                <span class="li-result-icon">
                    <i class="fa-solid {{ $failed ? 'fa-triangle-exclamation' : 'fa-circle-check' }}"></i>
                </span>
                <div class="li-result-copy">
                    <p class="li-result-title">
                        @if($failed === 0)
                            All learners imported
                        @elseif($importedCount === 0)
                            No learners imported
                        @else
                            {{ $importedCount }} imported, {{ $failed }} {{ $failed === 1 ? 'needs' : 'need' }} fixing
                        @endif
                    </p>
                    <p class="li-result-text">
                        @if($failed)
                            Fix these rows in your file and upload only them again.@if($importedCount) The imported learners are already saved.@endif
                        @else
                            {{ $importedCount }} {{ \Illuminate\Support\Str::plural('learner', $importedCount) }} added.
                        @endif
                    </p>
                </div>
                <div class="li-result-nums">
                    <div class="li-num is-ok"><strong>{{ $importedCount }}</strong><span>Imported</span></div>
                    <div class="li-num {{ $failed ? 'is-bad' : '' }}"><strong>{{ $failed }}</strong><span>To fix</span></div>
                </div>
                <button type="button" class="li-icon-btn li-result-close" id="liClearResult" aria-label="Dismiss"><i class="fa-solid fa-xmark"></i></button>
            </div>

            @if($failed)
                @if($errorGroups->count() > 1)
                    <div class="li-reasons" aria-label="Problems found">
                        @foreach($errorGroups as $reason => $count)
                            <span class="li-reason">{{ $reason }} <b>{{ $count }}</b></span>
                        @endforeach
                    </div>
                @endif

                <div class="li-table" role="table" aria-label="Rows to fix">
                    <div class="li-tr li-th" role="row">
                        <span role="columnheader">Row</span>
                        <span role="columnheader">Learner</span>
                        <span role="columnheader">Plan &amp; shift</span>
                        <span role="columnheader">What to fix</span>
                    </div>
                    @foreach($invalidRecords as $index => $record)
                        @php
                            $timing = trim(($record['shift_start_time'] ?? '') . (!empty($record['shift_end_time']) ? ' – ' . $record['shift_end_time'] : ''));
                        @endphp
                        <div class="li-tr" role="row">
                            <span class="li-td-row" role="cell">{{ $record['csv_row'] ?? $index + 1 }}</span>
                            <span class="li-td-main" role="cell">
                                <b>{{ $record['name'] ?? 'No name' }}</b>
                                <small>{{ collect([$record['mobile'] ?? null, !empty($record['seat_no']) ? 'Seat ' . $record['seat_no'] : 'General seat'])->filter()->implode(' · ') }}</small>
                            </span>
                            <span class="li-td-main" role="cell">
                                <b>{{ $record['plan_type'] ?? '—' }}</b>
                                <small>{{ collect([$record['plan'] ?? null, $timing ?: null, $record['start_date'] ?? null])->filter()->implode(' · ') }}</small>
                            </span>
                            <span class="li-td-reason" role="cell">{{ $reasonOf($record) }}</span>
                        </div>
                    @endforeach
                </div>

                <div class="li-result-foot">
                    <a href="{{ $exportUrl }}" class="li-link"><i class="fa-solid fa-file-arrow-down"></i> Download these rows</a>
                </div>
            @endif
        </section>
    @endif

    <div class="li-grid">
        {{-- Upload --}}
        <div class="li-col">
            <section class="li-card li-upload">
                <ol class="li-steps" aria-label="Import steps">
                    <li class="li-step is-done" data-step="1"><span>1</span> Prepare file</li>
                    <li class="li-step is-current" data-step="2"><span>2</span> Choose file</li>
                    <li class="li-step" data-step="3"><span>3</span> Import</li>
                </ol>

                <form action="{{ route('library.csv.upload') }}" method="POST" enctype="multipart/form-data" id="liForm" novalidate>
                    @csrf
                    <input type="file" name="csv_file" id="liFile" class="li-file-input" accept=".csv,text/csv" required>

                    <label for="liFile" class="li-drop" id="liDrop">
                        <span class="li-drop-icon"><i class="fa-solid fa-arrow-up-from-bracket"></i></span>
                        <span class="li-drop-title">Drop your CSV here or <u>browse</u></span>
                        <span class="li-drop-sub">.csv only · in Excel use Save As → CSV (Comma delimited)</span>
                    </label>

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
                            <p class="li-label">Preview</p>
                            <div class="li-preview-scroll"><table class="li-preview-table" id="liPreviewTable"></table></div>
                        </div>
                    </div>

                    <button type="submit" class="li-btn li-btn-primary li-submit" id="liSubmit" disabled>
                        <i class="fa-solid fa-file-import"></i> <span>Import learners</span>
                    </button>
                </form>
            </section>

            <section class="li-card">
                <p class="li-label">Columns</p>
                <div class="li-cols">
                    <div>
                        <span class="li-cols-head">Required</span>
                        <div class="li-chips">
                            @foreach($requiredColumns as $column)
                                <code class="li-chip is-req">{{ $column }}</code>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <span class="li-cols-head">Optional</span>
                        <div class="li-chips">
                            @foreach($optionalColumns as $column)
                                <code class="li-chip">{{ $column }}</code>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <span class="li-cols-head">Advanced shifts only</span>
                        <div class="li-chips">
                            @foreach($shiftColumns as $column)
                                <code class="li-chip is-adv">{{ $column }}</code>
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>
        </div>

        {{-- Setup: the exact values the file has to use --}}
        <aside class="li-col">
            <section class="li-card li-setup">
                <div class="li-setup-head">
                    <p class="li-label">Your setup</p>
                    <div class="li-tabs" role="tablist">
                        <button type="button" class="li-tab is-active" role="tab" aria-selected="true" data-tab="shifts">Shifts <span>{{ $shifts->count() }}</span></button>
                        <button type="button" class="li-tab" role="tab" aria-selected="false" data-tab="plans">Plans <span>{{ $plans->count() }}</span></button>
                    </div>
                </div>

                {{-- Shifts → plan_type --}}
                <div class="li-pane is-active" data-pane="shifts" role="tabpanel">
                    <p class="li-pane-hint">Type the name in <code>plan_type</code>. Tap <i class="fa-regular fa-copy"></i> to copy it.</p>
                    @forelse($shifts as $shift)
                        <div class="li-item">
                            <div class="li-item-top">
                                <div class="li-item-name">
                                    <span>{{ $shift->plan_type }}</span>
                                    <button type="button" class="li-copy" data-copy="{{ $shift->plan_type }}" aria-label="Copy {{ $shift->plan_type }}"><i class="fa-regular fa-copy"></i></button>
                                </div>
                                <span class="li-badge is-{{ $shift->mode }}">{{ $shift->mode_label }}</span>
                            </div>
                            <p class="li-item-meta">
                                <i class="fa-regular fa-clock"></i>
                                {{ $shift->time_label ?: '—' }}
                                @if((float) $shift->slot_hours > 0) <span class="li-dot"></span> {{ $shift->hours_label }} hrs @endif
                            </p>
                            @if($shift->import_hint)
                                <p class="li-item-hint"><i class="fa-solid fa-circle-info"></i> {{ $shift->import_hint }}</p>
                            @endif
                            @if($plans->count())
                                <div class="li-prices">
                                    @foreach($plans as $plan)
                                        @php $price = $shift->plan_prices[$plan->id] ?? null; @endphp
                                        <span class="li-price {{ $price === null ? 'is-none' : '' }}">
                                            <em>{{ $plan->name }}</em>
                                            @if($shift->mode === 'free')
                                                Own price
                                            @elseif($price === null)
                                                Not priced
                                            @else
                                                ₹{{ number_format($price) }}
                                            @endif
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @empty
                        <p class="li-empty">No shifts yet. Add shifts first.</p>
                    @endforelse
                </div>

                {{-- Plans → plan --}}
                <div class="li-pane" data-pane="plans" role="tabpanel" hidden>
                    <p class="li-pane-hint">Type the name in <code>plan</code>. Tap <i class="fa-regular fa-copy"></i> to copy it.</p>
                    @forelse($plans as $plan)
                        <div class="li-item li-item-row">
                            <div class="li-item-name">
                                <span>{{ $plan->name }}</span>
                                <button type="button" class="li-copy" data-copy="{{ $plan->name }}" aria-label="Copy {{ $plan->name }}"><i class="fa-regular fa-copy"></i></button>
                            </div>
                            <span class="li-item-meta">{{ $planDuration($plan) }}</span>
                        </div>
                    @empty
                        <p class="li-empty">No plans yet. Add plans first.</p>
                    @endforelse
                </div>
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
                    <p class="li-rules-head">Every file</p>
                    <ol class="li-rules">
                        <li><strong>Match your setup.</strong> Plan, shift and price must match what is set up in your library.</li>
                        <li><strong>One row per shift.</strong> A learner in both First Half and Second Half needs two rows. Hourly shifts work the same way.</li>
                        <li><strong>Paid amount means paid.</strong> Any amount in <code>paid_amount</code> is recorded as received.</li>
                        <li><strong>Extended learners.</strong> Enter the real start date; the extension is worked out from your library's extension policy.</li>
                        <li><strong>Expired learners.</strong> To keep old records, enter their real start date.</li>
                        <li><strong>Dates.</strong> Use the same date format as the sample file (DD-MM-YYYY).</li>
                        <li><strong>Mobile.</strong> 10 digits, numbers only.</li>
                        <li><strong>Seat clash.</strong> A row is not imported when its time overlaps a booking already on that seat for the same dates (extra shifts included). The other rows still import.</li>
                    </ol>
                    <p class="li-rules-head">Advanced shifts</p>
                    <ol class="li-rules">
                        <li><strong>Fixed hours.</strong> Needs a numbered <code>seat_no</code> and the start time in <code>shift_start_time</code> (e.g. <code>06:00</code> or <code>6 AM</code>). The end time comes from the shift hours and must fit inside the library hours.</li>
                        <li><strong>Direct booking.</strong> Needs a numbered <code>seat_no</code>, <code>shift_start_time</code>, <code>shift_end_time</code> and the price in <code>shift_price</code> (if empty, paid + pending is used).</li>
                        <li><strong>Flexible hours.</strong> Only on a general seat: leave <code>seat_no</code> empty.</li>
                        <li><strong>Normal shifts.</strong> Leave the three shift columns empty. Old files without these columns still work.</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="li-toast" id="liToast" role="status" aria-live="polite"></div>

@else
    <section class="li-card li-denied">
        <span class="li-denied-icon"><i class="fa-solid fa-lock"></i></span>
        <h5 class="li-title">You can't import learners</h5>
        <p class="li-sub">Ask the library owner to give you the Import Student permission.</p>
    </section>
@endcan
</div>

<script src="{{ asset('public/js/learner-import.js') }}?v={{ time() }}"></script>
@endsection
