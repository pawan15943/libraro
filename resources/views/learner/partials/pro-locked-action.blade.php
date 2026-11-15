{{-- A learner option the library's plan does not include (planLocked): same layout as the real one,
     in light grey; a click opens the "available in Pro" modal (partials/pro-plan-modal).
     Params: $proLabel, $proIcon (Font Awesome class), $proFeature (shown in the modal). Uses the
     including partial's $isModalAction / $isMobileAction. --}}
@if(!empty($isModalAction))
    <a href="javascript:;" class="modal-op-item is-pro-locked" data-pro-lock="{{ $proFeature ?? $proLabel }}" title="{{ $proLabel }} · Pro plan">
        <div class="op-icon-circle shadow-sm"><i class="{{ $proIcon }}"></i></div>
        <span class="op-icon-label">{{ $proLabel }}</span>
    </a>
@elseif(!empty($isMobileAction))
    <div class="mobile-action-item is-pro-locked">
        <a href="javascript:;" class="action-btn is-pro-locked" data-pro-lock="{{ $proFeature ?? $proLabel }}">
            <i class="{{ $proIcon }}"></i>
        </a>
        <span class="mobile-action-label">{{ $proLabel }}</span>
    </div>
@else
    <li>
        <a href="javascript:;" class="action-btn is-pro-locked" data-pro-lock="{{ $proFeature ?? $proLabel }}" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="{{ $proLabel }} · Pro plan">
            <i class="{{ $proIcon }}"></i>
        </a>
    </li>
@endif
