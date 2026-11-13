@if($lead->next_follow_up_at)
    @php
        $f = $lead->next_follow_up_at;
        $cls = $f->isPast() ? 'is-overdue' : ($f->isToday() ? 'is-today' : '');
        $label = $f->isToday() ? 'Today '.$f->format('g:i A') : ($f->isTomorrow() ? 'Tomorrow '.$f->format('g:i A') : $f->format('j M, g:i A'));
    @endphp
    <span class="crm-follow {{ $cls }}" title="Next follow-up"><i class="fa-regular fa-clock"></i>{{ $f->isPast() ? 'Overdue · ' : '' }}{{ $label }}</span>
@endif
