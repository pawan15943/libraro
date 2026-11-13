@extends('layouts.admin')

@section('body_class', 'ax-custom-page')

@section('page_actions')
    <x-ui.button variant="gray" icon="fa-solid fa-arrow-left" :href="route('admin.limo.knowledge')">Knowledge base</x-ui.button>
@endsection

@section('content')
@php($editing = (bool) $entry->id)

<form method="post" action="{{ $editing ? route('admin.limo.knowledge.update', $entry->id) : route('admin.limo.knowledge.store') }}" class="mx-auto max-w-4xl">
    @csrf
    @if ($editing) @method('PUT') @endif

    <p class="ui-subhead mb-1">Write the answer in both languages. Limo shows the language the library asks in, with a switch for the other one.</p>

    <x-ui.section label="When to answer" note="One phrase per line, in any language (English, Hinglish, हिंदी). Longer, specific phrases win over single words.">
        <x-ui.card>
            <x-ui.textarea name="keys" id="laKeys" label="Key phrases" rows="4" required :value="$entry->keys" :placeholder="implode(PHP_EOL, ['increase admissions', 'admission kaise badhaye', 'एडमिशन कैसे बढ़ाएं'])" />
        </x-ui.card>
    </x-ui.section>

    <x-ui.section label="Answer" note="One step or point per line — Limo numbers them.">
        <x-ui.card>
            <div class="grid gap-x-5 gap-y-1 md:grid-cols-2">
                <x-ui.input name="title_en" id="laTitleEn" label="Title (English)" required maxlength="191" :value="$entry->title_en" />
                <x-ui.input name="title_hi" id="laTitleHi" label="Title (हिंदी)" required maxlength="191" lang="hi" :value="$entry->title_hi" />
                <x-ui.textarea name="steps_en" id="laStepsEn" label="Answer (English)" rows="8" required :value="$entry->steps_en" />
                <x-ui.textarea name="steps_hi" id="laStepsHi" label="Answer (हिंदी)" rows="8" required lang="hi" :value="$entry->steps_hi" hint="हर लाइन में एक स्टेप या बात।" />
            </div>
        </x-ui.card>
    </x-ui.section>

    <x-ui.section label="Buttons under the answer" note="Optional. One per line: route.name | English label | Hindi label — a library-panel page Limo should link to.">
        <x-ui.card>
            <x-ui.textarea name="links" id="laLinks" label="Links" rows="3" class="ui-mono" :value="$linksText" placeholder="pending.payment.report | Pending payment report | बकाया रिपोर्ट" />
        </x-ui.card>
    </x-ui.section>

    <x-ui.card class="mt-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <x-ui.switch name="status" :checked="$entry->status">Limo uses this answer</x-ui.switch>
            <div class="flex flex-col-reverse gap-2 sm:flex-row">
                <x-ui.button variant="gray" :href="route('admin.limo.knowledge')">Cancel</x-ui.button>
                <x-ui.button type="submit" icon="fa-solid fa-check">{{ $editing ? 'Save changes' : 'Add answer' }}</x-ui.button>
            </div>
        </div>
    </x-ui.card>
</form>
@endsection
