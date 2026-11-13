@extends('layouts.admin')

@section('body_class', 'ax-custom-page')

@section('page_actions')
    <x-ui.button variant="gray" icon="fa-solid fa-arrow-left" :href="route('library')" class="hidden sm:inline-flex">Libraries</x-ui.button>
@endsection

@section('content')
<x-ui.flash />

<form action="{{ route('library.store') }}" class="validateForm max-w-3xl" method="POST" enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="terms" value="1">

    <x-ui.section label="Library" note="A verification code is sent to this email after you add the library.">
        <x-ui.card>
            <x-ui.input name="library_name" label="Library name" class="char-only" placeholder="e.g. Gyan Deep Library" icon="fa-solid fa-building-columns" required />
            <div class="ui-fields sm:grid-cols-2">
                <x-ui.input type="email" name="email" label="Email" icon="fa-regular fa-envelope" placeholder="library@example.com" required />
                <x-ui.input type="tel" name="library_mobile" label="Mobile" class="digit-only" maxlength="10" prefix="+91" placeholder="10 digit number" required />
            </div>
            <x-ui.textarea name="library_address" label="Address" rows="2" class="no-validate" placeholder="Street, area, city" />
        </x-ui.card>
    </x-ui.section>

    <x-ui.section label="Owner" note="Optional — used for WhatsApp updates and the owner's login help.">
        <x-ui.card>
            <x-ui.input name="library_owner" label="Owner name" class="char-only no-validate" icon="fa-regular fa-user" />
            <div class="ui-fields sm:grid-cols-2">
                <x-ui.input type="email" name="library_owner_email" label="Owner email" class="no-validate" icon="fa-regular fa-envelope" />
                <x-ui.input type="tel" name="library_owner_contact" label="Owner WhatsApp" class="digit-only no-validate" maxlength="10" prefix="+91" />
            </div>
            <x-slot:footer>
                <x-ui.button variant="gray" :href="route('library')">Cancel</x-ui.button>
                <x-ui.button type="submit" icon="fa-solid fa-plus">{{ isset($library) ? 'Update Library' : 'Add Library' }}</x-ui.button>
            </x-slot:footer>
        </x-ui.card>
    </x-ui.section>
</form>

@include('library.script')
@endsection
