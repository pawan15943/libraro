{{-- Paid more than the bill: is the extra an advance or a higher price for this plan? (docs/LEARNER_ADVANCE.md)
     Only when the branch allows a higher price and the user has "Charge Above Plan Price"; otherwise
     the extra is always an advance. Shown by the page script (lpExtraAs()) only while there is an extra.
     $exId: unique suffix for the ids --}}
@if(extraAsPriceAllowed())
<div class="lb-extra" data-extra-as hidden>
    <span class="lb-extra-k">Extra <b data-extra-amt>₹0</b> is</span>
    <div class="lb-extra-opts" role="radiogroup">
        <label><input type="radio" name="extra_as" value="advance" checked><span><b>Advance</b><small>used on the next renewal</small></span></label>
        <label><input type="radio" name="extra_as" value="price"><span><b>Higher price</b><small>this plan costs <em data-extra-total>₹0</em></small></span></label>
    </div>
</div>
@endif
