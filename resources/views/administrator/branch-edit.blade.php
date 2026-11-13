@extends('layouts.admin')

@section('body_class', 'ax-custom-page')

@section('page_actions')
    <x-ui.button variant="gray" icon="fa-solid fa-arrow-left" :href="route('library.branches', $branch->library_id)" class="hidden sm:inline-flex">Branches</x-ui.button>
    <x-ui.button type="submit" form="branchUpdate" icon="fa-solid fa-check">Save</x-ui.button>
@endsection

@push('styles')
<style>
    .be-logo { width: 132px; height: 132px; border-radius: var(--r-xl); background: var(--fill-4); box-shadow: var(--sh-0); overflow: hidden; display: grid; place-items: center; margin: 0 auto; }
    .be-logo img { width: 100%; height: 100%; object-fit: cover; }
    .be-status { font-size: 12.5px; margin: 8px 0 0; text-align: center; color: var(--label-3); min-height: 1em; }
    .be-status.success { color: var(--green); }
    .be-status.error { color: var(--red); }
</style>
@endpush

@section('content')
<x-ui.flash />

<form method="POST" action="{{ route('library.branch.update', $branch->id) }}" enctype="multipart/form-data" id="branchUpdate">
    @csrf
    @method('PUT')

    <div class="grid gap-x-5 lg:grid-cols-[minmax(0,1fr)_320px] lg:items-start">
        <div class="min-w-0">
            <x-ui.section label="Branch">
                <x-ui.card>
                    <div class="ui-fields sm:grid-cols-2 xl:grid-cols-3">
                        <x-ui.input name="name" label="Branch name" :value="$branch->name" required />
                        <x-ui.input name="display_name" label="Display name" :value="$branch->display_name" required />
                        <x-ui.input type="date" name="founder_day" label="Founded on" :value="$branch->founder_day" />
                        <x-ui.input type="email" name="email" label="Email" :value="$branch->email" icon="fa-regular fa-envelope" required />
                        <x-ui.input type="tel" name="mobile" label="Mobile" maxlength="10" :value="$branch->mobile" prefix="+91" required />
                        <x-ui.input name="upi_id" label="UPI ID" :value="$branch->upi_id" placeholder="name@bank" />
                    </div>
                </x-ui.card>
            </x-ui.section>

            <x-ui.section label="Profile">
                <x-ui.card>
                    <div class="ui-fields sm:grid-cols-2">
                        <div class="ui-span-all"><x-ui.textarea name="working_days" label="Working days" :value="$branch->working_days" rows="2" /></div>
                        <div class="ui-span-all"><x-ui.textarea name="description" label="Description" :value="$branch->description" rows="4" /></div>
                        <x-ui.select name="library_category" label="Category" :options="['Public' => 'Public', 'Private' => 'Private']" :value="$branch->library_category" placeholder="Select category" />
                        <x-ui.input type="number" name="fixed_billing_date" label="Fixed billing date" min="1" max="31" :value="$branch->fixed_billing_date" hint="Day of the month, 1–31" />
                    </div>
                </x-ui.card>
            </x-ui.section>

            <x-ui.section label="Address & location">
                <x-ui.card>
                    <div class="ui-fields sm:grid-cols-3">
                        <div class="ui-span-all"><x-ui.textarea name="library_address" label="Address" :value="$branch->library_address" rows="2" required /></div>
                        <x-ui.select name="state_id" id="state_id" label="State" :options="collect($states)->pluck('state_name', 'id')" :value="$branch->state_id" placeholder="Select state" required />
                        <x-ui.select name="city_id" id="city_id" label="City" :options="collect($cities)->pluck('city_name', 'id')" :value="$branch->city_id" placeholder="Select city" required />
                        <x-ui.input name="library_zip" label="PIN code" maxlength="6" inputmode="numeric" :value="$branch->library_zip" required />
                        <div class="ui-span-all"><x-ui.textarea name="google_map" label="Google Maps embed URL" :value="$branch->google_map" rows="2" /></div>
                        <x-ui.input name="latitude" label="Latitude" :value="$branch->latitude" />
                        <x-ui.input name="longitude" label="Longitude" :value="$branch->longitude" />
                    </div>
                </x-ui.card>
            </x-ui.section>

            <x-ui.section label="Settings">
                <x-ui.card>
                    <div class="ui-fields sm:grid-cols-3">
                        <x-ui.input type="number" name="locker_amount" label="Locker amount" min="0" prefix="₹" :value="$branch->locker_amount" />
                        <x-ui.input type="number" name="extend_days" label="Extend days" min="0" max="30" :value="$branch->extend_days" />
                        <x-ui.input type="number" name="token_money" label="Token money" min="0" prefix="₹" :value="$branch->token_money" />
                    </div>
                </x-ui.card>
            </x-ui.section>

            <x-ui.section label="Features">
                <x-ui.card>
                    @php $selectedFeatures = old('features', $branch->features ?? []); @endphp
                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach($features as $feature)
                            <x-ui.check name="features[]" :value="$feature->id" id="feature_{{ $feature->id }}" :checked="in_array($feature->id, $selectedFeatures ?? [])">{{ $feature->name }}</x-ui.check>
                        @endforeach
                    </div>
                </x-ui.card>
            </x-ui.section>

            <x-ui.section label="Gallery" note="Up to 4 photos in total. Tick a photo to remove it when you save.">
                <x-ui.card>
                    @php $existingImages = $branch->library_images ?? []; @endphp
                    @if(!empty($existingImages))
                        <div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                            @foreach($existingImages as $image)
                                <label class="block cursor-pointer">
                                    <img src="{{ asset('public/' . $image) }}" alt="" class="aspect-[4/3] w-full rounded-md object-cover shadow-0">
                                    <span class="ui-check mt-2"><input type="checkbox" name="deleted_images[]" value="{{ $image }}"><span>Remove</span></span>
                                </label>
                            @endforeach
                        </div>
                    @endif
                    <x-ui.input type="file" name="library_images[]" label="Add photos" accept="image/jpeg,image/png,image/webp" multiple />
                </x-ui.card>
            </x-ui.section>
        </div>

        {{-- Logo + save --}}
        <x-ui.section label="Logo" class="lg:sticky lg:top-20">
            <x-ui.card>
                <div class="be-logo" id="preview">
                    @if($branch->library_logo)
                        <img src="{{ asset('public/' . $branch->library_logo) }}" alt="Logo" class="preview">
                    @else
                        <img src="{{ asset('public/img/user.png') }}" alt="No logo" class="preview">
                    @endif
                </div>
                <div class="ui-meter mt-4"><i id="progressBar" style="width:0"></i></div>
                <p class="be-status" id="statusMessage">{{ $branch->library_logo ? '' : 'No logo uploaded' }}</p>
                <x-ui.button variant="gray" block icon="fa-solid fa-upload" class="mt-2" onclick="document.getElementById('fileInput').click()">Choose logo</x-ui.button>
                <input type="file" class="hidden no-validate @error('library_logo') is-invalid @enderror" name="library_logo" id="fileInput" accept="image/jpeg, image/png, image/webp">
                @error('library_logo')<div class="ui-error"><i class="fa-solid fa-circle-exclamation"></i>{{ $message }}</div>@enderror
                <div id="logoUploadError" class="ui-error empty:hidden"></div>
                <p class="ui-hint mt-3">Square, about 250 × 250 px — JPG, PNG or WEBP, up to 2 MB. Shown in the account and on receipts.</p>
                <x-slot:footer>
                    <x-ui.button type="submit" block icon="fa-solid fa-check">Save branch</x-ui.button>
                </x-slot:footer>
            </x-ui.card>
        </x-ui.section>
    </div>
</form>

<script>
$(document).ready(function () {
    const maxLogoSize = 2 * 1024 * 1024; // 2 MB

    $('#fileInput').on('change', function (event) {
        let file = event.target.files[0];
        let validTypes = ["image/jpeg", "image/png", "image/webp"];
        let statusMessage = $('#statusMessage');
        let preview = $('#preview');
        let progressBar = $('#progressBar');

        statusMessage.text('').removeClass('success error');
        $('#logoUploadError').html('');
        progressBar.width('0');

        if (!file) return;

        if (!validTypes.includes(file.type)) {
            statusMessage.text('Invalid file format. Only JPG, PNG, JPEG, WEBP allowed.').addClass('error');
            return;
        }
        if (file.size > maxLogoSize) {
            $('#logoUploadError').html('Image size should not exceed 2 MB.');
            return;
        }

        let reader = new FileReader();
        reader.onload = function (e) {
            preview.html('<img src="' + e.target.result + '" alt="Preview" class="preview">');

            let progress = 0;
            let interval = setInterval(function () {
                progress += 20;
                progressBar.width(progress + '%');
                if (progress >= 100) {
                    clearInterval(interval);
                    statusMessage.text('Upload Successful!').addClass('success');
                }
            }, 200);
        };
        reader.readAsDataURL(file);
    });

    $('#branchUpdate').on('submit', function () {
        const fileInput = $('#fileInput')[0];
        if (fileInput.files.length > 0 && fileInput.files[0].size > maxLogoSize) {
            $('#logoUploadError').html('Image size should not exceed 2 MB.');
            return false;
        }
    });
});
</script>

<script>
document.getElementById('state_id').addEventListener('change', function () {
    const stateId = this.value;
    const citySelect = document.getElementById('city_id');
    citySelect.innerHTML = '<option value="">Loading...</option>';

    fetch('{{ route("cityGetStateWise") }}?state_id=' + encodeURIComponent(stateId), {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function (res) { return res.json(); })
    .then(function (cities) {
        citySelect.innerHTML = '<option value="">Select City</option>';
        Object.keys(cities).forEach(function (id) {
            const opt = document.createElement('option');
            opt.value = id;
            opt.textContent = cities[id];
            citySelect.appendChild(opt);
        });
    });
});
</script>
@endsection
