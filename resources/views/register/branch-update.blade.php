@extends('layouts.library')

@section('title', isset($branch) ? 'Edit branch' : 'Add branch')

@push('lp-styles')
<link rel="stylesheet" href="{{ asset('public/css/lb-opform.css') }}?v={{ @filemtime(public_path('css/lb-opform.css')) }}">
<link rel="stylesheet" href="{{ asset('public/css/lb-pages.css') }}?v={{ @filemtime(public_path('css/lb-pages.css')) }}">
<link rel="stylesheet" href="{{ asset('public/css/lb-branch.css') }}?v={{ @filemtime(public_path('css/lb-branch.css')) }}">
@endpush

@section('content')

@php
    $isEdit = isset($branch);
    $images = $images ?? [];
    $selectedFeatures = array_map('intval', (array) old('features', $isEdit ? ($branch->features ?? []) : []));
    $branchTitle = $isEdit ? ($branch->display_name ?: $branch->name) : 'New branch';
    $initials = collect(explode(' ', trim((string) $branchTitle)))->filter()->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('') ?: 'B';
    $logo = $isEdit && $branch->library_logo ? asset('public/' . $branch->library_logo) : null;
    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    // "public" / "PRIVATE" saved earlier still selects the matching option
    $category = (string) old('library_category', $branch->library_category ?? '');
    $category = collect(['Public', 'Private'])->first(fn ($c) => strcasecmp($c, trim($category)) === 0) ?? $category;
    $hourOld = old('hour');
    $listed = $isEdit && (($branch->latitude && $branch->longitude) || $branch->google_map);

    // A field with its error state: class on the wrapper, message under it
    $bad = fn ($key) => $errors->has($key) ? ' is-bad' : '';
@endphp

<div class="lpg lbr">
    <header class="lpg-head">
        <div class="lpg-head-t">
            <div class="lpg-crumb">
                <a href="{{ route('library.home') }}">Dashboard</a><x-lb-icon name="chevron-right" />
                <a href="{{ route('branch.list') }}">Branches</a><x-lb-icon name="chevron-right" />
                <span>{{ $isEdit ? 'Edit' : 'Add' }}</span>
            </div>
            <h1>{{ $isEdit ? 'Edit branch' : 'Add a new branch' }}</h1>
            <p>{{ $isEdit ? 'Contact details, address and the listing learners see when they find your library online.' : 'Basic details, seats and plans for the new branch. You can complete the listing after it is created.' }}</p>
        </div>
    </header>

    @if($errors->any())
        <div class="lbr-alert" role="alert">
            <x-lb-icon name="circle-alert" />
            <div>
                <b>{{ $errors->count() === 1 ? '1 field needs attention' : $errors->count() . ' fields need attention' }}</b>
                <p>{{ $errors->first() }}</p>
            </div>
        </div>
    @endif

    <form action="{{ $isEdit ? route('branch.update', $branch->id) : route('branch.store') }}" method="POST" enctype="multipart/form-data" id="branchUpdate" novalidate>
        @csrf
        @if($isEdit) @method('PUT') @endif

        <div class="lbr-layout">
            @if($isEdit)
            {{-- identity + listing readiness --}}
            <aside class="lbr-side">
                <section class="lpg-card lbr-id">
                    <div class="lbr-logo-wrap">
                        <button type="button" class="lbr-logo" id="logoPick" aria-label="Change library logo">
                            <span class="lbr-logo-in">
                                <img id="logoImg" src="{{ $logo ?? '' }}" alt="" @unless($logo) hidden @endunless onerror="this.hidden=true;this.removeAttribute('src');document.getElementById('logoInitials').hidden=false;">
                                <b id="logoInitials" @if($logo) hidden @endif>{{ $initials }}</b>
                            </span>
                            <i><x-lb-icon name="camera" /></i>
                        </button>
                        <input type="file" class="d-none" name="library_logo" id="fileInput" accept="image/jpeg,image/png,image/webp">
                    </div>
                    <div class="lbr-id-t">
                        <h2 id="idTitle">{{ $branchTitle }}</h2>
                        <div class="lbr-tags">
                            <span class="lpg-tag"><x-lb-icon name="hash" />{{ $branch->name }}</span>
                            @if($listed)
                                <span class="lpg-tag is-ok"><x-lb-icon name="circle-check" />On listing</span>
                            @else
                                <span class="lpg-tag is-warn"><x-lb-icon name="map-pin" />Location missing</span>
                            @endif
                        </div>
                    </div>
                    <p class="lbr-logo-note" id="logoNote">Logo shows on receipts and your listing. JPG, PNG or WEBP. Large images are resized to 250 × 250 for you.</p>
                    @error('library_logo')<p class="lef-err">{{ $message }}</p>@enderror
                </section>

                <section class="lpg-card lbr-ready" id="readyCard">
                    <div class="lbr-ready-h">
                        <div>
                            <b>Listing profile</b>
                            <p id="readyText">Complete these so learners can find and trust you.</p>
                        </div>
                        <span class="lbr-ready-n" id="readyPct">0%</span>
                    </div>
                    <div class="lbr-bar"><i id="readyBar"></i></div>
                    <ul class="lbr-checks" id="readyList">
                        <li data-check="display"><a href="#sec-basics">Display name</a></li>
                        <li data-check="contact"><a href="#sec-basics">Email &amp; WhatsApp</a></li>
                        <li data-check="upi"><a href="#sec-basics">UPI ID for online booking</a></li>
                        <li data-check="about"><a href="#sec-profile">About your library</a></li>
                        <li data-check="address"><a href="#sec-address">Full address</a></li>
                        <li data-check="map"><a href="#sec-map">Map location</a></li>
                        <li data-check="amenities"><a href="#sec-amenities">Amenities</a></li>
                        <li data-check="photos"><a href="#sec-photos">Photos</a></li>
                        <li data-check="logo"><a href="#logoPick">Logo</a></li>
                    </ul>
                </section>
            </aside>
            @endif

            <div class="lbr-main">
                <nav class="lef-tabs lbr-tabs" aria-label="Sections">
                    <a href="#sec-basics" class="lef-tab is-on">Basics</a>
                    @if($isEdit)<a href="#sec-profile" class="lef-tab">Profile</a>@endif
                    <a href="#sec-address" class="lef-tab">Address</a>
                    @if($isEdit)
                        <a href="#sec-map" class="lef-tab">Map</a>
                        <a href="#sec-amenities" class="lef-tab">Amenities</a>
                        <a href="#sec-photos" class="lef-tab">Photos</a>
                    @else
                        <a href="#sec-seats" class="lef-tab">Seats</a>
                        <a href="#sec-plans" class="lef-tab">Plans</a>
                    @endif
                </nav>

                {{-- Basics --}}
                <section class="lef-sec" id="sec-basics">
                    <div class="lef-sec-h">
                        <span class="lef-sec-ic is-blue"><x-lb-icon name="building" /></span>
                        <div><h4>Basics</h4><p>Name and the contact learners reach you on.</p></div>
                    </div>
                    <div class="lef-grid">
                        <div class="lef-f{{ $bad('name') }}">
                            <label for="name">Branch name <i>*</i>@if($isEdit)<span>Locked</span>@endif</label>
                            <div class="lef-in">
                                <x-lb-icon name="{{ $isEdit ? 'lock' : 'building' }}" />
                                <input type="text" id="name" name="name" value="{{ old('name', $branch->name ?? '') }}" placeholder="e.g. Main Road" maxlength="255" @if($isEdit) readonly @endif>
                            </div>
                            @error('name')<p class="lef-err">{{ $message }}</p>@else<p class="lef-hint">For internal use only. It can't be changed later.</p>@enderror
                        </div>

                        @if($isEdit)
                        <div class="lef-f{{ $bad('display_name') }}">
                            <label for="display_name">Display name <i>*</i></label>
                            <div class="lef-in">
                                <x-lb-icon name="tag" />
                                <input type="text" id="display_name" name="display_name" value="{{ old('display_name', $branch->display_name ?? '') }}" placeholder="Name shown on your listing" maxlength="255">
                            </div>
                            @error('display_name')<p class="lef-err">{{ $message }}</p>@else<p class="lef-hint">Shown on your online listing, receipts and ID cards.</p>@enderror
                        </div>
                        @endif

                        <div class="lef-f{{ $bad('email') }}">
                            <label for="email">Email <i>*</i></label>
                            <div class="lef-in">
                                <x-lb-icon name="mail" />
                                <input type="email" id="email" name="email" value="{{ old('email', $branch->email ?? '') }}" placeholder="branch@example.com" autocomplete="email" inputmode="email">
                            </div>
                            @error('email')<p class="lef-err">{{ $message }}</p>@enderror
                        </div>

                        <div class="lef-f{{ $bad('mobile') }}">
                            <label for="mobile">WhatsApp number <i>*</i></label>
                            <div class="lef-in">
                                <span class="lef-pre">+91</span>
                                <input type="text" id="mobile" name="mobile" class="digit-only" maxlength="10" inputmode="numeric" value="{{ old('mobile', $branch->mobile ?? '') }}" placeholder="10 digit number" autocomplete="tel-national">
                            </div>
                            @error('mobile')<p class="lef-err">{{ $message }}</p>@enderror
                        </div>

                        <div class="lef-f{{ $bad('founder_day') }}">
                            <label for="founder_day">Founded on @unless($isEdit)<i>*</i>@endunless</label>
                            <div class="lef-in">
                                <x-lb-icon name="cake" />
                                <input type="date" id="founder_day" name="founder_day" value="{{ old('founder_day', $branch->founder_day ?? '') }}" max="{{ now()->format('Y-m-d') }}">
                            </div>
                            @error('founder_day')<p class="lef-err">{{ $message }}</p>@else<p class="lef-hint">We wish your learners on the anniversary.</p>@enderror
                        </div>

                        <div class="lef-f{{ $bad('upi_id') }}">
                            <label for="upi_id">UPI ID <span>For online booking</span></label>
                            <div class="lef-in">
                                <x-lb-icon name="wallet" />
                                <input type="text" id="upi_id" name="upi_id" value="{{ old('upi_id', $branch->upi_id ?? '') }}" placeholder="name@bank" autocapitalize="off" spellcheck="false">
                            </div>
                            @error('upi_id')<p class="lef-err">{{ $message }}</p>@else<p class="lef-hint" id="upiHint">Learners pay here when they book a seat from your listing or QR code.</p>@enderror
                        </div>
                    </div>
                </section>

                @if($isEdit)
                {{-- Profile --}}
                <section class="lef-sec" id="sec-profile">
                    <div class="lef-sec-h">
                        <span class="lef-sec-ic is-violet"><x-lb-icon name="library" /></span>
                        <div><h4>Profile</h4><p>What learners read about you on the listing.</p></div>
                    </div>

                    <div class="lef-f{{ $bad('working_days') }}">
                        <label>Open on</label>
                        <div class="lbr-days" id="dayChips" role="group" aria-label="Working days">
                            @foreach($days as $d)
                                <button type="button" class="lbr-day" data-day="{{ $d }}" aria-pressed="false">{{ substr($d, 0, 3) }}</button>
                            @endforeach
                            <button type="button" class="lbr-day lbr-day--all" id="dayAll">Every day</button>
                        </div>
                        <div class="lef-in lbr-days-text">
                            <x-lb-icon name="calendar-days" />
                            <input type="text" id="working_days" name="working_days" value="{{ old('working_days', $branch->working_days ?? 'Monday, Tuesday, Wednesday, Thursday, Friday, Saturday, Sunday') }}" placeholder="e.g. Monday to Saturday, 6 AM to 10 PM" maxlength="255">
                        </div>
                        @error('working_days')<p class="lef-err">{{ $message }}</p>@else<p class="lef-hint">Tap days, or write it your way (you can add timings too).</p>@enderror
                    </div>

                    <div class="lef-f{{ $bad('description') }}">
                        <label for="description">About your library <span id="descCount"></span></label>
                        <div class="lef-in is-area">
                            <textarea id="description" name="description" rows="5" maxlength="2000" placeholder="When you started, the atmosphere, facilities and what makes your library a good place to study.">{{ old('description', $branch->description ?? '') }}</textarea>
                        </div>
                        @error('description')<p class="lef-err">{{ $message }}</p>@enderror
                    </div>

                    <div class="lef-grid">
                        <div class="lef-f{{ $bad('library_category') }}">
                            <label>Category</label>
                            <div class="lbr-seg" role="radiogroup" aria-label="Library category">
                                @if($category && !in_array($category, ['Public', 'Private']))
                                    <label><input type="radio" name="library_category" value="{{ $category }}" checked><span>{{ $category }}</span></label>
                                @endif
                                @foreach(['Public' => 'users', 'Private' => 'lock'] as $c => $ic)
                                    <label><input type="radio" name="library_category" value="{{ $c }}" @checked($category === $c)><span><x-lb-icon name="{{ $ic }}" />{{ $c }}</span></label>
                                @endforeach
                            </div>
                            @error('library_category')<p class="lef-err">{{ $message }}</p>@enderror
                        </div>

                        <div class="lef-f{{ $bad('fixed_billing_date') }}">
                            <label for="fixed_billing_date">Fixed billing date <span>Optional</span></label>
                            <div class="lef-in">
                                <x-lb-icon name="calendar-check" />
                                <input type="text" id="fixed_billing_date" name="fixed_billing_date" class="digit-only" maxlength="2" inputmode="numeric" value="{{ old('fixed_billing_date', $branch->fixed_billing_date ?? '') }}" placeholder="Day of month, 1 to 31">
                            </div>
                            @error('fixed_billing_date')<p class="lef-err">{{ $message }}</p>@else<p class="lef-hint">Leave empty to bill from each learner's own start date.</p>@enderror
                        </div>
                    </div>
                </section>
                @endif

                {{-- Address --}}
                <section class="lef-sec" id="sec-address">
                    <div class="lef-sec-h">
                        <span class="lef-sec-ic is-green"><x-lb-icon name="map-pin" /></span>
                        <div><h4>Address</h4><p>Printed on receipts and ID cards, shown on your listing.</p></div>
                    </div>

                    <div class="lef-f{{ $bad('library_address') }}">
                        <label for="library_address">Full address with landmark <i>*</i></label>
                        <div class="lef-in is-area">
                            <textarea id="library_address" name="library_address" rows="3" placeholder="Building, street, area and a landmark near you">{{ old('library_address', $branch->library_address ?? '') }}</textarea>
                        </div>
                        @error('library_address')<p class="lef-err">{{ $message }}</p>@enderror
                    </div>

                    <div class="lef-grid lbr-grid-3">
                        <div class="lef-f lbr-choice{{ $bad('state_id') }}">
                            <label for="state_id">State @if($isEdit)<i>*</i>@endif</label>
                            <select name="state_id" id="state_id">
                                <option value="">Select state</option>
                                @foreach($states as $value)
                                    <option value="{{ $value->id }}" @selected(old('state_id', $branch->state_id ?? '') == $value->id)>{{ $value->state_name }}</option>
                                @endforeach
                            </select>
                            @error('state_id')<p class="lef-err">{{ $message }}</p>@enderror
                        </div>

                        <div class="lef-f lbr-choice{{ $bad('city_id') }}">
                            <label for="city_id">City @if($isEdit)<i>*</i>@endif</label>
                            <select name="city_id" id="city_id">
                                <option value="">Select city</option>
                            </select>
                            @error('city_id')<p class="lef-err">{{ $message }}</p>@enderror
                        </div>

                        <div class="lef-f{{ $bad('library_zip') }}">
                            <label for="library_zip">PIN code @if($isEdit)<i>*</i>@endif</label>
                            <div class="lef-in">
                                <x-lb-icon name="hash" />
                                <input type="text" id="library_zip" name="library_zip" class="digit-only" maxlength="6" inputmode="numeric" value="{{ old('library_zip', $branch->library_zip ?? '') }}" placeholder="6 digits">
                            </div>
                            @error('library_zip')<p class="lef-err">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </section>

                @unless($isEdit)
                {{-- Seats & rules (new branch) --}}
                <section class="lef-sec" id="sec-seats">
                    <div class="lef-sec-h">
                        <span class="lef-sec-ic is-amber"><x-lb-icon name="armchair" /></span>
                        <div><h4>Seats &amp; rules</h4><p>How big the branch is and how long it stays open.</p></div>
                    </div>
                    <div class="lef-grid">
                        <div class="lef-f{{ $bad('seats') }}">
                            <label for="seats">Number of seats <i>*</i></label>
                            <div class="lef-in">
                                <x-lb-icon name="armchair" />
                                <input type="text" id="seats" name="seats" class="digit-only" inputmode="numeric" maxlength="4" value="{{ old('seats') }}" placeholder="e.g. 60">
                            </div>
                            @error('seats')<p class="lef-err">{{ $message }}</p>@enderror
                        </div>

                        <div class="lef-f{{ $bad('hour') }}">
                            <label for="hour">Open hours a day <i>*</i></label>
                            <div class="lef-in is-select">
                                <x-lb-icon name="clock" />
                                <select name="hour" id="hour">
                                    <option value="">Select hours</option>
                                    @for($h = 10; $h <= 23; $h++)
                                        <option value="{{ $h }}" @selected($hourOld == $h)>{{ $h }} hours</option>
                                    @endfor
                                    @can('has-permission', 'All Day')
                                        <option value="24" @selected($hourOld == 24)>24 hours</option>
                                    @endcan
                                </select>
                            </div>
                            @error('hour')<p class="lef-err">{{ $message }}</p>@enderror
                        </div>

                        <div class="lef-f{{ $bad('locker_amount') }}">
                            <label for="locker_amount">Locker fee <i>*</i></label>
                            <div class="lef-in">
                                <span class="lef-pre">₹</span>
                                <input type="text" id="locker_amount" name="locker_amount" class="digit-only" inputmode="numeric" value="{{ old('locker_amount') }}" placeholder="0">
                            </div>
                            @error('locker_amount')<p class="lef-err">{{ $message }}</p>@else<p class="lef-hint">Enter 0 if you don't offer lockers.</p>@enderror
                        </div>

                        <div class="lef-f{{ $bad('extend_days') }}">
                            <label for="extend_days">Grace days after expiry <i>*</i></label>
                            <div class="lef-in">
                                <x-lb-icon name="hourglass" />
                                <input type="text" id="extend_days" name="extend_days" class="digit-only" inputmode="numeric" maxlength="2" value="{{ old('extend_days') }}" placeholder="e.g. 3">
                            </div>
                            @error('extend_days')<p class="lef-err">{{ $message }}</p>@else<p class="lef-hint">Days a learner can keep the seat after the plan ends.</p>@enderror
                        </div>

                        <div class="lef-f{{ $bad('token_money') }}">
                            <label for="token_money">Token money <span>Optional</span></label>
                            <div class="lef-in">
                                <span class="lef-pre">₹</span>
                                <input type="text" id="token_money" name="token_money" class="digit-only" inputmode="numeric" value="{{ old('token_money') }}" placeholder="0">
                            </div>
                            @error('token_money')<p class="lef-err">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </section>

                {{-- Plans & floors (new branch) --}}
                <section class="lef-sec" id="sec-plans">
                    <div class="lef-sec-h">
                        <span class="lef-sec-ic is-blue"><x-lb-icon name="calendar-range" /></span>
                        <div><h4>Plans &amp; floors</h4><p>Plan lengths you sell. Floors only if the branch has more than one.</p></div>
                    </div>
                    @php($oldPlans = (array) old('plans', []))
                    <div class="lef-grid">
                        <div class="lef-f lbr-choice{{ $bad('plans') }}">
                            <label for="duration">Plans <i>*</i></label>
                            <select id="duration" name="plans[]" multiple>
                                <optgroup label="Months">
                                    @for($i = 1; $i <= 12; $i++)<option value="{{ $i }} MONTH" @selected(in_array("$i MONTH", $oldPlans))>{{ $i }} MONTH</option>@endfor
                                </optgroup>
                                <optgroup label="Weeks">
                                    @for($i = 1; $i <= 4; $i++)<option value="{{ $i }} WEEK" @selected(in_array("$i WEEK", $oldPlans))>{{ $i }} WEEK</option>@endfor
                                </optgroup>
                                <optgroup label="Days">
                                    @for($i = 1; $i <= 31; $i++)<option value="{{ $i }} DAY" @selected(in_array("$i DAY", $oldPlans))>{{ $i }} DAY</option>@endfor
                                </optgroup>
                            </select>
                            @error('plans')<p class="lef-err">{{ $message }}</p>@else<p class="lef-hint">Pick at least one monthly plan.</p>@enderror
                        </div>

                        <div class="lef-f{{ $bad('monthdays') }}">
                            <label for="monthdays">One month counts as</label>
                            <div class="lef-in is-select">
                                <x-lb-icon name="calendar-days" />
                                <select name="monthdays" id="monthdays">
                                    <option value="">Calendar month</option>
                                    <option value="30" @selected(old('monthdays') == 30)>30 days</option>
                                    <option value="28" @selected(old('monthdays') == 28)>28 days</option>
                                </select>
                            </div>
                            @error('monthdays')<p class="lef-err">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="lbr-floors-h">
                        <b>Floors</b><span>Optional</span>
                        <button type="button" class="lef-btn-soft lbr-add" id="addFloor"><x-lb-icon name="plus" />Add floor</button>
                    </div>
                    <div id="floorWrapper">
                        @foreach(old('floors', [['name' => '', 'from' => '', 'to' => '']]) as $i => $floor)
                            <div class="floor-block lbr-floor">
                                <div class="lef-f{{ $bad("floors.$i.name") }}">
                                    <label>Floor name</label>
                                    <div class="lef-in"><input type="text" name="floors[{{ $i }}][name]" value="{{ $floor['name'] ?? '' }}" placeholder="e.g. Ground"></div>
                                    @error("floors.$i.name")<p class="lef-err">{{ $message }}</p>@enderror
                                </div>
                                <div class="lef-f{{ $bad("floors.$i.from") }}">
                                    <label>Seat from</label>
                                    <div class="lef-in"><input type="number" min="1" inputmode="numeric" name="floors[{{ $i }}][from]" value="{{ $floor['from'] ?? '' }}" placeholder="1"></div>
                                    @error("floors.$i.from")<p class="lef-err">{{ $message }}</p>@enderror
                                </div>
                                <div class="lef-f{{ $bad("floors.$i.to") }}">
                                    <label>Seat to</label>
                                    <div class="lef-in"><input type="number" min="1" inputmode="numeric" name="floors[{{ $i }}][to]" value="{{ $floor['to'] ?? '' }}" placeholder="50"></div>
                                    @error("floors.$i.to")<p class="lef-err">{{ $message }}</p>@enderror
                                </div>
                                <button type="button" class="lbr-x remove-floor" aria-label="Remove floor"><x-lb-icon name="trash" /></button>
                            </div>
                        @endforeach
                    </div>
                </section>
                @endunless

                @if($isEdit)
                {{-- Map --}}
                <section class="lef-sec" id="sec-map">
                    <div class="lef-sec-h">
                        <span class="lef-sec-ic is-amber"><x-lb-icon name="navigation" /></span>
                        <div><h4>Location on map</h4><p>Needed to show your library on the listing. Use either option.</p></div>
                    </div>

                    <div class="lbr-loc">
                        <div class="lbr-loc-opt">
                            <span class="lbr-loc-k">Option 1</span>
                            <div class="lef-f{{ $bad('google_map') }}">
                                <label for="google_map">Google Maps embed code</label>
                                <div class="lef-in is-area">
                                    <textarea id="google_map" name="google_map" rows="3" spellcheck="false" placeholder='Google Maps → Share → Embed a map → Copy HTML, then paste here'>{{ old('google_map', $branch->google_map ?? '') }}</textarea>
                                </div>
                                @error('google_map')<p class="lef-err">{{ $message }}</p>@else<p class="lef-hint" id="mapHint"></p>@enderror
                            </div>
                            <div class="lbr-map" id="mapPreview" hidden><iframe title="Map preview" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe></div>
                        </div>

                        <div class="lbr-or"><span>or</span></div>

                        <div class="lbr-loc-opt">
                            <span class="lbr-loc-k">Option 2</span>
                            <div class="lef-grid">
                                <div class="lef-f{{ $bad('latitude') }}">
                                    <label for="latitude">Latitude</label>
                                    <div class="lef-in"><x-lb-icon name="locate-fixed" /><input type="text" id="latitude" name="latitude" class="lbr-coord" inputmode="decimal" value="{{ old('latitude', $branch->latitude ?? '') }}" placeholder="e.g. 26.9124"></div>
                                    @error('latitude')<p class="lef-err">{{ $message }}</p>@enderror
                                </div>
                                <div class="lef-f{{ $bad('longitude') }}">
                                    <label for="longitude">Longitude</label>
                                    <div class="lef-in"><x-lb-icon name="locate-fixed" /><input type="text" id="longitude" name="longitude" class="lbr-coord" inputmode="decimal" value="{{ old('longitude', $branch->longitude ?? '') }}" placeholder="e.g. 75.7873"></div>
                                    @error('longitude')<p class="lef-err">{{ $message }}</p>@enderror
                                </div>
                            </div>
                            <div class="lbr-loc-acts">
                                <button type="button" class="lef-btn-soft" id="useLocation"><x-lb-icon name="locate-fixed" />Use my current location</button>
                                <a href="#" target="_blank" rel="noopener" class="lef-btn-soft" id="checkLocation" hidden><x-lb-icon name="external-link" />Check on Google Maps</a>
                            </div>
                            <p class="lef-hint" id="locHint">Stand inside your library and tap "Use my current location".</p>
                        </div>
                    </div>
                </section>

                {{-- Amenities --}}
                <section class="lef-sec" id="sec-amenities">
                    <div class="lef-sec-h">
                        <span class="lef-sec-ic is-blue"><x-lb-icon name="sparkles" /></span>
                        <div><h4>Amenities</h4><p>Tap everything your library offers.</p></div>
                        <span class="lef-sec-badge" id="amenityCount">0 selected</span>
                    </div>
                    <div class="lbr-amen">
                        @foreach($features as $feature)
                            <label class="lbr-amen-i">
                                <input type="checkbox" name="features[]" value="{{ $feature->id }}" @checked(in_array((int) $feature->id, $selectedFeatures, true))>
                                <span class="lbr-amen-b">
                                    <img src="{{ asset('public/' . $feature->image) }}" alt="" loading="lazy">
                                    <span>{{ $feature->name }}</span>
                                    <i><x-lb-icon name="check" /></i>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </section>

                {{-- Photos --}}
                <section class="lef-sec" id="sec-photos">
                    <div class="lef-sec-h">
                        <span class="lef-sec-ic is-violet"><x-lb-icon name="images" /></span>
                        <div><h4>Photos</h4><p>Up to 4 photos of your reading hall. JPG, PNG or WEBP, 2 MB each.</p></div>
                        <span class="lef-sec-badge" id="photoCount">0 / 4</span>
                    </div>
                    <div class="lbr-photos" id="photoGrid">
                        @foreach($images as $img)
                            <figure class="lbr-ph" data-existing="{{ $img }}">
                                <img src="{{ asset('public/' . $img) }}" alt="Library photo" loading="lazy">
                                <button type="button" class="lbr-ph-x" aria-label="Remove photo"><x-lb-icon name="x" /></button>
                            </figure>
                        @endforeach
                        <label class="lbr-ph-add" id="photoAdd" tabindex="0">
                            <x-lb-icon name="upload" />
                            <b>Add photos</b>
                            <span>or drop them here</span>
                            <input type="file" class="d-none" name="library_images[]" id="libraryImages" multiple accept="image/jpeg,image/png,image/webp">
                        </label>
                    </div>
                    <p class="lef-err" id="fileUploadError" @unless($errors->has('library_images') || $errors->has('library_images.*')) hidden @endunless>{{ $errors->first('library_images') ?: $errors->first('library_images.*') }}</p>
                    <div id="deletedImages"></div>
                </section>
                @endif
            </div>
        </div>

        <div class="lpg-save lbr-save">
            <p id="saveNote">{{ $isEdit ? 'Changes show on your listing as soon as you save.' : 'You can add the map, amenities and photos after creating the branch.' }}</p>
            <a href="{{ route('branch.list') }}" class="lp-btn lp-btn--ghost">Cancel</a>
            <button type="submit" class="lp-btn"><x-lb-icon name="circle-check" />{{ $isEdit ? 'Save changes' : 'Create branch' }}</button>
        </div>
    </form>
</div>

<script>
$(function () {
    var form = document.getElementById('branchUpdate');

    /* ---------- State -> city (layout sets up Choices on both selects) ---------- */
    function loadCities(stateId, selectedCity) {
        if (!stateId || typeof cityChoices === 'undefined' || !cityChoices) return;
        $.getJSON("{{ route('cityGetStateWise') }}", { state_id: stateId }, function (cities) {
            var list = [];
            $.each(cities, function (id, name) { list.push({ value: id, label: name, selected: String(selectedCity) === String(id) }); });
            cityChoices.clearStore();
            cityChoices.setChoices([{ value: '', label: 'Select city', placeholder: true }].concat(list), 'value', 'label', true);
            ready();
        });
    }
    $('#state_id').on('change', function () { loadCities(this.value, ''); });
    var initialState = $('#state_id').val();
    // The layout creates cityChoices in a DOMContentLoaded handler registered after this one.
    if (initialState) setTimeout(function () { loadCities(initialState, "{{ old('city_id', $branch->city_id ?? '') }}"); }, 0);

    /* ---------- Section tabs follow the scroll ---------- */
    var tabs = document.querySelectorAll('.lbr-tabs .lef-tab');
    if ('IntersectionObserver' in window && tabs.length) {
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (en) {
                if (!en.isIntersecting) return;
                tabs.forEach(function (t) {
                    var on = t.getAttribute('href') === '#' + en.target.id;
                    t.classList.toggle('is-on', on);
                    if (on && t.scrollIntoView) t.parentNode.scrollTo({ left: t.offsetLeft - 8, behavior: 'smooth' });
                });
            });
        }, { rootMargin: '-35% 0px -60% 0px' });
        document.querySelectorAll('.lbr-main > .lef-sec').forEach(function (s) { io.observe(s); });
    }

    /* ---------- Clear an error as soon as the field is touched ---------- */
    form.addEventListener('input', function (e) {
        var f = e.target.closest('.lef-f');
        if (f && f.classList.contains('is-bad')) {
            f.classList.remove('is-bad');
            var err = f.querySelector('.lef-err'); if (err) err.remove();
        }
        ready();
    });
    form.addEventListener('change', ready);

    form.addEventListener('submit', function () {
        form.querySelector('button[type="submit"]').classList.add('is-busy');
    });

    @if($isEdit)
    /* ---------- Working days: chips write the text, text lights the chips ---------- */
    var dayInput = document.getElementById('working_days');
    var dayBtns = Array.prototype.slice.call(document.querySelectorAll('.lbr-day[data-day]'));
    function paintDays() {
        var t = dayInput.value.toLowerCase(), n = 0;
        dayBtns.forEach(function (b) {
            var on = t.indexOf(b.dataset.day.toLowerCase()) !== -1;
            n += on;
            b.classList.toggle('is-on', on);
            b.setAttribute('aria-pressed', on);
        });
        document.getElementById('dayAll').classList.toggle('is-on', n === 7);
    }
    function writeDays(list) {
        dayInput.value = list.join(', ');
        paintDays();
        ready();
    }
    dayBtns.forEach(function (b) {
        b.addEventListener('click', function () {
            var on = dayBtns.filter(function (x) { return x === b ? !x.classList.contains('is-on') : x.classList.contains('is-on'); });
            writeDays(on.map(function (x) { return x.dataset.day; }));
        });
    });
    document.getElementById('dayAll').addEventListener('click', function () {
        writeDays(dayBtns.map(function (x) { return x.dataset.day; }));
    });
    dayInput.addEventListener('input', paintDays);
    paintDays();

    /* ---------- About: character count ---------- */
    var desc = document.getElementById('description'), descCount = document.getElementById('descCount');
    function countDesc() { descCount.textContent = desc.value.length ? desc.value.length + ' / 2000' : ''; }
    desc.addEventListener('input', countDesc); countDesc();

    /* ---------- Display name updates the card title ---------- */
    var dn = document.getElementById('display_name');
    dn.addEventListener('input', function () { document.getElementById('idTitle').textContent = dn.value.trim() || @json($branch->name); });

    /* ---------- Map: live preview of the embed, or a link for coordinates ---------- */
    var mapBox = document.getElementById('google_map'), mapPrev = document.getElementById('mapPreview'), mapHint = document.getElementById('mapHint');
    function embedSrc(v) {
        var m = String(v || '').match(/src\s*=\s*["']([^"']+)["']/i);
        var src = m ? m[1] : String(v || '').trim();
        return /^https:\/\/(www\.)?google\.[a-z.]+\/maps\/embed/i.test(src) ? src : '';
    }
    function paintMap() {
        var src = embedSrc(mapBox.value);
        mapPrev.hidden = !src;
        var frame = mapPrev.querySelector('iframe');
        if (src && frame.getAttribute('src') !== src) frame.setAttribute('src', src);
        if (mapHint) {
            mapHint.className = 'lef-hint' + (mapBox.value.trim() && !src ? ' is-warn' : '');
            mapHint.textContent = mapBox.value.trim() && !src
                ? 'This doesn\'t look like a Google Maps embed. Use Share → Embed a map → Copy HTML.'
                : (src ? 'Looks good. This is how the map will appear.' : '');
        }
    }
    mapBox.addEventListener('input', paintMap); paintMap();

    var lat = document.getElementById('latitude'), lng = document.getElementById('longitude');
    var check = document.getElementById('checkLocation'), locHint = document.getElementById('locHint');
    function paintCoords() {
        var ok = lat.value && lng.value && !isNaN(+lat.value) && !isNaN(+lng.value);
        check.hidden = !ok;
        if (ok) check.href = 'https://www.google.com/maps?q=' + encodeURIComponent(lat.value + ',' + lng.value);
    }
    document.querySelectorAll('.lbr-coord').forEach(function (inp) {
        inp.addEventListener('input', function () {
            var v = inp.value.replace(/[^0-9.\-]/g, '').replace(/(?!^)-/g, '');
            var dot = v.indexOf('.');
            if (dot !== -1) v = v.slice(0, dot + 1) + v.slice(dot + 1).replace(/\./g, '');
            if (v !== inp.value) inp.value = v;
            paintCoords();
        });
    });
    paintCoords();
    document.getElementById('useLocation').addEventListener('click', function () {
        var btn = this;
        if (!navigator.geolocation) { locHint.textContent = 'Your browser can\'t share location. Type the coordinates instead.'; return; }
        btn.classList.add('is-busy'); locHint.textContent = 'Finding your location…';
        navigator.geolocation.getCurrentPosition(function (pos) {
            btn.classList.remove('is-busy');
            lat.value = pos.coords.latitude.toFixed(6);
            lng.value = pos.coords.longitude.toFixed(6);
            [lat, lng].forEach(function (i) { i.dispatchEvent(new Event('input', { bubbles: true })); });
            locHint.textContent = 'Location added (accurate to about ' + Math.round(pos.coords.accuracy) + ' m). Check it on Google Maps.';
        }, function () {
            btn.classList.remove('is-busy');
            locHint.textContent = 'Couldn\'t get your location. Allow location access, or type the coordinates.';
        }, { enableHighAccuracy: true, timeout: 15000 });
    });

    /* ---------- Amenities count ---------- */
    function countAmen() {
        var n = form.querySelectorAll('input[name="features[]"]:checked').length;
        document.getElementById('amenityCount').textContent = n + ' selected';
    }
    form.addEventListener('change', function (e) { if (e.target.name === 'features[]') countAmen(); });
    countAmen();

    /* ---------- Logo: pick, check, resize to fit 250 x 250 ---------- */
    var logoInput = document.getElementById('fileInput'), logoImg = document.getElementById('logoImg'), logoNote = document.getElementById('logoNote');
    var logoDefault = logoNote.textContent;
    document.getElementById('logoPick').addEventListener('click', function () { logoInput.click(); });
    logoInput.addEventListener('change', function () {
        var file = logoInput.files[0];
        logoNote.classList.remove('is-bad');
        logoNote.textContent = logoDefault;
        if (!file) return;
        if (['image/jpeg', 'image/png', 'image/webp'].indexOf(file.type) === -1) {
            logoInput.value = '';
            logoNote.classList.add('is-bad');
            logoNote.textContent = 'Use a JPG, PNG or WEBP image.';
            return;
        }
        var url = URL.createObjectURL(file), img = new Image();
        img.onload = function () {
            var fits = img.width <= 250 && img.height <= 250 && file.size <= 200 * 1024;
            var show = function (f, note) {
                logoImg.src = URL.createObjectURL(f); logoImg.hidden = false;
                document.getElementById('logoInitials').hidden = true;
                logoNote.textContent = note;
                ready();
            };
            if (fits) { show(file, 'New logo selected. Save to apply.'); return; }
            var k = Math.min(1, 250 / Math.max(img.width, img.height));
            var c = document.createElement('canvas');
            c.width = Math.round(img.width * k); c.height = Math.round(img.height * k);
            c.getContext('2d').drawImage(img, 0, 0, c.width, c.height);
            var type = file.type === 'image/jpeg' ? 'image/jpeg' : (file.type === 'image/png' ? 'image/png' : 'image/webp');
            c.toBlob(function (blob) {
                if (!blob || blob.size > 200 * 1024 || typeof DataTransfer === 'undefined') {
                    logoInput.value = '';
                    logoNote.classList.add('is-bad');
                    logoNote.textContent = 'This image is too large. Use one under 200 KB and 250 × 250 px.';
                    return;
                }
                var ext = type.split('/')[1].replace('jpeg', 'jpg');
                var out = new File([blob], 'logo.' + ext, { type: type });
                var dt = new DataTransfer(); dt.items.add(out); logoInput.files = dt.files;
                show(out, 'Resized to ' + c.width + ' × ' + c.height + ' to fit. Save to apply.');
            }, type, .9);
            URL.revokeObjectURL(url);
        };
        img.src = url;
    });

    /* ---------- Photos: kept + new, max 4, removable before saving ---------- */
    var MAX = 4, grid = document.getElementById('photoGrid'), add = document.getElementById('photoAdd');
    var picker = document.getElementById('libraryImages'), photoErr = document.getElementById('fileUploadError');
    var fresh = [];   // File objects chosen on this visit
    function photoMsg(t) { photoErr.textContent = t || ''; photoErr.hidden = !t; }
    function syncPicker() {
        if (typeof DataTransfer === 'undefined') return;
        var dt = new DataTransfer();
        fresh.forEach(function (f) { dt.items.add(f); });
        picker.files = dt.files;
    }
    function paintPhotos() {
        grid.querySelectorAll('.lbr-ph[data-new]').forEach(function (n) { n.remove(); });
        fresh.forEach(function (f, i) {
            var fig = document.createElement('figure');
            fig.className = 'lbr-ph is-new'; fig.setAttribute('data-new', i);
            fig.innerHTML = '<img alt="New photo"><span class="lbr-ph-tag">New</span><button type="button" class="lbr-ph-x" aria-label="Remove photo">×</button>';
            fig.querySelector('img').src = URL.createObjectURL(f);
            grid.insertBefore(fig, add);
        });
        var total = grid.querySelectorAll('.lbr-ph').length;
        document.getElementById('photoCount').textContent = total + ' / ' + MAX;
        add.hidden = total >= MAX;
        ready();
    }
    function addFiles(list) {
        var room = MAX - grid.querySelectorAll('.lbr-ph').length, skipped = '';
        Array.prototype.forEach.call(list, function (f) {
            if (['image/jpeg', 'image/png', 'image/webp'].indexOf(f.type) === -1) { skipped = '"' + f.name + '" isn\'t a JPG, PNG or WEBP image.'; return; }
            if (f.size > 2 * 1024 * 1024) { skipped = '"' + f.name + '" is over 2 MB.'; return; }
            if (room <= 0) { skipped = 'Only ' + MAX + ' photos are allowed. Remove one to add another.'; return; }
            fresh.push(f); room--;
        });
        photoMsg(skipped);
        syncPicker(); paintPhotos();
    }
    picker.addEventListener('change', function () {
        var chosen = Array.prototype.slice.call(picker.files);
        // Browsers without DataTransfer keep the native selection as is
        if (typeof DataTransfer === 'undefined') { fresh = chosen.slice(0, MAX); paintPhotos(); return; }
        addFiles(chosen);
    });
    add.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); picker.click(); } });
    ['dragenter', 'dragover'].forEach(function (ev) { add.addEventListener(ev, function (e) { e.preventDefault(); add.classList.add('is-drag'); }); });
    ['dragleave', 'drop'].forEach(function (ev) { add.addEventListener(ev, function (e) { e.preventDefault(); add.classList.remove('is-drag'); }); });
    add.addEventListener('drop', function (e) { if (e.dataTransfer && e.dataTransfer.files.length) addFiles(e.dataTransfer.files); });
    grid.addEventListener('click', function (e) {
        var x = e.target.closest('.lbr-ph-x'); if (!x) return;
        var fig = x.closest('.lbr-ph');
        if (fig.hasAttribute('data-existing')) {
            var h = document.createElement('input');
            h.type = 'hidden'; h.name = 'deleted_images[]'; h.value = fig.getAttribute('data-existing');
            document.getElementById('deletedImages').appendChild(h);
            fig.remove();
        } else {
            fresh.splice(+fig.getAttribute('data-new'), 1);
            syncPicker();
        }
        photoMsg(''); paintPhotos();
    });
    paintPhotos();
    @endif

    /* ---------- Listing readiness ---------- */
    // Looked up inside this form: the layout's popups reuse ids like "email" and "mobile"
    function val(id) { var el = form.querySelector('#' + id); return el ? String(el.value || '').trim() : ''; }
    function ready() {
        var list = document.getElementById('readyList');
        if (!list) return;
        var checks = {
            display: !!val('display_name'),
            contact: /\S+@\S+\.\S+/.test(val('email')) && val('mobile').length === 10,
            upi: /^[\w.\-]{2,}@[a-z]{2,}$/i.test(val('upi_id')),
            about: val('description').length >= 30,
            address: !!(val('library_address') && val('state_id') && val('city_id') && val('library_zip').length === 6),
            map: !!(document.getElementById('mapPreview') && !document.getElementById('mapPreview').hidden) || !!(val('latitude') && val('longitude')),
            amenities: form.querySelectorAll('input[name="features[]"]:checked').length > 0,
            photos: document.querySelectorAll('#photoGrid .lbr-ph').length > 0,
            logo: !!(document.getElementById('logoImg') && !document.getElementById('logoImg').hidden)
        };
        var keys = Object.keys(checks), done = 0;
        keys.forEach(function (k) {
            var li = list.querySelector('[data-check="' + k + '"]');
            li.classList.toggle('is-done', checks[k]);
            done += checks[k] ? 1 : 0;
        });
        var pct = Math.round(done / keys.length * 100);
        document.getElementById('readyPct').textContent = pct + '%';
        document.getElementById('readyBar').style.width = pct + '%';
        document.getElementById('readyCard').classList.toggle('is-full', pct === 100);
        document.getElementById('readyText').textContent = pct === 100
            ? 'Your listing is complete. Learners see everything they need.'
            : (keys.length - done) + ' thing' + (keys.length - done === 1 ? '' : 's') + ' left so learners can find and trust you.';
    }
    ready();

    @unless($isEdit)
    /* ---------- Floors (new branch) ---------- */
    var wrap = document.getElementById('floorWrapper');
    function reindexFloors() {
        var blocks = wrap.querySelectorAll('.floor-block');
        blocks.forEach(function (b, i) {
            b.querySelectorAll('input').forEach(function (inp) { inp.name = inp.name.replace(/\[\d+\]/, '[' + i + ']'); });
        });
        wrap.querySelectorAll('.remove-floor').forEach(function (x) { x.hidden = blocks.length === 1; });
    }
    document.getElementById('addFloor').addEventListener('click', function () {
        var first = wrap.querySelector('.floor-block'), clone = first.cloneNode(true);
        clone.querySelectorAll('input').forEach(function (i) { i.value = ''; });
        clone.querySelectorAll('.lef-err').forEach(function (e) { e.remove(); });
        clone.querySelectorAll('.is-bad').forEach(function (e) { e.classList.remove('is-bad'); });
        wrap.appendChild(clone);
        reindexFloors();
        clone.querySelector('input').focus();
    });
    wrap.addEventListener('click', function (e) {
        var x = e.target.closest('.remove-floor'); if (!x) return;
        x.closest('.floor-block').remove();
        reindexFloors();
    });
    reindexFloors();
    @endunless
});
</script>

@endsection
