@php
    // phone tab bar: the first four screens this admin may open, in this order
    $axTabs = collect([
        ['label' => 'Home', 'icon' => 'fa-solid fa-gauge-high', 'route' => 'home', 'active' => ['home'], 'perm' => 'view-dashboard'],
        ['label' => 'Calls', 'icon' => 'fa-solid fa-phone-volume', 'route' => 'crm.queue', 'active' => ['crm.queue'], 'perm' => 'crm-view-leads'],
        ['label' => 'Follow-ups', 'icon' => 'fa-regular fa-bell', 'route' => 'crm.reminders.index', 'active' => ['crm.reminders.*'], 'perm' => 'crm-view-leads', 'badge' => true],
        ['label' => 'Leads', 'icon' => 'fa-solid fa-address-book', 'route' => 'crm.leads.index', 'active' => ['crm.leads.*'], 'perm' => 'crm-view-leads'],
        ['label' => 'Stats', 'icon' => 'fa-solid fa-chart-line', 'route' => 'crm.dashboard', 'active' => ['crm.dashboard'], 'perm' => 'crm-view-dashboard'],
        ['label' => 'Libraries', 'icon' => 'fa-solid fa-building-columns', 'route' => 'library', 'active' => ['library', 'library.show', 'library.branches', 'library.branch.*', 'library.payment', 'library.create'], 'perm' => 'manage-libraries'],
        ['label' => 'Plans', 'icon' => 'fa-solid fa-layer-group', 'route' => 'admin.subscriptions', 'active' => ['admin.subscriptions*', 'subscriptions.edit*'], 'perm' => 'view-subscriptions'],
    ])->filter(fn ($t) => \App\Support\AdminNav::canOpen($t['route']) && (empty($t['perm']) || Gate::allows('has-permission', [$t['perm']])))->take(4);
@endphp
<nav class="ax-tabbar" aria-label="Quick navigation">
    @foreach($axTabs as $tab)
        @php $axTabOn = request()->routeIs(...$tab['active']); @endphp
        <a href="{{ route($tab['route']) }}" class="{{ $axTabOn ? 'on' : '' }}" @if($axTabOn) aria-current="page" @endif>
            <span class="ax-tab-ico" aria-hidden="true"><i class="{{ $tab['icon'] }}"></i>@if(!empty($tab['badge']))<b class="ax-badge" data-crm-due-badge hidden></b>@endif</span>
            <span>{{ $tab['label'] }}</span>
        </a>
    @endforeach
    <button type="button" id="axTabMore" aria-controls="axSidebar" aria-expanded="false"><span class="ax-tab-ico" aria-hidden="true"><i class="fa-solid fa-ellipsis"></i></span><span>More</span></button>
</nav>
