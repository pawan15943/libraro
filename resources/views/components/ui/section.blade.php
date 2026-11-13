{{-- iOS-style group: small caps label, then the card. <x-ui.section label="Contact" note="Shown on receipts.">…cards…</x-ui.section> --}}
@props(['label' => null, 'note' => null])
<div {{ $attributes }}>
    @if($label)<div class="ui-section-label">{{ $label }}</div>@endif
    {{ $slot }}
    @if($note)<div class="ui-section-note">{{ $note }}</div>@endif
</div>
