{{-- Renewals = two views of the same job: who is about to expire, and who already did (grace period). --}}
<nav class="lpg-seg rpt-tabs" aria-label="Renewals">
    @can('has-permission', 'Upcoming Payment Report')
        <a href="{{ route('upcoming.payment.report') }}" class="lpg-seg-b {{ $on === 'soon' ? 'is-on' : '' }}" @if($on === 'soon') aria-current="page" @endif><x-lb-icon name="calendar-clock" />Expiring soon</a>
    @endcan
    @can('has-permission', 'Pending Payment Report')
        <a href="{{ route('pending.payment.report') }}" class="lpg-seg-b {{ $on === 'grace' ? 'is-on' : '' }}" @if($on === 'grace') aria-current="page" @endif><x-lb-icon name="hourglass" />In grace period</a>
    @endcan
    <a href="{{ route('expired.learner.report') }}" class="lpg-seg-b {{ $on === 'lost' ? 'is-on' : '' }}" @if($on === 'lost') aria-current="page" @endif><x-lb-icon name="user-x" />Expired</a>
</nav>
