{{-- The learner's advance (extra paid earlier) on the renew / upgrade / reactivate pages: ticked = it pays
     this bill, the rest is what to collect. Read by calculatePaidAmount() / calculatePending() in
     learner/script.blade.php. docs/LEARNER_ADVANCE.md --}}
@php $advBal = app(\App\Services\LearnerAdvanceService::class)->balance((int) $learnerId); @endphp
<input type="hidden" id="advance_balance10" value="{{ $advBal }}">
@if($advBal > 0)
<div class="col-12">
    <label class="lb-adv" id="pageAdvance">
        <input type="hidden" name="use_advance" value="0">
        <input type="checkbox" name="use_advance" value="1" id="use_advance10" @checked(old('use_advance', '1') !== '0')>
        <span class="lb-adv-t"><b>Use advance</b><small>₹{{ number_format($advBal) }} paid earlier by this learner</small></span>
        <em data-adv="use">− ₹0</em>
    </label>
</div>
@endif
