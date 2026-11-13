{{-- Call / WhatsApp / SMS / Log call / Follow-up buttons for one lead. $size: is-compact (table rows) | is-large (lead page) --}}
<div class="crm-actions {{ $size ?? '' }}">
    @if($crm['can']['call'])
        <a href="tel:+91{{ $lead->mobile }}" class="ui-btn crm-call" data-crm-call title="Call +91 {{ $lead->mobile }}"><i class="fa-solid fa-phone"></i><span>Call</span></a>
    @endif
    @if($crm['can']['message'])
        <button type="button" class="ui-btn ui-btn--icon crm-wa" data-crm-open="message" data-channel="whatsapp" title="WhatsApp" aria-label="WhatsApp"><i class="fa-brands fa-whatsapp"></i></button>
        <button type="button" class="ui-btn ui-btn--gray ui-btn--icon" data-crm-open="message" data-channel="sms" title="Text message" aria-label="Text message"><i class="fa-regular fa-comment-dots"></i></button>
    @endif
    @if($crm['can']['call'])
        <button type="button" class="ui-btn ui-btn--gray ui-btn--icon" data-crm-open="call" title="Log a call" aria-label="Log a call"><i class="fa-solid fa-clipboard-check"></i></button>
    @endif
    @if($crm['can']['remind'])
        <button type="button" class="ui-btn ui-btn--gray ui-btn--icon" data-crm-open="reminder" title="Set follow-up" aria-label="Set follow-up"><i class="fa-regular fa-bell"></i></button>
    @endif
</div>
