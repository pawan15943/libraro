
<div class="row">
    @php $lpHeadRight = trim($__env->yieldPushContent('page_head_right')); @endphp
    <div class="d-flex bradcrumb {{ $lpHeadRight !== '' ? 'has-right' : '' }}">
        {{-- "Plan (Duration)": the part in brackets explains the page, shown smaller --}}
        @php [$lpTitle, $lpTitleNote] = array_pad(preg_match('/^(.*?)\s*\((.+)\)\s*$/', (string) ($pageTitle ?? ''), $lpM) ? [$lpM[1], $lpM[2]] : [$pageTitle ?? ''], 2, null); @endphp
        @php
            // Back (right of the title) only on operation pages on one learner (edit, renew, swap,
            // payment, …; same list as AppServiceProvider::withLearnerCrumb): the page you came from,
            // else the previous step in the trail, else the dashboard. Listing pages keep their own header.
            $lpShowBack = request()->routeIs('learners.edit.plan', 'learners.edit', 'learners.swap', 'learner.change.plan',
                'learners.reactive', 'learner.payment', 'learners.upgrade', 'learner.renew.plan', 'learner.other.payment',
                'learner.pending.payment', 'learners.settlement.page', 'learners.transactions', 'learners.transactions.edit',
                'learners.booking.done', 'idCard');
            if ($lpShowBack) {
                $lpPrev = url()->previous();
                $lpPrevOk = $lpPrev && $lpPrev !== url()->current() && str_starts_with($lpPrev, url('/')) && !preg_match('#/(login|logout)\b#', $lpPrev);
                $lpTrailBack = collect($breadcrumb ?? [])->slice(0, -1)->filter(fn ($l) => $l && $l !== '#')->last();
                $lpBackUrl = $lpPrevOk ? $lpPrev : ($lpTrailBack ?: (\Illuminate\Support\Facades\Route::has('library.home') ? route('library.home') : url('/')));
            }
        @endphp
        <div class="lp-title-row">
            <h4>{{ $lpTitle }}@if($lpTitleNote)<small class="lp-title-note">{{ $lpTitleNote }}</small>@endif</h4>
            @if($lpShowBack)
            <a href="{{ $lpBackUrl }}" class="lp-back" title="Back" aria-label="Back"><x-lb-icon name="arrow-left" /><span>Back</span></a>
            @endif
        </div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
               @if(isset($breadcrumb))
             
                @foreach($breadcrumb as $name => $link)
                @if($loop->last)
                <li class="breadcrumb-item active" aria-current="page">{{ $name }}</li>
                @else
                <li class="breadcrumb-item"><a href="{{ $link }}">{{ $name }}</a></li>
                @endif
                @endforeach
                   
               @else
                
               @endif
            </ol>
        </nav>
        {{-- a page can put a small card on the right of its title: @push('page_head_right') --}}
        @if($lpHeadRight !== '')<div class="lp-head-right">{!! $lpHeadRight !!}</div>@endif
    </div>
</div>
<script>
// The page's own header (title + buttons) has its title hidden in favour of the one above;
// lift its buttons into this title row so they sit level with the title, as before.
document.addEventListener('DOMContentLoaded', function () {
    var row = document.querySelector('.content .bradcrumb .lp-title-row');
    if (!row || row.querySelector('.lp-title-acts')) return;
    var head = document.querySelector('.content .lpg-head, .content .lml-head, .content .lsm-head');
    if (!head) return;
    var acts = head.querySelector(':scope > .lpg-head-r, :scope > .lml-head-r, :scope > .lsm-head-r');
    if (!acts) return;
    acts.classList.add('lp-title-acts');
    var back = row.querySelector('.lp-back');
    row.insertBefore(acts, back || null);
    // whatever the page header still shows (e.g. a description line) stays where it is
    var rest = Array.prototype.filter.call(head.children, function (el) {
        return getComputedStyle(el).display !== 'none';
    });
    if (!rest.length) head.style.display = 'none';
});
</script>