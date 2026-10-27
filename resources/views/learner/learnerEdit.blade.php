@extends('layouts.library')

@section('content')
@php
$current_route = Route::currentRouteName();
$planDetails = getPlanStatusDetails($customer->plan_end_date);
$class = $planDetails['class'];

$planEndDate = $customer->plan_end_date;
$today = \Carbon\Carbon::today();

if ($planEndDate) {
    $endDate = \Carbon\Carbon::parse($planEndDate);
    $diffInDays = $today->diffInDays($endDate, false);
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

if ($customer->locker_no) {
    $locker_read = '';
} else {
    $locker_read = 'readonly';
}
@endphp

@php
    $lswName = trim((string) ($customer->name ?? ($customer->learner->name ?? 'Learner')));
    $lswFirst = \Illuminate\Support\Str::of($lswName)->explode(' ')->first() ?: $lswName;
    $lswInitials = collect(explode(' ', $lswName))->filter()->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('') ?: 'L';
    $learnerProfilePic = $customer->profile_picture ?? ($customer->learner->profile_picture ?? null);
    $lswPhoto = ($learnerProfilePic && file_exists(public_path($learnerProfilePic))) ? asset($learnerProfilePic) : null;
    $lswTone = $statusClass === 'status-expired' ? 'bad' : ($statusClass === 'status-warning' ? 'warn' : 'ok');
    $lswCurSeat = $customer->seat_no ?? null;
    $lswCurLabel = $lswCurSeat ? (getSeatDisplayShortFloorName($lswCurSeat) ?? $lswCurSeat) : 'General';
    $lswShift = $customer->plan_type_name ?? 'N/A';
    $hide = toggleHideField();
    $showPhoto = !in_array('8', $hide);
    $showAlt = !in_array('30', $hide);
    $showFather = !in_array('29', $hide);
    $showExam = !in_array('4', $hide);
    $showProof = !in_array('5', $hide);
    $showAddress = !in_array('32', $hide);
    $showRemark = !in_array('31', $hide);
    $proofFile = $customer->id_proof_file ?? null;
    $proofIsImg = $proofFile && preg_match('/\.(jpe?g|png|webp|gif)$/i', $proofFile);
    $showExtra = $showAlt || $showFather || $showExam || $showProof || $showAddress || $showRemark;
@endphp

<link rel="stylesheet" href="{{ asset('public/css/lb-swap.css') }}?v={{ @filemtime(public_path('css/lb-swap.css')) }}" />
<link rel="stylesheet" href="{{ asset('public/css/lb-opform.css') }}?v={{ @filemtime(public_path('css/lb-opform.css')) }}" />

{{-- Image viewer (photo / ID proof) --}}
<div id="imageViewModal" class="lef-viewer" style="display:none;" role="dialog" aria-modal="true" aria-label="Document preview">
    <div class="lef-viewer-box">
        <button type="button" class="close-modal lef-viewer-x" aria-label="Close"><x-lb-icon name="x" /></button>
        <img src="" id="modalImage" alt="Document Preview">
    </div>
</div>

<div class="lsw lsw--wide" id="operationContent">
    <form action="{{ route('learners.update', $customer->id) }}" method="POST" enctype="multipart/form-data" id="editLearnerForm" class="lsw-sheet">
        @csrf
        @method('PUT')
        <input id="edit_seat" type="hidden" name="seat_no" value="{{ old('seat_no', $customer->seat_no) }}">
        <input name="user_id" type="hidden" value="{{ $customer->id }}">
        <input name="learner_id" type="hidden" value="{{ $customer->id }}">
        <input name="plan_id" type="hidden" value="{{ $customer->plan_id }}" id="plan_id10">
        <input name="plan_type_id" type="hidden" value="{{ $customer->plan_type_id }}" id="plan_type_id10">
        <input type="hidden" name="payment_type" value="EDITLEARNER" id="payment_type_operation">

        {{-- header (same as Swap seat) --}}
        <header class="lsw-head">
            <span class="lsw-head-ic"><x-lb-icon name="pencil" /></span>
            <div class="lsw-head-t">
                <span class="lsw-kicker">Edit details</span>
                <h2>Update {{ $lswFirst }}’s profile</h2>
            </div>
            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('learners') }}" class="lsw-close" title="Close" aria-label="Close"><x-lb-icon name="x" /></a>
        </header>

        <div class="lsw-body">
            @if ($errors->any())
            <div class="lef-alert" role="alert"><x-lb-icon name="circle-alert" /><span>Please fix the highlighted {{ $errors->count() > 1 ? $errors->count() . ' fields' : 'field' }} below.</span></div>
            @endif

            {{-- member card --}}
            <section class="lsw-member">
                <div class="lsw-member-top">
                    <span class="lsw-ava" id="lswAvatar">
                        @if($lswPhoto)<img src="{{ $lswPhoto }}" alt="{{ $lswName }}" id="topSeatAvatarImg">@else<span id="lswAvatarTxt">{{ $lswInitials }}</span>@endif
                    </span>
                    <div class="lsw-member-id">
                        <h3><span class="lsw-nm seat-title" id="lswLiveName">{{ $lswName }}</span> <span class="lsw-name-seat"><x-lb-icon name="armchair" />{{ $lswCurSeat ? 'Seat ' . $lswCurLabel : 'General seat' }}</span></h3>
                        <div class="lsw-member-tags">
                            <span class="lsw-uid">UID: {{ $customer->learner_no ?? ('#' . $customer->id) }}</span>
                            <span class="lsw-pill is-{{ $lswTone }}"><x-lb-icon name="{{ $lswTone === 'ok' ? 'circle-check' : ($lswTone === 'bad' ? 'circle-x' : 'clock') }}" />{{ $statusText }}</span>
                        </div>
                    </div>
                </div>
                <div class="lsw-chips">
                    <span class="lsw-chip"><x-lb-icon name="calendar-days" />{{ $customer->plan_name ?? 'Plan' }}</span>
                    <span class="lsw-chip"><x-lb-icon name="clock" />{{ $lswShift }}</span>
                    @if($customer->plan_end_date)
                    <span class="lsw-chip"><x-lb-icon name="calendar-check" />Till {{ \Carbon\Carbon::parse($customer->plan_end_date)->format('d M Y') }}</span>
                    @endif
                </div>
            </section>

            {{-- quick jump between sections --}}
            <nav class="lef-tabs" aria-label="Sections">
                @if($showPhoto)<a href="#lefPhoto" class="lef-tab">Photo</a>@endif
                <a href="#lefPersonal" class="lef-tab">Personal</a>
                <a href="#lefAlerts" class="lef-tab">Reminders</a>
                @if($showExtra)<a href="#lefExtra" class="lef-tab">More details</a>@endif
            </nav>

            {{-- 1. Profile photo --}}
            @if($showPhoto)
            <section class="lef-sec" id="lefPhoto">
                <div class="lef-sec-h">
                    <span class="lef-sec-ic is-violet"><x-lb-icon name="camera" /></span>
                    <div><h4>Profile photo</h4><p>Optional · JPG, PNG or WEBP, up to 5 MB</p></div>
                </div>
                <div class="lef-photo">
                    <button type="button" class="lef-photo-ava" id="avatarUploadTrigger" title="Upload or change photo" aria-label="Upload or change photo">
                        <span class="lef-photo-circle" id="avatarCircle">
                            <span class="lef-photo-ph" id="avatarDefaultIcon" @if($customer->profile_picture) style="display:none;" @endif><x-lb-icon name="user-round" /></span>
                            <img id="avatarCroppedPreview" src="{{ $customer->profile_picture ? asset($customer->profile_picture) : '' }}" alt="Profile photo" @unless($customer->profile_picture) style="display:none;" @endunless>
                        </span>
                        <span class="lef-photo-cam"><x-lb-icon name="camera" /></span>
                    </button>
                    <div class="lef-photo-t">
                        <strong id="avatarStatusTitle">{{ $customer->profile_picture ? 'Photo on file' : 'No photo yet' }}</strong>
                        <p id="avatarSubtext">{{ $customer->profile_picture ? 'Tap the photo to replace it.' : 'Add a clear face photo for the ID card.' }}</p>
                        <div class="lef-photo-acts">
                            <button type="button" class="lef-btn-soft" onclick="document.getElementById('avatarUploadTrigger').click()"><x-lb-icon name="upload" />{{ $customer->profile_picture ? 'Change photo' : 'Upload photo' }}</button>
                            @if($customer->profile_picture)
                            <a href="{{ asset($customer->profile_picture) }}" class="lef-btn-soft view-image" id="viewExistingAvatar"><x-lb-icon name="eye" />View</a>
                            @endif
                        </div>
                    </div>
                    <input type="file" class="d-none image-cropper @error('profile_picture_image') is-invalid @enderror" name="profile_picture_image" id="profile_picture_image" autocomplete="off" accept=".jpeg, .jpg, .png, .webp" />
                    <img class="preview-img d-none" style="display:none !important; visibility:hidden !important; position:absolute !important;" alt="Preview">
                </div>
                @error('profile_picture_image')<p class="lef-err">{{ $message }}</p>@enderror
            </section>
            @endif

            {{-- 2. Personal details --}}
            <section class="lef-sec" id="lefPersonal">
                <div class="lef-sec-h">
                    <span class="lef-sec-ic is-blue"><x-lb-icon name="user-round" /></span>
                    <div><h4>Personal details</h4><p>Name, birthday and how to reach {{ $lswFirst }}</p></div>
                </div>
                <div class="lef-grid">
                    <div class="lef-f">
                        <label for="ed_name">Full name <i>*</i></label>
                        <div class="lef-in"><x-lb-icon name="user-round" /><input type="text" class="@error('name') is-invalid @enderror" placeholder="Full name" name="name" id="ed_name" value="{{ old('name', $customer->name) }}" autocomplete="off" required></div>
                        @error('name')<p class="lef-err">{{ $message }}</p>@enderror
                    </div>
                    <div class="lef-f">
                        <label for="ed_dob">Date of birth <i>*</i></label>
                        <div class="lef-in"><x-lb-icon name="cake" /><input type="date" class="@error('dob') is-invalid @enderror" name="dob" id="ed_dob" value="{{ old('dob', $customer->dob) }}" required></div>
                        @error('dob')<p class="lef-err">{{ $message }}</p>@enderror
                    </div>
                    <div class="lef-f">
                        <label for="ed_mobile">Mobile number <i>*</i></label>
                        <div class="lef-in"><span class="lef-pre">+91</span><input type="tel" class="@error('mobile') is-invalid @enderror digit-only" maxlength="10" minlength="10" inputmode="numeric" placeholder="10-digit number" name="mobile" id="ed_mobile" value="{{ old('mobile', $customer->mobile) }}" required></div>
                        <p class="lef-hint" data-count-for="ed_mobile"></p>
                        @error('mobile')<p class="lef-err">{{ $message }}</p>@enderror
                    </div>
                    <div class="lef-f">
                        <label for="ed_email">Email <span>Optional</span></label>
                        <div class="lef-in"><x-lb-icon name="mail" /><input type="email" class="@error('email') is-invalid @enderror" placeholder="name@example.com" name="email" id="ed_email" value="{{ old('email', $customer->email) }}" autocomplete="off"></div>
                        @error('email')<p class="lef-err">{{ $message }}</p>@enderror
                    </div>
                </div>
            </section>

            {{-- 3. Reminders & expiry --}}
            <section class="lef-sec" id="lefAlerts">
                <div class="lef-sec-h">
                    <span class="lef-sec-ic is-green"><x-lb-icon name="bell" /></span>
                    <div><h4>Reminders &amp; expiry</h4><p>How {{ $lswFirst }} gets fee reminders, and whether the seat expires</p></div>
                </div>
                <div class="lef-grid">
                    <div class="lef-f">
                        <label for="ed_sended_message_type">Send reminders via <span>Optional</span></label>
                        <div class="lef-in is-select"><x-lb-icon name="message-circle" />
                            <select id="ed_sended_message_type" name="sended_message_type">
                                <option value="">Select type</option>
                                @if($hasFreeWaba ?? false)
                                <option value="whatsapp" {{ old('sended_message_type', $customer->sended_message_type) == 'whatsapp' ? 'selected' : '' }}>WhatsApp message only</option>
                                @endif
                                @if($hasFreeText ?? false)
                                <option value="text" {{ old('sended_message_type', $customer->sended_message_type) == 'text' ? 'selected' : '' }}>Text message only</option>
                                @endif
                                @if(($hasFreeWaba ?? false) && ($hasFreeText ?? false))
                                <option value="both" {{ old('sended_message_type', $customer->sended_message_type) == 'both' ? 'selected' : '' }}>Both (WhatsApp &amp; text)</option>
                                @endif
                                <option value="no" {{ old('sended_message_type', $customer->sended_message_type) == 'no' ? 'selected' : '' }}>No reminders</option>
                            </select>
                        </div>
                    </div>
                    <div class="lef-f">
                        <label for="ed_no_expiry">Seat expiry <span>Optional</span></label>
                        <div class="lef-in is-select"><x-lb-icon name="calendar-x" />
                            <select name="no_expiry" id="ed_no_expiry" class="@error('no_expiry') is-invalid @enderror">
                                <option value="">Select expiry mode</option>
                                <option value="1" {{ old('no_expiry', $customer->no_expiry) == 1 ? 'selected' : '' }}>Never expires (non-expiring seat)</option>
                                <option value="0" {{ old('no_expiry', $customer->no_expiry) == 0 ? 'selected' : '' }}>Expires with the plan</option>
                            </select>
                        </div>
                        @error('no_expiry')<p class="lef-err">{{ $message }}</p>@enderror
                    </div>
                </div>
            </section>

            {{-- 4. More details (optional) --}}
            @if($showExtra)
            <section class="lef-sec" id="lefExtra">
                <div class="lef-sec-h">
                    <span class="lef-sec-ic is-amber"><x-lb-icon name="id-card" /></span>
                    <div><h4>More details</h4><p>Optional — family contact, exam, ID proof and address</p></div>
                    <span class="lef-sec-badge">Optional</span>
                </div>
                <div class="lef-grid">
                    @if($showAlt)
                    <div class="lef-f">
                        <label for="ed_alternate_mobile">Alternate mobile</label>
                        <div class="lef-in"><span class="lef-pre">+91</span><input type="tel" class="@error('alternate_mobile') is-invalid @enderror digit-only" name="alternate_mobile" id="ed_alternate_mobile" maxlength="10" minlength="10" inputmode="numeric" placeholder="10-digit number" value="{{ old('alternate_mobile', $customer->alternate_mobile) }}"></div>
                        @error('alternate_mobile')<p class="lef-err">{{ $message }}</p>@enderror
                    </div>
                    @endif
                    @if($showFather)
                    <div class="lef-f">
                        <label for="ed_father_name">Father’s name</label>
                        <div class="lef-in"><x-lb-icon name="users-round" /><input type="text" class="@error('father_name') is-invalid @enderror char-only" name="father_name" id="ed_father_name" placeholder="Father’s name" value="{{ old('father_name', $customer->father_name) }}" autocomplete="off"></div>
                        @error('father_name')<p class="lef-err">{{ $message }}</p>@enderror
                    </div>
                    @endif
                    @if($showExam)
                    <div class="lef-f is-full">
                        <label for="exam_id">Preparing for</label>
                        <div class="lef-in is-select"><x-lb-icon name="graduation-cap" />
                            <select name="exam_id" id="exam_id" class="@error('exam_id') is-invalid @enderror">
                                <option value="">Select exam</option>
                                @foreach($exams as $key => $value)
                                <option value="{{ $value->id }}" {{ old('exam_id', $customer->exam_id) == $value->id ? 'selected' : '' }}>{{ $value->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @error('exam_id')<p class="lef-err">{{ $message }}</p>@enderror
                    </div>
                    @endif
                    @if($showProof)
                    <div class="lef-f">
                        <label for="edit_id_proof_name">ID proof type</label>
                        <div class="lef-in is-select"><x-lb-icon name="id-card" />
                            <select name="id_proof_name" id="edit_id_proof_name" class="@error('id_proof_name') is-invalid @enderror">
                                <option value="">Select ID proof</option>
                                <option value="1" {{ old('id_proof_name', $customer->id_proof_name) == 1 ? 'selected' : '' }}>Aadhar Card</option>
                                <option value="2" {{ old('id_proof_name', $customer->id_proof_name) == 2 ? 'selected' : '' }}>Driving License</option>
                                <option value="4" {{ old('id_proof_name', $customer->id_proof_name) == 4 ? 'selected' : '' }}>Pan Card</option>
                                <option value="5" {{ old('id_proof_name', $customer->id_proof_name) == 5 ? 'selected' : '' }}>Voter Id</option>
                                <option value="3" {{ old('id_proof_name', $customer->id_proof_name) == 3 ? 'selected' : '' }}>Other</option>
                            </select>
                        </div>
                        @error('id_proof_name')<p class="lef-err">{{ $message }}</p>@enderror
                    </div>
                    <div class="lef-f">
                        <label for="edit_id_proof_number">ID proof number</label>
                        <div class="lef-in"><x-lb-icon name="hash" /><input type="text" class="@error('id_proof_number') is-invalid @enderror" name="id_proof_number" id="edit_id_proof_number" placeholder="ID number" maxlength="12" value="{{ old('id_proof_number', $customer->id_proof_number) }}" autocomplete="off"></div>
                        @error('id_proof_number')<p class="lef-err">{{ $message }}</p>@enderror
                    </div>
                    <div class="lef-f is-full">
                        <label for="edit_id_proof_file">ID proof scan <span>Front side · JPG, PNG, WEBP or PDF</span></label>
                        <div class="lef-drop doc-dropzone disabled {{ $proofFile ? 'has-file' : '' }}" id="docDropzone" role="button" tabindex="0" aria-describedby="docDropzoneHint">
                            <div class="lef-drop-in" id="docDropzoneContent" style="{{ $proofFile ? 'display:none;' : '' }}">
                                <span class="lef-drop-ic"><x-lb-icon name="upload" class="lef-ic-up" /><x-lb-icon name="lock" class="lef-ic-lock" /></span>
                                <div>
                                    <strong class="lef-drop-t-on">Drop the file here or <u>browse</u></strong>
                                    <strong class="lef-drop-t-off">Choose the ID proof type first</strong>
                                    <small id="docDropzoneHint">Select ID proof type above to enable upload</small>
                                </div>
                            </div>
                            <div class="lef-drop-file" id="docDropzonePreview" style="{{ $proofFile ? 'display:flex;' : 'display:none;' }}">
                                <span class="lef-drop-thumb" id="docThumb">
                                    @if($proofIsImg)<img src="{{ asset($proofFile) }}" alt="ID proof">@else<x-lb-icon name="file-text" />@endif
                                </span>
                                <div class="lef-drop-meta">
                                    <strong id="docFileName">{{ $proofFile ? basename($proofFile) : '' }}</strong>
                                    <small id="docFileMeta">{{ $proofFile ? 'Saved on file' : '' }}</small>
                                </div>
                                <div class="lef-drop-acts">
                                    <a href="{{ $proofFile ? asset($proofFile) : '#' }}" class="lef-chip-btn view-image" id="viewExistingDoc" target="_blank" rel="noopener" @unless($proofFile) hidden @endunless><x-lb-icon name="eye" /><span>View</span></a>
                                    <button type="button" class="lef-chip-btn" id="removeDocFile"><x-lb-icon name="refresh-cw" /><span>Replace</span></button>
                                </div>
                            </div>
                        </div>
                        <input type="file" class="d-none id_proof_file image-cropper @error('id_proof') is-invalid @enderror" name="id_proof" id="edit_id_proof_file" autocomplete="off" accept=".jpeg, .jpg, .png, .webp, .pdf">
                        <img class="preview-img one d-none" style="display:none !important; visibility:hidden !important; position:absolute !important;" alt="Doc Preview">
                        @error('id_proof')<p class="lef-err">{{ $message }}</p>@enderror
                    </div>
                    @endif
                    @if($showAddress)
                    <div class="lef-f is-full">
                        <label for="ed_address">Address</label>
                        <div class="lef-in is-area"><textarea class="@error('address') is-invalid @enderror" name="address" id="ed_address" rows="2" placeholder="House, street, city">{{ old('address', $customer->address) }}</textarea></div>
                        @error('address')<p class="lef-err">{{ $message }}</p>@enderror
                    </div>
                    @endif
                    @if($showRemark)
                    <div class="lef-f is-full">
                        <label for="ed_remark">Remark <span>Only staff can see this</span></label>
                        <div class="lef-in is-area"><textarea class="@error('remark') is-invalid @enderror" name="remark" id="ed_remark" rows="2" placeholder="Any note about this learner">{{ old('remark', $customer->remark) }}</textarea></div>
                        @error('remark')<p class="lef-err">{{ $message }}</p>@enderror
                    </div>
                    @endif
                </div>
            </section>
            @endif
        </div>

        {{-- footer --}}
        <footer class="lsw-foot">
            <div class="lsw-fee lef-state" id="lefState" data-dirty="0">
                <span id="lefStateK">No changes yet</span>
                <strong id="lefStateV">All saved</strong>
            </div>
            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('learners') }}" class="lsw-btn-ghost">Cancel</a>
            <button type="submit" class="lsw-btn-primary btn-submit-operation" id="editLearnerSubmit">
                <x-lb-icon name="check" /><span>Save changes</span>
            </button>
        </footer>
    </form>
</div>

<script>
$(document).ready(function () {
    var form = document.getElementById('editLearnerForm');

    // ---------- profile photo (cropper is the shared layout one: .image-cropper + sibling .preview-img) ----------
    $('#avatarUploadTrigger').on('click', function (e) { e.preventDefault(); $('#profile_picture_image').trigger('click'); });

    function setAvatarPreview(src, title) {
        if (!src) return;
        $('#avatarCroppedPreview').attr('src', src).show();
        $('#avatarDefaultIcon').hide();
        $('#avatarStatusTitle').text(title || 'Photo selected');
        $('#avatarSubtext').text('It will be saved when you tap Save changes.');
        var $ava = $('#lswAvatar');
        if ($ava.find('img').length) { $ava.find('img').attr('src', src); } else { $ava.html('<img id="topSeatAvatarImg" alt="">'); $ava.find('img').attr('src', src); }
        markDirty();
    }

    // live name in the member card
    $('#ed_name').on('input', function () { $('#lswLiveName').text($(this).val().trim() || 'Learner'); });

    $('#profile_picture_image').on('change', function () {
        var file = this.files && this.files[0];
        if (!file) return;
        var reader = new FileReader();
        reader.onload = function (e) { setAvatarPreview(e.target.result, 'New photo selected'); };
        reader.readAsDataURL(file);
    });

    var profileSibling = document.querySelector('#profile_picture_image ~ .preview-img');
    if (profileSibling) {
        new MutationObserver(function (mutations) {
            mutations.forEach(function (m) {
                if (m.attributeName === 'src') {
                    var src = profileSibling.getAttribute('src');
                    if (src && src !== window.location.href) setAvatarPreview(src, 'New photo ready');
                }
            });
            profileSibling.style.setProperty('display', 'none', 'important');
        }).observe(profileSibling, { attributes: true, attributeFilter: ['src', 'style'] });
    }

    $(document).on('click', '.cropbtn', function () {
        setTimeout(function () {
            var pImg = document.querySelector('#profile_picture_image ~ .preview-img');
            if (pImg) { pImg.style.setProperty('display', 'none', 'important'); }
        }, 100);
    });

    // ---------- ID proof upload (enabled only after a type is picked — same rule as before) ----------
    var hasSavedProof = {{ $proofFile ? 'true' : 'false' }};
    var savedProof = { name: @json($proofFile ? basename($proofFile) : ''), url: @json($proofFile ? asset($proofFile) : ''), img: {{ $proofIsImg ? 'true' : 'false' }} };
    var newProofUrl = null;

    function fmtSize(b) { return b > 1048576 ? (b / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(b / 1024)) + ' KB'; }
    function showProof(o) {
        // o: { name, meta, thumb (url|null), url (view link|null) }
        var $t = $('#docThumb').empty();
        if (o.thumb) { $('<img alt="ID proof">').attr('src', o.thumb).appendTo($t); }
        else { $t.html('<svg class="lb-i" aria-hidden="true"><use href="{{ asset('public/img/lb-icons.svg') }}?v={{ @filemtime(public_path('img/lb-icons.svg')) }}#i-file-text"></use></svg>'); }
        $('#docFileName').text(o.name || 'Document');
        $('#docFileMeta').text(o.meta || '');
        var $v = $('#viewExistingDoc');
        if (o.url) { $v.attr('href', o.url).prop('hidden', false); } else { $v.attr('href', '#').prop('hidden', true); }
        $('#docDropzoneContent').hide();
        $('#docDropzonePreview').css('display', 'flex');
        $('#docDropzone').addClass('has-file');
    }
    function showEmpty() {
        $('#docDropzonePreview').hide();
        $('#docDropzoneContent').show();
        $('#docDropzone').removeClass('has-file');
    }

    function updateIdProofUploadState() {
        var proofSelected = $('#edit_id_proof_name').val();
        var $dropzone = $('#docDropzone'), $fileInput = $('#edit_id_proof_file'), $hint = $('#docDropzoneHint');
        if (!$dropzone.length) return;
        if (proofSelected) {
            $dropzone.removeClass('disabled').attr({ title: 'Click or drag a file to upload', 'aria-disabled': 'false' });
            $fileInput.prop('disabled', false);
            $hint.text('JPG, PNG, WEBP or PDF · up to 5 MB');
        } else {
            $dropzone.addClass('disabled').attr({ title: 'Choose the ID proof type first', 'aria-disabled': 'true' });
            $fileInput.prop('disabled', true);
            $hint.text('Pick Aadhar, PAN, Voter ID… above, then upload its scan');
            if (!hasSavedProof) { $fileInput.val(''); showEmpty(); }
        }
    }
    updateIdProofUploadState();
    $(document).on('change', '#edit_id_proof_name', updateIdProofUploadState);

    function nudgeProofType() {
        var $s = $('#edit_id_proof_name');
        $s.focus().closest('.lef-in').addClass('is-nudge');
        setTimeout(function () { $s.closest('.lef-in').removeClass('is-nudge'); }, 1400);
    }
    function openPicker() { if ($('#docDropzone').hasClass('disabled')) { nudgeProofType(); return; } $('#edit_id_proof_file').trigger('click'); }
    $('#docDropzone').on('click', function (e) {
        if ($(e.target).closest('.lef-drop-acts').length) return;
        if ($(this).hasClass('has-file')) return;              // file card: use View / Replace
        openPicker();
    }).on('keydown', function (e) { if ((e.key === 'Enter' || e.key === ' ') && !$(e.target).closest('.lef-drop-acts').length) { e.preventDefault(); if (!$(this).hasClass('has-file')) openPicker(); } });

    $('#removeDocFile').on('click', function (e) { e.preventDefault(); e.stopPropagation(); openPicker(); });

    $(document).on('change', '#edit_id_proof_file', function () {
        var file = this.files && this.files[0];
        if (!file) return;
        if (newProofUrl) URL.revokeObjectURL(newProofUrl);
        newProofUrl = URL.createObjectURL(file);
        var isImg = /^image\//i.test(file.type);
        showProof({ name: file.name, meta: (isImg ? 'Image' : 'PDF') + ' · ' + fmtSize(file.size) + ' · not saved yet', thumb: isImg ? newProofUrl : null, url: newProofUrl });
        markDirty();
    });

    // after "Crop & Save" the shared cropper replaces the file — show the cropped image
    var docPrev = document.querySelector('.preview-img.one');
    if (docPrev) {
        new MutationObserver(function () {
            docPrev.style.setProperty('display', 'none', 'important');
            var src = docPrev.getAttribute('src');
            var input = document.getElementById('edit_id_proof_file');
            if (src && src.indexOf('blob:') === 0 && input.files && input.files[0]) {
                showProof({ name: $('#docFileName').text() || 'ID proof', meta: 'Cropped image · ' + fmtSize(input.files[0].size) + ' · not saved yet', thumb: src, url: src });
                markDirty();
            }
        }).observe(docPrev, { attributes: true, attributeFilter: ['src', 'style'] });
    }

    var dz = document.getElementById('docDropzone');
    if (dz) {
        ['dragenter', 'dragover'].forEach(function (ev) { dz.addEventListener(ev, function (e) { e.preventDefault(); e.stopPropagation(); if (!dz.classList.contains('disabled')) dz.classList.add('dragover'); }); });
        ['dragleave', 'dragend'].forEach(function (ev) { dz.addEventListener(ev, function (e) { e.preventDefault(); e.stopPropagation(); dz.classList.remove('dragover'); }); });
        dz.addEventListener('drop', function (e) {
            e.preventDefault(); e.stopPropagation(); dz.classList.remove('dragover');
            if (dz.classList.contains('disabled')) { nudgeProofType(); return; }
            var files = e.dataTransfer && e.dataTransfer.files;
            if (files && files.length) {
                var f = files[0];
                if (!/^(image\/(jpe?g|png|webp)|application\/pdf)$/i.test(f.type)) { if (window.lpToast) lpToast('Use a JPG, PNG, WEBP or PDF file', 'error'); return; }
                var input = document.getElementById('edit_id_proof_file'), t = new DataTransfer();
                t.items.add(f); input.files = t.files;
                input.dispatchEvent(new Event('change', { bubbles: true }));   // also opens the crop dialog for images
            }
        });
    }

    // ---------- image viewer ----------
    $(document).on('click', 'a.view-image', function (e) {
        var url = $(this).attr('href') || '';
        if (url === '#') { e.preventDefault(); return; }
        if (/\.pdf$/i.test(url)) return true;
        if (/\.(jpg|jpeg|png|webp)$/i.test(url) || (url.indexOf('blob:') === 0 && $('#docThumb img').length && this.id === 'viewExistingDoc')) { e.preventDefault(); $('#modalImage').attr('src', url); $('#imageViewModal').fadeIn(160); }
    });
    function closeViewer() { $('#imageViewModal').fadeOut(160); $('#modalImage').attr('src', ''); }
    $('.close-modal').on('click', closeViewer);
    $('#imageViewModal').on('click', function (e) { if ($(e.target).is(this)) closeViewer(); });
    $(document).on('keydown', function (e) { if (e.key === 'Escape' && $('#imageViewModal').is(':visible')) closeViewer(); });

    // ---------- unsaved-changes indicator ----------
    var fields = Array.prototype.slice.call(form.querySelectorAll('input:not([type=hidden]):not([type=file]), select, textarea'));
    var initial = {}; fields.forEach(function (f) { initial[f.name || f.id] = f.value; });
    var fileDirty = false;
    function markDirty() { fileDirty = true; refresh(); }
    function refresh() {
        var n = fields.filter(function (f) { return f.value !== initial[f.name || f.id]; }).length + (fileDirty ? 1 : 0);
        var st = document.getElementById('lefState');
        st.setAttribute('data-dirty', n ? '1' : '0');
        document.getElementById('lefStateK').textContent = n ? 'Unsaved changes' : 'No changes yet';
        document.getElementById('lefStateV').textContent = n ? (n === 1 ? '1 field edited' : n + ' fields edited') : 'All saved';
    }
    $(form).on('input change', 'input, select, textarea', refresh);

    // mobile: live 10-digit counter
    var $mob = $('#ed_mobile'), $mh = $('[data-count-for="ed_mobile"]');
    function mobHint() { var l = ($mob.val() || '').length; $mh.text(l && l < 10 ? (10 - l) + ' more digit' + (10 - l > 1 ? 's' : '') : '').toggleClass('is-warn', l > 0 && l < 10); }
    $mob.on('input', mobHint);

    // first invalid field: scroll into view nicely
    form.addEventListener('invalid', function (e) {
        var el = e.target; if (form.__scrolled) return; form.__scrolled = true;
        setTimeout(function () { form.__scrolled = false; }, 400);
        el.closest('.lef-f') && el.closest('.lef-f').classList.add('is-bad');
        el.scrollIntoView({ block: 'center', behavior: 'smooth' });
    }, true);
    $(form).on('input change', '.lef-f.is-bad input, .lef-f.is-bad select', function () { if (this.checkValidity()) $(this).closest('.lef-f').removeClass('is-bad'); });

    // submit: one tap only, spinner instead of the icon
    form.addEventListener('submit', function () {
        var b = document.getElementById('editLearnerSubmit');
        setTimeout(function () { b.disabled = true; b.classList.add('is-busy'); b.querySelector('span').textContent = 'Saving…'; }, 0);
    });
    window.addEventListener('pageshow', function (e) {
        if (!e.persisted) return;
        var b = document.getElementById('editLearnerSubmit'); b.disabled = false; b.classList.remove('is-busy'); b.querySelector('span').textContent = 'Save changes';
    });

    // tabs: highlight the section in view
    var tabs = document.querySelectorAll('.lef-tab');
    if ('IntersectionObserver' in window && tabs.length) {
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (en) { if (en.isIntersecting) tabs.forEach(function (t) { t.classList.toggle('is-on', t.getAttribute('href') === '#' + en.target.id); }); });
        }, { rootMargin: '-40% 0px -55% 0px' });
        document.querySelectorAll('.lef-sec').forEach(function (s) { io.observe(s); });
    }
    $('.lef-tab').on('click', function (e) { var t = document.querySelector(this.getAttribute('href')); if (t) { e.preventDefault(); t.scrollIntoView({ behavior: 'smooth', block: 'start' }); } });
});
</script>

@endsection
